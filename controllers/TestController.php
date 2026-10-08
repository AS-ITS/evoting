<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Users;
use app\components\AdminIdentity;

class TestController extends Controller
{
    public function actionLogin($user)
    {
        if (!YII_ENV_TEST) {
            throw new \yii\web\ForbiddenHttpException('Not allowed');
        }

        // 使用 cn 欄位查找使用者
        $userModel = Users::findOne(['cn' => $user]);
        if (!$userModel) {
            return 'Login failed: user not found';
        }

        // 創建 AdminIdentity
        $identity = new AdminIdentity();

        // 設置認證資料到 session（使用 identity 的 setAttr 方法確保 key 格式一致）
        $authData = [
            'cn' => $userModel->cn,
            'name' => $userModel->name,
            'roles' => $userModel->roles,
        ];

        // 確保 session 已開啟
        $session = Yii::$app->session;
        if (!$session->isActive) {
            $session->open();
        }

        // 設置物件屬性（先設置屬性，再存入 session）
        $identity->authData = $authData;
        $identity->role = $userModel->roles;
        $identity->roles = explode(',', $userModel->roles);

        // 直接使用 session->set() 設置，確保 key 格式與 UserIdentity::getKey() 一致
        $flag = $identity->getFlag(); // 應該回傳 'Admin'
        $session->set("{$flag}.AuthData", $authData);
        $session->set("{$flag}.Role", $userModel->roles);
        $session->set("{$flag}.Roles", explode(',', $userModel->roles));
        $session->set("{$flag}.Identity", $identity);

        // 使用 switchIdentity 來設置用戶（不觸發 afterLogin）
        Yii::$app->user->switchIdentity($identity, 3600);

        // 設置 RBAC 權限
        $auth = Yii::$app->authManager;
        if ($auth) {
            // 先移除所有現有權限
            $auth->revokeAll($userModel->cn);

            $roles = explode(',', $userModel->roles);
            foreach ($roles as $role) {
                $roleObj = $auth->getRole(trim($role));
                if ($roleObj) {
                    try {
                        $auth->assign($roleObj, $userModel->cn);
                    } catch (\Exception $e) {
                        // 可能已經分配過
                    }
                }
            }
        }

        // 強制寫入所有 session 資料
        Yii::$app->session->close();

        // Debug: 記錄 session 內容
        Yii::info("Test login: session data saved for user {$user}, flag={$identity->getFlag()}", __METHOD__);

        return 'Login successful: ' . $user;
    }
}