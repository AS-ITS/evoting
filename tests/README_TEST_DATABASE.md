# 測試資料庫設置指南

本文件說明如何設置和使用獨立的測試資料庫，避免測試過程影響開發資料。

## 為什麼需要測試資料庫？

Codeception 測試框架的 `cleanup: true` 設定會在每次測試後自動清理資料庫，這是為了：

- ✅ 確保測試隔離（每個測試都在乾淨的環境中執行）
- ✅ 測試結果可重複
- ✅ 測試之間不會互相影響

使用獨立的測試資料庫可以：

- ✅ 保護開發資料不被測試清除
- ✅ 允許測試自由地建立、修改、刪除數據
- ✅ 提供乾淨一致的測試環境

## 快速開始

### 方法 1: 使用自動初始化腳本（推薦）

```bash
# 執行初始化腳本
cd /var/www/html/voting
./tests/_data/init_test_db.sh

# 依提示輸入資料庫 root 密碼與測試使用者密碼（也可先 export DB_PASS、TEST_DB_PASSWORD），腳本會自動：
# 1. 建立 voting_test 資料庫
# 2. 建立 voting_test 使用者
# 3. 複製資料庫結構
# 密碼不會出現在命令列參數，也不會輸出到畫面
```

### 方法 2: 手動設置

#### 步驟 1: 建立測試資料庫

```bash
mysql -u root -p < tests/_data/setup_test_database.sql
```

或手動執行 SQL：

```sql
CREATE DATABASE IF NOT EXISTS `voting_test`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'voting_test'@'localhost'
  IDENTIFIED BY '<測試密碼>';

GRANT ALL PRIVILEGES ON `voting_test`.* TO 'voting_test'@'localhost';
FLUSH PRIVILEGES;
```

#### 步驟 2: 複製資料庫結構

```bash
# 從開發資料庫匯出結構（不含數據）
mysqldump -u root -p --no-data --routines --triggers voting > /tmp/voting_structure.sql

# 匯入到測試資料庫
mysql -u voting_test -p voting_test < /tmp/voting_structure.sql
```

#### 步驟 3: 設定 `.env`

```bash
TEST_DB_HOST=localhost
TEST_DB_PORT=3306
TEST_DB_NAME=voting_test
TEST_DB_USERNAME=voting_test
TEST_DB_PASSWORD=<測試密碼>
```

## 執行測試

設置完成後，即可正常執行測試：

```bash
# 執行所有單元測試（PHP 8.5 請用 composer test，見 tests/README.md）
composer test unit

# 執行特定測試
composer test unit AnonPartyVoteTest

# 執行 acceptance 測試（fixture 務必加 --appconfig，否則會寫入正式資料庫；完整清單見 CLAUDE.md／tests/README.md）
echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac,Group,GroupMember,Ballots,BallotsSelected,ResultsConfig" --appconfig=config/console-test.php
composer test acceptance AnonVoteCest
# 重設資料請重新 fixture/load；不要用 fixture/unload（會連同相依 fixture 清空）
```

## 配置說明

### 測試資料庫配置

測試配置（[config/test.php](../config/test.php)、[config/console-test.php](../config/console-test.php)）從 `.env` 的 `TEST_DB_HOST`、`TEST_DB_PORT`、`TEST_DB_NAME`、`TEST_DB_USERNAME`、`TEST_DB_PASSWORD` 讀取連線資訊，程式內沒有預設密碼。

### Cleanup 設定

測試套件配置位於 [tests/unit.suite.yml](../tests/unit.suite.yml)：

```yaml
modules:
    enabled:
        - Yii2:
            cleanup: true  # 測試後自動清理資料庫
            part: [orm, email, fixtures]
```

**保持 `cleanup: true`**，因為這是測試最佳實踐。

## 安全建議

### 1. 測試密碼只放在 `.env`

`TEST_DB_PASSWORD` 只寫在 `.env`（不進版控），不要寫進程式或文件。

### 2. 修改預設密碼

```sql
ALTER USER 'voting_test'@'localhost' IDENTIFIED BY 'your_new_password';
```

記得同步更新 `config/test.php` 中的密碼。

### 3. 限制權限

如果不需要 DDL 操作，可以限制權限：

```sql
REVOKE ALL PRIVILEGES ON `voting_test`.* FROM 'voting_test'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON `voting_test`.* TO 'voting_test'@'localhost';
FLUSH PRIVILEGES;
```

## 維護

### 重置測試資料庫

```bash
# 重新執行初始化腳本
./tests/_data/init_test_db.sh
```

### 手動清空測試數據

```bash
mysql -u voting_test -p voting_test -e "
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE ballots;
TRUNCATE TABLE ballotsSelected;
SET FOREIGN_KEY_CHECKS=1;
"
```

### 查看測試資料庫狀態

```bash
mysql -u voting_test -p voting_test -e "SHOW TABLES;"
```

## 疑難排解

### 問題 1: 連接被拒絕

**錯誤**: `Access denied for user 'voting_test'@'localhost'`

**解決**:
```sql
-- 檢查使用者是否存在
SELECT User, Host FROM mysql.user WHERE User='voting_test';

-- 重新授予權限
GRANT ALL PRIVILEGES ON `voting_test`.* TO 'voting_test'@'localhost';
FLUSH PRIVILEGES;
```

### 問題 2: 資料庫不存在

**錯誤**: `Unknown database 'voting_test'`

**解決**:
```bash
./tests/_data/init_test_db.sh
```

### 問題 3: 測試失敗

**檢查項目**:
1. 確認測試資料庫結構是否最新
2. 結構變更見 `docs/migrations/`，需另外套用到 voting_test
3. 重新初始化測試資料庫

## 相關文件

- [docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md) - 系統架構
- [codeception.yml](../codeception.yml) - Codeception 配置
- [unit.suite.yml](unit.suite.yml) - 單元測試套件配置
