<?php

use app\models\Questions;
use app\models\Votes;
use app\models\CandiData;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

/**
 * 測試 Questions 模型的基礎功能
 *
 * 補充 QuestionTest.php 未涵蓋的模型層級測試
 */
class QuestionsModelTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
        ]);
        $this->tester->userLogin();
    }

    protected function _after()
    {
    }

    /**
     * 載入數據
     *
     * @return array
     */
    public function _fixtures()
    {
        return [
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'questions' => QuestionsFixture::class,
            'candiConfig' => CandiConfigFixture::class,
            'candiData' => CandiDataFixture::class,
        ];
    }

    /**
     * 測試 Questions 模型基本結構
     */
    public function testQuestionsModelStructure()
    {
        $question = new Questions();
        $this->assertNotNull($question);
        $this->assertEquals('questions', Questions::tableName());
    }

    /**
     * 測試 Questions 常數定義
     */
    public function testQuestionsConstants()
    {
        $this->assertEquals('N', Questions::ALL_PARTY_CODE);
    }

    /**
     * 測試 Questions 必填欄位驗證
     */
    public function testQuestionsRequiredFieldsOnCreate()
    {
        $question = new Questions();
        $question->scenario = 'create';
        $this->assertFalse($question->validate());

        $errors = $question->errors;
        $this->assertArrayHasKey('voteID', $errors);
        $this->assertArrayHasKey('party', $errors);
        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('titleE', $errors);
        $this->assertArrayHasKey('numBallots', $errors);
        $this->assertArrayHasKey('maxElect', $errors);
    }

    /**
     * 測試 Questions 數值驗證 - 最小值
     */
    public function testQuestionsIntegerMinValidation()
    {
        $question = new Questions();
        $question->scenario = 'create';

        // numBallots 最小值為 1
        $question->numBallots = 0;
        $question->validate(['numBallots']);
        $this->assertArrayHasKey('numBallots', $question->errors);

        $question->numBallots = 1;
        $question->validate(['numBallots']);
        $this->assertArrayNotHasKey('numBallots', $question->errors);

        // maxElect 最小值為 1
        $question->maxElect = 0;
        $question->validate(['maxElect']);
        $this->assertArrayHasKey('maxElect', $question->errors);

        $question->maxElect = 1;
        $question->validate(['maxElect']);
        $this->assertArrayNotHasKey('maxElect', $question->errors);
    }

    /**
     * 測試 Questions 字串長度驗證
     */
    public function testQuestionsStringLengthValidation()
    {
        $question = new Questions();

        // voteID 最大長度 20
        $question->voteID = str_repeat('a', 21);
        $question->validate(['voteID']);
        $this->assertArrayHasKey('voteID', $question->errors);

        // titleE 最大長度 100
        $question->titleE = str_repeat('b', 101);
        $question->validate(['titleE']);
        $this->assertArrayHasKey('titleE', $question->errors);

        // ruleText 最大長度 50
        $question->ruleText = str_repeat('c', 51);
        $question->validate(['ruleText']);
        $this->assertArrayHasKey('ruleText', $question->errors);
    }

    /**
     * 測試 getQuestionInfo 方法
     */
    public function testGetQuestionInfo()
    {
        $questionFixture = $this->tester->grabFixture('questions', 0);

        if (!$questionFixture) {
            $this->markTestSkipped('No questions in fixture');
        }

        $question = new Questions();
        $result = $question->getQuestionInfo($questionFixture->questionID);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Questions::class, $result);
        $this->assertEquals($questionFixture->questionID, $result->questionID);
    }

    /**
     * 測試 getQuestionsInfo 方法
     */
    public function testGetQuestionsInfo()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $question = new Questions();
        $results = $question->getQuestionsInfo($vote->voteID, 1);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        foreach ($results as $q) {
            $this->assertInstanceOf(Questions::class, $q);
            $this->assertEquals($vote->voteID, $q->voteID);
            $this->assertEquals(1, $q->round);
        }
    }

    /**
     * 測試 getQuestionsInfo 方法不指定輪次
     */
    public function testGetQuestionsInfoWithoutRound()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $question = new Questions();
        $results = $question->getQuestionsInfo($vote->voteID);

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        foreach ($results as $q) {
            $this->assertEquals($vote->voteID, $q->voteID);
        }
    }

    /**
     * 測試 getPartyQuestionsInfo 方法
     */
    public function testGetPartyQuestionsInfo()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $question = new Questions();
        $results = $question->getPartyQuestionsInfo($vote->voteID, '0', false);

        $this->assertIsArray($results);
        foreach ($results as $q) {
            $this->assertEquals($vote->voteID, $q['voteID']);
            $this->assertEquals('0', $q['party']);
        }
    }

    /**
     * 測試 getPartyQuestionsInfo 方法包含共同問題
     */
    public function testGetPartyQuestionsInfoWithAllParty()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $question = new Questions();
        $results = $question->getPartyQuestionsInfo($vote->voteID, '0', true);

        $this->assertIsArray($results);
        foreach ($results as $q) {
            $this->assertEquals($vote->voteID, $q['voteID']);
            $this->assertTrue(
                $q['party'] === '0' || $q['party'] === Questions::ALL_PARTY_CODE,
                "Party should be '0' or ALL_PARTY_CODE"
            );
        }
    }

    /**
     * 測試 getQuestionNumBallots 方法
     */
    public function testGetQuestionNumBallots()
    {
        $questionFixture = $this->tester->grabFixture('questions', 0);

        if (!$questionFixture) {
            $this->markTestSkipped('No questions in fixture');
        }

        $question = new Questions();
        $result = $question->getQuestionNumBallots($questionFixture->questionID);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('mostNum', $result);
        $this->assertArrayHasKey('leastNum', $result);
        $this->assertEquals($questionFixture->numBallots, $result['mostNum']);
        $this->assertEquals($questionFixture->leastNumBallots, $result['leastNum']);
    }

    /**
     * 測試 getQuestionListWithVoteID 方法
     */
    public function testGetQuestionListWithVoteID()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $question = new Questions();
        $query = $question->getQuestionListWithVoteID($vote->voteID);
        $results = $query->all();

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        foreach ($results as $q) {
            $this->assertEquals($vote->voteID, $q->voteID);
        }
    }

    /**
     * 測試 getRoundQuestions 方法
     */
    public function testGetRoundQuestions()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $question = new Questions();
        $query = $question->getRoundQuestions($vote->voteID, 1);
        $results = $query->all();

        $this->assertIsArray($results);
        foreach ($results as $q) {
            $this->assertEquals($vote->voteID, $q->voteID);
            $this->assertEquals(1, $q->round);
        }
    }

    /**
     * 測試 getQuestionsIdList 方法
     */
    public function testGetQuestionsIdList()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $question = new Questions();
        $questionIds = $question->getQuestionsIdList($vote->voteID, 1);

        $this->assertIsArray($questionIds);
        $this->assertNotEmpty($questionIds);
        foreach ($questionIds as $id) {
            $this->assertIsInt($id);
        }
    }

    /**
     * 測試 getQuestionsNumBallots 方法
     */
    public function testGetQuestionsNumBallots()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $question = new Questions();
        $result = $question->getQuestionsNumBallots($vote->voteID);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // 檢查結果按 party 索引
        foreach ($result as $party => $q) {
            $this->assertInstanceOf(Questions::class, $q);
            $this->assertEquals($party, $q->party);
        }
    }

    /**
     * 測試 search 方法基本功能
     */
    public function testSearchMethod()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $question = new Questions();
        $query = $question->search($vote->voteID, 1, []);

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query);

        $results = $query->all();
        foreach ($results as $q) {
            $this->assertEquals($vote->voteID, $q->voteID);
        }
    }

    /**
     * 測試 search 方法with參數過濾
     */
    public function testSearchMethodWithFilters()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $questionFixture = $this->tester->grabFixture('questions', 0);

        if (!$questionFixture) {
            $this->markTestSkipped('No questions in fixture');
        }

        $question = new Questions();
        $query = $question->search($vote->voteID, 1, [
            'Questions' => [
                'party' => $questionFixture->party,
            ]
        ]);

        $results = $query->all();
        foreach ($results as $q) {
            $this->assertEquals($questionFixture->party, $q->party);
        }
    }

    /**
     * 測試 HTML 淨化過濾器
     */
    public function testHtmlPurifierFilter()
    {
        $question = new Questions();
        $question->scenario = 'create';

        // 設置包含潛在惡意內容的描述
        $question->description = '<script>alert("XSS")</script><b>Safe Content</b>';
        $question->descriptionE = '<script>alert("XSS")</script><i>English Content</i>';

        // 驗證會觸發 HtmlPurifier 過濾器
        $question->validate(['description', 'descriptionE']);

        // 驗證惡意腳本被移除，但安全的 HTML 標籤保留
        $this->assertStringNotContainsString('<script>', $question->description);
        $this->assertStringContainsString('<b>', $question->description);
        $this->assertStringNotContainsString('<script>', $question->descriptionE);
        $this->assertStringContainsString('<i>', $question->descriptionE);
    }

    /**
     * 測試 Questions 與 CandiData 的關係（間接關聯通過 questionID）
     */
    public function testQuestionsRelationshipWithCandiData()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $questions = Questions::find()->where(['voteID' => $vote->voteID])->all();

        if (empty($questions)) {
            $this->markTestSkipped('No questions available');
        }

        $question = $questions[0];

        // 驗證可以查詢相關的候選人資料
        $candiData = CandiData::find()
            ->where(['voteID' => $vote->voteID, 'party' => $question->party])
            ->all();

        // 確認查詢結果
        $this->assertIsArray($candiData);
        foreach ($candiData as $candi) {
            $this->assertEquals($vote->voteID, $candi->voteID);
        }
    }

    /**
     * 測試 allParty 屬性
     */
    public function testAllPartyAttribute()
    {
        $question = new Questions();
        $question->allParty = true;

        $this->assertTrue($question->allParty);

        // 測試可以安全設定和讀取
        $question->allParty = false;
        $this->assertFalse($question->allParty);
    }
}
