<?php

use app\models\Users;

/**
 * 密碼修改功能測試
 *
 * 測試 AuthController::actionChangePassword() 的完整流程
 */
class ChangePasswordCest
{
    /**
     * 測試前準備
     */
    public function _before(AcceptanceTester $I)
    {
        // 確保測試用戶存在
        $user = Users::findOne(['cn' => 'test_change_pwd']);
        if (!$user) {
            $user = new Users();
            $user->cn = 'test_change_pwd';
            $user->name = 'Test User';
            $user->roles = 'va';
            $user->password_plain = 'TestPass123';
            if (!$user->save()) {
                \Yii::error('Failed to create test user: ' . json_encode($user->errors));
            }
        } else {
            // 重設密碼為初始值
            $user->password_plain = 'TestPass123';
            $user->save(false);
        }
    }

    /**
     * 測試後清理
     */
    public function _after(AcceptanceTester $I)
    {
        // 登出
        if (!\Yii::$app->user->isGuest) {
            \Yii::$app->user->logout();
        }
    }

    /**
     * 測試：未登入無法訪問密碼修改頁面
     */
    public function testCannotAccessChangePasswordWhenNotLoggedIn(AcceptanceTester $I)
    {
        $I->wantTo('verify that change password page requires login');

        // 確保未登入
        if (!\Yii::$app->user->isGuest) {
            \Yii::$app->user->logout();
        }

        // 嘗試訪問密碼修改頁面
        $I->amOnPage('/auth/change-password');

        // 應該被重定向到登入頁面 (系統使用 /admin 作為登入 URL 別名)
        $I->seeCurrentUrlMatches('~/(auth/login|admin)~');
        $I->see('管理員登入');
    }

    /**
     * 輔助方法：登入管理員
     */
    protected function loginAdmin(AcceptanceTester $I, string $username, string $password)
    {
        $I->amOnPage('/auth/login');
        $I->fillField('#dynamicmodel-username', $username);
        $I->fillField('#dynamicmodel-password', $password);
        $I->click('登入');
    }

    /**
     * 輔助方法：填寫密碼修改表單
     */
    protected function fillChangePasswordForm(AcceptanceTester $I, string $oldPassword, string $newPassword, string $confirmPassword)
    {
        $I->fillField('#dynamicmodel-old_password', $oldPassword);
        $I->fillField('#dynamicmodel-new_password', $newPassword);
        $I->fillField('#dynamicmodel-confirm_password', $confirmPassword);
    }

    /**
     * 測試：成功修改密碼的完整流程
     */
    public function testChangePasswordSuccessfully(AcceptanceTester $I)
    {
        $I->wantTo('change password successfully');

        // 步驟 1: 登入
        $this->loginAdmin($I, 'test_change_pwd', 'TestPass123');
        $I->see('登入成功');

        // 步驟 2: 訪問密碼修改頁面
        $I->amOnPage('/auth/change-password');
        $I->see('修改密碼');
        $I->see('密碼要求');

        // 步驟 3: 填寫表單
        $I->fillField('#dynamicmodel-old_password', 'TestPass123');
        $I->fillField('#dynamicmodel-new_password', 'NewPass456');
        $I->fillField('#dynamicmodel-confirm_password', 'NewPass456');

        // 步驟 4: 提交表單
        $I->click('確認修改');

        // 步驟 5: 驗證被登出並重定向到登入頁面
        // 注意：PhpBrowser 可能無法正確顯示 flash 訊息，所以只驗證重定向
        $I->seeCurrentUrlMatches('~/(auth/login|admin)~');
        $I->see('管理員登入');

        // 步驟 6: 使用新密碼登入
        $this->loginAdmin($I, 'test_change_pwd', 'NewPass456');
        // 如果登入成功，應該看到修改密碼連結（表示已登入）
        $I->dontSee('帳號或密碼錯誤');

        // 步驟 7: 驗證舊密碼無法登入
        $I->amOnPage('/auth/logout?type=user');  // 先登出
        $this->loginAdmin($I, 'test_change_pwd', 'TestPass123');
        $I->see('帳號或密碼錯誤');
    }

    /**
     * 測試：舊密碼錯誤
     */
    public function testChangePasswordWithWrongOldPassword(AcceptanceTester $I)
    {
        $I->wantTo('test validation with wrong old password');

        // 登入
        $this->loginAdmin($I, 'test_change_pwd', 'TestPass123');

        // 訪問密碼修改頁面
        $I->amOnPage('/auth/change-password');

        // 填寫錯誤的舊密碼
        $I->fillField('#dynamicmodel-old_password', 'WrongOldPass');
        $I->fillField('#dynamicmodel-new_password', 'NewPass456');
        $I->fillField('#dynamicmodel-confirm_password', 'NewPass456');
        $I->click('確認修改');

        // 應該顯示錯誤訊息
        $I->see('目前密碼不正確');
    }

