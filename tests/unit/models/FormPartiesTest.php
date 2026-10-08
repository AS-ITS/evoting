<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormParties;
use app\models\Parties;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\ConfigFixture;
use app\components\AdminIdentity;

/**
 * FormParties 模型測試
 * 測試分組表單模型的各項功能
 */
class FormPartiesTest extends \Codeception\Test\Unit
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
     * 測試：FormParties 繼承 Parties
     */
    public function testFormPartiesExtendsParties()
    {
        $model = new FormParties();
        $this->assertInstanceOf(Parties::class, $model, 'FormParties should extend Parties');
    }

    /**
     * 測試：tableName 正確
     */
    public function testTableName()
    {
        $this->assertEquals('parties', FormParties::tableName(), 'Table name should be "parties"');
    }

    /**
     * 測試：DEF_PARTY 常數存在
     */
    public function testDefPartyConstantExists()
    {
        $this->assertTrue(defined('app\models\Parties::DEF_PARTY'), 'DEF_PARTY constant should exist');
    }

    // ==================== 驗證規則測試 ====================

    /**
     * 測試：update scenario 必填欄位
     */
    public function testUpdateScenarioRequiredFields()
    {
        $model = new FormParties();
        $model->scenario = 'update';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('voteID', $model->errors);
        $this->assertArrayHasKey('party', $model->errors);
        $this->assertArrayHasKey('numBallots', $model->errors);
        $this->assertArrayHasKey('leastNumBallots', $model->errors);
        $this->assertArrayHasKey('numOfKeep', $model->errors);
        $this->assertArrayHasKey('maxElect', $model->errors);
    }

    /**
     * 測試：voteID 最大長度驗證
     */
    public function testVoteIdMaxLength()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = str_repeat('A', 21); // 超過 20 字元
        $model->party = 'def';
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertFalse($model->validate(['voteID']), 'Should fail with voteID > 20 chars');
        $this->assertArrayHasKey('voteID', $model->errors);
    }

    /**
     * 測試：party 最大長度驗證
     */
    public function testPartyMaxLength()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = str_repeat('A', 13); // 超過 12 字元
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertFalse($model->validate(['party']), 'Should fail with party > 12 chars');
        $this->assertArrayHasKey('party', $model->errors);
    }

    /**
     * 測試：nameE 最大長度驗證
     */
    public function testNameEMaxLength()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'test';
        $model->name = '測試';
        $model->nameE = str_repeat('A', 101); // 超過 100 字元
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertFalse($model->validate(['nameE']), 'Should fail with nameE > 100 chars');
        $this->assertArrayHasKey('nameE', $model->errors);
    }

    /**
     * 測試：numBallots 最小值驗證
     */
    public function testNumBallotsMinValue()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 0; // 小於最小值 1
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertFalse($model->validate(['numBallots']), 'Should fail with numBallots < 1');
        $this->assertArrayHasKey('numBallots', $model->errors);
    }

    /**
     * 測試：maxElect 最小值驗證
     */
    public function testMaxElectMinValue()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 0; // 小於最小值 1

        $this->assertFalse($model->validate(['maxElect']), 'Should fail with maxElect < 1');
        $this->assertArrayHasKey('maxElect', $model->errors);
    }

    /**
     * 測試：有效資料通過驗證 (預設分組)
     */
    public function testValidDataPassesValidationForDefaultParty()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = Parties::DEF_PARTY; // 預設分組
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertTrue($model->validate(), 'Valid data should pass validation for default party');
    }

    /**
     * 測試：非預設分組需要 name 和 nameE
     */
    public function testNonDefaultPartyRequiresName()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = '1'; // 非預設分組
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;
        // 缺少 name 和 nameE

        $this->assertFalse($model->validate(), 'Should fail validation for non-default party without name');
    }

    /**
     * 測試：非預設分組含 name 和 nameE 通過驗證
     */
    public function testNonDefaultPartyWithNamePassesValidation()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = '1';
        $model->name = '數理科學組';
        $model->nameE = 'Division of Mathematics';
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertTrue($model->validate(), 'Valid data with name should pass validation');
    }

    // ==================== getPartyInfo() 測試 ====================

    /**
     * 測試：getPartyInfo() 查詢存在的分組
     */
    public function testGetPartyInfoExists()
    {
        $model = new FormParties();
        $result = $model->getPartyInfo('AnonPartyTest', '0');

        if ($result !== null) {
            $this->assertInstanceOf(Parties::class, $result);
            $this->assertEquals('AnonPartyTest', $result->voteID);
            $this->assertEquals('0', $result->party);
        } else {
            $this->markTestSkipped('No party found for AnonPartyTest');
        }
    }

    /**
     * 測試：getPartyInfo() 查詢不存在的分組
     */
    public function testGetPartyInfoNonExistent()
    {
        $model = new FormParties();
        $result = $model->getPartyInfo('NonExistentVote', '999');

        $this->assertNull($result, 'Should return null for non-existent party');
    }

    // ==================== getPartyAll() 測試 ====================

    /**
     * 測試：getPartyAll() 返回陣列
     */
    public function testGetPartyAllReturnsArray()
    {
        $model = new FormParties();
        $result = $model->getPartyAll('AnonPartyTest');

        $this->assertIsArray($result, 'Should return array');
    }

    /**
     * 測試：getPartyAll() 查詢存在的投票
     */
    public function testGetPartyAllExists()
    {
        $model = new FormParties();
        $result = $model->getPartyAll('AnonPartyTest');

        $this->assertNotEmpty($result, 'Should return parties for existing vote');
    }

    /**
     * 測試：getPartyAll() 查詢不存在的投票
     */
    public function testGetPartyAllNonExistent()
    {
        $model = new FormParties();
        $result = $model->getPartyAll('NonExistentVote');

        $this->assertIsArray($result, 'Should return empty array for non-existent vote');
        $this->assertEmpty($result, 'Array should be empty');
    }

    // ==================== getPartyNumBallots() 測試 ====================

    /**
     * 測試：getPartyNumBallots() 查詢存在的分組
     */
    public function testGetPartyNumBallotsExists()
    {
        $model = new FormParties();
        $result = $model->getPartyNumBallots('AnonPartyTest', '0');

        if ($result !== null) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('mostNum', $result);
            $this->assertArrayHasKey('leastNum', $result);
        } else {
            $this->markTestSkipped('No party found for AnonPartyTest');
        }
    }

    /**
     * 測試：getPartyNumBallots() 查詢不存在的分組
     * 修正：PHP 8.x 相容，不存在時回傳 null 而非拋出 Warning
     */
    public function testGetPartyNumBallotsNonExistent()
    {
        $model = new FormParties();

        $result = $model->getPartyNumBallots('NonExistentVote', '999');
        $this->assertNull($result);
    }

    // ==================== deleteParty() 測試 ====================

    /**
     * 測試：deleteParty() 刪除不存在的投票分組
     */
    public function testDeletePartyNonExistent()
    {
        $model = new FormParties();
        $result = $model->deleteParty('NonExistentVote');

        $this->assertFalse($result, 'Should return false for non-existent vote');
    }

    // ==================== updateParty() 測試 ====================

    /**
     * 測試：updateParty() 更新存在的分組
     */
    public function testUpdatePartyExists()
    {
        $this->loginAdmin('va_test', 'VA Test', 'va');

        $model = new FormParties();
        $post = [
            'FormParties' => [
                '0' => [
                    'name' => '數理科學組更新',
                    'nameE' => 'Updated Division',
                    'numBallots' => 2,
                    'leastNumBallots' => 1,
                    'numOfKeep' => 1,
                    'maxElect' => 2,
                ]
            ]
        ];

        $result = $model->updateParty('AnonPartyTest', $post);

        $this->assertTrue($result, 'Should return true on successful update');

        // 驗證更新
        $updated = Parties::findOne(['voteID' => 'AnonPartyTest', 'party' => '0']);
        $this->assertEquals('數理科學組更新', $updated->name);
    }

    /**
     * 測試：updateParty() 驗證失敗
     */
    public function testUpdatePartyValidationFail()
    {
        $this->loginAdmin('va_test', 'VA Test', 'va');

        $model = new FormParties();
        $post = [
            'FormParties' => [
                '1' => [
                    'name' => '', // 非預設分組需要名稱
                    'nameE' => '',
                    'numBallots' => 0, // 無效值
                    'leastNumBallots' => 1,
                    'numOfKeep' => 1,
                    'maxElect' => 1,
                ]
            ]
        ];

        $result = $model->updateParty('AnonPartyTest', $post);

        $this->assertFalse($result, 'Should return false on validation failure');
    }

    /**
     * 測試：updateParty() 另存模式
     */
    public function testUpdatePartySaveAs()
    {
        $this->loginAdmin('va_test', 'VA Test', 'va');

        $model = new FormParties();
        $post = [
            'FormParties' => [
                'def' => [
                    'name' => '預設',
                    'nameE' => 'Default',
                    'numBallots' => 1,
                    'leastNumBallots' => 1,
                    'numOfKeep' => 1,
                    'maxElect' => 1,
                ]
            ]
        ];

        $result = $model->updateParty('SaveAsTest', $post, true);

        $this->assertTrue($result, 'Should return true on successful save as');

        // 清理測試資料
        Parties::deleteAll(['voteID' => 'SaveAsTest']);
    }

    // ==================== 整數欄位驗證測試 ====================

    /**
     * 測試：numBallots 必須為整數
     */
    public function testNumBallotsMustBeInteger()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 'not_integer';
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertFalse($model->validate(['numBallots']), 'Should fail with non-integer numBallots');
        $this->assertArrayHasKey('numBallots', $model->errors);
    }

    /**
     * 測試：leastNumBallots 必須為整數
     */
    public function testLeastNumBallotsMustBeInteger()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 1;
        $model->leastNumBallots = 'not_integer';
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertFalse($model->validate(['leastNumBallots']), 'Should fail with non-integer leastNumBallots');
        $this->assertArrayHasKey('leastNumBallots', $model->errors);
    }

    /**
     * 測試：maxElect 必須為整數
     */
    public function testMaxElectMustBeInteger()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 'not_integer';

        $this->assertFalse($model->validate(['maxElect']), 'Should fail with non-integer maxElect');
        $this->assertArrayHasKey('maxElect', $model->errors);
    }

    /**
     * 測試：numOfKeep 必須為整數
     */
    public function testNumOfKeepMustBeInteger()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 'not_integer';
        $model->maxElect = 1;

        $this->assertFalse($model->validate(['numOfKeep']), 'Should fail with non-integer numOfKeep');
        $this->assertArrayHasKey('numOfKeep', $model->errors);
    }

    /**
     * 測試：numFemaleKeep 必須為整數
     */
    public function testNumFemaleKeepMustBeInteger()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;
        $model->numFemaleKeep = 'not_integer';

        $this->assertFalse($model->validate(['numFemaleKeep']), 'Should fail with non-integer numFemaleKeep');
        $this->assertArrayHasKey('numFemaleKeep', $model->errors);
    }

    // ==================== BVA：字串長度 max 邊界值 ====================

    /**
     * BVA：voteID 恰好 20 字元應通過驗證
     */
    public function testVoteIdExactMaxLength()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = str_repeat('A', 20);
        $model->party = 'def';
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertTrue($model->validate(['voteID']), 'voteID at max length (20) should pass');
    }

    /**
     * BVA：party 恰好 12 字元應通過驗證
     */
    public function testPartyExactMaxLength()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = str_repeat('A', 12);
        $model->name = '測試';
        $model->nameE = 'Test';
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertTrue($model->validate(['party']), 'party at max length (12) should pass');
    }

    /**
     * BVA：nameE 恰好 100 字元應通過驗證
     */
    public function testNameEExactMaxLength()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = '1';
        $model->name = '測試';
        $model->nameE = str_repeat('A', 100);
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertTrue($model->validate(['nameE']), 'nameE at max length (100) should pass');
    }

    // ==================== BVA：整數欄位負數邊界 ====================

    /**
     * BVA：numBallots 為負數應驗證失敗
     */
    public function testNumBallotsNegativeValue()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = -1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertFalse($model->validate(['numBallots']), 'numBallots=-1 should fail validation');
        $this->assertArrayHasKey('numBallots', $model->errors);
    }

    /**
     * BVA：maxElect 為負數應驗證失敗
     */
    public function testMaxElectNegativeValue()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = -1;

        $this->assertFalse($model->validate(['maxElect']), 'maxElect=-1 should fail validation');
        $this->assertArrayHasKey('maxElect', $model->errors);
    }

    /**
     * BVA：numBallots 為浮點數應驗證失敗
     */
    public function testNumBallotsFloatValue()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 1.5;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 1;

        $this->assertFalse($model->validate(['numBallots']), 'numBallots=1.5 should fail validation');
        $this->assertArrayHasKey('numBallots', $model->errors);
    }

    // ==================== EP：numBallots vs maxElect 業務邏輯 ====================

    /**
     * EP：numBallots 等於 maxElect 應通過驗證
     */
    public function testNumBallotsEqualsMaxElect()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 3;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 3;

        $this->assertTrue($model->validate(), 'numBallots == maxElect should pass');
    }

    /**
     * EP：leastNumBallots 等於 numBallots 應通過驗證
     */
    public function testLeastNumBallotsEqualsNumBallots()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 5;
        $model->leastNumBallots = 5;
        $model->numOfKeep = 1;
        $model->maxElect = 3;

        $this->assertTrue($model->validate(), 'leastNumBallots == numBallots should pass');
    }

    /**
     * EP：大量選票數（壓力邊界）
     */
    public function testLargeNumBallots()
    {
        $model = new FormParties();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->numBallots = 1000;
        $model->leastNumBallots = 1;
        $model->numOfKeep = 1;
        $model->maxElect = 500;

        $this->assertTrue($model->validate(), 'Large numBallots (1000) should pass validation');
    }
}
