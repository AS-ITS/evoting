# 環境設定指南

本文件說明如何設定電子投票系統（voting）的環境配置。

## 📋 目錄

- [系統需求](#系統需求)
- [快速開始](#快速開始)
- [環境配置](#環境配置)（含 [.env 必填](#env-必填項目)）
- [資料庫設定](#資料庫設定)
- [目錄結構設定](#目錄結構設定)
- [測試環境設定](#測試環境設定)
- [正式環境部署檢查表](#正式環境部署檢查表)
- [Web Server 設定](#web-server-設定)
- [常見問題](#常見問題)

---

## 系統需求

- PHP 8.0 或以上（建議 PHP 8.2+）
- MariaDB 10.6 或以上（建議 MariaDB 10.11）
- Composer
- Web Server (Apache/Nginx)
- 建議使用 Linux 環境（已驗證：Rocky Linux 10）

### PHP 擴展需求

- php-mysql
- php-mbstring
- php-json
- php-openssl
- php-curl
- php-gd

---

## 快速開始

### 1. 複製專案

```bash
git clone https://github.com/AS-ITS/evoting.git /var/www/html/voting
cd /var/www/html/voting
```

**不要**使用 `composer create-project`（本專案是可更新的應用，不是 Yii 模板）。`vendor/` 不上版控；`frontend/` 已在 repo，無需 npm。

### 2. 安裝依賴

```bash
composer install
chmod 755 yii
```

正式環境改用 `composer install --no-dev --optimize-autoloader`。版本由 `composer.lock` 鎖定；結束後會自動套用 [`patches/`](patches/)（見 [VENDOR_PATCHES.md](VENDOR_PATCHES.md)）。目錄權限見 §5，不要依賴 Composer hook。

### 3. 配置環境（`.env`）

```bash
cp .env.example .env
chmod 640 .env
```

至少填寫下列項目（產生指令、加密密碼與正式／開發差異見 [.env 必填項目](#env-必填項目)）：

| 變數 | 用途 |
|------|------|
| `APP_ENV` | `development` / `testing` / `production` |
| `APP_BASE_PATH` | 應用程式根目錄，預設 `/var/www/html/voting` |
| `COOKIE_VALIDATION_KEY` | Cookie／CSRF；每環境一組，不可共用 |
| `MASTER_KEY` 或 `VOTING_MASTER_KEY`／金鑰檔 | 加密派生。金鑰檔：`VOTING_KEY_FILE`（若有）→ 否則 Linux `/etc/voting/master.key`、Windows `C:\ProgramData\voting\master.key`。正式環境不要只靠 `.env` 的 `MASTER_KEY` |
| `DB_HOST` `DB_NAME` `DB_USERNAME` `DB_PASSWORD` | 資料庫；正式環境密碼必填，建議 `php yii encrypt/db-password` |
| `ALLOWED_HOSTS` | 正式環境**必填**實際 FQDN（不可 `*`、`localhost`） |

金鑰產生與 `DB_PASSWORD` 加密指令見該節「產生金鑰與加密密碼」。完整鍵名見 [`.env.example`](.env.example)；主金鑰細節見 [`docs/MASTER_KEY_SETUP.md`](docs/MASTER_KEY_SETUP.md)。填完後可跑 `php yii config/validate`。

### 4. 設定資料庫

```bash
# 建立正式資料庫與使用者
mysql -u root -p -e "CREATE DATABASE voting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'voting'@'localhost' IDENTIFIED BY 'your_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON voting.* TO 'voting'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# 匯入初始資料庫結構與預設資料
mysql -u voting -p voting < docs/init_db.sql
```

**建立初始管理員**（匯入 `init_db.sql` 後）：

```bash
php yii admin/create
```

依提示設定 `admin` 密碼（至少 8 字元，須含英文字母與數字）。密碼不會寫入文件或 SQL。

> [!IMPORTANT]
> 請妥善保存 admin 密碼；正式環境建議完成 TOTP 綁定並定期更換密碼。

### 從舊版升級（Ubuntu 測試機／既有 DB）

若主機上已有舊版程式與 `voting` 資料庫，**請勿**再次匯入 `init_db.sql`（會清空資料）。改為：

```bash
# 1. 部署新版程式（git pull 或 rsync 至 APP_ROOT）
cd /var/www/html/voting

# 2. 編輯腳本變數後執行（會備份 DB、跑增量 migration、健檢）
chmod +x scripts/ubuntu-upgrade-test.sh
export APP_ROOT=/var/www/html/voting
export DB_USER=voting          # 依實際 DB 帳號
export APP_ENV=testing         # 測試機
export PHP_USER=www-data         # Ubuntu php-fpm 常見
./scripts/ubuntu-upgrade-test.sh
```

增量 SQL 說明見 [`docs/migrations/README.md`](docs/migrations/README.md)。  
密碼 crypto v1 backfill 詳見 [`docs/PHASE_2_1_CRYPTO_UPGRADE_RUNBOOK.md`](docs/PHASE_2_1_CRYPTO_UPGRADE_RUNBOOK.md)。

乾跑（不寫入 DB）：`DRY_RUN=1 ./scripts/ubuntu-upgrade-test.sh`

### 5. 設定目錄權限

> **重要**：必須確認 PHP-FPM worker 的執行身份。本系統在 Rocky Linux 10 上的實測環境中，PHP-FPM pool worker 以 **`apache`** 使用者執行（即使 Web Server 是 nginx）。請先確認後再設定：
> ```bash
> ps aux | grep php-fpm | grep -v master | grep -v grep | head -1
> ```

```bash
# 建立必要目錄
mkdir -p /var/www/uploads/voting
mkdir -p /var/www/db/voting

# 設定整體權限（將 PHP_USER 替換為實際 PHP-FPM 執行身份，例如 apache）
PHP_USER=apache

chmod -R 755 /var/www/html/voting
chmod 755 /var/www/html/voting/yii
chmod -R 775 /var/www/html/voting/web/assets
chown -R ${PHP_USER}:${PHP_USER} /var/www/html/voting/web/assets
chown -R ${PHP_USER}:${PHP_USER} /var/www/uploads/voting

# 設定 runtime 目錄（整體可寫）
chmod -R 775 /var/www/html/voting/runtime
chown -R ${PHP_USER}:${PHP_USER} /var/www/html/voting/runtime
```

#### runtime 子目錄說明

`runtime/` 下的部分子目錄由系統在執行期間自動建立，若權限不正確會導致寫入失敗。**特別注意以下兩個目錄**：

| 目錄 | 用途 | 常見問題 |
|---|---|---|
| `runtime/storage/` | 儲存匯出的 Word/CSV 暫存檔（密碼函、範例檔等） | 若 owner 非 PHP-FPM 使用者且權限為 755，會出現 `copy(): Permission denied` |
| `runtime/URI/` | 短網址對應記錄 | 同上 |

若這些目錄是由 root 建立（例如首次部署時以 root 執行），需手動修正：

```bash
# 確認 PHP-FPM 實際執行身份
PHP_USER=$(ps aux | grep 'php-fpm: pool' | grep -v grep | awk '{print $1}' | head -1)
echo "PHP-FPM user: ${PHP_USER}"

# 修正 runtime 下所有子目錄的擁有者
chown -R ${PHP_USER}:${PHP_USER} /var/www/html/voting/runtime
chmod -R 775 /var/www/html/voting/runtime

# 或僅修正特定子目錄
chown ${PHP_USER}:${PHP_USER} /var/www/html/voting/runtime/storage
chmod 775 /var/www/html/voting/runtime/storage
chown ${PHP_USER}:${PHP_USER} /var/www/html/voting/runtime/URI
chmod 775 /var/www/html/voting/runtime/URI
```

> [!TIP]
> 可用以下指令快速確認 PHP-FPM 執行身份及 runtime 下哪些目錄權限異常：
> ```bash
> # 確認 PHP-FPM worker 使用者
> ps aux | grep 'php-fpm: pool' | grep -v grep | awk '{print $1}' | sort -u
>
> # 列出 runtime 下所有目錄及擁有者
> ls -la /var/www/html/voting/runtime/
> ```

### 6. 訪問應用程式

在瀏覽器中訪問：`http://your-domain/voting/web/`

### 首次部署檢查清單

- [ ] `git clone` 後 `composer install`（正式用 `--no-dev`；會套用 `patches/`）
- [ ] 複製 `.env.example` 為 `.env`（`chmod 640`），填寫 §[.env 必填項目](#env-必填項目)（含 `COOKIE_VALIDATION_KEY`、主金鑰、`DB_*`、`APP_BASE_PATH`；正式環境加 `ALLOWED_HOSTS`）
- [ ] 匯入 `docs/init_db.sql`（新裝）或增量 migration（升級）
- [ ] 執行 `php yii admin/create` 建立管理員
- [ ] 建立 uploads／runtime 等目錄並設定權限（見 §5）
- [ ] 配置 Web Server，Document Root 指向 `web/`
- [ ] 執行 branding 等增量 migration（見 [`docs/migrations/README.md`](docs/migrations/README.md)）
- [ ] 執行 `php yii config/validate` 與 `tests/deploy-smoke.sh`（staging）
- [ ] 登入後台、匿名投票各抽測一場次


---


## 環境配置

### 環境類型

系統支援三種環境類型：

| 環境 | 說明 | 適用場景 |
|------|------|----------|
| `production` | 正式環境 | 生產環境，關閉除錯模式 |
| `testing` | 測試環境 | 測試環境，用於 UAT 測試 |
| `development` | 開發環境 | 開發環境，啟用除錯模式 |

### 環境變數設定

複製 [`.env.example`](.env.example) 為 `.env` 後編輯。`.env` **不可**納入版控。

#### `.env` 必填項目

**所有環境（系統無法空白啟動）**

| 變數 | 說明 | 產生／填法 |
|------|------|------------|
| `APP_ENV` | `development`、`testing`、`production`（亦接受 `dev`／`test`／`prod`） | 依主機角色 |
| `COOKIE_VALIDATION_KEY` | Cookie 簽章與 CSRF | `php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"` |
| `DB_HOST`、`DB_NAME`、`DB_USERNAME` | 資料庫連線 | 與 MariaDB 帳號一致 |
| `DB_PASSWORD` | 資料庫密碼。開發可明文；**正式必填**，建議加密 | `php yii encrypt/db-password`（須先有主金鑰） |
| 主金鑰 | 派生加密子金鑰；無金鑰系統不能跑 | 見下表 |

**主金鑰（擇一，優先順序由高到低）**

| 來源 | 適用 | 作法 |
|------|------|------|
| `VOTING_MASTER_KEY` | 正式（建議） | 系統環境變數，不要寫進可被備份的 `.env` |
| `VOTING_KEY_FILE` | 正式（自訂檔案） | 金鑰檔**絕對路徑**。若設則優先於下一列 OS 預設 |
| OS 預設金鑰檔 | 正式（傳統主機） | Linux：`/etc/voting/master.key`；Windows：`C:\ProgramData\voting\master.key`。`chmod 0600` |
| `.env` 的 `MASTER_KEY` | **僅開發** | 見下方產生指令。正式環境禁止 |

**路徑與識別（新裝請對齊實際目錄）**

| 變數 | 預設／範例 | 說明 |
|------|------------|------|
| `APP_BASE_PATH` | `/var/www/html/voting` | 應用程式根目錄 |
| `APP_ID` | `voting` | 應用識別（可維持 `voting`） |
| `APP_NAME` | `投票系統` | 顯示名稱；畫面標題亦可在後台品牌設定覆寫 |
| `APP_PATH_PROGRAM` | `/var/www/html` | 程式目錄根 |
| `APP_PATH_UPLOADS` | `/var/www/filepool` | 上傳，須在 docroot **外** |
| `APP_PATH_DB` | `/var/www/db` | 備份目錄 |

**正式環境另必填**

| 變數 | 說明 |
|------|------|
| `ALLOWED_HOSTS` | 逗號分隔實際 FQDN，例如 `voting.example.com`。不可含 `*`、`localhost`。未設則 `config/validate` 失敗 |
| `YII_DEBUG` | `false` 或未設 |
| `YII_ENV` | `prod` |

開發環境可另設 `YII_DEBUG=true`、`YII_ENV=dev`。跑測試時填 `TEST_DB_*`（見 §測試資料庫）。

選用：`SESSION_TIMEOUT`、`SMTP_*`、`CSP_MODE`、`TRUSTED_PROXY_CIDRS`、`SENSITIVE_REAUTH` 等，見 `.env.example` 與本文件 §安全性設定、§正式環境部署檢查表。

#### 產生金鑰與加密密碼

Cookie 與主金鑰每次執行都會不同；請用自己產出的值，勿抄文件或他人環境。

```bash
# Cookie 驗證金鑰 → COOKIE_VALIDATION_KEY（64 字元 hex）
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

# 主金鑰 → VOTING_MASTER_KEY / 金鑰檔 / 開發用 MASTER_KEY（Base64）
php -r "echo base64_encode(random_bytes(64)) . PHP_EOL;"
```

正式環境寫入金鑰檔（將 `<KEY>` 換成上一步產出）。金鑰檔載入順序：

1. 若設了 `VOTING_KEY_FILE` → 用該**絕對路徑**
2. 否則 Linux：`/etc/voting/master.key`；Windows：`C:\ProgramData\voting\master.key`

Linux 預設路徑範例：

```bash
sudo mkdir -p /etc/voting
echo "<KEY>" | sudo tee /etc/voting/master.key
sudo chmod 600 /etc/voting/master.key
# chown 依 PHP-FPM 身份（Rocky 常見 apache；Ubuntu 常見 www-data）
sudo chown apache:apache /etc/voting/master.key
```

自訂路徑（仍低於 `VOTING_MASTER_KEY`）：

```bash
export VOTING_KEY_FILE=/var/lib/voting/master.key
```

金鑰檔**不是**專案目錄。**不要**放 repo 根：會進備份／版控，也與 `.env` 同目錄，失去金鑰分離。

加密 `DB_PASSWORD`（**須先有主金鑰**，否則會失敗）。建議互動輸入，避免密碼進 shell history：

```bash
php yii encrypt/db-password
# 或 php yii encrypt/db-password "your_plain_password"
```

命令會印出 `DB_PASSWORD=ENC:...`，可手動貼進 `.env`，或依提示自動寫入。SMTP 同理：`php yii encrypt/smtp-password`。

填寫範例（開發；金鑰請自行產生，勿抄範例值）：

```bash
APP_ENV=development
APP_BASE_PATH=/var/www/html/voting
APP_ID=voting
APP_NAME=投票系統

COOKIE_VALIDATION_KEY=<hex-64-chars>
MASTER_KEY=<base64-64-bytes>

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=voting
DB_USERNAME=voting
DB_PASSWORD=<password-or-encrypted>

# 開發可留空；正式必填真實 FQDN
# ALLOWED_HOSTS=voting.example.com
```

驗證：

```bash
php yii config/validate
```

### 支援的環境變數別名

系統支援多種環境值寫法，會自動標準化：

- **正式環境**: `production`, `prod`, `product`
- **測試環境**: `testing`, `test`
- **開發環境**: `development`, `dev`

---

## 資料庫設定

### 正式資料庫

在 `.env` 填寫（密碼加密見 [.env 必填項目](#env-必填項目)）：

```bash
DB_HOST=localhost
DB_PORT=3306
DB_NAME=voting
DB_USERNAME=voting
DB_PASSWORD=your_secure_password
DB_CHARSET=utf8mb4
```

### 測試資料庫

測試環境使用獨立的資料庫，需額外建立：

```bash
# 建立測試資料庫與使用者
mysql -u root -p -e "CREATE DATABASE voting_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'voting_test'@'localhost' IDENTIFIED BY 'test_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON voting_test.* TO 'voting_test'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# 匯入資料庫結構（不含初始資料，fixture 會載入測試資料）
mysql -u voting_test -p voting_test < docs/init_db.sql
```

在 `.env` 中設定測試資料庫連線：

```bash
TEST_DB_HOST=localhost
TEST_DB_NAME=voting_test
TEST_DB_USERNAME=voting_test
TEST_DB_PASSWORD=test_password
```

---

## 目錄結構設定

### 預設目錄結構

```
/var/www/
├── html/
│   └── voting/          # 應用程式根目錄
│       ├── config/      # 配置檔案
│       ├── controllers/ # 控制器
│       ├── models/      # 模型
│       ├── views/       # 視圖
│       ├── frontend/    # 前端套件（見 frontend/README.md）
│       ├── runtime/     # 執行時快取（需可寫）
│       └── web/         # Web 根目錄
├── uploads/
│   └── voting/          # 檔案上傳目錄（需可寫）
└── db/
    └── voting/          # 資料庫備份目錄
```

### 自訂目錄路徑

您可以透過環境變數自訂各目錄路徑：

```bash
# .env 檔案
APP_PATH_PROGRAM=/custom/path/html
APP_PATH_CONFIG=/custom/path/config
APP_PATH_UPLOADS=/custom/path/uploads
APP_PATH_DB=/custom/path/db
```


---

## 組織分組 preset（`ct.division*Ary`）

投票場次的分組名稱在 **場次編輯**（`elect/edit-vote`）中設定，寫入 `parties` 表；分組數量上限由 **網站設定**（`manage/setting`）的 `partyLimit` 控制。

另有一組部署端可自訂的 **組織分組 preset**，定義於 [`config/params.php`](config/params.php)：

| 鍵 | 用途 |
|----|------|
| `ct.division0Ary` / `0EAry` | 不分組時的「預設」標籤 |
| `ct.division1Ary` / `1EAry` | 四組制 preset（含英文對照）；亦供機構對照（`select-creator`） |
| `ct.division3Ary` / `3EAry` | 另一組四組制 preset |
| `ct.division4Ary` / `4EAry` | 三組制 preset |

部署單位可依組織結構（科、課、院區等）修改中英文名稱。**建議**：preset 的 key 與場次 `parties.party` 代碼對齊，以便選票編輯頁正確顯示分組標籤。

開源預設範例保留原示範名稱，不影響場次自行輸入的分組名稱。

---

## 測試環境設定

本節說明完整的測試流程。測試分為兩類：**單元測試**（Unit Tests）與**驗收測試**（Acceptance Tests）。

> [!WARNING]
> 務必使用 `--appconfig=config/console-test.php`，否則指令將操作**正式資料庫**。

---

### 測試用 Fixture 說明

執行測試前需先載入 Fixture（測試資料），以下為各 Fixture 對應的資料表：

| Fixture 名稱 | 資料表 | 說明 |
|---|---|---|
| `Config` | config | 系統設定 |
| `Users` | users | 系統使用者 |
| `Rbac` | auth_rule, auth_item, auth_item_child, auth_assignment | RBAC 權限資料 |
| `Votes` | votes | 投票場次 |
| `Parties` | parties | 投票分組 |
| `Round` | round | 投票輪次 |
| `Questions` | questions | 投票問題 |
| `CandiData` | candiData | 候選人資料 |
| `CandiConfig` | candiConfig | 候選人設定 |
| `Passwords` | passwords | 匿名投票密碼 |

---

### 單元測試

```bash
# 步驟 1：清除舊測試資料
echo "yes" | php yii fixture/unload "*" --appconfig=config/console-test.php

# 步驟 2：執行單元測試
php vendor/bin/codecept run unit

# 執行特定測試檔案
php vendor/bin/codecept run unit models/FormPartiesTest

# 顯示詳細輸出
php vendor/bin/codecept run unit --debug
```

> [!NOTE]
> 單元測試會透過 `haveFixtures()` 自動管理各測試所需的 Fixture，測試後會自動清除（包含 Config、Users、Rbac 等資料表）。

---

### 驗收測試

驗收測試需要執行中的 PHP 測試伺服器，並事先載入所有 Fixture（包含 RBAC 權限資料）。

```bash
# 步驟 1：啟動測試伺服器（在獨立終端機視窗執行，保持執行中）
php -S localhost:8080 -t web web/router-test.php
```

```bash
# 步驟 2：載入所有測試 Fixture（在另一個終端機執行）
# 必須包含 Config、Users、Rbac，否則登入會因權限問題失敗
echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac" --appconfig=config/console-test.php

# 步驟 3：執行驗收測試
php vendor/bin/codecept run acceptance

# 執行特定測試類別
php vendor/bin/codecept run acceptance AnonVoteCest

# 顯示詳細輸出
php vendor/bin/codecept run acceptance --debug

# 步驟 4：測試完成後清理投票資料（選用）
echo "yes" | php yii fixture/unload "Ballots,BallotsSelected" --appconfig=config/console-test.php
```

> [!IMPORTANT]
> - Fixture 載入時**務必包含** `Config,Users,Rbac`，否則驗收測試登入會回傳 403 Forbidden。
> - 執行完單元測試後若需執行驗收測試，需重新載入所有 Fixture，因為單元測試會清除 Config、Users、Rbac 資料表。

---

### 完整測試流程（單元 + 驗收）

```bash
# 終端機 A：啟動測試伺服器（整個測試期間保持執行）
php -S localhost:8080 -t web web/router-test.php

# 終端機 B：執行完整測試流程
# 1. 清除舊資料
echo "yes" | php yii fixture/unload "*" --appconfig=config/console-test.php

# 2. 執行單元測試（單元測試會自動管理 Fixture）
php vendor/bin/codecept run unit

# 3. 重新載入 Fixture（單元測試後需重新載入，供驗收測試使用）
echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac" --appconfig=config/console-test.php

# 4. 執行驗收測試
php vendor/bin/codecept run acceptance
```

---

## 安全性設定

### 除錯模式

```bash
# 開發環境
YII_DEBUG=true
YII_ENV=dev

# 正式環境（務必關閉除錯）
YII_DEBUG=false
YII_ENV=prod
```

**⚠️ 重要**：正式環境務必將 `YII_DEBUG` 設為 `false`，避免洩漏敏感資訊。

### Session 超時設定

```bash
# .env 設定（秒數，預設 7200）
SESSION_TIMEOUT=7200
```

### 匿名登入限速 bypass（選用，非正式環境）

正式環境（`APP_ENV=production`）對匿名密碼錯誤一律套用限速／鎖定，**不**因內部 IP 略過。  
非正式環境未設定時僅 `127.0.x.x` 可略過；若需額外 CIDR 前綴，設定：

```bash
# .env（逗號分隔；比對 client IP 前兩段，例 203.0）
# ANON_LOGIN_RATE_LIMIT_BYPASS_CIDRS=203.0,10.0
```

> 舊文件曾出現的 `ALLOWED_IP_RANGES` 並非現行匿名限速機制；全站 Host 限制請用 `ALLOWED_HOSTS`。

---


## 部署最佳實踐

1. **環境隔離**：開發、測試、正式環境使用不同資料庫與 `.env` 設定；勿共用 `COOKIE_VALIDATION_KEY` 或 `MASTER_KEY`。
2. **安全性**：強密碼、HTTPS、正式環境關閉 `YII_DEBUG`（詳見 §安全性設定）；sa 等高權限帳號建議綁定 TOTP。
3. **備份**：定期備份資料庫與上傳目錄（`APP_PATH_UPLOADS`）；選舉期間勿將備份直接還原至 production 做驗證。
4. **金鑰管理**：正式環境使用 `VOTING_MASTER_KEY`，或金鑰檔（`VOTING_KEY_FILE`，否則 Linux `/etc/voting/master.key`／Windows `C:\ProgramData\voting\master.key`）；部署後執行 `php yii config/validate` 確認全綠。
5. **先測後上**：先在 testing／staging 完成 smoke test，再切換 production。

---

## 正式環境部署檢查表

> **用途**：首次上線或重大升級前，由**維運**逐項確認。  
> **注意**：本節為部署 SOP 摘要；**實際部署當下**仍須依目標主機環境逐項勾選，並留存簽核紀錄（可列印本表或併入變更單）。

### 環境與機敏設定

- [ ] `APP_ENV=production`（或 `prod` / `product`）
- [ ] `YII_DEBUG` 未設或為 `false`
- [ ] `ALLOWED_HOSTS` 已設為實際 FQDN（逗號分隔；不可含 `*`、`localhost`）
- [ ] `VOTING_MASTER_KEY` 或金鑰檔已設定（`VOTING_KEY_FILE`，否則 Linux `/etc/voting/master.key`／Windows `C:\ProgramData\voting\master.key`，權限 `0600`）；**正式環境不可**依賴 `.env` 的 `MASTER_KEY`
- [ ] `COOKIE_VALIDATION_KEY` 已設定且與其他環境不同
- [ ] `DB_PASSWORD` 已加密（`php yii encrypt/db-password`）；`php yii config/validate` 全綠
- [ ] `SA_DB_TOOLS_ENABLED` 未設或為 `false`
- [ ] `SENSITIVE_REAUTH=true`（密碼匯出、RBAC 同步等敏感操作）
- [ ] `CSP_MODE=report-only` 或依政策設 `enforce`
- [ ] `TRUSTED_PROXY_CIDRS` 已設（若反向代理不在 127.0.0.1）
- [ ] 遷移完成後 `DISABLE_LEGACY_ADMIN_PASSWORD=true`（先跑 `php yii migrate-passwords/admin-legacy-report`）
- [ ] `composer.lock` 已納入版控；部署使用 `composer install --no-dev`（post-install 會套用 `patches/`）

### Web 與檔案

- [ ] Document Root **僅**指向 `web/`
- [ ] `web/index-test.php`、`web/router-test.php` **不可 HTTP 存取**（`.htaccess` deny 或部署排除）
- [ ] `APP_PATH_UPLOADS` / filepool 在 docroot **外**；上傳目錄不可執行 PHP
- [ ] 驗證 filepool：`realpath` 不在 `web/` 下；目錄無 `.php` 執行權（`php_admin_value engine off` 或 nginx `location ~ \.php$ { deny all; }`）
- [ ] TLS 有效；反向代理傳 `X-Forwarded-Proto: https`

### 資料庫與權限

- [ ] MariaDB 應用帳號最小權限（非 root）
- [ ] 預設帳號 `admin` 密碼已變更

### 部署後驗證

```bash
# 設定與金鑰
php yii config/validate
php yii config/health
php yii encrypt/test
php yii migrate-passwords/verify
php yii migrate-passwords/admin-legacy-report

# HTTP smoke（於目標主機或 staging，Document Root=web/）
# production 可加：CHECK_TEST_ENTRY_BLOCKED=1
BASE_URL=https://voting.example.com tests/deploy-smoke.sh
```

- [ ] 上述指令通過
- [ ] `tests/deploy-smoke.sh` 通過（含選用之 `CHECK_TEST_ENTRY_BLOCKED=1`）
- [ ] 管理者 `/admin` 登入、匿名投票登入各抽測一場次
- [ ] sa 帳號已綁定 TOTP（`/users/totp`，建議）

### 二次驗證與 TOTP

高敏感操作（密碼 CSV 匯出、RBAC 同步）在 `SENSITIVE_REAUTH=true`（預設）時需輸入**目前登入管理員密碼**；若帳號已啟用 TOTP，另需 6 碼驗證器代碼。

**TOTP 綁定步驟：**

1. 執行 migration（新裝或升級）：`mysql voting < docs/migrations/20260713_users_totp.sql`
2. 登入後台 → **使用者管理** → **雙因素驗證**
3. 依畫面完成 QR 掃描與 6 碼確認

詳見 [`SECURITY.md`](SECURITY.md) §二次驗證。

### 開源 / 移轉釋出前

對外釋出原始碼或 tarball 前：

```bash
bash scripts/open-source-prep.sh
# 可選：RUN_DEPLOY_SMOKE=1 bash scripts/open-source-prep.sh
```

- [ ] `scripts/open-source-prep.sh` 通過（建議安裝 gitleaks 一併掃描）
- [ ] `.env`、`master.key` 未納入版控

### 選舉期間（建議）

- [ ] 監控 `LOGIN_PASSWORD_FAIL`（501）速率異常
- [ ] sa 等高權限帳號已綁定 TOTP
- [ ] 密碼 CSV 匯出雙人覆核
- [ ] 每日備份可還原驗證（**不含**還原至 production）

---

## Web Server 設定

### Apache 設定範例

```apache
<VirtualHost *:80>
    ServerName voting.example.com
    DocumentRoot /var/www/html/voting/web

    <Directory /var/www/html/voting/web>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/voting_error.log
    CustomLog ${APACHE_LOG_DIR}/voting_access.log combined
</VirtualHost>
```

### Nginx 設定範例

```nginx
server {
    listen 80;
    server_name voting.example.com;
    root /var/www/html/voting/web;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(ht|svn|git) {
        deny all;
    }
}
```

---

## 常見問題

### Q1: 出現「請確認有設定環境參數」錯誤

**A**: 請確認已設定 `APP_ENV` 環境變數或在程式中正確初始化 Config 物件。

### Q2: 資料庫連線失敗

**A**: 請檢查：
1. 資料庫服務是否啟動
2. `.env` 中的資料庫設定是否正確
3. 資料庫使用者權限是否正確設定

### Q3: 無法寫入 runtime 或 assets 目錄

**A**: 請設定正確的目錄權限：

```bash
chmod -R 775 runtime web/assets
chown -R apache:apache runtime web/assets
```

### Q4: 如何切換環境？

**A**: 修改 `.env` 檔案中的 `APP_ENV` 值即可：

```bash
APP_ENV=production   # 正式環境
APP_ENV=testing      # 測試環境
APP_ENV=development  # 開發環境
```

### Q5: 驗收測試登入失敗（403 Forbidden）

**A**: 確認載入 Fixture 時包含了 `Rbac`：

```bash
echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac" --appconfig=config/console-test.php
```

如果先執行了單元測試，單元測試會清除 Config、Users、Rbac 資料表，需重新載入。

### Q6: 執行驗收測試時連線被拒（Connection refused）

**A**: 測試伺服器未啟動。請先在獨立終端機執行：

```bash
php -S localhost:8080 -t web web/router-test.php
```

### Q7: 單元測試顯示 fixture 衝突或重複資料錯誤

**A**: 先清除所有測試資料再執行：

```bash
echo "yes" | php yii fixture/unload "*" --appconfig=config/console-test.php
php vendor/bin/codecept run unit
```

## 支援與協助

若在設定過程中遇到問題，請參考：

1. 專案的 [CLAUDE.md](CLAUDE.md) 檔案 - 完整的專案文件
2. 資安政策：[SECURITY.md](SECURITY.md)（二次驗證、開源 prep 腳本）
3. 正式部署檢查表：本文件 §正式環境部署檢查表
4. 檢查 `runtime/logs/` 目錄下的錯誤日誌
5. 提交 Issue（見 [CONTRIBUTING.md](CONTRIBUTING.md)）

---

## 版本資訊

- 文件版本：1.0.0
- 最後更新：2026-09-24
- 適用系統版本：1.0.0
- 驗證環境：Rocky Linux 10 / PHP 8.3.29 / MariaDB 10.11.15

---
