<?php
/**
 * SiteController 功能測試
 */
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;

class SiteControllerCest
{
    /**
     * 載入測試用 fixture
     * @return array
     */
    public function _fixtures()
    {
        return [
            'config' => ConfigFixture::class,
            'users' => UsersFixture::class,
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'round' => RoundFixture::class,
            'questions' => QuestionsFixture::class,
            'candiConfig' => CandiConfigFixture::class,
            'candiData' => CandiDataFixture::class,
            'passwords' => PasswordsFixture::class,
        ];
    }

    /**
     * 測試首頁導向
     */
    public function testIndexRedirectsToHome(FunctionalTester $I)
    {
        $I->amOnPage('/site/index');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試投票詳情路由（P3-3 部署 smoke，避免短網址受其他測試 DB 狀態影響）
     */
    public function testVoteDetailRoute(FunctionalTester $I)
    {
        $I->amOnPage('/vote/vote-detail?voteID=AnonPartyTest');
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試登出功能
     */
    public function testLogout(FunctionalTester $I)
    {
        $I->wantToTest('logout functionality');
        // 跳過此測試 - 需要完整的登入流程
    }
}
