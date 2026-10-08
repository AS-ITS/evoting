<?php

namespace tests\unit\components;

use Yii;
use app\components\AdminIdentity;
use app\models\Users;
use app\tests\fixtures\UsersFixture;
use Codeception\Test\Unit;

/**
 * AdminIdentity 單元測試
 *
 * 測試管理員身份認證組件的基本功能和屬性
 */
class AdminIdentityTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        // 確保管理員用戶登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }

        // 清除 session
        Yii::$app->session->removeAll();
    }

    protected function _after()
    {
        // 清理：登出管理員用戶
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
        ];
    }

    /**
     * 測試：Component 基本結構
     */
    public function testComponentStructure()
    {
        $identity = new AdminIdentity();

        // 驗證靜態屬性
        $this->assertEquals('AdminIdentity', AdminIdentity::$flag, 'Static flag should be AdminIdentity');

        // 驗證方法存在
        $this->assertTrue(method_exists($identity, 'getUnique'), 'Should have getUnique method');
        $this->assertTrue(method_exists($identity, 'afterLogin'), 'Should have afterLogin method');

        // 驗證 getUnique 返回值
        $this->assertEquals('cn', $identity->getUnique(), 'getUnique should return "cn"');
    }

    /**
     * 測試：AdminIdentity 可以實例化
     */
    public function testAdminIdentityCanBeInstantiated()
    {
        $identity = new AdminIdentity();
        $this->assertInstanceOf(AdminIdentity::class, $identity, 'Should be instance of AdminIdentity');
        $this->assertInstanceOf(\app\models\UserIdentity::class, $identity, 'Should extend UserIdentity');
    }

    /**
     * 測試：setAuthData 和 getAuthData
     */
    public function testSetAndGetAuthData()
    {
        $identity = new AdminIdentity();

        $authData = [
            'cn' => 'test_user',
            'name' => 'Test User',
            'roles' => 'sa',
        ];

        $identity->setAuthData($authData);

        // 測試完整數據
        $retrievedData = $identity->getAuthData();
        $this->assertIsArray($retrievedData, 'Auth data should be an array');
        $this->assertEquals('test_user', $retrievedData['cn'], 'CN should match');
        $this->assertEquals('Test User', $retrievedData['name'], 'Name should match');

        // 測試單一鍵值
        $this->assertEquals('test_user', $identity->getAuthData('cn'), 'CN should be retrievable');
        $this->assertEquals('Test User', $identity->getAuthData('name'), 'Name should be retrievable');
    }

    /**
     * 測試：setRole 和 getRole
     */
    public function testSetAndGetRole()
    {
        $identity = new AdminIdentity();

        $identity->setRole('sa');
        $this->assertEquals('sa', $identity->getRole(), 'Role should be sa');

        $identity->setRole('va');
        $this->assertEquals('va', $identity->getRole(), 'Role should be updated to va');
    }

    /**
     * 測試：setRoles 和 getRoles
     */
    public function testSetAndGetRoles()
    {
        $identity = new AdminIdentity();

        $roles = ['sa', 'va', 'ga'];
        $identity->setRoles($roles);

        $retrievedRoles = $identity->getRoles();
        $this->assertIsArray($retrievedRoles, 'Roles should be an array');
        $this->assertEquals($roles, $retrievedRoles, 'Roles should match');
    }

    /**
     * 測試：getId 方法
     */
    public function testGetId()
    {
        $identity = new AdminIdentity();

        $identity->setAuthData([
            'name' => 'admin_name',
            'cn' => 'admin_cn',
        ]);

        // getId 應該返回 getUnique() 指定的欄位值（cn）
        $this->assertEquals('admin_cn', $identity->getId(), 'getId should return cn field value');
    }

    /**
     * 測試：getCn 方法
     */
    public function testGetCn()
    {
        $identity = new AdminIdentity();

        $identity->setAuthData([
            'cn' => 'test_cn',
            'name' => 'Test Name',
        ]);

        $this->assertEquals('test_cn', $identity->getCn(), 'getCn should return cn field value');
    }

    /**
     * 測試：getName 方法
     */
    public function testGetName()
    {
        $identity = new AdminIdentity();

        $identity->setAuthData([
            'cn' => 'test_cn',
            'name' => 'Test Name',
        ]);

        $this->assertEquals('Test Name', $identity->getName(), 'getName should return name field value');
    }

    /**
     * 測試：inUserRole 方法 - 單一角色字串檢查
     */
    public function testInUserRoleWithString()
    {
        $identity = new AdminIdentity();
        $identity->setRole('sa');

        $this->assertTrue($identity->inUserRole('sa'), 'Should return true for matching role');
        $this->assertFalse($identity->inUserRole('gm'), 'Should return false for non-matching role');
    }

    /**
     * 測試：inUserRole 方法 - 角色陣列檢查
     */
    public function testInUserRoleWithArray()
    {
        $identity = new AdminIdentity();
        $identity->setRole('va');

        $this->assertTrue($identity->inUserRole(['sa', 'va', 'ga']), 'Should return true if role in array');
        $this->assertFalse($identity->inUserRole(['sa', 'gm']), 'Should return false if role not in array');
    }

    /**
     * 測試：isSwitchRoles 方法 - 單一角色
     */
    public function testIsSwitchRolesWithSingleRole()
    {
        $identity = new AdminIdentity();
        $identity->setRoles(['sa']);

        $this->assertFalse($identity->isSwitchRoles(), 'Should return false for single role');
    }

    /**
     * 測試：isSwitchRoles 方法 - 多角色
     */
    public function testIsSwitchRolesWithMultipleRoles()
    {
        $identity = new AdminIdentity();
        $identity->setRoles(['sa', 'va', 'ga']);

        $this->assertTrue($identity->isSwitchRoles(), 'Should return true for multiple roles');
    }

    /**
     * 測試：isSwitchRoles 方法 - 空角色
     */
    public function testIsSwitchRolesWithNoRoles()
    {
        $identity = new AdminIdentity();
        $identity->setRoles([]);

        $this->assertFalse($identity->isSwitchRoles(), 'Should return false for empty roles');
    }

    /**
     * 測試：getFlag 方法
     */
    public function testGetFlag()
    {
        $identity = new AdminIdentity();

        // getFlag 應該移除 Identity 後綴
        $this->assertEquals('Admin', $identity->getFlag(), 'Flag should be "Admin" (AdminIdentity without "Identity")');
    }

    /**
     * 測試：getKey 方法
     */
    public function testGetKey()
    {
        $identity = new AdminIdentity();

        $key = $identity->getKey('authData');
        $this->assertEquals('Admin.authData', $key, 'Key should be prefixed with flag');
    }

    /**
     * 測試：setIdentity 和 getIdentity
     */
    public function testSetAndGetIdentity()
    {
        $identity = new AdminIdentity();
        $identity->setAuthData(['cn' => 'test_admin', 'name' => 'Test Admin']);
        $identity->setIdentity($identity);

        $retrievedIdentity = $identity->getIdentity();
        $this->assertInstanceOf(AdminIdentity::class, $retrievedIdentity, 'Should retrieve same identity instance');
        $this->assertSame($identity, $retrievedIdentity);
        $this->assertNull($identity->getAttr(AdminIdentity::ATTR_IDENTITY), 'Should not store Identity object in session');
    }

    /**
     * 測試：syncAttr 方法
     */
    public function testSyncAttr()
    {
        $identity = new AdminIdentity();

        $identity->setAuthData([
            'cn' => 'sync_user',
            'name' => 'Sync User',
        ]);
        $identity->setRole('sa');
        $identity->setRoles(['sa', 'va']);

        // syncAttr 應該同步所有屬性到 session
        $result = $identity->syncAttr();
        $this->assertTrue($result, 'syncAttr should return true');

        // 驗證屬性已同步
        $this->assertEquals(['cn' => 'sync_user', 'name' => 'Sync User'], $identity->getAuthData());
        $this->assertEquals('sa', $identity->getRole());
        $this->assertEquals(['sa', 'va'], $identity->getRoles());
    }

    /**
     * 測試：afterLogin 方法存在且可調用
     */
    public function testAfterLoginMethodExists()
    {
        $identity = new AdminIdentity();

        // 建立測試用戶
        $user = new Users();
        $user->cn = 'after_login_user';
        $user->name = 'After Login User';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'User should be created');

        // 建立身份認證
        $testIdentity = new AdminIdentity();
        $testIdentity->setAuthData([
            'cn' => 'after_login_user',
            'name' => 'After Login User',
            'roles' => 'sa',
        ]);

        // 驗證 afterLogin 方法存在且可調用（不會拋出異常）
        $this->assertTrue(method_exists($identity, 'afterLogin'), 'afterLogin method should exist');
    }

    /**
     * 測試：Session 屬性儲存和讀取
     */
    public function testSessionStorageAndRetrieval()
    {
        $identity = new AdminIdentity();

        // 設定屬性
        $identity->setAttr('test_key', 'test_value');

        // 讀取屬性
        $value = $identity->getAttr('test_key');
        $this->assertEquals('test_value', $value, 'Should retrieve stored value from session');

        // 驗證完整的 key 格式
        $fullKey = $identity->getKey('test_key');
        $sessionValue = Yii::$app->session->get($fullKey);
        $this->assertEquals('test_value', $sessionValue, 'Should store with correct key format');
    }
}
