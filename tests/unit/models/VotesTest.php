<?php

use app\models\Votes;
use app\models\Parties;
use app\models\Round;
use app\models\Questions;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

class VotesTest extends \Codeception\Test\Unit
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
    public function _fixtures()
    {
        return [
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'round' => RoundFixture::class,
            'questions' => QuestionsFixture::class,
        ];
    }

    /**
     * 測試 Votes 模型的基本結構
     */
    public function testVotesModelStructure()
    {
        $vote = new Votes();
        $this->assertNotNull($vote);
        $this->assertEquals('votes', Votes::tableName());
    }

    /**
     * 測試 Votes 常數定義
     */
    public function testVotesConstants()
    {
        // 投票類型
        $this->assertEquals('0', Votes::TYPE_NO_AUTH);
        $this->assertEquals('1', Votes::TYPE_ANON);
        // TYPE_VOTER (記名投票) 已於 2026-01-02 移除
        // $this->assertEquals('2', Votes::TYPE_VOTER);

        // 投票狀態
        $this->assertEquals('0', Votes::STATUS_READY);
        $this->assertEquals('1', Votes::STATUS_ACTIVE);
        $this->assertEquals('2', Votes::STATUS_TERMINATE);
        $this->assertEquals('3', Votes::STATUS_BACKFILL);

        // 候選人配置
        $this->assertEquals('2', Votes::CANDI_CONFIG_BY_Q);
    }

    /**
     * 測試 Votes 必填欄位驗證
     */
    public function testRequiredFieldsValidation()
    {
        $vote = new Votes();
        $this->assertFalse($vote->validate());

        // 檢查必填欄位錯誤
        $this->assertArrayHasKey('creator', $vote->errors);
        $this->assertArrayHasKey('voteID', $vote->errors);
        $this->assertArrayHasKey('Name', $vote->errors);
        $this->assertArrayHasKey('type', $vote->errors);
        $this->assertArrayHasKey('openStart', $vote->errors);
        $this->assertArrayHasKey('openEnd', $vote->errors);
    }

    /**
     * 測試 voteID 唯一性驗證
     */
    public function testVoteIdUniqueness()
    {
        $vote1 = $this->tester->grabFixture('votes', 'AnonPartyTest');

        // 嘗試創建相同 voteID 的投票
        $vote2 = new Votes();
        $vote2->attributes = $vote1->attributes;
        $vote2->voteID = $vote1->voteID;

        $this->assertFalse($vote2->save());
        $this->assertArrayHasKey('voteID', $vote2->errors);
    }

    /**
     * 測試 shortUrl 唯一性驗證
     */
    public function testShortUrlUniqueness()
    {
        $vote1 = $this->tester->grabFixture('votes', 'AnonPartyTest');

        // 創建新投票但使用相同的 shortUrl
        $vote2 = new Votes();
        $vote2->attributes = $vote1->attributes;
        $vote2->voteID = 'NewVoteID123';
        $vote2->shortUrl = $vote1->shortUrl;

        $this->assertFalse($vote2->save());
        $this->assertArrayHasKey('shortUrl', $vote2->errors);
    }

    /**
     * 測試字串長度驗證
     */
    public function testStringLengthValidation()
    {
        $vote = new Votes();

        // voteID 最大長度 20
        $vote->voteID = str_repeat('a', 21);
        $vote->validate(['voteID']);
        $this->assertArrayHasKey('voteID', $vote->errors);

        // shortUrl 最大長度 8
        $vote->shortUrl = str_repeat('b', 9);
        $vote->validate(['shortUrl']);
        $this->assertArrayHasKey('shortUrl', $vote->errors);

        // themeColor 最大長度 30
        $vote->themeColor = str_repeat('c', 31);
        $vote->validate(['themeColor']);
        $this->assertArrayHasKey('themeColor', $vote->errors);
    }

    /**
     * 測試取得投票組別關聯
     */
    public function testGetPartiesRelation()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $parties = $vote->parties;

        $this->assertNotEmpty($parties);
        $this->assertIsArray($parties);
        foreach ($parties as $party) {
            $this->assertInstanceOf(Parties::class, $party);
            $this->assertEquals($vote->voteID, $party->voteID);
        }
    }

    /**
     * 測試取得輪次關聯
     */
    public function testGetRoundsRelation()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $rounds = $vote->rounds;

        $this->assertNotEmpty($rounds);
        $this->assertIsArray($rounds);
        foreach ($rounds as $round) {
            $this->assertInstanceOf(Round::class, $round);
            $this->assertEquals($vote->voteID, $round->voteID);
        }
    }

    /**
     * 測試取得當前輪次
     */
    public function testGetCurrentRound()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $currentRound = $vote->currentRound;

        $this->assertNotNull($currentRound);
        $this->assertInstanceOf(Round::class, $currentRound);
        $this->assertEquals($vote->voteID, $currentRound->voteID);
        $this->assertEquals($vote->round, $currentRound->round);
    }

    /**
     * 測試取得問題關聯
     */
    public function testGetQuestionsRelation()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $questions = $vote->questions;

        $this->assertNotEmpty($questions);
        $this->assertIsArray($questions);
        foreach ($questions as $question) {
            $this->assertInstanceOf(Questions::class, $question);
            $this->assertEquals($vote->voteID, $question->voteID);
        }
    }

    /**
     * 測試取得投票名稱（中文）
     */
    public function testGetVoteNameZhTw()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $voteName = $vote->getVoteName(false, false, 'zh-tw');

        $this->assertNotEmpty($voteName);
        $this->assertStringContainsString('測試分組匿名投票', $voteName);
    }

    /**
     * 測試取得投票名稱（英文）
     */
    public function testGetVoteNameEnUs()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $voteName = $vote->getVoteName(false, false, 'en-us');

        $this->assertNotEmpty($voteName);
        $this->assertStringContainsString('Test Party Anon', $voteName);
    }

    /**
     * 測試取得投票名稱（雙語，預設）
     */
    public function testGetVoteNameBilingual()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $voteName = $vote->getVoteName(false, false, false);

        $this->assertNotEmpty($voteName);
        // 預設行為應該根據系統語言返回適當的名稱
        $this->assertIsString($voteName);
    }

    /**
     * 測試取得投票問題
     */
    public function testGetVoteQuestion()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $questions = $vote->getVoteQuestion($vote->voteID, 1, 'zh-tw');

        $this->assertIsArray($questions);
        $this->assertNotEmpty($questions);

        // 檢查格式: questionID => title
        foreach ($questions as $questionID => $title) {
            $this->assertIsInt($questionID);
            $this->assertIsString($title);
        }
    }

    /**
     * 測試 newSort 方法
     */
    public function testNewSort()
    {
        $vote = new Votes();
        $newSort = $vote->newSort();

        $this->assertIsInt($newSort);
        $this->assertGreaterThan(0, $newSort);
    }

    /**
     * 測試檢查投票是否尚未開始
     */
    public function testCheckVoteReady()
    {
        $vote = new Votes();
        $vote->active = Votes::STATUS_READY;
        $vote->openStart = date('Y-m-d H:i:s', strtotime('+1 day'));

        $result = $vote->checkVoteReady();
        $this->assertTrue($result);
    }

    /**
     * 測試檢查投票已開始
     */
    public function testCheckVoteNotReady()
    {
        $vote = new Votes();
        $vote->active = Votes::STATUS_ACTIVE;
        $vote->openStart = date('Y-m-d H:i:s', strtotime('-1 day'));

        $result = $vote->checkVoteReady();
        $this->assertFalse($result);
    }

    /**
     * 測試檢查投票是否已結束
     */
    public function testCheckVoteExpired()
    {
        $vote = new Votes();
        $vote->active = Votes::STATUS_TERMINATE;
        $vote->openEnd = date('Y-m-d H:i:s', strtotime('-1 day'));

        $result = $vote->checkVoteExpired();
        $this->assertTrue($result);
    }

    /**
     * 測試檢查投票未結束
     */
    public function testCheckVoteNotExpired()
    {
        $vote = new Votes();
        $vote->active = Votes::STATUS_ACTIVE;
        $vote->openEnd = date('Y-m-d H:i:s', strtotime('+1 day'));

        $result = $vote->checkVoteExpired();
        $this->assertFalse($result);
    }

    /**
     * 測試取得民國年日期
     */
    public function testGetRocDate()
    {
        $vote = new Votes();

        // 測試 2024-01-15 應該是民國113年1月15日
        $rocDate = $vote->getRocDate('2024-01-15 10:30:00');
        $this->assertEquals('中華民國113年1月15日', $rocDate);

        // 測試 2023-03-05 應該是民國112年3月5日
        $rocDate = $vote->getRocDate('2023-03-05');
        $this->assertEquals('中華民國112年3月5日', $rocDate);
    }

    /**
     * 測試取得完成頁面 URL - 首頁
     */
    public function testGetFinishPageUrlIndex()
    {
        $vote = new Votes();
        $vote->finishPage = '1';

        $url = $vote->getFinishPageUrl();
        $this->assertEquals(['index'], $url);
    }

    /**
     * 測試取得完成頁面 URL - 投票詳情
     */
    public function testGetFinishPageUrlVoteDetail()
    {
        $vote = new Votes();
        $vote->voteID = 'test123';
        $vote->finishPage = 'value';

        $url = $vote->getFinishPageUrl();
        $this->assertEquals(['vote/vote-detail', 'voteID' => 'test123'], $url);
    }

    /**
     * 測試取得完成頁面 URL - 預設
     */
    public function testGetFinishPageUrlDefault()
    {
        $vote = new Votes();
        $vote->voteID = 'test456';
        $vote->finishPage = 'other';

        $url = $vote->getFinishPageUrl();
        $this->assertEquals(['vote/vote-detail', 'voteID' => 'test456'], $url);
    }

    /**
     * 測試取得投票組別
     */
    public function testGetVoteParty()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $parties = $vote->getVoteParty($vote->voteID, 'zh-tw');

        $this->assertIsArray($parties);
        $this->assertNotEmpty($parties);

        // 檢查格式: party code => party name
        // 注意: numeric string keys like '0', '1' 可能被 PHP 轉為 int
        foreach ($parties as $partyCode => $partyName) {
            $this->assertTrue(is_string($partyCode) || is_int($partyCode));
            $this->assertIsString($partyName);
        }
    }

    /**
     * 測試取得投票組別（包含共同投票）
     */
    public function testGetVotePartyWithAll()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $parties = $vote->getVoteParty($vote->voteID, 'zh-tw', true);

        $this->assertIsArray($parties);
        $this->assertNotEmpty($parties);
        $this->assertArrayHasKey(Questions::ALL_PARTY_CODE, $parties);
    }

    /**
     * 測試 replaceQuestionRule 方法
     */
    public function testReplaceQuestionRule()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        // 假設有問題 ID 為 1 的 title 是 '測試問題'
        $questions = Questions::find()
            ->where(['voteID' => $vote->voteID])
            ->indexBy('questionID')
            ->asArray()
            ->all();

        if (empty($questions)) {
            $this->markTestSkipped('No questions found for this vote');
        }

        $firstQuestionId = array_key_first($questions);
        $text = "這是測試文字 {%{$firstQuestionId}_title%} 結束";
        $result = $vote->replaceQuestionRule($text, $questions);

        $this->assertStringNotContainsString("{%{$firstQuestionId}_title%}", $result);
        $this->assertStringContainsString($questions[$firstQuestionId]['title'], $result);
    }

    /**
     * 測試 getOpenVote 方法
     */
    public function testGetOpenVote()
    {
        $vote = new Votes();
        $openVotes = $vote->getOpenVote()->all();

        $this->assertIsArray($openVotes);
        foreach ($openVotes as $v) {
            $this->assertEquals('1', $v->active);
            $this->assertEquals('0', $v->isFinish);
        }
    }

    /**
     * 測試 getAllVote 方法
     */
    public function testGetAllVote()
    {
        $vote = new Votes();
        $allVotes = $vote->getAllVote()->all();

        $this->assertIsArray($allVotes);
        $this->assertNotEmpty($allVotes);
    }

    /**
     * 測試 getOneVote 方法
     */
    public function testGetOneVote()
    {
        $voteFixture = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $vote = new Votes();
        $foundVote = $vote->getOneVote($voteFixture->voteID);

        $this->assertNotNull($foundVote);
        $this->assertInstanceOf(Votes::class, $foundVote);
        $this->assertEquals($voteFixture->voteID, $foundVote->voteID);
    }

    /**
     * 測試 getVoteInfo 方法
     */
    public function testGetVoteInfo()
    {
        $voteFixture = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $vote = new Votes();
        $voteInfo = $vote->getVoteInfo($voteFixture->voteID)->one();

        $this->assertNotNull($voteInfo);
        $this->assertInstanceOf(Votes::class, $voteInfo);
        $this->assertEquals($voteFixture->voteID, $voteInfo->voteID);
    }

    /**
     * 測試取得輪次徽章
     */
    public function testGetRoundBadge()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $badge = $vote->getRoundBadge();

        $this->assertIsString($badge);
        $this->assertStringContainsString('輪次', $badge);
        $this->assertStringContainsString((string)$vote->round, $badge);
    }

    /**
     * 測試取得投票編號徽章
     */
    public function testGetVoteIdBadge()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $badge = $vote->getVoteIdBadge();

        $this->assertIsString($badge);
        $this->assertStringContainsString($vote->voteID, $badge);
    }

    /**
     * 測試取得投票狀態徽章
     */
    public function testGetStatusBadge()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
        $badge = $vote->getStatusBadge();

        $this->assertIsString($badge);
        $this->assertStringContainsString('投票狀態', $badge);
    }

    // ==================== BVA：字串長度邊界值測試 ====================

    /**
     * BVA：voteID 恰好 20 字元應通過驗證（max 邊界）
     */
    public function testVoteIdExactMaxLength()
    {
        $vote = new Votes();
        $vote->voteID = str_repeat('a', 20);
        $vote->validate(['voteID']);
        $this->assertArrayNotHasKey('voteID', $vote->errors, 'voteID at max length (20) should pass');
    }

    /**
     * BVA：shortUrl 恰好 8 字元應通過驗證（max 邊界）
     */
    public function testShortUrlExactMaxLength()
    {
        $vote = new Votes();
        $vote->shortUrl = str_repeat('b', 8);
        $vote->validate(['shortUrl']);
        $this->assertArrayNotHasKey('shortUrl', $vote->errors, 'shortUrl at max length (8) should pass');
    }

    /**
     * BVA：shortUrl 1 字元應通過驗證（min 邊界）
     */
    public function testShortUrlMinLength()
    {
        $vote = new Votes();
        $vote->shortUrl = 'a';
        $vote->validate(['shortUrl']);
        $this->assertArrayNotHasKey('shortUrl', $vote->errors, 'shortUrl with 1 char should pass');
    }

    /**
     * BVA：themeColor 恰好 30 字元應通過驗證（max 邊界）
     */
    public function testThemeColorExactMaxLength()
    {
        $vote = new Votes();
        $vote->themeColor = str_repeat('c', 30);
        $vote->validate(['themeColor']);
        $this->assertArrayNotHasKey('themeColor', $vote->errors, 'themeColor at max length (30) should pass');
    }

    // ==================== EP：無效投票類型測試 ====================

    /**
     * EP：確認 TYPE_VOTER (記名投票) 常數已移除
     */
    public function testTypeVoterConstantRemoved()
    {
        $this->assertFalse(
            defined('app\models\Votes::TYPE_VOTER'),
            'TYPE_VOTER constant should have been removed'
        );
    }

    /**
     * EP：記名投票 type=2 不在允許的投票類型清單中
     */
    public function testTypeVoterNotInAllowedVoteTypes()
    {
        $voteTypes = Yii::$app->params['ct.voteType'] ?? [];
        $this->assertArrayHasKey(Votes::TYPE_NO_AUTH, $voteTypes);
        $this->assertArrayHasKey(Votes::TYPE_ANON, $voteTypes);
        $this->assertArrayNotHasKey('2', $voteTypes, 'Named voting type 2 should not be allowed');
    }

    /**
     * EP：log.type 應包含管理員修改密碼 (418)
     */
    public function testLogType418Defined()
    {
        $this->assertArrayHasKey('418', Yii::$app->params['log.type']);
        $this->assertNotEmpty(Yii::$app->params['log.type']['418']);
    }

    /**
     * EP：無效的 active 狀態值
     */
    public function testInvalidActiveStatusValues()
    {
        $validStatuses = [
            Votes::STATUS_READY,
            Votes::STATUS_ACTIVE,
            Votes::STATUS_TERMINATE,
            Votes::STATUS_BACKFILL,
        ];

        // 確認有效狀態值
        $this->assertContains('0', $validStatuses);
        $this->assertContains('1', $validStatuses);
        $this->assertContains('2', $validStatuses);
        $this->assertContains('3', $validStatuses);

        // 確認無效值不在合法狀態中
        $this->assertNotContains('4', $validStatuses, 'Status 4 should be invalid');
        $this->assertNotContains('-1', $validStatuses, 'Status -1 should be invalid');
    }

    // ==================== BVA：日期邊界測試 ====================

    /**
     * BVA：openStart 等於 openEnd（邊界條件）
     */
    public function testOpenStartEqualsOpenEnd()
    {
        $vote = new Votes();
        $sameTime = date('Y-m-d H:i:s');
        $vote->openStart = $sameTime;
        $vote->openEnd = $sameTime;
        $vote->active = Votes::STATUS_READY;

        // openStart == openEnd 時，投票時間為零，checkVoteReady 應為 true（尚未開始）
        $result = $vote->checkVoteReady();
        $this->assertTrue($result, 'Vote with openStart == openEnd and STATUS_READY should be ready');
    }

    /**
     * BVA：不合法日期格式的 ROC 日期轉換
     */
    public function testGetRocDateWithEmptyInput()
    {
        $vote = new Votes();

        // 空字串
        $result = $vote->getRocDate('');
        $this->assertIsString($result, 'getRocDate with empty string should return string');
    }

    // ==================== #10 BVA：日期邊界補強 ====================

    /**
     * BVA：verifyStart 等於 openEnd（相鄰時間邊界）
     */
    public function testVerifyStartEqualsOpenEnd()
    {
        $vote = new Votes();
        $now = date('Y-m-d H:i:s');
        $vote->openStart = $now;
        $vote->openEnd = date('Y-m-d H:i:s', strtotime($now . '+1 hour'));
        $vote->verifyStart = $vote->openEnd; // 驗證開始 = 投票結束
        $vote->verifyEnd = date('Y-m-d H:i:s', strtotime($vote->verifyStart . '+1 hour'));

        // safe rule 不驗證格式，只確認不會報錯
        $vote->validate(['openStart', 'openEnd', 'verifyStart', 'verifyEnd']);
        $this->assertArrayNotHasKey('verifyStart', $vote->errors,
            'verifyStart == openEnd should be accepted (safe rule)');
    }

    /**
     * EP：不合法日期格式寫入（safe rule 允許任何字串）
     */
    public function testInvalidDateFormatAcceptedBySafeRule()
    {
        $vote = new Votes();
        $vote->openStart = 'not-a-date';
        $vote->openEnd = '2024-13-45'; // 不合法月份/日期

        // safe rule 不做格式驗證
        $vote->validate(['openStart', 'openEnd']);
        $this->assertArrayNotHasKey('openStart', $vote->errors,
            'Safe rule should accept any string including invalid date');
        $this->assertArrayNotHasKey('openEnd', $vote->errors,
            'Safe rule should accept any string including invalid date');
    }

    /**
     * EP：不合法日期格式對 checkVoteReady() 的影響
     */
    public function testCheckVoteReadyWithInvalidDate()
    {
        $vote = new Votes();
        $vote->active = Votes::STATUS_READY;
        $vote->openStart = 'invalid-date';

        // strtotime('invalid-date') returns false, 比較結果可能異常
        $result = $vote->checkVoteReady();
        // STATUS_READY 時第一個條件直接返回 true，不依賴日期
        $this->assertTrue($result,
            'checkVoteReady with STATUS_READY should return true regardless of date');
    }

    /**
     * EP：不合法日期格式對 checkVoteExpired() 的影響
     */
    public function testCheckVoteExpiredWithInvalidDate()
    {
        $vote = new Votes();
        $vote->active = Votes::STATUS_ACTIVE;
        $vote->openEnd = 'invalid-date';

        // strtotime('invalid-date') returns false → 比較: time() > false → true
        $result = $vote->checkVoteExpired();
        // 進行中的投票若 openEnd 不合法，strtotime 回傳 false，比較時為 true
        $this->assertIsBool($result, 'Should return boolean even with invalid date');
    }

    /**
     * BVA：getRocDate 不合法日期格式
     */
    public function testGetRocDateWithInvalidFormat()
    {
        $vote = new Votes();

        $result = $vote->getRocDate('not-a-date');
        $this->assertIsString($result, 'getRocDate with invalid date should still return string');
    }

    /**
     * BVA：getRocDate 含不合法月份/日期
     */
    public function testGetRocDateWithInvalidMonthDay()
    {
        $vote = new Votes();

        $result = $vote->getRocDate('2024-13-45 00:00:00');
        $this->assertIsString($result, 'getRocDate with invalid month/day should return string');
    }
}
