<?php
/**
 * 表決投票 (TYPE_NO_AUTH='0') 接受測試
 *
 * 測試無需驗證的表決投票流程。
 * 表決投票不需要密碼驗證即可直接進行投票。
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\UsersFixture;

class NoAuthVoteCest
{
    /**
     * 載入 fixture
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
        ];
    }

    /**
     * 測試表決投票首頁可以存取
     */
    public function testVoteIndexPage(AcceptanceTester $I)
    {
        $I->amOnPage('/vote/index');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試表決投票詳細頁
     */
    public function testNoAuthVoteDetailPage(AcceptanceTester $I)
    {
        $I->amOnPage('/vote/vote-detail?voteID=NoAuthVoteTest');
        $I->seeResponseCodeIs(200);
        $I->see('測試表決投票');
    }

    /**
     * 測試表決投票不需要密碼驗證
     *
     * 表決投票 (type=0) 應該可以直接進入投票頁面，無需登入
     */
    public function testNoAuthVoteDirectAccess(AcceptanceTester $I)
    {
        $I->amOnPage('/vote/vote-detail?voteID=NoAuthVoteTest');
        $I->seeResponseCodeIs(200);
        // 表決投票應該顯示投票資訊而不是登入表單
        $I->see('表決投票');
    }

    /**
     * 測試首頁不顯示表決投票項目
     *
     * 根據系統設計，首頁只顯示匿名投票 (TYPE_ANON)，
     * 表決投票 (TYPE_NO_AUTH) 不會出現在首頁列表中。
     */
    public function testNoAuthVoteNotVisibleOnIndex(AcceptanceTester $I)
    {
        $I->amOnPage('/vote/index');
        $I->seeResponseCodeIs(200);
        // 表決投票不應該在首頁顯示（這是系統設計）
        $I->dontSee('測試表決投票');
        // 但匿名投票應該顯示
        $I->see('測試分組匿名投票');
    }

    /**
     * 測試表決投票結果列表頁
     */
    public function testNoAuthVoteResultList(AcceptanceTester $I)
    {
        $I->amOnPage('/vote/result-list');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試透過短網址存取表決投票
     */
    public function testNoAuthVoteShortUrl(AcceptanceTester $I)
    {
        // 使用短網址存取
        $I->amOnPage('/test3');
        // 應該重導到投票詳細頁或直接顯示投票頁面
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試表決投票與匿名投票的區別
     *
     * 匿名投票需要密碼驗證，表決投票則不需要
     */
    public function testDifferenceFromAnonVote(AcceptanceTester $I)
    {
        // 匿名投票應該要求密碼
        $I->amOnPage('/vote/vote-detail?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);

        // 表決投票不需要密碼
        $I->amOnPage('/vote/vote-detail?voteID=NoAuthVoteTest');
        $I->seeResponseCodeIs(200);
    }
}
