<?php

use app\models\Round;
use app\models\Votes;
use app\models\FormVotes;
use app\models\Questions;
use app\models\FormParties;
use app\models\FormManageVote;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

class AnonNoPartyVoteTest extends \Codeception\Test\Unit
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
     * 建立投票
     *
     * @dataProvider voteProvider
     */
    public function testCreateAnonVote($voteID, $data)
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
        // 測試此場次為匿名不分組投票
        $this->assertEquals($formVotes->type, FormVotes::TYPE_ANON);
        $this->assertEquals('0', $formVotes->partyOrNot);
        // 測試預設問題一同建立
        $questions = Questions::find()->where(['voteID' => $voteID])->count();
        $this->assertEquals(1, $questions);
        // 確認新增的資料是否存在，應該存在
        $this->tester->seeRecord($formVotes::className(), $data['FormVotes']);
    }

    /**
     * 建立分組
     * 
     * @dataProvider voteProvider
     */
    public function testCreateVoteParty($voteID, $data)
    {
        // 本測試獨立執行時須先建立投票場次
        Questions::deleteAll(['voteID' => $voteID]);
        FormParties::deleteAll(['voteID' => $voteID]);
        Votes::deleteAll(['voteID' => $voteID]);

        $formVotes = new FormVotes;
        $this->assertTrue($formVotes->updateVoteInfo($voteID, $data, true));

        // 建立 FormParties 物件
        $FormParties = new FormParties;

        // 呼叫 updateParty 方法
        $result = $FormParties->updateParty($voteID, $data, true);
        // 驗證方法是否成功執行
        $this->assertFalse(Yii::$app->session->hasFlash('error'));
        $parties = FormParties::find()->where(['voteID' => $voteID]);
        $this->assertEquals(1, $parties->count());
        $party = $parties->one();
        $this->assertEquals(FormParties::DEF_PARTY, $party->party);
        $this->assertEquals('預設', $party->name);
        $this->assertEquals('Default', $party->nameE);
        // 確認方法是否回傳 True，因為資料正確，方法應該成功
        $this->assertTrue($result);
    }

    /**
     * 建立輪次
     *
     * @depends testCreateAnonVote
     * @dataProvider voteProvider
     */
    public function testCreateVoteRound($voteID, $data)
    {
        // 投票已經在 testCreateAnonVote 中建立，這裡測試建立新輪次
        // 先清理可能存在的 round 記錄（避免與 fixture 衝突）
        Round::deleteAll(['voteID' => $voteID]);

        $result = (new Round())->createRound($voteID, 1, '第1次投票');
        // 驗證方法是否成功執行
        $this->assertFalse(Yii::$app->session->hasFlash('error'));
        // 確認方法是否回傳 True，因為資料正確，方法應該成功
        $this->assertTrue($result);
        // 確認新增的資料是否存在，應該存在
        $rounds = Round::find()->where(['voteID' => $voteID])->count();
        $this->assertEquals(1, $rounds);
    }

    /**
     * 刪除投票
     * 
     * @depends testCreateAnonVote
     */
    public function testDeleteVote()
    {
        // load fixture
        $this->tester->haveFixtures([
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
        ]);
        $model = new FormManageVote(UnitTester::ANON_NO_PARTY_VOTEID);
        $result = $model->deleteVote();
        $this->assertEquals(1, $result['voteInfo']);
        $this->assertEquals(1, $result['party']);
        // 確認被刪除的資料是否存在，不應該存在
        $this->tester->dontSeeRecord(Votes::className(), ['voteID' => UnitTester::ANON_NO_PARTY_VOTEID]);
    }

    /**
     * @return array
     */
    public function voteProvider()
    {
        $voteID = UnitTester::ANON_NO_PARTY_VOTEID;
        $openStart = date('Y-m-d H:i:s');

        return [
            [
                'voteID' => $voteID,
                'data' => [
                    'FormVotes' => [
                        'voteID' => $voteID,
                        'Name' => '測試匿名不分組投票',
                        'NameE' => 'Test Not Anon Vote',
                        'contact' => '測試員',
                        'contactE' => 'Tester',
                        'openStart' => $openStart,
                        'openEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+10 minute')),
                        'verifyStart' => date('Y-m-d H:i:s', strtotime($openStart . '+11 minute')),
                        'verifyEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+12 minute')),
                        'type' => '1',
                        'partyOrNot' => '0',
                        'addiCondition' => 'n',
                        'hosted' => '資訊服務處',
                        'hostedE' => 'Department of Information Technology Services',
                        'tel' => '+886-2-2789-8855',
                        'email' => 'vote-admin@example.com',
                        'isByParty' => '0',
                        'isBindVote' => '0',
                        'bindWhichVote' => '',
                        'active' => '1',
                        'isFinish' => '0',
                        'finishPage' => '1',
                        'pattern' => '1',
                        'session' => '',
                        'authBeforeDetail' => '1',
                        'skipDetail' => '1',
                        'skipCheck' => '1',
                        'isShow' => '1',
                        'round' => 1,
                        'candiConfig' => '1'
                    ],
                    'FormParties' => [
                        'def' => [
                            'name' => '預設',
                            'nameE' => 'Default',
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
}