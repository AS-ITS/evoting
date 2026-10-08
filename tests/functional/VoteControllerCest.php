<?php
/**
 * VoteController 功能測試
 *
 * 測試內容涵蓋投票詳細頁、列表等基本功能。
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\BallotsSelectedFixture;

class VoteControllerCest
{
    /**
     * 載入投票資料 fixture
     */
    public function _fixtures()
    {
        return [
            'users' => UsersFixture::class,
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'round' => RoundFixture::class,
            'questions' => QuestionsFixture::class,
            'candiConfig' => CandiConfigFixture::class,
            'candiData' => CandiDataFixture::class,
            'passwords' => PasswordsFixture::class,
            'ballots' => BallotsFixture::class,
            'ballotsSelected' => BallotsSelectedFixture::class,
        ];
    }

    /**
     * 測試不分組匿名投票詳細頁
     */
    public function testVoteDetailAnonNoParty(FunctionalTester $I)
    {
        $I->amOnPage('/vote/vote-detail?voteID=AnonNoPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('測試不分組匿名投票');
    }

    /**
     * 測試投票列表頁
     */
    public function testIndexPage(FunctionalTester $I)
    {
        $I->amOnPage('/vote/index');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 短網址應導向 vote-detail（P4 加深）
     */
    public function testShortUrlRedirectsToVoteDetail(FunctionalTester $I)
    {
        $vote = \app\models\Votes::findOne(['voteID' => 'AnonPartyTest']);
        \PHPUnit\Framework\Assert::assertNotNull($vote, 'AnonPartyTest fixture vote must exist');
        \PHPUnit\Framework\Assert::assertNotEmpty($vote->shortUrl);

        $I->amOnPage('/' . $vote->shortUrl);
        $I->seeInCurrentUrl('vote/vote-detail');
        $I->seeInCurrentUrl('voteID=AnonPartyTest');
    }

    /**
     * 測試投票結果列表頁
     */
    public function testResultList(FunctionalTester $I)
    {
        $I->amOnPage('/vote/result-list');
        $I->seeResponseCodeIs(200);
    }
}
