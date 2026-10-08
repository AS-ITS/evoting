<?php

namespace app\tests\unit;

use Yii;
use app\models\Users;
use app\components\AdminIdentity;
use app\tests\fixtures\UsersFixture;
use Codeception\Test\Unit;

/**
 * RBAC 整合測試
 *
 * 測試完整的 RBAC 角色分配、權限檢查和規則邏輯
 */
class RbacIntegrationTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // 確保管理員登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }

        // 清除 session
        Yii::$app->session->removeAll();

        // 清除 RBAC 快取
        if (Yii::$app->cache) {
            Yii::$app->cache->flush();
        }
    }

    protected function _after()
    {
        // 清理：登出管理員
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }

        // 清除 session
        Yii::$app->session->removeAll();
    }

    /**
     * Helper: 手動分配 RBAC 角色給用戶
     *
     * @param string $userId 用戶 ID
     * @param string|array $roles 角色名稱或角色陣列
     * @return bool 是否成功分配
     */
    protected function assignRbacRoles($userId, $roles)
    {
        $auth = Yii::$app->authManager;

        // 清除現有角色
        $auth->revokeAll($userId);

        // 轉換為陣列
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        // 分配角色（優先分配 sa）
        $primaryRole = in_array('sa', $roles) ? 'sa' : $roles[0];

        foreach ($roles as $roleName) {
            $role = $auth->getRole($roleName);
            if ($role) {
                try {
                    $assignment = $auth->assign($role, $userId);
                } catch (\Exception $e) {
                    Yii::error("Failed to assign role $roleName to user $userId: " . $e->getMessage());
                    return false;
                }
            }
        }

        // 更新 identity 的角色信息
        if (!Yii::$app->user->isGuest && Yii::$app->user->identity) {
            Yii::$app->user->identity->setRole($primaryRole);
            Yii::$app->user->identity->setRoles($roles);
        }

        return true;
    }

    /**
     * 載入測試數據
     *
     * @return array
     */
    public function _fixtures()
    {
        return [
            'users' => UsersFixture::class,
            'rbac' => \app\tests\fixtures\RbacFixture::class,
            'config' => \app\tests\fixtures\ConfigFixture::class,
        ];
    }

    /**
     * 測試：SA 角色分配和權限檢查
     */
    public function testSaRoleAssignmentAndPermissions()
    {
        // 建立 SA 用戶
        $user = new Users();
        $user->cn = 'sa_rbac_test';
        $user->name = 'SA RBAC Test';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'SA user should be created');

        // 建立身份並登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'sa_rbac_test',
            'name' => 'SA RBAC Test',
            'roles' => 'sa',
        ]);
        // 執行登入（會自動觸發 afterLogin 事件並分配角色）
        Yii::$app->user->login($identity);

        // 驗證 RBAC 角色分配（afterLogin 應該已經分配角色）
        $auth = Yii::$app->authManager;
        $userId = Yii::$app->user->id;

        $roles = $auth->getRolesByUser($userId);

        $this->assertNotEmpty($roles, 'User should have roles assigned');
        $this->assertArrayHasKey('sa', $roles, 'User should have sa role');

        // 驗證 SA 擁有所有權限
        $this->assertTrue(Yii::$app->user->can('sa'), 'SA should have sa permission');

        // 驗證當前角色
        $this->assertEquals('sa', Yii::$app->user->identity->getRole(), 'Current role should be sa');
    }

    /**
     * 測試：VA 角色分配和權限檢查
     */
    public function testVaRoleAssignmentAndPermissions()
    {
        // 建立 VA 用戶
        $user = new Users();
        $user->cn = 'va_rbac_test';
        $user->name = 'VA RBAC Test';
        $user->roles = 'va';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'VA user should be created');

        // 建立身份並登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'va_rbac_test',
            'name' => 'VA RBAC Test',
            'roles' => 'va',
        ]);
        // 執行登入
        Yii::$app->user->login($identity);

        // 驗證 RBAC 角色分配
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(Yii::$app->user->id);

        $this->assertArrayHasKey('va', $roles, 'User should have va role');
        $this->assertTrue(Yii::$app->user->can('va'), 'VA should have va permission');
    }

    /**
     * 測試：GA 角色分配和權限檢查
     */
    public function testGaRoleAssignmentAndPermissions()
    {
        // 建立 GA 用戶
        $user = new Users();
        $user->cn = 'ga_rbac_test';
        $user->name = 'GA RBAC Test';
        $user->roles = 'ga';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'GA user should be created');

        // 建立身份並登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'ga_rbac_test',
            'name' => 'GA RBAC Test',
            'roles' => 'ga',
        ]);
        // 執行登入
        Yii::$app->user->login($identity);

        // 驗證 RBAC 角色分配
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(Yii::$app->user->id);

        $this->assertArrayHasKey('ga', $roles, 'User should have ga role');
        $this->assertTrue(Yii::$app->user->can('ga'), 'GA should have ga permission');
    }

    /**
     * 測試：GM 角色分配和權限檢查
     */
    public function testGmRoleAssignmentAndPermissions()
    {
        // 建立 GM 用戶
        $user = new Users();
        $user->cn = 'gm_rbac_test';
        $user->name = 'GM RBAC Test';
        $user->roles = 'gm';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'GM user should be created');

        // 建立身份並登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'gm_rbac_test',
            'name' => 'GM RBAC Test',
            'roles' => 'gm',
        ]);
        // 執行登入
        Yii::$app->user->login($identity);

        // 驗證 RBAC 角色分配
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(Yii::$app->user->id);

        $this->assertArrayHasKey('gm', $roles, 'User should have gm role');
        $this->assertTrue(Yii::$app->user->can('gm'), 'GM should have gm permission');
    }

    /**
     * 測試：多角色用戶 - SA 優先邏輯
     */
    public function testMultiRoleUserPrefersSa()
    {
        // 建立多角色用戶（包含 SA）
        $user = new Users();
        $user->cn = 'multi_sa_test';
        $user->name = 'Multi SA Test';
        $user->roles = 'va,sa,ga';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'Multi-role user should be created');

        // 建立身份並登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'multi_sa_test',
            'name' => 'Multi SA Test',
            'roles' => 'va,sa,ga',
        ]);
        // 執行登入
        Yii::$app->user->login($identity);

        // 驗證應優先分配 SA 角色
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(Yii::$app->user->id);

        $this->assertArrayHasKey('sa', $roles, 'User should have sa role (preferred)');
        $this->assertTrue(Yii::$app->user->can('sa'), 'Should have sa permission');
        $this->assertEquals('sa', Yii::$app->user->identity->getRole(), 'Current role should be sa');
    }

    /**
     * 測試：多角色用戶 - 無 SA 時取第一個角色
     */
    public function testMultiRoleUserWithoutSaTakesFirstRole()
    {
        // 建立多角色用戶（不含 SA）
        $user = new Users();
        $user->cn = 'multi_va_test';
        $user->name = 'Multi VA Test';
        $user->roles = 'va,ga,gm';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'Multi-role user should be created');

        // 建立身份並登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'multi_va_test',
            'name' => 'Multi VA Test',
            'roles' => 'va,ga,gm',
        ]);
        // 執行登入
        Yii::$app->user->login($identity);

        // 驗證應分配第一個角色（va）
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(Yii::$app->user->id);

        $this->assertArrayHasKey('va', $roles, 'User should have va role (first role)');
        $this->assertTrue(Yii::$app->user->can('va'), 'Should have va permission');

        // 驗證 roles 屬性完整
        $identity = Yii::$app->user->identity;
        $allRoles = $identity->getRoles();
        $this->assertCount(3, $allRoles, 'Should have all 3 roles stored');
        $this->assertContains('va', $allRoles);
        $this->assertContains('ga', $allRoles);
        $this->assertContains('gm', $allRoles);
    }

    /**
     * 測試：角色切換功能（isSwitchRoles）
     */
    public function testRoleSwitchingCapability()
    {
        // 建立多角色用戶
        $user = new Users();
        $user->cn = 'switch_test';
        $user->name = 'Switch Test';
        $user->roles = 'va,ga';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'Multi-role user should be created');

        // 建立身份並登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'switch_test',
            'name' => 'Switch Test',
            'roles' => 'va,ga',
        ]);
                Yii::$app->user->login($identity);

        // 驗證可以切換角色
        $this->assertTrue(Yii::$app->user->identity->isSwitchRoles(), 'User should be able to switch roles');

        // 測試單一角色用戶
        Yii::$app->user->logout();

        $singleUser = new Users();
        $singleUser->cn = 'single_test';
        $singleUser->name = 'Single Test';
        $singleUser->roles = 'va';
        $singleUser->password_plain = 'TestPass123';
        $this->assertTrue($singleUser->save());

        $singleIdentity = new AdminIdentity();
        $singleIdentity->setAuthData([
            'cn' => 'single_test',
            'name' => 'Single Test',
            'roles' => 'va',
        ]);
                Yii::$app->user->login($singleIdentity);

        $this->assertFalse(Yii::$app->user->identity->isSwitchRoles(), 'Single-role user should not be able to switch');
    }

    /**
     * 測試：revokeAll 在登入時清除舊角色
     */
    public function testRevokeAllClearsOldRolesOnLogin()
    {
        // 建立用戶
        $user = new Users();
        $user->cn = 'revoke_test';
        $user->name = 'Revoke Test';
        $user->roles = 'va';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save());

        // 手動分配一個錯誤的角色
        $auth = Yii::$app->authManager;
        $wrongRole = $auth->getRole('ga');
        $auth->assign($wrongRole, 'revoke_test');

        // 驗證錯誤角色已分配
        $oldRoles = $auth->getRolesByUser('revoke_test');
        $this->assertArrayHasKey('ga', $oldRoles, 'Should have wrong ga role initially');

        // 執行正確登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'revoke_test',
            'name' => 'Revoke Test',
            'roles' => 'va',
        ]);
                Yii::$app->user->login($identity);

        // 驗證舊角色已清除，新角色已分配
        $newRoles = $auth->getRolesByUser(Yii::$app->user->id);
        $this->assertArrayNotHasKey('ga', $newRoles, 'Old ga role should be revoked');
        $this->assertArrayHasKey('va', $newRoles, 'New va role should be assigned');
    }

    /**
     * 測試：無角色用戶的處理
     */
    public function testNoRoleUserHandling()
    {
        // 建立無角色用戶
        $user = new Users();
        $user->cn = 'norole_rbac_test';
        $user->name = 'No Role RBAC Test';
        $user->roles = '';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save());

        // 建立身份並嘗試登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'norole_rbac_test',
            'name' => 'No Role RBAC Test',
            'roles' => '',
        ]);
                Yii::$app->user->login($identity);

        // afterLogin 應該會登出用戶
        $this->assertTrue(Yii::$app->user->isGuest, 'User without roles should be logged out');

        // 驗證錯誤訊息
        $this->assertTrue(Yii::$app->session->hasFlash('error'), 'Should have error flash message');
    }

    /**
     * 測試：不存在用戶的處理
     */
    public function testNonExistentUserHandling()
    {
        // 建立身份（用戶不存在於資料庫）
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'nonexistent_rbac',
            'name' => 'Nonexistent RBAC',
            'roles' => 'va',
        ]);
                Yii::$app->user->login($identity);

        // afterLogin 應該會登出用戶
        $this->assertTrue(Yii::$app->user->isGuest, 'Nonexistent user should be logged out');

        // 驗證錯誤訊息
        $this->assertTrue(Yii::$app->session->hasFlash('error'), 'Should have error flash message');
    }

    /**
     * 測試：RBAC 角色階層（SA > VA > GA > GM）
     */
    public function testRoleHierarchy()
    {
        // 建立 SA 用戶並完整登入
        $saUser = new Users();
        $saUser->cn = 'hierarchy_sa';
        $saUser->name = 'Hierarchy SA';
        $saUser->roles = 'sa';
        $saUser->password_plain = 'TestPass123';
        $this->assertTrue($saUser->save());

        $saIdentity = new AdminIdentity();
        $saIdentity->setAuthData([
            'cn' => 'hierarchy_sa',
            'name' => 'Hierarchy SA',
            'roles' => 'sa',
        ]);
                Yii::$app->user->login($saIdentity);

        // 驗證 SA 權限
        $this->assertTrue(Yii::$app->user->can('sa'), 'SA should have sa permission');
        $this->assertTrue(Yii::$app->user->can('va'), 'SA should have va permission (inheritance)');

        Yii::$app->user->logout();

        // 建立 VA 用戶並完整登入
        $vaUser = new Users();
        $vaUser->cn = 'hierarchy_va';
        $vaUser->name = 'Hierarchy VA';
        $vaUser->roles = 'va';
        $vaUser->password_plain = 'TestPass123';
        $this->assertTrue($vaUser->save());

        $vaIdentity = new AdminIdentity();
        $vaIdentity->setAuthData([
            'cn' => 'hierarchy_va',
            'name' => 'Hierarchy VA',
            'roles' => 'va',
        ]);
                Yii::$app->user->login($vaIdentity);

        // 驗證 VA 權限
        $this->assertTrue(Yii::$app->user->can('va'), 'VA should have va permission');
        $this->assertFalse(Yii::$app->user->can('sa'), 'VA should not have sa permission');
    }

    /**
     * 測試：inUserRole 方法各種場景
     */
    public function testInUserRoleMethod()
    {
        // 建立 SA 用戶
        $user = new Users();
        $user->cn = 'inrole_test';
        $user->name = 'InRole Test';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save());

        // 完整登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'inrole_test',
            'name' => 'InRole Test',
            'roles' => 'sa',
        ]);
                Yii::$app->user->login($identity);

        $identity = Yii::$app->user->identity;

        // 測試字串比對
        $this->assertTrue($identity->inUserRole('sa'), 'Should match sa role with string');
        $this->assertFalse($identity->inUserRole('va'), 'Should not match va role');

        // 測試陣列比對
        $this->assertTrue($identity->inUserRole(['sa', 'va', 'ga']), 'Should match sa in array');
        $this->assertFalse($identity->inUserRole(['va', 'ga']), 'Should not match if sa not in array');

        // 測試無效參數
        $this->assertFalse($identity->inUserRole(null), 'Should return false for null');
        $this->assertFalse($identity->inUserRole(123), 'Should return false for invalid type');
    }

    /**
     * 測試：AuthManager 基本功能
     */
    public function testAuthManagerBasicFunctions()
    {
        $auth = Yii::$app->authManager;

        // 驗證 authManager 存在
        $this->assertNotNull($auth, 'AuthManager should exist');
        $this->assertInstanceOf(\yii\rbac\DbManager::class, $auth, 'Should use DbManager');

        // 驗證角色存在
        $saRole = $auth->getRole('sa');
        $vaRole = $auth->getRole('va');
        $gaRole = $auth->getRole('ga');
        $gmRole = $auth->getRole('gm');

        $this->assertNotNull($saRole, 'SA role should exist');
        $this->assertNotNull($vaRole, 'VA role should exist');
        $this->assertNotNull($gaRole, 'GA role should exist');
        $this->assertNotNull($gmRole, 'GM role should exist');
    }
}
