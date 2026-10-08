<?php
/**
 * ElectController 功能測試
 *
 * 測試投票管理功能，包含：
 * 1. 頁面顯示測試
 * 2. 表單提交測試（建立、編輯投票）
 * 3. 權限拒絕測試（未授權存取）
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\GroupFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\ConfigFixture;

class ElectControllerCest
{
    /**
     * 載入 fixture
     */
    public function _fixtures()
    {
        return [
            'rbac' => RbacFixture::class,
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'group' => GroupFixture::class,
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

    // ==================== 頁面顯示測試 ====================

    /**
     * 測試投票管理首頁
     */
    public function testIndex(FunctionalTester $I)
    {
        $I->amOnPage('/elect/index');
        $I->seeResponseCodeIs(200);
        $I->see('投票管理');
    }

    /**
     * 測試流程頁
     */
    public function testProcessPage(FunctionalTester $I)
    {
        $I->amOnPage('/elect/process?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('流程');
    }

    /**
     * 測試檔案上傳頁
     */
    public function testUploadFile(FunctionalTester $I)
    {
        $I->amOnPage('/elect/upload-file?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(302);
    }

    /**
     * 測試建立投票頁面顯示
     */
    public function testCreateVotePageDisplay(FunctionalTester $I)
    {
        $I->amOnPage('/elect/create-vote');
        $I->seeResponseCodeIs(200);
        $I->see('建立投票');
        $I->see('投票名稱');
        $I->see('投票類型');
    }

    /**
     * 測試編輯投票頁面顯示
     */
    public function testEditVotePageDisplay(FunctionalTester $I)
    {
        $I->amOnPage('/elect/edit-vote?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('編輯投票');
        $I->dontSeeInSource('webix.min.js');
        $I->seeInSource('krajeeDialog');
        $I->seeInSource('appDialog');
        $I->dontSeeInSource('appAlert');
        $I->dontSeeInSource('app-alert-compact');
    }

    /**
     * 測試重啟投票頁面顯示
     */
    public function testResetVotePageDisplay(FunctionalTester $I)
    {
        $I->amOnPage('/elect/reset-vote?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
    }

    // ==================== 表單內容測試 ====================

    /**
     * 測試建立投票頁面表單元素存在
     */
    public function testCreateVoteFormElements(FunctionalTester $I)
    {
        $I->amOnPage('/elect/create-vote');
        $I->seeResponseCodeIs(200);

        // 檢查表單必要欄位存在
        $I->see('投票名稱');
        $I->see('主辦單位');
        $I->see('投票時間');
    }

    /**
     * 測試編輯投票頁面表單元素存在
     */
    public function testEditVoteFormElements(FunctionalTester $I)
    {
        $I->amOnPage('/elect/edit-vote?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);

        // 檢查表單顯示了正確的投票資訊
        $I->see('測試分組匿名投票');
    }

    /**
     * 測試編輯投票頁面顯示組別設定
     */
    public function testEditVoteShowsPartySettings(FunctionalTester $I)
    {
        $I->amOnPage('/elect/edit-vote?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);

        // 分組投票應該顯示投票規則區塊
        $I->see('投票規則');
    }

    /**
     * 測試重啟投票 - 提交表單
     */
    public function testResetVoteSubmit(FunctionalTester $I)
    {
        $I->amOnPage('/elect/reset-vote?voteID=AnonNoPartyTest');
        $I->seeResponseCodeIs(200);

        // 提交重啟表單
        $I->submitForm('form', []);

        // 應該重導回同頁面
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試編輯不存在的投票
     */
    public function testEditNonexistentVote(FunctionalTester $I)
    {
        $I->amOnPage('/elect/edit-vote?voteID=NonexistentVote123');
        // 應該重導到列表頁
        $I->seeResponseCodeIsSuccessful();
    }

    // ==================== 權限拒絕測試 ====================

    /**
     * 測試未登入無法存取投票管理首頁
     */
    public function testUnauthorizedAccessToIndex(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/elect/index');
        // 未登入應被拒絕或重導
        $I->dontSee('投票管理');
    }

    /**
     * 測試未登入無法建立投票
     */
    public function testUnauthorizedAccessToCreateVote(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/elect/create-vote');
        // 應該被重導到登入頁
        $I->dontSee('建立投票');
    }

    /**
     * 測試未登入無法編輯投票
     */
    public function testUnauthorizedAccessToEditVote(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/elect/edit-vote?voteID=AnonPartyTest');
        // 應該被重導
        $I->dontSee('編輯投票');
    }

    /**
     * 測試 gm 角色無法存取投票管理（返回 403）
     */
    public function testGmRoleCannotAccessIndex(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('gm', 'test_gm');
        $I->amOnPage('/elect/index');
        // gm 沒有 voteManag 權限，應返回 403
        $I->seeResponseCodeIs(403);
    }

    /**
     * 測試 gm 角色無法建立投票（返回 403）
     */
    public function testGmRoleCannotCreateVote(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('gm', 'test_gm');
        $I->amOnPage('/elect/create-vote');
        // gm 沒有 voteCreate 權限，應返回 403
        $I->seeResponseCodeIs(403);
    }

    /**
     * 測試 va 角色可以建立投票
     */
    public function testVaRoleCanCreateVote(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/elect/create-vote');
        $I->seeResponseCodeIs(200);
        $I->see('建立投票');
    }

    /**
     * 測試 va 角色可以存取投票管理
     */
    public function testVaRoleCanAccessIndex(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/elect/index');
        $I->seeResponseCodeIs(200);
        $I->see('投票管理');
    }

    /**
     * 測試 ga 角色無法建立投票（返回 403）
     */
    public function testGaRoleCannotCreateVote(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('ga', 'test_ga');
        $I->amOnPage('/elect/create-vote');
        // ga 沒有 voteCreate 權限，應返回 403
        $I->seeResponseCodeIs(403);
    }

    /**
     * 測試未登入無法重啟投票
     */
    public function testUnauthorizedAccessToResetVote(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/elect/reset-vote?voteID=AnonPartyTest');
        // 應該被拒絕
        $I->seeResponseCodeIsSuccessful();  // 重導
    }

    /**
     * 測試未登入無法刪除投票
     */
    public function testUnauthorizedAccessToDeleteVote(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/elect/delete-vote?voteID=AnonPartyTest');
        // 應該被拒絕
        $I->seeResponseCodeIsSuccessful();  // 重導
    }

    // ==================== AJAX 請求測試 ====================

    /**
     * 測試更新短網址 (AJAX) - sa 角色
     */
    public function testRenewShortUrlAjax(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/elect/renew-short-url', [
            'voteID' => 'AnonPartyTest'
        ]);
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試更新短網址 - 未登入重導（302）
     */
    public function testRenewShortUrlGuestRedirect(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->sendAjaxPostRequest('/elect/renew-short-url', [
            'voteID' => 'AnonPartyTest'
        ]);
        // 未登入應該重導到登入頁
        $I->seeResponseCodeIs(302);
    }

    /**
     * 測試更新短網址 - va 角色返回 403
     */
    public function testRenewShortUrlVaRoleDenied(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->sendAjaxPostRequest('/elect/renew-short-url', [
            'voteID' => 'AnonPartyTest'
        ]);
        // va 沒有 sa 權限，應被拒絕
        $I->seeResponseCodeIs(403);
    }
}
