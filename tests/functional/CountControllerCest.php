<?php
/**
 * CountController 功能測試
 *
 * 使用方式：
 * 1. 確認已建立 tests/fixtures/VotesFixture.php、FormCandiConfigFixture.php 等，並有對應資料。
 * 2. 執行：vendor/bin/codecept run functional
 * 3. 若測試資料、欄位或顯示內容不同，請依專案實際情況調整。
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\BallotsSelectedFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\ResultsConfigFixture;
use app\models\FormManageCount;
use app\models\FormBallots;
use app\models\FormManageVote;
use app\models\Passwords;
use app\models\Parties;

class CountControllerCest
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
            'ballots' => BallotsFixture::class,
            'ballotsSelected' => BallotsSelectedFixture::class,
            'passwords' => PasswordsFixture::class,
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
     * 測試開票設定頁
     */
    public function testResultPage(FunctionalTester $I)
    {
        $I->amOnPage('/count/result?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('開票設定');
    }

    /**
     * 測試計票單頁
     */
    public function testIndexPage(FunctionalTester $I)
    {
        $I->amOnPage('/count/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('計票單');
        $I->dontSeeInSource('webix.min.js');
        $I->seeInSource('print-sign-modal');
        $I->seeInSource('請選擇簽名欄位');
        $I->seeInSource('data-sign-layout');
        $I->seeInSource('printSignModalEl');
        $I->seeInSource('btn-print-sign');
    }

    /**
     * 測試計票單 CSV 匯出（P2-1）
     *
     * @dataProvider exportSortProvider
     */
    public function testExportBallotCountCsv(FunctionalTester $I, \Codeception\Example $example)
    {
        $I->amOnPage('/count/export?voteID=AnonPartyTest&sort=' . $example['sort']);
        $I->seeResponseCodeIs(200);
    }

    protected function exportSortProvider(): array
    {
        return [
            ['sort' => 'N'],
            ['sort' => 'L'],
            ['sort' => 'I'],
        ];
    }

    /**
     * 測試無效排序參數回傳 400（P2-1）
     */
    public function testExportInvalidSort(FunctionalTester $I)
    {
        $I->amOnPage('/count/export?voteID=AnonPartyTest&sort=invalid');
        $I->seeResponseCodeIs(400);
    }
}
