# 資訊安全政策

本專案為通用電子投票系統（開源版，原院士選舉參考實作），原始碼以 [MIT License](LICENSE) 釋出。若您發現安全漏洞，請依下列流程回報。

## 支援版本

僅對**目前 main 分支最新版本**提供安全修補。請在回報前確認您使用的是最新程式碼。

## 回報方式

請**勿**在公開 Issue 中揭露漏洞細節（避免被利用）。

請透過 **GitHub Private vulnerability reporting**／Security Advisory 私下回報（公開 repo 請啟用該功能）。不另公布專用信箱。

回報內容請包含：

- 漏洞類型與影響（機密性 / 完整性 / 可用性）
- 重現步驟（含環境版本、PHP、MariaDB）
- 概念驗證（PoC）或截圖（若適用）
- 建議修復方向（可選）

## 回應時程（目標）

| 階段 | 目標時間 |
|------|----------|
| 初步確認 | 3 個工作天內 |
| 嚴重（Critical/High）修補評估 | 7 個工作天內 |
| 修補釋出 | 依嚴重度與選舉時程協調 |

## 範圍

**在範圍內：**

- 本 repository 內應用程式碼（PHP / 設定 / 部署腳本）
- 匿名投票身份隔離、RBAC 越權、CSRF、XSS、檔案存取
- 金鑰與密碼儲存相關問題

**不在範圍內：**

- 第三方套件已知 CVE（請同時通報上游；我們會追蹤 `composer audit`）
- 需實體存取或已完全控制主機的攻擊
- 社交工程、釣魚

## 威脅建模

應用層威脅模型見 [docs/threat-model/README.md](docs/threat-model/README.md)。

匿名投票的系統保證是：**管理者不知道亂數密碼由哪一位選民持有**（系統不存人↔密碼）；`ballots.creator` 連結的是密碼憑證與選票，供一密一票與封存，不是選民身分。

## 部署安全提醒

部署前請務必閱讀 [SETUP.md](SETUP.md)（§部署最佳實踐、§正式環境部署檢查表）與 [docs/MASTER_KEY_SETUP.md](docs/MASTER_KEY_SETUP.md)。

正式上線前請確認：

- 開發／測試／正式環境**資料庫與 `.env` 分離**
- 正式環境**關閉除錯**（`YII_DEBUG=false`）、**強制 HTTPS**
- 定期**備份資料庫與上傳目錄**；備份還原測試勿直接在 production 執行
- 管理員使用**強密碼**；高權限帳號建議啟用 **TOTP**

**重要預設值（開源版）：**

- `SA_DB_TOOLS_ENABLED` 預設為 **false**（SA 資料庫工具關閉）
- `SENSITIVE_REAUTH` 預設為 **true**（高敏感操作需管理員密碼確認）
- `CSP_MODE` 預設為 **report-only**（Content-Security-Policy 觀察模式）
- `YII_DEBUG` 預設為 **false**
- 勿將 `.env` 或 `master.key` 提交至版本控制

## 二次驗證（Sensitive Reauth / TOTP）

系統對**高敏感管理操作**要求二次驗證，由 `SENSITIVE_REAUTH` 控制（預設 `true`；設為 `false` 僅建議用於自動化測試環境）。

### 涵蓋操作

| 操作 | 路徑 | 驗證方式 |
|------|------|----------|
| 投票密碼 CSV 匯出 | 密碼管理 → 匯出 | 管理員密碼；若已啟用 TOTP 則另需 6 碼 |
| 投票密碼函（docx） | 密碼管理 → 生成密碼函 | 同上 |
| 密碼列表明文顯示 | 密碼管理 / 選票建立者 → 顯示明文 | 同上；解鎖後預設 15 分鐘有效（`SENSITIVE_PLAINTEXT_TTL`） |
| 選票統計匯出（CSV）／投票結果封存單 | 選票管理 / 檢票 → 選票統計匯出 | 同上；匿名投票時含明文密碼（紙本封存） |
| 同步 RBAC 權限 | 使用者管理 → 同步 RBAC 權限 | 同上（僅 sa） |

### TOTP 綁定（Google Authenticator 等）

1. 執行 DB migration（若尚未）：`docs/migrations/20260713_users_totp.sql`
2. 登入管理後台 → **使用者管理** → **雙因素驗證**（`/users/totp`）
3. 輸入密碼 → 掃描 QR Code → 輸入 6 碼完成啟用

啟用 TOTP 後，上述敏感操作除密碼外還需驗證器代碼。相關程式：`components/SensitiveReauth.php`、`components/TotpService.php`。


## 開源 / 移轉釋出前檢查

對外 push、tarball 或移轉維運前，請執行：

```bash
bash scripts/open-source-prep.sh
```

腳本會：

- 清空 `runtime/cache`、`debug`、`logs`、`sessions`
- 確認 `web/phpinfo.php` 等危險檔不存在
- 確認 `composer.lock` 存在
- 若已安裝 [gitleaks](https://github.com/gitleaks/gitleaks)，執行 secret scan

可選：

```bash
RUN_DEPLOY_SMOKE=1 bash scripts/open-source-prep.sh   # 需測試伺服器
```

詳細檢查表見 [SETUP.md](SETUP.md) §正式環境部署檢查表。

## 致謝

我們感謝負責任地揭露漏洞的研究人員與使用者。若您同意，可在修補釋出後於此處致謝（待維護者更新）。

---

*本文件隨開源釋出更新；部署 SOP 見 [SETUP.md](SETUP.md)。貢獻流程見 [CONTRIBUTING.md](CONTRIBUTING.md)。*
