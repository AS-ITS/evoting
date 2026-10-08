<?php

namespace app\components;

use Yii;
use app\models\Users;
use app\models\Config;
use app\components\helper\ArrayHelper;

/**
 * AdminIdentity 管理員身份認證組件
 *
 * 負責管理員登入後的 RBAC 角色分配和系統配置載入
 */
class AdminIdentity extends \app\models\UserIdentity
{
    public static $flag = 'AdminIdentity';

    /**
     * 取得唯一的鍵值
     *
     * @return string
     */
    public function getUnique()
    {
        return 'cn';
    }

    /**
     * 登入後事件處理
     *
     * @param  \app\models\UserIdentity $identity
     * @return void
     */
    public function afterLogin($identity)
    {
        try {
            $auth = Yii::$app->authManager;
            $cn = $identity->getAuthData('cn');

            // 用戶檢查已經在控制器中完成，這裡只需要處理角色分配
            $user = Users::findOne($cn);
            if (empty($user)) {
                $auth->revokeAll(Yii::$app->user->id);
                Yii::$app->user->identity->logout(true);
                Yii::$app->session->addFlash('error', '您沒有權限訪問此系統');
                return;
            }
            
            $roles = $user->roles;
            if (empty($roles)) {
                $auth->revokeAll(Yii::$app->user->id);
                Yii::$app->user->identity->logout(true);
                Yii::$app->session->addFlash('error', '無授權帳號');
                return; 
            }

            $session = Yii::$app->session;
            $config = Config::find()->where(['id' => Yii::$app->id])->asArray()->one();
            if (!empty($config)) {
                $session->set('System.config', ArrayHelper::forget($config, ['id', 'updated_at']));
            }

            // RBAC: 有系統管理者sa角色的話，登入直接切換成sa；
            $roles = explode(',', $roles);
            $identity->setRoles($roles);

            // 移除所有現有權限（清理舊的權限分配）
            $auth->revokeAll(Yii::$app->user->id);

            // 如果使用者擁有sa角色，分配sa角色（sa角色優先）
            if (in_array('sa', $roles)) {
                // 取得sa角色物件
                $role = $auth->getRole('sa');
                if ($role) {
                    // 賦予使用者sa權限
                    $auth->assign($role, Yii::$app->user->id);
                    // 設定使用者當前角色為sa
                    $identity->setRole('sa');
                }
            }
            else {
                // 取得使用者第一個角色物件
                $role = $auth->getRole($roles[0]);
                if ($role) {
                    // 賦予使用者該角色權限
                    $auth->assign($role, Yii::$app->user->id);
                    // 設定使用者當前角色
                    $identity->setRole($roles[0]);
                }
            }
        }catch (\Exception $e) {
            Yii::error("Error in Admin afterLogin: " . $e->getMessage());
            // 確保用戶被登出
            if (!Yii::$app->user->isGuest) {
                Yii::$app->user->logout(true);
            }
            Yii::$app->session->addFlash('error', '認證過程發生錯誤');
        }
    }
}