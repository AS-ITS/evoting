# 管理員登入功能測試指南

## 測試文件位置
- 測試檔案：`tests/unit/AdminLoginTest.php`
- Fixture 檔案：`tests/fixtures/UsersFixture.php`
- 測試數據：`tests/fixtures/data/users.php`

## 執行測試

### 方式一：使用 Codeception (推薦)

```bash
# 執行所有 unit tests
php vendor/bin/codecept run unit

# 只執行管理員登入測試
php vendor/bin/codecept run unit AdminLoginTest

# 顯示詳細輸出
php vendor/bin/codecept run unit AdminLoginTest --debug
```

### 方式二：使用 PHPUnit 直接執行

如果 Codeception 有環境問題，可以直接使用 PHPUnit：

```bash
# 執行單一測試檔案
vendor/bin/phpunit tests/unit/AdminLoginTest.php

# 執行特定測試方法
vendor/bin/phpunit --filter testSuccessfulLogin tests/unit/AdminLoginTest.php
```

### 方式三：在測試環境中執行

```bash
# 進入測試目錄
cd /var/www/html/voting

# 確保環境變數設定
export YII_ENV=test

# 執行測試
php vendor/bin/codecept run unit AdminLoginTest
```

## 測試涵蓋項目

### 1. 密碼驗證測試
- ✅ `testSuccessfulLogin` - 成功登入
- ✅ `testPasswordValidationFailed` - 密碼驗證失敗
- ✅ `testPasswordEncryption` - 密碼加密儲存

### 2. 密碼強度測試
- ✅ `testPasswordLengthValidation` - 長度不足（< 8 字元）
- ✅ `testPasswordMustContainNumbers` - 缺少數字
- ✅ `testPasswordMustContainLetters` - 缺少字母
- ✅ `testPasswordCannotBeSameAsUsername` - 與帳號相同
- ✅ `testPasswordCannotContainUsername` - 包含帳號
- ✅ `testPasswordSimilarityCheck` - 相似度檢查

### 3. 暴力破解防護測試
- ✅ `testLoginAttemptsTracking` - 登入失敗次數記錄
- ✅ `testAccountLockAfterMaxAttempts` - 達到上限後鎖定（5次失敗 = 15分鐘鎖定）
- ✅ `testClearAttemptsAfterSuccessfulLogin` - 成功登入後清除失敗記錄

### 4. 安全性測試
- ✅ `testPasswordDecryption` - 密碼加解密功能
- ✅ `testSessionRegenerationAfterLogin` - Session ID 重新生成（防止 Session Fixation）
- ✅ `testUpdateUserWithoutChangingPassword` - 更新時留空密碼不修改
- ✅ `testValidPasswordExamples` - 合法密碼範例

## 測試前準備

### 1. 確保資料庫連線正常
測試會使用測試資料庫，請確認 `config/test.php` 或 `config/test_db.php` 設定正確。

### 2. 清理測試數據
測試前後會自動載入/卸載 fixtures，但建議手動確認：

```bash
# 卸載所有 fixtures
php yii fixture/unload "*"

# 重新載入需要的 fixtures
php yii fixture/load "Users"
```

### 3. 檢查權限
確保測試檔案有執行權限：

```bash
chmod +x tests/unit/AdminLoginTest.php
```

## 故障排除

### 問題 1：Composer autoloader 錯誤

如果出現 `Cannot declare class ComposerAutoloaderInit...` 錯誤：

```bash
# 重新生成 autoloader
composer dump-autoload

# 重建 Codeception
php vendor/bin/codecept build
```

### 問題 2：找不到 Users model

確認 Users model 存在且可用：

```bash
php yii
# 應該不會報錯
```

### 問題 3：Session 錯誤

測試中使用 session，確保 session 可用：

```php
// 在測試中檢查
var_dump(Yii::$app->session);
```

### 問題 4：資料庫連線失敗

檢查測試環境的資料庫設定：

```bash
# 查看設定檔
cat config/test.php
cat config/test_db.php
```

## 測試結果解讀

### 成功範例
```
Codeception PHP Testing Framework v5.1.2

Unit Tests (15) ------------------------
✔ AdminLoginTest: Successful login (0.12s)
✔ AdminLoginTest: Password validation failed (0.08s)
✔ AdminLoginTest: Password encryption (0.09s)
...
✔ AdminLoginTest: Valid password examples (0.15s)

Time: 00:02.456, Memory: 28.00 MB

OK (15 tests, 35 assertions)
```

### 失敗範例
```
✖ AdminLoginTest: Password length validation (0.05s)
  Failed asserting that false is true.
  密碼長度不足應該無法儲存
```

## 持續整合 (CI/CD)

可以將測試加入 CI/CD pipeline：

```yaml
# .gitlab-ci.yml 或 .github/workflows/test.yml
test:
  script:
    - php vendor/bin/codecept run unit AdminLoginTest
```

## 測試覆蓋率報告

產生測試覆蓋率報告：

```bash
php vendor/bin/codecept run unit AdminLoginTest --coverage --coverage-html
```

報告會產生在 `tests/_output/coverage/` 目錄。

## 相關檔案

- Controller: `controllers/OidcController.php::actionAdmin()`
- Model: `models/Users.php`
- Password Model: `models/Passwd.php`
- View: `views/site/admin-login.php`
- Translations: `messages/en-US/app.php`
- Log Interface: `interfaces/LogInterface.php`

## 注意事項

1. **不使用 MD5**：已將 `md5()` 改為 `hash('sha256')` 以符合資安規範
2. **密碼加密**：使用 AES-256-CBC 加密儲存
3. **失敗鎖定**：5 次失敗鎖定 15 分鐘
4. **Session 安全**：登入成功後重新生成 Session ID
5. **CSRF 保護**：已啟用 CSRF token 驗證
6. **日誌記錄**：所有登入嘗試都會記錄（含 IP、次數）

## 更多資訊

詳細的資安檢測報告請參考專案的 Security Assessment 文件。
