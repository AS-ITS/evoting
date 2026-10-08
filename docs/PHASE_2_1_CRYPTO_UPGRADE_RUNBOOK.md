# Phase 2.1 投票密碼 crypto v1 升級 Runbook

> **適用**：正式環境 `voting` 資料庫  
> **前置**：程式已支援 v0/v1；`migrate-passwords/verify` 在測試庫通過  
> **相關**：[SETUP.md](../SETUP.md) §正式環境部署檢查表、[SECURITY.md](../SECURITY.md)

---

## 1. 目標

將 `passwords` 表由 **v0（固定 IV AES）** 升級為 **v1（per-record IV + HMAC lookup）**，降低相同明文產生相同密文之風險。

---

## 2. 前置檢查

- [ ] 無進行中投票場次（`votes.active = 1`）
- [ ] 已通知選務窗口維護時段
- [ ] 已備份 `passwords` 表（及完整 DB）
- [ ] `voting_test` 已完整演練且 `verify` 全綠
- [ ] 維運可存取 `VOTING_MASTER_KEY` 或 `/etc/voting/master.key`

```bash
# 測試庫演練（必做）
php yii migrate-passwords/verify --appconfig=config/console-test.php
ALLOW_PRODUCTION_MIGRATE=1 php yii migrate-passwords/upgrade-crypto --appconfig=config/console-test.php
php yii migrate-passwords/verify --appconfig=config/console-test.php
```

---

## 3. Schema migration

若正式庫尚未有 v1 欄位：

```bash
mysql -u ... -p voting < docs/migrations/20260709_passwords_crypto_v1.sql
```

（檔名依 repo 實際 migration 為準；若已併入 `init_db.sql` 新裝則跳過。）

---

## 4. 正式環境執行步驟

### 4.1 維護窗口

1. 公告維護時段（建議低峰 + 無投票進行）
2. 可選：nginx 維護頁或暫停匿名登入入口

### 4.2 備份

```bash
mysqldump -u ... -p voting passwords > passwords_backup_$(date +%Y%m%d_%H%M).sql
```

### 4.3 執行升級

```bash
cd /var/www/html/voting
export APP_ENV=production
export ALLOW_PRODUCTION_MIGRATE=1

php yii migrate-passwords/upgrade-crypto
```

> `ProductionConsoleGuard` 會寫入 audit log（420/421）。勿在無旗標下強制執行。

### 4.4 驗證

```bash
php yii migrate-passwords/verify
```

- 輸出應顯示 0 筆 v0 待升級
- 抽樣 3–5 組密碼執行匿名登入 + 投票流程（選務驗收）

### 4.5 復原（若失敗）

```bash
mysql -u ... -p voting < passwords_backup_YYYYMMDD_HHMM.sql
```

重啟應用並確認匿名登入正常。

---

## 5. 驗收簽核

| 項目 | 負責 | 簽核 |
|------|------|------|
| DB 備份完成 | 維運 | |
| upgrade-crypto 成功 | 維運 | |
| verify 全綠 | 開發 | |
| 抽樣登入投票 | 選務 | |
| 變更單結案 | 維運 | |

---

## 6. 事後

- 監控 `LOGIN_PASSWORD_FAIL` 異常（24h）
- 更新改善計畫 §14 狀態為「正式庫已完成」
- 排程下個版本移除 v0 解密路徑（可選）

---

*版本：1.0 | 2026-07-13*
