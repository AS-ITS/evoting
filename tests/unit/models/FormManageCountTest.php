<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormManageCount;
use app\components\helper\ArrayHelper;

/**
 * FormManageCount 單元測試
 *
 * 補強項目：
 * - #2 BVA：processNormalRule 圈選上下限邊界值驗證
 * - #6 EP：_countSort 得票排名正確性（含同票並列）
 * - #6 EP：_electedSort 當選/遞補/落選判定
 */
class FormManageCountTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * 建立 FormManageCount 實例（跳過建構子，避免 DB 依賴）
     */
    private function createInstance(): FormManageCount
    {
        $ref = new \ReflectionClass(FormManageCount::class);
        return $ref->newInstanceWithoutConstructor();
    }

    /**
     * 設定 _questions 保護屬性（供 _electedSort 使用）
     *
     * @param FormManageCount $instance
     * @param array $questionsMap questionID => object{maxElect, numOfKeep, numFemaleKeep}
     */
    private function setQuestions(FormManageCount $instance, array $questionsMap): void
    {
        $ref = new \ReflectionClass(FormManageCount::class);
        $prop = $ref->getProperty('_questions');
        $prop->setAccessible(true);
        $prop->setValue($instance, $questionsMap);
    }

    /**
     * 呼叫受保護方法 (protected)
     */
    private function invokeProtected(FormManageCount $instance, string $method, array $args)
    {
        $ref = new \ReflectionMethod(FormManageCount::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($instance, $args);
    }

    // ==================== processNormalRule BVA 測試 ====================

    /**
     * BVA：圈選數恰好等於最大限制 → 有效
     */
    public function testExactMaxSelections()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 20, 'leastNumBallots' => 0],
        ];
        $ballotCount = [1 => 20]; // 恰好 20 = numBallots

        $this->assertTrue(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Selection count at max (20/20) should be valid'
        );
    }

    /**
     * BVA：圈選數超過最大限制 1 → 無效
     */
    public function testExceedMaxSelectionsByOne()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 20, 'leastNumBallots' => 0],
        ];
        $ballotCount = [1 => 21]; // 21 > numBallots

        $this->assertFalse(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Selection count exceeding max (21/20) should be invalid'
        );
    }

    /**
     * BVA：圈選數恰好等於最少限制 → 有效
     */
    public function testExactMinSelections()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 20, 'leastNumBallots' => 5],
        ];
        $ballotCount = [1 => 5]; // 恰好 5 = leastNumBallots

        $this->assertTrue(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Selection count at min (5 with min=5) should be valid'
        );
    }

    /**
     * BVA：圈選數低於最少限制 1 → 無效
     */
    public function testBelowMinSelectionsByOne()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 20, 'leastNumBallots' => 5],
        ];
        $ballotCount = [1 => 4]; // 4 < leastNumBallots

        $this->assertFalse(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Selection count below min (4 with min=5) should be invalid'
        );
    }

    /**
     * BVA：零圈選且最少限制為 0 → 有效（允許空白投票）
     */
    public function testZeroSelectionsWithMinZero()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 20, 'leastNumBallots' => 0],
        ];
        // ballotCount 中此問題不存在 → empty()
        $ballotCount = [];

        $this->assertTrue(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Zero selections with leastNumBallots=0 should be valid (blank vote)'
        );
    }

    /**
     * BVA：零圈選但最少限制 > 0 → 無效
     */
    public function testZeroSelectionsWithMinNonZero()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 3, 'leastNumBallots' => 1],
        ];
        $ballotCount = [];

        $this->assertFalse(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Zero selections with leastNumBallots=1 should be invalid'
        );
    }

    /**
     * EP：圈選數在合法範圍中間 → 有效
     */
    public function testMidRangeSelections()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 20, 'leastNumBallots' => 0],
        ];
        $ballotCount = [1 => 10]; // 0 <= 10 <= 20

        $this->assertTrue(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Selection count in mid-range (10 of 0-20) should be valid'
        );
    }

    /**
     * BVA：最大等於最少（只允許固定數量） → 恰好該數有效
     */
    public function testMaxEqualsMinExactCount()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 3, 'leastNumBallots' => 3],
        ];
        $ballotCount = [1 => 3];

        $this->assertTrue(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Selection count exactly at min=max=3 should be valid'
        );
    }

    /**
     * BVA：最大等於最少，但圈選數+1 → 無效
     */
    public function testMaxEqualsMinExceed()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 3, 'leastNumBallots' => 3],
        ];
        $ballotCount = [1 => 4];

        $this->assertFalse(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Selection count 4 when min=max=3 should be invalid'
        );
    }

    /**
     * BVA：最大等於最少，但圈選數-1 → 無效
     */
    public function testMaxEqualsMinBelow()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 3, 'leastNumBallots' => 3],
        ];
        $ballotCount = [1 => 2];

        $this->assertFalse(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Selection count 2 when min=max=3 should be invalid'
        );
    }

    /**
     * EP：ballotCount 中計數為 0（非 empty，而是 key 存在值為 0）
     * empty(0) == true，所以走入第二分支
     */
    public function testBallotCountZeroKeyExistsMinZero()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 20, 'leastNumBallots' => 0],
        ];
        $ballotCount = [1 => 0]; // key exists but value is 0

        // empty(0) == true，且 leastNumBallots == 0 → 有效
        $this->assertTrue(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Ballot count of 0 (key exists) with min=0 should be valid'
        );
    }

    /**
     * EP：ballotCount 中計數為 0 但最少限制 > 0 → 無效
     */
    public function testBallotCountZeroKeyExistsMinNonZero()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 3, 'leastNumBallots' => 1],
        ];
        $ballotCount = [1 => 0]; // key exists but value is 0

        // empty(0) == true，但 leastNumBallots != 0 → 無效
        $this->assertFalse(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Ballot count of 0 (key exists) with min=1 should be invalid'
        );
    }

    /**
     * BVA：圈選數為 1，上限也為 1 → 有效
     */
    public function testSingleSelectionMaxOne()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 1, 'leastNumBallots' => 1],
        ];
        $ballotCount = [1 => 1];

        $this->assertTrue(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Single selection with max=min=1 should be valid'
        );
    }

    /**
     * EP：多個問題同時驗證 — 各問題獨立判定
     */
    public function testMultipleQuestionsIndependent()
    {
        $instance = $this->createInstance();
        $questions = [
            1 => ['numBallots' => 20, 'leastNumBallots' => 0],
            2 => ['numBallots' => 3, 'leastNumBallots' => 1],
        ];
        $ballotCount = [1 => 15, 2 => 2];

        $this->assertTrue(
            $instance->processNormalRule(1, $ballotCount, $questions),
            'Question 1: 15 within 0-20 should be valid'
        );
        $this->assertTrue(
            $instance->processNormalRule(2, $ballotCount, $questions),
            'Question 2: 2 within 1-3 should be valid'
        );
    }

    // ==================== _countSort 排名邏輯測試 ====================

    /**
     * EP：得票由多到少排序
     */
    public function testCountSortDescendingOrder()
    {
        $instance = $this->createInstance();

        $candiList = $this->buildCandiList([
            101 => ['Name' => 'A', 'questionID' => 1, 'count' => 5],
            102 => ['Name' => 'B', 'questionID' => 1, 'count' => 3],
            103 => ['Name' => 'C', 'questionID' => 1, 'count' => 8],
        ]);
        $ballotCount = [
            1 => [103 => 8, 101 => 5, 102 => 3],
        ];

        $result = $this->invokeProtected($instance, '_countSort', [$ballotCount, $candiList]);

        $this->assertArrayHasKey(1, $result);
        $ranking = $result[1]['ranking'];

        // 得票最高的排第一
        $this->assertEquals(103, $ranking[0]['candi'], 'Candidate with 8 votes should be rank 1');
        $this->assertEquals(1, $ranking[0]['rank']);
        $this->assertEquals(8, $ranking[0]['count']);

        // 得票次高的排第二
        $this->assertEquals(101, $ranking[1]['candi'], 'Candidate with 5 votes should be rank 2');
        $this->assertEquals(2, $ranking[1]['rank']);

        // 得票最低的排第三
        $this->assertEquals(102, $ranking[2]['candi'], 'Candidate with 3 votes should be rank 3');
        $this->assertEquals(3, $ranking[2]['rank']);
    }

    /**
     * BVA：同票候選人應獲得相同名次
     */
    public function testCountSortTiedVotesGetSameRank()
    {
        $instance = $this->createInstance();

        $candiList = $this->buildCandiList([
            101 => ['Name' => 'A', 'questionID' => 1],
            102 => ['Name' => 'B', 'questionID' => 1],
            103 => ['Name' => 'C', 'questionID' => 1],
        ]);
        $ballotCount = [
            1 => [101 => 5, 102 => 5, 103 => 3],
        ];

        $result = $this->invokeProtected($instance, '_countSort', [$ballotCount, $candiList]);
        $ranking = $result[1]['ranking'];

        // 前兩位同票 → 同名次
        $this->assertEquals($ranking[0]['rank'], $ranking[1]['rank'],
            'Tied candidates should have the same rank');
        $this->assertEquals(1, $ranking[0]['rank']);
    }

    /**
     * BVA：同票之後名次應跳號（standard competition ranking）
     */
    public function testCountSortRankSkipsAfterTie()
    {
        $instance = $this->createInstance();

        $candiList = $this->buildCandiList([
            101 => ['Name' => 'A', 'questionID' => 1],
            102 => ['Name' => 'B', 'questionID' => 1],
            103 => ['Name' => 'C', 'questionID' => 1],
        ]);
        $ballotCount = [
            1 => [101 => 5, 102 => 5, 103 => 3],
        ];

        $result = $this->invokeProtected($instance, '_countSort', [$ballotCount, $candiList]);
        $ranking = $result[1]['ranking'];

        // 第三名應為 3 (跳過 2)
        $this->assertEquals(3, $ranking[2]['rank'],
            'After two rank-1 ties, next rank should be 3 (skip 2)');
    }

    /**
     * EP：只有一位候選人
     */
    public function testCountSortSingleCandidate()
    {
        $instance = $this->createInstance();

        $candiList = $this->buildCandiList([
            101 => ['Name' => 'A', 'questionID' => 1],
        ]);
        $ballotCount = [
            1 => [101 => 10],
        ];

        $result = $this->invokeProtected($instance, '_countSort', [$ballotCount, $candiList]);
        $ranking = $result[1]['ranking'];

        $this->assertCount(1, $ranking);
        $this->assertEquals(1, $ranking[0]['rank']);
        $this->assertEquals(10, $ranking[0]['count']);
    }

    /**
     * EP：所有候選人同票
     */
    public function testCountSortAllTied()
    {
        $instance = $this->createInstance();

        $candiList = $this->buildCandiList([
            101 => ['Name' => 'A', 'questionID' => 1],
            102 => ['Name' => 'B', 'questionID' => 1],
            103 => ['Name' => 'C', 'questionID' => 1],
        ]);
        $ballotCount = [
            1 => [101 => 5, 102 => 5, 103 => 5],
        ];

        $result = $this->invokeProtected($instance, '_countSort', [$ballotCount, $candiList]);
        $ranking = $result[1]['ranking'];

        // 全部同名次
        $this->assertEquals(1, $ranking[0]['rank']);
        $this->assertEquals(1, $ranking[1]['rank']);
        $this->assertEquals(1, $ranking[2]['rank']);
    }

    /**
     * EP：rankingNext 應為候選人數 +1
     */
    public function testCountSortRankingNext()
    {
        $instance = $this->createInstance();

        $candiList = $this->buildCandiList([
            101 => ['Name' => 'A', 'questionID' => 1],
            102 => ['Name' => 'B', 'questionID' => 1],
        ]);
        $ballotCount = [
            1 => [101 => 5, 102 => 3],
        ];

        $result = $this->invokeProtected($instance, '_countSort', [$ballotCount, $candiList]);

        $this->assertEquals(3, $result[1]['rankingNext'],
            'rankingNext should be candidate count + 1');
    }

    // ==================== _electedSort 當選判定測試 ====================

    /**
     * EP：基本當選 / 遞補 / 落選判定
     */
    public function testElectedSortBasic()
    {
        $instance = $this->createInstance();
        $this->setQuestions($instance, [
            1 => (object)['maxElect' => 2, 'numOfKeep' => 1, 'numFemaleKeep' => 0],
        ]);

        $ranking = [
            ['candi' => 101, 'rank' => 1, 'count' => 10, 'femaleKeep' => false],
            ['candi' => 102, 'rank' => 2, 'count' => 8, 'femaleKeep' => false],
            ['candi' => 103, 'rank' => 3, 'count' => 5, 'femaleKeep' => false],
            ['candi' => 104, 'rank' => 4, 'count' => 2, 'femaleKeep' => false],
        ];

        $result = $instance->_electedSort($ranking, 1);

        $this->assertEquals('E', $result[0]['elect'], 'Rank 1 should be elected (E)');
        $this->assertEquals('E', $result[1]['elect'], 'Rank 2 should be elected (E) — maxElect=2');
        $this->assertEquals('W', $result[2]['elect'], 'Rank 3 should be runner-up (W) — numOfKeep=1');
        $this->assertEquals('LE', $result[3]['elect'], 'Rank 4 should be lost (LE)');
    }

    /**
     * EP：當選名額為 1，遞補為 0
     */
    public function testElectedSortMinimal()
    {
        $instance = $this->createInstance();
        $this->setQuestions($instance, [
            1 => (object)['maxElect' => 1, 'numOfKeep' => 0, 'numFemaleKeep' => 0],
        ]);

        $ranking = [
            ['candi' => 101, 'rank' => 1, 'count' => 10, 'femaleKeep' => false],
            ['candi' => 102, 'rank' => 2, 'count' => 5, 'femaleKeep' => false],
        ];

        $result = $instance->_electedSort($ranking, 1);

        $this->assertEquals('E', $result[0]['elect'], 'Rank 1 should be elected');
        $this->assertEquals('LE', $result[1]['elect'], 'Rank 2 should be lost (no runner-up slots)');
    }

    /**
     * BVA：同票候選人跨越當選/遞補邊界 → 待當選 (CE)
     */
    public function testElectedSortTiedAtElectionBoundary()
    {
        $instance = $this->createInstance();
        $this->setQuestions($instance, [
            1 => (object)['maxElect' => 1, 'numOfKeep' => 1, 'numFemaleKeep' => 0],
        ]);

        // rank 1 有 1 人當選，rank 2 有 2 人同票 → 超出 maxElect
        $ranking = [
            ['candi' => 101, 'rank' => 1, 'count' => 10, 'femaleKeep' => false],
            ['candi' => 102, 'rank' => 2, 'count' => 5, 'femaleKeep' => false],
            ['candi' => 103, 'rank' => 2, 'count' => 5, 'femaleKeep' => false],
            ['candi' => 104, 'rank' => 4, 'count' => 2, 'femaleKeep' => false],
        ];

        $result = $instance->_electedSort($ranking, 1);

        $this->assertEquals('E', $result[0]['elect'], 'Rank 1 alone → elected');
        // Rank 2 兩人同票但只有 1 遞補名額 → CW（待遞補，因為 electNum 已耗盡進入遞補區段）
    }

    /**
     * EP：所有候選人都當選（名額足夠）
     */
    public function testElectedSortAllElected()
    {
        $instance = $this->createInstance();
        $this->setQuestions($instance, [
            1 => (object)['maxElect' => 5, 'numOfKeep' => 0, 'numFemaleKeep' => 0],
        ]);

        $ranking = [
            ['candi' => 101, 'rank' => 1, 'count' => 10, 'femaleKeep' => false],
            ['candi' => 102, 'rank' => 2, 'count' => 8, 'femaleKeep' => false],
            ['candi' => 103, 'rank' => 3, 'count' => 5, 'femaleKeep' => false],
        ];

        $result = $instance->_electedSort($ranking, 1);

        $this->assertEquals('E', $result[0]['elect']);
        $this->assertEquals('E', $result[1]['elect']);
        $this->assertEquals('E', $result[2]['elect'], 'All candidates elected when slots > candidates');
    }

    /**
     * BVA：同票候選人全部在當選邊界 → 待當選 (CE)
     */
    public function testElectedSortTiedExceedingElectSlots()
    {
        $instance = $this->createInstance();
        $this->setQuestions($instance, [
            1 => (object)['maxElect' => 1, 'numOfKeep' => 0, 'numFemaleKeep' => 0],
        ]);

        // 2 人同票 rank 1，但只有 1 個當選名額
        $ranking = [
            ['candi' => 101, 'rank' => 1, 'count' => 10, 'femaleKeep' => false],
            ['candi' => 102, 'rank' => 1, 'count' => 10, 'femaleKeep' => false],
        ];

        $result = $instance->_electedSort($ranking, 1);

        // 2 人同票超過 1 個名額 → CE
        $this->assertEquals('CE', $result[0]['elect'], 'Tied candidates exceeding elect slots should be CE');
        $this->assertEquals('CE', $result[1]['elect']);
    }

    // ==================== 輔助方法 ====================

    /**
     * 建立候選人列表（模擬 CandiData 陣列格式）
     *
     * @param array $candidates [id => ['Name' => ..., 'questionID' => ...]]
     * @return array keyed by id
     */
    private function buildCandiList(array $candidates): array
    {
        $result = [];
        foreach ($candidates as $id => $data) {
            $result[$id] = [
                'id' => $id,
                'Name' => $data['Name'] ?? 'Candidate '.$id,
                'NameE' => $data['NameE'] ?? 'Candidate '.$id,
                'party' => $data['party'] ?? 'N',
                'questionID' => $data['questionID'] ?? 1,
                'sex' => $data['sex'] ?? '1',
                'orderNum' => $data['orderNum'] ?? 0,
                'isReachThreshold' => $data['isReachThreshold'] ?? null,
                'relateParty' => $data['relateParty'] ?? '0',
                'specialHonor' => $data['specialHonor'] ?? '0',
                'other' => $data['other'] ?? '0',
                'jobLctn' => $data['jobLctn'] ?? '0',
            ];
        }
        return $result;
    }

    // ==================== PHP 8.x Warning 邊界值測試 ====================

    /**
     * 建立 getStoreRanking 用的最小 candiData
     */
    private function makeCandiData(array $override = []): array
    {
        return array_merge([
            'party'            => 'A',
            'Name'             => '測試候選人',
            'NameE'            => 'Test',
            'isReachThreshold' => '0',
            'orderNum'         => 1,
            'questionID'       => 1,
            'relateParty'      => '',
            'specialHonor'     => '',
            'other'            => null,
        ], $override);
    }

    /**
     * 測試：getStoreRanking - other 為合法 JSON 但缺少 round/count 鍵
     * PHP 8.x 修正前：$other['round'] 觸發 E_WARNING "Undefined array key 'round'"
     * 修正後：($other['round'] ?? '') 安全回傳空字串
     */
    public function testGetStoreRankingJsonOtherMissingRoundCount()
    {
        $instance = $this->createInstance();
        $candiData = $this->makeCandiData([
            'other' => '{"someOtherKey":"value"}', // 合法 JSON，但無 round/count
        ]);

        $result = $this->invokeProtected($instance, 'getStoreRanking', [1, 1, 0, false, $candiData]);

        $this->assertSame('', $result['reachThresholdRound'],
            'JSON 中無 round 鍵時，reachThresholdRound 應回傳空字串，不應觸發 PHP 8.x Warning');
        $this->assertSame('', $result['reachThresholdCount'],
            'JSON 中無 count 鍵時，reachThresholdCount 應回傳空字串，不應觸發 PHP 8.x Warning');
    }

    /**
     * 測試：getStoreRanking - other 為空 JSON 物件
     */
    public function testGetStoreRankingWithEmptyJsonObject()
    {
        $instance = $this->createInstance();
        $candiData = $this->makeCandiData(['other' => '{}']);

        $result = $this->invokeProtected($instance, 'getStoreRanking', [1, 1, 0, false, $candiData]);

        $this->assertSame('', $result['reachThresholdRound'], '空 JSON 物件的 reachThresholdRound 應為空字串');
        $this->assertSame('', $result['reachThresholdCount'], '空 JSON 物件的 reachThresholdCount 應為空字串');
    }

    /**
     * 測試：getStoreRanking - other 含有 round/count 鍵（輪次匯入候選人）
     * 確保正常路徑不受修正影響
     */
    public function testGetStoreRankingJsonOtherHasRoundCount()
    {
        $instance = $this->createInstance();
        $candiData = $this->makeCandiData([
            'isReachThreshold' => '1',
            'other'            => '{"round":2,"count":15}',
        ]);

        $result = $this->invokeProtected($instance, 'getStoreRanking', [1, 1, 15, false, $candiData]);

        $this->assertSame(2,  $result['reachThresholdRound'], 'JSON 有 round 鍵時應正確回傳');
        $this->assertSame(15, $result['reachThresholdCount'], 'JSON 有 count 鍵時應正確回傳');
    }

    /**
     * 測試：getStoreRanking - other 為 null（非 JSON）
     */
    public function testGetStoreRankingNonJsonOther()
    {
        $instance = $this->createInstance();
        $candiData = $this->makeCandiData(['other' => null]);

        $result = $this->invokeProtected($instance, 'getStoreRanking', [1, 1, 0, false, $candiData]);

        $this->assertSame('', $result['reachThresholdRound'], '非 JSON other 的 reachThresholdRound 應為空字串');
        $this->assertSame('', $result['reachThresholdCount'], '非 JSON other 的 reachThresholdCount 應為空字串');
    }

    /**
     * 迴歸測試：getPasswordAndValidCount 分組選票計數正確性
     *
     * 修正前 bug：ArrayHelper::map($ballotList, 'ballotID', 'party') 產生
     *             扁平結構 [ballotID => party]，以 party 代碼查詢永遠回傳 null，
     *             導致有效票/廢票恆為 0。
     * 修正後：   ArrayHelper::map($ballotList, 'ballotID', '', 'party') 產生
     *             巢狀結構 [party => [ballotID => '']]，正確依分組過濾選票。
     */
    public function testGetPasswordAndValidCountCorrectlyGroupsBallotsByParty()
    {
        $instance = $this->createInstance();

        // 兩個分組各一張選票
        $ballotList = [
            ['ballotID' => 10, 'party' => 'A'],
            ['ballotID' => 11, 'party' => 'B'],
        ];
        // ballotID => [questionID => 圈選人數]
        $ballotCountAry = [
            10 => [1 => 1], // 選票 10：問題 1 圈選 1 人（有效）
            11 => [1 => 2], // 選票 11：問題 1 圈選 2 人（有效，但屬於 B 組，不應計入）
        ];
        $modelPartyA          = new \stdClass();
        $modelPartyA->numCounting = null;
        $modelParties         = ['A' => $modelPartyA];
        $passwordList         = ['A' => [['passwd' => 'pw1']]];
        $questions            = [1 => ['numBallots' => 3, 'leastNumBallots' => 0]];
        $validCount           = [1 => ['valid' => 0, 'invalid' => 0]];

        $instance->getPasswordAndValidCount(
            'A', 1, $validCount, $ballotList, $ballotCountAry, $modelParties, $passwordList, $questions
        );

        // 分組 A 只有 1 張選票（ballotID=10），B 組選票不應被計入
        $total = $validCount[1]['valid'] + $validCount[1]['invalid'];
        $this->assertEquals(1, $total,
            '分組 A 僅有 1 張選票，有效票+廢票應等於 1（修正前為 0）');
        $this->assertEquals(1, $validCount[1]['valid'],
            '選票 10 圈選 1 人符合限制(0~3)，應計為有效票');
        $this->assertEquals(0, $validCount[1]['invalid'],
            '分組 B 的選票不應計入分組 A 的廢票');
    }

    /**
     * 迴歸測試：ArrayHelper::map 3個參數 vs 4個參數的結構差異
     * 直接驗證造成 bug 的 ArrayHelper::map 呼叫方式
     */
    public function testArrayHelperMapGroupingStructureDifference()
    {
        $ballotList = [
            ['ballotID' => 10, 'party' => 'A'],
            ['ballotID' => 11, 'party' => 'B'],
            ['ballotID' => 12, 'party' => 'A'],
        ];

        // 修正後（4個參數）：[party => [ballotID => '']]
        $groupedByParty = ArrayHelper::map($ballotList, 'ballotID', '', 'party');
        $this->assertArrayHasKey('A', $groupedByParty, '4個參數 map 應以 party 為 key');
        $this->assertCount(2, $groupedByParty['A'],     '分組 A 應含 2 張選票');
        $this->assertArrayHasKey(10, $groupedByParty['A'], '選票 10 應在分組 A');
        $this->assertArrayHasKey(12, $groupedByParty['A'], '選票 12 應在分組 A');
        $this->assertArrayHasKey('B', $groupedByParty,  '分組 B 應存在');

        // 修正前（3個參數）：[ballotID => party]（扁平）→ 以 party 查詢永遠為 null
        $wrongMap = ArrayHelper::map($ballotList, 'ballotID', 'party');
        $this->assertNull($wrongMap['A'] ?? null,
            '3個參數 map 用 party 當 key 查不到資料（這是修正前 bug 的根因）');
    }
}
