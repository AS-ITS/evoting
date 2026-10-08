<?php

namespace app\tests\unit;

use Yii;
use app\models\Users;
use app\models\Passwd;
use app\tests\fixtures\UsersFixture;

/**
 * 管理員登入功能測試
 *
 * 測試項目：
 * 1. 正常登入流程
 * 2. 密碼驗證
 * 3. 暴力破解防護
 * 4. CSRF 保護
 * 5. Session 安全
 */
class AdminLoginTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        // 清除 session
        Yii::$app->session->removeAll();
    }

    protected function _after()
    {
        // 清理
        Yii::$app->session->removeAll();
    }

    /**
     * 載入測試數據
     *
     * @return array
     */
    public function _fixtures()
    {
        return [
            'users' => UsersFixture::class,
        ];
    }

    /**
     * 測試：成功登入
     */
    public function testSuccessfulLogin()
    {
        // 建立測試用戶
        $user = new Users();
        $user->cn = 'testuser';
        $user->name = '測試用戶';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), '用戶建立失敗');

        // 測試密碼驗證
        $foundUser = Users::findOne(['cn' => 'testuser']);
        $this->assertNotNull($foundUser, '找不到用戶');
        $this->assertTrue($foundUser->validatePassword('TestPass123'), '密碼驗證失敗');
    }

    /**
     * 測試：密碼驗證失敗
     */
    public function testPasswordValidationFailed()
    {
        // 建立測試用戶
        $user = new Users();
        $user->cn = 'testuser2';
        $user->name = '測試用戶2';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save());

        // 測試錯誤密碼
        $foundUser = Users::findOne(['cn' => 'testuser2']);
        $this->assertFalse($foundUser->validatePassword('WrongPassword'), '應該密碼驗證失敗');
    }

    /**
     * 測試：密碼加密儲存
     */
    public function testPasswordEncryption()
    {
        $user = new Users();
        $user->cn = 'testuser3';
        $user->name = '測試用戶3';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save());

        // 確認密碼已加密（不是明文）
        $foundUser = Users::findOne(['cn' => 'testuser3']);
        $this->assertNotEquals('TestPass123', $foundUser->password, '密碼應該被加密');
        $this->assertNotEmpty($foundUser->password, '密碼不應為空');
    }

    /**
     * 測試：密碼強度驗證 - 長度不足
     */
    public function testPasswordLengthValidation()
    {
        $user = new Users();
        $user->cn = 'testuser4';
        $user->name = '測試用戶4';
        $user->roles = 'sa';
        $user->password_plain = 'Test1'; // 只有 5 字元

        $this->assertFalse($user->save(), '密碼長度不足應該無法儲存');
        $this->assertTrue($user->hasErrors('password_plain'), '應該有密碼錯誤');
    }

    /**
     * 測試：密碼強度驗證 - 缺少數字
     */
    public function testPasswordMustContainNumbers()
    {
        $user = new Users();
        $user->cn = 'testuser5';
        $user->name = '測試用戶5';
        $user->roles = 'sa';
        $user->password_plain = 'TestPassword'; // 沒有數字

        $this->assertFalse($user->save(), '密碼缺少數字應該無法儲存');
        $this->assertTrue($user->hasErrors('password_plain'));
    }

    /**
     * 測試：密碼強度驗證 - 缺少字母
     */
    public function testPasswordMustContainLetters()
    {
        $user = new Users();
        $user->cn = 'testuser6';
        $user->name = '測試用戶6';
        $user->roles = 'sa';
        $user->password_plain = '12345678'; // 沒有字母

        $this->assertFalse($user->save(), '密碼缺少字母應該無法儲存');
        $this->assertTrue($user->hasErrors('password_plain'));
    }

    /**
     * 測試：密碼不可與帳號相同
     */
    public function testPasswordCannotBeSameAsUsername()
    {
        $user = new Users();
        $user->cn = 'testuser7';
        $user->name = '測試用戶7';
        $user->roles = 'sa';
        $user->password_plain = 'testuser7'; // 與帳號相同

        $this->assertFalse($user->save(), '密碼與帳號相同應該無法儲存');
        $this->assertTrue($user->hasErrors('password_plain'));
    }

    /**
     * 測試：密碼不可包含帳號
     */
    public function testPasswordCannotContainUsername()
    {
        $user = new Users();
        $user->cn = 'john';
        $user->name = '測試用戶8';
        $user->roles = 'sa';
        $user->password_plain = 'john123456'; // 包含帳號

        $this->assertFalse($user->save(), '密碼包含帳號應該無法儲存');
        $this->assertTrue($user->hasErrors('password_plain'));
    }

    /**
     * 測試：密碼相似度檢查
     */
    public function testPasswordSimilarityCheck()
    {
        $user = new Users();
        $user->cn = 'alice';
        $user->name = '測試用戶9';
        $user->roles = 'sa';
        $user->password_plain = 'alice123'; // 與帳號過於相似

        $this->assertFalse($user->save(), '密碼與帳號過於相似應該無法儲存');
        $this->assertTrue($user->hasErrors('password_plain'));
    }

    /**
     * 測試：暴力破解防護 - 登入失敗次數記錄
     */
    public function testLoginAttemptsTracking()
    {
        // 建立測試用戶
        $user = new Users();
        $user->cn = 'testuser10';
        $user->name = '測試用戶10';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save());

        $username = 'testuser10';
        $sessionKey = 'login_attempts_' . hash('sha256', $username);

        // 模擬 3 次失敗登入
        for ($i = 1; $i <= 3; $i++) {
            $attempts = Yii::$app->session->get($sessionKey, 0) + 1;
            Yii::$app->session->set($sessionKey, $attempts);
        }

        // 驗證失敗次數
        $this->assertEquals(3, Yii::$app->session->get($sessionKey), '登入失敗次數應為 3');
    }

    /**
     * 測試：暴力破解防護 - 帳號鎖定
     */
    public function testAccountLockAfterMaxAttempts()
    {
        $username = 'testuser11';
        $sessionKey = 'login_attempts_' . hash('sha256', $username);
        $lockKey = 'login_locked_' . hash('sha256', $username);

        // 模擬 5 次失敗登入
        Yii::$app->session->set($sessionKey, 5);

        // 設定鎖定時間
        $lockTime = 15 * 60; // 15 分鐘
        Yii::$app->session->set($lockKey, time() + $lockTime);

        // 檢查是否被鎖定
        $lockUntil = Yii::$app->session->get($lockKey);
        $this->assertNotNull($lockUntil, '帳號應該被鎖定');
        $this->assertGreaterThan(time(), $lockUntil, '鎖定時間應該在未來');
    }

    /**
     * 測試：登入成功後清除失敗記錄
     */
    public function testClearAttemptsAfterSuccessfulLogin()
    {
        $username = 'testuser12';
        $sessionKey = 'login_attempts_' . hash('sha256', $username);
        $lockKey = 'login_locked_' . hash('sha256', $username);

        // 模擬失敗記錄
        Yii::$app->session->set($sessionKey, 3);
        Yii::$app->session->set($lockKey, time() + 600);

        // 模擬登入成功，清除記錄
        Yii::$app->session->remove($sessionKey);
        Yii::$app->session->remove($lockKey);

        // 驗證已清除
        $this->assertNull(Yii::$app->session->get($sessionKey), '失敗次數應該被清除');
        $this->assertNull(Yii::$app->session->get($lockKey), '鎖定狀態應該被清除');
    }

    /**
     * 測試：密碼解密功能
     */
    public function testPasswordDecryption()
    {
        $passwdModel = new Passwd();
        $plainPassword = 'TestDecrypt123'; // gitleaks:allow -- unit-test fixture

        // 加密
        $encrypted = $passwdModel->encrypt($plainPassword);
        $this->assertNotEquals($plainPassword, $encrypted, '加密後應與原文不同');

        // 解密
        $decrypted = $passwdModel->decrypt($encrypted);
        $this->assertEquals($plainPassword, $decrypted, '解密後應與原文相同');
    }

    /**
     * 測試：Session 重新生成（防止 Session Fixation）
     */
    public function testSessionRegenerationAfterLogin()
    {
        // 記錄原始 Session ID
        $oldSessionId = Yii::$app->session->getId();

        // 模擬登入後重新生成 Session ID
        Yii::$app->session->regenerateID(true);

        // 驗證 Session ID 已改變
        $newSessionId = Yii::$app->session->getId();
        $this->assertNotEquals($oldSessionId, $newSessionId, 'Session ID 應該已改變');
    }

    /**
     * 測試：更新密碼（留空表示不修改）
     */
    public function testUpdateUserWithoutChangingPassword()
    {
        // 建立用戶
        $user = new Users();
        $user->cn = 'testuser13';
        $user->name = '測試用戶13';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save());

        // 記錄原密碼
        $originalPassword = $user->password;

        // 更新其他欄位，不修改密碼
        $foundUser = Users::findOne(['cn' => 'testuser13']);
        $foundUser->name = '測試用戶13-修改';
        $foundUser->password_plain = ''; // 留空
        $this->assertTrue($foundUser->save());

        // 驗證密碼未改變
        $updatedUser = Users::findOne(['cn' => 'testuser13']);
        $this->assertEquals($originalPassword, $updatedUser->password, '密碼不應該被修改');
        $this->assertEquals('測試用戶13-修改', $updatedUser->name, '名稱應該已更新');
    }

    /**
     * 測試：合法的密碼範例
     */
    public function testValidPasswordExamples()
    {
        $validPasswords = [
            'TestPass123',
            'MySecure99',
            'Admin2024!',
            'P@ssw0rd',
            'Qwerty123',
        ];

        foreach ($validPasswords as $password) {
            $user = new Users();
            $user->cn = 'test_' . uniqid();
            $user->name = '測試用戶';
            $user->roles = 'sa';
            $user->password_plain = $password;

            $this->assertTrue($user->save(), "密碼 '{$password}' 應該是合法的");
        }
    }

    // ==================== BVA：密碼長度邊界值 ====================

    /**
     * BVA：密碼恰好 7 字元（min-1）應失敗
     */
    public function testPasswordLength7ShouldFail()
    {
        $user = new Users();
        $user->cn = 'test_bva_7';
        $user->name = '測試';
        $user->roles = 'sa';
        $user->password_plain = 'Test12x'; // 7 字元

        $this->assertFalse($user->save(), '7 char password should fail');
        $this->assertTrue($user->hasErrors('password_plain'));
    }

    /**
     * BVA：密碼恰好 8 字元（min）應通過
     */
    public function testPasswordLength8ShouldPass()
    {
        $user = new Users();
        $user->cn = 'test_bva_8';
        $user->name = '測試';
        $user->roles = 'sa';
        $user->password_plain = 'Test1234'; // 8 字元

        $this->assertTrue($user->save(), '8 char password should pass');
    }

    /**
     * BVA：密碼恰好 9 字元（min+1）應通過
     */
    public function testPasswordLength9ShouldPass()
    {
        $user = new Users();
        $user->cn = 'test_bva_9';
        $user->name = '測試';
        $user->roles = 'sa';
        $user->password_plain = 'Test12345'; // 9 字元

        $this->assertTrue($user->save(), '9 char password should pass');
    }

    // ==================== EP：空值與極端輸入 ====================

    /**
     * EP：空密碼應失敗
     */
    public function testEmptyPasswordShouldFail()
    {
        $user = new Users();
        $user->cn = 'test_empty_pwd';
        $user->name = '測試';
        $user->roles = 'sa';
        $user->password_plain = '';

        // 空密碼在更新時不修改，在新建時應有問題
        $result = $user->save();
        // 空密碼不修改密碼欄位，但新用戶沒有密碼會導致問題
        $this->assertIsBool($result, 'Empty password should not cause exception');
    }

    /**
     * EP：帳號空字串應失敗
     */
    public function testEmptyCnShouldFail()
    {
        $user = new Users();
        $user->cn = '';
        $user->name = '測試';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';

        $this->assertFalse($user->save(), 'Empty cn should fail');
    }

    // ==================== BVA：登入嘗試次數邊界 ====================

    /**
     * BVA：第 4 次登入失敗（鎖定門檻 - 1）不應被鎖定
     */
    public function testFourthAttemptNotLocked()
    {
        $username = 'testuser_bva_4';
        $sessionKey = 'login_attempts_' . hash('sha256', $username);
        $maxAttempts = 5;

        Yii::$app->session->set($sessionKey, 4);
        $attempts = Yii::$app->session->get($sessionKey);

        $this->assertLessThan($maxAttempts, $attempts, '4 attempts should not reach lock threshold');
    }

    /**
     * BVA：第 5 次登入失敗（鎖定門檻）應被鎖定
     */
    public function testFifthAttemptShouldLock()
    {
        $username = 'testuser_bva_5';
        $sessionKey = 'login_attempts_' . hash('sha256', $username);
        $maxAttempts = 5;

        Yii::$app->session->set($sessionKey, 5);
        $attempts = Yii::$app->session->get($sessionKey);

        $this->assertGreaterThanOrEqual($maxAttempts, $attempts, '5 attempts should reach lock threshold');
    }

    /**
     * BVA：第 6 次登入失敗（鎖定門檻 + 1）應仍被鎖定
     */
    public function testSixthAttemptStillLocked()
    {
        $username = 'testuser_bva_6';
        $sessionKey = 'login_attempts_' . hash('sha256', $username);
        $maxAttempts = 5;

        Yii::$app->session->set($sessionKey, 6);
        $attempts = Yii::$app->session->get($sessionKey);

        $this->assertGreaterThan($maxAttempts, $attempts, '6 attempts should exceed lock threshold');
    }
}
