<?php

use app\models\Ballots;
use app\models\BallotsSelected;
use app\models\CandiData;
use app\models\Questions;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\BallotsSelectedFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

/**
 * 測試 Ballots 和 BallotsSelected 模型
 *
 * 這補充了 BallotTest.php 中未涵蓋的基礎模型功能測試
 */
class BallotsModelsTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        // 載入必要的 fixtures
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
        ]);
        $this->tester->userLogin();
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
     * 測試 Ballots 模型基本結構
     */
    public function testBallotsModelStructure()
    {
        $ballot = new Ballots();
        $this->assertNotNull($ballot);
        $this->assertEquals('ballots', Ballots::tableName());
    }

    /**
     * 測試 Ballots 必填欄位驗證
     */
    public function testBallotsRequiredFields()
    {
        $ballot = new Ballots();
        $this->assertFalse($ballot->validate());

        $errors = $ballot->errors;
        $this->assertArrayHasKey('voteID', $errors);
        $this->assertArrayHasKey('round', $errors);
        $this->assertArrayHasKey('party', $errors);
        $this->assertArrayHasKey('ballotID', $errors);
    }

    /**
     * 測試 Ballots 唯一性約束
     */
    public function testBallotsUniquenessConstraint()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        // 創建第一個選票
        $ballot1 = new Ballots();
        $ballot1->voteID = $vote->voteID;
        $ballot1->round = 1;
        $ballot1->party = '0';
        $ballot1->ballotID = 1;
        $ballot1->isAdminAdd = '0';
        $ballot1->ip = '127.0.0.1';
        $ballot1->creator = 'test';
        $ballot1->modifier = 'test';
        $ballot1->insTime = date('Y-m-d H:i:s');
        $ballot1->updTime = date('Y-m-d H:i:s');
        $this->assertTrue($ballot1->save());

        // 嘗試創建相同 voteID + party + ballotID 的選票
        $ballot2 = new Ballots();
        $ballot2->attributes = $ballot1->attributes;
        $this->assertFalse($ballot2->save());
        $this->assertArrayHasKey('voteID', $ballot2->errors);
    }

    /**
     * 測試 Ballots getBallotList 方法
     */
    public function testGetBallotList()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        // 創建測試選票 - 使用不同的 creator 避免唯一性約束錯誤
        $this->createTestBallot($vote->voteID, 1, '0', 1, 'user1');
        $this->createTestBallot($vote->voteID, 1, '0', 2, 'user2');
        $this->createTestBallot($vote->voteID, 1, '1', 3, 'user3');

        $ballot = new Ballots();
        $ballots = $ballot->getBallotList($vote->voteID, 1)->all();

        $this->assertIsArray($ballots);
        $this->assertCount(3, $ballots);
        foreach ($ballots as $b) {
            $this->assertEquals($vote->voteID, $b->voteID);
            $this->assertEquals(1, $b->round);
        }
    }

    /**
     * 測試 Ballots getVoteBallot 方法
     */
    public function testGetVoteBallot()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $this->createTestBallot($vote->voteID, 1, '0', 100);

        $ballot = new Ballots();
        $result = $ballot->getVoteBallot($vote->voteID, 100)->one();

        $this->assertNotNull($result);
        $this->assertInstanceOf(Ballots::class, $result);
        $this->assertEquals($vote->voteID, $result->voteID);
        $this->assertEquals(100, $result->ballotID);
    }

    /**
     * 測試 Ballots getBallotId 方法
     */
    public function testGetBallotId()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $creator = 'testuser123';
        $this->createTestBallot($vote->voteID, 1, '0', 200, $creator);

        $ballot = new Ballots();
        $result = $ballot->getBallotId($vote->voteID, 1, '0', $creator)->one();

        $this->assertNotNull($result);
        $this->assertEquals($creator, $result->creator);
        $this->assertEquals('0', $result->party);
    }

    /**
     * 測試 Ballots deleteAllBallot 方法
     */
    public function testDeleteAllBallot()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        // 創建測試選票和選擇 - 使用不同的 creator
        $ballot1 = $this->createTestBallot($vote->voteID, 1, '0', 301, 'deluser1');
        $ballot2 = $this->createTestBallot($vote->voteID, 1, '0', 302, 'deluser2');
        $ballot3 = $this->createTestBallot($vote->voteID, 1, '1', 303, 'deluser3');

        // 創建 BallotsSelected
        $candiIds = $this->candiIds($vote->voteID, 3);
        $this->createTestBallotsSelected($vote->voteID, 301, $candiIds[0]);
        $this->createTestBallotsSelected($vote->voteID, 302, $candiIds[1]);
        $this->createTestBallotsSelected($vote->voteID, 303, $candiIds[2]);

        // 刪除 party '0' 的所有選票
        $ballotModel = new Ballots();
        $result = $ballotModel->deleteAllBallot($vote->voteID, 1, '0');

        $this->assertTrue($result);

        // 驗證 party '0' 的選票已刪除
        $this->assertNull(Ballots::findOne(['voteID' => $vote->voteID, 'ballotID' => 301]));
        $this->assertNull(Ballots::findOne(['voteID' => $vote->voteID, 'ballotID' => 302]));

        // 驗證 party '1' 的選票仍存在
        $this->assertNotNull(Ballots::findOne(['voteID' => $vote->voteID, 'ballotID' => 303]));

        // 驗證相關的 BallotsSelected 也被刪除
        $this->assertNull(BallotsSelected::findOne(['voteID' => $vote->voteID, 'ballotID' => 301]));
        $this->assertNull(BallotsSelected::findOne(['voteID' => $vote->voteID, 'ballotID' => 302]));
    }

    /**
     * 測試 BallotsSelected 模型基本結構
     */
    public function testBallotsSelectedModelStructure()
    {
        $selected = new BallotsSelected();
        $this->assertNotNull($selected);
        $this->assertEquals('ballotsSelected', BallotsSelected::tableName());
    }

    /**
     * 測試 BallotsSelected 必填欄位驗證
     */
    public function testBallotsSelectedRequiredFields()
    {
        $selected = new BallotsSelected();
        $this->assertFalse($selected->validate());

        $errors = $selected->errors;
        $this->assertArrayHasKey('voteID', $errors);
        $this->assertArrayHasKey('ballotID', $errors);
    }

    /**
     * 測試 BallotsSelected 唯一性約束
     */
    public function testBallotsSelectedUniquenessConstraint()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        // 創建第一筆記錄
        $selected1 = new BallotsSelected();
        $selected1->voteID = $vote->voteID;
        $selected1->ballotID = 400;
        $selected1->selCandiID = $this->candiIds($vote->voteID, 1)[0];
        $selected1->party = '0';
        $selected1->questionID = 1;
        $selected1->isValiable = '1';
        $this->assertTrue($selected1->save());

        // 嘗試創建相同的記錄
        $selected2 = new BallotsSelected();
        $selected2->attributes = $selected1->attributes;
        $this->assertFalse($selected2->save());
        $this->assertArrayHasKey('voteID', $selected2->errors);
    }

    /**
     * 測試 BallotsSelected getVoteBallot 方法
     */
    public function testBallotsSelectedGetVoteBallot()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $candiIds = $this->candiIds($vote->voteID, 3);
        $this->createTestBallotsSelected($vote->voteID, 500, $candiIds[0]);
        $this->createTestBallotsSelected($vote->voteID, 500, $candiIds[1]);
        $this->createTestBallotsSelected($vote->voteID, 501, $candiIds[2]);

        $selected = new BallotsSelected();
        $results = $selected->getVoteBallot($vote->voteID, 500)->all();

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertEquals($vote->voteID, $result->voteID);
            $this->assertEquals(500, $result->ballotID);
        }
    }

    /**
     * 測試 BallotsSelected daleteBallot 方法
     */
    public function testBallotsSelectedDeleteBallot()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $candiIds = $this->candiIds($vote->voteID, 2);
        $this->createTestBallotsSelected($vote->voteID, 600, $candiIds[0]);
        $this->createTestBallotsSelected($vote->voteID, 600, $candiIds[1]);

        $selected = new BallotsSelected();
        $deleted = $selected->daleteBallot($vote->voteID, 600);

        $this->assertEquals(2, $deleted);

        // 驗證記錄已刪除
        $remaining = BallotsSelected::find()
            ->where(['voteID' => $vote->voteID, 'ballotID' => 600])
            ->count();
        $this->assertEquals(0, $remaining);
    }

    /**
     * 測試 BallotsSelected 和 CandiData 的關聯
     */
    public function testBallotsSelectedCandiDataRelation()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        // 從資料庫中取得候選人資料（因為 fixture 使用命名鍵）
        $candiData = CandiData::find()->where(['voteID' => $vote->voteID])->one();

        if (!$candiData) {
            $this->markTestSkipped('No candidate data available');
        }

        // 創建選擇記錄
        $selected = new BallotsSelected();
        $selected->voteID = $vote->voteID;
        $selected->ballotID = 700;
        $selected->selCandiID = $candiData->id;
        $selected->party = $candiData->party;
        $selected->questionID = 1;
        $selected->isValiable = '1';
        $selected->save();

        // 測試關聯
        $loaded = BallotsSelected::findOne([
            'voteID' => $vote->voteID,
            'ballotID' => 700
        ]);

        $this->assertNotNull($loaded->candiData);
        $this->assertInstanceOf(CandiData::class, $loaded->candiData);
        $this->assertEquals($candiData->id, $loaded->candiData->id);
    }

    /**
     * 測試 BallotsSelected 和 Ballots 的關聯
     */
    public function testBallotsSelectedBallotRelation()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        // 創建選票
        $ballot = $this->createTestBallot($vote->voteID, 1, '0', 800);

        // 創建選擇
        $this->createTestBallotsSelected($vote->voteID, 800, $this->candiIds($vote->voteID, 1)[0]);

        // 測試關聯
        $selected = BallotsSelected::findOne([
            'voteID' => $vote->voteID,
            'ballotID' => 800
        ]);

        $this->assertNotNull($selected->ballot);
        $this->assertInstanceOf(Ballots::class, $selected->ballot);
        $this->assertEquals(800, $selected->ballot->ballotID);
    }

    /**
     * 測試 BallotsSelected 和 Questions 的關聯
     */
    public function testBallotsSelectedQuestionRelation()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $questions = Questions::find()->where(['voteID' => $vote->voteID])->all();

        if (empty($questions)) {
            $this->markTestSkipped('No questions available');
        }

        $question = $questions[0];

        // 創建選擇
        $selected = new BallotsSelected();
        $selected->voteID = $vote->voteID;
        $selected->ballotID = 900;
        $selected->selCandiID = $this->candiIds($vote->voteID, 1)[0];
        $selected->party = '0';
        $selected->questionID = $question->questionID;
        $selected->isValiable = '1';
        $selected->save();

        // 測試關聯
        $loaded = BallotsSelected::findOne([
            'voteID' => $vote->voteID,
            'ballotID' => 900
        ]);

        $this->assertNotNull($loaded->question);
        $this->assertInstanceOf(Questions::class, $loaded->question);
        $this->assertEquals($question->questionID, $loaded->question->questionID);
    }

    /**
     * 創建測試選票
     */
    private function createTestBallot($voteID, $round, $party, $ballotID, $creator = 'test')
    {
        $ballot = new Ballots();
        $ballot->voteID = $voteID;
        $ballot->round = $round;
        $ballot->party = $party;
        $ballot->ballotID = $ballotID;
        $ballot->isAdminAdd = '0';
        $ballot->ip = '127.0.0.1';
        $ballot->creator = $creator;
        $ballot->modifier = $creator;
        $ballot->insTime = date('Y-m-d H:i:s');
        $ballot->updTime = date('Y-m-d H:i:s');
        $ballot->save();
        return $ballot;
    }

    /**
     * 創建測試選票選擇
     */
    private function createTestBallotsSelected($voteID, $ballotID, $selCandiID, $party = '0', $questionID = 1)
    {
        $selected = new BallotsSelected();
        $selected->voteID = $voteID;
        $selected->ballotID = $ballotID;
        $selected->selCandiID = $selCandiID;
        $selected->party = $party;
        $selected->questionID = $questionID;
        $selected->isValiable = '1';
        if (!$selected->save()) {
            throw new \RuntimeException('Failed to save BallotsSelected: ' . json_encode($selected->errors));
        }
        return $selected;
    }

    /**
     * @return int[]
     */
    private function candiIds(string $voteID, int $n): array
    {
        $ids = CandiData::find()
            ->select('id')
            ->where(['voteID' => $voteID])
            ->orderBy(['id' => SORT_ASC])
            ->limit($n)
            ->column();
        if (count($ids) < $n) {
            throw new \RuntimeException("Need {$n} candiData rows for {$voteID}");
        }
        return array_map('intval', $ids);
    }
}
