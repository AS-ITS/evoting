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
    const BIND = 'UtBind';        // 共用 UtParty 密碼的子場次
    const IMPORT = 'UtImport';    // 匯入選票的目標場次
    const COUNT_E = 'UtCountE';   // 開票：當選／遞補
    const COUNT_F = 'UtCountF';   // 開票：女性保留名額

    const ALL_VOTES = [self::NO_AUTH, self::PARTY, self::BIND, self::IMPORT, self::COUNT_E, self::COUNT_F];

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
        if (!Yii::$app->anon->isGuest) {
            Yii::$app->anon->logout(false);
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

    public function testNoAuthBallotChangerHidesRandomCreator()
    {
        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a1']]]));

        // 後台選票修改頁以此清單渲染「投票者／修改者」下拉選單，不可為 null
        $changer = (new FormManageVote(self::NO_AUTH))->getNewBallotChanger([$model->creator, 'test_admin']);
        $this->assertIsArray($changer);
        $this->assertSame('—', $changer[$model->creator]);
        $this->assertArrayHasKey('test_admin', $changer);
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

    // ==================== 投票者實際送出（匿名分組、共用密碼、第 2 輪） ====================

    public function testAnonPartyVoterSubmitsOwnPartyAndCommonCandidates()
    {
        $this->loginVoter(self::PARTY, '0', '900001');

        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::PARTY, ['selection' => [$this->c['p0'], $this->c['pn']]]));

        $ballot = Ballots::find()->where(['voteID' => self::PARTY])->one();
        $this->assertSame('0', $ballot->party);
        $this->assertSame('900001', $ballot->creator);
        $this->assertSame('0', $ballot->isAdminAdd);
        $this->assertEqualsCanonicalizing(
            [$this->c['p0'], $this->c['pn']],
            array_map('intval', BallotsSelected::find()->select('selCandiID')->where(['voteID' => self::PARTY])->column())
        );
        $this->assertSame('1', Yii::$app->db->createCommand("SELECT voted FROM passwords WHERE id = 900001")->queryScalar());
    }

    public function testAnonPartyVoterCannotSubmitOtherPartyCandidate()
    {
        $this->loginVoter(self::PARTY, '0', '123456789');

        $model = new FormBallots;
        $this->assertFalse($model->creatorBallot(self::PARTY, ['selection' => [$this->c['p0'], $this->c['p1']]]));
        $this->assertNoBallots(self::PARTY);
        $this->assertSame('0', Yii::$app->db->createCommand("SELECT voted FROM passwords WHERE id = 123456789")->queryScalar());
    }

    public function testAnonVoterOfAnotherVoteIsRejected()
    {
        $this->loginVoter(self::NO_AUTH, '0', '900001');

        $model = new FormBallots;
        $this->assertFalse($model->creatorBallot(self::PARTY, ['selection' => [$this->c['p0']]]));
        $this->assertNoBallots(self::PARTY);
    }

    /**
     * 共用密碼：以母場次（UtParty）的密碼登入子場次（UtBind）；組別取自密碼，題目與候選人屬於子場次
     */
    public function testBindVoteVoterUsesParentPasswordParty()
    {
        $this->vote(self::BIND, [
            'Name' => '單元測試共用密碼', 'shortUrl' => 'utbind', 'type' => '1', 'partyOrNot' => '1',
            'isBindVote' => '1', 'bindWhichVote' => self::PARTY,
        ]);
        $this->partyRow(self::BIND, '0');
        $this->partyRow(self::BIND, '1');
        $this->roundRow(self::BIND, 1);
        $q0 = $this->question(self::BIND, 1, '0');
        $q1 = $this->question(self::BIND, 1, '1');
        $qn = $this->question(self::BIND, 1, 'N');
        $c0 = $this->candidate(self::BIND, '0', $q0, 1);
        $c1 = $this->candidate(self::BIND, '1', $q1, 1);
        $cn = $this->candidate(self::BIND, 'N', $qn, 1);

        $this->loginVoter(self::PARTY, '0', '900001');

        $this->assertFalse((new FormBallots)->creatorBallot(self::BIND, ['selection' => [$c1]]), '他組候選人應被拒');
        $this->assertNoBallots(self::BIND);

        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::BIND, ['selection' => [$c0, $cn]]));
        $ballot = Ballots::find()->where(['voteID' => self::BIND])->one();
        $this->assertSame('0', $ballot->party);
        $this->assertSame('900001', $ballot->creator);
        $this->assertEqualsCanonicalizing(
            [$c0, $cn],
            array_map('intval', BallotsSelected::find()->select('selCandiID')->where(['voteID' => self::BIND])->column())
        );
        // 共用密碼不更新母場次密碼的投票狀態
        $this->assertSame('0', Yii::$app->db->createCommand("SELECT voted FROM passwords WHERE id = 900001")->queryScalar());
    }

    /**
     * 第 2 輪：候選人由「輪次匯入」自第 1 輪複製；切換輪次後投票者只能圈第 2 輪的候選人
     */
    public function testSecondRoundAcceptsRoundImportedCandidates()
    {
        // 第 1 輪投票：a1 兩票、a2 一票
        foreach ([[$this->c['a1']], [$this->c['a1'], $this->c['a2']]] as $selection) {
            $this->assertTrue((new FormBallots)->creatorBallot(self::NO_AUTH, ['selection' => $selection]));
        }

        // 輪次匯入：題目 A（第 1 輪）的候選人複製到題目 B（第 2 輪），a1 標記已達門檻
        $post = ['FormCandiData' => [
            'importParty' => 'def', 'importQuestionID' => $this->q['B'],
            'questionID' => $this->q['A'], 'id' => [$this->c['a1']],
        ]];
        $this->assertTrue((new \app\models\FormCandiData)->importRound(self::NO_AUTH, $post, $this->q['A']));
        $imported = \app\models\CandiData::find()
            ->where(['voteID' => self::NO_AUTH, 'questionID' => $this->q['B'], 'genMode' => 'round'])
            ->indexBy('orderNum')->all();
        $this->assertCount(3, $imported);

        // 切換到第 2 輪（RoundController::actionSwitch 僅更新 votes.round）
        Yii::$app->db->createCommand()->update('votes', ['round' => 2], ['voteID' => self::NO_AUTH])->execute();

        $this->assertFalse(
            (new FormBallots)->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a2']]]),
            '第 1 輪的候選人不可在第 2 輪圈選'
        );

        $model = new FormBallots;
        $this->assertTrue($model->creatorBallot(self::NO_AUTH, ['selection' => [$imported[2]->id]]));
        $this->assertSame('2', (string) Ballots::find()->select('round')->where(['voteID' => self::NO_AUTH, 'ballotID' => $model->ballotID])->scalar());
        $this->assertSame(
            $this->q['B'],
            (int) BallotsSelected::find()->select('questionID')->where(['voteID' => self::NO_AUTH, 'ballotID' => $model->ballotID])->scalar()
        );

        // 第 2 輪計票只計第 2 輪的選票
        $sort = (new FormManageCount(self::NO_AUTH))->getBallotCountSort(true);
        $count = array_column($sort['def'][$this->q['B']]['ranking'], 'count', 'candi');
        $this->assertSame(1, (int) $count[$imported[2]->id]);
        $this->assertSame(0, (int) ($count[$imported[1]->id] ?? 0));
    }

    // ==================== 匯入選票（自其他場次複製） ====================

    private function seedImportTarget(): int
    {
        // 目標場次：題目 T 只有 orderNum 1、2 的候選人（來源題目 A 有 1~3）
        $this->vote(self::IMPORT, [
            'Name' => '單元測試匯入', 'shortUrl' => 'utimport',
            'type' => '0', 'partyOrNot' => '0', 'addiCondition' => 'n', 'session' => '', 'authBeforeDetail' => '0',
        ]);
        $this->partyRow(self::IMPORT, 'def');
        $this->roundRow(self::IMPORT, 1);
        $qt = $this->question(self::IMPORT, 1, 'def');
        $this->c['t1'] = $this->candidate(self::IMPORT, 'def', $qt, 1);
        $this->c['t2'] = $this->candidate(self::IMPORT, 'def', $qt, 2);
        return $qt;
    }

    private function importPost(): array
    {
        return ['FormBallots' => ['voteID' => self::NO_AUTH, 'questionID' => [$this->q['A']]]];
    }

    public function testImportBallotsCopiesMappedSelections()
    {
        $qt = $this->seedImportTarget();
        $this->assertTrue((new FormBallots)->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a1'], $this->c['a2']]]));
        $this->assertTrue((new FormBallots)->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a2']]]));

        $voteInfo = \app\models\FormVotes::findOne(['voteID' => self::IMPORT]);
        $this->assertSame(2, (new FormBallots)->importBallots($voteInfo, [$qt], $this->importPost()));

        $this->assertSame(2, (int) Ballots::find()->where(['voteID' => self::IMPORT])->count());
        $selected = array_map('intval', BallotsSelected::find()->select('selCandiID')->where(['voteID' => self::IMPORT])->column());
        sort($selected);
        $expected = [$this->c['t1'], $this->c['t2'], $this->c['t2']];
        sort($expected);
        $this->assertSame($expected, $selected);
    }

    /**
     * 來源選票圈了目標場次沒有對應（orderNum）的候選人 → 整批失敗、不寫入任何選票，並說明原因
     */
    public function testImportBallotsFailsWholeBatchWhenCandidateUnmapped()
    {
        $qt = $this->seedImportTarget();
        $this->assertTrue((new FormBallots)->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a1']]]));
        $this->assertTrue((new FormBallots)->creatorBallot(self::NO_AUTH, ['selection' => [$this->c['a3']]])); // orderNum 3：無對應

        Yii::$app->session->removeAllFlashes();
        $voteInfo = \app\models\FormVotes::findOne(['voteID' => self::IMPORT]);
        $this->assertSame(0, (new FormBallots)->importBallots($voteInfo, [$qt], $this->importPost()));

        $this->assertNoBallots(self::IMPORT);
        $errors = (array) Yii::$app->session->getFlash('error', []);
        $this->assertContains(
            '匯入失敗：場次 ' . self::NO_AUTH . ' 的候選人或題目在本場次找不到對應，未匯入任何選票',
            $errors,
            json_encode($errors, JSON_UNESCAPED_UNICODE)
        );
    }

    // ==================== 開票結果寫入 results（計票 → 開票整段流程） ====================

    private function seedCountVote(string $voteID, string $addiCondition, array $question): int
    {
        $this->vote($voteID, [
            'Name' => "單元測試開票 $voteID", 'shortUrl' => strtolower(substr($voteID, 2)),
            'type' => '1', 'partyOrNot' => '0', 'addiCondition' => $addiCondition,
        ]);
        $this->partyRow($voteID, 'def');
        $this->roundRow($voteID, 1);
        return $this->question($voteID, 1, 'def', $question + ['numBallots' => 6, 'leastNumBallots' => 0]);
    }

    /**
     * 當選 3、遞補 2；甲 10、乙丙丁各 8、戊 5、己 2 → 甲 E、乙丙丁 CE、戊 W、己 LE
     */
    public function testResultsElectedStatusWithTieOverflow()
    {
        $q = $this->seedCountVote(self::COUNT_E, 'n', ['maxElect' => 3, 'numOfKeep' => 2]);
        $id = [];
        foreach (['甲' => 10, '乙' => 8, '丙' => 8, '丁' => 8, '戊' => 5, '己' => 2] as $name => $votes) {
            $id[$name] = $this->candidate(self::COUNT_E, 'def', $q, count($id) + 1, ['Name' => $name, 'sex' => '1']);
        }
        $counts = ['甲' => 10, '乙' => 8, '丙' => 8, '丁' => 8, '戊' => 5, '己' => 2];
        $selections = [];
        for ($i = 0; $i < 10; $i++) {
            $selections[] = array_values(array_map(fn ($n) => $id[$n], array_keys(array_filter($counts, fn ($c) => $c > $i))));
        }
        $this->castBallots(self::COUNT_E, 'def', $q, $selections);

        $this->assertTrue((new FormResults)->createResults(self::COUNT_E));

        $rows = Results::find()->where(['voteID' => self::COUNT_E])->indexBy('candID')->all();
        $this->assertCount(6, $rows);
        $expect = ['甲' => ['E', 1, 10], '乙' => ['CE', 2, 8], '丙' => ['CE', 2, 8], '丁' => ['CE', 2, 8], '戊' => ['W', 3, 5], '己' => ['LE', 4, 2]];
        foreach ($expect as $name => [$elected, $rank, $count]) {
            $row = $rows[$id[$name]];
            $this->assertSame([$elected, $rank, $count], [$row->elected, (int) $row->rank, (int) $row->ballotCounts], $name);
        }
    }

    /**
     * 女性保留 2 名、當選 2；女 A 10、女 B 8、男 C 9 → A、B 以女性保留名額當選（名次 1、2），C 第 3 名落選
     */
    public function testResultsFemaleKeepSeats()
    {
        $q = $this->seedCountVote(self::COUNT_F, 'female', ['maxElect' => 2, 'numOfKeep' => 0, 'numFemaleKeep' => 2]);
        $a = $this->candidate(self::COUNT_F, 'def', $q, 1, ['Name' => 'A', 'sex' => '0']);
        $b = $this->candidate(self::COUNT_F, 'def', $q, 2, ['Name' => 'B', 'sex' => '0']);
        $c = $this->candidate(self::COUNT_F, 'def', $q, 3, ['Name' => 'C', 'sex' => '1']);
        $counts = [$a => 10, $b => 8, $c => 9];
        $selections = [];
        for ($i = 0; $i < 10; $i++) {
            $selections[] = array_keys(array_filter($counts, fn ($n) => $n > $i));
        }
        $this->castBallots(self::COUNT_F, 'def', $q, $selections);

        $this->assertTrue((new FormResults)->createResults(self::COUNT_F));

        $rows = Results::find()->where(['voteID' => self::COUNT_F])->indexBy('candID')->all();
        $this->assertSame(['E', 1, 10, '女性保留名額'], [$rows[$a]->elected, (int) $rows[$a]->rank, (int) $rows[$a]->ballotCounts, $rows[$a]->comment]);
        $this->assertSame(['E', 2, 8, '女性保留名額'], [$rows[$b]->elected, (int) $rows[$b]->rank, (int) $rows[$b]->ballotCounts, $rows[$b]->comment]);
        $this->assertSame(['LE', 3, 9, ''], [$rows[$c]->elected, (int) $rows[$c]->rank, (int) $rows[$c]->ballotCounts, (string) $rows[$c]->comment]);
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
        $where = ['voteID' => self::ALL_VOTES];
        foreach (['logins', 'results', 'ballotsSelected', 'ballots', 'passwords', 'candiData', 'questions', 'round', 'parties', 'votes'] as $table) {
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
        $this->vote(self::NO_AUTH, [
            'Name' => '單元測試表決', 'shortUrl' => 'utnoauth',
            'type' => '0', 'partyOrNot' => '0', 'addiCondition' => 'n', 'session' => '', 'authBeforeDetail' => '0',
        ]);
        $this->vote(self::PARTY, ['Name' => '單元測試分組', 'shortUrl' => 'utparty', 'type' => '1', 'partyOrNot' => '1']);

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

    /**
     * 以 fixture 的 AnonPartyTest 為底建立場次
     */
    private function vote(string $voteID, array $override): void
    {
        $voteRows = require codecept_root_dir() . 'tests/fixtures/data/votes.php';
        $this->insert('votes', array_merge($voteRows['AnonPartyTest'], [
            'voteID' => $voteID, 'isBindVote' => '0', 'bindWhichVote' => '', 'round' => '1',
        ], $override));
    }

    private function question(string $voteID, int $round, string $party, array $override = []): int
    {
        return $this->insert('questions', array_merge([
            'voteID' => $voteID, 'round' => $round, 'party' => $party,
            'title' => "Q $party r$round", 'titleE' => "Q $party r$round",
            'numBallots' => 2, 'leastNumBallots' => 1, 'maxElect' => 1, 'numOfKeep' => 0,
        ], $override));
    }

    private function candidate(string $voteID, string $party, int $questionID, int $order, array $override = []): int
    {
        $rows = require codecept_root_dir() . 'tests/fixtures/data/candiData.php';
        return $this->insert('candiData', array_merge($rows['candiData730'], [
            'voteID' => $voteID, 'party' => $party, 'questionID' => $questionID,
            'Name' => "候選人{$questionID}-{$order}", 'NameE' => "Candidate {$questionID}-{$order}",
            'orderNum' => $order,
        ], $override));
    }

    private function partyRow(string $voteID, string $party): void
    {
        $this->insert('parties', [
            'voteID' => $voteID, 'party' => $party, 'name' => "組$party", 'nameE' => "Party $party",
            'numBallots' => 2, 'leastNumBallots' => 1, 'maxElect' => 1, 'numOfKeep' => 0,
        ]);
    }

    private function roundRow(string $voteID, int $round): void
    {
        $this->insert('round', ['voteID' => $voteID, 'round' => $round, 'name' => "第{$round}輪", 'nameE' => "Round $round"]);
    }

    /**
     * 以匿名投票者身分登入（不寫 logins，等同 FormAnon 驗證通過後的 identity）
     */
    private function loginVoter(string $voteID, string $party, string $passwordId): void
    {
        $anon = new \app\components\Anon();
        $anon->setAuthData(['id' => $passwordId, 'voteID' => $voteID, 'party' => $party]);
        Yii::$app->anon->switchIdentity($anon);
    }

    /**
     * 直接寫入選票（開票測試用）：$selections 每個元素為一張選票圈選的候選人 id
     */
    private function castBallots(string $voteID, string $party, int $questionID, array $selections): void
    {
        foreach ($selections as $i => $candidates) {
            $ballotID = $this->insert('ballots', [
                'voteID' => $voteID, 'round' => 1, 'party' => $party, 'isAdminAdd' => '1', 'ip' => '127.0.0.1',
                'creator' => "c$i", 'modifier' => '', 'insTime' => date('Y-m-d H:i:s'), 'updTime' => '0000-00-00 00:00:00',
            ]);
            foreach ($candidates as $candiID) {
                $this->insert('ballotsSelected', [
                    'voteID' => $voteID, 'ballotID' => $ballotID, 'selCandiID' => $candiID,
                    'party' => $party, 'questionID' => $questionID, 'jobLctn' => '1', 'isValiable' => '1',
                ]);
            }
        }
    }

    private function insert(string $table, array $row): int
    {
        $db = Yii::$app->db;
        $db->createCommand()->insert($table, $row)->execute();
        return (int) $db->getLastInsertID();
    }
}
