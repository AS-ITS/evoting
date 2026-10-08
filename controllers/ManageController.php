<?php

namespace app\controllers;

use Yii;
use yii\db\Query;
use app\models\Logs;
use app\models\Votes;
use yii\helpers\Json;
use app\models\Config;
use app\models\Logins;
use app\models\Parties;
use app\models\Passwords;
use app\components\helper\ArrayHelper;
use app\components\Controller;
use app\components\SessionInvalidator;
use app\models\Users;

class ManageController extends Controller
{
    /**
     * 行為
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        // 不設定actions表示全部
                        'allow' => true,
                        'roles' => ['sa'],
                    ],
                ],
                'denyCallback' => self::denyCallback()
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'https' => ['post','get'],
                ],
            ],
        ];
    }
    
    /**
     * 網站管理儀表板
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    /**
     * 網站設定
     *
     * @return string
     */
    public function actionSetting()
    {
        $model = Config::findOne(Yii::$app->id);
        if ($model === null) {
            $model = new Config();
            $model->id = Yii::$app->id;
        }
        return $this->render('setting', compact('model'));
    }
    
    /**
     * 修改網站設定
     *
     * @return Response
     */
    public function actionEditSetting()
    {
        $model = new Config();

        $request = Yii::$app->request;

        if($request->isPost)// 提交表單
        {
            $update = $model->updateConfig(Yii::$app->id, $request->post());

            if ($update) {
                Yii::$app->session->addFlash('success', '網站設定編輯完成！');
            } 
            else {
                Yii::$app->session->addFlash('error', '網站設定編輯失敗！');
            }
            
            return $this->redirect($request->referrer);
        }
    }
    
    /**
     * 網站Log
     *
     * @return string
     */
    public function actionLog()
    {
        $logs = new Logs();
        $users = ArrayHelper::getColumn((new \yii\db\Query())->select('DISTINCT `user`')->from('logs')->all(), 'user');
        return $this->render('log',[
            'model' => $logs,
            'users' => ArrayHelper::map(
                Users::find()->select(['cn', 'name'])->where(['cn' => $users])->asArray()->all(),
                'cn', 'name'
            ),
            'dataProvider' => (new \app\models\DataProvider)->getBasicDataProvider(
                $logs->search(Yii::$app->request->queryParams)
            ),
        ]);
    }
    
    /**
     * 取得特定ID的Log詳細資料
     *
     * @param  int $id
     * @return string
     */
    public function actionLogDetail($id)
    {
        if (Yii::$app->request->isAjax) {
            $this->layout = false;
            $model = Logs::findOne($id);
            return $this->render('_log_detail', compact('model'));
        }
    }
    
    /**
     * 匿名登入狀態
     *
     * @return void
     */
    public function actionLogins()
    {
        $votes = Logins::find()
            ->select(['votes.voteID', 'votes.Name'])
            ->innerJoin('votes', 'logins.voteID = votes.voteID')
            ->groupBy('voteID')
            ->asArray()
            ->all();
        $votes = ArrayHelper::map($votes, 'voteID', 'Name');

        return $this->render('logins', compact('votes'));
    }
    
    /**
     * 取得密碼的分組及標記資料
     *
     * @return void
     */
    public function actionPasswdInfo()
    {
        if ($this->request->isPost) {
            $voteID = $this->request->post('voteID');
            $parties = Parties::find()->select(['party', 'name'])->where(['voteID' => $voteID])->asArray()->all();
            $marks = Passwords::find()->select(['mark'])->where(['voteID' => $voteID])->orderBy('id')->asArray()->distinct()->all();

            try {
                $partiesJson = json_encode($parties);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('JSON encode error for parties: ' . json_last_error_msg());
                }
                $marksJson = json_encode($marks);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('JSON encode error for marks: ' . json_last_error_msg());
                }
                return $this->asJson(['parties' => $partiesJson, 'marks' => $marksJson]);
            } catch (\Exception $e) {
                Yii::error("Error encoding JSON in actionPasswdInfo: " . $e->getMessage(), __METHOD__);
                return $this->asJson(['error' => 'Unable to encode data properly']);
            }
        }
    }
    
    /**
     * 清除登入中的匿名投票者
     *
     * @return \yii\web\Response|null
     */
    public function actionLogoutAnon()
    {
        if (!$this->request->isPost) {
            return null;
        }

        $voteID = trim((string) $this->request->post('voteID', ''));
        $party = $this->normalizeOptionalFilter($this->request->post('party'));
        $mark = $this->normalizeOptionalFilter($this->request->post('mark'));

        $voteInfo = $voteID !== '' ? Votes::findOne($voteID) : null;
        if ($voteInfo === null) {
            Yii::$app->session->addFlash('error', '投票場次不存在。');
            return $this->redirect(['manage/logins']);
        }

        if ($voteInfo->isBindVote) {
            $voteID = (string) $voteInfo->bindWhichVote;
            if (Votes::findOne($voteID) === null) {
                Yii::$app->session->addFlash('error', '綁定投票場次不存在。');
                return $this->redirect(['manage/logins']);
            }
        }

        if ($party !== null && !Parties::find()->where(['voteID' => $voteID, 'party' => $party])->exists()) {
            Yii::$app->session->addFlash('error', '組別不存在。');
            return $this->redirect(['manage/logins']);
        }

        if ($mark !== null && !Passwords::find()->where(['voteID' => $voteID, 'mark' => $mark])->exists()) {
            Yii::$app->session->addFlash('error', '標記不存在。');
            return $this->redirect(['manage/logins']);
        }

        $passwordIds = array_map(
            'intval',
            Passwords::find()
                ->select(['id'])
                ->where(['voteID' => $voteID])
                ->andFilterWhere(['party' => $party])
                ->andFilterWhere(['mark' => $mark])
                ->column()
        );

        if ($passwordIds === []) {
            Yii::$app->session->addFlash('warning', '找不到符合條件的密碼，未清除任何登入狀態。');
            return $this->redirect(['manage/logins']);
        }

        $sessionIds = Logins::find()
            ->select(['session'])
            ->where(['creator' => $passwordIds])
            ->andWhere(['not', ['session' => null]])
            ->andWhere(['<>', 'session', ''])
            ->column();

        $destroyed = 0;
        $failed = 0;
        foreach ($sessionIds as $sessionId) {
            if (SessionInvalidator::destroyById((string) $sessionId)) {
                $destroyed++;
            } else {
                $failed++;
            }
        }

        // 補強：regenerateID 後 Logins.session 可能是舊 id，改依 anon idParam 掃實際 session 檔
        $destroyed += SessionInvalidator::destroyByCreatorIds($passwordIds);

        $deleted = Logins::deleteAll(['creator' => $passwordIds]);

        if ($failed > 0) {
            Yii::$app->session->addFlash(
                'warning',
                "已清除 {$deleted} 筆登入紀錄、{$destroyed} 個 session，另有 {$failed} 個 session 清除失敗。"
            );
        } elseif ($deleted === 0 && $destroyed === 0) {
            Yii::$app->session->addFlash('warning', '目前沒有登入中的匿名投票者。');
        } else {
            Yii::$app->session->addFlash('success', "清除session完成！（登入紀錄 {$deleted} 筆，session {$destroyed} 個）");
        }
        return $this->redirect(['manage/logins']);
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function normalizeOptionalFilter($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
