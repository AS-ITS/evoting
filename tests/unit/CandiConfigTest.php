<?php

use app\models\FormCandiConfig;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

class CandiConfigTest extends \Codeception\Test\Unit
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
            'candiConfigs'   => CandiConfigFixture::className(),
        ];
    }

    /**
     * 使用正確資料建立候選名單配置
     * 
     * @dataProvider createValidProvider
     */
    public function testCreateCandiConfigWithValidData($voteID, $data)
    {
        $action = 'create';
        // 建立 CandiConfig 物件
        $candiConfig = new FormCandiConfig(['scenario' => $action]);

        // 呼叫 updateConfig 方法
        $result = $candiConfig->updateConfig($voteID, $data, $action);
        // 驗證方法是否成功執行
        $this->assertFalse(Yii::$app->session->hasFlash('error'));
        // 確認方法是否回傳 True，因為資料正確，方法應該成功
        $this->assertTrue($result);
        // 確認新增的資料是否存在，應該存在
        $this->tester->seeRecord($candiConfig::className(), ['voteID' => $voteID]);
    }

    /**
     * 使用錯誤資料建立候選名單配置
     * 
     * @dataProvider invalidProvider
     */
    public function testCreateCandiConfigWithInvalidData($voteID, $data, $errorMessages)
    {
        $action = 'create';
        // 建立 CandiConfig 物件
        $candiConfig = new FormCandiConfig(['scenario' => $action]);

        // 呼叫 updateConfig 方法
        $result = $candiConfig->updateConfig($voteID, $data, $action);
        // 確認是否符合預期的錯誤
        $this->tester->assertErrors(26, $errorMessages);
        // 確認方法是否回傳 false，因為資料有錯誤，方法不應該成功
        $this->assertFalse($result);
        // 確認新增的資料是否存在，應該不存在
        $this->tester->dontSeeRecord($candiConfig::className(), ['voteID' => '']);
    }

    /**
     * 使用正確資料編輯候選名單配置
     * 
     * @depends testCreateCandiConfigWithValidData
     * @dataProvider updateValidProvider
     */
    public function testUpdateCandiConfigWithValidData($voteID, $data)
    {
        $action = 'update';
        // 建立 CandiConfig 物件
        $candiConfig = FormCandiConfig::findOne(['voteID' => $voteID]);
        $candiConfig->scenario = $action;
        // 呼叫 updateConfig 方法
        $result = $candiConfig->updateConfig($voteID, $data, $action);
        // 驗證方法是否成功執行
        $this->assertFalse(Yii::$app->session->hasFlash('error'));
        // 確認方法是否回傳 True，因為資料正確，方法應該成功
        $this->assertTrue($result);
    }

    /**
     * 使用錯誤資料編輯候選名單配置
     * 
     * @depends testCreateCandiConfigWithValidData
     * @dataProvider invalidProvider
     */
    public function testUpdateCandiConfigWithInvalidData($voteID, $data, $errorMessages)
    {
        $action = 'update';
        // 建立 CandiConfig 物件
        $candiConfig = new FormCandiConfig(['scenario' => $action]);

        // 呼叫 updateConfig 方法
        $result = $candiConfig->updateConfig($voteID, $data, $action);
        // 確認是否符合預期的錯誤
        $this->tester->assertErrors(26, $errorMessages);
        // 確認方法是否回傳 false，因為資料有錯誤，方法不應該成功
        $this->assertFalse($result);
    }

    /**
     * 刪除候選名單配置
     * 
     * @depends testCreateCandiConfigWithValidData
     * @dataProvider createValidProvider
     */
    public function testDeleteCandiConfig($voteID)
    {
        // 建立 CandiConfig 物件
        $candiConfig = new FormCandiConfig();

        // 呼叫 deleteAllCandiConfig 方法
        $candiConfig->deleteAllCandiConfig($voteID);
        // 確認被刪除的資料是否存在，不應該存在
        $this->tester->dontSeeRecord($candiConfig::className(), ['voteID' => $voteID]);
    }

    /**
     * 新增候選名單配置 - 正確資料
     */
    public function createValidProvider()
    {
        return [
            [
                'voteID' => UnitTester::ANON_PARTY_VOTEID,
                'data' => [
                    'FormCandiConfig' => [
                        'voteID' => UnitTester::ANON_PARTY_VOTEID,
                        'questionID' => NULL,
                        'num' => 'A',
                        'Name' => '姓名',
                        'NameE' => 'Name',
                        'useBeforeHeader' => '0',
                        'columnNum' => '1',
                        'width' => '1',
                        'showFieldSort' => UnitTester::CANDI_CONFIG_DEF_FIELD_SORT,
                        'otherColNameA' => '自定義欄位名稱 1',
                        'otherColNameB' => '自定義欄位名稱 2',
                        'otherColNameC' => '自定義欄位名稱 3',
                        'otherColNameD' => '自定義欄位名稱 4',
                        'otherColNameE' => '自定義欄位名稱 5',
                        'otherColNameF' => '自定義欄位名稱 6',
                        'beforeHeaderA' => '欄位表頭名稱 1',
                        'beforeHeaderB' => '欄位表頭名稱 2',
                        'beforeHeaderC' => '欄位表頭名稱 3',
                        'otherColNameAE' => '自定義欄位名稱 1 (英文)',
                        'otherColNameBE' => '自定義欄位名稱 2 (英文)',
                        'otherColNameCE' => '自定義欄位名稱 3 (英文)',
                        'otherColNameDE' => '自定義欄位名稱 4 (英文)',
                        'otherColNameEE' => '自定義欄位名稱 5 (英文)',
                        'otherColNameFE' => '自定義欄位名稱 6 (英文)',
                        'beforeHeaderAE' => '欄位表頭名稱 1 (英文)',
                        'beforeHeaderBE' => '欄位表頭名稱 2 (英文)',
                        'beforeHeaderCE' => '欄位表頭名稱 3 (英文)',
                    ]
                ]
            ],
            [
                'voteID' => UnitTester::ANON_NO_PARTY_VOTEID,
                'data' => [
                    'FormCandiConfig' => [
                        'voteID' => UnitTester::ANON_NO_PARTY_VOTEID,
                        'questionID' => NULL,
                        'num' => 'A',
                        'Name' => '姓名',
                        'NameE' => 'Name',
                        'useBeforeHeader' => '0',
                        'columnNum' => '1',
                        'width' => '1',
                        'showFieldSort' => UnitTester::CANDI_CONFIG_DEF_FIELD_SORT,
                        'otherColNameA' => '自定義欄位名稱 1',
                        'otherColNameB' => '自定義欄位名稱 2',
                        'otherColNameC' => '自定義欄位名稱 3',
                        'otherColNameD' => '自定義欄位名稱 4',
                        'otherColNameE' => '自定義欄位名稱 5',
                        'otherColNameF' => '自定義欄位名稱 6',
                        'beforeHeaderA' => '欄位表頭名稱 1',
                        'beforeHeaderB' => '欄位表頭名稱 2',
                        'beforeHeaderC' => '欄位表頭名稱 3',
                        'otherColNameAE' => '自定義欄位名稱 1 (英文)',
                        'otherColNameBE' => '自定義欄位名稱 2 (英文)',
                        'otherColNameCE' => '自定義欄位名稱 3 (英文)',
                        'otherColNameDE' => '自定義欄位名稱 4 (英文)',
                        'otherColNameEE' => '自定義欄位名稱 5 (英文)',
                        'otherColNameFE' => '自定義欄位名稱 6 (英文)',
                        'beforeHeaderAE' => '欄位表頭名稱 1 (英文)',
                        'beforeHeaderBE' => '欄位表頭名稱 2 (英文)',
                        'beforeHeaderCE' => '欄位表頭名稱 3 (英文)',
                    ]
                ]
            ],
        ];
    }

    /**
     * 更新候選名單配置 - 正確資料
     */
    public function updateValidProvider()
    {
        return [
            [
                'voteID' => UnitTester::ANON_PARTY_VOTEID,
                'data' => [
                    'FormCandiConfig' => [
                        'voteID' => UnitTester::ANON_PARTY_VOTEID,
                        'questionID' => 1,
                        'num' => 'C',
                        'Name' => '候選人姓名',
                        'NameE' => 'Name',
                        'useBeforeHeader' => '1',
                        'columnNum' => '3',
                        'width' => '2',
                        'showFieldSort' => '[{"id":"headerA","children":[{"id":"Name","children":[]}]},{"id":"otherColA","children":[]}]',
                        'otherColNameA' => '更新自定義欄位名稱 1',
                        'otherColNameB' => '更新自定義欄位名稱 2',
                        'otherColNameC' => '更新自定義欄位名稱 3',
                        'otherColNameD' => '更新自定義欄位名稱 4',
                        'otherColNameE' => '更新自定義欄位名稱 5',
                        'otherColNameF' => '更新自定義欄位名稱 6',
                        'beforeHeaderA' => '更新欄位表頭名稱 1',
                        'beforeHeaderB' => '更新欄位表頭名稱 2',
                        'beforeHeaderC' => '更新欄位表頭名稱 3',
                        'otherColNameAE' => '更新自定義欄位名稱 1 (英文)',
                        'otherColNameBE' => '更新自定義欄位名稱 2 (英文)',
                        'otherColNameCE' => '更新自定義欄位名稱 3 (英文)',
                        'otherColNameDE' => '更新自定義欄位名稱 4 (英文)',
                        'otherColNameEE' => '更新自定義欄位名稱 5 (英文)',
                        'otherColNameFE' => '更新自定義欄位名稱 6 (英文)',
                        'beforeHeaderAE' => '更新欄位表頭名稱 1 (英文)',
                        'beforeHeaderBE' => '更新欄位表頭名稱 2 (英文)',
                        'beforeHeaderCE' => '更新欄位表頭名稱 3 (英文)',
                    ]
                ]
            ],
            [
                'voteID' => UnitTester::ANON_NO_PARTY_VOTEID,
                'data' => [
                    'FormCandiConfig' => [
                        'voteID' => UnitTester::ANON_NO_PARTY_VOTEID,
                        'questionID' => NULL,
                        'num' => 'A',
                        'Name' => '候選人姓名',
                        'NameE' => 'Name',
                        'useBeforeHeader' => '0',
                        'columnNum' => '1',
                        'width' => '1',
                        'showFieldSort' => UnitTester::CANDI_CONFIG_DEF_FIELD_SORT,
                        'otherColNameA' => '自定義欄位名稱 1',
                        'otherColNameB' => '自定義欄位名稱 2',
                        'otherColNameC' => '自定義欄位名稱 3',
                        'otherColNameD' => '自定義欄位名稱 4',
                        'otherColNameE' => '自定義欄位名稱 5',
                        'otherColNameF' => '自定義欄位名稱 6',
                        'beforeHeaderA' => '欄位表頭名稱 1',
                        'beforeHeaderB' => '欄位表頭名稱 2',
                        'beforeHeaderC' => '欄位表頭名稱 3',
                        'otherColNameAE' => '自定義欄位名稱 1 (英文)',
                        'otherColNameBE' => '自定義欄位名稱 2 (英文)',
                        'otherColNameCE' => '自定義欄位名稱 3 (英文)',
                        'otherColNameDE' => '自定義欄位名稱 4 (英文)',
                        'otherColNameEE' => '自定義欄位名稱 5 (英文)',
                        'otherColNameFE' => '自定義欄位名稱 6 (英文)',
                        'beforeHeaderAE' => '欄位表頭名稱 1 (英文)',
                        'beforeHeaderBE' => '欄位表頭名稱 2 (英文)',
                        'beforeHeaderCE' => '欄位表頭名稱 3 (英文)',
                    ]
                ]
            ],
        ];
    }

    /**
     * 候選名單配置 - 錯誤資料
     */
    public function invalidProvider()
    {
        return [
            [
                'voteID' => UnitTester::ANON_PARTY_VOTEID,
                'data' => [
                    'FormCandiConfig' => [
                        'voteID' => '',
                        'questionID' => NULL,
                        'num' => '',
                        'Name' => '',
                        'NameE' => '',
                        'useBeforeHeader' => '',
                        'columnNum' => '',
                        'width' => '',
                        'showFieldSort' => '',
                        'otherColNameA' => str_repeat('a', 121),
                        'otherColNameB' => str_repeat('a', 121),
                        'otherColNameC' => str_repeat('a', 121),
                        'otherColNameD' => str_repeat('a', 121),
                        'otherColNameE' => str_repeat('a', 121),
                        'otherColNameF' => str_repeat('a', 121),
                        'beforeHeaderA' => str_repeat('a', 121),
                        'beforeHeaderB' => str_repeat('a', 121),
                        'beforeHeaderC' => str_repeat('a', 121),
                        'otherColNameAE' => str_repeat('a', 256),
                        'otherColNameBE' => str_repeat('a', 256),
                        'otherColNameCE' => str_repeat('a', 256),
                        'otherColNameDE' => str_repeat('a', 256),
                        'otherColNameEE' => str_repeat('a', 256),
                        'otherColNameFE' => str_repeat('a', 256),
                        'beforeHeaderAE' => str_repeat('a', 256),
                        'beforeHeaderBE' => str_repeat('a', 256),
                        'beforeHeaderCE' => str_repeat('a', 256),
                    ]
                ],
                'errorMessages' => [
                    'Vote ID 不能為空白。',
                    '編號顯示 不能為空白。',
                    '投票時候選名單顯示列數 不能為空白。',
                    '名稱顯示文字 不能為空白。',
                    '名稱(英)顯示文字 不能為空白。',
                    '顯示的欄位跟排序 不能為空白。',
                    '使用欄位表頭 不能為空白。',
                    '寬度 不能為空白。',
                    '自定義欄位名稱 1(超過長度的部分會被替換成...) 只能包含最多 120 個字符。',
                    '自定義欄位名稱 2 只能包含最多 120 個字符。',
                    '自定義欄位名稱 3 只能包含最多 120 個字符。',
                    '自定義欄位名稱 4 只能包含最多 120 個字符。',
                    '自定義欄位名稱 5 只能包含最多 120 個字符。',
                    '自定義欄位名稱 6 只能包含最多 120 個字符。',
                    '欄位表頭名稱 1 只能包含最多 120 個字符。',
                    '欄位表頭名稱 2 只能包含最多 120 個字符。',
                    '欄位表頭名稱 3 只能包含最多 120 個字符。',
                    '自定義欄位名稱 1 (英文) 只能包含最多 255 個字符。',
                    '自定義欄位名稱 2 (英文) 只能包含最多 255 個字符。',
                    '自定義欄位名稱 3 (英文) 只能包含最多 255 個字符。',
                    '自定義欄位名稱 4 (英文) 只能包含最多 255 個字符。',
                    '自定義欄位名稱 5 (英文) 只能包含最多 255 個字符。',
                    '自定義欄位名稱 6 (英文) 只能包含最多 255 個字符。',
                    '欄位表頭名稱 1 (英文) 只能包含最多 255 個字符。',
                    '欄位表頭名稱 2 (英文) 只能包含最多 255 個字符。',
                    '欄位表頭名稱 3 (英文) 只能包含最多 255 個字符。',
                ]
            ],
        ];
    }
}