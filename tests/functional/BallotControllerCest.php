<?php
/**
 * BallotController 功能測試
 * 
 * 使用方式：
 * 1. 確認已建立 tests/fixtures/VotesFixture.php、BallotsFixture.php 並有對應資料。
 * 2. 執行：vendor/bin/codecept run functional
 * 3. 若測試資料、欄位或顯示內容不同，請依專案實際情況調整。
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\BallotsSelectedFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\ResultsConfigFixture;

class BallotControllerCest
{
    /**
     * 載入投票與投票紀錄 fixture
     */
    public function _fixtures()
    {
        return [
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
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
    }

    /**
     * 測試顯示投票頁
     */
    public function testShowBallotPage(FunctionalTester $I)
    {
        $I->amOnPage('/ballot/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('投票');
    }

    /**
     * 測試選票匯入頁（兩場合併計票，非代投）
     */
    public function testImportPage(FunctionalTester $I)
    {
        $I->amOnPage('/ballot/import?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('合併計票');
        $I->see('代為輸入');
    }

    /**
     * 測試選票列印頁（P2-4）
     */
    public function testPrintPage(FunctionalTester $I)
    {
        $I->amOnPage('/ballot/print?voteID=AnonPartyTest&questionID=1');
        $I->seeResponseCodeIsSuccessful();
    }


    /**
     * 代為輸入欄可查、可篩
     */
    public function testIndexShowsAdminAddColumn(FunctionalTester $I)
    {
        $I->amOnPage('/ballot/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('代為輸入');
        $I->see('建立選票');
        $I->see('匯入選票');
        $I->seeElement('select[name="FormBallots[isAdminAdd]"]');
    }

    /**
     * 選票列表可篩「代為輸入＝是」
     */
    public function testIndexFiltersAdminAdd(FunctionalTester $I)
    {
        $I->amOnPage('/ballot/index?voteID=AnonPartyTest&FormBallots[isAdminAdd]=1');
        $I->seeResponseCodeIs(200);
        $I->seeElement('select[name="FormBallots[isAdminAdd]"] option[value="1"][selected]');
    }

    /**
     * 選票統計匯出未帶管理員密碼時不得取得檔案
     */
    public function testExportRequiresReauth(FunctionalTester $I)
    {
        putenv('SENSITIVE_REAUTH=true');
        $I->amOnPage('/ballot/index?voteID=AnonPartyTest');
        $I->submitForm('#ballotExportForm-collapseBallotExport', [
            'type' => '1',
            'exportAcknowledged' => '1',
            'adminPassword' => '',
        ]);
        $I->seeResponseCodeIsSuccessful();
        $I->see('請輸入正確的管理員密碼');
    }

    /**
     * C6.4 公開結果頁不得出現投票密碼明文或 ballots.creator
     */
    public function testPublicResultOmitsCredentials(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/vote/result?voteID=AnonPartyTest');
        $I->dontSeeInSource('testN1');
        $I->dontSeeInSource('testdef1');
        $I->dontSeeInSource('ballots.creator');
    }

}
