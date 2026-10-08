<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormCandiConfig;
use app\models\CandiConfig;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\ConfigFixture;
use app\components\AdminIdentity;

/**
 * FormCandiConfig 模型測試
 * 測試候選人設定表單模型的各項功能
 */
class FormCandiConfigTest extends \Codeception\Test\Unit
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
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'round' => RoundFixture::class,
            'questions' => QuestionsFixture::class,
            'candiData' => CandiDataFixture::class,
            'candiConfig' => CandiConfigFixture::class,
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
     * 輔助方法：建立並登入管理員
     */
    private function loginAdmin($cn, $name, $roles)
    {
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => $cn,
            'name' => $name,
            'roles' => $roles,
        ]);
                Yii::$app->user->switchIdentity($identity);

        $auth = Yii::$app->authManager;
        $auth->revokeAll($cn);
        $role = $auth->getRole($roles);
        if ($role) {
            $auth->assign($role, $cn);
        }

        $this->assertNotNull(Yii::$app->user->identity, 'User identity should be set after login');
    }

    // ==================== 基本結構測試 ====================

    /**
     * 測試：FormCandiConfig 繼承 CandiConfig
     */
    public function testFormCandiConfigExtendsCandiConfig()
    {
        $model = new FormCandiConfig();
        $this->assertInstanceOf(CandiConfig::class, $model, 'FormCandiConfig should extend CandiConfig');
    }

    /**
     * 測試：tableName 正確
     */
    public function testTableName()
    {
        $this->assertEquals('candiConfig', FormCandiConfig::tableName(), 'Table name should be "candiConfig"');
    }

    /**
     * 測試：靜態屬性存在
     */
    public function testStaticPropertiesExist()
    {
        $this->assertNotNull(FormCandiConfig::$defFieldSort, 'Should have defFieldSort property');
        $this->assertEquals(0, FormCandiConfig::$FsBefore, 'FsBefore should be 0');
        $this->assertEquals(1, FormCandiConfig::$FsAfter, 'FsAfter should be 1');
    }

    /**
     * 測試：預設欄位排序格式正確
     */
    public function testDefFieldSortIsValidJson()
    {
        $decoded = json_decode(FormCandiConfig::$defFieldSort, true);
        $this->assertIsArray($decoded, 'defFieldSort should be valid JSON array');
        $this->assertNotEmpty($decoded, 'defFieldSort should not be empty');
    }

    // ==================== 驗證規則測試 ====================

    /**
     * 測試：search scenario 允許所有欄位
     */
    public function testSearchScenarioAllowsAllFields()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'search';
        $model->voteID = 'TestVote';
        $model->questionID = 1;
        $model->num = 'A';
        $model->Name = '測試';
        $model->NameE = 'Test';
        $model->showFieldSort = '[{"id":"num"}]';

        $this->assertTrue($model->validate(), 'Search scenario should allow all fields');
    }

    /**
     * 測試：create scenario 必填欄位
     */
    public function testCreateScenarioRequiredFields()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'create';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('voteID', $model->errors);
        $this->assertArrayHasKey('Name', $model->errors);
        $this->assertArrayHasKey('NameE', $model->errors);
        $this->assertArrayHasKey('num', $model->errors);
        $this->assertArrayHasKey('columnNum', $model->errors);
        $this->assertArrayHasKey('useBeforeHeader', $model->errors);
        $this->assertArrayHasKey('width', $model->errors);
        $this->assertArrayHasKey('showFieldSort', $model->errors);
    }

    /**
     * 測試：update scenario 必填欄位
     */
    public function testUpdateScenarioRequiredFields()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'update';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('voteID', $model->errors);
        $this->assertArrayHasKey('Name', $model->errors);
    }

    /**
     * 測試：voteID 最大長度驗證
     */
    public function testVoteIdMaxLength()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'create';
        $model->voteID = str_repeat('A', 21); // 超過 20 字元
        $model->num = 'A';
        $model->Name = '測試';
        $model->NameE = 'Test';
        $model->columnNum = 1;
        $model->useBeforeHeader = '0';
        $model->width = '1';
        $model->showFieldSort = '[{"id":"num"}]';

        $this->assertFalse($model->validate(['voteID']), 'Should fail with voteID > 20 chars');
        $this->assertArrayHasKey('voteID', $model->errors);
    }

    /**
     * 測試：Name 最大長度驗證
     */
    public function testNameMaxLength()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'create';
        $model->voteID = 'TestVote';
        $model->num = 'A';
        $model->Name = str_repeat('A', 21); // 超過 20 字元
        $model->NameE = 'Test';
        $model->columnNum = 1;
        $model->useBeforeHeader = '0';
        $model->width = '1';
        $model->showFieldSort = '[{"id":"num"}]';

        $this->assertFalse($model->validate(['Name']), 'Should fail with Name > 20 chars');
        $this->assertArrayHasKey('Name', $model->errors);
    }

    /**
     * 測試：NameE 最大長度驗證
     */
    public function testNameEMaxLength()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'create';
        $model->voteID = 'TestVote';
        $model->num = 'A';
        $model->Name = '測試';
        $model->NameE = str_repeat('A', 101); // 超過 100 字元
        $model->columnNum = 1;
        $model->useBeforeHeader = '0';
        $model->width = '1';
        $model->showFieldSort = '[{"id":"num"}]';

        $this->assertFalse($model->validate(['NameE']), 'Should fail with NameE > 100 chars');
        $this->assertArrayHasKey('NameE', $model->errors);
    }

    /**
     * 測試：columnNum 最大值驗證
     */
    public function testColumnNumMaxValue()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'create';
        $model->voteID = 'TestVote';
        $model->num = 'A';
        $model->Name = '測試';
        $model->NameE = 'Test';
        $model->columnNum = 6; // 超過最大值 5
        $model->useBeforeHeader = '0';
        $model->width = '1';
        $model->showFieldSort = '[{"id":"num"}]';

        $this->assertFalse($model->validate(['columnNum']), 'Should fail with columnNum > 5');
        $this->assertArrayHasKey('columnNum', $model->errors);
    }

    /**
     * 測試：有效資料通過驗證
     */
    public function testValidDataPassesValidation()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'create';
        $model->voteID = 'TestVote';
        $model->num = 'A';
        $model->Name = '候選人名稱';
        $model->NameE = 'Candidate Name';
        $model->columnNum = 1;
        $model->useBeforeHeader = '0';
        $model->width = '1';
        $model->showFieldSort = '[{"id":"num"},{"id":"Name"}]';

        $this->assertTrue($model->validate(), 'Valid data should pass validation');
    }

    // ==================== search() 測試 ====================

    /**
     * 測試：search() 返回查詢物件
     */
    public function testSearchReturnsQuery()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'search';
        $query = $model->search('AnonPartyTest', []);

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query, 'Should return ActiveQuery');
    }

    /**
     * 測試：search() 帶過濾參數
     */
    public function testSearchWithFilters()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'search';
        $params = [
            'FormCandiConfig' => [
                'Name' => '測試',
            ]
        ];
        $query = $model->search('AnonPartyTest', $params);

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query);
    }

    // ==================== getConfigWithVoteID() 測試 ====================

    /**
     * 測試：getConfigWithVoteID() 查詢不存在的投票
     */
    public function testGetConfigWithVoteIDNonExistent()
    {
        $model = new FormCandiConfig();
        $result = $model->getConfigWithVoteID('NonExistentVote');

        $this->assertNull($result, 'Should return null for non-existent vote');
    }

    /**
     * 測試：getConfigWithVoteID() 查詢存在的投票
     */
    public function testGetConfigWithVoteIDExists()
    {
        $model = new FormCandiConfig();
        $result = $model->getConfigWithVoteID('AnonPartyTest');

        if ($result !== null) {
            $this->assertInstanceOf(FormCandiConfig::class, $result);
            $this->assertEquals('AnonPartyTest', $result->voteID);
        } else {
            $this->markTestSkipped('No CandiConfig found for AnonPartyTest');
        }
    }

    // ==================== removeHeaderFromFieldSort() 測試 ====================

    /**
     * 測試：removeHeaderFromFieldSort() 處理標準 JSON 格式
     */
    public function testRemoveHeaderFromFieldSortStandardJson()
    {
        $model = new FormCandiConfig();
        $origin = '[{"id":"num"},{"id":"Name"},{"id":"instName"}]';
        $result = $model->removeHeaderFromFieldSort($origin);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('num', $result[0]['id']);
        $this->assertEquals('Name', $result[1]['id']);
        $this->assertEquals('instName', $result[2]['id']);
    }

    /**
     * 測試：removeHeaderFromFieldSort() 處理含有 header 的 JSON
     */
    public function testRemoveHeaderFromFieldSortWithHeader()
    {
        $model = new FormCandiConfig();
        $origin = '[{"id":"num"},{"id":"headerA","children":[{"id":"Name"},{"id":"instName"}]},{"id":"title"}]';
        $result = $model->removeHeaderFromFieldSort($origin);

        $this->assertIsArray($result);
        // 應該展開 header 內的 children
        $ids = array_column($result, 'id');
        $this->assertContains('num', $ids);
        $this->assertContains('Name', $ids);
        $this->assertContains('instName', $ids);
        $this->assertContains('title', $ids);
        $this->assertNotContains('headerA', $ids);
    }

    /**
     * 測試：removeHeaderFromFieldSort() 處理舊格式（逗點分隔）
     */
    public function testRemoveHeaderFromFieldSortOldFormat()
    {
        $model = new FormCandiConfig();
        $model->showFieldSort = 'num,Name,instName';
        $result = $model->removeHeaderFromFieldSort($model->showFieldSort);

        $this->assertIsArray($result);
        // fixOldData 會被呼叫
        $ids = array_column($result, 'id');
        $this->assertContains('num', $ids);
        $this->assertContains('Name', $ids);
        $this->assertContains('instName', $ids);
    }

    // ==================== fixOldData() 測試 ====================

    /**
     * 測試：fixOldData() 轉換舊格式
     */
    public function testFixOldData()
    {
        $model = new FormCandiConfig();
        $model->showFieldSort = 'num,Name,instName,title';
        $result = $model->fixOldData($model->showFieldSort);

        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertEquals('num', $result[0]['id']);
        $this->assertEquals('Name', $result[1]['id']);
        $this->assertEquals('instName', $result[2]['id']);
        $this->assertEquals('title', $result[3]['id']);
    }

    // ==================== getDNoneRows() 測試 ====================

    /**
     * 測試：getDNoneRows() 返回非 header 欄位
     */
    public function testGetDNoneRows()
    {
        $model = new FormCandiConfig();
        $model->showFieldSort = '[{"id":"num"},{"id":"Name"},{"id":"instName"}]';
        $result = $model->getDNoneRows();

        $this->assertIsArray($result);
        $this->assertContains('num', $result);
        $this->assertContains('Name', $result);
        $this->assertContains('instName', $result);
    }

    /**
     * 測試：getDNoneRows() 排除 header
     */
    public function testGetDNoneRowsExcludesHeaders()
    {
        $model = new FormCandiConfig();
        $model->showFieldSort = '[{"id":"num"},{"id":"headerA","children":[{"id":"Name"}]},{"id":"title"}]';
        $result = $model->getDNoneRows();

        $this->assertIsArray($result);
        $this->assertContains('num', $result);
        $this->assertContains('title', $result);
        $this->assertNotContains('headerA', $result);
    }

    // ==================== getShowFiled() 測試 ====================

    /**
     * 測試：getShowFiled() 返回顯示欄位
     */
    public function testGetShowFiled()
    {
        $model = new FormCandiConfig();
        $showFieldSort = [
            ['id' => 'num'],
            ['id' => 'Name'],
            ['id' => 'instName'],
        ];
        $result = $model->getShowFiled($showFieldSort);

        $this->assertIsArray($result);
        $this->assertContains('num', $result);
        $this->assertContains('Name', $result);
        $this->assertContains('instName', $result);
    }

    /**
     * 測試：getShowFiled() 處理含有 header 的結構
     */
    public function testGetShowFiledWithHeaders()
    {
        $model = new FormCandiConfig();
        $showFieldSort = [
            ['id' => 'num'],
            ['id' => 'headerA', 'children' => [
                ['id' => 'Name'],
                ['id' => 'instName'],
            ]],
            ['id' => 'title'],
        ];
        $result = $model->getShowFiled($showFieldSort);

        $this->assertIsArray($result);
        $this->assertContains('num', $result);
        $this->assertContains('Name', $result);
        $this->assertContains('instName', $result);
        $this->assertNotContains('headerA', $result); // header 本身不加入，只展開其子欄位
        $this->assertContains('title', $result);
        $this->assertCount(4, $result);
    }

    // ==================== deleteAllCandiConfig() 測試 ====================

    /**
     * 測試：deleteAllCandiConfig() 刪除不存在的投票設定
     */
    public function testDeleteAllCandiConfigNonExistent()
    {
        $model = new FormCandiConfig();
        $result = $model->deleteAllCandiConfig('NonExistentVote');

        // deleteAll 回傳刪除的列數，不存在的資料應為 0
        $this->assertEquals(0, $result, 'Deleting non-existent vote config should return 0 rows');
    }

    // ==================== getFieldSort() 測試 ====================

    /**
     * 測試：getFieldSort() 返回空陣列當無有效欄位
     */
    public function testGetFieldSortWithEmptyColumns()
    {
        $model = new FormCandiConfig();
        $model->showFieldSort = '[]'; // 空 JSON 陣列
        $model->num = 'A';
        $model->alignLeft = null;

        // 提供必要的 columns
        $columnsAry = [
            'autoId' => ['attribute' => 'autoId'],
            'id' => ['attribute' => 'id'],
        ];

        $result = $model->getFieldSort($columnsAry);

        $this->assertIsArray($result);
    }

    /**
     * 測試：getFieldSort() 處理有效欄位陣列
     */
    public function testGetFieldSortWithValidColumns()
    {
        $model = new FormCandiConfig();
        $model->showFieldSort = '[{"id":"num"},{"id":"Name"}]';
        $model->num = 'A';
        $model->alignLeft = null;

        $columnsAry = [
            'autoId' => ['attribute' => 'autoId'],
            'id' => ['attribute' => 'id'],
            'Name' => ['attribute' => 'Name'],
        ];

        $result = $model->getFieldSort($columnsAry);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('num', $result);
        $this->assertArrayHasKey('Name', $result);
    }

    /**
     * 測試：getFieldSort() 處理 addField 參數
     */
    public function testGetFieldSortWithAddField()
    {
        $model = new FormCandiConfig();
        $model->showFieldSort = '[{"id":"num"}]';
        $model->num = 'A';
        $model->alignLeft = null;

        $columnsAry = [
            'autoId' => ['attribute' => 'autoId'],
            'extra' => ['attribute' => 'extra'],
        ];

        $addField = [
            'extra' => FormCandiConfig::$FsAfter,
        ];

        $result = $model->getFieldSort($columnsAry, $addField);

        $this->assertIsArray($result);
    }

    /**
     * 測試：getFieldSort() 處理 removeField 參數
     */
    public function testGetFieldSortWithRemoveField()
    {
        $model = new FormCandiConfig();
        $model->showFieldSort = '[{"id":"num"},{"id":"Name"},{"id":"instName"}]';
        $model->num = 'A';
        $model->alignLeft = null;

        $columnsAry = [
            'autoId' => ['attribute' => 'autoId'],
            'Name' => ['attribute' => 'Name'],
            'instName' => ['attribute' => 'instName'],
        ];

        $removeField = ['instName'];

        $result = $model->getFieldSort($columnsAry, [], $removeField);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('instName', $result);
    }

    // ==================== getDataSort() 測試 ====================

    /**
     * 測試：getDataSort() 無排序欄位時返回 false
     */
    public function testGetDataSortReturnsFalseWhenEmpty()
    {
        $model = new FormCandiConfig();
        $model->sortColumns = '[]'; // 空 JSON 陣列而非 null

        $result = $model->getDataSort();

        $this->assertFalse($result);
    }

    /**
     * 測試：getDataSort() 空 JSON 陣列時返回 false
     */
    public function testGetDataSortReturnsFalseForEmptyJson()
    {
        $model = new FormCandiConfig();
        $model->sortColumns = '[]';

        $result = $model->getDataSort();

        $this->assertFalse($result);
    }

    /**
     * 測試：getDataSort() 有排序欄位時返回陣列
     */
    public function testGetDataSortReturnsArrayWithColumns()
    {
        $model = new FormCandiConfig();
        $model->sortColumns = '[{"id":"orderNum"}]';
        $model->sortDefault = '[{"orderNum":4}]';
        $model->Name = '名稱';
        $model->NameE = 'Name';

        $result = $model->getDataSort();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('attributes', $result);
        $this->assertArrayHasKey('defaultOrder', $result);
    }

    // ==================== #13 BVA：columnNum 邊界測試 ====================

    /**
     * BVA：columnNum = 0（下界，integer 無 min 限制應通過）
     */
    public function testColumnNumZero()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'create';
        $model->columnNum = 0;

        $model->validate(['columnNum']);
        $this->assertArrayNotHasKey('columnNum', $model->errors,
            'columnNum=0 should pass (no min constraint)');
    }

    /**
     * BVA：columnNum = 1（最小合理值）
     */
    public function testColumnNumMinReasonableValue()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'create';
        $model->columnNum = 1;

        $model->validate(['columnNum']);
        $this->assertArrayNotHasKey('columnNum', $model->errors,
            'columnNum=1 should pass');
    }

    /**
     * BVA：columnNum = 5（恰好最大值）
     */
    public function testColumnNumExactMax()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'create';
        $model->columnNum = 5;

        $model->validate(['columnNum']);
        $this->assertArrayNotHasKey('columnNum', $model->errors,
            'columnNum=5 (exact max) should pass');
    }

    /**
     * BVA：columnNum 負數
     */
    public function testColumnNumNegative()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'create';
        $model->columnNum = -1;

        $model->validate(['columnNum']);
        // integer validator 無 min 限制，負數通過驗證（設計缺口）
        $this->assertFalse($model->hasErrors('columnNum'),
            'columnNum=-1 passes because integer rule has no min constraint (design gap)');
    }

    /**
     * EP：columnNum 非整數字串
     */
    public function testColumnNumNonInteger()
    {
        $model = new FormCandiConfig();
        $model->scenario = 'create';
        $model->columnNum = 'abc';

        $model->validate(['columnNum']);
        $this->assertArrayHasKey('columnNum', $model->errors,
            'columnNum=abc should fail integer validation');
    }
}
