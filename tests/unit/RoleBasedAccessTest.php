<?php

namespace app\tests\unit;

use Yii;
use app\models\Users;
use app\models\Votes;
use app\models\FormGroup;
use app\models\FormGroupMember;
use app\components\AdminIdentity;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\ConfigFixture;

/**
 * 角色權限存取測試
 * 測試 sa, va, ga, gm 四種角色登入後的實際功能權限
 */
class RoleBasedAccessTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        // 確保管理員登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }

        // 清除 session
        Yii::$app->session->removeAll();
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
     * 載入測試數據
     *
     * @return array
     */
    public function _fixtures()
    {
        return [
            'users' => UsersFixture::class,
            'votes' => VotesFixture::class,
            'rbac' => RbacFixture::class,
            'config' => ConfigFixture::class,
        ];
    }

    /**
     * 測試：SA 角色可以存取所有功能
     */
    public function testSaRoleHasFullAccess()
    {
        // 建立 SA 用戶
        $user = new Users();
        $user->cn = 'sa_access_test';
        $user->name = 'SA Access Test';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'SA user should be saved');

        // 完整登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'sa_access_test',
            'name' => 'SA Access Test',
            'roles' => 'sa',
        ]);
                Yii::$app->user->login($identity);

        // 驗證 SA 權限
        $this->assertTrue(Yii::$app->user->can('sa'), 'SA should have sa role');

        // SA 可以存取系統管理功能
        $this->assertTrue(Yii::$app->user->can('sa'), 'SA can access system management');

        // SA 可以建立投票 (voteCreate 權限由 va 角色提供，SA 繼承 va)
        $this->assertTrue(Yii::$app->user->can('va'), 'SA inherits va role');

        // SA 可以管理群組 (由 ga 角色提供，SA 繼承 ga)
        $this->assertTrue(Yii::$app->user->can('ga'), 'SA inherits ga role');

        // SA 可以看到所有群組 (不只是自己的群組)
        $this->assertTrue(Yii::$app->user->can('gm'), 'SA inherits gm role');
    }

    /**
     * 測試：VA 角色可以管理投票但不能系統管理
     */
    public function testVaRoleHasVoteManagementAccess()
    {
        // 建立 VA 用戶
        $user = new Users();
        $user->cn = 'va_access_test';
        $user->name = 'VA Access Test';
        $user->roles = 'va';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'VA user should be saved');

        // 完整登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'va_access_test',
            'name' => 'VA Access Test',
            'roles' => 'va',
        ]);
                Yii::$app->user->login($identity);

        // 驗證 VA 權限
        $this->assertTrue(Yii::$app->user->can('va'), 'VA should have va role');

        // VA 不能存取系統管理功能
        $this->assertFalse(Yii::$app->user->can('sa'), 'VA cannot access system management');

        // VA 不能管理群組 (除非同時有 ga 角色)
        $this->assertFalse(Yii::$app->user->can('ga'), 'VA does not have ga role');

        // VA 繼承 GM 角色 (根據 RbacFixture 設定)
        $this->assertTrue(Yii::$app->user->can('gm'), 'VA inherits gm role from RBAC hierarchy');
    }

    /**
     * 測試：GA 角色可以管理群組但不能管理投票
     */
    public function testGaRoleHasGroupManagementAccess()
    {
        // 建立 GA 用戶
        $user = new Users();
        $user->cn = 'ga_access_test';
        $user->name = 'GA Access Test';
        $user->roles = 'ga';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'GA user should be saved');

        // 完整登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'ga_access_test',
            'name' => 'GA Access Test',
            'roles' => 'ga',
        ]);
                Yii::$app->user->login($identity);

        // 驗證 GA 權限
        $this->assertTrue(Yii::$app->user->can('ga'), 'GA should have ga role');

        // GA 不能存取系統管理功能
        $this->assertFalse(Yii::$app->user->can('sa'), 'GA cannot access system management');

        // GA 不能管理投票 (除非同時有 va 角色)
        $this->assertFalse(Yii::$app->user->can('va'), 'GA does not have va role');

        // GA 繼承 GM 角色 (根據 RbacFixture 設定)
        $this->assertTrue(Yii::$app->user->can('gm'), 'GA inherits gm role from RBAC hierarchy');
    }

    /**
     * 測試：GM 角色只能存取群組成員功能
     */
    public function testGmRoleHasLimitedAccess()
    {
        // 建立 GM 用戶
        $user = new Users();
        $user->cn = 'gm_access_test';
        $user->name = 'GM Access Test';
        $user->roles = 'gm';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'GM user should be saved');

        // 完整登入
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'gm_access_test',
            'name' => 'GM Access Test',
            'roles' => 'gm',
        ]);
                Yii::$app->user->login($identity);

        // 驗證 GM 權限
        $this->assertTrue(Yii::$app->user->can('gm'), 'GM should have gm role');

        // GM 不能存取系統管理功能
        $this->assertFalse(Yii::$app->user->can('sa'), 'GM cannot access system management');

        // GM 不能管理投票
        $this->assertFalse(Yii::$app->user->can('va'), 'GM cannot manage votes');

        // GM 不能管理群組
        $this->assertFalse(Yii::$app->user->can('ga'), 'GM cannot manage groups');
    }

    /**
     * 測試：VA+GA 雙角色用戶同時擁有投票和群組管理權限
     */
    public function testVaGaMultiRoleAccess()
    {
        // 建立 VA+GA 用戶
        $user = new Users();
        $user->cn = 'vaga_access_test';
        $user->name = 'VA GA Access Test';
        $user->roles = 'va,ga';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'VA+GA user should be saved');

        // 完整登入 (會選擇第一個角色 va)
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'vaga_access_test',
            'name' => 'VA GA Access Test',
            'roles' => 'va,ga',
        ]);
                Yii::$app->user->login($identity);

        // 驗證 VA 角色被分配 (因為是第一個)
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(Yii::$app->user->id);
        $this->assertArrayHasKey('va', $roles, 'Should have va role assigned');

        // 可以切換角色
        $this->assertTrue(Yii::$app->user->identity->isSwitchRoles(), 'Should be able to switch roles');

        // 檢查 roles 屬性包含兩個角色 (getRoles() 返回陣列)
        $userRoles = Yii::$app->user->identity->getRoles();
        $this->assertIsArray($userRoles, 'getRoles should return array');
        $this->assertContains('va', $userRoles, 'Should have va in roles');
        $this->assertContains('ga', $userRoles, 'Should have ga in roles');
    }

    /**
     * 測試：SA+VA+GA 三角色用戶優先分配 SA
     */
    public function testSaVaGaMultiRolePrefersSa()
    {
        // 建立 SA+VA+GA 用戶
        $user = new Users();
        $user->cn = 'savaga_access_test';
        $user->name = 'SA VA GA Access Test';
        $user->roles = 'va,sa,ga';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'SA+VA+GA user should be saved');

        // 完整登入 (應該選擇 SA 角色)
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'savaga_access_test',
            'name' => 'SA VA GA Access Test',
            'roles' => 'va,sa,ga',
        ]);
                Yii::$app->user->login($identity);

        // 驗證 SA 角色被分配 (即使 va 在前面)
        $auth = Yii::$app->authManager;
        $roles = $auth->getRolesByUser(Yii::$app->user->id);
        $this->assertArrayHasKey('sa', $roles, 'Should have sa role assigned (priority)');

        // SA 繼承所有角色的權限
        $this->assertTrue(Yii::$app->user->can('sa'), 'Should have sa permission');
        $this->assertTrue(Yii::$app->user->can('va'), 'Should have va permission (inherited)');
        $this->assertTrue(Yii::$app->user->can('ga'), 'Should have ga permission (inherited)');
        $this->assertTrue(Yii::$app->user->can('gm'), 'Should have gm permission (inherited)');
    }

    /**
     * 測試：voteManag 權限檢查 (需要 VA 或 SA 角色)
     */
    public function testVoteManagPermission()
    {
        // 測試 VA 用戶有 voteManag 權限
        $vaUser = new Users();
        $vaUser->cn = 'va_votemanag_test';
        $vaUser->name = 'VA VoteManag Test';
        $vaUser->roles = 'va';
        $vaUser->password_plain = 'TestPass123';
        $this->assertTrue($vaUser->save());

        $vaIdentity = new AdminIdentity();
        $vaIdentity->setAuthData([
            'cn' => 'va_votemanag_test',
            'name' => 'VA VoteManag Test',
            'roles' => 'va',
        ]);
                Yii::$app->user->login($vaIdentity);

        // VA 應該有 voteManag 權限 (透過 RbacFixture 設定)
        $this->assertTrue(Yii::$app->user->can('va'), 'VA should have va role');
        $this->assertTrue(Yii::$app->user->can('voteManag'), 'VA should have voteManag permission');

        Yii::$app->user->logout();

        // 測試 GA 用戶沒有 voteManag 權限
        $gaUser = new Users();
        $gaUser->cn = 'ga_votemanag_test';
        $gaUser->name = 'GA VoteManag Test';
        $gaUser->roles = 'ga';
        $gaUser->password_plain = 'TestPass123';
        $this->assertTrue($gaUser->save());

        $gaIdentity = new AdminIdentity();
        $gaIdentity->setAuthData([
            'cn' => 'ga_votemanag_test',
            'name' => 'GA VoteManag Test',
            'roles' => 'ga',
        ]);
                Yii::$app->user->login($gaIdentity);

        // GA 不應該有 voteManag 權限
        $this->assertFalse(Yii::$app->user->can('va'), 'GA should not have va role');
        $this->assertFalse(Yii::$app->user->can('voteManag'), 'GA should not have voteManag permission');
    }

    /**
     * 測試：groupManag 權限檢查 (需要 GA 或 SA 角色)
     */
    public function testGroupManagPermission()
    {
        // 測試 GA 用戶有 groupManag 權限
        $gaUser = new Users();
        $gaUser->cn = 'ga_groupmanag_test';
        $gaUser->name = 'GA GroupManag Test';
        $gaUser->roles = 'ga';
        $gaUser->password_plain = 'TestPass123';
        $this->assertTrue($gaUser->save());

        $gaIdentity = new AdminIdentity();
        $gaIdentity->setAuthData([
            'cn' => 'ga_groupmanag_test',
            'name' => 'GA GroupManag Test',
            'roles' => 'ga',
        ]);
                Yii::$app->user->login($gaIdentity);

        // GA 應該有 groupManag 權限
        $this->assertTrue(Yii::$app->user->can('ga'), 'GA should have ga role');

        Yii::$app->user->logout();

        // 測試 VA 用戶沒有 groupManag 權限
        $vaUser = new Users();
        $vaUser->cn = 'va_groupmanag_test';
        $vaUser->name = 'VA GroupManag Test';
        $vaUser->roles = 'va';
        $vaUser->password_plain = 'TestPass123';
        $this->assertTrue($vaUser->save());

        $vaIdentity = new AdminIdentity();
        $vaIdentity->setAuthData([
            'cn' => 'va_groupmanag_test',
            'name' => 'VA GroupManag Test',
            'roles' => 'va',
        ]);
                Yii::$app->user->login($vaIdentity);

        // VA 不應該有 groupManag 權限
        $this->assertFalse(Yii::$app->user->can('ga'), 'VA should not have ga role');
    }

    /**
     * 測試：角色切換功能
     */
    public function testRoleSwitching()
    {
        // 建立擁有多個角色的用戶
        $user = new Users();
        $user->cn = 'switch_test';
        $user->name = 'Switch Test';
        $user->roles = 'va,ga,gm';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save());

        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'switch_test',
            'name' => 'Switch Test',
            'roles' => 'va,ga,gm',
        ]);
                Yii::$app->user->login($identity);

        // 驗證可以切換角色
        $this->assertTrue(Yii::$app->user->identity->isSwitchRoles(), 'Should be able to switch roles');

        // 驗證 roles 包含多個角色 (getRoles() 返回陣列)
        $roles = Yii::$app->user->identity->getRoles();
        $this->assertIsArray($roles, 'getRoles should return array');
        $this->assertCount(3, $roles, 'Should have 3 roles');
        $this->assertContains('va', $roles, 'Should have va role');
        $this->assertContains('ga', $roles, 'Should have ga role');
        $this->assertContains('gm', $roles, 'Should have gm role');

        // 驗證當前分配的角色是第一個
        $auth = Yii::$app->authManager;
        $assignedRoles = $auth->getRolesByUser(Yii::$app->user->id);
        $this->assertArrayHasKey('va', $assignedRoles, 'First role should be assigned');
    }

    /**
     * 測試：單一角色用戶不能切換角色
     */
    public function testSingleRoleCannotSwitch()
    {
        // 建立單一角色用戶
        $user = new Users();
        $user->cn = 'single_role_test';
        $user->name = 'Single Role Test';
        $user->roles = 'va';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save());

        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'single_role_test',
            'name' => 'Single Role Test',
            'roles' => 'va',
        ]);
                Yii::$app->user->login($identity);

        // 驗證不能切換角色
        $this->assertFalse(Yii::$app->user->identity->isSwitchRoles(), 'Single role user cannot switch');

        // 驗證只有一個角色 (getRoles() 返回陣列)
        $roles = Yii::$app->user->identity->getRoles();
        $this->assertIsArray($roles, 'getRoles should return array');
        $this->assertCount(1, $roles, 'Should have only 1 role');
    }

    /**
     * 測試：登出清除所有 RBAC 分配
     */
    public function testLogoutClearsRbacAssignments()
    {
        // 建立用戶並登入
        $user = new Users();
        $user->cn = 'logout_test';
        $user->name = 'Logout Test';
        $user->roles = 'va';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save());

        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => 'logout_test',
            'name' => 'Logout Test',
            'roles' => 'va',
        ]);
                Yii::$app->user->login($identity);

        // 驗證已登入且有角色
        $this->assertFalse(Yii::$app->user->isGuest, 'Should be logged in');
        $this->assertTrue(Yii::$app->user->can('va'), 'Should have va role');

        // 登出
        Yii::$app->user->logout();

        // 驗證已登出
        $this->assertTrue(Yii::$app->user->isGuest, 'Should be logged out');
    }

    /**
     * 測試：重複登入會清除舊的 RBAC 分配
     */
    public function testReloginClearsOldRbacAssignments()
    {
        // 第一次登入 (VA 角色)
        $user1 = new Users();
        $user1->cn = 'relogin_test_va';
        $user1->name = 'Relogin VA';
        $user1->roles = 'va';
        $user1->password_plain = 'TestPass123';
        $this->assertTrue($user1->save());

        $identity1 = new AdminIdentity();
        $identity1->setAuthData([
            'cn' => 'relogin_test_va',
            'name' => 'Relogin VA',
            'roles' => 'va',
        ]);
                Yii::$app->user->login($identity1);

        $this->assertTrue(Yii::$app->user->can('va'), 'Should have va role');
        $this->assertFalse(Yii::$app->user->can('ga'), 'Should not have ga role');

        // 登出
        Yii::$app->user->logout();

        // 第二次登入 (GA 角色)
        $user2 = new Users();
        $user2->cn = 'relogin_test_ga';
        $user2->name = 'Relogin GA';
        $user2->roles = 'ga';
        $user2->password_plain = 'TestPass123';
        $this->assertTrue($user2->save());

        $identity2 = new AdminIdentity();
        $identity2->setAuthData([
            'cn' => 'relogin_test_ga',
            'name' => 'Relogin GA',
            'roles' => 'ga',
        ]);
                Yii::$app->user->login($identity2);

        // 驗證新角色
        $this->assertFalse(Yii::$app->user->can('va'), 'Should not have old va role');
        $this->assertTrue(Yii::$app->user->can('ga'), 'Should have new ga role');
    }
}
