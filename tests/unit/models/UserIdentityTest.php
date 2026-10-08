<?php

namespace app\tests\unit\models;

use Yii;
use app\models\UserIdentity;
use app\components\AdminIdentity;
use app\components\Anon;
use Codeception\Test\Unit;

/**
 * UserIdentity 基類測試
 * 測試 UserIdentity 的核心功能（AdminIdentity 和 Anon 的基類）
 */
class UserIdentityTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * 測試用的 UserIdentity 子類
     */
    protected $identity;

    protected function _before()
    {
        // 清除 session
        Yii::$app->session->removeAll();

        // 創建測試用的 identity
        $this->identity = new AdminIdentity();
    }

    protected function _after()
    {
        // 清除 session
        Yii::$app->session->removeAll();
    }

    /**
     * 測試：UserIdentity 基本結構
     */
    public function testUserIdentityStructure()
    {
        $identity = new AdminIdentity();

        // 驗證實現 IdentityInterface
        $this->assertInstanceOf(\yii\web\IdentityInterface::class, $identity,
            'Should implement IdentityInterface');

        // 驗證實現 UserIdentityInterface
        $this->assertInstanceOf(\app\interfaces\UserIdentityInterface::class, $identity,
            'Should implement UserIdentityInterface');

        // 驗證屬性存在
        $this->assertTrue(property_exists($identity, 'authData'), 'Should have authData property');
        $this->assertTrue(property_exists($identity, 'role'), 'Should have role property');
        $this->assertTrue(property_exists($identity, 'roles'), 'Should have roles property');
    }

    /**
     * 測試：setAuthData() 和 getAuthData()
     */
    public function testSetAndGetAuthData()
    {
        $testData = [
            'id' => 123,
            'cn' => 'test_user',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        // 設定 authData
        $this->identity->setAuthData($testData);

        // 取得全部 authData
        $retrieved = $this->identity->getAuthData();
        $this->assertIsArray($retrieved, 'Should return array');
        $this->assertEquals($testData, $retrieved, 'Should match set data');

        // 取得單一 key
        $this->assertEquals(123, $this->identity->getAuthData('id'), 'Should get id');
        $this->assertEquals('test_user', $this->identity->getAuthData('cn'), 'Should get cn');
        $this->assertEquals('Test User', $this->identity->getAuthData('name'), 'Should get name');
        $this->assertEquals('test@example.com', $this->identity->getAuthData('email'), 'Should get email');
    }

    /**
     * 測試：getAuthData() 不存在的 key
     */
    public function testGetAuthDataNonExistentKey()
    {
        $this->identity->setAuthData(['id' => 123]);

        // 取得不存在的 key（指定 key 時不存在回傳 null，避免誤回傳整包陣列）
        $result = $this->identity->getAuthData('non_existent');
        $this->assertNull($result, 'Should return null when key not exists');
    }

    /**
     * 測試：setRole() 和 getRole()
     */
    public function testSetAndGetRole()
    {
        // 設定角色
        $this->identity->setRole('sa');

        // 取得角色
        $role = $this->identity->getRole();
        $this->assertEquals('sa', $role, 'Should get sa role');

        // 變更角色
        $this->identity->setRole('va');
        $this->assertEquals('va', $this->identity->getRole(), 'Should get updated role');
    }

    /**
     * 測試：setRoles() 和 getRoles()
     */
    public function testSetAndGetRoles()
    {
        // 設定多個角色
        $roles = ['sa', 'va', 'ga'];
        $this->identity->setRoles($roles);

        // 取得角色
        $retrieved = $this->identity->getRoles();
        $this->assertIsArray($retrieved, 'Should return array');
        $this->assertEquals($roles, $retrieved, 'Should match set roles');
        $this->assertCount(3, $retrieved, 'Should have 3 roles');
    }

    /**
     * 測試：isSwitchRoles() 單一角色
     */
    public function testIsSwitchRolesSingleRole()
    {
        // 單一角色
        $this->identity->setRoles(['va']);

        $result = $this->identity->isSwitchRoles();
        $this->assertFalse($result, 'Single role should not be switchable');
    }

    /**
     * 測試：isSwitchRoles() 多個角色
     */
    public function testIsSwitchRolesMultipleRoles()
    {
        // 多個角色
        $this->identity->setRoles(['va', 'ga', 'gm']);

        $result = $this->identity->isSwitchRoles();
        $this->assertTrue($result, 'Multiple roles should be switchable');
    }

    /**
     * 測試：isSwitchRoles() 空角色
     */
    public function testIsSwitchRolesEmptyRoles()
    {
        // 空角色
        $this->identity->setRoles([]);

        $result = $this->identity->isSwitchRoles();
        $this->assertFalse($result, 'Empty roles should not be switchable');
    }

    /**
     * 測試：inUserRole() 字串比對
     */
    public function testInUserRoleStringMatch()
    {
        $this->identity->setRole('va');

        // 相符
        $this->assertTrue($this->identity->inUserRole('va'), 'Should match va role');

        // 不相符
        $this->assertFalse($this->identity->inUserRole('sa'), 'Should not match sa role');
    }

    /**
     * 測試：inUserRole() 陣列比對
     */
    public function testInUserRoleArrayMatch()
    {
        $this->identity->setRole('va');

        // 在陣列中
        $this->assertTrue($this->identity->inUserRole(['sa', 'va', 'ga']),
            'Should match when role in array');

        // 不在陣列中
        $this->assertFalse($this->identity->inUserRole(['sa', 'gm']),
            'Should not match when role not in array');
    }

    /**
     * 測試：inUserRole() 無效參數
     */
    public function testInUserRoleInvalidParameter()
    {
        $this->identity->setRole('va');

        // 數字參數
        $this->assertFalse($this->identity->inUserRole(123),
            'Should return false for invalid parameter type');

        // null
        $this->assertFalse($this->identity->inUserRole(null),
            'Should return false for null');
    }

    /**
     * 測試：setAttr() 和 getAttr()
     */
    public function testSetAndGetAttr()
    {
        $key = 'test_key';
        $value = 'test_value';

        // 設定屬性
        $this->identity->setAttr($key, $value);

        // 取得屬性
        $retrieved = $this->identity->getAttr($key);
        $this->assertEquals($value, $retrieved, 'Should get set value');
    }

    /**
     * 測試：getAttr() 不存在的 key
     */
    public function testGetAttrNonExistentKey()
    {
        $result = $this->identity->getAttr('non_existent_key');
        $this->assertNull($result, 'Should return null for non-existent key');
    }

    /**
     * 測試：getKey() 產生正確的 session key
     */
    public function testGetKey()
    {
        $identity = new AdminIdentity();

        // getFlag() 應該返回 'Admin' (移除 'Identity' 後綴)
        $key = $identity->getKey('test');
        $this->assertEquals('Admin.test', $key, 'Should generate Admin.test');

        // Anon 的例子
        $anonIdentity = new Anon();
        $anonKey = $anonIdentity->getKey('test');
        $this->assertEquals('Anon.test', $anonKey, 'Should generate Anon.test');
    }

    /**
     * 測試：getFlag()
     */
    public function testGetFlag()
    {
        // AdminIdentity -> Admin
        $adminIdentity = new AdminIdentity();
        $this->assertEquals('Admin', $adminIdentity->getFlag(),
            'AdminIdentity should return Admin');

        // Anon -> Anon
        $anonIdentity = new Anon();
        $this->assertEquals('Anon', $anonIdentity->getFlag(),
            'Anon should return Anon');
    }

    /**
     * 測試：getId() 從 authData 取得 ID
     */
    public function testGetId()
    {
        // AdminIdentity 的 unique key 是 'cn'
        $this->identity->setAuthData(['cn' => 'test_user', 'name' => 'Test User']);
        $id = $this->identity->getId();
        $this->assertEquals('test_user', $id, 'Should get id from unique key');

        // Anon 的 unique key 是 'id'
        $anonIdentity = new Anon();
        $anonIdentity->setAuthData(['id' => 123]);
        $anonId = $anonIdentity->getId();
        $this->assertEquals(123, $anonId, 'Should get numeric id');
    }

    /**
     * 測試：getCn() 取得帳號
     */
    public function testGetCn()
    {
        $this->identity->setAuthData(['cn' => 'test_account']);

        $cn = $this->identity->getCn();
        $this->assertEquals('test_account', $cn, 'Should get cn');
    }

    /**
     * 測試：getName() 取得姓名
     */
    public function testGetName()
    {
        $this->identity->setAuthData(['name' => 'Test User']);

        $name = $this->identity->getName();
        $this->assertEquals('Test User', $name, 'Should get name');
    }

    /**
     * 測試：syncAttr() 同步屬性
     */
    public function testSyncAttr()
    {
        // 設定各種屬性
        $this->identity->setAuthData(['id' => 123, 'name' => 'Test']);
        $this->identity->setRole('va');
        $this->identity->setRoles(['va', 'ga']);

        // 呼叫 syncAttr
        $result = $this->identity->syncAttr();
        $this->assertTrue($result, 'syncAttr should return true');

        // 驗證屬性可以取得
        $this->assertEquals(['id' => 123, 'name' => 'Test'], $this->identity->getAuthData());
        $this->assertEquals('va', $this->identity->getRole());
        $this->assertEquals(['va', 'ga'], $this->identity->getRoles());
    }

    /**
     * 測試：setIdentity() 和 getIdentity()
     */
    public function testSetAndGetIdentity()
    {
        // Identity 以 AuthData 持久化，不再把物件塞進 session
        $this->identity->setAuthData(['cn' => 'test_user', 'name' => 'Test']);
        $this->identity->setIdentity();

        $retrieved = $this->identity->getIdentity();
        $this->assertInstanceOf(UserIdentity::class, $retrieved,
            'Should return UserIdentity instance');
        $this->assertSame($this->identity, $retrieved,
            'Should return same instance');
        $this->assertNull($this->identity->getAttr(UserIdentity::ATTR_IDENTITY),
            'Should not store Identity object in session');
    }

    /**
     * 測試：Session-based storage
     */
    public function testSessionBasedStorage()
    {
        // 設定數據到 session
        $this->identity->setAuthData(['test' => 'value']);
        $this->identity->setRole('sa');

        // 清除本地物件
        unset($this->identity);

        // 創建新的 identity 並檢查是否能從 session 讀取
        $newIdentity = new AdminIdentity();
        $storedAuth = $newIdentity->getAttr(UserIdentity::ATTR_AUTH_DATA);
        $storedRole = $newIdentity->getAttr(UserIdentity::ATTR_ROLE);

        $this->assertEquals(['test' => 'value'], $storedAuth,
            'Should retrieve authData from session');
        $this->assertEquals('sa', $storedRole,
            'Should retrieve role from session');
    }

    /**
     * 測試：findIdentity() 靜態方法
     */
    public function testFindIdentity()
    {
        // AdminIdentity::getUnique() = cn
        $this->identity->setAuthData(['cn' => 'test_user', 'name' => 'Test']);

        $found = AdminIdentity::findIdentity('test_user');
        $this->assertInstanceOf(UserIdentity::class, $found,
            'Should return UserIdentity instance');
        $this->assertEquals('test_user', $found->getId());
        $this->assertNull(AdminIdentity::findIdentity('other'),
            'Should return null when cn mismatches');
    }

    /**
     * 測試：常數定義
     */
    public function testConstants()
    {
        // 驗證常數存在
        $this->assertTrue(defined('app\models\UserIdentity::ATTR_IDENTITY'),
            'ATTR_IDENTITY constant should exist');
        $this->assertTrue(defined('app\models\UserIdentity::ATTR_AUTH_DATA'),
            'ATTR_AUTH_DATA constant should exist');
        $this->assertTrue(defined('app\models\UserIdentity::ATTR_ROLE'),
            'ATTR_ROLE constant should exist');
        $this->assertTrue(defined('app\models\UserIdentity::ATTR_ROLES'),
            'ATTR_ROLES constant should exist');
    }

    /**
     * 測試：多個 identity 實例隔離
     */
    public function testMultipleInstancesIsolation()
    {
        // 第一個 identity
        $identity1 = new AdminIdentity();
        $identity1->setAuthData(['id' => 1, 'name' => 'User 1']);
        $identity1->setRole('sa');

        // 第二個 identity (不同類型)
        $identity2 = new Anon();
        $identity2->setAuthData(['id' => 2, 'name' => 'User 2']);

        // 驗證數據隔離
        $this->assertEquals(1, $identity1->getAuthData('id'), 'Identity 1 should have id 1');
        $this->assertEquals(2, $identity2->getAuthData('id'), 'Identity 2 should have id 2');
        $this->assertEquals('sa', $identity1->getRole(), 'Identity 1 should have sa role');
    }
}
