<?php

use app\models\Round;
use app\models\Votes;
use app\models\FormVotes;
use app\models\Questions;
use app\models\FormParties;
use app\models\FormManageVote;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

class AnonPartyVoteTest extends \Codeception\Test\Unit
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
     * 使用正確資料建立投票
     * 
     * @dataProvider voteValidProvider
     */
    public function testCreateAnonVoteWithValidData($voteID, $data)
    {
        // 清理可能存在的舊資料
        Questions::deleteAll(['voteID' => $voteID]);
        FormParties::deleteAll(['voteID' => $voteID]);
        Votes::deleteAll(['voteID' => $voteID]);

        // 建立 FormVotes 物件
        $formVotes = new FormVotes;

        // 呼叫 updateVoteInfo 方法
        $result = $formVotes->updateVoteInfo($voteID, $data, true);
        // 驗證方法是否成功執行
        $this->assertFalse(Yii::$app->session->hasFlash('error'));
        // 確認方法是否回傳 True，因為資料正確，方法應該成功
        $this->assertTrue($result);
        // 測試排序不為空
        $this->assertNotEmpty($formVotes->sort);
        // 測試短網址不為空
        $this->assertNotEmpty($formVotes->shortUrl);
        // 測試此場次為匿名分組投票
        $this->assertEquals($formVotes->type, FormVotes::TYPE_ANON);
        $this->assertEquals('1', $formVotes->partyOrNot);
        // 測試預設問題一同建立
        $questions = Questions::find()->where(['voteID' => $voteID])->count();
        $this->assertEquals(4, $questions);
        // 確認資料寫入資料庫
        $this->tester->seeRecord(FormVotes::class, $data['FormVotes']);
    }

    /**
     * 使用錯誤資料建立投票
     * 
     * @dataProvider voteInvalidProvider
     */
    public function testCreateAnonVoteWithInvalidData($voteID, $data, $voteErrors)
    {
        // 建立 FormVotes 物件
        $formVotes = new FormVotes;

        // 呼叫 updateVoteInfo 方法
        $result = $formVotes->updateVoteInfo($voteID, $data, true);
        // 確認是否符合預期的錯誤
        $this->tester->assertErrors(17, $voteErrors);
        // 確認方法是否回傳 false，因為資料有錯誤，方法不應該成功
        $this->assertFalse($result);
    }

    /**
     * 使用正確資料建立分組
     * 
     * @dataProvider voteValidProvider
     */
    public function testCreateVotePartyWithValidData($voteID, $data)
    {
        // 建立 FormParties 物件
        $formParties = new FormParties;
        // 呼叫 updateParty 方法
        $result = $formParties->updateParty($voteID, $data, true);
        // 驗證方法是否成功執行
        $this->assertFalse(Yii::$app->session->hasFlash('error'));
        // 確認方法是否回傳 True，因為資料正確，方法應該成功
        $this->assertTrue($result);
        // 確認資料寫入資料庫
        $parties = FormParties::find()->where(['voteID' => $voteID])->all();
        $this->assertCount(4, $parties);
        
        foreach ($data['FormParties'] as $party) {
            $this->tester->seeRecord(FormParties::class, ['voteID' => $voteID, 'name' => $party['name']]);
        }
    }

    /**
     * 使用錯誤資料建立分組
     * 
     * @dataProvider voteInvalidProvider
     */
    public function testCreateVotePartyWithInvalidData($voteID, $data, $voteErrors, $partyErrors)
    {
        // 建立 FormParties 物件
        $FormParties = new FormParties;

        // 呼叫 updateParty 方法
        $result = $FormParties->updateParty($voteID, $data, true);
        // 確認是否符合預期的錯誤
        $this->tester->assertErrors(6, $partyErrors);
        // 確認方法是否回傳 false，因為資料有錯誤，方法不應該成功
        $this->assertFalse($result);
    }

    /**
     * 建立輪次
     *
     * @depends testCreateAnonVoteWithValidData
     * @dataProvider voteValidProvider
     */
    public function testCreateVoteRound($voteID, $data)
    {
        // 投票已經在 testCreateAnonVoteWithValidData 中建立，這裡測試建立新輪次
        // 先清理可能存在的 round 記錄（避免與 fixture 衝突）
        Round::deleteAll(['voteID' => $voteID]);

        $result = (new Round())->createRound($voteID, 1, '第1次投票');
        // 驗證方法是否成功執行
        $this->assertNotContains('error', Yii::$app->session->getAllFlashes());
        // 確認方法是否回傳 True，因為資料正確，方法應該成功
        $this->assertTrue($result);
        // 確認資料寫入資料庫
        $rounds = Round::find()->where(['voteID' => $voteID])->count();
        $this->assertEquals(1, $rounds);
        $this->tester->seeRecord(Round::class, ['voteID' => $voteID, 'round' => 1]);
    }

    /**
     * 刪除投票
     */
    public function testDeleteVote()
    {
        // load fixture
        $this->tester->haveFixtures([
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
        ]);
        $model = new FormManageVote(UnitTester::ANON_PARTY_VOTEID);
        $result = $model->deleteVote();
        // 驗證方法是否成功執行
        $this->assertEquals(1, $result['voteInfo']);
        $this->assertEquals(1, $result['party']);
        // 確認被刪除的資料是否存在，不應該存在
        $this->tester->dontSeeRecord(Votes::class, ['voteID' => UnitTester::ANON_PARTY_VOTEID]);
    }
    
    /**
     * 正確資料
     * 
     * @return array
     */
    public function voteValidProvider()
    {
        $voteID = UnitTester::ANON_PARTY_VOTEID;
        
        $openStart = date('Y-m-d H:i:s');

        return [
            [
                'voteID' => $voteID,
                'data' => [
                    'FormVotes' => [
                        'voteID' => $voteID,
                        'Name' => '測試匿名分組投票',
                        'NameE' => 'Test Anon Vote',
                        'contact' => '測試員',
                        'contactE' => 'Tester',
                        'openStart' => $openStart,
                        'openEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+30 minute')),
                        'verifyStart' => date('Y-m-d H:i:s', strtotime($openStart . '+31 minute')),
                        'verifyEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+32 minute')),
                        'type' => '1', // 匿名
                        'partyOrNot' => '1', // 分組
                        'addiCondition' => 'n', 
                        'hosted' => '資訊服務處',
                        'hostedE' => 'Department of Information Technology Services',
                        'tel' => '+886-2-2789-8855',
                        'email' => 'vote-admin@example.com',
                        'isByParty' => '0',
                        'isBindVote' => '0',
                        'bindWhichVote' => '',
                        'active' => '0',
                        'isFinish' => '0',
                        'finishPage' => '1',
                        'pattern' => 'meeting',
                        'loginLayout' => 'meeting',
                        'themeColor' => '',
                        'session' => '',
                        'authBeforeDetail' => '1',
                        'skipDetail' => '1',
                        'skipCheck' => '1',
                        'isShow' => '1',
                        'round' => 1,
                        'candiConfig' => '1'
                    ],
                    'FormParties' => [
                        0 => [
                            'name' => '1',
                            'nameE' => '1',
                            'numBallots' => '1',
                            'leastNumBallots' => '1',
                            'maxElect' => '1',
                            'numOfKeep' => '1',
                            'numFemaleKeep' => NULL,
                        ],
                        1 => [
                            'name' => '2',
                            'nameE' => '2',
                            'numBallots' => '1',
                            'leastNumBallots' => '1',
                            'maxElect' => '1',
                            'numOfKeep' => '1',
                            'numFemaleKeep' => NULL,
                        ],
                        2 => [
                            'name' => '3',
                            'nameE' => '3',
                            'numBallots' => '1',
                            'leastNumBallots' => '1',
                            'maxElect' => '1',
                            'numOfKeep' => '1',
                            'numFemaleKeep' => NULL,
                        ],
                        3 => [
                            'name' => '4',
                            'nameE' => '4',
                            'numBallots' => '1',
                            'leastNumBallots' => '1',
                            'maxElect' => '1',
                            'numOfKeep' => '1',
                            'numFemaleKeep' => NULL,
                        ],
                    ]
                ],
                
            ]
        ];
    }

    /**
     * 錯誤資料
     * 
     * @return array
     */
    public function voteInvalidProvider()
    {
        $voteID = UnitTester::ANON_PARTY_VOTEID.'Invalid';
        
        return [
            [
                'voteID' => $voteID,
                'data' => [
                    'FormVotes' => [
                        'Name' => '',
                        'openStart' => '',
                        'openEnd' => '',
                        'verifyStart' => '',
                        'verifyEnd' => '',
                        'hosted' => '',
                    ],
                    'FormParties' => [
                        0 => [
                            'name' => '',
                            'nameE' => '',
                            'numBallots' => '',
                            'leastNumBallots' => '',
                            'maxElect' => '',
                            'numOfKeep' => '',
                            'numFemaleKeep' => NULL,
                        ],
                    ]
                ],
                'voteErrors' => [
                    '投票名稱 不能為空白。',
                    '主辦單位 不能為空白。',
                    '投票時間(起) 不能為空白。',
                    '投票時間(迄) 不能為空白。',
                    '驗證時間(起) 不能為空白。',
                    '驗證時間(迄) 不能為空白。',
                    '投票類型 不能為空白。',
                    '是否分組 不能為空白。',
                    '保留名額 不能為空白。',
                    '投票結果顯示 不能為空白。',
                    '是否共用投票密碼 不能為空白。',
                    '投票狀態 不能為空白。',
                    '查看投票資訊是否驗證 不能為空白。',
                    '是否略過投票資訊頁面 不能為空白。',
                    '是否於首頁顯示 不能為空白。',
                    '候選名單配置 不能為空白。',
                    '是否略過圈選結果頁 不能為空白。'
                ],
                'partyErrors' => [
                    '最多可投票數 不能為空白。',
                    '最少應投票數 不能為空白。',
                    '遞補人數 不能為空白。',
                    '當選人數 不能為空白。',
                    '分組中文名稱 不能為空白。',
                    '分組英文名稱 不能為空白。',
                ]
            ]
        ];
    }
}