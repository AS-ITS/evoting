<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormGroup;
use app\models\Group;
use app\models\GroupMember;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\GroupFixture;
use app\tests\fixtures\GroupMemberFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\ConfigFixture;
use app\components\AdminIdentity;

/**
 * FormGroup 模型測試
 * 測試群組表單模型的各項功能
 */
class FormGroupTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // 載入 fixtures
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
            'group' => GroupFixture::class,
            'groupMember' => GroupMemberFixture::class,
        ]);

        // 確保登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }
        Yii::$app->session->removeAll();
    }

    protected function _after()
    {
        // 清理：登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }
        Yii::$app->session->removeAll();
    }

    /**
     * 輔助方法：建立並登入使用者
     */
    private function loginUser($cn, $name, $roles)
    {
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => $cn,
            'name' => $name,
            'roles' => $roles,
        ]);
        // 使用 switchIdentity 繞過 afterLogin 事件（避免測試環境中的 Config 查詢問題）
        Yii::$app->user->switchIdentity($identity);

        // 手動設置 RBAC 角色（繞過 afterLogin）
        $auth = Yii::$app->authManager;
        $auth->revokeAll($cn); // 使用 cn 作為 userId (因為 getId() 現在返回 cn)
        $role = $auth->getRole($roles);
        if ($role) {
            $auth->assign($role, $cn);
        }

        // 確保 identity 可以正確訪問
        $this->assertNotNull(Yii::$app->user->identity, 'User identity should be set after login');
    }

    // ==================== 基本結構測試 ====================

    /**
     * 測試：FormGroup 繼承 Group
     */
    public function testFormGroupExtendsGroup()
    {
        $model = new FormGroup();
        $this->assertInstanceOf(Group::class, $model, 'FormGroup should extend Group');
    }

    /**
     * 測試：tableName 正確
     */
    public function testTableName()
    {
        $this->assertEquals('group', FormGroup::tableName(), 'Table name should be "group"');
    }

    /**
     * 測試：attributeLabels 包含所有欄位
     */
    public function testAttributeLabels()
    {
        $model = new FormGroup();
        $labels = $model->attributeLabels();

        $this->assertArrayHasKey('groupId', $labels);
        $this->assertArrayHasKey('groupName', $labels);
        $this->assertArrayHasKey('isRoutine', $labels);

        $this->assertEquals('群組 ID', $labels['groupId']);
        $this->assertEquals('群組名稱', $labels['groupName']);
        $this->assertEquals('例行投票與否', $labels['isRoutine']);
    }

    // ==================== 驗證規則測試 ====================

    /**
     * 測試：create scenario 必填欄位
     */
    public function testCreateScenarioRequiredFields()
    {
        $model = new FormGroup();
        $model->scenario = 'create';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('groupName', $model->errors);
        $this->assertArrayHasKey('isRoutine', $model->errors);
    }

    /**
     * 測試：update scenario 必填欄位
     */
    public function testUpdateScenarioRequiredFields()
    {
        $model = new FormGroup();
        $model->scenario = 'update';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('groupName', $model->errors);
        $this->assertArrayHasKey('isRoutine', $model->errors);
    }

    /**
     * 測試：groupName 最大長度驗證
     */
    public function testGroupNameMaxLength()
    {
        $model = new FormGroup();
        $model->scenario = 'create';
        $model->groupName = str_repeat('A', 101); // 超過 100 字元
        $model->isRoutine = 'Y';

        $this->assertFalse($model->validate(['groupName']), 'Should fail with name > 100 chars');
        $this->assertArrayHasKey('groupName', $model->errors);
    }

    /**
     * 測試：isRoutine 必須在允許範圍內
     */
    public function testIsRoutineValidation()
    {
        $model = new FormGroup();
        $model->scenario = 'create';
        $model->groupName = 'Test Group';
        $model->isRoutine = 'INVALID';

        $this->assertFalse($model->validate(['isRoutine']), 'Should fail with invalid isRoutine value');
        $this->assertArrayHasKey('isRoutine', $model->errors);
    }

    /**
     * 測試：有效的資料通過驗證
     */
    public function testValidDataPassesValidation()
    {
        $model = new FormGroup();
        $model->scenario = 'create';
        $model->groupName = 'Test Group';
        $model->isRoutine = 'Y';

        $this->assertTrue($model->validate(), 'Valid data should pass validation');
    }

    // ==================== getGroupList() 測試 ====================

    /**
     * 測試：getGroupList('all') 返回所有群組
     */
    public function testGetGroupListAll()
    {
        $this->loginUser('sa_test', 'SA Test', 'sa');

        $model = new FormGroup();
        $dataProvider = $model->getGroupList('all');

        $this->assertInstanceOf(\yii\data\ActiveDataProvider::class, $dataProvider);
        $this->assertGreaterThan(0, $dataProvider->getTotalCount(), 'Should return groups');
    }

    /**
     * 測試：getGroupList('member') 只返回成員所在群組
     */
    public function testGetGroupListMember()
    {
        $this->loginUser('gm_test', 'GM Test', 'gm');

        $model = new FormGroup();
        $dataProvider = $model->getGroupList('member');

        $this->assertInstanceOf(\yii\data\ActiveDataProvider::class, $dataProvider);
        // gm_test 是群組1的成員
        $groups = $dataProvider->getModels();
        $this->assertNotEmpty($groups, 'Should return member groups');
    }

    // ==================== getGroups() 測試 ====================

    /**
     * 測試：getGroups('all') 返回所有群組陣列
     */
    public function testGetGroupsAll()
    {
        $this->loginUser('sa_test', 'SA Test', 'sa');

        $model = new FormGroup();
        $groups = $model->getGroups('all');

        $this->assertIsArray($groups);
        $this->assertGreaterThan(0, count($groups), 'Should return groups array');
    }

    /**
     * 測試：getGroups('member') 返回成員所在群組陣列
     */
    public function testGetGroupsMember()
    {
        $this->loginUser('gm_test', 'GM Test', 'gm');

        $model = new FormGroup();
        $groups = $model->getGroups('member');

        $this->assertIsArray($groups);
        $this->assertNotEmpty($groups, 'Should return member groups array');
    }

    // ==================== getGroupInfo() 測試 ====================

    /**
     * 測試：getGroupInfo() 返回特定群組資訊
     */
    public function testGetGroupInfo()
    {
        $this->loginUser('sa_test', 'SA Test', 'sa');

        $model = new FormGroup();
        $dataProvider = $model->getGroupInfo(1);

        $this->assertInstanceOf(\yii\data\ActiveDataProvider::class, $dataProvider);
        $this->assertEquals(1, $dataProvider->getTotalCount(), 'Should return one group');
    }

    /**
     * 測試：getGroupInfo() 查詢不存在的群組
     */
    public function testGetGroupInfoNonExistent()
    {
        $this->loginUser('sa_test', 'SA Test', 'sa');

        $model = new FormGroup();
        $dataProvider = $model->getGroupInfo(999);

        $this->assertInstanceOf(\yii\data\ActiveDataProvider::class, $dataProvider);
        $this->assertEquals(0, $dataProvider->getTotalCount(), 'Should return no group');
    }

    // ==================== createBase() 測試 ====================

    /**
     * 測試：成功建立群組
     *
     * 修復歷史：
     * - 2024-06-12 (Alan): FormGroup::createBase() Line 131 從 getId() 改為 getCn()
     * - 2026-01-06 (Fisher): AdminIdentity::getUnique() 從 'name' 改為 'cn'
     */
    public function testCreateBaseSuccess()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroup();
        $post = [
            'FormGroup' => [
                'groupName' => '新測試群組',
                'isRoutine' => 'Y',
            ]
        ];

        $result = $model->createBase($post);
        $this->assertTrue($result, 'Should create group successfully');
        $this->assertNotNull($model->groupId, 'Group ID should be set');

        // 驗證創建者被自動加入為成員（isWrite=Y, isOwner=Y）
        // 修復後使用 getCn()，所以 cn 欄位應該是 'ga_test'（帳號）
        $member = GroupMember::findOne(['groupId' => $model->groupId, 'cn' => 'ga_test']);
        $this->assertNotNull($member, 'Creator should be added as member');
        $this->assertEquals('Y', $member->isWrite, 'Creator should have write permission');
        $this->assertEquals('Y', $member->isOwner, 'Creator should have owner permission');
    }

    /**
     * 測試：建立群組失敗（缺少必填欄位）
     */
    public function testCreateBaseFailMissingFields()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroup();
        $post = [
            'FormGroup' => [
                // 缺少 groupName
                'isRoutine' => 'Y',
            ]
        ];

        $result = $model->createBase($post);
        $this->assertFalse($result, 'Should fail to create group');
        $this->assertArrayHasKey('groupName', $model->errors);
    }

    /**
     * 測試：建立群組失敗（無效的 isRoutine 值）
     */
    public function testCreateBaseFailInvalidIsRoutine()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroup();
        $post = [
            'FormGroup' => [
                'groupName' => '測試群組',
                'isRoutine' => 'INVALID',
            ]
        ];

        $result = $model->createBase($post);
        $this->assertFalse($result, 'Should fail with invalid isRoutine');
        $this->assertArrayHasKey('isRoutine', $model->errors);
    }

    // ==================== updateBase() 測試 ====================

    /**
     * 測試：成功更新群組
     */
    public function testUpdateBaseSuccess()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $post = [
            'FormGroup' => [
                'groupName' => '更新後的群組名稱',
                'isRoutine' => 'N',
            ]
        ];

        $model = new FormGroup();
        $result = $model->updateBase(1, $post);

        $this->assertTrue($result, 'Should update group successfully');

        // 驗證更新
        $group = Group::findOne(1);
        $this->assertEquals('更新後的群組名稱', $group->groupName);
        $this->assertEquals('N', $group->isRoutine);
    }

    /**
     * 測試：更新群組失敗（groupName 設為空）
     */
    public function testUpdateBaseFailMissingFields()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $post = [
            'FormGroup' => [
                'groupName' => '', // 明確設為空
                'isRoutine' => 'Y',
            ]
        ];

        $model = new FormGroup();
        $result = $model->updateBase(1, $post);

        $this->assertFalse($result, 'Should fail to update group with empty groupName');
    }

    /**
     * 測試：更新不存在的群組
     */
    public function testUpdateBaseNonExistentGroup()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $post = [
            'FormGroup' => [
                'groupName' => '測試',
                'isRoutine' => 'Y',
            ]
        ];

        // updateBase() returns false (not throw) when group not found (findOne returns null → return false)
        $model = new FormGroup();
        $result = $model->updateBase(999, $post);
        $this->assertFalse($result);
    }

    // ==================== Scenario 測試 ====================

    /**
     * 測試：search scenario 支援所有欄位
     */
    public function testSearchScenarioAllowsAllFields()
    {
        $model = new FormGroup();
        $model->scenario = 'search';
        $model->groupId = 1;
        $model->groupName = 'Test';
        $model->isRoutine = 'Y';

        $this->assertTrue($model->validate(), 'Search scenario should allow all fields');
    }
}
