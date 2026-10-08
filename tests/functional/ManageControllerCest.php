<?php
/**
 * ManageController 功能測試
 *
 * 測試網站管理功能，包含儀表板、設定、日誌、匿名登入狀態等。
 * 需要 SA 權限才能存取。
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;

class ManageControllerCest
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
            'passwords' => PasswordsFixture::class,
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
     * 測試管理儀表板首頁
     */
    public function testIndexPage(FunctionalTester $I)
    {
        $I->amOnPage('/manage/index');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試網站設定頁
     */
    public function testSettingPage(FunctionalTester $I)
    {
        $I->amOnPage('/manage/setting');
        $I->seeResponseCodeIs(200);
        $I->see('網站設定');
    }

    /**
     * 測試日誌頁面
     */
    public function testLogPage(FunctionalTester $I)
    {
        $I->amOnPage('/manage/log');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試匿名登入狀態頁
     */
    public function testLoginsPage(FunctionalTester $I)
    {
        $I->amOnPage('/manage/logins');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試未登入時無法存取管理頁面
     */
    public function testUnauthorizedAccessToIndex(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/manage/index');
        // 應該被重導或返回 403
        $I->dontSee('儀表板');
    }

    /**
     * 測試取得密碼分組資訊 (POST)
     */
    public function testPasswdInfoPost(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/manage/passwd-info', [
            'voteID' => 'AnonPartyTest'
        ]);
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試匿名登入狀態頁顯示關鍵字（P2-6）
     */
    public function testLoginsPageContent(FunctionalTester $I)
    {
        $I->amOnPage('/manage/logins');
        $I->seeResponseCodeIs(200);
        $I->see('清除session');
    }

    /**
     * 測試清除匿名登入 session（P2-6）
     */
    public function testLogoutAnonPost(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/manage/logout-anon', [
            'voteID' => 'AnonPartyTest',
        ]);
        $I->seeResponseCodeIs(302);
    }
}
