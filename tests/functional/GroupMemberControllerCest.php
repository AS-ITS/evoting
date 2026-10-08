<?php
/**
 * GroupMemberController 功能測試
 *
 * 測試群組成員管理功能，包含成員列表、新增、編輯、刪除等操作。
 * 需要特定權限（groupViewMember, groupCreateMember, groupEditMember, groupDeleteMember）才能存取。
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\GroupFixture;
use app\tests\fixtures\GroupMemberFixture;
use app\tests\fixtures\RbacFixture;

class GroupMemberControllerCest
{
    /**
     * 載入 fixture
     */
    public function _fixtures()
    {
        return [
            'rbac' => RbacFixture::class,
            'users' => UsersFixture::class,
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

    // ==================== 成員列表測試 ====================

    /**
     * 測試群組成員列表頁面
     */
    public function testIndexPage(FunctionalTester $I)
    {
        $I->amOnPage('/group-member/index?groupId=1');
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試無效群組 ID
     */
    public function testIndexPageInvalidGroupId(FunctionalTester $I)
    {
        $I->amOnPage('/group-member/index?groupId=999');
        $I->seeResponseCodeIsSuccessful();
    }

    // ==================== 新增成員測試 ====================

    /**
     * 測試新增成員頁面
     */
    public function testCreatePage(FunctionalTester $I)
    {
        $I->amOnPage('/group-member/create?groupId=1');
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試清除搜尋
     */
    public function testClearSearch(FunctionalTester $I)
    {
        $I->amOnPage('/group-member/clear-search?groupId=1');
        // 應該重導回 create 頁面
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試儲存新增成員 (POST)
     */
    public function testStoreNewMember(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/group-member/store', [
            'FormGroupMember' => [
                'groupId' => '1',
                'cn' => 'new_test_member',
                'isOwner' => 'N',
                'isWrite' => 'N',
            ]
        ]);
        // POST 成功後會重導
        $I->seeResponseCodeIs(302);
    }

    // ==================== 編輯成員測試 ====================

    /**
     * 測試編輯成員頁面
     */
    public function testUpdatePage(FunctionalTester $I)
    {
        // 使用 fixture 中的成員 (gm_write_only)
        $I->amOnPage('/group-member/update?groupId=1&cn=gm_write_only');
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試更新成員 (POST)
     */
    public function testEditMember(FunctionalTester $I)
    {
        $I->sendAjaxPostRequest('/group-member/edit?groupId=1&cn=gm_write_only', [
            'FormGroupMember' => [
                'isOwner' => 'N',
                'isWrite' => 'Y',
            ]
        ]);
        // POST 成功後會重導
        $I->seeResponseCodeIs(302);
    }

    // ==================== 刪除成員測試 ====================

    /**
     * 測試刪除成員 (POST)
     */
    public function testDeleteMember(FunctionalTester $I)
    {
        // 使用 gm_view_only 因為可以被刪除
        $I->sendAjaxPostRequest('/group-member/delete?groupId=1&cn=gm_view_only', [
            '_csrf' => 'test',
        ]);
        // POST 成功後會重導
        $I->seeResponseCodeIs(302);
    }

    // ==================== 未授權存取測試 ====================

    /**
     * 測試未登入時無法存取群組成員管理
     */
    public function testUnauthorizedAccess(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/group-member/index?groupId=1');
        // 確認用戶無法看到群組成員管理內容
        $I->dontSee('群組成員管理');
    }

    /**
     * 測試 VA 角色可以存取群組成員列表
     */
    public function testVaRoleAccess(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amLoggedInAsAdmin('va', 'test_va');
        $I->amOnPage('/group-member/index?groupId=1');
        // VA 角色應該可以查看
        $I->seeResponseCodeIsSuccessful();
    }
}
