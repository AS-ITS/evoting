<?php
namespace app\components;

use Yii;
use yii\helpers\VarDumper;

/**
 * 提供 Access Control Filter 部分功能運作
 *
 * @link Security: Authorization https://www.yiiframework.com/doc/guide/2.0/en/security-authorization#access-control-filter
 */
class Controller extends \yii\web\Controller
{
    /**
     * 初始化共享 Session 資料
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if ($this->adminSessionEpochStale()) {
            Yii::$app->user->logout(true);
            Yii::$app->session->setFlash('error', Yii::t('app', '密碼已變更，請重新登入'));
            Yii::$app->response->redirect(['/auth/login'])->send();
            return false;
        }

        return true;
    }

    /**
     * 密碼重設後 auth_epoch 遞增，舊 session 不再有效。
     */
    private function adminSessionEpochStale(): bool
    {
        $component = Yii::$app->user;
        if ($component->isGuest || !$component->identity || !method_exists($component->identity, 'getAuthData')) {
            return false;
        }
        $cn = $component->identity->getAuthData('cn');
        if (!$cn) {
            return false;
        }
        $sessionEpoch = (int) ($component->identity->getAuthData('auth_epoch') ?? 0);
        try {
            $row = \app\models\Users::find()->select(['auth_epoch'])->where(['cn' => $cn])->asArray()->one();
        } catch (\Throwable $e) {
            return false;
        }
        if ($row === null || !array_key_exists('auth_epoch', $row)) {
            return false;
        }

        return (int) $row['auth_epoch'] !== $sessionEpoch;
    }

    public function init()
    {
        parent::init();

        // 初始化 Share.instAry 和 Share.payTitle (如果尚未設置)
        if (!Yii::$app->session->has('Share.instAry')) {
            // TODO: 這裡應該從資料庫或 API 載入實際的機構資料
            // 目前使用空陣列作為預設值以避免 array_column 錯誤
            Yii::$app->session->set('Share.instAry', []);
        }

        if (!Yii::$app->session->has('Share.payTitle')) {
            // TODO: 這裡應該從資料庫或 API 載入實際的職稱資料
            // 目前使用空陣列作為預設值以避免 array_column 錯誤
            Yii::$app->session->set('Share.payTitle', []);
        }
    }

    /**
     * 無權限訪問時的處理
     *
     * @return void
     */
    public function NotAllowedAccess()
    {
        throw new \yii\web\HttpException( 403, '您無權訪問此頁面(You are not allowed to access this page)');
    }

    /**
     * [behaviors] 同步權限管理相關函式
     *
     * @param string $type 配置登入的 User flag
     *
     * @return \Closure
     *
     * @link runAction https://www.yiiframework.com/doc/api/2.0/yii-base-module#runAction()-detail
     */
    public function denyCallback($type='user')
    {
        $method = __METHOD__;
        return function ($rule, $action) use ($type, $method)
        {
            if (!Yii::$app->{$type}->isGuest)
                $action->controller->NotAllowedAccess();

            $loginUrl = Yii::$app->{$type}->loginUrl;

            Yii::debug(sprintf(
                "type: %s, loginUrl: %s",
                VarDumper::dumpAsString($type),
                VarDumper::dumpAsString($loginUrl)
            ), $method);

            // 使用 loginRequired() 會自動保存 returnUrl 並重定向到登入頁面
            // 這樣可以確保瀏覽器 URL 改變，避免空白頁面問題
            return Yii::$app->{$type}->loginRequired();
        };
    }
}