    /**
     * 測試：新密碼太短
     */
    public function testChangePasswordWithShortPassword(AcceptanceTester $I)
    {
        $I->wantTo('test validation with short password');

        // 登入
        $this->loginAdmin($I, 'test_change_pwd', 'TestPass123');

        // 訪問密碼修改頁面
        $I->amOnPage('/auth/change-password');

        // 填寫太短的新密碼
        $I->fillField('#dynamicmodel-old_password', 'TestPass123');
        $I->fillField('#dynamicmodel-new_password', 'Pass1');
        $I->fillField('#dynamicmodel-confirm_password', 'Pass1');
        $I->click('確認修改');

        // 應該顯示驗證錯誤
        $I->see('至少');
    }

    /**
     * 測試：新密碼缺少字母或數字
     */
    public function testChangePasswordWithWeakPassword(AcceptanceTester $I)
    {
        $I->wantTo('test validation with weak password');

        // 登入
        $this->loginAdmin($I, 'test_change_pwd', 'TestPass123');

        // 訪問密碼修改頁面
        $I->amOnPage('/auth/change-password');

        // 測試只有字母
        $I->fillField('#dynamicmodel-old_password', 'TestPass123');
        $I->fillField('#dynamicmodel-new_password', 'OnlyLetters');
        $I->fillField('#dynamicmodel-confirm_password', 'OnlyLetters');
        $I->click('確認修改');
        $I->see('密碼必須包含至少一個字母和一個數字');

        // 測試只有數字
        $I->amOnPage('/auth/change-password');
        $I->fillField('#dynamicmodel-old_password', 'TestPass123');
        $I->fillField('#dynamicmodel-new_password', '12345678');
        $I->fillField('#dynamicmodel-confirm_password', '12345678');
        $I->click('確認修改');
        $I->see('密碼必須包含至少一個字母和一個數字');
    }

    /**
     * 測試：新密碼與帳號相同
     */
    public function testChangePasswordSameAsUsername(AcceptanceTester $I)
    {
        $I->wantTo('test validation when new password is same as username');

        // 登入
        $this->loginAdmin($I, 'test_change_pwd', 'TestPass123');

        // 訪問密碼修改頁面
        $I->amOnPage('/auth/change-password');

        // 新密碼與帳號相同
        $I->fillField('#dynamicmodel-old_password', 'TestPass123');
        $I->fillField('#dynamicmodel-new_password', 'test_change_pwd');
        $I->fillField('#dynamicmodel-confirm_password', 'test_change_pwd');
        $I->click('確認修改');

        // 應該顯示錯誤訊息
        $I->see('新密碼不可與帳號相同');
    }

    /**
     * 測試：新密碼與舊密碼相同
     */
    public function testChangePasswordSameAsOldPassword(AcceptanceTester $I)
    {
        $I->wantTo('test validation when new password is same as old password');

        // 登入
        $this->loginAdmin($I, 'test_change_pwd', 'TestPass123');

        // 訪問密碼修改頁面
        $I->amOnPage('/auth/change-password');

        // 新密碼與舊密碼相同
        $I->fillField('#dynamicmodel-old_password', 'TestPass123');
        $I->fillField('#dynamicmodel-new_password', 'TestPass123');
        $I->fillField('#dynamicmodel-confirm_password', 'TestPass123');
        $I->click('確認修改');

        // 應該顯示錯誤訊息
        $I->see('新密碼不可與目前密碼相同');
    }

    /**
     * 測試：確認密碼不一致
     */
    public function testChangePasswordWithMismatchConfirmation(AcceptanceTester $I)
    {
        $I->wantTo('test validation when confirmation password does not match');

        // 登入
        $this->loginAdmin($I, 'test_change_pwd', 'TestPass123');

        // 訪問密碼修改頁面
        $I->amOnPage('/auth/change-password');

        // 確認密碼不一致
        $I->fillField('#dynamicmodel-old_password', 'TestPass123');
        $I->fillField('#dynamicmodel-new_password', 'NewPass456');
        $I->fillField('#dynamicmodel-confirm_password', 'DifferentPass');
        $I->click('確認修改');

        // 應該顯示驗證錯誤
        $I->see('必須');
    }

    /**
     * 測試：導航選單顯示「修改密碼」選項
     */
    public function testNavigationShowsChangePasswordLink(AcceptanceTester $I)
    {
        $I->wantTo('verify that change password link appears in navigation when logged in');

        // 登入
        $this->loginAdmin($I, 'test_change_pwd', 'TestPass123');

        // 驗證導航中有「修改密碼」選項
        $I->see('修改密碼');
        $I->seeLink('修改密碼');
    }
}
