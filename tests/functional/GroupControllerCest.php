<?php
/**
 * GroupController 功能測試
 *
 * 測試群組管理功能，包含群組列表、建立、編輯、群組投票管理等操作。
 * 需要特定權限才能存取不同功能。
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\GroupFixture;
use app\tests\fixtures\GroupMemberFixture;
use app\tests\fixtures\RbacFixture;

class GroupControllerCest
{
    /**
     * 載入 fixture
     */
    public function _fixtures()
    {
        return [
            'rbac' => RbacFixture::class,
            'users' => UsersFixture::class,
            'votes' => VotesFixture::class,
            'group' => GroupFixture::class,
            'groupMember' => GroupMemberFixture::class,
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
     * 測試群組列表頁面
     */
    public function testIndexPage(FunctionalTester $I)
    {
        $I->amOnPage('/group/index');
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試群組基本資料頁面
     */
    public function testViewBasePage(FunctionalTester $I)
    {
        // 使用 fixture 中的群組 (groupId = 1)
        $I->amOnPage('/group/view-base?groupId=1');
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試群組投票管理頁面
     */
    public function testViewVotePage(FunctionalTester $I)
    {
        $I->amOnPage('/group/view-vote');
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試未登入時無法存取群組管理
     */
    public function testUnauthorizedAccess(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/group/index');
        // 確認用戶無法看到群組管理內容
        $I->dontSee('群組管理');
    }

    /**
     * 測試建立群組 (POST)
     */
    public function testCreateGroupPost(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/group/create', [
            'FormGroup' => [
                'groupId' => 'NEW_TEST_GROUP',
                'groupName' => '新測試群組',
            ]
        ]);
        // POST 成功後會重導 (302)
        $I->seeResponseCodeIs(302);
    }

    /**
     * 測試編輯群組基本資料 (POST)
     */
    public function testEditBasePost(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/group/edit-base?groupId=1', [
            'FormGroup' => [
                'groupName' => '修改後的群組名稱',
            ]
        ]);
        // POST 成功後會重導 (302)
        $I->seeResponseCodeIs(302);
    }
}
