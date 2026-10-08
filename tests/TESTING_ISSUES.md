# 測試環境問題排查與解決方案

## 當前問題

執行 `php vendor/bin/codecept run unit` 時出現以下錯誤：

```
PHP Fatal error:  Uncaught Error: Class "Yii" not found in /var/www/html/voting/tests/unit/CandiConfigTest.php:298
```

## 問題分析

### 根本原因

測試類別的 **data provider** 方法在 Codeception 載入測試時就會被呼叫，但此時 Yii 應用程式尚未初始化。

在以下測試檔案中，data provider 使用了 `Yii::$app->security->generateRandomString()`：
- `tests/unit/CandiConfigTest.php::invalidProvider()`
- `tests/unit/CandiDataTest.php::createManualInvalidProvider()`
- `tests/unit/CandiDataTest.php::updateManualInvalidProvider()`

Codeception 的測試載入流程：
1. Codeception 掃描測試類別，收集所有測試方法
2. **呼叫所有 data provider 方法**（此時 Yii 尚未初始化）
3. Yii2 模組初始化應用程式
4. 執行測試

### 已完成的修復

✅ 修改了所有 bootstrap 檔案，移除重複的 autoloader 載入
✅ 建立了獨立的測試資料庫配置
✅ 創建了 `tests/_app.php` 測試配置檔案
✅ 修復了 VoteInterfaces 介面的載入問題

### 當前狀況

❌ Data provider 在 Yii 初始化前被呼叫，無法使用 `Yii::$app`
❌ 需要修改測試檔案中的 data provider，改用不依賴 Yii 的方式生成測試資料

## 解決方案

### 方案 A: 使用 PHPUnit 直接執行測試（推薦）

Yii2 專案可以直接使用 PHPUnit 而不依賴 Codeception：

```bash
# 安裝 PHPUnit（如果尚未安裝）
composer require --dev phpunit/phpunit

# 直接執行單元測試
./vendor/bin/phpunit tests/unit/AnonPartyVoteTest.php

# 執行所有單元測試
./vendor/bin/phpunit tests/unit/
```

**優點**：
- 避開 Codeception Yii2 模組的問題
- 更輕量，執行更快
- 與 Yii2 原生測試方式一致

### 方案 B: 檢查並移除全域 Yii2 路徑

查找可能設定全域 Yii2 的位置：

```bash
# 1. 檢查 PHP 配置
php -i | grep -i "include_path\|auto_prepend\|auto_append"

# 2. 檢查環境變數
env | grep -i yii
env | grep -i vendor

# 3. 檢查 Apache/Nginx 配置
grep -r "framework/yii2" /etc/httpd/ 2>/dev/null
grep -r "framework/yii2" /etc/nginx/ 2>/dev/null

# 4. 檢查使用者級配置
grep -r "framework/yii2" ~/.bashrc ~/.bash_profile ~/.profile 2>/dev/null
```

### 方案 C: 修改 Codeception 使用專案 Yii2

1. 確保沒有任何全域 PHP 配置載入 Yii2
2. 清理 Composer cache：
```bash
composer clear-cache
composer dump-autoload -o
```

3. 重新建置 Codeception：
```bash
php vendor/bin/codecept clean
php vendor/bin/codecept build
```

### 方案 D: 使用容器化測試環境

使用 Docker 隔離測試環境，避免系統層級的干擾：

```dockerfile
# Dockerfile.test
FROM php:8.1-cli
WORKDIR /app
COPY . /app
RUN composer install
CMD ["vendor/bin/codecept", "run", "unit"]
```

```bash
docker build -f Dockerfile.test -t voting-test .
docker run voting-test
```

## 測試資料庫設置（已完成）

測試資料庫配置已經完成，請執行以下步驟初始化：

```bash
# 方法 1: 使用自動腳本
./tests/_data/init_test_db.sh

# 方法 2: 手動執行
mysql -u root -p < tests/_data/setup_test_database.sql
mysqldump -u root -p --no-data voting | mysql -u voting_test -ptest_password_123 voting_test
php yii migrate
```

測試資料庫資訊：
- 資料庫名稱: `voting_test`
- 使用者: `voting_test`
- 密碼: `test_password_123`

## 驗證測試配置

執行簡單的測試確認資料庫連接：

```bash
php -r "
require 'vendor/autoload.php';
require 'vendor/yiisoft/yii2/Yii.php';
\$config = require 'config/test.php';
\$app = new yii\console\Application(\$config);
echo 'Database: ' . \$app->db->dsn . PHP_EOL;
echo 'Connection: ' . (\$app->db->getIsActive() ? 'OK' : 'Failed') . PHP_EOL;
"
```

預期輸出：
```
Database: mysql:host=localhost;dbname=voting_test
Connection: OK
```

## 下一步建議

1. **優先使用方案 A（PHPUnit）** - 最簡單直接
2. 如果需要使用 Codeception，請執行方案 B 找出全域 Yii2 的來源
3. 考慮使用 Docker 建立乾淨的測試環境（方案 D）

## 相關檔案

- [config/test.php](../config/test.php) - 測試資料庫配置
- [README_TEST_DATABASE.md](README_TEST_DATABASE.md) - 測試資料庫完整文檔
- [codeception.yml](../codeception.yml) - Codeception 配置
