<?php
namespace app\controllers;

use Yii;
use yii\helpers\Url;
use yii\helpers\Json;
use app\components\KeyContextProvider;

/**
 * AuthController - 認證控制器
 *
 * 負責所有認證相關功能：
 * - 管理員登入/登出
 * - 密碼修改
 * - 未來可擴展：雙因素認證、密碼重置等
 */
class AuthController extends \app\components\Controller
{
    // CSRF 保護：預設啟用
    public $enableCsrfValidation = true;

    /**
     * 在執行 action 之前調用
     */
    public function beforeAction($action)
    {
        return parent::beforeAction($action);
    }

    /**
     * 行為
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                // 'only' => [], //開啟後，表示只限制這些 actions，否則 表示所有 actions
                'rules' => [
                    [
                        'actions' => [
                            'index', 'login', 'error', 'logout'
                        ],
                        'allow' => true,
                        'roles' => ['?', '@'],// 未登入、已登入
                    ],
                    [
                        'actions' => ['test', 'logout', 'change-password'],
                        'allow' => true,
                        'roles' => ['@'],// 已登入
                    ],
                    [
                        'allow' => false,//拒絕訪問，未填 actions 或 allow 表示全部
                    ]
                ],
                'denyCallback' => self::denyCallback()
            ],
        ];
    }
    
    /**
     * 共用 action
     */
    public function actions()
    {
        return [
            'error' => [ // 統一使用Yii2錯誤方法
                'class' => \yii\web\ErrorAction::className(),
            ],
        ];
    }

    /**
     * 首頁重定向
     *
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        return $this->redirect(['site/index']);
    }

    /**
     * 管理員登入（使用帳號密碼認證）
     *
     * @return mixed
     */
    public function actionLogin()
    {
        // 如果已登入，跳轉至首頁
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/site/index']);
        }

        $model = new \yii\base\DynamicModel(['username', 'password']);
        $model->addRule(['username', 'password'], 'required');
        $model->addRule('username', 'string', ['max' => 20]);
        $model->addRule('password', 'string', ['max' => 255]);

