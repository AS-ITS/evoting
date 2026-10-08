# Fixture 管理命令

## 重要說明

執行 fixture 命令時，**必須使用 `--appconfig=config/console-test.php`** 參數，否則會影響到生產資料庫 `voting`。

## 基本命令

### 載入 Fixture

```bash
# 載入指定的 fixtures 到測試資料庫
php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig" --appconfig=config/console-test.php

# 載入所有 fixtures
php yii fixture/load "*" --appconfig=config/console-test.php

# 載入所有 fixtures 除了指定的
php yii fixture/load "*, -User, -UserProfile" --appconfig=config/console-test.php
```

### 卸載 Fixture

```bash
# 卸載指定的 fixtures
php yii fixture/unload "Ballots,BallotsSelected" --appconfig=config/console-test.php

# 卸載所有 fixtures
php yii fixture/unload "*" --appconfig=config/console-test.php
```

### 生成 Fixture 資料

```bash
# 從 template 生成指定數量的測試資料
php yii fixture/generate "Votes,Parties" --count=10 --appconfig=config/console-test.php
```

## 測試資料庫配置

測試資料庫配置位於 [config/console-test.php](../config/console-test.php#L58-L64)：

- **資料庫名稱**: `voting_test`
- **使用者**: `voting_test`
- **密碼**: `test_password_123`

## 常用 Fixture 組合

### Acceptance 測試用
```bash
php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig" --appconfig=config/console-test.php
```

### 清理選票資料
```bash
php yii fixture/unload "Ballots,BallotsSelected" --appconfig=config/console-test.php
```

## 注意事項

1. **永遠不要**在沒有 `--appconfig=config/console-test.php` 的情況下執行 fixture 命令
2. fixture 會執行 `DELETE FROM` 操作清空表格，請確保使用測試資料庫
3. 如果需要重新建立測試資料庫，請參考 [README_TEST_DATABASE.md](README_TEST_DATABASE.md)
4. `config/console-test.php` 是專門給 console 命令使用的測試配置
5. `config/test.php` 是給 Codeception 測試框架使用的配置（不要用於 fixture 命令）

## 快速檢查

執行前可以先確認當前配置使用的資料庫：

```bash
# 使用測試配置（正確）
php yii fixture/load "Votes" --appconfig=config/console-test.php --interactive=0 --count=0

# 不使用配置參數（危險！會使用生產資料庫）
# ❌ 不要這樣做！
php yii fixture/load "Votes"
```
