<?php

namespace app\tests\unit\models;

use Yii;
use app\components\AdminIdentity;
use app\models\Ballots;
use app\models\BallotsSelected;
use app\models\FormBallots;
use app\models\FormBallotsSelected;
use app\models\FormManageCount;
use app\models\FormManageVote;
use app\models\FormResults;
use app\models\Results;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\UsersFixture;

/**
 * 選票送出整合測試：表決投票（多人投票）、圈選候選人範圍檢查、批次寫入原子性
 *
 * 使用自建的獨立場次（UtNoAuth、UtParty），不依賴也不影響共用 fixture 的投票資料。
 */
class FormBallotsSubmitTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    const NO_AUTH = 'UtNoAuth';
    const PARTY = 'UtParty';

    /** @var array<string, int> 題目代號 => questionID */
    private array $q = [];

    /** @var array<string, int> 候選人代號 => candiData.id */
    private array $c = [];

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
        ]);
        $this->cleanup();
        $this->seed();
    }

    protected function _after()
    {
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }
        $this->cleanup();
    }

    // ==================== #1 表決投票：多人投票 ====================

    public function testNoAuthAcceptsMultipleVoters()
    {
        $model = null;
        for ($i = 0; $i < 3; $i++) {
            $model = new FormBallots;
            $this->assertTrue(
                $model->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a1']]]),
                '第 ' . ($i + 1) . ' 位投票者應能送出選票：' . json_encode($model->errors, JSON_UNESCAPED_UNICODE)
            );
        }

        $ballots = Ballots::find()->where(['voteID' => self::NO_AUTH])->asArray()->all();
        $this->assertCount(3, $ballots);
        $creators = array_column($ballots, 'creator');
        $this->assertCount(3, array_unique($creators), '每張表決選票的 creator 應不重複');
        foreach ($ballots as $ballot) {
            $this->assertNotSame('', $ballot['creator']);
            $this->assertLessThanOrEqual(50, strlen($ballot['creator']));
            $this->assertSame('def', $ballot['party']);
            $this->assertSame('0', $ballot['isAdminAdd']);
            $this->assertSame('1', (string) $ballot['round']);
        }
        $this->assertSame(3, (int) BallotsSelected::find()->where(['voteID' => self::NO_AUTH])->count());
    }

    public function testNoAuthStoresSelections()
    {
        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a1'], $this->c['a2']]]));

        $rows = BallotsSelected::find()->where(['voteID' => self::NO_AUTH, 'ballotID' => $model->ballotID])->asArray()->all();
        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing([$this->c['a1'], $this->c['a2']], array_map('intval', array_column($rows, 'selCandiID')));
        foreach ($rows as $row) {
            $this->assertSame($this->q['A'], (int) $row['questionID']);
            $this->assertSame('1', $row['isValiable']);
        }
    }

    public function testNoAuthOverLimitIsStoredAsInvalid()
    {
        // 題目 A 可投 2 票，圈 3 人 → 寫入但為廢票（現行行為）
        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a1'], $this->c['a2'], $this->c['a3']]]));

        $valid = BallotsSelected::find()->select('isValiable')->where(['voteID' => self::NO_AUTH, 'ballotID' => $model->ballotID])->column();
        $this->assertSame(['0', '0', '0'], $valid);
    }

    public function testNoAuthEmptySelectionCreatesBlankBallot()
    {
        // 未圈選任何候選人送出：寫入一張空白選票（現行行為）
        $first = new FormBallots;
        $this->assertTrue($first->creatorBallot(self::NO_AUTH, []));
        $second = new FormBallots;
        $this->assertTrue($second->creatorBallot(self::NO_AUTH, []), '第 2 張空白票也應能送出');

        $this->assertSame(2, (int) Ballots::find()->where(['voteID' => self::NO_AUTH])->count());
        $this->assertSame(0, (int) BallotsSelected::find()->where(['voteID' => self::NO_AUTH])->count());
    }

    public function testNoAuthIgnoresTamperedPartyFlag()
    {
        // 表決投票不支援分組；即使資料被改成 partyOrNot=1，也不可因 $loginData 未定義而失敗
        Yii::$app->db->createCommand()->update('votes', ['partyOrNot' => '1'], ['voteID' => self::NO_AUTH])->execute();

        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a1']]]));
        $this->assertSame('def', Ballots::find()->select('party')->where(['voteID' => self::NO_AUTH])->scalar());
    }

    public function testNoAuthVoterCanAlwaysVoteAgain()
    {
        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a1']]]));

        $manage = new FormManageVote(self::NO_AUTH);
        $this->assertFalse($manage->isVoteBallot(), '表決投票不以投票者識別是否已投票');
    }

    public function testNoAuthDeleteBallotAndDeleteAll()
    {
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $model = new FormBallots;
            $this->assertTrue($model->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a1']]]));
            $ids[] = $model->ballotID;
        }

        $this->assertNotEmpty((new FormBallots)->deleteBallot(self::NO_AUTH, $ids[0]));
        $this->assertSame(2, (int) Ballots::find()->where(['voteID' => self::NO_AUTH])->count());
        $this->assertSame(0, (int) BallotsSelected::find()->where(['voteID' => self::NO_AUTH, 'ballotID' => $ids[0]])->count());

        $this->assertTrue((new FormBallots)->deleteAllBallot(self::NO_AUTH, 1));
        $this->assertSame(0, (int) Ballots::find()->where(['voteID' => self::NO_AUTH])->count());
        $this->assertSame(0, (int) BallotsSelected::find()->where(['voteID' => self::NO_AUTH])->count());
    }

    public function testNoAuthCountAndResults()
    {
        $votes = [
            [$this->c['a1']],
            [$this->c['a1'], $this->c['a2']],
            [$this->c['a1'], $this->c['a2'], $this->c['a3']], // 超過可投票數 → 廢票
        ];
        foreach ($votes as $selection) {
            $model = new FormBallots;
            $this->assertTrue($model->creatorBallot(self::NO_AUTH, ['selection' => $selection]));
        }

        $sort = (new FormManageCount(self::NO_AUTH))->getBallotCountSort(true);
        $count = array_column($sort['def'][$this->q['A']]['ranking'], 'count', 'candi');
        $this->assertSame(2, (int) $count[$this->c['a1']], '廢票不計入得票');
        $this->assertSame(1, (int) $count[$this->c['a2']]);
        $this->assertSame(0, (int) ($count[$this->c['a3']] ?? 0));

        $this->assertTrue((new FormResults)->createResults(self::NO_AUTH));
        $result = Results::find()->where(['voteID' => self::NO_AUTH, 'candID' => $this->c['a1']])->one();
        $this->assertNotNull($result);
        $this->assertSame(2, (int) $result->ballotCounts);
        $this->assertSame(1, (int) $result->rank);
    }

    // ==================== #2 圈選候選人範圍檢查 ====================

    public function testRejectsCandidateFromAnotherVote()
    {
        $model = new FormBallots;
        $this->assertFalse($model->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['p0']]]));
        $this->assertNoBallots(self::NO_AUTH);
    }

    public function testRejectsUnknownCandidateId()
    {
        $model = new FormBallots;
        $this->assertFalse($model->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a1'], 999999999]]));
        $this->assertNoBallots(self::NO_AUTH);
    }

    public function testRejectsCandidateFromAnotherRound()
    {
        $model = new FormBallots;
        $this->assertFalse($model->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a1'], $this->c['b1']]]));
        $this->assertNoBallots(self::NO_AUTH);
    }

    public function testAdminRejectsCandidateFromAnotherParty()
    {
        $model = new FormBallots;
        $this->assertFalse($model->creatorBallot(self::PARTY, $this->adminPost('0', '900001', [$this->c['p1']]), true));
        $this->assertNoBallots(self::PARTY);
    }

    public function testAdminAcceptsOwnPartyAndCommonCandidates()
    {
        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::PARTY, $this->adminPost('0', '900001', [$this->c['p0'], $this->c['pn']]), true));
        $this->assertEqualsCanonicalizing(
            [$this->c['p0'], $this->c['pn']],
            array_map('intval', BallotsSelected::find()->select('selCandiID')->where(['voteID' => self::PARTY, 'ballotID' => $model->ballotID])->column())
        );
    }

    public function testUpdateBallotRejectsForeignCandidateAndKeepsSelections()
    {
        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::PARTY, $this->adminPost('0', '900001', [$this->c['p0']]), true));
        $this->loginAdmin();

        $this->assertEmpty((new FormBallots)->updateBallot(self::PARTY, $model->ballotID, ['selection' => [$this->c['p1']]]));
        $this->assertSame(
            [$this->c['p0']],
            array_map('intval', BallotsSelected::find()->select('selCandiID')->where(['voteID' => self::PARTY, 'ballotID' => $model->ballotID])->column()),
            '修改失敗時應保留原本的圈選'
        );
    }

    public function testUpdateBallotAcceptsValidSelection()
    {
        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::PARTY, $this->adminPost('0', '900001', [$this->c['p0']]), true));
        $this->loginAdmin();

        $this->assertTrue((new FormBallots)->updateBallot(self::PARTY, $model->ballotID, ['selection' => [$this->c['pn']]]));
        $this->assertSame(
            [$this->c['pn']],
            array_map('intval', BallotsSelected::find()->select('selCandiID')->where(['voteID' => self::PARTY, 'ballotID' => $model->ballotID])->column())
        );
        $this->assertSame('test_admin', Ballots::find()->select('modifier')->where(['voteID' => self::PARTY, 'ballotID' => $model->ballotID])->scalar());
    }

    // ==================== #3 creator 長度 ====================

    public function testAdminCreatorLongerThanEightCharsIsAccepted()
    {
        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::PARTY, $this->adminPost('0', '123456789', [$this->c['p0']]), true));
    }

    // ==================== #4 批次寫入原子性 ====================

    public function testCreateBallotSelectedIsAtomic()
    {
        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::PARTY, $this->adminPost('0', '900001', []), true));

        $rows = [
            ['id' => $this->c['p0'], 'party' => '0', 'questionID' => $this->q['P0'], 'jobLctn' => '1'],
            ['id' => $this->c['pn'], 'party' => 'N', 'questionID' => $this->q['N'], 'jobLctn' => 'XX'], // 驗證失敗
        ];
        $this->assertFalse((new FormBallotsSelected)->createBallotSelected(self::PARTY, $model->ballotID, $rows));
        $this->assertSame(0, (int) BallotsSelected::find()->where(['voteID' => self::PARTY])->count(), '任一筆失敗應全部回滾');
    }

    public function testSaveRankingsIsAtomic()
    {
        $row = function ($candi, $elect) {
            return ['candi' => $candi, 'count' => 1, 'elect' => $elect, 'rank' => 1, 'femaleKeep' => false];
        };
        $sort = ['0' => [$this->q['P0'] => ['ranking' => [
            $row($this->c['p0'], 'E'),
            $row($this->c['pn'], 'TOO_LONG'), // elected 最長 2 字元 → 驗證失敗
        ]]]];

        $this->assertFalse((new FormResults)->saveRankings(self::PARTY, $sort));
        $this->assertSame(0, (int) Results::find()->where(['voteID' => self::PARTY])->count(), '任一筆失敗應全部回滾');
    }

    // ==================== 輔助方法 ====================

    private function adminPost(string $party, string $creator, array $selection): array
    {
        $post = ['FormBallots' => ['party' => $party, 'creator' => $creator]];
        if ($selection !== []) {
            $post['selection'] = $selection;
        }
        return $post;
    }

    private function assertNoBallots(string $voteID): void
    {
        $this->assertSame(0, (int) Ballots::find()->where(['voteID' => $voteID])->count(), '不可寫入選票');
        $this->assertSame(0, (int) BallotsSelected::find()->where(['voteID' => $voteID])->count(), '不可寫入圈選');
    }

    private function loginAdmin(): void
    {
        $identity = new AdminIdentity();
        $identity->setAuthData(['cn' => 'test_admin', 'name' => 'Test Admin', 'roles' => 'sa']);
        Yii::$app->user->switchIdentity($identity);
    }

    private function cleanup(): void
    {
        $db = Yii::$app->db;
        $where = ['voteID' => [self::NO_AUTH, self::PARTY]];
        foreach (['results', 'ballotsSelected', 'ballots', 'passwords', 'candiData', 'questions', 'round', 'parties', 'votes'] as $table) {
            $db->createCommand()->delete($table, $where)->execute();
        }
    }

    /**
     * 建立測試場次：
     * - UtNoAuth：表決、不分組；第 1 輪題目 A（可投 2、至少 1）候選人 a1~a3；第 2 輪題目 B 候選人 b1
     * - UtParty：匿名、分組 0/1；第 1 輪題目 P0（組 0）、P1（組 1）、N（共同）各一位候選人；
     *   組 0 密碼 id 900001、123456789（供後台代投）
     */
    private function seed(): void
    {
        $voteRows = require codecept_root_dir() . 'tests/fixtures/data/votes.php';
        $base = $voteRows['AnonPartyTest'];
        $this->insert('votes', array_merge($base, [
            'voteID' => self::NO_AUTH, 'Name' => '單元測試表決', 'shortUrl' => 'utnoauth',
            'type' => '0', 'partyOrNot' => '0', 'isBindVote' => '0', 'bindWhichVote' => '',
            'addiCondition' => 'n', 'session' => '', 'authBeforeDetail' => '0', 'round' => '1',
        ]));
        $this->insert('votes', array_merge($base, [
            'voteID' => self::PARTY, 'Name' => '單元測試分組', 'shortUrl' => 'utparty',
            'type' => '1', 'partyOrNot' => '1', 'isBindVote' => '0', 'bindWhichVote' => '', 'round' => '1',
        ]));

        foreach ([[self::NO_AUTH, 'def'], [self::PARTY, '0'], [self::PARTY, '1']] as [$voteID, $party]) {
            $this->insert('parties', [
                'voteID' => $voteID, 'party' => $party, 'name' => "組$party", 'nameE' => "Party $party",
                'numBallots' => 2, 'leastNumBallots' => 1, 'maxElect' => 1, 'numOfKeep' => 0,
            ]);
        }
        foreach ([[self::NO_AUTH, 1], [self::NO_AUTH, 2], [self::PARTY, 1]] as [$voteID, $round]) {
            $this->insert('round', ['voteID' => $voteID, 'round' => $round, 'name' => "第{$round}輪", 'nameE' => "Round $round"]);
        }

        // 後台代投的 creator 為匿名密碼 id（setVote 會更新該密碼的投票狀態）
        foreach (['900001', '123456789'] as $sn => $id) {
            $this->insert('passwords', [
                'id' => $id, 'voteID' => self::PARTY, 'party' => '0', 'sn' => $sn + 1,
                'passwd' => "ut-$id", 'status' => '1', 'voted' => '0',
            ]);
        }

        $this->q['A'] = $this->question(self::NO_AUTH, 1, 'def');
        $this->q['B'] = $this->question(self::NO_AUTH, 2, 'def');
        $this->q['P0'] = $this->question(self::PARTY, 1, '0');
        $this->q['P1'] = $this->question(self::PARTY, 1, '1');
        $this->q['N'] = $this->question(self::PARTY, 1, 'N');

        $this->c['a1'] = $this->candidate(self::NO_AUTH, 'def', $this->q['A'], 1);
        $this->c['a2'] = $this->candidate(self::NO_AUTH, 'def', $this->q['A'], 2);
        $this->c['a3'] = $this->candidate(self::NO_AUTH, 'def', $this->q['A'], 3);
        $this->c['b1'] = $this->candidate(self::NO_AUTH, 'def', $this->q['B'], 1);
        $this->c['p0'] = $this->candidate(self::PARTY, '0', $this->q['P0'], 1);
        $this->c['p1'] = $this->candidate(self::PARTY, '1', $this->q['P1'], 1);
        $this->c['pn'] = $this->candidate(self::PARTY, 'N', $this->q['N'], 1);
    }

    private function question(string $voteID, int $round, string $party): int
    {
        return $this->insert('questions', [
            'voteID' => $voteID, 'round' => $round, 'party' => $party,
            'title' => "Q $party r$round", 'titleE' => "Q $party r$round",
            'numBallots' => 2, 'leastNumBallots' => 1, 'maxElect' => 1, 'numOfKeep' => 0,
        ]);
    }

    private function candidate(string $voteID, string $party, int $questionID, int $order): int
    {
        $rows = require codecept_root_dir() . 'tests/fixtures/data/candiData.php';
        return $this->insert('candiData', array_merge($rows['candiData730'], [
            'voteID' => $voteID, 'party' => $party, 'questionID' => $questionID,
            'Name' => "候選人{$questionID}-{$order}", 'NameE' => "Candidate {$questionID}-{$order}",
            'orderNum' => $order,
        ]));
    }

    private function insert(string $table, array $row): int
    {
        $db = Yii::$app->db;
        $db->createCommand()->insert($table, $row)->execute();
        return (int) $db->getLastInsertID();
    }
}
