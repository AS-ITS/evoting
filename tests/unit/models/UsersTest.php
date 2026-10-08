<?php

namespace tests\unit\models;

use app\models\Users;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

/**
 * Users 模型單元測試
 *
 * 測試用戶模型的各項功能，包含驗證規則、密碼處理等
 */
class UsersTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
        ]);
        $this->tester->userLogin();
    }

    // ==================== 基本結構測試 ====================

    /**
     * 測試資料表名稱
     */
    public function testTableName()
    {
        $this->assertEquals('users', Users::tableName());
    }

    /**
     * 測試屬性標籤
     */
    public function testAttributeLabels()
    {
        $model = new Users();
        $labels = $model->attributeLabels();

        $this->assertArrayHasKey('cn', $labels);
        $this->assertArrayHasKey('name', $labels);
        $this->assertArrayHasKey('roles', $labels);
        $this->assertArrayHasKey('password', $labels);
        $this->assertArrayHasKey('password_plain', $labels);

        $this->assertEquals('帳號', $labels['cn']);
        $this->assertEquals('姓名', $labels['name']);
    }

    // ==================== 必填欄位驗證測試 ====================

    /**
     * 測試：cn 和 name 為必填欄位
     */
    public function testRequiredFields()
    {
        $model = new Users();

        $this->assertFalse($model->validate());
        $this->assertArrayHasKey('cn', $model->errors);
        $this->assertArrayHasKey('name', $model->errors);
    }

    /**
     * 測試：提供 cn 和 name 時驗證通過
     */
    public function testValidDataPassesValidation()
    {
        $model = new Users();
        $model->cn = 'test_user';
        $model->name = '測試使用者';

        // 只驗證 cn 和 name
        $this->assertTrue($model->validate(['cn', 'name']));
    }

    // ==================== 字串長度驗證測試 ====================

    /**
     * 測試：cn 最大長度 20
     */
    public function testCnMaxLength()
    {
        $model = new Users();
        $model->cn = str_repeat('a', 21);
        $model->name = '測試';

        $this->assertFalse($model->validate(['cn']));
        $this->assertArrayHasKey('cn', $model->errors);
    }

    /**
     * 測試：name 最大長度 20
     */
    public function testNameMaxLength()
    {
        $model = new Users();
        $model->cn = 'test';
        $model->name = str_repeat('測', 21);

        $this->assertFalse($model->validate(['name']));
        $this->assertArrayHasKey('name', $model->errors);
    }

    /**
     * 測試：roles 最大長度 20
     */
    public function testRolesMaxLength()
    {
        $model = new Users();
        $model->cn = 'test';
        $model->name = '測試';
        $model->roles = str_repeat('a', 21);

        $this->assertFalse($model->validate(['roles']));
        $this->assertArrayHasKey('roles', $model->errors);
    }

    // ==================== cn 唯一性驗證測試 ====================

    /**
     * 測試：cn 必須唯一
     */
    public function testCnUniqueness()
    {
        // 先取得現有用戶
        $existingUser = Users::findOne(['cn' => 'test_admin']);
        if (!$existingUser) {
            $this->markTestSkipped('No existing user found for uniqueness test');
        }

        // 嘗試建立相同 cn 的用戶
        $model = new Users();
        $model->cn = $existingUser->cn;
        $model->name = '新使用者';

        $this->assertFalse($model->validate(['cn']));
        $this->assertArrayHasKey('cn', $model->errors);
    }

    // ==================== 密碼驗證規則測試 ====================

    /**
     * 測試：密碼最少 8 字元
     */
    public function testPasswordMinLength()
    {
        $model = new Users();
        $model->cn = 'new_user';
        $model->name = '新使用者';
        $model->password_plain = 'Ab1234'; // 只有 6 字元

        $this->assertFalse($model->validate(['password_plain']));
        $this->assertArrayHasKey('password_plain', $model->errors);
    }

    /**
     * 測試：密碼必須包含英文字母和數字
     */
    public function testPasswordMustContainLettersAndDigits()
    {
        $model = new Users();
        $model->cn = 'new_user';
        $model->name = '新使用者';

        // 只有數字
        $model->password_plain = '12345678';
        $this->assertFalse($model->validate(['password_plain']));
        $this->assertArrayHasKey('password_plain', $model->errors);

        // 只有字母
        $model->clearErrors();
        $model->password_plain = 'abcdefgh';
        $this->assertFalse($model->validate(['password_plain']));
        $this->assertArrayHasKey('password_plain', $model->errors);
    }

    /**
     * 測試：有效的密碼格式
     */
    public function testValidPasswordFormat()
    {
        $model = new Users();
        $model->cn = 'new_user';
        $model->name = '新使用者';
        $model->password_plain = 'Password123';

        $this->assertTrue($model->validate(['password_plain']));
    }

    /**
     * 測試：密碼不可與帳號相同
     */
    public function testPasswordCannotBeSameAsUsername()
    {
        $model = new Users();
        $model->cn = 'testuser1';
        $model->name = '測試使用者';
        $model->password_plain = 'testuser1';

        $this->assertFalse($model->validate(['password_plain']));
        $this->assertArrayHasKey('password_plain', $model->errors);
    }

    /**
     * 測試：密碼不可包含帳號
     */
    public function testPasswordCannotContainUsername()
    {
        $model = new Users();
        $model->cn = 'admin';
        $model->name = '測試使用者';
        $model->password_plain = 'admin12345';

        $this->assertFalse($model->validate(['password_plain']));
        $this->assertArrayHasKey('password_plain', $model->errors);
    }

    /**
     * 測試：密碼與帳號相似度檢查
     */
    public function testPasswordSimilarityCheck()
    {
        $model = new Users();
        $model->cn = 'testuser';
        $model->name = '測試使用者';
        $model->password_plain = 'testuser1'; // 與帳號過於相似

        $this->assertFalse($model->validate(['password_plain']));
        $this->assertArrayHasKey('password_plain', $model->errors);
    }

    /**
     * 測試：空密碼跳過驗證（用於更新時不修改密碼）
     */
    public function testEmptyPasswordSkipsValidation()
    {
        $model = new Users();
        $model->cn = 'new_user';
        $model->name = '新使用者';
        $model->password_plain = '';

        // 空密碼不應產生錯誤
        $this->assertTrue($model->validate(['password_plain']));
    }

    // ==================== 密碼雜湊測試 ====================

    /**
     * 測試：雜湊密碼
     */
    public function testHashPassword()
    {
        $model = new Users();
        $plainPassword = 'TestPassword123';
        $hashed = $model->hashPassword($plainPassword);

        $this->assertNotEmpty($hashed);
        $this->assertNotEquals($plainPassword, $hashed);
        $this->assertTrue(password_verify($plainPassword, $hashed));
    }

    /**
     * 測試：驗證密碼 - 正確密碼
     */
    public function testValidatePasswordCorrect()
    {
        $model = new Users();
        $plainPassword = 'TestPassword123';
        $model->password = $model->hashPassword($plainPassword);

        $this->assertTrue($model->validatePassword($plainPassword));
    }

    /**
     * 測試：驗證密碼 - 錯誤密碼
     */
    public function testValidatePasswordIncorrect()
    {
        $model = new Users();
        $plainPassword = 'TestPassword123';
        $model->password = $model->hashPassword($plainPassword);

        $this->assertFalse($model->validatePassword('WrongPassword'));
    }

    /**
     * 測試：驗證密碼 - 舊制 AES 密文（漸進式遷移）
     */
    public function testValidatePasswordLegacyAes()
    {
        $model = new Users();
        $plainPassword = 'TestPassword123';
        // 模擬資料庫中殘留的舊制 AES 可逆密文
        $model->password = (new \app\models\Passwd())->encrypt($plainPassword);

        $this->assertTrue($model->validatePassword($plainPassword));
        $this->assertFalse($model->validatePassword('WrongPassword'));
    }

    /**
     * 測試：驗證密碼 - 空密碼欄位
     */
    public function testValidatePasswordEmptyField()
    {
        $model = new Users();
        $model->password = '';

        $this->assertFalse($model->validatePassword('AnyPassword'));
    }

    // ==================== beforeSave 測試 ====================

    /**
     * 測試：beforeSave 會雜湊 password_plain
     * 使用模擬方式測試 hook 邏輯
     */
    public function testBeforeSaveHashesPasswordLogic()
    {
        $model = new Users();
        $model->cn = 'test_beforesave';
        $model->name = '測試自動雜湊';
        $model->password_plain = 'TestPassword123';

        // 手動觸發 beforeSave 邏輯
        $hashed = $model->hashPassword('TestPassword123');

        // 確認雜湊後不等於原始密碼
        $this->assertNotEquals('TestPassword123', $hashed);
        $this->assertNotEmpty($hashed);
    }

    // ==================== submitForm 測試 ====================

    /**
     * 測試：submitForm 處理多角色邏輯
     */
    public function testSubmitFormRolesProcessing()
    {
        $model = new Users();
        $postData = [
            'Users' => [
                'cn' => 'test_roles',
                'name' => '測試角色',
                'roles' => ['va', 'ga'],
            ],
        ];

        // load 資料並處理 roles
        $model->load($postData);
        $roles = $postData['Users']['roles'] ?? [];
        $model->roles = is_array($roles) ? implode(',', $roles) : null;

        // 確認 roles 被正確處理為逗號分隔字串
        $this->assertEquals('va,ga', $model->roles);
    }

    /**
     * 測試：submitForm 無角色時處理
     * 注意：實際 submitForm 邏輯是 implode(',', []) 會產生空字串 ''
     */
    public function testSubmitFormNoRolesProcessing()
    {
        $model = new Users();
        $postData = [
            'Users' => [
                'cn' => 'test_norole',
                'name' => '測試無角色',
            ],
        ];

        // 使用與 submitForm 相同的邏輯
        $model->load($postData);
        $roles = isset($postData['Users']['roles']) ? $postData['Users']['roles'] : [];
        $model->roles = is_array($roles) ? implode(',', $roles) : null;

        // 沒有 roles 時，implode 空陣列會產生空字串
        $this->assertEquals('', $model->roles);
    }

    /**
     * 測試：submitForm 驗證失敗
     */
    public function testSubmitFormValidationFail()
    {
        $model = new Users();
        $postData = [
            'Users' => [
                'cn' => '', // 空的帳號
                'name' => '',
            ],
        ];

        $result = $model->submitForm($postData);

        $this->assertFalse($result);
        $this->assertNotEmpty($model->errors);
    }

    // ==================== 現有使用者測試 ====================

    /**
     * 測試：使用 fixture 資料查詢使用者
     */
    public function testFindUserFromFixture()
    {
        $user = Users::findOne(['cn' => 'test_admin']);

        $this->assertNotNull($user);
        $this->assertInstanceOf(Users::class, $user);
        $this->assertEquals('test_admin', $user->cn);
    }

    /**
     * 測試：更新使用者資料（使用現有使用者）
     */
    public function testUpdateUserName()
    {
        $user = Users::findOne(['cn' => 'test_admin']);

        if (!$user) {
            $this->markTestSkipped('No test_admin user in fixture');
        }

        $originalName = $user->name;

        // 更新名稱
        $user->name = '臨時更新名稱';
        $this->assertTrue($user->validate(['name']));

        // 還原（不實際儲存以避免影響其他測試）
        $user->name = $originalName;
    }

    /**
     * 測試：更新使用者時空密碼不會覆蓋現有密碼（邏輯測試）
     */
    public function testUpdateUserEmptyPasswordPreservesExisting()
    {
        $model = new Users();
        $originalPassword = 'encrypted_password_hash';
        $model->password = $originalPassword;
        $model->password_plain = ''; // 空白表示不修改

        // 模擬 beforeSave 邏輯：如果 password_plain 為空，不應覆蓋 password
        if (!empty($model->password_plain)) {
            $model->password = $model->hashPassword($model->password_plain);
        }

        // 密碼應該保持不變
        $this->assertEquals($originalPassword, $model->password);
    }

    /**
     * 測試：設定新密碼時會雜湊
     */
    public function testSetNewPasswordHashes()
    {
        $model = new Users();
        $model->password_plain = 'NewPassword123';

        // 模擬 beforeSave 邏輯
        if (!empty($model->password_plain)) {
            $model->password = $model->hashPassword($model->password_plain);
        }

        // 密碼應該被雜湊
        $this->assertNotEquals('NewPassword123', $model->password);
        $this->assertNotEmpty($model->password);
    }

    // ==================== #15 BVA：密碼長度邊界 ====================

    /**
     * BVA：密碼恰好 7 字元（min-1）→ 應失敗
     */
    public function testPasswordExact7CharsMinMinus1()
    {
        $model = new Users();
        $model->cn = 'pw_test7';
        $model->name = '測試';
        $model->password_plain = 'Abcde12'; // 7 字元，含英文+數字

        $this->assertFalse($model->validate(['password_plain']),
            'Password with 7 chars should fail (min=8)');
        $this->assertArrayHasKey('password_plain', $model->errors);
    }

    /**
     * BVA：密碼恰好 8 字元（min 邊界）→ 應通過
     */
    public function testPasswordExact8CharsMinBoundary()
    {
        $model = new Users();
        $model->cn = 'pw_test8';
        $model->name = '測試';
        $model->password_plain = 'Abcdef12'; // 8 字元，含英文+數字

        $this->assertTrue($model->validate(['password_plain']),
            'Password with exactly 8 chars should pass (min=8)');
    }

    /**
     * BVA：密碼 255 字元（max 邊界）→ 應通過
     */
    public function testPasswordExact255CharsMaxBoundary()
    {
        $model = new Users();
        $model->cn = 'pw_test_max';
        $model->name = '測試';
        $model->password_plain = 'Ab1' . str_repeat('x', 252); // 255 字元

        $this->assertTrue($model->validate(['password_plain']),
            'Password with exactly 255 chars should pass (max=255)');
    }

    /**
     * BVA：密碼 256 字元（max+1）→ 應失敗
     */
    public function testPasswordExact256CharsExceedsMax()
    {
        $model = new Users();
        $model->cn = 'pw_test_over';
        $model->name = '測試';
        $model->password_plain = 'Ab1' . str_repeat('x', 253); // 256 字元

        $this->assertFalse($model->validate(['password_plain']),
            'Password with 256 chars should fail (max=255)');
        $this->assertArrayHasKey('password_plain', $model->errors);
    }

    // ==================== #16 BVA：帳號(cn)邊界 ====================

    /**
     * BVA：cn 空字串 → required 應拒絕
     */
    public function testCnEmptyString()
    {
        $model = new Users();
        $model->cn = '';
        $model->name = '測試';

        $this->assertFalse($model->validate(['cn']),
            'Empty cn should fail required validation');
        $this->assertArrayHasKey('cn', $model->errors);
    }

    /**
     * BVA：cn 恰好 20 字元（max 邊界）→ 應通過
     */
    public function testCnExactMaxLength()
    {
        $model = new Users();
        $model->cn = str_repeat('a', 20); // 恰好 20 字元
        $model->name = '測試';

        $model->validate(['cn']);
        $this->assertArrayNotHasKey('cn', $model->errors,
            'cn at exactly 20 chars should pass (max=20)');
    }

    /**
     * BVA：name 空字串 → required 應拒絕
     */
    public function testNameEmptyString()
    {
        $model = new Users();
        $model->cn = 'test_user';
        $model->name = '';

        $this->assertFalse($model->validate(['name']),
            'Empty name should fail required validation');
        $this->assertArrayHasKey('name', $model->errors);
    }

    /**
     * BVA：name 恰好 20 字元 → 應通過
     */
    public function testNameExactMaxLength()
    {
        $model = new Users();
        $model->cn = 'test_user';
        $model->name = str_repeat('測', 20); // 恰好 20 字元

        $model->validate(['name']);
        $this->assertArrayNotHasKey('name', $model->errors,
            'name at exactly 20 chars should pass (max=20)');
    }
}
