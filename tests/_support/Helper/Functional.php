<?php
namespace Helper;

use Yii;
use app\models\Users;
use app\components\AdminIdentity;

class Functional extends \Codeception\Module
{
    /**
     * 初始化 RBAC（如果還沒初始化）
     *
     * 重要：此方法創建的 RBAC 結構需要與 SiteController::actionRbacInit() 保持一致
     * 特別是有帶規則的權限需要確保 sa 角色可以通過檢查
     */
    protected function initRbac()
    {
        $auth = Yii::$app->authManager;
        if (!$auth) {
            return;
        }

        // 檢查 sa 角色是否存在
        $saRole = $auth->getRole('sa');
        if ($saRole) {
            // 清除 RBAC cache 確保權限更新
            if ($auth->cache) {
                $auth->cache->delete($auth->cacheKey);
            }
            return; // 已經初始化過
        }

        // 清除所有現有資料以確保乾淨的狀態
        $auth->removeAll();

        // ========================================
        // 創建規則（Rules）
        // ========================================

        // voteManagRule - 用於檢查投票管理權限
        // 此規則會先檢查 Yii::$app->user->can('sa')，如果是 sa 則直接通過
        $voteManagRule = new \app\rules\voteManagRule();
        $auth->add($voteManagRule);

        // ballotWorkRule - 用於檢查開票作業權限
        $ballotWorkRule = new \app\rules\ballotWorkRule();
        $auth->add($ballotWorkRule);

        // ========================================
        // 創建角色（Roles）- 先創建 sa 讓規則檢查可以通過
        // ========================================

        // sa 角色 - 系統管理員，不需要任何規則即可通過所有權限檢查
        $sa = $auth->createRole('sa');
        $sa->description = 'System Administrator';
        $auth->add($sa);

        // ========================================
        // 創建權限（Permissions）
        // ========================================

        // 開票作業權限（帶 ballotWorkRule）
        $ballotWorkIndex = $auth->createPermission('ballotWorkIndex');
        $ballotWorkIndex->description = '開票作業列表';
        $auth->add($ballotWorkIndex);

        $ballotWorkStatus = $auth->createPermission('ballotWorkStatus');
        $ballotWorkStatus->description = '開票狀態';
        $ballotWorkStatus->ruleName = $ballotWorkRule->name;
        $auth->add($ballotWorkStatus);

        $ballotWorkSetting = $auth->createPermission('ballotWorkSetting');
        $ballotWorkSetting->description = '開票設定';
        $ballotWorkSetting->ruleName = $ballotWorkRule->name;
        $auth->add($ballotWorkSetting);

        $ballotWorkCount = $auth->createPermission('ballotWorkCount');
        $ballotWorkCount->description = '計票';
        $ballotWorkCount->ruleName = $ballotWorkRule->name;
        $auth->add($ballotWorkCount);

        $ballotWorkResult = $auth->createPermission('ballotWorkResult');
        $ballotWorkResult->description = '開票結果';
        $ballotWorkResult->ruleName = $ballotWorkRule->name;
        $auth->add($ballotWorkResult);

        // 投票管理權限
        $voteCreate = $auth->createPermission('voteCreate');
        $voteCreate->description = '建立投票';
        $auth->add($voteCreate);

        $voteManag = $auth->createPermission('voteManag');
        $voteManag->description = '投票管理頁面';
        $auth->add($voteManag);

        // 帶 voteManagRule 的權限 - sa 角色可以直接通過
        $voteInfo = $auth->createPermission('voteInfo');
        $voteInfo->description = '投票基本設定編輯';
        $voteInfo->ruleName = $voteManagRule->name;
        $auth->add($voteInfo);

        $voteQuestion = $auth->createPermission('voteQuestion');
        $voteQuestion->description = '投票問題全功能';
        $voteQuestion->ruleName = $voteManagRule->name;
        $auth->add($voteQuestion);

        $voteCandi = $auth->createPermission('voteCandi');
        $voteCandi->description = '投票候選人管理全功能';
        $voteCandi->ruleName = $voteManagRule->name;
        $auth->add($voteCandi);

        $votePasswd = $auth->createPermission('votePasswd');
        $votePasswd->description = '投票密碼管理全功能';
        $votePasswd->ruleName = $voteManagRule->name;
        $auth->add($votePasswd);

        $voteBallot = $auth->createPermission('voteBallot');
        $voteBallot->description = '投票選票管理全功能';
        $voteBallot->ruleName = $voteManagRule->name;
        $auth->add($voteBallot);

        $voteCount = $auth->createPermission('voteCount');
        $voteCount->description = '投票計票單、開票全功能';
        $voteCount->ruleName = $voteManagRule->name;
        $auth->add($voteCount);

        $voteResult = $auth->createPermission('voteResult');
        $voteResult->description = '投票結果全功能';
        $voteResult->ruleName = $voteManagRule->name;
        $auth->add($voteResult);

        $voteReset = $auth->createPermission('voteReset');
        $voteReset->description = '投票重啟全功能';
        $voteReset->ruleName = $voteManagRule->name;
        $auth->add($voteReset);

        $voteGenManag = $auth->createPermission('voteGenManag');
        $voteGenManag->description = '投票總管理';
        $auth->add($voteGenManag);

        // 其他權限
        $electManag = $auth->createPermission('electManag');
        $electManag->description = '管理選舉';
        $auth->add($electManag);

        // ========================================
        // 設定 SA 角色權限（擁有所有權限）
        // ========================================
        $auth->addChild($sa, $ballotWorkIndex);
        $auth->addChild($sa, $ballotWorkStatus);
        $auth->addChild($sa, $ballotWorkSetting);
        $auth->addChild($sa, $ballotWorkCount);
        $auth->addChild($sa, $ballotWorkResult);
        $auth->addChild($sa, $voteCreate);
        $auth->addChild($sa, $voteManag);
        $auth->addChild($sa, $voteInfo);
        $auth->addChild($sa, $voteQuestion);
        $auth->addChild($sa, $voteCandi);
        $auth->addChild($sa, $votePasswd);
        $auth->addChild($sa, $voteBallot);
        $auth->addChild($sa, $voteCount);
        $auth->addChild($sa, $voteResult);
        $auth->addChild($sa, $voteReset);
        $auth->addChild($sa, $voteGenManag);
        $auth->addChild($sa, $electManag);

        // ========================================
        // 創建其他角色
        // ========================================
        $va = $auth->createRole('va');
        $va->description = 'Vote Administrator';
        $auth->add($va);

        // VA 權限
        $auth->addChild($va, $voteCreate);
        $auth->addChild($va, $voteManag);
        $auth->addChild($va, $voteInfo);
        $auth->addChild($va, $voteQuestion);
        $auth->addChild($va, $voteCandi);
        $auth->addChild($va, $votePasswd);
        $auth->addChild($va, $voteBallot);
        $auth->addChild($va, $voteCount);
        $auth->addChild($va, $voteResult);
        $auth->addChild($va, $voteReset);
        $auth->addChild($va, $ballotWorkIndex);
        $auth->addChild($va, $ballotWorkStatus);
        $auth->addChild($va, $ballotWorkSetting);
        $auth->addChild($va, $ballotWorkCount);
        $auth->addChild($va, $ballotWorkResult);

        $ga = $auth->createRole('ga');
        $ga->description = 'Group Administrator';
        $auth->add($ga);

        $gm = $auth->createRole('gm');
        $gm->description = 'Group Member';
        $auth->add($gm);

        // 清除 RBAC cache
        if ($auth->cache) {
            $auth->cache->delete($auth->cacheKey);
        }
    }

