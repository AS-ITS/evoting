<?php

namespace app\tests\unit\models;

use Yii;
use app\models\Questions;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

/**
 * Questions 模型邊界值分析與等價劃分測試
 *
 * 補強項目：
 * - BVA：字串長度 max 邊界值
 * - BVA：整數欄位 min 邊界值
 * - EP：XSS 過濾（HtmlPurifier）驗證
 * - EP：無效輸入等價類
 */
class QuestionsBoundaryTest extends \Codeception\Test\Unit
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

    public function _fixtures()
    {
        return [
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'questions' => QuestionsFixture::class,
        ];
    }

    /**
     * 輔助方法：建立有效的 Questions model
     */
    private function createValidQuestion()
    {
        $model = new Questions();
        $model->scenario = 'create';
        $model->voteID = 'AnonPartyTest';
        $model->party = '0';
        $model->title = '測試問題';
        $model->titleE = 'Test Question';
        $model->numBallots = 1;
        $model->leastNumBallots = 1;
        $model->maxElect = 1;
        $model->numOfKeep = 1;
        $model->round = 1;
        return $model;
    }

    // ==================== BVA：字串長度 max 邊界值 ====================

    /**
     * BVA：title 恰好 20 字元應通過驗證
     */
    public function testTitleExactMaxLength()
    {
        $model = $this->createValidQuestion();
        $model->title = str_repeat('字', 20);
        $this->assertTrue($model->validate(['title']), 'title at max length (20) should pass');
    }

    /**
     * BVA：title 超過 20 字元應失敗
     */
    public function testTitleExceedsMaxLength()
    {
        $model = $this->createValidQuestion();
        $model->title = str_repeat('字', 21);
        $this->assertFalse($model->validate(['title']), 'title exceeding 20 chars should fail');
        $this->assertArrayHasKey('title', $model->errors);
    }

    /**
     * BVA：titleE 恰好 100 字元應通過驗證
     */
    public function testTitleEExactMaxLength()
    {
        $model = $this->createValidQuestion();
        $model->titleE = str_repeat('A', 100);
        $this->assertTrue($model->validate(['titleE']), 'titleE at max length (100) should pass');
    }

    /**
     * BVA：titleE 超過 100 字元應失敗
     */
    public function testTitleEExceedsMaxLength()
    {
        $model = $this->createValidQuestion();
        $model->titleE = str_repeat('A', 101);
        $this->assertFalse($model->validate(['titleE']), 'titleE exceeding 100 chars should fail');
        $this->assertArrayHasKey('titleE', $model->errors);
    }

    /**
     * BVA：ruleText 恰好 50 字元應通過驗證
     */
    public function testRuleTextExactMaxLength()
    {
        $model = $this->createValidQuestion();
        $model->ruleText = str_repeat('字', 50);
        $this->assertTrue($model->validate(['ruleText']), 'ruleText at max length (50) should pass');
    }

    /**
     * BVA：ruleText 超過 50 字元應失敗
     */
    public function testRuleTextExceedsMaxLength()
    {
        $model = $this->createValidQuestion();
        $model->ruleText = str_repeat('字', 51);
        $this->assertFalse($model->validate(['ruleText']), 'ruleText exceeding 50 chars should fail');
        $this->assertArrayHasKey('ruleText', $model->errors);
    }

    /**
     * BVA：ruleTextE 恰好 200 字元應通過驗證
     */
    public function testRuleTextEExactMaxLength()
    {
        $model = $this->createValidQuestion();
        $model->ruleTextE = str_repeat('A', 200);
        $this->assertTrue($model->validate(['ruleTextE']), 'ruleTextE at max length (200) should pass');
    }

    /**
     * BVA：ruleTextE 超過 200 字元應失敗
     */
    public function testRuleTextEExceedsMaxLength()
    {
        $model = $this->createValidQuestion();
        $model->ruleTextE = str_repeat('A', 201);
        $this->assertFalse($model->validate(['ruleTextE']), 'ruleTextE exceeding 200 chars should fail');
        $this->assertArrayHasKey('ruleTextE', $model->errors);
    }

    // ==================== BVA：整數欄位邊界值 ====================

    /**
     * BVA：numBallots 為 0 應驗證失敗（min=1）
     */
    public function testNumBallotsZero()
    {
        $model = $this->createValidQuestion();
        $model->numBallots = 0;
        $this->assertFalse($model->validate(['numBallots']), 'numBallots=0 should fail (min=1)');
        $this->assertArrayHasKey('numBallots', $model->errors);
    }

    /**
     * BVA：numBallots 為負數應驗證失敗
     */
    public function testNumBallotsNegative()
    {
        $model = $this->createValidQuestion();
        $model->numBallots = -1;
        $this->assertFalse($model->validate(['numBallots']), 'numBallots=-1 should fail');
        $this->assertArrayHasKey('numBallots', $model->errors);
    }

    /**
     * BVA：maxElect 為 0 應驗證失敗（min=1）
     */
    public function testMaxElectZero()
    {
        $model = $this->createValidQuestion();
        $model->maxElect = 0;
        $this->assertFalse($model->validate(['maxElect']), 'maxElect=0 should fail (min=1)');
        $this->assertArrayHasKey('maxElect', $model->errors);
    }

    /**
     * BVA：maxElect 為負數應驗證失敗
     */
    public function testMaxElectNegative()
    {
        $model = $this->createValidQuestion();
        $model->maxElect = -1;
        $this->assertFalse($model->validate(['maxElect']), 'maxElect=-1 should fail');
        $this->assertArrayHasKey('maxElect', $model->errors);
    }

    // ==================== EP：XSS 過濾驗證（HtmlPurifier） ====================

    /**
     * EP：description 中的 <script> 標籤應被 HtmlPurifier 移除
     */
    public function testDescriptionXssScriptTag()
    {
        $model = $this->createValidQuestion();
        $model->description = '<p>正常內容</p><script>alert("xss")</script>';

        $model->validate();

        $this->assertStringNotContainsString('<script>', $model->description,
            'HtmlPurifier should remove <script> tags from description');
        $this->assertStringContainsString('正常內容', $model->description,
            'Normal content should be preserved');
    }

    /**
     * EP：description 中的惡意事件屬性應被移除
     */
    public function testDescriptionXssEventHandler()
    {
        $model = $this->createValidQuestion();
        $model->description = '<img src="x" onerror="alert(1)">';

        $model->validate();

        $this->assertStringNotContainsString('onerror', $model->description,
            'HtmlPurifier should remove onerror event handler');
    }

    /**
     * EP：description 中的 javascript: URI 應被移除
     */
    public function testDescriptionXssJavascriptUri()
    {
        $model = $this->createValidQuestion();
        $model->description = '<a href="javascript:alert(1)">click</a>';

        $model->validate();

        $this->assertStringNotContainsString('javascript:', $model->description,
            'HtmlPurifier should remove javascript: URIs');
    }

    /**
     * EP：descriptionE（英文描述）同樣應被 XSS 過濾
     */
    public function testDescriptionEXssFilter()
    {
        $model = $this->createValidQuestion();
        $model->descriptionE = '<p>Normal</p><script>alert("xss")</script>';

        $model->validate();

        $this->assertStringNotContainsString('<script>', $model->descriptionE,
            'HtmlPurifier should remove <script> tags from descriptionE');
        $this->assertStringContainsString('Normal', $model->descriptionE,
            'Normal content should be preserved in descriptionE');
    }

    /**
     * EP：合法 HTML 標籤應保留
     */
    public function testDescriptionValidHtmlPreserved()
    {
        $model = $this->createValidQuestion();
        $model->description = '<p><b>粗體</b>與<i>斜體</i></p>';

        $model->validate();

        $this->assertStringContainsString('<b>粗體</b>', $model->description,
            'Valid HTML tags should be preserved');
        $this->assertStringContainsString('<i>斜體</i>', $model->description,
            'Valid HTML tags should be preserved');
    }

    /**
     * EP：空字串 description 應通過驗證
     */
    public function testDescriptionEmptyString()
    {
        $model = $this->createValidQuestion();
        $model->description = '';
        $model->descriptionE = '';

        $this->assertTrue($model->validate(['description', 'descriptionE']),
            'Empty description should pass validation');
    }

    // ==================== EP：單一分組問題 ====================

    /**
     * EP：只有單一分組的問題建立
     */
    public function testCreateQuestionWithSingleParty()
    {
        $questions = new Questions();
        $voteID = \UnitTester::ANON_PARTY_VOTEID;
        $data = [
            'Questions' => [
                'party' => ['0'],
                'title' => '單一分組問題',
                'titleE' => 'Single Party Question',
                'numBallots' => '1',
                'leastNumBallots' => '1',
                'maxElect' => '1',
                'numOfKeep' => '1',
                'voteID' => $voteID,
            ]
        ];

        $result = $questions->createQuestions($voteID, $data, false);
        $this->assertTrue($result, 'Single party question should be created successfully');

        // 清理
        Questions::deleteAll([
            'voteID' => $voteID,
            'title' => '單一分組問題',
        ]);
    }
}
