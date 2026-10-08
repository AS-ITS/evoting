<?php
/**
 * 權限拒絕測試
 *
 * 測試各個 Controller 的權限控制機制，確保：
 * 1. 未登入用戶無法存取受保護頁面
 * 2. 低權限用戶無法存取高權限功能
 * 3. 角色權限正確生效
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
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\ConfigFixture;

class PermissionDenialCest
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
            'passwords' => PasswordsFixture::class,
        ];
    }

    // ==================== 未登入權限測試 ====================

    /**
     * 測試未登入無法存取 ManageController
     */
    public function testGuestCannotAccessManage(FunctionalTester $I)
    {
        $I->amOnPage('/manage/index');
        $I->dontSee('儀表板');
    }

    /**
     * 測試未登入無法存取 ManageController/setting
     */
    public function testGuestCannotAccessManageSetting(FunctionalTester $I)
    {
        $I->amOnPage('/manage/setting');
        $I->dontSee('網站設定');
    }

    /**
     * 測試未登入無法存取 ManageController/log
     */
    public function testGuestCannotAccessManageLog(FunctionalTester $I)
    {
        $I->amOnPage('/manage/log');
        $I->dontSee('系統日誌');
    }

    /**
     * 測試未登入無法存取 ElectController/index
     */
    public function testGuestCannotAccessElectIndex(FunctionalTester $I)
    {
        $I->amOnPage('/elect/index');
        $I->dontSee('投票管理');
    }

    /**
     * 測試未登入無法存取 ElectController/create-vote
     */
    public function testGuestCannotAccessCreateVote(FunctionalTester $I)
    {
        $I->amOnPage('/elect/create-vote');
        $I->dontSee('建立投票');
    }

    /**
     * 測試未登入無法存取 UsersController
     */
    public function testGuestCannotAccessUsers(FunctionalTester $I)
    {
        $I->amOnPage('/users/index');
        $I->dontSee('使用者管理');
    }

    /**
     * 測試未登入無法存取 GroupController
     */
    public function testGuestCannotAccessGroup(FunctionalTester $I)
    {
        $I->amOnPage('/group/index');
        $I->dontSee('群組管理');
    }

    /**
     * 測試未登入無法存取 PasswdController
     */
    public function testGuestCannotAccessPasswd(FunctionalTester $I)
    {
        $I->amOnPage('/passwd/index?voteID=AnonPartyTest');
        $I->dontSee('密碼管理');
    }

    /**
     * 測試未登入無法存取 QuestionController
     */
    public function testGuestCannotAccessQuestion(FunctionalTester $I)
    {
        $I->amOnPage('/question/index?voteID=AnonPartyTest');
        $I->dontSee('問題管理');
    }

    /**
     * 測試未登入無法存取 CandiController
     */
    public function testGuestCannotAccessCandi(FunctionalTester $I)
    {
        $I->amOnPage('/candi/index?voteID=AnonPartyTest');
        $I->dontSee('候選人管理');
    }

    /**
     * 測試未登入無法存取 BallotController
     */
    public function testGuestCannotAccessBallot(FunctionalTester $I)
    {
        $I->amOnPage('/ballot/index?voteID=AnonPartyTest');
        $I->dontSee('選票管理');
    }

    /**
     * 測試未登入無法存取 CountController
     */
    public function testGuestCannotAccessCount(FunctionalTester $I)
    {
        $I->amOnPage('/count/index?voteID=AnonPartyTest');
        $I->dontSee('計票');
    }

    /**
     * 測試未登入無法存取 ResultController
     */
    public function testGuestCannotAccessResult(FunctionalTester $I)
    {
        $I->amOnPage('/result/index?voteID=AnonPartyTest');
        $I->dontSee('開票結果');
    }

    /**
     * 測試未登入無法存取 RoundController
     */
    public function testGuestCannotAccessRound(FunctionalTester $I)
    {
        $I->amOnPage('/round/index?voteID=AnonPartyTest');
        $I->dontSee('輪次管理');
    }

    /**
     * 測試未登入無法存取 BallotWorkController
     */
    public function testGuestCannotAccessBallotWork(FunctionalTester $I)
    {
        $I->amOnPage('/ballot-work/index');
        $I->dontSee('開票作業');
    }

    // ==================== gm 角色權限測試（最低權限） ====================

    /**
     * 測試 gm 無法存取 ManageController
     */
    public function testGmCannotAccessManage(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('gm', 'test_gm');
        $I->amOnPage('/manage/index');
        // gm 沒有 sa 權限，應該被拒絕
        $I->dontSee('儀表板');
    }

    /**
     * 測試 gm 無法存取 UsersController
     */
    public function testGmCannotAccessUsers(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('gm', 'test_gm');
        $I->amOnPage('/users/index');
        // gm 沒有用戶管理權限
        $I->dontSee('使用者管理');
    }

    /**
     * 測試 gm 無法建立投票
     */
    public function testGmCannotCreateVote(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('gm', 'test_gm');
        $I->amOnPage('/elect/create-vote');
        $I->dontSee('建立投票');
    }

    /**
     * 測試 gm 無法存取密碼管理
     */
    public function testGmCannotAccessPasswd(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('gm', 'test_gm');
        $I->amOnPage('/passwd/index?voteID=AnonPartyTest');
        $I->dontSee('密碼管理');
    }

    /**
     * 測試 gm 可存取開票作業列表（僅能查看）
     *
     * 注意：gm 有 ballotWorkIndex 權限，可查看列表但不能操作
     */
    public function testGmCanAccessBallotWorkIndex(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('gm', 'test_gm');
        $I->amOnPage('/ballot-work/index');
        $I->seeResponseCodeIs(200);
    }

    // ==================== ga 角色權限測試 ====================

    /**
     * 測試 ga 無法存取 ManageController
     */
    public function testGaCannotAccessManage(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('ga', 'test_ga');
        $I->amOnPage('/manage/index');
        // ga 沒有 sa 權限
        $I->dontSee('儀表板');
    }

    /**
     * 測試 ga 無法存取 UsersController
     */
    public function testGaCannotAccessUsers(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('ga', 'test_ga');
        $I->amOnPage('/users/index');
        $I->dontSee('使用者管理');
    }

    // ==================== va 角色權限測試 ====================

    /**
     * 測試 va 無法存取 ManageController
     */
    public function testVaCannotAccessManage(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/manage/index');
        // va 沒有 sa 權限
        $I->dontSee('儀表板');
    }

    /**
     * 測試 va 無法存取 UsersController
     */
    public function testVaCannotAccessUsers(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/users/index');
        $I->dontSee('使用者管理');
    }

    /**
     * 測試 va 可以存取投票管理
     */
    public function testVaCanAccessElectIndex(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/elect/index');
        $I->seeResponseCodeIs(200);
        $I->see('投票管理');
    }

    /**
     * 測試 va 可以建立投票
     */
    public function testVaCanCreateVote(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/elect/create-vote');
        $I->seeResponseCodeIs(200);
        $I->see('建立投票');
    }

    /**
     * 測試 va 可以存取開票作業
     */
    public function testVaCanAccessBallotWork(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/ballot-work/index');
        $I->seeResponseCodeIs(200);
    }

    // ==================== sa 角色權限測試（完整權限） ====================

    /**
     * 測試 sa 可以存取 ManageController
     */
    public function testSaCanAccessManage(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/manage/index');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試 sa 可以存取 UsersController
     */
    public function testSaCanAccessUsers(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/users/index');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試 sa 可以存取所有投票管理頁面
     */
    public function testSaCanAccessAllElectPages(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');

        // index
        $I->amOnPage('/elect/index');
        $I->seeResponseCodeIs(200);

        // create-vote
        $I->amOnPage('/elect/create-vote');
        $I->seeResponseCodeIs(200);

        // edit-vote
        $I->amOnPage('/elect/edit-vote?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);

        // process
        $I->amOnPage('/elect/process?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);

        // reset-vote
        $I->amOnPage('/elect/reset-vote?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試 sa 可以存取群組管理
     */
    public function testSaCanAccessGroup(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/group/index');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試 sa 可以存取網站設定
     */
    public function testSaCanAccessSetting(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/manage/setting');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試 sa 可以存取日誌
     */
    public function testSaCanAccessLog(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/manage/log');
        $I->seeResponseCodeIs(200);
    }

    // ==================== 特定功能權限測試 ====================

    /**
     * 測試未登入提交建立投票表單被重導
     */
    public function testGuestSubmitCreateVoteRedirect(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/elect/create-vote', [
            'Votes[Name]' => '測試投票',
        ]);
        // 未登入應被重導
        $I->seeResponseCodeIs(302);
    }

    /**
     * 測試未登入更新短網址被重導
     */
    public function testGuestRenewShortUrlRedirect(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/elect/renew-short-url', [
            'voteID' => 'AnonPartyTest'
        ]);
        // 未登入應被重導
        $I->seeResponseCodeIs(302);
    }

    /**
     * 測試 va 無法更新短網址（僅 sa 可以）
     */
    public function testVaCannotRenewShortUrl(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->sendAjaxPostRequest('/elect/renew-short-url', [
            'voteID' => 'AnonPartyTest'
        ]);
        // va 沒有 sa 權限，應返回 403
        $I->seeResponseCodeIs(403);
    }

    /**
     * 測試 sa 可以更新短網址
     */
    public function testSaCanRenewShortUrl(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->sendAjaxPostRequest('/elect/renew-short-url', [
            'voteID' => 'AnonPartyTest'
        ]);
        $I->seeResponseCodeIs(200);
    }

    // ==================== 密碼管理權限測試 ====================

    /**
     * 測試 va 無法存取非自己建立的投票密碼管理（返回 403）
     *
     * voteManagRule 檢查用戶是否為投票建立者
     */
    public function testVaCannotAccessOthersPasswd(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/passwd/index?voteID=AnonPartyTest');  // creator='admin'
        $I->seeResponseCodeIs(403);
    }

    /**
     * 測試 sa 可以存取任何投票的密碼管理
     */
    public function testSaCanAccessPasswd(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/passwd/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
    }

    // ==================== 候選人管理權限測試 ====================

    /**
     * 測試 va 無法存取非自己建立的投票候選人管理（返回 403）
     */
    public function testVaCannotAccessOthersCandi(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/candi/index?voteID=AnonPartyTest');  // creator='admin'
        $I->seeResponseCodeIs(403);
    }

    /**
     * 測試 sa 可以存取任何投票的候選人管理
     */
    public function testSaCanAccessCandi(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/candi/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
    }

    // ==================== 選票管理權限測試 ====================

    /**
     * 測試 va 存取選票管理行為驗證
     */
    public function testVaAccessBallotBehavior(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/ballot/index?voteID=AnonPartyTest');
        // va 無法存取非自己建立的投票，應返回 403
        $I->seeResponseCodeIs(403);
    }

    /**
     * 測試 sa 可以存取任何投票的選票管理
     */
    public function testSaCanAccessBallot(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/ballot/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
    }

    // ==================== 輪次管理權限測試 ====================

    /**
     * 測試 va 無法存取非自己建立的投票輪次管理（返回 403）
     */
    public function testVaCannotAccessOthersRound(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/round/index?voteID=AnonPartyTest');  // creator='admin'
        $I->seeResponseCodeIs(403);
    }

    /**
     * 測試 sa 可以存取輪次管理
     */
    public function testSaCanAccessRound(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/round/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
    }
}