    /**
     * 模擬管理員登入
     *
     * 此方法會建立測試用戶並設置完整的 session 認證狀態
     * Codeception Yii2 模組會在每次請求前恢復 session，因此這裡設置的狀態會保持
     */
    public function amLoggedInAsAdmin($role = 'sa', $cn = 'test_admin')
    {
        // 初始化 RBAC
        $this->initRbac();

        // 確保測試用戶存在
        $user = Users::findOne(['cn' => $cn]);
        if (!$user) {
            $user = new Users();
            $user->cn = $cn;
            $user->name = 'TestAdmin';
            $user->password_plain = 'TestPass123';
            $user->roles = $role;
            if (!$user->save(false)) {
                throw new \Exception('Failed to create test user: ' . json_encode($user->errors));
            }
        } else {
            // 確保角色正確
            if ($user->roles !== $role) {
                $user->roles = $role;
                $user->save(false);
            }
        }

        // 創建 AdminIdentity
        $identity = new AdminIdentity();

        // 設置認證資料到 session
        $authData = [
            'cn' => $cn,
            'name' => $user->name,
            'roles' => $role,
        ];

        // 直接設置 session 值
        $session = Yii::$app->session;
        $flag = 'Admin'; // AdminIdentity 的 flag

        $session->set("{$flag}.AuthData", $authData);
        $session->set("{$flag}.Role", $role);
        $session->set("{$flag}.Roles", [$role]);
        $session->set("{$flag}.Identity", $identity);

        // 設置物件屬性
        $identity->authData = $authData;
        $identity->role = $role;
        $identity->roles = [$role];

        // 使用 switchIdentity 來設置用戶
        Yii::$app->user->switchIdentity($identity, 3600);

        // 設置 System.config（模擬 AdminIdentity 中的行為）
        $config = \app\models\Config::find()->one();
        if ($config) {
            $configArray = $config->toArray();
            unset($configArray['id'], $configArray['updated_at']);
            $session->set('System.config', $configArray);
        } else {
            // 使用預設值
            $session->set('System.config', [
                'partyLimit' => 4,
                'countColumnNum' => 3,
                'canDeletePassword' => 1,
                'anonPasswordErrorTimes' => 999,
                'anonLoginLockPeriod' => 1,
                'anonLoginWaiting' => 0,
                'homeLayout' => 'meeting',
                'logoPath' => null,
                'faviconPath' => 'favicon.ico',
                'copyright' => null,
            ]);
        }

        // 確保 RBAC 權限已分配
        $auth = Yii::$app->authManager;
        if ($auth) {
            // 清除 RBAC cache 以確保最新狀態
            $auth->invalidateCache();

            // 清除舊權限並重新分配
            try {
                $auth->revokeAll($cn);
            } catch (\Exception $e) {
                // 忽略錯誤
            }

            // 分配新角色
            $roleObj = $auth->getRole($role);
            if ($roleObj) {
                $auth->assign($roleObj, $cn);
            }

            // 清除 cache
            $auth->invalidateCache();

            // 驗證 RBAC 分配 - 直接從資料庫查詢
            $userId = Yii::$app->user->id;
            $canSa = Yii::$app->user->can('sa');
            $assignments = $auth->getAssignments($cn);

            // 輸出到 stderr 以便在測試中看到
            codecept_debug("RBAC: User ID=$userId, CN=$cn, Role=$role");
            codecept_debug("RBAC: Assignments=" . json_encode(array_keys($assignments)));
            codecept_debug("RBAC: can('sa')=" . ($canSa ? 'true' : 'false'));
        }

        // 驗證登入狀態
        if (Yii::$app->user->isGuest) {
            throw new \Exception('Login verification failed - user is still guest');
        }
    }

    /**
     * 登出
     */
    public function amLoggedOut()
    {
        if (Yii::$app->has('user') && !Yii::$app->user->isGuest) {
            Yii::$app->user->logout(false);
        }

        // 清除 session 中的認證相關資料
        $session = Yii::$app->session;
        $keysToRemove = [];

        foreach ($session as $key => $value) {
            if (strpos($key, 'Admin.') === 0) {
                $keysToRemove[] = $key;
            }
        }

        foreach ($keysToRemove as $key) {
            $session->remove($key);
        }
    }

    /**
     * 檢查當前用戶是否已登入
     */
    public function seeAmLoggedIn()
    {
        $this->assertFalse(
            Yii::$app->user->isGuest,
            'User should be logged in'
        );
    }

    /**
     * 檢查當前用戶是否為訪客
     */
    public function seeAmGuest()
    {
        $this->assertTrue(
            Yii::$app->user->isGuest,
            'User should be guest'
        );
    }
}
