<?php
/**
 * BallotWorkController 功能測試
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\BallotsSelectedFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\ResultsConfigFixture;
use app\models\FormResults;

class BallotWorkControllerCest
{
    /**
     * 載入投票 fixture
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
            'resultsConfig' => ResultsConfigFixture::class,
        ];
    }

    /**
     * 每個測試前執行 - 模擬管理員登入
     */
    public function _before(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $formResults = new FormResults();
        if (!$formResults->isResults('AnonPartyTest', 1)) {
            $formResults->createResults('AnonPartyTest');
        }
    }

    /**
     * 測試開票作業首頁
     */
    public function testIndex(FunctionalTester $I)
    {
        $I->amOnPage('/ballot-work/index');
        $I->seeResponseCodeIs(200);
        $I->see('開票作業');
    }

    /**
     * 檢票頁選票統計匯出表單須 POST 到 ballot/export（相對路徑會變成 404）
     */
    public function testStatusExportPostsToBallotExport(FunctionalTester $I)
    {
        $I->amOnPage('/ballot-work/status?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('選票統計匯出');
        $I->seeInSource('ballot/export');
        $I->dontSeeInSource('ballot-work/export');
    }

    /**
     * 測試計票顯示頁
     */
    public function testCount(FunctionalTester $I)
    {
        $I->amOnPage('/ballot-work/count?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('計票');
    }

    /**
     * 測試投票結果頁
     */
    public function testResult(FunctionalTester $I)
    {
        $I->amOnPage('/ballot-work/result?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('投票結果');
        $I->dontSeeInSource('webix.min.js');
        $I->seeInSource('批次操作失敗');
    }

    /**
     * 測試選票詳細內容 AJAX
     */
    public function testBallotsDetailAjax(FunctionalTester $I)
    {
        // 先訪問計票頁面以建立 session 和 history
        $I->amOnPage('/ballot-work/count?voteID=AnonPartyTest');
        $I->sendAjaxGetRequest('/ballot-work/ballots-detail?voteID=AnonPartyTest&party=A&questionID=1&valid=1');
        $I->seeResponseCodeIs(200);
    }
}
