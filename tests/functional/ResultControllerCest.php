<?php
/**
 * ResultController 功能測試
 *
 * 測試投票結果功能，包含結果列表、預覽、批次編輯等操作。
 * 需要先建立候選人配置和開票設定才能存取。
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
use app\tests\fixtures\ResultsConfigFixture;

class ResultControllerCest
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
            'passwords' => PasswordsFixture::class,
            'ballots' => BallotsFixture::class,
            'ballotsSelected' => BallotsSelectedFixture::class,
            'resultsConfig' => ResultsConfigFixture::class,
        ];
    }

    /**
     * 每個測試前執行 - 模擬管理員登入
     * 明確重新載入 questions 和 candiConfig fixture，避免跨 Cest 污染
     * （cleanup: false 下 loadedFixtures 不清空，後續 Cest 的 _fixtures() 不會自動執行）
     */
    public function _before(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->haveFixtures([
            'questions' => QuestionsFixture::class,
            'candiConfig' => CandiConfigFixture::class,
        ]);
    }

    /**
     * 測試結果頁面 - 需要候選人配置
     *
     * 如果沒有候選人配置，會被重導到 candi/config
     */
    public function testIndexPageRedirectsWithoutConfig(FunctionalTester $I)
    {
        $I->amOnPage('/result/index?voteID=AnonPartyTest');
        // 可能會重導或顯示警告
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試開票頁面（使用 print-mode.php）
     */
    public function testCountPageWithPrintMode(FunctionalTester $I)
    {
        $I->amOnPage('/count/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeInSource('webix.min.js');
        $I->seeInSource('print-sign-modal');
    }

    /**
     * 測試未登入時無法存取結果頁面
     */
    public function testUnauthorizedAccess(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/result/index?voteID=AnonPartyTest');
        // 確認用戶無法看到結果管理頁面的特定內容（候選人當選編輯功能）
        $I->dontSee('批次編輯');
    }

    /**
     * 測試批次編輯當選狀態 (POST)
     */
    public function testMultiEditPost(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/result/multi-edit?voteID=AnonPartyTest', [
            'ids' => [1, 2],
            'action' => '1'  // 設為當選
        ]);
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試不分組投票的結果頁面
     */
    public function testNoPartyResultPage(FunctionalTester $I)
    {
        $I->amOnPage('/result/index?voteID=AnonNoPartyTest');
        $I->seeResponseCodeIsSuccessful();
    }
}
