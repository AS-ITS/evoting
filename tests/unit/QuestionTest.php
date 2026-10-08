<?php

use app\models\Questions;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

class QuestionTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        // 載入 UsersFixture, ConfigFixture 和 RbacFixture 以提供登入所需的資料
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
    public function _fixtures() {
        return [
            'votes'   => VotesFixture::className(),
            'parties'   => PartiesFixture::className(),
            'questions'   => QuestionsFixture::className(),
        ];
    }

    /**
     * 使用正確資料編輯問題
     * 
     * @dataProvider editQuestionsValidProvider
     */
    public function testUpdateQuestionWithValidData($voteID, $data)
    {
        // 取得所有問題
        $questions = Questions::findAll(['voteID' => UnitTester::ANON_PARTY_VOTEID]);

        // 呼叫 updateQuestion 方法
        foreach ($questions as $question) {
            $result = $question->updateQuestion($question->questionID, $data);
            // 驗證方法是否成功執行
            $this->assertTrue($result);
        }
    }

    /**
     * 使用錯誤資料編輯問題
     * 
     * @dataProvider editQuestionsInvalidProvider
     */
    public function testUpdateQuestionWithInvalidData($voteID, $data)
    {
        // 取得所有問題
        $questions = Questions::findAll(['voteID' => UnitTester::ANON_PARTY_VOTEID]);

        // 呼叫 updateQuestion 方法
        foreach ($questions as $question) {
            $result = $question->updateQuestion($question->questionID, $data);
            // 驗證方法是否成功執行
            $this->assertFalse($result);
        }
    }

    /**
     * 使用正確資料建立分組問題
     * 
     * @dataProvider createPartyQuestionsValidProvider
     */
    public function testCreatePartyQuestionWithValidData($voteID, $data)
    {
        // 建立 Questions 物件
        $questions = new Questions();

        // 呼叫 createQuestions 方法
        $result = $questions->createQuestions($voteID, $data, false);
        // 驗證方法是否成功執行
        $this->assertFalse(Yii::$app->session->hasFlash('error'));
        // 確認方法是否回傳 True，因為資料正確，方法應該成功
        $this->assertTrue($result);
        // 確認新增的資料是否存在，應該存在
        foreach ($data[$questions->formName()]['party'] as $party) {
            $this->tester->seeRecord($questions::className(), ['voteID' => $voteID, 'party' => $party]);
        }
    }

    /**
     * 使用錯誤資料建立分組問題-分組為空
     * 
     * @dataProvider createEmptyPartyQuestionProvider
     */
    public function testCreateQuestionWithEmptyParty($voteID, $data, $errorMessages)
    {
        // 建立 Questions 物件
        $questions = new Questions();

        // 呼叫 createQuestions 方法
        $result = $questions->createQuestions($voteID, $data, false);
        // 確認是否符合預期的錯誤
        $this->tester->assertErrors(1, $errorMessages);
        // 確認方法是否回傳 false，因為資料有錯誤，方法不應該成功
        $this->assertFalse($result);
    }

    /**
     * 使用錯誤資料建立分組問題-資料為空
     * 
     * @dataProvider createEmptyDataQuestionProvider
     */
    public function testCreateQuestionWithEmptyData($voteID, $data, $errorMessages)
    {
        // 建立 Questions 物件
        $questions = new Questions();

        // 呼叫 createQuestions 方法
        $result = $questions->createQuestions($voteID, $data, false);
        // 確認是否符合預期的錯誤
        $this->tester->assertErrors(6, $errorMessages);
        // 確認方法是否回傳 false，因為資料有錯誤，方法不應該成功
        $this->assertFalse($result);
    }

    /**
     * 使用錯誤資料建立分組問題-Exception
     * 
     * @dataProvider createInvalidDataQuestionProvider
     */
    public function testCreateQuestionWithInvalidData($voteID, $data, $errorMessages)
    {
        // 建立 Questions 物件
        $questions = new Questions();

        // 呼叫 createQuestions 方法
        $result = $questions->createQuestions($voteID, $data, false);
        // 確認是否符合預期的錯誤
        $this->tester->assertErrors(6, $errorMessages);
        // 確認方法是否回傳 false，因為資料有錯誤，方法不應該成功
        $this->assertFalse($result);
    }

    /**
     * 刪除問題
     * 
     * @depends testCreatePartyQuestionWithValidData
     * @dataProvider voteIDProvider
     */
    public function testDeleteQuestions($voteID)
    {
        // 取得所有問題
        $questions = Questions::findAll(['voteID' => $voteID]);
        
        // 呼叫 deleteQuestion 方法
        foreach ($questions as $question) {
            $result = $question->deleteQuestion($question->questionID);
            // 驗證方法是否成功執行
            $this->assertTrue($result);
        }
        // 確認被刪除的資料是否存在，不應該存在
        $this->tester->dontSeeRecord($question::className(), ['voteID' => $voteID]);
    }

    /**
     * 投票場次
     * 
     * @return array
     */
    public function voteIDProvider()
    {
        return [
            [UnitTester::ANON_PARTY_VOTEID],
            [UnitTester::ANON_NO_PARTY_VOTEID],
        ];
    }

    /**
     * 新增分組問題 - 正確資料
     * 
     * @return array
     */
    public function createPartyQuestionsValidProvider()
    {
        $voteID = UnitTester::ANON_PARTY_VOTEID;
        
        return [
            [
                'voteID' => $voteID,
                'data' => [
                    'Questions' =>[
                        'party' => ['0', '1', '2', '3'],
                        'title' => '問題二',
                        'titleE' => 'Question Two',
                        'confirmTitle' => '自定義未圈選顯示名稱',
                        'confirmTitleE' => '自定義未圈選顯示名稱(英)',
                        'description' => '<b>這是問題二</b>',
                        'descriptionE' => '<b>This is Question Two</b>',
                        'ruleText' => '<b>自定義投票規則文字</b>',
                        'ruleTextE' => '<b>自定義投票規則文字(英)</b>',
                        'numBallots' => '1',
                        'leastNumBallots' => '1',
                        'maxElect' => '1',
                        'numOfKeep' => '1',
                        'voteID' => $voteID,
                    ]
                ]
            ]
        ];
    }

    /**
     * 新增分組問題 - 錯誤資料(分組為空)
     * 
     * @return array
     */
    public function createEmptyPartyQuestionProvider()
    {
        $voteID = UnitTester::ANON_PARTY_VOTEID;
        
        return [
            [
                'voteID' => $voteID,
                'data' => [
                    'Questions' =>[
                        'party' => [],
                        'voteID' => $voteID,
                    ]
                ],
                'errorMessages' => [
                    '組別不能為空',
                ]
            ]
        ];
    }

    /**
     * 新增分組問題 - 錯誤資料(標題和投票規則為空)
     * 
     * @return array
     */
    public function createEmptyDataQuestionProvider()
    {
        $voteID = UnitTester::ANON_PARTY_VOTEID;
        
        return [
            [
                'voteID' => $voteID,
                'data' => [
                    'Questions' =>[
                        'party' => ['0'],
                        'title' => '',
                        'titleE' => '',
                        'description' => '<b>這是錯誤問題二</b>',
                        'descriptionE' => '<b>This is Error Question Two</b>',
                        'numBallots' => '',
                        'leastNumBallots' => '',
                        'maxElect' => '',
                        'numOfKeep' => '',
                        'voteID' => $voteID,
                    ]
                ],
                'errorMessages' => [
                    '標題 不能為空白。',
                    '英文標題 不能為空白。',
                    '最多可投票數 不能為空白。',
                    '當選人數 不能為空白。',
                    '最少應投票數 不能為空白。',
                    '遞補人數 不能為空白。',
                ]
            ]
        ];
    }

    /**
     * 新增分組問題 - 錯誤資料(觸發Exception)
     * 
     * @return array
     */
    public function createInvalidDataQuestionProvider()
    {
        $voteID = UnitTester::ANON_PARTY_VOTEID;
        
        return [
            [
                'voteID' => $voteID,
                'data' => [
                    'Questions' =>[
                        'party' => ['0'],
                        'voteID' => $voteID,
                    ]
                ],
                'errorMessages' => [
                    '標題 不能為空白。',
                    '英文標題 不能為空白。',
                    '最多可投票數 不能為空白。',
                    '當選人數 不能為空白。',
                    '最少應投票數 不能為空白。',
                    '遞補人數 不能為空白。',
                ]
            ]
        ];
    }

    /**
     * 更新分組問題 - 正確資料
     * 
     * @return array
     */
    public function editQuestionsValidProvider()
    {
        $voteID = UnitTester::ANON_PARTY_VOTEID;
        
        return [
            [
                'voteID' => $voteID,
                'data' => [
                    'Questions' => [
                        'title' => '更新後問題一',
                        'titleE' => 'After Edit Question One',
                        'confirmTitle' => '更新後自定義未圈選顯示名稱',
                        'confirmTitleE' => '更新後自定義未圈選顯示名稱(英)',
                        'description' => '<b>這是更新後問題一</b>',
                        'descriptionE' => '<b>This is After Edit Question One</b>',
                        'ruleText' => '<b>更新後自定義投票規則文字</b>',
                        'ruleTextE' => '<b>更新後自定義投票規則文字(英)</b>',
                        'numBallots' => '2',
                        'leastNumBallots' => '2',
                        'maxElect' => '2',
                        'numOfKeep' => '2',
                        'voteID' => $voteID,
                    ]
                ]
            ]
        ];
    }

    /**
     * 更新分組問題 - 錯誤資料
     * 
     * @return array
     */
    public function editQuestionsInvalidProvider()
    {
        $voteID = UnitTester::ANON_PARTY_VOTEID;
        
        return [
            [
                'voteID' => $voteID,
                'data' => [
                    'Questions' => [
                        'title' => '更新後問題一',
                        'titleE' => 'After Edit Question One',
                        'confirmTitle' => '更新後自定義未圈選顯示名稱',
                        'confirmTitleE' => '更新後自定義未圈選顯示名稱(英)',
                        'description' => '<b>這是更新後問題一</b>',
                        'descriptionE' => '<b>This is After Edit Question One</b>',
                        'ruleText' => '<b>更新後自定義投票規則文字</b>',
                        'ruleTextE' => '<b>更新後自定義投票規則文字(英)</b>',
                        'numBallots' => '',
                        'leastNumBallots' => '',
                        'maxElect' => '',
                        'numOfKeep' => '',
                        'voteID' => $voteID,
                    ]
                ]
            ]
        ];
    }
}