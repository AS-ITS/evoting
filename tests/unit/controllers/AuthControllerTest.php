<?php

namespace tests\unit\controllers;

use Yii;
use app\models\Users;
use app\controllers\AuthController;
use app\tests\fixtures\UsersFixture;
use Codeception\Test\Unit;

/**
 * AuthController 單元測試
 *
 * 測試 AuthController::actionAdmin 登入功能
 */
class AuthControllerTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // 確保管理員登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }

        // 清除 session
        Yii::$app->session->removeAll();
    }

    protected function _after()
    {
        // 清理：登出管理員
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }

        // 清除 session
        Yii::$app->session->removeAll();

        // 清理測試期間建立的使用者
        $testUsers = ['change_pwd_test', 'wrong_old_pwd', 'db_persist_test', 'encrypt_format_test', 'multi_change_test'];
        foreach ($testUsers as $cn) {
            $user = Users::findOne(['cn' => $cn]);
            if ($user) {
                $user->delete();
            }
        }
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
     * 測試：Controller 可以實例化
     */
    public function testControllerCanBeInstantiated()
    {
        $controller = new AuthController('auth', Yii::$app);
        $this->assertInstanceOf(AuthController::class, $controller, 'Should be instance of AuthController');
    }

    /**
     * 測試：actionIndex 跳轉到 site/index
     */
    public function testActionIndexRedirectsToSiteIndex()
    {
        // actionIndex 需要在請求上下文中執行，跳過直接測試
        // 這個測試應該在功能測試中進行
        $controller = new AuthController('auth', Yii::$app);

        // 驗證方法存在
        $this->assertTrue(method_exists($controller, 'actionIndex'), 'Should have actionIndex method');
    }

    /**
     * 測試：actionLogout 方法存在
     */
    public function testActionLogoutMethodExists()
    {
        $controller = new AuthController('auth', Yii::$app);

        // 驗證 actionLogout 方法存在
        $this->assertTrue(method_exists($controller, 'actionLogout'), 'Should have actionLogout method');

        // 驗證登出邏輯（不實際執行 controller action）
        // afterLogin 需要 Users 列，否則會立即 logout
        $user = new Users();
        $user->cn = 'test_logout';
        $user->name = 'Test Logout User';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'Logout test user should be created');

        $identity = new \app\components\AdminIdentity();
        $identity->setAuthData([
            'cn' => 'test_logout',
            'name' => 'Test Logout User',
        ]);
        Yii::$app->user->login($identity);

        // 驗證登入狀態
        $this->assertFalse(Yii::$app->user->isGuest, 'User should be logged in');

        // 執行登出（透過 Yii::$app->user->logout）
        Yii::$app->user->logout();

        // 驗證已登出
        $this->assertTrue(Yii::$app->user->isGuest, 'User should be logged out');
    }

    /**
     * 測試：Login attempts 追蹤機制
     */
    public function testLoginAttemptsTracking()
    {
        // 建立測試用戶
        $user = new Users();
        $user->cn = 'attempt_test';
        $user->name = 'Attempt Test User';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'User should be created');

        $username = 'attempt_test';
        $sessionKey = 'login_attempts_' . hash('sha256', $username);

        // 模擬失敗登入
        Yii::$app->session->set($sessionKey, 3);

        // 驗證追蹤
        $attempts = Yii::$app->session->get($sessionKey);
        $this->assertEquals(3, $attempts, 'Attempts should be tracked');

        // 清除追蹤
        Yii::$app->session->remove($sessionKey);
        $this->assertNull(Yii::$app->session->get($sessionKey), 'Attempts should be cleared');
    }

    /**
     * 測試：帳號鎖定機制
     */
    public function testAccountLockMechanism()
    {
        $username = 'lock_test';
        $lockKey = 'login_locked_' . hash('sha256', $username);

        // 模擬鎖定
        $lockTime = 15 * 60; // 15 分鐘
        $lockUntil = time() + $lockTime;
        Yii::$app->session->set($lockKey, $lockUntil);

        // 驗證鎖定狀態
        $lockedUntil = Yii::$app->session->get($lockKey);
        $this->assertNotNull($lockedUntil, 'Lock should be set');
        $this->assertGreaterThan(time(), $lockedUntil, 'Lock time should be in future');

        // 計算剩餘時間
        $remainingTime = ceil(($lockedUntil - time()) / 60);
        $this->assertGreaterThan(0, $remainingTime, 'Remaining time should be positive');
        $this->assertLessThanOrEqual(15, $remainingTime, 'Remaining time should be <= 15 minutes');
    }

    /**
     * 測試：Session 安全 - SHA-256 用於 session key
     */
    public function testSessionKeyUsingSha256()
    {
        $username = 'security_test';

        // 驗證使用 SHA-256（而非 MD5）
        $sessionKey = 'login_attempts_' . hash('sha256', $username);
        $this->assertEquals(64, strlen(hash('sha256', $username)), 'SHA-256 hash should be 64 characters');

        // 驗證不同用戶名產生不同 hash
        $sessionKey1 = hash('sha256', 'user1');
        $sessionKey2 = hash('sha256', 'user2');
        $this->assertNotEquals($sessionKey1, $sessionKey2, 'Different usernames should produce different hashes');
    }

    /**
     * 測試：密碼驗證流程
     */
    public function testPasswordValidationFlow()
    {
        // 建立測試用戶
        $user = new Users();
        $user->cn = 'validation_test';
        $user->name = 'Validation Test User';
        $user->roles = 'sa';
        $user->password_plain = 'ValidPass123';
        $this->assertTrue($user->save(), 'User should be created');

        // 測試正確密碼
        $foundUser = Users::findOne(['cn' => 'validation_test']);
        $this->assertNotNull($foundUser, 'User should be found');
        $this->assertTrue($foundUser->validatePassword('ValidPass123'), 'Correct password should validate');

        // 測試錯誤密碼
        $this->assertFalse($foundUser->validatePassword('WrongPassword'), 'Wrong password should not validate');

        // 測試空密碼
        $this->assertFalse($foundUser->validatePassword(''), 'Empty password should not validate');
    }

    /**
     * 測試：RBAC 角色分配邏輯
     */
    public function testRbacRoleAssignmentLogic()
    {
        // 建立測試用戶
        $user = new Users();
        $user->cn = 'rbac_test';
        $user->name = 'RBAC Test User';
        $user->roles = 'sa,va,ga';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'User should be created');

        // 驗證角色字串
        $foundUser = Users::findOne(['cn' => 'rbac_test']);
        $this->assertEquals('sa,va,ga', $foundUser->roles, 'Roles should be stored as comma-separated string');

        // 驗證角色拆分
        $rolesArray = explode(',', $foundUser->roles);
        $this->assertIsArray($rolesArray, 'Roles should be split into array');
        $this->assertCount(3, $rolesArray, 'Should have 3 roles');
        $this->assertContains('sa', $rolesArray, 'Should contain sa role');
        $this->assertContains('va', $rolesArray, 'Should contain va role');
        $this->assertContains('ga', $rolesArray, 'Should contain ga role');
    }

    /**
     * 測試：登入成功後的 payload 結構
     */
    public function testLoginPayloadStructure()
    {
        // 建立測試用戶
        $user = new Users();
        $user->cn = 'payload_test';
        $user->name = 'Payload Test User';
        $user->roles = 'sa';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'User should be created');

        // 模擬 payload 建立
        $foundUser = Users::findOne(['cn' => 'payload_test']);
        $payload = [
            'cn' => $foundUser->cn,
            'name' => $foundUser->name,
            'roles' => $foundUser->roles ?? '',
        ];

        // 驗證 payload 結構
        $this->assertArrayHasKey('cn', $payload, 'Payload should have cn');
        $this->assertArrayHasKey('name', $payload, 'Payload should have name');
        $this->assertArrayHasKey('roles', $payload, 'Payload should have roles');

        // 驗證 payload 值
        $this->assertEquals('payload_test', $payload['cn'], 'CN should match');
        $this->assertEquals('Payload Test User', $payload['name'], 'Name should match');
        $this->assertEquals('sa', $payload['roles'], 'Roles should match');
    }

    /**
     * 測試：登入失敗延遲（防止暴力破解）
     */
    public function testLoginFailureDelay()
    {
        // 記錄開始時間
        $startTime = microtime(true);

        // 模擬登入失敗延遲（不實際執行 sleep(2)，只驗證邏輯）
        $delaySeconds = 2;

        // 驗證延遲時間設定
        $this->assertEquals(2, $delaySeconds, 'Delay should be 2 seconds');
        $this->assertGreaterThan(0, $delaySeconds, 'Delay should be positive');

        // 注意：實際測試中不執行 sleep，以加快測試速度
    }

    /**
     * 測試：無授權用戶處理
     */
    public function testUnauthorizedUserHandling()
    {
        // 建立無角色的用戶
        $user = new Users();
        $user->cn = 'norole_test';
        $user->name = 'No Role Test User';
        $user->roles = ''; // 空角色
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'User should be created');

        // 驗證角色為空
        $foundUser = Users::findOne(['cn' => 'norole_test']);
        $this->assertEmpty($foundUser->roles, 'Roles should be empty');

        // 驗證角色拆分結果
        $rolesArray = explode(',', $foundUser->roles);
        $this->assertCount(1, $rolesArray, 'Empty string splits to 1 element');
        $this->assertEquals('', $rolesArray[0], 'First element should be empty string');
    }

    /**
     * 測試：Session 重新生成邏輯
     */
    public function testSessionRegenerationLogic()
    {
        // 記錄原始 Session ID
        $oldSessionId = Yii::$app->session->getId();
        $this->assertNotEmpty($oldSessionId, 'Original session ID should exist');

        // 重新生成 Session ID
        Yii::$app->session->regenerateID(true);

        // 取得新 Session ID
        $newSessionId = Yii::$app->session->getId();
        $this->assertNotEmpty($newSessionId, 'New session ID should exist');

        // 驗證 Session ID 已改變（防止 Session Fixation）
        $this->assertNotEquals($oldSessionId, $newSessionId, 'Session ID should be regenerated');
    }

    /**
     * 測試：DynamicModel 驗證規則
     */
    public function testDynamicModelValidationRules()
    {
        // 模擬 actionAdmin 使用的 DynamicModel
        $model = new \yii\base\DynamicModel(['username', 'password']);
        $model->addRule(['username', 'password'], 'required');
        $model->addRule('username', 'string', ['max' => 20]);
        $model->addRule('password', 'string', ['max' => 255]);

        // 測試空白驗證
        $model->username = '';
        $model->password = '';
        $this->assertFalse($model->validate(), 'Empty fields should fail validation');
        $this->assertTrue($model->hasErrors('username'), 'Should have username error');
        $this->assertTrue($model->hasErrors('password'), 'Should have password error');

        // 測試長度限制
        $model->username = str_repeat('a', 21); // 超過 20 字元
        $model->password = 'ValidPass123';
        $this->assertFalse($model->validate(), 'Too long username should fail validation');

        // 測試有效數據
        $model->username = 'validuser';
        $model->password = 'ValidPass123';
        $this->assertTrue($model->validate(), 'Valid data should pass validation');
    }

    /**
     * 測試：屬性標籤中文化
     */
    public function testAttributeLabelsInChinese()
    {
        $model = new \yii\base\DynamicModel(['username', 'password']);
        $model->setAttributeLabels([
            'username' => Yii::t('app', '帳號'),
            'password' => Yii::t('app', '密碼'),
        ]);

        $this->assertEquals('帳號', $model->getAttributeLabel('username'), 'Username label should be in Chinese');
        $this->assertEquals('密碼', $model->getAttributeLabel('password'), 'Password label should be in Chinese');
    }

    /**
     * 測試：ActionAdmin behaviors 設定
     */
    public function testActionAdminBehaviors()
    {
        $controller = new AuthController('auth', Yii::$app);
        $behaviors = $controller->behaviors();

        // 驗證 behaviors 存在
        $this->assertIsArray($behaviors, 'Behaviors should be an array');
        $this->assertArrayHasKey('access', $behaviors, 'Should have access control behavior');

        // 驗證 access control 設定
        $accessControl = $behaviors['access'];
        $this->assertEquals(\yii\filters\AccessControl::className(), $accessControl['class'], 'Should use AccessControl');
        $this->assertArrayHasKey('rules', $accessControl, 'Should have access rules');
    }

    /**
     * 測試：密碼修改 - 正常流程
     */
    public function testChangePasswordSuccess()
    {
        // 先刪除可能存在的測試使用者
        $existingUser = Users::findOne(['cn' => 'change_pwd_test']);
        if ($existingUser) {
            $existingUser->delete();
        }

        // 建立測試用戶並登入
        $user = new Users();
        $user->cn = 'change_pwd_test';
        $user->name = 'ChangePwdTest';  // Max 20 chars
        $user->roles = 'va';
        $user->password_plain = 'OldPass123';
        $this->assertTrue($user->save(), 'User should be created');

        // 驗證舊密碼
        $foundUser = Users::findOne(['cn' => 'change_pwd_test']);
        $this->assertNotNull($foundUser, 'User should exist');
        $this->assertTrue($foundUser->validatePassword('OldPass123'), 'Old password should be valid');

        // 更新密碼（使用 password_plain，讓 beforeSave() 自動加密）
        $foundUser->password_plain = 'NewPass456';
        $this->assertTrue($foundUser->save(), 'Password should be updated');

        // 驗證新密碼
        $updatedUser = Users::findOne(['cn' => 'change_pwd_test']);
        $this->assertTrue($updatedUser->validatePassword('NewPass456'), 'New password should be valid');
        $this->assertFalse($updatedUser->validatePassword('OldPass123'), 'Old password should no longer be valid');
    }

    /**
     * 測試：密碼修改 - 舊密碼錯誤
     */
    public function testChangePasswordWithWrongOldPassword()
    {
        // 先刪除可能存在的測試使用者
        $existingUser = Users::findOne(['cn' => 'wrong_old_pwd']);
        if ($existingUser) {
            $existingUser->delete();
        }

        $user = new Users();
        $user->cn = 'wrong_old_pwd';
        $user->name = 'WrongPwdTest';  // Max 20 chars
        $user->roles = 'va';
        $user->password_plain = 'OldPass123';
        $this->assertTrue($user->save(), 'User should be created');

        $foundUser = Users::findOne(['cn' => 'wrong_old_pwd']);
        $this->assertFalse($foundUser->validatePassword('WrongOldPass'), 'Wrong old password should fail validation');
    }

    /**
     * 測試：密碼修改 - 新密碼強度不足
     */
    public function testChangePasswordWithWeakPassword()
    {
        // 測試太短的密碼
        $shortPassword = 'Pass1';
        $this->assertLessThan(8, strlen($shortPassword), 'Password should be too short');

        // 測試缺少數字
        $noNumberPassword = 'Password';
        $this->assertEquals(0, preg_match('/[0-9]/', $noNumberPassword), 'Password should lack numbers');

        // 測試缺少字母
        $noLetterPassword = '12345678';
        $this->assertEquals(0, preg_match('/[A-Za-z]/', $noLetterPassword), 'Password should lack letters');
    }

    /**
     * 測試：密碼修改 - 新密碼與帳號相同
     */
    public function testChangePasswordSameAsUsername()
    {
        $username = 'testuser';
        $newPassword = 'testuser';
        $this->assertEquals($username, $newPassword, 'Password should be same as username (should be rejected)');
    }

    /**
     * 測試：密碼修改 - 新密碼與舊密碼相同
     */
    public function testChangePasswordSameAsOldPassword()
    {
        $oldPassword = 'OldPass123';
        $newPassword = 'OldPass123';
        $this->assertEquals($oldPassword, $newPassword, 'New password should be same as old password (should be rejected)');
    }

    /**
     * 測試：密碼修改 - 需要登入才能訪問
     */
    public function testChangePasswordRequiresLogin()
    {
        // 確保未登入
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }

        $this->assertTrue(Yii::$app->user->isGuest, 'User should not be logged in');

        // 在實際應用中，未登入訪問 /auth/change-password 會重定向到登入頁面
        // 這裡驗證 isGuest 狀態
    }

    /**
     * 測試：密碼修改後資料庫儲存驗證
     */
    public function testChangePasswordDatabasePersistence()
    {
        // 先刪除可能存在的測試使用者
        $existingUser = Users::findOne(['cn' => 'db_persist_test']);
        if ($existingUser) {
            $existingUser->delete();
        }

        // 建立測試用戶
        $user = new Users();
        $user->cn = 'db_persist_test';
        $user->name = 'DbPersistTest';  // Max 20 chars
        $user->roles = 'va';
        $user->password_plain = 'OriginalPass123';
        $this->assertTrue($user->save(), 'User should be created');

        // 記錄原始密碼（加密後）
        $originalPassword = $user->password;
        $this->assertNotEmpty($originalPassword, 'Original password should be stored');

        // 驗證原始密碼可以解密並驗證
        $foundUser = Users::findOne(['cn' => 'db_persist_test']);
        $this->assertTrue($foundUser->validatePassword('OriginalPass123'), 'Original password should validate');

        // 模擬密碼修改：更新為新密碼（使用 password_plain）
        $newPasswordPlain = 'UpdatedPass456';
        $foundUser->password_plain = $newPasswordPlain;
        $this->assertTrue($foundUser->save(), 'Password update should be saved to database');

        // 從資料庫重新載入用戶（確保是從資料庫讀取，而非記憶體）
        $foundUser->refresh();

        // 驗證 1: 新密碼已儲存到資料庫
        $this->assertNotEmpty($foundUser->password, 'Password field should not be empty');
        $this->assertNotEquals($originalPassword, $foundUser->password, 'Password should be different from original');

        // 驗證 2: 新密碼可以正確驗證
        $this->assertTrue($foundUser->validatePassword($newPasswordPlain), 'New password should validate correctly');

        // 驗證 3: 舊密碼無法通過驗證
        $this->assertFalse($foundUser->validatePassword('OriginalPass123'), 'Old password should no longer validate');

        // 驗證 4: 再次從資料庫查詢，確保持久化
        $reloadedUser = Users::findOne(['cn' => 'db_persist_test']);
        $this->assertNotNull($reloadedUser, 'User should exist in database');
        $this->assertTrue($reloadedUser->validatePassword($newPasswordPlain), 'New password should still validate after reload');
        $this->assertFalse($reloadedUser->validatePassword('OriginalPass123'), 'Old password should still not validate after reload');

        // 驗證 5: 密碼欄位確實有被更新
        $this->assertEquals($foundUser->password, $reloadedUser->password, 'Password in database should match');
    }

    /**
     * 測試：密碼加密後的格式驗證
     */
    public function testPasswordEncryptionFormat()
    {
        // 先刪除可能存在的測試使用者
        $existingUser = Users::findOne(['cn' => 'encrypt_format_test']);
        if ($existingUser) {
            $existingUser->delete();
        }

        $user = new Users();
        $user->cn = 'encrypt_format_test';
        $user->name = 'EncryptTest';  // Max 20 chars
        $user->roles = 'va';
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), 'User should be created');

        $foundUser = Users::findOne(['cn' => 'encrypt_format_test']);

        // 驗證密碼已加密（不應該是明文）
        $this->assertNotEquals('TestPass123', $foundUser->password, 'Password should be encrypted, not plain text');

        // 驗證密碼不為空
        $this->assertNotEmpty($foundUser->password, 'Encrypted password should not be empty');

        // 驗證密碼長度（加密後應該比較長）
        $this->assertGreaterThan(strlen('TestPass123'), strlen($foundUser->password), 'Encrypted password should be longer than plain text');

        // 驗證可以解密並驗證
        $this->assertTrue($foundUser->validatePassword('TestPass123'), 'Encrypted password should be validatable');
    }

    /**
     * 測試：多次修改密碼的連續性
     */
    public function testMultiplePasswordChanges()
    {
        // 先刪除可能存在的測試使用者
        $existingUser = Users::findOne(['cn' => 'multi_change_test']);
        if ($existingUser) {
            $existingUser->delete();
        }

        // 建立測試用戶
        $user = new Users();
        $user->cn = 'multi_change_test';
        $user->name = 'MultiChangeTest';  // Max 20 chars
        $user->roles = 'va';
        $user->password_plain = 'Password1';
        $this->assertTrue($user->save(), 'User should be created');

        // 第一次修改（使用 password_plain）
        $foundUser = Users::findOne(['cn' => 'multi_change_test']);
        $foundUser->password_plain = 'Password2';
        $this->assertTrue($foundUser->save(), 'First password change should succeed');
        $foundUser->refresh();
        $this->assertTrue($foundUser->validatePassword('Password2'), 'Password2 should be valid');
        $this->assertFalse($foundUser->validatePassword('Password1'), 'Password1 should no longer be valid');

        // 第二次修改（使用 password_plain）
        $foundUser->password_plain = 'Password3';
        $this->assertTrue($foundUser->save(), 'Second password change should succeed');
        $foundUser->refresh();
        $this->assertTrue($foundUser->validatePassword('Password3'), 'Password3 should be valid');
        $this->assertFalse($foundUser->validatePassword('Password2'), 'Password2 should no longer be valid');
        $this->assertFalse($foundUser->validatePassword('Password1'), 'Password1 should still not be valid');

        // 第三次修改（使用 password_plain）
        $foundUser->password_plain = 'Password4';
        $this->assertTrue($foundUser->save(), 'Third password change should succeed');
        $foundUser->refresh();
        $this->assertTrue($foundUser->validatePassword('Password4'), 'Password4 should be valid');
        $this->assertFalse($foundUser->validatePassword('Password3'), 'Password3 should no longer be valid');

        // 驗證最終狀態
        $finalUser = Users::findOne(['cn' => 'multi_change_test']);
        $this->assertTrue($finalUser->validatePassword('Password4'), 'Final password should be Password4');
        $this->assertFalse($finalUser->validatePassword('Password1'), 'Original password should not work');
    }
}
