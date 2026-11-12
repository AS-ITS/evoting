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

    /**
     * 表決投票開啟「略過圈選結果頁」時，完成頁不應取用已移除的 voter 元件
     */
    public function testVoteFinishSkipCheckNoAuth(FunctionalTester $I)
    {
        \app\models\Votes::updateAll(['skipCheck' => '1'], ['voteID' => 'NoAuthVoteTest']);

        $I->amOnPage('/vote/vote-finish?voteID=NoAuthVoteTest');
        $I->seeResponseCodeIs(200);
        $I->see('投票完成');
        $I->seeInSource('vote\/index"');
        $I->dontSeeInSource('check-ballot');
    }

    /**
     * 匿名投票開啟「略過圈選結果頁」時，完成頁導向 finishPage 設定的頁面
     */
    public function testVoteFinishSkipCheckAnon(FunctionalTester $I)
    {
        \app\models\Votes::updateAll(['skipCheck' => '1'], ['voteID' => 'AnonNoPartyTest']);

        $I->amOnPage('/vote/vote-finish?voteID=AnonNoPartyTest');
        $I->seeResponseCodeIs(200);
        $I->seeInSource('vote\/index"');
        $I->dontSeeInSource('check-ballot');
    }

    /**
     * 未略過圈選結果頁時，完成頁導向圈選結果頁
     */
    public function testVoteFinishRedirectsToCheckBallot(FunctionalTester $I)
    {
        $I->amOnPage('/vote/vote-finish?voteID=AnonNoPartyTest');
        $I->seeResponseCodeIs(200);
        $I->seeInSource('check-ballot?voteID=AnonNoPartyTest"');
    }

    /**
     * 主題色格式不符（舊資料）時不得原樣寫入 <style>
     */
    public function testUnsafeThemeColorIsNotRendered(FunctionalTester $I)
    {
        $payload = '</style><script src=//x.io>';
        \app\models\Votes::updateAll(['themeColor' => $payload], ['voteID' => 'AnonNoPartyTest']);

        $I->amOnPage('/vote/vote-finish?voteID=AnonNoPartyTest');
        $I->seeResponseCodeIs(200);
        $I->dontSeeInSource($payload);
        $I->seeInSource('background-color: #ffffff');

        $I->amOnPage('/site/password?voteID=AnonNoPartyTest');
        $I->seeResponseCodeIs(200);
        $I->dontSeeInSource($payload);
    }

    /**
     * 合法的主題色照常套用
     */
    public function testValidThemeColorIsRendered(FunctionalTester $I)
    {
        \app\models\Votes::updateAll(['themeColor' => '#abcdef'], ['voteID' => 'AnonNoPartyTest']);

        $I->amOnPage('/vote/vote-finish?voteID=AnonNoPartyTest');
        $I->seeInSource('background-color: #abcdef');
    }
}
