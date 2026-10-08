<?php

use app\models\CandiData;
use app\models\FormCandiData;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

class CandiDataTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /** Faker中文 */
    public $faker;
    /** Faker英文 */
    public $fakerE;

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
        $this->faker = Faker\Factory::create('zh_TW');
        $this->fakerE = Faker\Factory::create();
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
            'candiConfig'   => CandiConfigFixture::className(),
        ];
    }

    /**
     * 使用正確資料新增候選名單
     */
    public function testCreateManualWithValidData()
    {
        $questions = $this->tester->grabFixture('questions');
        foreach ($questions as $key => $question) {
            for ($i=0; $i < 5; $i++) { 
                $key = Yii::$app->security->generateRandomString(8);
                $data['FormCandiData'] = [
                    'voteID' => $question['voteID'],
                    'party' => $question['party'],
                    'questionID' => $question['questionID'],
                    'isReachThreshold' => NULL,
                    'jobLctn' => '1',
                    'instCode' => '00',
                    'instName' => $this->faker->company(),
                    'instNameE' => $this->fakerE->company(),
                    'title' => $this->faker->jobTitle(),
                    'titleE' => $this->fakerE->jobTitle(),
                    'Name' => $this->faker->name(),
                    'NameE' => $this->fakerE->name(),
                    'sex' => strval($this->faker->numberBetween(0, 1)),
                    'orderNum' => 0,
                    'otherColA' => '自定義欄位1',
                    'otherColAE' => '自定義欄位1(英文)',
                    'otherColB' => '自定義欄位2',
                    'otherColBE' => '自定義欄位2(英文)',
                    'otherColC' => '自定義欄位3',
                    'otherColCE' => '自定義欄位3(英文)',
                    'otherColD' => '自定義欄位4',
                    'otherColDE' => '自定義欄位4(英文)',
                    'otherColE' => '自定義欄位 5',
                    'otherColEE' => '自定義欄位 5(英文)',
                    'otherColF' => '自定義欄位 6',
                    'otherColFE' => '自定義欄位 6(英文)',
                    'genMode' => 'manual',
                    'backgroundColor' => $this->faker->hexColor(),
                    'other' => $key,
                ];
                // 建立 FormCandiData 物件
                $candiData = new FormCandiData();

                // 呼叫 updateCandiData 方法
                $result = $candiData->updateCandiData($question['voteID'], null, $data, 'create');
                // 驗證方法是否成功執行
                $this->assertFalse(Yii::$app->session->hasFlash('error'));
                // 確認方法是否回傳 True，因為資料正確，方法應該成功
                $this->assertTrue($result);
                // 確認新增的資料是否存在，應該存在
                $this->tester->seeRecord($candiData::className(), [
                    'voteID' => $question['voteID'], 
                    'party' => $question['party'],
                    'other' => $key
                ]);
            }
        }
    }
    
    /**
     * 使用錯誤資料新增候選名單
     * 
     * @dataProvider createManualInvalidProvider
     */
    public function testCreateManualWithInvalidRequireData($data, $errorCount, $errorMessages)
    {
        $questions = $this->tester->grabFixture('questions');
        foreach ($questions as $key => $question) {
            // 建立 FormCandiData 物件
            $candiData = new FormCandiData();

            // 呼叫 updateCandiData 方法
            $result = $candiData->updateCandiData($question['voteID'], null, $data, 'create');
            // 確認是否符合預期的錯誤
            $this->tester->assertErrors($errorCount, $errorMessages);
            // 確認方法是否回傳 false，因為資料有錯誤，方法不應該成功
            $this->assertFalse($result);
        }
    }

    /**
     * 使用正確資料更新候選名單
     * 
     * @dataProvider voteIDProvider
     */
    public function testUpdateManualWithValidData($voteID)
    {
        $candiDatas = FormCandiData::find()->where(['voteID' => $voteID])->all();
        foreach ($candiDatas as $candiData) {
            $key = $candiData->other;
            $data['FormCandiData'] = [
                'id' => $candiData->id,
                'voteID' => $voteID,
                'party' => $candiData->party,
                'questionID' => $candiData->questionID,
                'isReachThreshold' => NULL,
                'jobLctn' => '1',
                'instCode' => '00',
                'instName' => $this->faker->company(),
                'instNameE' => $this->fakerE->company(),
                'title' => $this->faker->jobTitle(),
                'titleE' => $this->fakerE->jobTitle(),
                'Name' => $this->faker->name(),
                'NameE' => $this->fakerE->name(),
                'sex' => strval($this->faker->numberBetween(0, 1)),
                'orderNum' => 0,
                'otherColA' => '自定義欄位1',
                'otherColAE' => '自定義欄位1(英文)',
                'otherColB' => '自定義欄位2',
                'otherColBE' => '自定義欄位2(英文)',
                'otherColC' => '自定義欄位3',
                'otherColCE' => '自定義欄位3(英文)',
                'otherColD' => '自定義欄位4',
                'otherColDE' => '自定義欄位4(英文)',
                'otherColE' => '自定義欄位 5',
                'otherColEE' => '自定義欄位 5(英文)',
                'otherColF' => '自定義欄位 6',
                'otherColFE' => '自定義欄位 6(英文)',
                'genMode' => 'manual',
                'backgroundColor' => $this->faker->hexColor(),
                'other' => $key,
            ];
            // 呼叫 updateCandiData 方法
            $result = $candiData->updateCandiData($voteID, $candiData->id, $data, 'update');
            // 驗證方法是否成功執行
            $this->assertFalse(Yii::$app->session->hasFlash('error'));
            // 確認方法是否回傳 True，因為資料正確，方法應該成功
            $this->assertTrue($result);
            // 確認新增的資料是否存在，應該存在
            $this->tester->seeRecord($candiData::className(), [
                'voteID' => $voteID, 
                'party' => $candiData->party,
                'other' => $key
            ]);
        }
    }

    /**
     * 使用錯誤資料更新候選名單
     * 
     * @dataProvider updateManualInvalidProvider
     */
    public function testUpdateManualWithInvalidData($data, $errorCount, $errorMessages)
    {
        $questions = $this->tester->grabFixture('questions');
        foreach ($questions as  $question) {
            $candiDatas = FormCandiData::find()->where(['questionID' => $question['questionID']])->all();
            foreach ($candiDatas as $candiData) {
                // 呼叫 updateCandiData 方法
                $result = $candiData->updateCandiData($question['voteID'], $candiData->id, $data, 'update');
                // 確認是否符合預期的錯誤
                $this->tester->assertErrors($errorCount, $errorMessages);
                // 確認方法是否回傳 false，因為資料有錯誤，方法不應該成功
                $this->assertFalse($result);
            }
        }
    }

    /**
     * 使用錯誤資料更新候選名單
     * 
     * @dataProvider voteIDProvider
     */
    public function testDeleteManualDataWithCondition($voteID)
    {
        $voteInfo = $this->tester->grabFixture('votes', $voteID);
        $FormCandiData = new FormCandiData();
        $result = $FormCandiData->deleteCandiData($voteID, $voteInfo['round'], [
            'FormCandiData' => [
                'voteID' => $voteID,
                'genMode' => 'manual'
            ]
        ]);
        $this->assertTrue($result);
        $this->tester->dontSeeRecord(CandiData::className(), [
            'voteID' => $voteID,
            'genMode' => 'manual'
        ]);
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
     * 新增候選名單 - 錯誤資料
     * 
     * @return array
     */
    public function createManualInvalidProvider()
    {
        return [
            [
                'data' => [
                    'FormCandiData' => [
                        'voteID' => '',
                        'party' => '',
                        'questionID' => '',
                        'instCode' => '',
                        'Name' => '',
                        'orderNum' => '',
                        'genMode' => '',
                    ]
                ],
                'errorCount' => 6,
                'errorMessages' => [
                    '分組 不能為空白。',
                    '問題 不能為空白。',
                    '單位代碼 不能為空白。',
                    '名字/名稱 不能為空白。',
                    '自訂排序 不能為空白。',
                    '生成方式 不能為空白。',
                ]
            ],
            [
                'data' => [
                    'FormCandiData' => [
                        'voteID' => str_repeat('a', 21),
                        'party' => str_repeat('a', 13),
                        'isReachThreshold' => str_repeat('a', 2),
                        'jobLctn' => str_repeat('a', 2),
                        'instCode' => str_repeat('a', 3),
                        'tCode' => str_repeat('a', 4),
                        'instName' => str_repeat('a', 51),
                        'instNameE' => str_repeat('a', 101),
                        'title' => str_repeat('a', 51),
                        'titleE' => str_repeat('a', 101),
                        'Name' => str_repeat('a', 51),
                        'NameE' => str_repeat('a', 101),
                        'sex' => str_repeat('a', 2),
                        'otherColA' => str_repeat('a', 51),
                        'otherColAE' => str_repeat('a', 256),
                        'otherColB' => str_repeat('a', 51),
                        'otherColBE' => str_repeat('a', 256),
                        'otherColC' => str_repeat('a', 51),
                        'otherColCE' => str_repeat('a', 256),
                        'otherColD' => str_repeat('a', 51),
                        'otherColDE' => str_repeat('a', 256),
                        'otherColE' => str_repeat('a', 51),
                        'otherColEE' => str_repeat('a', 256),
                        'otherColF' => str_repeat('a', 51),
                        'otherColFE' => str_repeat('a', 256),
                        'genMode' => str_repeat('a', 21),
                        'other' => str_repeat('a', 21),
                        'backgroundColor' => str_repeat('a', 31),
                    ]
                ],
                'errorCount' => 26,
                'errorMessages' => [
                    '分組 只能包含最多 12 個字符。',
                    '工作地點 只能包含最多 1 個字符。',
                    '性別 只能包含最多 1 個字符。',
                    '已達門檻 只能包含最多 1 個字符。',
                    '單位 只能包含最多 2 個字符。',
                    '人事法規職稱 只能包含最多 3 個字符。',
                    '生成方式 只能包含最多 10 個字符。',
                    '背景顏色 只能包含最多 30 個字符。',
                    '單位 只能包含最多 50 個字符。',
                    '職稱 只能包含最多 50 個字符。',
                    '名字/名稱 只能包含最多 50 個字符。',
                    '自定義欄位 1 只能包含最多 50 個字符。',
                    '自定義欄位 2 只能包含最多 50 個字符。',
                    '自定義欄位 3 只能包含最多 50 個字符。',
                    '自定義欄位 4 只能包含最多 50 個字符。',
                    '自定義欄位 5 只能包含最多 50 個字符。',
                    '自定義欄位 6 只能包含最多 50 個字符。',
                    '單位(英文) 只能包含最多 100 個字符。',
                    '職稱(英文) 只能包含最多 100 個字符。',
                    '名字/名稱(英文) 只能包含最多 100 個字符。',
                    '自定義欄位 1(英文) 只能包含最多 255 個字符。',
                    '自定義欄位 2(英文) 只能包含最多 255 個字符。',
                    '自定義欄位 3(英文) 只能包含最多 255 個字符。',
                    '自定義欄位 4(英文) 只能包含最多 255 個字符。',
                    '自定義欄位 5(英文) 只能包含最多 255 個字符。',
                    '自定義欄位 6(英文) 只能包含最多 255 個字符。',
                ]
            ]
        ];
    }

    /**
     * 更新候選名單 - 錯誤資料
     * 
     * @return array
     */
    public function updateManualInvalidProvider()
    {
        return [
            [
                'data' => [
                    'FormCandiData' => [
                        'voteID' => str_repeat('a', 21),
                        'party' => str_repeat('a', 13),
                        'isReachThreshold' => str_repeat('a', 2),
                        'jobLctn' => str_repeat('a', 2),
                        'instCode' => str_repeat('a', 3),
                        'tCode' => str_repeat('a', 4),
                        'instName' => str_repeat('a', 51),
                        'instNameE' => str_repeat('a', 101),
                        'title' => str_repeat('a', 51),
                        'titleE' => str_repeat('a', 101),
                        'Name' => str_repeat('a', 51),
                        'NameE' => str_repeat('a', 101),
                        'sex' => str_repeat('a', 2),
                        'otherColA' => str_repeat('a', 51),
                        'otherColAE' => str_repeat('a', 256),
                        'otherColB' => str_repeat('a', 51),
                        'otherColBE' => str_repeat('a', 256),
                        'otherColC' => str_repeat('a', 51),
                        'otherColCE' => str_repeat('a', 256),
                        'otherColD' => str_repeat('a', 51),
                        'otherColDE' => str_repeat('a', 256),
                        'otherColE' => str_repeat('a', 51),
                        'otherColEE' => str_repeat('a', 256),
                        'otherColF' => str_repeat('a', 51),
                        'otherColFE' => str_repeat('a', 256),
                        'genMode' => str_repeat('a', 21),
                        'other' => str_repeat('a', 21),
                        'backgroundColor' => str_repeat('a', 31),
                    ]
                ],
                'errorCount' => 26,
                'errorMessages' => [
                    '分組 只能包含最多 12 個字符。',
                    '工作地點 只能包含最多 1 個字符。',
                    '性別 只能包含最多 1 個字符。',
                    '已達門檻 只能包含最多 1 個字符。',
                    '單位 只能包含最多 2 個字符。',
                    '人事法規職稱 只能包含最多 3 個字符。',
                    '生成方式 只能包含最多 10 個字符。',
                    '背景顏色 只能包含最多 30 個字符。',
                    '單位 只能包含最多 50 個字符。',
                    '職稱 只能包含最多 50 個字符。',
                    '名字/名稱 只能包含最多 50 個字符。',
                    '自定義欄位 1 只能包含最多 50 個字符。',
                    '自定義欄位 2 只能包含最多 50 個字符。',
                    '自定義欄位 3 只能包含最多 50 個字符。',
                    '自定義欄位 4 只能包含最多 50 個字符。',
                    '自定義欄位 5 只能包含最多 50 個字符。',
                    '自定義欄位 6 只能包含最多 50 個字符。',
                    '單位(英文) 只能包含最多 100 個字符。',
                    '職稱(英文) 只能包含最多 100 個字符。',
                    '名字/名稱(英文) 只能包含最多 100 個字符。',
                    '自定義欄位 1(英文) 只能包含最多 255 個字符。',
                    '自定義欄位 2(英文) 只能包含最多 255 個字符。',
                    '自定義欄位 3(英文) 只能包含最多 255 個字符。',
                    '自定義欄位 4(英文) 只能包含最多 255 個字符。',
                    '自定義欄位 5(英文) 只能包含最多 255 個字符。',
                    '自定義欄位 6(英文) 只能包含最多 255 個字符。',
                ]
            ]
        ];
    }
}