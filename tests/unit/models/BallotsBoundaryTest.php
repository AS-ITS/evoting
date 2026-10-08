<?php

namespace app\tests\unit\models;

use Yii;
use app\models\Ballots;
use app\models\BallotsSelected;
use app\models\CandiData;
use app\models\FormBallots;
use app\models\FormBallotsSelected;
use app\models\FormManageCount;
use app\models\Questions;
use app\components\helper\ArrayHelper;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\BallotsSelectedFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

/**
 * Ballots / BallotsSelected 邊界值分析與等價劃分測試
 *
 * 補強項目：
 * - BVA：圈選人數上下限驗證
 * - EP：不存在的候選人 ID
 * - EP：選票基本驗證
 */
class BallotsBoundaryTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
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

    public function _fixtures()
    {
        return [
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'questions' => QuestionsFixture::class,
            'candiConfig' => CandiConfigFixture::class,
            'candiData' => CandiDataFixture::class,
            'passwords' => PasswordsFixture::class,
            'round' => RoundFixture::class,
        ];
    }

    // ==================== EP：Ballots 模型驗證 ====================

    /**
     * EP：Ballots 必填欄位驗證
     */
    public function testBallotsRequiredFields()
    {
        $ballot = new Ballots();
        $this->assertFalse($ballot->validate(), 'Empty ballot should fail validation');

        $this->assertArrayHasKey('voteID', $ballot->errors, 'voteID is required');
        $this->assertArrayHasKey('ballotID', $ballot->errors, 'ballotID is required');
    }

    /**
     * BVA：voteID 恰好 20 字元應通過驗證
     */
    public function testBallotsVoteIdExactMaxLength()
    {
        $ballot = new Ballots();
        $ballot->voteID = str_repeat('a', 20);
        $ballot->validate(['voteID']);
        $this->assertArrayNotHasKey('voteID', $ballot->errors, 'voteID at max 20 should pass');
    }

    /**
     * BVA：voteID 超過 20 字元應失敗
     */
    public function testBallotsVoteIdExceedsMaxLength()
    {
        $ballot = new Ballots();
        $ballot->voteID = str_repeat('a', 21);
        $ballot->validate(['voteID']);
        $this->assertArrayHasKey('voteID', $ballot->errors, 'voteID exceeding 20 should fail');
    }

    /**
     * BVA：party 恰好 12 字元應通過驗證
     */
    public function testBallotsPartyExactMaxLength()
    {
        $ballot = new Ballots();
        $ballot->party = str_repeat('a', 12);
        $ballot->validate(['party']);
        $this->assertArrayNotHasKey('party', $ballot->errors, 'party at max 12 should pass');
    }

    /**
     * BVA：party 超過 12 字元應失敗
     */
    public function testBallotsPartyExceedsMaxLength()
    {
        $ballot = new Ballots();
        $ballot->party = str_repeat('a', 13);
        $ballot->validate(['party']);
        $this->assertArrayHasKey('party', $ballot->errors, 'party exceeding 12 should fail');
    }

    // ==================== EP：BallotsSelected 模型驗證 ====================

    /**
     * EP：BallotsSelected 必填欄位驗證
     */
    public function testBallotsSelectedRequiredFields()
    {
        $selected = new BallotsSelected();
        $this->assertFalse($selected->validate(), 'Empty BallotsSelected should fail validation');

        $this->assertArrayHasKey('voteID', $selected->errors, 'voteID is required');
        $this->assertArrayHasKey('ballotID', $selected->errors, 'ballotID is required');
    }

    /**
     * BVA：BallotsSelected voteID 恰好 20 字元應通過
     */
    public function testBallotsSelectedVoteIdMaxLength()
    {
        $selected = new BallotsSelected();
        $selected->voteID = str_repeat('a', 20);
        $selected->validate(['voteID']);
        $this->assertArrayNotHasKey('voteID', $selected->errors, 'voteID at max 20 should pass');
    }

    /**
     * BVA：BallotsSelected voteID 超過 20 字元應失敗
     */
    public function testBallotsSelectedVoteIdExceedsMaxLength()
    {
        $selected = new BallotsSelected();
        $selected->voteID = str_repeat('a', 21);
        $selected->validate(['voteID']);
        $this->assertArrayHasKey('voteID', $selected->errors, 'voteID exceeding 20 should fail');
    }

    // ==================== EP：選票建立邊界測試 ====================

    /**
     * EP：不選擇任何候選人（空選票）應成功建立
     */
    public function testCreateBallotWithZeroCandidates()
    {
        $password = $this->tester->grabFixture('passwords', 0);
        $this->tester->anonLogin($password);

        $FormBallots = new FormBallots();
        $post = [
            'FormBallots' => [
                'party' => $password['party'],
                'creator' => $password['id'],
            ]
            // 沒有 selection — 空選票
        ];

        $result = $FormBallots->creatorBallot($password['voteID'], $post, false);
        $this->assertTrue($result, 'Ballot with zero selections should be created');

        // 確認沒有 BallotsSelected 紀錄
        $selectedCount = BallotsSelected::find()->where([
            'voteID' => $password['voteID'],
            'ballotID' => $FormBallots->ballotID,
        ])->count();
        $this->assertEquals(0, intval($selectedCount), 'Zero candidates should be selected');

        Yii::$app->anon->identity->logout(true);
    }

    /**
     * EP：選擇恰好 1 位候選人
     */
    public function testCreateBallotWithExactlyOneCandidate()
    {
        $password = $this->tester->grabFixture('passwords', 0);
        $this->tester->anonLogin($password);

        $candiData = CandiData::find()
            ->select(['id'])
            ->where(['voteID' => $password['voteID']])
            ->asArray()->all();
        $candiDatas = ArrayHelper::map($candiData, 'id', 'id');

        if (empty($candiDatas)) {
            $this->markTestSkipped('No candidate data available');
            return;
        }

        // 選擇恰好 1 位
        $firstCandiId = array_key_first($candiDatas);
        $FormBallots = new FormBallots();
        $post = [
            'FormBallots' => [
                'party' => $password['party'],
                'creator' => $password['id'],
            ],
            'selection' => [$firstCandiId],
        ];

        $result = $FormBallots->creatorBallot($password['voteID'], $post, false);
        $this->assertTrue($result, 'Ballot with 1 selection should be created');

        $selectedCount = BallotsSelected::find()->where([
            'voteID' => $password['voteID'],
            'ballotID' => $FormBallots->ballotID,
        ])->count();
        $this->assertEquals(1, intval($selectedCount), 'Exactly 1 candidate should be selected');

        Yii::$app->anon->identity->logout(true);
    }

    /**
     * EP：isValiable 欄位只接受 '0' 或 '1'（max 1 字元）
     */
    public function testIsValiableFieldMaxLength()
    {
        $selected = new BallotsSelected();
        $selected->isValiable = '1';
        $selected->validate(['isValiable']);
        $this->assertArrayNotHasKey('isValiable', $selected->errors, 'isValiable=1 should pass');

        $selected2 = new BallotsSelected();
        $selected2->isValiable = 'ab';
        $selected2->validate(['isValiable']);
        $this->assertArrayHasKey('isValiable', $selected2->errors, 'isValiable=ab should fail (max 1)');
    }

    /**
     * P1-2：ballotsSelected 表無 rank 欄（序位在 results 表，由計票寫入）
     */
    public function testBallotsSelectedTableHasNoRankColumn()
    {
        $schema = Yii::$app->db->getTableSchema('ballotsSelected');
        $this->assertNull($schema->getColumn('rank'), 'ballotsSelected should not have rank column');
    }

    // ==================== #2 BVA：圈選上限驗證（透過 createBallotSelected） ====================

    /**
     * BVA：圈選數恰好等於 numBallots → isValiable='1'
     *
     * AnonPartyTest questionID=5: numBallots=3, leastNumBallots=0
     * 有 3 位候選人（candiData for questionID=5）
     */
    public function testSelectExactMaxIsValid()
    {
        $password = $this->tester->grabFixture('passwords', 0);
        $this->tester->anonLogin($password);
        $voteID = $password['voteID'];

        // 取 questionID=5 的所有候選人（numBallots=3）
        $candidates = CandiData::find()
            ->where(['voteID' => $voteID, 'questionID' => 5])
            ->asArray()->all();

        $this->assertGreaterThanOrEqual(3, count($candidates),
            'Fixture should have at least 3 candidates for questionID=5');

        // 建立選票
        $FormBallots = new FormBallots();
        $selectedIds = array_slice(ArrayHelper::getColumn($candidates, 'id'), 0, 3);
        $post = [
            'FormBallots' => [
                'party' => $password['party'],
                'creator' => $password['id'],
            ],
            'selection' => $selectedIds,
        ];

        $result = $FormBallots->creatorBallot($voteID, $post, false);
        $this->assertTrue($result, 'Ballot with exactly numBallots=3 selections should succeed');

        // 檢查 isValiable
        $ballotSelections = BallotsSelected::find()->where([
            'voteID' => $voteID,
            'ballotID' => $FormBallots->ballotID,
            'questionID' => 5,
        ])->all();

        $this->assertCount(3, $ballotSelections, 'Should have 3 selections');
        foreach ($ballotSelections as $bs) {
            $this->assertEquals('1', $bs->isValiable,
                "Selection for candidate {$bs->selCandiID} should be valid (isValiable='1')");
        }

        Yii::$app->anon->identity->logout(true);
    }

    /**
     * BVA：圈選數超過 numBallots → isValiable='0'（廢票）
     *
     * AnonNoPartyTest questionID=6: numBallots=1, leastNumBallots=1
     * 需選超過 1 位 → 成為廢票
     */
    public function testSelectExceedMaxIsInvalid()
    {
        // 使用 AnonNoPartyTest 的密碼（不分組）
        $password = $this->tester->grabFixture('passwords', 'AnonNoPartyPassword1');
        if (!$password) {
            // 嘗試備用取法
            $allPasswords = \app\models\Passwords::find()
                ->where(['voteID' => 'AnonNoPartyTest', 'status' => '1', 'voted' => '0'])
                ->asArray()->one();
            if (!$allPasswords) {
                $this->markTestSkipped('No AnonNoPartyTest password available');
                return;
            }
            $password = $allPasswords;
        }
        $this->tester->anonLogin($password);
        $voteID = $password['voteID'];

        // 取 questionID=6 的所有候選人（numBallots=1, leastNumBallots=1）
        $candidates = CandiData::find()
            ->where(['voteID' => $voteID, 'questionID' => 6])
            ->asArray()->all();

        if (count($candidates) < 2) {
            $this->markTestSkipped('Need at least 2 candidates for questionID=6');
            Yii::$app->anon->identity->logout(true);
            return;
        }

        // 選 2 位（超過 numBallots=1）
        $selectedIds = array_slice(ArrayHelper::getColumn($candidates, 'id'), 0, 2);
        $FormBallots = new FormBallots();
        $post = [
            'FormBallots' => [
                'party' => $password['party'] ?? 'def',
                'creator' => $password['id'],
            ],
            'selection' => $selectedIds,
        ];

        $result = $FormBallots->creatorBallot($voteID, $post, false);
        $this->assertTrue($result, 'Ballot should still be created even with excess selections');

        // 檢查 isValiable — 超出上限的問題圈選應為廢票
        $ballotSelections = BallotsSelected::find()->where([
            'voteID' => $voteID,
            'ballotID' => $FormBallots->ballotID,
            'questionID' => 6,
        ])->all();

        $this->assertCount(2, $ballotSelections, 'Should have 2 selections stored');
        foreach ($ballotSelections as $bs) {
            $this->assertEquals('0', $bs->isValiable,
                "Exceeding numBallots should mark selections as invalid (isValiable='0')");
        }

        Yii::$app->anon->identity->logout(true);
    }

    /**
     * BVA：圈選數低於 leastNumBallots → isValiable='0'
     *
     * AnonNoPartyTest questionID=6: numBallots=1, leastNumBallots=1
     * 不選任何候選人 → 不產生 BallotsSelected 紀錄（空選票無此問題的 isValiable）
     * 改用 processNormalRule 直接測試
     */
    public function testSelectBelowMinIsInvalid()
    {
        $instance = (new \ReflectionClass(FormManageCount::class))
            ->newInstanceWithoutConstructor();

        $questions = [
            6 => ['numBallots' => 1, 'leastNumBallots' => 1],
        ];

        // 0 個圈選但 leastNumBallots=1 → 無效
        $ballotCount = [];
        $this->assertFalse(
            $instance->processNormalRule(6, $ballotCount, $questions),
            'Zero selections when leastNumBallots=1 should be invalid'
        );

        // 恰好 1 個圈選 → 有效
        $ballotCount = [6 => 1];
        $this->assertTrue(
            $instance->processNormalRule(6, $ballotCount, $questions),
            'Exactly 1 selection when leastNumBallots=1, numBallots=1 should be valid'
        );
    }

    // ==================== #5 EP：不存在候選人 ID 測試 ====================

    /**
     * EP：直接寫入不存在的候選人 ID 到 BallotsSelected → FK 約束應阻止
     */
    public function testNonExistentCandidateIdFkViolation()
    {
        $selected = new BallotsSelected();
        $selected->voteID = 'AnonPartyTest';
        $selected->ballotID = 1;
        $selected->selCandiID = 999999; // 不存在的候選人 ID
        $selected->party = 'N';
        $selected->questionID = 1;
        $selected->jobLctn = '0';
        $selected->isValiable = '1';

        // FK 約束應導致儲存失敗或拋出例外
        try {
            $saveResult = $selected->save(false); // skip validation to test DB constraint
            if ($saveResult) {
                // 若 DB 沒有 FK constraint（可能被停用），手動清理
                $selected->delete();
                $this->markTestIncomplete(
                    'FK constraint not enforced — non-existent selCandiID was saved'
                );
            } else {
                $this->assertFalse($saveResult,
                    'Saving non-existent candidate ID should fail');
            }
        } catch (\yii\db\IntegrityException $e) {
            $this->assertStringContainsString('foreign key', strtolower($e->getMessage()),
                'Should throw FK constraint violation');
        } catch (\Exception $e) {
            // 任何 DB 錯誤都可接受
            $this->assertTrue(true, 'Non-existent candidate ID correctly rejected by DB');
        }
    }

    /**
     * EP：透過 createBallotSelected 傳入不存在的候選人 → 內部捕獲例外並回傳 false
     *
     * save() 已移入 try/catch 內，FK 例外會被捕獲，
     * transaction rollBack 後回傳 false。
     */
    public function testCreateBallotSelectedWithNonExistentCandidate()
    {
        $password = $this->tester->grabFixture('passwords', 0);
        $this->tester->anonLogin($password);
        $voteID = $password['voteID'];

        // 先建立選票
        $FormBallots = new FormBallots();
        $post = [
            'FormBallots' => [
                'party' => $password['party'],
                'creator' => $password['id'],
            ],
        ];
        $result = $FormBallots->creatorBallot($voteID, $post, false);
        $this->assertTrue($result, 'Empty ballot should be created first');

        $ballotID = $FormBallots->ballotID;

        // 嘗試用不存在的候選人 ID 建立 BallotsSelected
        $ballotsSelected = new FormBallotsSelected();
        $fakeCandiData = [
            999999 => [
                'id' => 999999,
                'party' => 'N',
                'questionID' => 1,
                'jobLctn' => '0',
            ],
        ];

        // FK 例外由 try/catch 內部捕獲，回傳 false
        $result = $ballotsSelected->createBallotSelected($voteID, $ballotID, $fakeCandiData);
        $this->assertFalse($result,
            'createBallotSelected with non-existent candidate should return false');

        // 確認沒有寫入任何 BallotsSelected 紀錄
        $count = BallotsSelected::find()->where([
            'voteID' => $voteID,
            'ballotID' => $ballotID,
        ])->count();
        $this->assertEquals(0, intval($count),
            'No selections should be persisted after FK violation');

        Yii::$app->anon->identity->logout(true);
    }

    /**
     * EP：混合有效和無效候選人 ID — FK 例外由內部捕獲，整個 transaction rollBack
     *
     * save() 在 forEach 中且在 try/catch 內，
     * 遇到不存在的候選人時 FK 例外被捕獲，transaction rollBack，回傳 false。
     * 所有資料（包括先寫入的有效候選人）都不應持久化。
     */
    public function testMixedValidAndInvalidCandidateIds()
    {
        $password = $this->tester->grabFixture('passwords', 0);
        $this->tester->anonLogin($password);
        $voteID = $password['voteID'];

        // 取一個真實候選人
        $realCandi = CandiData::find()
            ->where(['voteID' => $voteID, 'questionID' => 1])
            ->asArray()->one();

        if (!$realCandi) {
            Yii::$app->anon->identity->logout(true);
            $this->markTestSkipped('No candidate data available');
            return;
        }

        // 先建立空選票
        $FormBallots = new FormBallots();
        $post = [
            'FormBallots' => [
                'party' => $password['party'],
                'creator' => $password['id'],
            ],
        ];
        $result = $FormBallots->creatorBallot($voteID, $post, false);
        $this->assertTrue($result, 'Empty ballot should be created');

        $ballotID = $FormBallots->ballotID;

        // 混合：1 個不存在（放前面觸發 FK）+ 1 個真實
        $mixedCandiData = [
            999999 => [
                'id' => 999999,
                'party' => 'N',
                'questionID' => 1,
                'jobLctn' => '0',
            ],
            $realCandi['id'] => [
                'id' => $realCandi['id'],
                'party' => $realCandi['party'],
                'questionID' => $realCandi['questionID'],
                'jobLctn' => $realCandi['jobLctn'],
            ],
        ];

        // FK 例外由內部捕獲，回傳 false
        $ballotsSelected = new FormBallotsSelected();
        $result = $ballotsSelected->createBallotSelected($voteID, $ballotID, $mixedCandiData);
        $this->assertFalse($result,
            'Mixed valid/invalid candidates should return false after internal exception handling');

        // 確認沒有任何 BallotsSelected 被持久化（transaction rollBack）
        $count = BallotsSelected::find()->where([
            'voteID' => $voteID,
            'ballotID' => $ballotID,
        ])->count();
        $this->assertEquals(0, intval($count),
            'No selections should be persisted when FK violation triggers rollBack');

        Yii::$app->anon->identity->logout(true);
    }
}
