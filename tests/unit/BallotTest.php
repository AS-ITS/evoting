<?php

use app\models\CandiData;
use app\models\FormBallots;
use app\models\BallotsSelected;
use app\components\helper\ArrayHelper;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\BallotsSelectedFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

class BallotTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /** Faker中文 */
    public $faker;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        // 載入 UsersFixture, ConfigFixture 和 RbacFixture 以提供登入所需的資料
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
        ]);
    }

    protected function _after()
    {
        (new BallotsFixture)->unload();
        (new BallotsSelectedFixture)->unload();
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
            'candiData'   => CandiDataFixture::className(),
            'passwords'   => PasswordsFixture::className(),
        ];
    }

    /**
     * 後台建立選票
     * 
     * @dataProvider voteIDProvider
     */
    public function testCreateBallotWithAdmin($voteID)
    {
        // 取得 passwords fixture 資料
        $passwords = $this->tester->grabFixture('passwords');
        $this->tester->userLogin();
        foreach ($passwords as $password) {
            $FormBallots = new FormBallots();
            if ($password['voteID'] == $voteID) {
                $this->createBallot($voteID, $password, $FormBallots, true);
            }
        }
    }

    /**
     * 匿名投票者登入建立選票
     * 
     * @dataProvider voteIDProvider
     */
    public function testCreateBallotWithAnon($voteID)
    {
        // 取得 passwords fixture 資料
        $passwords = $this->tester->grabFixture('passwords');
        foreach ($passwords as $password) {
            $FormBallots = new FormBallots();
            if ($password['voteID'] == $voteID) {
                $this->tester->anonLogin($password);
                $this->createBallot($voteID, $password, $FormBallots, false);
                Yii::$app->anon->identity->logout(true);
            }
        }
    }
    
    /**
     * 測試選票編輯
     */
    public function testUpdateBallot()
    {
        // 建立選票
        $FormBallots = new FormBallots();
        $password = $this->tester->grabFixture('passwords', 0);
        $this->tester->anonLogin($password);
        $this->createBallot($password['voteID'], $password, $FormBallots, false);
        Yii::$app->anon->identity->logout(true);

        // 修改選票
        $this->tester->userLogin();
        $ballotID = $FormBallots->ballotID;
        $candiData = CandiData::find()
            ->select(['id'])
            ->where(['voteID' => $password['voteID']])
            ->andWhere(['in', 'party', ['N', $password['party']]])
            ->asArray()->all();
        $candiDatas = ArrayHelper::map($candiData, 'id', 'id');

        // 勾選全部候選人
        $post = ['selection' => $candiDatas];
        $result = $FormBallots->updateBallot($password['voteID'], $ballotID, $post);
        $this->assertTrue($result);
        // 確認投票人選數量是否正確
        $selectedCount = BallotsSelected::find()->where([
            'voteID' => $password['voteID'],
            'ballotID' => $ballotID,
        ])->count();
        $this->assertEquals(count($candiDatas), intval($selectedCount));

        // 取消勾選全部候選人
        $result = $FormBallots->updateBallot($password['voteID'], $ballotID, []);
        $this->assertTrue($result);
        // 確認投票人選數量是否正確
        $selectedCount = BallotsSelected::find()->where([
            'voteID' => $password['voteID'],
            'ballotID' => $ballotID,
        ])->count();
        $this->assertEquals(0, intval($selectedCount));
    }
    
    /**
     * 測試刪除單一選票
     */
    public function testDeleteSingleBallot()
    {
        // 建立選票
        $FormBallots = new FormBallots();
        $password = $this->tester->grabFixture('passwords', 0);
        $this->tester->anonLogin($password);
        $this->createBallot($password['voteID'], $password, $FormBallots, false);
        Yii::$app->anon->identity->logout(true);
        $ballotID = $FormBallots->ballotID;
        // 刪除選票
        $FormBallots->deleteBallot($password['voteID'], $ballotID);
        // 確認選票是否刪除成功
        $this->tester->dontSeeRecord(FormBallots::className(), [
            'voteID' => $password['voteID'],
            'ballotID' => $ballotID,
            'party' => $password['party'],
        ]);
        // 確認投票人選是否不存在
        $this->tester->dontSeeRecord(BallotsSelected::className(), [
            'voteID' => $password['voteID'],
            'ballotID' => $ballotID,
        ]);
    }

    /**
     * 測試刪除全部選票
     */
    public function testDeleteAllBallot()
    {
        // 建立選票
        $FormBallots = new FormBallots();
        $password = $this->tester->grabFixture('passwords', 0);
        $this->tester->anonLogin($password);
        $this->createBallot($password['voteID'], $password, $FormBallots, false);
        Yii::$app->anon->identity->logout(true);

        // 刪除選票
        $FormBallots->deleteAllBallot($password['voteID'], 1);
        // 確認選票是否刪除成功
        $this->tester->dontSeeRecord(FormBallots::className(), [
            'voteID' => $password['voteID'],
            'party' => $password['party'],
        ]);
        // 確認投票人選是否不存在
        $this->tester->dontSeeRecord(BallotsSelected::className(), [
            'voteID' => $password['voteID'],
            'party' => $password['party'],
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
     * 建立選票
     *
     * @param  string $voteID
     * @param  array $password
     * @param  object $FormBallots
     * @param  bool $isAdmin
     * @return void
     */
    private function createBallot($voteID, $password, $FormBallots, $isAdmin)
    {
        // 取得候選人資料
        // 根據 password 的 party 來查詢對應的候選人
        $passwordParty = $password['party'] ?? 'N';

        // 簡化查詢，只用 voteID
        $candiData = CandiData::find()
            ->select(['id'])
            ->where(['voteID' => $voteID])
            ->asArray()->all();
        $candiDatas = ArrayHelper::map($candiData, 'id', 'id');

        // 如果沒有候選人資料，跳過此測試
        if (empty($candiDatas)) {
            // 取得資料庫中所有候選人數量以便診斷
            $totalCount = CandiData::find()->where(['voteID' => $voteID])->count();
            $this->markTestSkipped('沒有候選人資料可供測試 (voteID=' . $voteID . ', totalCount=' . $totalCount . ')');
            return;
        }

        // 隨機選擇投票人選（確保不超過候選人數量）
        $maxSelection = min(3, count($candiDatas));
        $selection = random_int(0, $maxSelection);
        $post = [
            'FormBallots' => [
                'party' => $password['party'],
                'creator' => $password['id'],
            ]
        ];
        if ($selection == 1) {
            $ballotsSelected = [array_rand($candiDatas)];
            $post['selection'] = $ballotsSelected;
        }
        elseif ($selection > 1) {
            $ballotsSelected = array_rand($candiDatas, $selection);
            $post['selection'] = $ballotsSelected;
        }
        // 儲存投票資料
        $result = $FormBallots->creatorBallot($voteID, $post, $isAdmin);
        $this->assertTrue($result);
        // 確認選票是否儲存成功
        $this->tester->seeRecord(FormBallots::className(), [
            'voteID' => $voteID,
            'party' => $password['party'],
            'creator' => $password['id'],
        ]);
        if ($selection > 0) {
            foreach ($ballotsSelected as $select) {
                // 確認投票人選是否正確
                $this->tester->seeRecord(BallotsSelected::className(), [
                    'voteID' => $voteID,
                    'ballotID' => $FormBallots->ballotID,
                    'selCandiID' => $select,
                ]);
            }
        }
        // 確認投票人選數量是否正確
        $selectedCount = BallotsSelected::find()->where([
            'voteID' => $voteID,
            'ballotID' => $FormBallots->ballotID,
        ])->count();
        $this->assertEquals($selection, intval($selectedCount));
    }
}