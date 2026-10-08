<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormGroupMember;
use app\models\GroupMember;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\GroupFixture;
use app\tests\fixtures\GroupMemberFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\ConfigFixture;
use app\components\AdminIdentity;

/**
 * FormGroupMember 模型測試
 * 測試群組成員表單模型的各項功能
 */
class FormGroupMemberTest extends \Codeception\Test\Unit
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
                Yii::$app->user->login($identity);
    }

    // ==================== 基本結構測試 ====================

    /**
     * 測試：FormGroupMember 繼承 GroupMember
     */
    public function testFormGroupMemberExtendsGroupMember()
    {
        $model = new FormGroupMember();
        $this->assertInstanceOf(GroupMember::class, $model, 'FormGroupMember should extend GroupMember');
    }

    /**
     * 測試：tableName 正確
     */
    public function testTableName()
    {
        $this->assertEquals('groupMember', FormGroupMember::tableName(), 'Table name should be "groupMember"');
    }

    /**
     * 測試：attributeLabels 包含所有欄位
     */
    public function testAttributeLabels()
    {
        $model = new FormGroupMember();
        $labels = $model->attributeLabels();

        $this->assertArrayHasKey('groupId', $labels);
        $this->assertArrayHasKey('cn', $labels);
        $this->assertArrayHasKey('isWrite', $labels);
        $this->assertArrayHasKey('isOwner', $labels);

        $this->assertEquals('群組 ID', $labels['groupId']);
        $this->assertEquals('群組成員', $labels['cn']);
        $this->assertEquals('編輯投票與否', $labels['isWrite']);
        $this->assertEquals('群組管理與否', $labels['isOwner']);
    }

    // ==================== 驗證規則測試 ====================

    /**
     * 測試：create scenario 必填欄位
     */
    public function testCreateScenarioRequiredFields()
    {
        $model = new FormGroupMember();
        $model->scenario = 'create';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('groupId', $model->errors);
        $this->assertArrayHasKey('cn', $model->errors);
    }

    /**
     * 測試：update scenario 必填欄位
     */
    public function testUpdateScenarioRequiredFields()
    {
        $model = new FormGroupMember();
        $model->scenario = 'update';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('groupId', $model->errors);
        $this->assertArrayHasKey('cn', $model->errors);
    }

    /**
     * 測試：isWrite 必須在允許範圍內
     */
    public function testIsWriteValidation()
    {
        $model = new FormGroupMember();
        $model->scenario = 'create';
        $model->groupId = 1;
        $model->cn = 'test_user';
        $model->isWrite = 'INVALID';
        $model->isOwner = 'Y';

        $this->assertFalse($model->validate(['isWrite']), 'Should fail with invalid isWrite value');
        $this->assertArrayHasKey('isWrite', $model->errors);
    }

    /**
     * 測試：isOwner 必須在允許範圍內
     */
    public function testIsOwnerValidation()
    {
        $model = new FormGroupMember();
        $model->scenario = 'create';
        $model->groupId = 1;
        $model->cn = 'test_user';
        $model->isWrite = 'Y';
        $model->isOwner = 'INVALID';

        $this->assertFalse($model->validate(['isOwner']), 'Should fail with invalid isOwner value');
        $this->assertArrayHasKey('isOwner', $model->errors);
    }

    /**
     * 測試：有效的資料通過驗證
     */
    public function testValidDataPassesValidation()
    {
        $model = new FormGroupMember();
        $model->scenario = 'create';
        $model->groupId = 1;
        $model->cn = 'test_user';
        $model->isWrite = 'Y';
        $model->isOwner = 'N';

        $this->assertTrue($model->validate(), 'Valid data should pass validation');
    }

    // ==================== createMember() 測試 ====================

    /**
     * 測試：成功建立群組成員
     */
    public function testCreateMemberSuccess()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $post = [
            'FormGroupMember' => [
                'groupId' => 1,
                'cn' => 'new_member',
                'isWrite' => 'Y',
                'isOwner' => 'N',
            ]
        ];

        $result = $model->createMember($post);
        $this->assertTrue($result, 'Should create member successfully');

        // 驗證成員已建立
        $member = GroupMember::findOne(['groupId' => 1, 'cn' => 'new_member']);
        $this->assertNotNull($member, 'Member should be created');
        $this->assertEquals('Y', $member->isWrite);
        $this->assertEquals('N', $member->isOwner);
    }

    /**
     * 測試：建立成員失敗（缺少必填欄位）
     */
    public function testCreateMemberFailMissingFields()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $post = [
            'FormGroupMember' => [
                'groupId' => 1,
                // 缺少 cn
                'isWrite' => 'Y',
                'isOwner' => 'N',
            ]
        ];

        $result = $model->createMember($post);
        $this->assertFalse($result, 'Should fail to create member');
    }

    /**
     * 測試：建立成員失敗（成員已存在）
     */
    public function testCreateMemberFailDuplicate()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $post = [
            'FormGroupMember' => [
                'groupId' => 1,
                'cn' => 'gm_test', // 已存在的成員
                'isWrite' => 'Y',
                'isOwner' => 'N',
            ]
        ];

        $result = $model->createMember($post);
        $this->assertFalse($result, 'Should fail with duplicate member');
    }

    /**
     * 測試：建立成員失敗（無效的 isWrite 值）
     */
    public function testCreateMemberFailInvalidIsWrite()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $post = [
            'FormGroupMember' => [
                'groupId' => 1,
                'cn' => 'new_member2',
                'isWrite' => 'INVALID',
                'isOwner' => 'N',
            ]
        ];

        $result = $model->createMember($post);
        $this->assertFalse($result, 'Should fail with invalid isWrite');
    }

    // ==================== updateMember() 測試 ====================

    /**
     * 測試：成功更新群組成員
     */
    public function testUpdateMemberSuccess()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $post = [
            'FormGroupMember' => [
                'groupId' => 1,
                'cn' => 'gm_write_only',
                'isWrite' => 'N',
                'isOwner' => 'Y',
            ]
        ];

        $model = new FormGroupMember();
        $result = $model->updateMember(1, 'gm_write_only', $post);

        $this->assertTrue($result, 'Should update member successfully');

        // 驗證更新
        $member = GroupMember::findOne(['groupId' => 1, 'cn' => 'gm_write_only']);
        $this->assertEquals('N', $member->isWrite);
        $this->assertEquals('Y', $member->isOwner);
    }

    /**
     * 測試：更新成員失敗（cn 設為空）
     */
    public function testUpdateMemberFailMissingFields()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $post = [
            'FormGroupMember' => [
                'groupId' => 1,
                'cn' => '', // 明確設為空
                'isWrite' => 'Y',
                'isOwner' => 'N',
            ]
        ];

        $model = new FormGroupMember();
        $result = $model->updateMember(1, 'gm_test', $post);

        $this->assertFalse($result, 'Should fail to update member with empty cn');
    }

    /**
     * 測試：更新不存在的成員
     */
    public function testUpdateMemberNonExistent()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $post = [
            'FormGroupMember' => [
                'groupId' => 1,
                'cn' => 'nonexistent',
                'isWrite' => 'Y',
                'isOwner' => 'N',
            ]
        ];

        // updateMember() returns false (not throw) when member not found (findOne returns null → return false)
        $model = new FormGroupMember();
        $result = $model->updateMember(1, 'nonexistent', $post);
        $this->assertFalse($result);
    }

    // ==================== deleteMember() 測試 ====================

    /**
     * 測試：成功刪除群組成員
     */
    public function testDeleteMemberSuccess()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $result = $model->deleteMember(1, 'gm_view_only', []);

        $this->assertTrue($result, 'Should delete member successfully');

        // 驗證成員已刪除
        $member = GroupMember::findOne(['groupId' => 1, 'cn' => 'gm_view_only']);
        $this->assertNull($member, 'Member should be deleted');
    }

    /**
     * 測試：刪除不存在的成員
     */
    public function testDeleteMemberNonExistent()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $result = $model->deleteMember(1, 'nonexistent', []);

        // 刪除不存在的成員應返回 false
        $this->assertFalse($result);
    }

    // ==================== getGroupMemberList() 測試 ====================

    /**
     * 測試：getGroupMemberList() 返回群組成員列表
     */
    public function testGetGroupMemberList()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $dataProvider = $model->getGroupMemberList(1);

        $this->assertInstanceOf(\yii\data\ActiveDataProvider::class, $dataProvider);
        $this->assertGreaterThan(0, $dataProvider->getTotalCount(), 'Should return members');
    }

    /**
     * 測試：getGroupMemberList() 空群組
     */
    public function testGetGroupMemberListEmptyGroup()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $dataProvider = $model->getGroupMemberList(3); // 群組3 沒有成員

        $this->assertInstanceOf(\yii\data\ActiveDataProvider::class, $dataProvider);
        $this->assertEquals(0, $dataProvider->getTotalCount(), 'Should return no members');
    }

    // ==================== 權限組合測試 ====================

    /**
     * 測試：建立成員（isWrite=Y, isOwner=Y）
     */
    public function testCreateMemberWithFullPermissions()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $post = [
            'FormGroupMember' => [
                'groupId' => 2,
                'cn' => 'full_perm_user',
                'isWrite' => 'Y',
                'isOwner' => 'Y',
            ]
        ];

        $result = $model->createMember($post);
        $this->assertTrue($result);

        $member = GroupMember::findOne(['groupId' => 2, 'cn' => 'full_perm_user']);
        $this->assertEquals('Y', $member->isWrite);
        $this->assertEquals('Y', $member->isOwner);
    }

    /**
     * 測試：建立成員（isWrite=N, isOwner=N）
     */
    public function testCreateMemberWithNoPermissions()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $post = [
            'FormGroupMember' => [
                'groupId' => 2,
                'cn' => 'no_perm_user',
                'isWrite' => 'N',
                'isOwner' => 'N',
            ]
        ];

        $result = $model->createMember($post);
        $this->assertTrue($result);

        $member = GroupMember::findOne(['groupId' => 2, 'cn' => 'no_perm_user']);
        $this->assertEquals('N', $member->isWrite);
        $this->assertEquals('N', $member->isOwner);
    }

    /**
     * 測試：建立成員（isWrite=Y, isOwner=N）
     */
    public function testCreateMemberWithWriteOnly()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $post = [
            'FormGroupMember' => [
                'groupId' => 2,
                'cn' => 'write_only_user',
                'isWrite' => 'Y',
                'isOwner' => 'N',
            ]
        ];

        $result = $model->createMember($post);
        $this->assertTrue($result);

        $member = GroupMember::findOne(['groupId' => 2, 'cn' => 'write_only_user']);
        $this->assertEquals('Y', $member->isWrite);
        $this->assertEquals('N', $member->isOwner);
    }

    /**
     * 測試：建立成員（isWrite=N, isOwner=Y）
     */
    public function testCreateMemberWithOwnerOnly()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        $model = new FormGroupMember();
        $post = [
            'FormGroupMember' => [
                'groupId' => 2,
                'cn' => 'owner_only_user',
                'isWrite' => 'N',
                'isOwner' => 'Y',
            ]
        ];

        $result = $model->createMember($post);
        $this->assertTrue($result);

        $member = GroupMember::findOne(['groupId' => 2, 'cn' => 'owner_only_user']);
        $this->assertEquals('N', $member->isWrite);
        $this->assertEquals('Y', $member->isOwner);
    }
}
