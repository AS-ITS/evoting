# 電子投票系統 (voting)

通用電子投票平台（原院士選舉參考實作）。

[English](#english) · [繁體中文](#繁體中文)

## English

Electronic voting platform (general-purpose; originally a reference implementation for academician elections).

**Requirements:** PHP 8.0+ (8.2+ recommended), MariaDB 10.6+, Composer, Apache/Nginx.

**Quick start:**

1. `git clone https://github.com/AS-ITS/evoting.git` (do **not** use `composer create-project`)
2. `composer install` (uses `composer.lock`; applies [`patches/`](patches/) automatically). `frontend/` is in the repo — no npm
3. Copy `.env.example` to `.env`; set database credentials and `APP_ENV`
4. Import [`docs/init_db.sql`](docs/init_db.sql), then run `php yii admin/create` to create the admin user
5. Fix directory permissions (`runtime/`, `web/assets/`, `chmod 755 yii`); point the web server Document Root to `web/`

Full install, Web Server samples, and production checklist: **[SETUP.md](SETUP.md)** (Traditional Chinese).  
Architecture notes: **[CLAUDE.md](CLAUDE.md)**. Security reporting: **[SECURITY.md](SECURITY.md)**.

Vote types: open ballot (no auth) and anonymous (one-time passwords). **Maintenance: Tier 2** (see [GOVERNANCE.md](GOVERNANCE.md)). Licensed under **MIT** ([LICENSE](LICENSE)). Report vulnerabilities via [SECURITY.md](SECURITY.md).

---

## 繁體中文

## 維護等級

**Tier 2：官方長期維護**（見 [GOVERNANCE.md](GOVERNANCE.md)）。歡迎外部貢獻；合併與安全修補由維護者負責。

## 快速開始

1. `git clone https://github.com/AS-ITS/evoting.git`（**不要**使用 `composer create-project`）
2. `composer install`（依 `composer.lock`；會自動套用 [`patches/`](patches/)）。`frontend/` 已在 repo，無需 npm
3. 複製 `.env.example` 為 `.env`，設定資料庫與 `APP_ENV`
4. 匯入 [`docs/init_db.sql`](docs/init_db.sql)，執行 `php yii admin/create` 建立管理員
5. 設定目錄權限（`runtime/`、`web/assets/`、`chmod 755 yii`）後以 Web Server 指向 `web/`

完整安裝、Web Server 設定與正式環境檢查表見 **[SETUP.md](SETUP.md)**。  
系統架構與開發指引見 **[CLAUDE.md](CLAUDE.md)**。

## 系統功能

### 管理員認證
- **登入**: `/auth/login` 或 `/admin`
- **登出**: `/auth/logout`
- **修改密碼**: `/auth/change-password`

### 密碼修改功能

管理員可透過「修改密碼」功能更新自己的登入密碼。

**密碼要求**:
- 長度至少 8 個字元
- 必須包含至少一個字母和一個數字
- 不可與帳號相同
- 不可與目前密碼相同

**安全特性**:
- 驗證舊密碼正確性
- 密碼強度檢查
- 防止密碼重用
- 操作日誌記錄（LOG 418）
- 修改成功後自動登出，要求重新登入

### 二次驗證（TOTP）與敏感操作

- **TOTP 綁定**：登入後台 → 使用者管理 → **雙因素驗證**（`/users/totp`）
- **敏感操作**（密碼 CSV 匯出、RBAC 同步）：需輸入管理員密碼；已啟用 TOTP 者另需 6 碼
- 詳見 [SECURITY.md](SECURITY.md)、[SETUP.md](SETUP.md) §二次驗證與 TOTP

### 開源釋出前

```bash
bash scripts/open-source-prep.sh
```

---

## 測試

### 單元測試（Unit Tests）

單元測試直接呼叫 Model 方法，不需要測試伺服器。Fixture 由各測試自動管理。

```bash
# 步驟 1：清除舊測試資料（確保乾淨起始狀態）
echo "yes" | php yii fixture/unload "*" --appconfig=config/console-test.php

# 步驟 2：執行所有單元測試
php vendor/bin/codecept run unit

# 執行特定測試檔案
php vendor/bin/codecept run unit models/FormPartiesTest

# 顯示詳細輸出
php vendor/bin/codecept run unit --debug
```

> **注意**：單元測試執行後會清除 Config、Users、Rbac 等資料表，若需接著執行驗收或功能測試，需重新載入所有 Fixture。

---

### 功能測試（Functional Tests）

功能測試透過 Codeception Yii2 模組模擬 HTTP 請求，直接測試 Controller 行為，**不需要啟動測試伺服器**。Fixture 由各 Cest 類別的 `_fixtures()` 方法自動管理，每個測試方法執行前會自動載入。

共 **190 個測試**，涵蓋 19 個 Controller/場景（詳見 [tests/TEST_PROGRESS.md](tests/TEST_PROGRESS.md)）：

| Cest 檔案 | 測試範圍 |
|---|---|
| `AuthControllerCest` | 登入、登出、修改密碼 |
| `BallotControllerCest` | 選票管理 |
| `BallotWorkControllerCest` | 投票工作管理 |
| `CandiControllerCest` | 候選人設定 |
| `CountControllerCest` | 開票作業 |
| `ElectControllerCest` | 投票場次管理 |
| `GroupControllerCest` | 群組管理 |
| `GroupMemberControllerCest` | 群組成員管理 |
| `ManageControllerCest` | 系統管理 |
| `PasswdControllerCest` | 匿名投票密碼管理 |
| `PermissionDenialCest` | 權限控制驗證（各角色存取） |
| `QuestionControllerCest` | 投票問題管理 |
| `ResultControllerCest` | 開票結果 |
| `RoundControllerCest` | 輪次管理 |
| `SiteControllerCest` | 網站首頁 |
| `UsersControllerCest` | 使用者管理 |
| `VoteControllerCest` | 投票操作 |
| `VoteListCest` | 投票列表 |
| `VoteResultCest` | 投票結果頁 |

**執行方式**:

```bash
# 執行所有功能測試
php vendor/bin/codecept run functional

# 執行特定 Cest 檔案
php vendor/bin/codecept run functional ElectControllerCest

# 執行特定測試方法
php vendor/bin/codecept run functional ElectControllerCest:testCreateVotePageDisplay

# 顯示詳細輸出
php vendor/bin/codecept run functional --debug
```

> **⚠️ 首次執行注意事項**：若測試資料庫是全新空的（或資料不完整），第一次執行可能因資料庫狀態不一致而有部分測試失敗。再次執行一遍即可全數通過（Fixture 已在第一次執行時載入至資料庫）。建議在首次執行前先預熱資料庫：
>
> ```bash
> echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac" --appconfig=config/console-test.php
> php vendor/bin/codecept run functional
> ```

---

### 驗收測試（Acceptance Tests）

驗收測試透過瀏覽器模擬完整操作流程，需先啟動測試伺服器並載入所有 Fixture。

```bash
# 步驟 1：啟動測試伺服器（在獨立終端機中執行，保持執行）
php -S localhost:8080 -t web web/router-test.php

# 步驟 2：載入所有 Fixture（必須包含 Config、Users、Rbac）
echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac" --appconfig=config/console-test.php

# 步驟 3：執行所有驗收測試
php vendor/bin/codecept run acceptance

# 執行特定測試
php vendor/bin/codecept run acceptance AnonVoteCest

# 步驟 4（選用）：清除投票資料以便重複測試
echo "yes" | php yii fixture/unload "Ballots,BallotsSelected" --appconfig=config/console-test.php
```

**⚠️ 重要**: 務必使用 `--appconfig=config/console-test.php` 參數，確保操作測試資料庫而非正式資料庫。

### 產生測試數據
```bash
# 產生預期的測試資料: 從 /tests/fixtures/data 修改
php yii fixture/load "*" --appconfig=config/console-test.php

# 透過 template 產生測試資料
php yii fixture/generate <fixtures> --count=<number> --appconfig=config/console-test.php
```

### 清除測試資料
```bash
# 清除所有測試資料
php yii fixture/unload "*" --appconfig=config/console-test.php

# 清除特定測試資料
php yii fixture/unload "Ballots,BallotsSelected" --appconfig=config/console-test.php
```

### 簡易壓力測試
```
php ./vendor/bin/codecept run acceptance AnonVoteCest.php
```
只執行`tryAnonVoteCandidates`，其他@skip；然後開n個cmd，更改anonProvider()中的密碼清單(limit、offset)，同時執行`php ./vendor/bin/codecept run acceptance AnonVoteCest.php`，觀察結果。

ex: 

cmd1: offset(0)，執行php ./vendor/bin/codecept run acceptance AnonVoteCest.php

cmd2: offset(100)，執行php ./vendor/bin/codecept run acceptance AnonVoteCest.php

以此類推....

---

## S-BOM (Software Bill of Materials)

本專案提供符合 CycloneDX 1.5 標準的 S-BOM 資料，用於軟體供應鏈安全與依賴管理。

### S-BOM 檔案
- **[sbom-vendor.json](docs/sbom-vendor.json)**: 後端 PHP 依賴 (Composer) 的完整清單。
- **[sbom-frontend.json](docs/sbom-frontend.json)**: 前端 JavaScript 函式庫 (Bootstrap 等) 的完整清單。

### 產生與更新 S-BOM
專案內含一個獨立的 PHP 腳本，**無需安裝任何額外套件**即可讀取專案狀態並產生最新的 S-BOM。

**執行方式**:
```bash
# 產生所有 S-BOM (後端與前端)
php docs/generate_sbom.php all

# 僅產生後端 S-BOM
php docs/generate_sbom.php vendor

# 僅產生前端 S-BOM
php docs/generate_sbom.php frontend
```

---

## 開發文檔

### 系統架構
- [CLAUDE.md](CLAUDE.md) - Claude Code 專案開發指南
- [SETUP.md](SETUP.md) - 環境設定指南

### 配置文檔
- [docs/CONFIGURATION.md](docs/CONFIGURATION.md) - 系統配置完整說明
- [docs/MASTER_KEY_SETUP.md](docs/MASTER_KEY_SETUP.md) - 主金鑰設定指南
- [docs/KEY_DERIVATION_RULES.md](docs/KEY_DERIVATION_RULES.md) - 金鑰衍生規則

### 測試文檔
- [tests/README.md](tests/README.md) - 測試文件索引
- [tests/TEST_GUIDE.md](tests/TEST_GUIDE.md) - 測試撰寫指南
- [tests/TEST_PROGRESS.md](tests/TEST_PROGRESS.md) - 測試進度追蹤

### 測試檔案位置
```
tests/
├── unit/
│   └── controllers/
│       └── AuthControllerTest.php        # 管理員認證單元測試（含密碼修改）
└── acceptance/
    └── ChangePasswordCest.php            # 密碼修改功能測試（9 個場景）
```

---

## 重要提醒

### Fixture 載入注意事項
- **正式環境**：`php yii fixture/load` → 使用 `config/console.php` → 操作正式資料庫 `voting`
- **測試環境**：`php yii fixture/load --appconfig=config/console-test.php` → 使用 `config/console-test.php` → 操作測試資料庫 `voting_test`

**務必使用測試配置**避免影響正式資料！

### 密碼修改實作重點
Users 模型使用雙欄位設計：
- `password` - 資料庫欄位（以 `password_hash()` 單向雜湊儲存，不可還原）
- `password_plain` - 虛擬屬性（表單輸入/驗證用）

**正確的密碼更新方式**：
```php
// ✅ 正確
$user->password_plain = '新密碼';
$user->save();  // beforeSave() 自動雜湊

// ❌ 錯誤
$user->password = $user->hashPassword('新密碼');
$user->save(false);  // beforeSave() 可能用舊 password_plain 覆蓋
```

**漸進式遷移**：`validatePassword()` 會先以 `password_verify()` 驗證；
若資料庫仍是舊制 AES 可逆密文，驗證成功後會自動轉為雜湊寫回，無須一次性重設全部帳號。

詳見 `models/Users.php` 的 `beforeSave()` 方法。

### 部署安全注意事項

1. **執行環境**：`web/index.php` 由環境變數 `YII_DEBUG` / `YII_ENV` 控制，未設定時預設為正式環境（`prod`、除錯關閉）。開發環境請在 `.env` 設定 `YII_DEBUG=true`、`YII_ENV=dev`。
2. **測試入口**：`web/index-test.php`、`web/router-test.php` 僅供本機驗收測試使用，**正式部署時必須刪除或排除**。
3. **初始管理員**：匯入 `docs/init_db.sql` 後執行 `php yii admin/create` 建立 `admin`（sa）帳號；密碼由安裝時互動設定，不寫入文件。
4. **上傳目錄**：檔案上傳目錄（`APP_PATH_UPLOADS`，預設 `/var/www/filepool`）必須位於網站根目錄之外；若因故置於網站根目錄下，請停用該目錄的 PHP 執行：

```apache
# Apache（上傳目錄的 .htaccess）
<FilesMatch "\.ph(p[3457]?|t|tml)$">
    Require all denied
</FilesMatch>
```

```nginx
# nginx
location ~* ^/uploads/.*\.php$ {
    deny all;
}
```

---

## 文件與支援

| 文件 | 用途 |
|------|------|
| [CHANGELOG.md](CHANGELOG.md) | 版本變更紀錄（Keep a Changelog） |
| [SETUP.md](SETUP.md) | 安裝、部署、測試環境、部署最佳實踐 |
| [SECURITY.md](SECURITY.md) | 資安政策、TOTP、開源釋出前檢查 |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Issue／PR 與測試流程 |
| [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) | 行為準則 |
| [GOVERNANCE.md](GOVERNANCE.md) | 維護等級與決策 |
| [CLAUDE.md](CLAUDE.md) | 開發者架構說明 |
| [docs/threat-model/README.md](docs/threat-model/README.md) | 威脅建模（STRIDE／匿名性） |
| [docs/CONFIGURATION.md](docs/CONFIGURATION.md) | 環境變數與配置詳解 |
| [LICENSE](LICENSE) / [NOTICE](NOTICE) | MIT 授權與第三方聲明 |
| [docs/授權合規評估檢核表.md](docs/授權合規評估檢核表.md) | 授權合規檢核表 |
| [OPENSOURCE_MIGRATION.md](OPENSOURCE_MIGRATION.md) | 2026-01 院內→通用化遷移紀錄 |

**回報問題**：一般問題開 GitHub Issue（見模板）；提交前請先查 [SETUP.md](SETUP.md) 常見問題與 `runtime/logs/`，並附上 PHP、MariaDB、`APP_ENV`。**安全漏洞請勿在公開 Issue 揭露**，見 [SECURITY.md](SECURITY.md)。

**程式碼貢獻**：見 [CONTRIBUTING.md](CONTRIBUTING.md)。

## 授權

本專案原始碼以 **[MIT License](LICENSE)** 釋出。第三方套件見 [NOTICE](NOTICE) 與 [`docs/授權合規評估檢核表.md`](docs/授權合規評估檢核表.md)。

