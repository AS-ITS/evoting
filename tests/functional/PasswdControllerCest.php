<?php
/**
 * PasswdController 功能測試
 *
 * 測試密碼管理功能，包含密碼列表、生成、狀態切換、匯出等操作。
 * 僅適用於匿名投票 (TYPE_ANON='1')。
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
use app\tests\fixtures\ConfigFixture;

class PasswdControllerCest
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
            'config' => ConfigFixture::class,
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
     * 測試密碼管理首頁 - 分組投票
     */
    public function testIndexPageWithParty(FunctionalTester $I)
    {
        $I->amOnPage('/passwd/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試密碼管理首頁 - 不分組投票
     */
    public function testIndexPageNoParty(FunctionalTester $I)
    {
        $I->amOnPage('/passwd/index?voteID=AnonNoPartyTest');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試密碼生成頁面 (GET)
     */
    public function testCreatePasswordPage(FunctionalTester $I)
    {
        $I->amOnPage('/passwd/create-password?voteID=AnonPartyTest');
        // 應該重導回 index 或顯示頁面
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試密碼範例下載
     */
    public function testExportDefaultTemplate(FunctionalTester $I)
    {
        $I->amOnPage('/passwd/export-default?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試密碼匯出（P2-5）
     */
    public function testExportPasswords(FunctionalTester $I)
    {
        $I->amOnPage('/passwd/export?voteID=AnonPartyTest');
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試未登入時無法存取密碼管理
     */
    public function testUnauthorizedAccess(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/passwd/index?voteID=AnonPartyTest');
        // 確認用戶無法看到密碼管理內容
        $I->dontSee('密碼管理');
    }

    /**
     * 測試狀態切換 (AJAX POST)
     */
    public function testSwitchStatus(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/passwd/switch-status?voteID=AnonPartyTest', [
            'party' => 'def',
            'status' => '1',
        ]);
        // POST 成功後會重導 (302)
        $I->seeResponseCodeIs(302);
    }

    /**
     * 測試切換開關 (AJAX POST)
     */
    public function testToggle(FunctionalTester $I)
    {
        // 需要有有效的密碼 ID
        $I->sendAjaxPostRequest('/passwd/toggle?voteID=AnonPartyTest', [
            'action' => 'status',
            'id' => 1,
        ]);
        // 可能成功或因為 ID 不存在而失敗
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試批次刪除密碼（P2-5）
     */
    public function testDeleteAllByMark(FunctionalTester $I)
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $model = new \app\models\FormPasswords();
        $Passwd = new \app\models\Passwd();
        $model->creationPasswd(
            'AnonPartyTest',
            [$Passwd->encrypt('p2_delete_smoke1'), $Passwd->encrypt('p2_delete_smoke2')],
            'N',
            '1',
            '0',
            'p2_delete_smoke'
        );

        $I->stopFollowingRedirects();
        $I->amOnPage('/passwd/delete-all?voteID=AnonPartyTest&mark=p2_delete_smoke');
        $I->seeResponseCodeIs(302);
    }
}
