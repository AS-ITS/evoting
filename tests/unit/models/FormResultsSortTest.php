<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormResults;

/**
 * 投票結果排序（ResultsConfig.sort）
 *
 * - N 依得票高低：名次相同時依候選名單順序，與計票頁（FormManageCount::sortRanking）一致
 * - L 依字首筆劃
 * - I 依候選名單：編號顯示為自動編號（A）時依候選人 id，否則依自訂排序（orderNum）
 */
class FormResultsSortTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    const VOTE = 'UtSort';

    /** @var array<string, int> 代號 => candiData.id（建立順序即 id 順序） */
    private array $c = [];

    protected function _before()
    {
        $this->cleanup();
        $voteRows = require codecept_root_dir() . 'tests/fixtures/data/votes.php';
        $this->insert('votes', array_merge($voteRows['AnonNoPartyTest'], [
            'voteID' => self::VOTE, 'shortUrl' => 'utsort', 'round' => '1', 'isBindVote' => '0', 'bindWhichVote' => '',
        ]));
        $this->insert('round', ['voteID' => self::VOTE, 'round' => 1, 'name' => '第1輪', 'nameE' => 'Round 1']);
        $q = $this->insert('questions', [
            'voteID' => self::VOTE, 'round' => 1, 'party' => 'def', 'title' => 'Q', 'titleE' => 'Q',
            'numBallots' => 4, 'leastNumBallots' => 0, 'maxElect' => 1, 'numOfKeep' => 0,
        ]);

        // id 順序 W、D、L、Y；姓名首字筆劃 一(1) < 丁(2) < 王(4) < 林(8)；orderNum 與 id 順序相反
        $rows = require codecept_root_dir() . 'tests/fixtures/data/candiData.php';
        foreach (['W' => ['王', 4], 'D' => ['丁', 3], 'L' => ['林', 2], 'Y' => ['一', 1]] as $key => [$name, $order]) {
            $this->c[$key] = $this->insert('candiData', array_merge($rows['candiData730'], [
                'voteID' => self::VOTE, 'party' => 'def', 'questionID' => $q, 'Name' => $name, 'orderNum' => $order,
            ]));
        }

        // 結果（寫入順序刻意與各排序都不同）：林 第 1、丁與一 同為第 2、王 第 3；全部落選，避免當選狀態影響順序
        foreach ([['L', 1, 10], ['D', 2, 5], ['W', 3, 1], ['Y', 2, 5]] as [$key, $rank, $count]) {
            $this->insert('results', [
                'voteID' => self::VOTE, 'candID' => $this->c[$key], 'party' => 'def', 'questionID' => $q,
                'jobLctn' => '0', 'ballotCounts' => $count, 'elected' => 'LE', 'rank' => $rank, 'comment' => '',
            ]);
        }
    }

    protected function _after()
    {
        $this->cleanup();
    }

    public function testSortByBallotsBreaksTiesByCustomListOrder()
    {
        $this->setNumbering('C');
        // 林 第 1；丁、一 同為第 2，依 orderNum：一(1) 先於 丁(3)；王 第 3
        $this->assertSame(['L', 'Y', 'D', 'W'], $this->order('N'));
    }

    public function testSortByBallotsBreaksTiesByIdWhenAutoNumbered()
    {
        $this->setNumbering('A');
        // 同為第 2 時依 id：丁 先於 一
        $this->assertSame(['L', 'D', 'Y', 'W'], $this->order('N'));
    }

    public function testSortByListUsesCustomOrder()
    {
        $this->setNumbering('C');
        $this->assertSame(['Y', 'L', 'D', 'W'], $this->order('I'));
    }

    public function testSortByListUsesIdWhenAutoNumbered()
    {
        $this->setNumbering('A');
        $this->assertSame(['W', 'D', 'L', 'Y'], $this->order('I'));
    }

    public function testSortByNameStrokes()
    {
        $this->setNumbering('C');
        $this->assertSame(['Y', 'D', 'W', 'L'], $this->order('L'));
    }

    // ==================== 輔助 ====================

    private function order(string $sort): array
    {
        $config = (object) ['isParty' => '0', 'showElectedStatus' => '', 'sort' => $sort];
        $ids = array_map('intval', (new FormResults)->search(self::VOTE, 1, $config, false, false)->select('results.candID')->column());
        $byId = array_flip($this->c);
        return array_map(fn ($id) => $byId[$id], $ids);
    }

    private function setNumbering(string $num): void
    {
        Yii::$app->db->createCommand()->delete('candiConfig', ['voteID' => self::VOTE])->execute();
        $this->insert('candiConfig', [
            'voteID' => self::VOTE, 'num' => $num, 'Name' => '姓名', 'NameE' => 'Name', 'showFieldSort' => 'Name',
        ]);
    }

    private function cleanup(): void
    {
        foreach (['results', 'candiConfig', 'candiData', 'questions', 'round', 'votes'] as $table) {
            Yii::$app->db->createCommand()->delete($table, ['voteID' => self::VOTE])->execute();
        }
    }

    private function insert(string $table, array $row): int
    {
        Yii::$app->db->createCommand()->insert($table, $row)->execute();
        return (int) Yii::$app->db->getLastInsertID();
    }
}
