<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormResults;
use app\models\FormManageCount;
use app\models\Results;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\BallotsSelectedFixture;
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
 * FormResults 模型測試
 * 測試投票結果表單模型的各項功能
 */
class FormResultsTest extends \Codeception\Test\Unit
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
            'ballots' => BallotsFixture::class,
            'ballotsSelected' => BallotsSelectedFixture::class,
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
     * 測試：FormResults 繼承 Results
     */
    public function testFormResultsExtendsResults()
    {
        $model = new FormResults();
        $this->assertInstanceOf(Results::class, $model, 'FormResults should extend Results');
    }

    /**
     * 測試：tableName 正確
     */
    public function testTableName()
    {
        $this->assertEquals('results', FormResults::tableName(), 'Table name should be "results"');
    }

    // ==================== 驗證規則測試 ====================

    /**
     * 測試：create scenario 必填欄位
     */
    public function testCreateScenarioRequiredFields()
    {
        $model = new FormResults();
        $model->scenario = 'create';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('voteID', $model->errors);
        $this->assertArrayHasKey('candID', $model->errors);
        $this->assertArrayHasKey('party', $model->errors);
        $this->assertArrayHasKey('jobLctn', $model->errors);
        $this->assertArrayHasKey('ballotCounts', $model->errors);
        $this->assertArrayHasKey('elected', $model->errors);
        $this->assertArrayHasKey('rank', $model->errors);
    }

    /**
     * 測試：update scenario 必填欄位
     */
    public function testUpdateScenarioRequiredFields()
    {
        $model = new FormResults();
        $model->scenario = 'update';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('elected', $model->errors);
        $this->assertArrayHasKey('rank', $model->errors);
    }

    /**
     * 測試：search scenario 允許所有欄位
     */
    public function testSearchScenarioAllowsAllFields()
    {
        $model = new FormResults();
        $model->scenario = 'search';
        $model->party = 'def';
        $model->questionID = 1;
        $model->elected = 'Y';

        $this->assertTrue($model->validate(), 'Search scenario should allow all fields');
    }

    /**
     * 測試：candID 必須為整數
     */
    public function testCandIdMustBeInteger()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->candID = 'not_integer';

        $this->assertFalse($model->validate(['candID']), 'Should fail with non-integer candID');
        $this->assertArrayHasKey('candID', $model->errors);
    }

    /**
     * 測試：ballotCounts 必須為整數
     */
    public function testBallotCountsMustBeInteger()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->ballotCounts = 'not_integer';

        $this->assertFalse($model->validate(['ballotCounts']), 'Should fail with non-integer ballotCounts');
        $this->assertArrayHasKey('ballotCounts', $model->errors);
    }

    /**
     * 測試：rank 必須為整數
     */
    public function testRankMustBeInteger()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->rank = 'not_integer';

        $this->assertFalse($model->validate(['rank']), 'Should fail with non-integer rank');
        $this->assertArrayHasKey('rank', $model->errors);
    }

    /**
     * 測試：voteID 最大長度驗證
     */
    public function testVoteIdMaxLength()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->voteID = str_repeat('A', 21); // 超過 20 字元

        $this->assertFalse($model->validate(['voteID']), 'Should fail with voteID > 20 chars');
        $this->assertArrayHasKey('voteID', $model->errors);
    }

    /**
     * 測試：party 最大長度驗證
     */
    public function testPartyMaxLength()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->party = str_repeat('A', 13); // 超過 12 字元

        $this->assertFalse($model->validate(['party']), 'Should fail with party > 12 chars');
        $this->assertArrayHasKey('party', $model->errors);
    }

    /**
     * 測試：elected 最大長度驗證
     */
    public function testElectedMaxLength()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->elected = 'ABC'; // 超過 2 字元

        $this->assertFalse($model->validate(['elected']), 'Should fail with elected > 2 chars');
        $this->assertArrayHasKey('elected', $model->errors);
    }

    /**
     * 測試：jobLctn 最大長度驗證
     */
    public function testJobLctnMaxLength()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->jobLctn = 'AB'; // 超過 1 字元

        $this->assertFalse($model->validate(['jobLctn']), 'Should fail with jobLctn > 1 char');
        $this->assertArrayHasKey('jobLctn', $model->errors);
    }

    /**
     * 測試：comment 最大長度驗證
     */
    public function testCommentMaxLength()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->comment = str_repeat('A', 501); // 超過 500 字元

        $this->assertFalse($model->validate(['comment']), 'Should fail with comment > 500 chars');
        $this->assertArrayHasKey('comment', $model->errors);
    }

    /**
     * 測試：有效資料通過驗證
     */
    public function testValidDataPassesValidation()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->voteID = 'TestVote';
        $model->candID = 1;
        $model->party = 'def';
        $model->jobLctn = '0';
        $model->ballotCounts = 10;
        $model->elected = 'Y';
        $model->rank = 1;

        $this->assertTrue($model->validate(), 'Valid data should pass validation');
    }

    // ==================== isResults() 測試 ====================

    /**
     * 測試：isResults() 檢查不存在的結果
     */
    public function testIsResultsNonExistent()
    {
        $model = new FormResults();
        $result = $model->isResults('NonExistentVote', 1);

        $this->assertFalse($result, 'Should return false for non-existent results');
    }

    /**
     * 測試：isResults() 檢查存在的投票
     */
    public function testIsResultsExistingVote()
    {
        $model = new FormResults();
        $result = $model->isResults('AnonPartyTest', 1);

        // 結果取決於 fixture 中是否有結果資料
        $this->assertIsBool($result);
    }

    // ==================== deleteResults() 測試 ====================

    /**
     * 測試：deleteResults() 刪除不存在的結果
     */
    public function testDeleteResultsNonExistent()
    {
        $model = new FormResults();
        $result = $model->deleteResults('NonExistentVote', 1);

        $this->assertFalse($result, 'Should return false for non-existent results');
    }

    // ==================== deleteResultsAll() 測試 ====================

    /**
     * 測試：deleteResultsAll() 刪除不存在的結果
     */
    public function testDeleteResultsAllNonExistent()
    {
        $model = new FormResults();
        $result = $model->deleteResultsAll('NonExistentVote', 1);

        // 結果應該是 0 或 false
        $this->assertTrue($result === 0 || $result === false);
    }

    /**
     * 測試：deleteResultsAll() 帶分組參數
     */
    public function testDeleteResultsAllWithParty()
    {
        $model = new FormResults();
        $result = $model->deleteResultsAll('NonExistentVote', 1, 'def');

        // 結果應該是 0 或 false
        $this->assertTrue($result === 0 || $result === false);
    }

    // ==================== Scenario 測試 ====================

    /**
     * 測試：create scenario 可以設定所有必要欄位
     */
    public function testCreateScenarioSetsAllFields()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->voteID = 'TestVote';
        $model->candID = 1;
        $model->party = 'def';
        $model->questionID = 1;
        $model->jobLctn = '0';
        $model->ballotCounts = 10;
        $model->elected = 'Y';
        $model->rank = 1;
        $model->comment = '測試註解';

        $this->assertTrue($model->validate(), 'Should validate with all fields');
        $this->assertEquals('TestVote', $model->voteID);
        $this->assertEquals(1, $model->candID);
        $this->assertEquals('def', $model->party);
        $this->assertEquals('0', $model->jobLctn);
        $this->assertEquals(10, $model->ballotCounts);
        $this->assertEquals('Y', $model->elected);
        $this->assertEquals(1, $model->rank);
        $this->assertEquals('測試註解', $model->comment);
    }

    /**
     * 測試：update scenario 只需要部分欄位
     */
    public function testUpdateScenarioPartialFields()
    {
        $model = new FormResults();
        $model->scenario = 'update';
        $model->elected = 'N';
        $model->rank = 2;

        $this->assertTrue($model->validate(), 'Update scenario should require only elected and rank');
    }

    // ==================== 有效值測試 ====================

    /**
     * 測試：elected 有效值
     */
    public function testElectedValidValues()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->voteID = 'TestVote';
        $model->candID = 1;
        $model->party = 'def';
        $model->jobLctn = '0';
        $model->ballotCounts = 10;
        $model->rank = 1;

        // 測試有效值 Y
        $model->elected = 'Y';
        $this->assertTrue($model->validate(['elected']), 'elected=Y should be valid');

        // 測試有效值 N
        $model->elected = 'N';
        $this->assertTrue($model->validate(['elected']), 'elected=N should be valid');

        // 測試有效值 D (備取)
        $model->elected = 'D';
        $this->assertTrue($model->validate(['elected']), 'elected=D should be valid');
    }

    /**
     * 測試：jobLctn 有效值
     */
    public function testJobLctnValidValues()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->voteID = 'TestVote';
        $model->candID = 1;
        $model->party = 'def';
        $model->ballotCounts = 10;
        $model->elected = 'Y';
        $model->rank = 1;

        // 測試有效值 0
        $model->jobLctn = '0';
        $this->assertTrue($model->validate(['jobLctn']), 'jobLctn=0 should be valid');

        // 測試有效值 1
        $model->jobLctn = '1';
        $this->assertTrue($model->validate(['jobLctn']), 'jobLctn=1 should be valid');
    }

    /**
     * 測試：rank 正整數值
     */
    public function testRankPositiveValues()
    {
        $model = new FormResults();
        $model->scenario = 'update';
        $model->elected = 'Y';

        // 測試正整數
        $model->rank = 1;
        $this->assertTrue($model->validate(['rank']), 'rank=1 should be valid');

        $model->rank = 100;
        $this->assertTrue($model->validate(['rank']), 'rank=100 should be valid');
    }

    /**
     * 測試：ballotCounts 正整數值
     */
    public function testBallotCountsPositiveValues()
    {
        $model = new FormResults();
        $model->scenario = 'create';
        $model->voteID = 'TestVote';
        $model->candID = 1;
        $model->party = 'def';
        $model->jobLctn = '0';
        $model->elected = 'Y';
        $model->rank = 1;

        // 測試 0 票
        $model->ballotCounts = 0;
        $this->assertTrue($model->validate(['ballotCounts']), 'ballotCounts=0 should be valid');

        // 測試正整數
        $model->ballotCounts = 100;
        $this->assertTrue($model->validate(['ballotCounts']), 'ballotCounts=100 should be valid');
    }

    // ==================== P1-2：rank 整合（Ballots → Results） ====================

    /**
     * createResults 應將 getBallotCountSort 的 rank 寫入 results 表
     * （rank 不在 ballotsSelected，而在 results）
     */
    public function testCreateResultsWritesRankFromBallotCountSort()
    {
        $voteID = 'AnonPartyTest';
        $formResults = new FormResults();
        if ($formResults->isResults($voteID, 1)) {
            $formResults->deleteResults($voteID, 1);
        }

        $this->assertTrue($formResults->createResults($voteID), 'createResults should succeed');

        $manageCount = new FormManageCount($voteID);
        $expectedSort = $manageCount->getBallotCountSort(true);

        $this->assertNotEmpty($expectedSort, 'Fixture ballots should produce count sort data');

        foreach ($expectedSort as $party => $questions) {
            foreach ($questions as $questionID => $questionData) {
                foreach ($questionData['ranking'] as $rankRow) {
                    $record = Results::findOne([
                        'voteID' => $voteID,
                        'party' => $party,
                        'questionID' => (string)$questionID,
                        'candID' => $rankRow['candi'],
                    ]);
                    $this->assertNotNull(
                        $record,
                        "Missing results row for candidate {$rankRow['candi']}"
                    );
                    $this->assertEquals(
                        (int)$rankRow['rank'],
                        (int)$record->rank,
                        'results.rank should match getBallotCountSort'
                    );
                }
            }
        }
    }
}
