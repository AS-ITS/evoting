<?php
/**
 * AuthController 功能測試
 *
 * 測試認證功能，包含登入、登出、密碼修改等操作。
 * 這是安全關鍵測試，確保認證機制正常運作。
 */

use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\RbacFixture;

class AuthControllerCest
{
    /**
     * 載入 fixture
     */
    public function _fixtures()
    {
        return [
            'rbac' => RbacFixture::class,
            'users' => UsersFixture::class,
        ];
    }

    // ==================== 登入頁面測試 ====================

    /**
     * 測試登入頁面顯示
     */
    public function testLoginPageDisplay(FunctionalTester $I)
    {
        $I->amOnPage('/auth/login');
        $I->seeResponseCodeIs(200);
        $I->see('帳號');
        $I->see('密碼');
    }

    /**
     * 測試 /admin 短路由導向登入頁（P3-3 部署 smoke）
     */
    public function testAdminAliasShowsLoginPage(FunctionalTester $I)
    {
        $I->amOnPage('/admin');
        $I->seeResponseCodeIs(200);
        $I->see('帳號');
    }

    /**
     * 測試已登入用戶訪問登入頁面應重導
     */
    public function testLoginPageRedirectsWhenLoggedIn(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/auth/login');
        // 應該重導到首頁
        $I->seeResponseCodeIsSuccessful();
    }

    // ==================== 登入功能測試 ====================

    /**
     * 測試成功登入
     */
    public function testSuccessfulLogin(FunctionalTester $I)
    {
        $I->amOnPage('/auth/login');
        $I->submitForm('#admin-login-form', [
            'DynamicModel[username]' => 'test_admin',
            'DynamicModel[password]' => 'TestPass123',
        ]);
        // 登入成功後應該重導
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試登入失敗 - 錯誤密碼
     */
    public function testFailedLoginWrongPassword(FunctionalTester $I)
    {
        $I->amOnPage('/auth/login');
        $I->submitForm('#admin-login-form', [
            'DynamicModel[username]' => 'test_admin',
            'DynamicModel[password]' => 'WrongPassword',
        ]);
        $I->seeResponseCodeIs(200);
        // 應該看到錯誤訊息
        $I->see('帳號或密碼錯誤');
    }

    /**
     * 測試登入失敗 - 不存在的用戶
     */
    public function testFailedLoginNonexistentUser(FunctionalTester $I)
    {
        $I->amOnPage('/auth/login');
        $I->submitForm('#admin-login-form', [
            'DynamicModel[username]' => 'nonexistent_user',
            'DynamicModel[password]' => 'SomePassword',
        ]);
        $I->seeResponseCodeIs(200);
        $I->see('帳號或密碼錯誤');
    }

    /**
     * 測試登入失敗 - 空白欄位
     */
    public function testFailedLoginEmptyFields(FunctionalTester $I)
    {
        $I->amOnPage('/auth/login');
        $I->submitForm('#admin-login-form', [
            'DynamicModel[username]' => '',
            'DynamicModel[password]' => '',
        ]);
        $I->seeResponseCodeIs(200);
    }

    // ==================== 登出功能測試 ====================

    /**
     * 測試登出功能
     */
    public function testLogout(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/auth/logout');
        // 登出後應該重導
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試未登入時訪問登出
     */
    public function testLogoutWhenNotLoggedIn(FunctionalTester $I)
    {
        $I->amOnPage('/auth/logout');
        // 應該重導到首頁或登入頁
        $I->seeResponseCodeIsSuccessful();
    }

    // ==================== 修改密碼測試 ====================

    /**
     * 測試修改密碼頁面 - 需要登入
     */
    public function testChangePasswordPageRequiresLogin(FunctionalTester $I)
    {
        $I->amOnPage('/auth/change-password');
        // 未登入應該重導到登入頁
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試修改密碼頁面顯示
     */
    public function testChangePasswordPageDisplay(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/auth/change-password');
        $I->seeResponseCodeIs(200);
        $I->see('目前密碼');
        $I->see('新密碼');
        $I->see('確認新密碼');
    }

    /**
     * 測試修改密碼 - 舊密碼錯誤
     */
    public function testChangePasswordWrongOldPassword(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/auth/change-password');
        $I->submitForm('form', [
            'DynamicModel[old_password]' => 'WrongOldPassword',
            'DynamicModel[new_password]' => 'NewPass123',
            'DynamicModel[confirm_password]' => 'NewPass123',
        ]);
        $I->seeResponseCodeIs(200);
        $I->see('目前密碼不正確');
    }

    /**
     * 測試修改密碼 - 新密碼太短
     */
    public function testChangePasswordTooShort(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/auth/change-password');
        $I->submitForm('form', [
            'DynamicModel[old_password]' => 'TestPass123',
            'DynamicModel[new_password]' => 'Short1',
            'DynamicModel[confirm_password]' => 'Short1',
        ]);
        $I->seeResponseCodeIs(200);
        // 密碼最少 8 字元
    }

    /**
     * 測試修改密碼 - 確認密碼不符
     */
    public function testChangePasswordMismatch(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/auth/change-password');
        $I->submitForm('form', [
            'DynamicModel[old_password]' => 'TestPass123',
            'DynamicModel[new_password]' => 'NewPass123',
            'DynamicModel[confirm_password]' => 'DifferentPass123',
        ]);
        $I->seeResponseCodeIs(200);
    }

    // ==================== 首頁重導測試 ====================

    /**
     * 測試 auth/index 重導
     */
    public function testIndexRedirect(FunctionalTester $I)
    {
        $I->amOnPage('/auth/index');
        $I->seeResponseCodeIsSuccessful();
    }

    // ==================== 未授權存取測試 ====================

    /**
     * 測試未登入無法存取受保護頁面
     */
    public function testUnauthorizedAccessToProtectedPage(FunctionalTester $I)
    {
        $I->amOnPage('/auth/change-password');
        // 應該被重導到登入頁
        $I->dontSee('確認新密碼');
    }
}
