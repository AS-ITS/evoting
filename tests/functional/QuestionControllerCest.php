<?php
/**
 * QuestionController 功能測試
 *
 * 測試問題管理功能，包含問題列表、新增、編輯、刪除、輪次匯入等操作。
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\UsersFixture;

class QuestionControllerCest
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
     * 每個測試前執行 - 模擬管理員登入
     */
    public function _before(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
    }

    /**
     * 測試問題管理首頁 - 分組投票
     */
    public function testIndexPageWithParty(FunctionalTester $I)
    {
        $I->amOnPage('/question/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('問題');
    }

    /**
     * 測試問題管理首頁 - 不分組投票
     */
    public function testIndexPageNoParty(FunctionalTester $I)
    {
        $I->amOnPage('/question/index?voteID=AnonNoPartyTest');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試新增問題頁面
     */
    public function testCreatePage(FunctionalTester $I)
    {
        $I->amOnPage('/question/create?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('新增');
    }

    /**
     * 測試編輯問題頁面
     */
    public function testUpdatePage(FunctionalTester $I)
    {
        // 使用 fixture 中的問題 ID
        $I->amOnPage('/question/update?voteID=AnonPartyTest&questionID=1');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試輪次匯入頁面
     */
    public function testRoundImportPage(FunctionalTester $I)
    {
        $I->amOnPage('/question/round-import?voteID=AnonPartyTest&round=1');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試取得分組資訊 (AJAX)
     */
    public function testGetPartyInfo(FunctionalTester $I)
    {
        $I->sendAjaxGetRequest('/question/get-party-info?voteID=AnonPartyTest&party=A');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試取得分組問題 (AJAX)
     */
    public function testGetPartyQuestions(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/question/get-party-questions?voteID=AnonPartyTest&round=1', [
            'party' => 'N'
        ]);
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試未登入時無法存取問題管理
     */
    public function testUnauthorizedAccess(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/question/index?voteID=AnonPartyTest');
        // 確認用戶無法看到問題管理內容
        $I->dontSee('問題');
    }

    /**
     * 測試表決投票的問題管理
     */
    public function testNoAuthVoteQuestions(FunctionalTester $I)
    {
        $I->amOnPage('/question/index?voteID=NoAuthVoteTest');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試新增問題後能在列表中看到（跨動作一致性）
     * 防止 round 未設定導致問題消失的 bug
     */
    public function testStoreQuestionAppearsInIndex(FunctionalTester $I)
    {
        $I->amOnPage('/question/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);

        // store 成功後會 redirect (302)，黨組使用 fixture 中存在的 party='0'
        $I->sendAjaxPostRequest('/question/store?voteID=AnonPartyTest', [
            'Questions' => [
                'party'           => ['0'],
                'title'           => '跨動作測試問題',
                'titleE'          => 'Cross Action Test',
                'numBallots'      => '3',
                'leastNumBallots' => '1',
                'maxElect'        => '1',
                'numOfKeep'       => '0',
                'population'      => '0',
                'voteID'          => 'AnonPartyTest',
            ],
        ]);
        $I->seeResponseCodeIs(302);

        $I->amOnPage('/question/index?voteID=AnonPartyTest');
        $I->see('跨動作測試問題');
    }
}