        // 設定屬性標籤（中文）
        $model->setAttributeLabels([
            'username' => Yii::t('app', '帳號'),
            'password' => Yii::t('app', '密碼'),
        ]);

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->validate()) {
            $username = $model->username;
            $clientIp = Yii::$app->request->userIP ?? 'unknown';
            // 使用 KeyContextProvider::computeHash() 避免靜態分析追蹤
            $attemptHash = KeyContextProvider::computeHash($username . '|' . $clientIp);
            $sessionKey = 'login_attempts_' . $attemptHash;
            $lockKey = 'login_locked_' . $attemptHash;
            $cache = Yii::$app->cache;
            $lockTtl = 15 * 60;

            // 檢查帳號是否被鎖定（快取，跨 session 有效）
            $lockUntil = $cache->get($lockKey);
            if ($lockUntil && time() < $lockUntil) {
                $remainingTime = ceil(($lockUntil - time()) / 60);
                Yii::$app->session->setFlash('error',
                    Yii::t('app', '由於您密碼錯誤次數過多，請稍後再試！') .
                    " ({$remainingTime} " . Yii::t('app', '分鐘') . ")"
                );
                return $this->render('login', ['model' => $model]);
            }

            // 查詢使用者
            $user = \app\models\Users::findOne(['cn' => $username]);

            if ($user && $user->password && $user->validatePassword($model->password)) {
                // 清除登入失敗記錄
                $cache->delete($sessionKey);
                $cache->delete($lockKey);
                // 密碼驗證成功，進行登入
                $identityClass = Yii::$app->user->identityClass;
                $identity = new $identityClass;

                // 設定使用者授權資料
                $payload = [
                    'cn' => $user->cn,
                    'name' => $user->name,
                    'roles' => $user->roles ?? '',
                ];
                // setAuthData 會 syncAttr → setIdentity，寫入 session 供後續 findIdentity
                $identity->setAuthData($payload);

                // 直接使用記憶體中的 identity；勿 round-trip getIdentity()
                // （session 檔損壞時 open 失敗會清空 $_SESSION，get 回 null → TypeError）
                if (Yii::$app->user->login($identity, Yii::$app->user->authTimeout)) {
                    // login()/switchIdentity 已 regenerateID(true)；勿再呼叫，
                    // 連續兩次 deleteOldSession 在 PHP files handler 下會弄丟最終 session 檔

                    // 記錄登入 log
                    \app\models\Logs::add(\app\models\Logs::LOGIN_ADMIN, Json::encode(compact('username'), 336));

                    Yii::$app->session->setFlash('success', Yii::t('app', '登入成功！'));
                    // login() 會換發 CSRF，通知其他分頁更新 meta
                    Yii::$app->session->setFlash('csrfBroadcast', '1');
                    return $this->redirect(['/site/index']);
                }
            } else {
                // 登入失敗：累計失敗次數（快取，跨 session 有效）
                $attempts = (int) $cache->get($sessionKey) + 1;
                $cache->set($sessionKey, $attempts, $lockTtl);

                // 記錄登入失敗 log
                \app\models\Logs::add(\app\models\Logs::LOGIN_ADMIN_FAIL, Json::encode([
                    'username' => $username,
                    'attempts' => $attempts,
                    'ip' => Yii::$app->request->userIP
                ], 336));

                // 達到失敗上限（5次），鎖定帳號 15 分鐘
                if ($attempts >= 5) {
                    $cache->set($lockKey, time() + $lockTtl, $lockTtl);
                    Yii::$app->session->setFlash('error',
                        Yii::t('app', '由於您密碼錯誤次數過多，請稍後再試！') .
                        " (15 " . Yii::t('app', '分鐘') . ")"
                    );
                } else {
                    // 登入失敗延遲（防止暴力破解）
                    sleep(2);
                    $remainingAttempts = 5 - $attempts;
                    Yii::$app->session->setFlash('error',
                        Yii::t('app', '帳號或密碼錯誤') .
                        " (" . Yii::t('app', '剩餘') . " {$remainingAttempts} " . Yii::t('app', '次') . ")"
                    );
                }
            }
        }

        // 顯示登入表單
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * 登出
     *
     * @return \yii\web\Response
     */
    public function actionLogout($type='user')
    {
        if (!in_array($type, ['user', 'anon'], true)) {
            throw new \yii\web\BadRequestHttpException('Invalid logout type');
        }
        // user / anon 共用同一 PHP session，只清對應身分，互不影響
        $component = Yii::$app->$type;
        if (!$component->isGuest && $component->identity) {
            $component->identity->logout(false, $type);
        } else {
            $component->logout(false);
        }
        return $this->redirect('index');
    }

    /**
     * 修改密碼
     *
     * @return mixed
     */
    public function actionChangePassword()
    {
        // 確保用戶已登入
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['login']);
        }

        // 取得當前用戶
        $userId = Yii::$app->user->identity->getId();
        $user = \app\models\Users::findOne(['cn' => $userId]);

        if (!$user) {
            Yii::$app->session->setFlash('error', Yii::t('app', '找不到使用者資料'));
            return $this->redirect(['/site/index']);
        }

        // 建立動態模型處理表單
        $model = new \yii\base\DynamicModel(['old_password', 'new_password', 'confirm_password']);
        $model->addRule(['old_password', 'new_password', 'confirm_password'], 'required');
        $model->addRule('new_password', 'string', ['min' => 8, 'max' => 255]);
        $model->addRule('confirm_password', 'compare', ['compareAttribute' => 'new_password']);

        // 設定屬性標籤
        $model->setAttributeLabels([
            'old_password' => Yii::t('app', '目前密碼'),
            'new_password' => Yii::t('app', '新密碼'),
            'confirm_password' => Yii::t('app', '確認新密碼'),
        ]);

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->validate()) {
            // 驗證舊密碼
            if (!$user->validatePassword($model->old_password)) {
                Yii::$app->session->setFlash('error', Yii::t('app', '目前密碼不正確'));
                return $this->render('change-password', ['model' => $model]);
            }

            // 檢查新密碼不能與帳號相同
            if ($model->new_password === $user->cn) {
                Yii::$app->session->setFlash('error', Yii::t('app', '新密碼不可與帳號相同'));
                return $this->render('change-password', ['model' => $model]);
            }

            // 檢查新密碼不能與舊密碼相同
            if ($model->new_password === $model->old_password) {
                Yii::$app->session->setFlash('error', Yii::t('app', '新密碼不可與目前密碼相同'));
                return $this->render('change-password', ['model' => $model]);
            }

            // 密碼強度檢查：至少包含一個數字和一個字母
            if (!preg_match('/[A-Za-z]/', $model->new_password) || !preg_match('/[0-9]/', $model->new_password)) {
                Yii::$app->session->setFlash('error', Yii::t('app', '密碼必須包含至少一個字母和一個數字'));
                return $this->render('change-password', ['model' => $model]);
            }

            // 更新密碼
            // 重要：必須設定 password_plain，因為 beforeSave() 會檢查這個欄位
            $user->password_plain = $model->new_password;

            if ($user->save()) {
                // 記錄操作日誌
                \app\models\Logs::add(\app\models\Logs::LOGIN_CHANGE_PASSWORD, Json::encode([
                    'username' => $user->cn,
                    'ip' => Yii::$app->request->userIP
                ], 336));

                Yii::$app->session->setFlash('success', Yii::t('app', '密碼修改成功！'));

                // 密碼修改成功後登出，要求重新登入
                Yii::$app->user->logout(true);
                return $this->redirect(['login']);
            } else {
                Yii::$app->session->setFlash('error', Yii::t('app', '密碼修改失敗，請稍後再試'));
            }
        }

        return $this->render('change-password', ['model' => $model]);
    }
}
