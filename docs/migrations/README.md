# 資料庫增量 Migration

> **全新安裝**：直接使用 [`../init_db.sql`](../init_db.sql)（已含下列變更）。  
> **從舊版升級**：依序執行本目錄 SQL，或使用 [`../../scripts/ubuntu-upgrade-test.sh`](../../scripts/ubuntu-upgrade-test.sh)。

## 執行順序

| 順序 | 檔案 | 說明 |
|------|------|------|
| 1 | `20260709_passwords_crypto_v1.sql` | `passwords` 新增 `passwd_lookup`、`crypto_version` |
| 2 | `20260713_votes_shorturl_8.sql` | `votes.shortUrl` 由 char(6) 擴充為 char(8) |
| 3 | `20260713_users_totp.sql` | `users` 新增 TOTP 欄位（選用 MFA） |
| 4 | `20260806_config_branding.sql` | `config` logo／favicon／copyright |
| 5 | `20260924_drop_unused.sql` | 移除 `siteTitle*`、`voters`、`votePerms`、`urlRedirection`、`migration` |

## Schema 變更後的程式步驟

SQL 只改結構；若已有匿名投票密碼，還須在維護窗口執行：

```bash
# 測試庫先演練
php yii migrate-passwords/verify --appconfig=config/console-test.php
ALLOW_PRODUCTION_MIGRATE=1 php yii migrate-passwords/upgrade-crypto --appconfig=config/console-test.php

# 正式庫（需主金鑰、備份 passwords 表）
ALLOW_PRODUCTION_MIGRATE=1 php yii migrate-passwords/upgrade-crypto
php yii migrate-passwords/verify
```

詳見 [`../PHASE_2_1_CRYPTO_UPGRADE_RUNBOOK.md`](../PHASE_2_1_CRYPTO_UPGRADE_RUNBOOK.md)。

## 回滾

各 SQL 檔末尾有註解說明；crypto v1 若已 backfill 資料，只能從 DB 備份還原。
