# 系統配置文件說明 (Configuration Documentation)

## 目錄 (Table of Contents)

1. [配置系統概述](#配置系統概述)
2. [環境變數 (.env)](#環境變數-env)
3. [配置架構](#配置架構)
4. [ConfigInterface 常數定義](#configinterface-常數定義)
5. [EnvPathConfigTrait 環境檢測](#envpathconfigtrait-環境檢測)
6. [配置使用範例](#配置使用範例)
7. [環境部署指南](#環境部署指南)
8. [故障排除](#故障排除)

---

## 配置系統概述

配置系統採用**環境變數驅動配置架構**，支援不同環境（正式、測試、開發）的靈活部署：

```
配置層次結構：
├── .env (環境變數) - 部署特定設定
├── ConfigInterface (介面常數) - 預設值與常數定義
├── EnvPathConfigTrait (環境檢測) - 自動環境判定
└── Config (主配置類) - 整合所有配置來源
```

**配置優先順序：**
1. `.env` 環境變數（高敏感資訊儲存於此）
2. ConfigInterface 常數（預設值）

---

## 環境變數 (.env)

### 檔案位置
- **模板檔案**: `/config/.env.example`
- **實際檔案**: `/.env` (需手動創建，不納入版本控制)

### .env 載入機制

環境變數通過 `/config/env-loader.php` 載入：

```php
// 在 web/index.php 或 yii 腳本中引入
require __DIR__ . '/../config/env-loader.php';
```

**載入規則：**
- 跳過註解行（以 `#` 開頭）
- 解析 `KEY=VALUE` 格式
- 移除值前後的引號
- **不覆蓋已存在的環境變數**（系統級環境變數優先）

### 環境變數分類

#### 1. 環境識別 (Environment Identification)

```bash
# 環境類型：production (正式), testing (測試), development (開發)
APP_ENV=production
```

### 環境類別差異 (Environment Differences)

在 `.env` 中設定 `APP_ENV` 會直接決定系統的運作行為、安全層級以及資料庫的選擇。

#### 標準化映射
系統會自動將別名映射至標準環境：
- **`production`**: 支援 `prod`, `product`
- **`testing`**: 支援 `test`
- **`development`**: 支援 `dev`, `alpha`, `ws`

#### 行為差異對照表

| 功能特性 | `production` (正式) | `testing` (測試) | `development` (開發) |
| :--- | :--- | :--- | :--- |
| **YII_DEBUG** | 強制關閉 (安全性) | 預設關閉 | 由 `.env` 決定 (預設開啟) |
| **Debug/Gii 工具** | **強行禁用** | **關閉** | 允許載入 (需 Debug 開啟) |
| **資料庫 (DB)** | 使用 `DB_DSN` 系列 | 使用 **`TEST_DB_`** 系列變數 | 使用 `DB_DSN` 系列 |
| **資料庫快取** | **開啟** (效能優化) | 關閉 | 關閉 |
| **郵件發送** | **實際送出** (SMTP) | 攔截並寫入檔案 | 攔截並寫入檔案 |
| **Cookie 安全性** | `secure=true` (**限 HTTPS**) | `secure=false` | `secure=false` |
| **配置驗證** | 基本檢核 | 檢核測試參數完整性 | **最嚴格檢核** |
| **錯誤顯示** | 隱藏細節 (導向 Error Page) | 簡化訊息 | **顯示完整堆疊追蹤** |
| **日誌層級** | `0` (僅記錄錯誤) | `0` | `3` (記錄詳細記錄) |

詳細行為說明
1. 資料庫連線 (ConfigManager::getDatabaseConfig)
Production: 會啟用 enableSchemaCache。當你修改資料庫結構時，在正式環境必須手動清除快取，否則系統會讀到舊欄位。
Development/Test: 關閉快取，確保開發時修改 Schema 能立即反映。
2. 安全性與 Session (web.php)
Production: identityCookie 與 session 的 secure 屬性會自動設為 true。這意味著如果你的網站沒有安裝 SSL 憑證 (HTTPS)，登入功能將會失效。
Development: 允許在 HTTP 環境下運行，方便局部測試。
3. 除錯工具與 Gii (web.php)
系統會檢查 YII_DEBUG && $cm->isDevelopmentEnvironment()。
只有在 APP_ENV 為開發類別（dev, development, alpha）且 YII_DEBUG 標籤為 true 時，右下角的 Debug Toolbar 與 Gii 工具網址才會生效。
4. 郵件行為 (ConfigManager::getMailerConfig)
Production: useFileTransport 為 false，會透過 SMTP 真實寄信。
非 Production: useFileTransport 為 true，郵件會被攔截並存放在 runtime/mail/ 目錄下，避免測試時誤寄信給真實使用者。

> [!IMPORTANT]
> **正式環境安全性**：切換到 `production` 時，`cookies`、`session` 與 `csrf` 的 `secure` 屬性會強制生效。若網站未架設 HTTPS，瀏覽器會拒收 Cookie，導致無法登入或 CSRF 失敗。

> [!NOTE]
> **測試環境隔離**：`testing` 環境專為自動化測試設計，會自動讀取 `TEST_DB_` 系列變數，確保測試腳本不會污染到開發或正式數據。

#### 2. 資料庫配置 (Database Configuration)

```bash
# 主資料庫連線
DB_DSN=mysql:host=localhost;dbname=voting
DB_USERNAME=root
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4

# 測試資料庫連線 (用於單元測試)
TEST_DB_DSN=mysql:host=localhost;dbname=voting_test
TEST_DB_USERNAME=root
TEST_DB_PASSWORD=your_password
```

**支援的資料庫主機：**
- `dbhost` - 預設主機
- `dbhost2` - 備援主機
- `dbhost-t` - 測試環境主機
- `dbhost-a` - Alpha 環境主機

#### 3. 應用程式基礎設定 (Application Settings)

```bash
# 應用程式名稱（顯示於頁面標題）
APP_NAME="Voting System"

# 應用程式語言
APP_LANGUAGE=zh-TW

# 除錯模式 (true/false)
YII_DEBUG=false

# 環境模式 (dev/prod/test)
YII_ENV=prod

# Cookie 驗證金鑰（必須更改為隨機字串）
COOKIE_VALIDATION_KEY=your-secret-cookie-key-here

# 加密金鑰（用於加密 .env 中的機敏資料，如 DB 密密碼）
MASTER_KEY=your-64-character-base64-key
```

#### 4. 路徑配置 (Path Configuration)

```bash
# 應用程式根目錄（絕對路徑）
APP_BASE_PATH=/var/www/html/voting

# 網站根 URL（用於產生絕對連結）
APP_BASE_URL=https://vote.example.com

# 資產檔案 URL 前綴
ASSET_URL=/assets
```

#### 5. SMTP 郵件設定 (Email Configuration)

```bash
# SMTP 伺服器設定
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=your_email@example.com
SMTP_PASSWORD=your_email_password
SMTP_ENCRYPTION=tls

# 寄件人資訊
SMTP_FROM_EMAIL=noreply@example.com
SMTP_FROM_NAME="Voting System"
```

#### 6. Proxy 代理設定 (Proxy Configuration)

```bash
# HTTP Proxy (若需要透過代理存取外部資源)
PROXY_HOST=proxy.example.com
PROXY_PORT=3128
PROXY_USERNAME=your_proxy_user
PROXY_PASSWORD=your_proxy_password

# Proxy 類型：http, socks4, socks5
PROXY_TYPE=http
```

#### 7. 日誌與快取設定 (Logging & Caching)

```bash
# 日誌等級：error, warning, info, trace
LOG_LEVEL=error

# 日誌檔案路徑
LOG_FILE_PATH=@runtime/logs/app.log

# 快取驅動：file, redis, memcached
CACHE_DRIVER=file

# Redis 設定（若使用 Redis 快取）
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_DATABASE=0
```

#### 8. 安全性設定 (Security Settings)

```bash
# 加密金鑰（用於加密 .env 中的機敏資料，如 DB 密碼）
MASTER_KEY=your-64-character-base64-key


# Session 逾時（秒）
SESSION_TIMEOUT=7200

# 允許的主機名稱（多個以逗號分隔）
ALLOWED_HOSTS=*.example.com,localhost
```

#### 9. 外部服務整合 (External Services)

```bash
# API 端點
API_ENDPOINT=https://api.example.com

# API 金鑰
API_KEY=your-api-key

# API 逾時（秒）
API_TIMEOUT=30
```

---

## 配置架構

### Config.php - 主配置類

**檔案位置**: `/config/Config.php`

**繼承結構：**
```php
class Config extends BaseObject
    implements ConfigInterface
{
    use EnvPathConfigTrait;   // 環境參數和路徑設定
    use HeaderConfigTrait;    // HTTP Header 相關設定

    // 配置方法
}
```

敏感欄位加密已改由 `ConfigManager` + `MasterKeyLoader` 處理（主金鑰派生子金鑰），
詳見 `docs/MASTER_KEY_SETUP.md` 與 `KEY_DERIVATION_RULES.md`。

```php
```

**主要方法：**

1. **`getDefaults()`** - 取得預設配置陣列
2. **`secretParams()`** - 取得機敏參數配置
3. **`encryptedParams()`** - 取得加密後的機敏參數
4. **`actionCleanCache()`** - 清理快取（runtime、assets）

**使用範例：**
```php
$config = new Config();
$defaults = $config->getDefaults();
$config = ConfigManager::getInstance();
```

---

## ConfigInterface 常數定義

**檔案位置**: `/interfaces/ConfigInterface.php`

### 環境常數 (Environment Constants)

```php
// 現代環境常數
const ENV_PRODUCTION = 'production';  // 正式環境
const ENV_TESTING = 'testing';        // 測試環境
const ENV_DEVELOPMENT = 'development';      // 開發環境
```

### 資料庫主機常數

```php
const DBHOST = 'dbhost';        // 主要資料庫
const DBHOST2 = 'dbhost2';      // 次要資料庫
const DBHOST_TEST = 'dbhost-t'; // 測試資料庫
const DBHOST_ALPHA = 'dbhost-a';// Alpha 資料庫
```

### SMTP 設定

SMTP 主機由環境變數 `SMTP_HOST` 設定（詳見上方 SMTP 郵件配置章節）。

### Proxy 設定常數

```php
const PROXY_TYPE_HTTP = CURLPROXY_HTTP;
const PROXY_TYPE_SOCKS4 = CURLPROXY_SOCKS4;
const PROXY_TYPE_SOCKS5 = CURLPROXY_SOCKS5;
```

---

## EnvPathConfigTrait 環境檢測

**檔案位置**: `/traits/EnvPathConfigTrait.php`

### 環境檢測機制（v2.0 更新）

系統使用以下**優先順序**判定當前環境：

#### 1. 環境變數 APP_ENV（最高優先權，推薦方式）
```php
// .env 或系統環境變數
APP_ENV=production   // 可選值：production, test, development
APP_ID=voting        // 應用程式識別名稱
```

這是**最推薦的方式**，適合所有部署環境：
- Docker/Kubernetes
- 傳統伺服器
- 開發環境

#### 2. 域名檢測（向後相容）
```bash
# 僅在 APP_ENV 未設定時生效，由環境變數設定域名後綴
TEST_DOMAIN_SUFFIX=-t.example.com   # 命中則視為 testing
PROD_DOMAIN_SUFFIX=.example.com     # 命中則視為 production
```

#### 3. 預設值（安全）
```php
// 無法判定時，預設為開發環境（最安全）
return 'development';
```

### 路徑配置（v2.0 更新）

路徑現在**優先使用環境變數**：

```env
# .env 檔案
APP_ID=voting                        # 應用程式識別名稱
APP_BASE_PATH=/var/www/html/voting # 應用程式根目錄
APP_PATH_PROGRAM=/var/www/html    # 程式目錄根路徑
APP_PATH_CONFIG=/var/www/config   # 機敏參數目錄
APP_PATH_DB=/var/www/db           # 資料庫備份目錄
APP_PATH_UPLOADS=/var/www/filepool      # 檔案上傳目錄
```

### 已棄用的功能

以下功能已標記為 `@deprecated`，建議使用環境變數替代：

| 舊方式 | 新方式 |
|--------|--------|
| `AREA_REGEX_SCRIPT_PATH` | `APP_ENV` + `APP_ID` |
| `AREA_REGEX_PATH` | `APP_BASE_PATH` |
| `determinePartitionByPath()` | 直接設定 `APP_ENV` |
| IP 對照表 `$_ipEnvInfo` | `APP_ENV` 環境變數 |

### 路徑管理方法

```php
// 取得當前環境
$currentEnv = $config->getCurrentEnvironment();
// 回傳：'production', 'testing', 或 'development'

// 判斷是否為特定環境
if ($config->isProductionEnvironment()) {
    // 正式環境邏輯
}
if ($config->isTestEnvironment()) {
    // 測試環境邏輯
}
if ($config->isDevelopmentEnvironment()) {
    // 開發環境邏輯
}

// 取得路徑配置
$paths = $config->getPath();
// 回傳：['apSign', 'program', 'dore', 'db', 'filePool']
```

### 遷移指南

從舊版遷移到新版的步驟：

1. 在 `.env` 檔案中設定 `APP_ENV` 和 `APP_ID`
2. 設定所需的路徑環境變數
3. 移除對 `$_ipEnvInfo` 和 `$_pathBaseDomain` 的依賴
4. 確認系統正常運作後，舊的自動檢測邏輯會被忽略


## 配置使用範例

### 1. 在應用程式中讀取配置

**在 Controller 中：**
```php
use app\config\Config;

class SiteController extends Controller
{
    public function actionIndex()
    {
        $config = new Config();

        // 取得當前環境
        $env = $config->getCurrentEnvironment();

        // 根據環境執行不同邏輯
        if ($config->isProductionEnvironment()) {
            // 正式環境邏輯
            $dbHost = ConfigInterface::DBHOST;
        } else {
            // 測試/開發環境邏輯
            $dbHost = ConfigInterface::DBHOST_TEST;
        }

        // 取得環境參數
        $config = ConfigManager::getInstance();
        $dbPassword = $config->get('DB_PASSWORD');

        return $this->render('index', [
            'environment' => $env,
            'dbHost' => $dbHost
        ]);
    }
}
```

### 2. 動態資料庫配置

**在 web.php 或 console.php 中：**
```php
use app\config\Config;

$config = new Config();
$env = $config->getCurrentEnvironment();

// 根據環境選擇資料庫
$dbConfig = [
    'class' => 'yii\db\Connection',
    'charset' => 'utf8mb4',
];

if ($env === Config::ENV_PRODUCTION) {
    $dbConfig['dsn'] = getenv('DB_DSN');
    $dbConfig['username'] = getenv('DB_USERNAME');
    $dbConfig['password'] = getenv('DB_PASSWORD');
} elseif ($env === Config::ENV_TESTING) {
    $dbConfig['dsn'] = getenv('TEST_DB_DSN');
    $dbConfig['username'] = getenv('TEST_DB_USERNAME');
    $dbConfig['password'] = getenv('TEST_DB_PASSWORD');
}

return [
    'components' => [
        'db' => $dbConfig,
        // ... 其他組件
    ],
];
```

### 3. SMTP 郵件配置

```php
use app\config\Config;
use app\interfaces\ConfigInterface;

$config = new Config();

return [
    'components' => [
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'transport' => [
                'scheme' => (getenv('SMTP_ENCRYPTION') ?: 'tls') === 'ssl' ? 'smtps' : 'smtp',
                'host' => getenv('SMTP_HOST') ?: ConfigInterface::SMTP_SINICA,
                'port' => getenv('SMTP_PORT') ?: 587,
                'username' => getenv('SMTP_USERNAME'),
                'password' => getenv('SMTP_PASSWORD'),
            ],
            'messageConfig' => [
                'from' => [
                    getenv('SMTP_FROM_EMAIL') => getenv('SMTP_FROM_NAME')
                ],
            ],
        ],
    ],
];
```

### 4. 清理快取

```php
use app\config\Config;

$config = new Config();

// 取得清理快取的 Closure
$cleanCacheAction = $config->actionCleanCache();

// 執行清理（會刪除 runtime 目錄和 web/assets 符號連結）
$cleanCacheAction(Yii::$app);
```

---

## 環境部署指南

### 正式環境部署 (Production)

#### 步驟 1: 複製並編輯 .env

```bash
cd /path/to/voting
cp config/.env.example .env
chmod 600 .env  # 限制檔案權限
```

#### 步驟 2: 編輯 .env

```bash
# 環境設定
APP_ENV=product

# 除錯關閉
YII_DEBUG=false
YII_ENV=prod

# 資料庫設定（使用正式資料庫）
DB_DSN=mysql:host=dbhost.example.com;dbname=voting
DB_USERNAME=voting_user
DB_PASSWORD=strong_password_here

# Cookie 金鑰（必須更改！）
COOKIE_VALIDATION_KEY=$(openssl rand -base64 32)

# 加密金鑰
ENCRYPTION_KEY=$(openssl rand -base64 32)
ENCRYPTION_IV=$(openssl rand -base64 16)

# SMTP 設定
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=voting@example.com
SMTP_PASSWORD=your_email_password
SMTP_ENCRYPTION=tls

# 日誌設定
LOG_LEVEL=error
```

#### 步驟 3: 設定檔案權限

```bash
# 設定目錄權限
chmod 755 /path/to/voting
chmod 755 /path/to/voting/web

# 設定可寫目錄
chmod 777 /path/to/voting/runtime
chmod 777 /path/to/voting/web/assets

# 保護敏感檔案
chmod 600 .env
chmod 600 config/*.json
```

#### 步驟 4: 初始化應用程式

```bash
# 安裝依賴
composer install --no-dev --optimize-autoloader

# 執行資料庫遷移
php yii migrate

# 清理快取
php yii cache/flush-all
```

### 測試環境部署 (Test)

```bash
# .env 設定
APP_ENV=test
YII_DEBUG=true
YII_ENV=dev

# 使用測試資料庫
DB_DSN=mysql:host=dbhost-t.example.com;dbname=voting_test
TEST_DB_DSN=mysql:host=localhost;dbname=voting_test

# 其餘設定同正式環境
```

### 開發環境部署 (Alpha/Development)

```bash
# .env 設定
APP_ENV=alpha
YII_DEBUG=true
YII_ENV=dev

# 使用本地資料庫
DB_DSN=mysql:host=localhost;dbname=voting_dev
TEST_DB_DSN=mysql:host=localhost;dbname=voting_test

# 開發環境可使用簡單密碼
COOKIE_VALIDATION_KEY=dev-key-not-for-production
```

---

## 故障排除

### 常見問題

#### 1. 環境變數未載入

**症狀:** 應用程式無法讀取 .env 設定

**解決方案:**
```php
// 檢查 web/index.php 是否包含 env-loader
require __DIR__ . '/../config/env-loader.php';

// 驗證環境變數
var_dump(getenv('APP_ENV'));
var_dump($_ENV['DB_DSN']);
```


#### 3. 環境判定錯誤

**症狀:** 系統判定為錯誤的環境

**解決方案:**
```php
// 強制指定環境（在 .env 中）
APP_ENV=production

// 或在 web/index.php 中設定
putenv('APP_ENV=production');
$_ENV['APP_ENV'] = 'production';

// 檢查環境判定邏輯
$config = new Config();
echo "Current environment: " . $config->getCurrentEnvironment();
```

#### 4. 資料庫連線失敗

**症狀:** `SQLSTATE[HY000] [2002] Connection refused`

**檢查清單:**
- [ ] DB_DSN 格式正確
- [ ] 資料庫主機可連線
- [ ] 使用者名稱/密碼正確
- [ ] 資料庫已創建
- [ ] 防火牆規則允許連線

**除錯方法:**
```php
// 測試資料庫連線
$dsn = getenv('DB_DSN');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

try {
    $pdo = new PDO($dsn, $username, $password);
    echo "Database connection successful!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

#### 5. SMTP 郵件發送失敗

**症狀:** `Connection could not be established with host`

**解決方案:**
```bash
# 測試 SMTP 連線
telnet smtp.example.com 587

# 檢查 .env 設定
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls  # 或 ssl

# 檢查 Proxy 設定（若需要透過 Proxy）
PROXY_HOST=proxy.example.com
PROXY_PORT=3128
```

#### 6. 快取問題

**症狀:** 配置更改後未生效

**解決方案:**
```bash
# 清理 Yii2 快取
php yii cache/flush-all

# 清理 runtime 目錄
rm -rf runtime/cache/*

# 清理 web/assets 符號連結
rm -rf web/assets/*

# 或使用 Config 類別提供的方法
php yii config/clean-cache
```

---

## 安全性建議

### 1. 保護 .env 檔案

```bash
# 設定嚴格的檔案權限
chmod 600 .env

# 確保 .env 不被納入版本控制
echo ".env" >> .gitignore

# 在 web server 設定中阻擋存取
# Apache (.htaccess):
<Files .env>
    Require all denied
</Files>

# Nginx:
location ~ /\.env {
    deny all;
}
```

### 2. 定期更換金鑰

```bash
# 產生新的 Cookie 驗證金鑰
openssl rand -base64 32

# 產生新的加密金鑰
openssl rand -base64 32

# 產生新的 IV
openssl rand -base64 16
```

### 3. 機敏參數管理

- 所有密碼使用加密儲存
- 定期審查有權存取 .env 的人員
- 使用環境變數而非硬編碼
- 正式環境與測試環境使用不同的金鑰

### 4. 日誌監控

```php
// 記錄配置變更
Yii::warning('Configuration changed', 'config');

// 監控異常的環境存取
if ($config->isProductionEnvironment() && YII_DEBUG) {
    Yii::error('Debug mode enabled in production!', 'security');
}
```

---

## 配置檔案清單

| 檔案 | 用途 | 版本控制 |
|------|------|----------|
| `.env` | 環境變數（實際部署） | ❌ 否（敏感資訊） |
| `config/.env.example` | 環境變數模板 | ✅ 是 |
| `config/Config.php` | 主配置類別 | ✅ 是 |
| `config/env-loader.php` | 環境變數載入器 | ✅ 是 |
| `interfaces/ConfigInterface.php` | 配置介面 | ✅ 是 |
| `traits/EnvPathConfigTrait.php` | 環境檢測 Trait | ✅ 是 |
| `config/web.php` | Web 應用程式配置 | ✅ 是 |
| `config/console.php` | Console 應用程式配置 | ✅ 是 |
| `config/params.php` | 應用程式參數 | ✅ 是 |


---

## 組織分組 preset（`config/params.php`）

`ct.division*Ary` 為**部署端可自訂**的組織分組代碼表，與執行期場次分組（`parties` 表、於 `elect/edit-vote` 設定）並行：

- **場次分組**：每場投票自行決定是否分組、分組中英文名稱；上限見 `config.partyLimit`（網站設定）。
- **Preset**：供選票編輯顯示、機構對照等；各單位可改為科／課／院區等名稱。

修改後無需 migration，部署更新 `params.php` 即可。詳見 [`SETUP.md`](../SETUP.md)「組織分組 preset」。

---

## 附錄：完整 .env 範例

```bash
##############################################
# Voting System Configuration
##############################################

# ============================================
# 環境識別 (Environment Identification)
# ============================================
# ============================================
# 環境識別 (Environment Identification)
# ============================================
APP_ENV=production

# ============================================
# 應用程式基礎設定 (Application Settings)
# ============================================
APP_NAME="Voting System"
APP_LANGUAGE=zh-TW
YII_DEBUG=false
YII_ENV=prod

# ============================================
# 安全性金鑰 (Security Keys)
# ============================================
# 重要：必須更換為隨機產生的金鑰！
# 產生方式：openssl rand -base64 32
COOKIE_VALIDATION_KEY=your-secret-cookie-key-here
ENCRYPTION_KEY=your-32-character-secret-key-here
ENCRYPTION_IV=your-16-character-iv-here

# ============================================
# 資料庫配置 (Database Configuration)
# ============================================
DB_DSN=mysql:host=dbhost.example.com;dbname=voting
DB_USERNAME=voting_user
DB_PASSWORD=your_database_password
DB_CHARSET=utf8mb4

# 測試資料庫
TEST_DB_DSN=mysql:host=localhost;dbname=voting_test
TEST_DB_USERNAME=root
TEST_DB_PASSWORD=your_test_password

# ============================================
# 路徑配置 (Path Configuration)
# ============================================
APP_BASE_PATH=/var/www/html/voting
APP_BASE_URL=https://vote.example.com
ASSET_URL=/assets

# ============================================
# SMTP 郵件設定 (Email Configuration)
# ============================================
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=voting@example.com
SMTP_PASSWORD=your_email_password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=noreply@example.com
SMTP_FROM_NAME="Voting System"

# ============================================
# Proxy 設定 (Proxy Configuration)
# ============================================
# 若不需要 Proxy，可留空
PROXY_HOST=
PROXY_PORT=
PROXY_USERNAME=
PROXY_PASSWORD=
PROXY_TYPE=http

# ============================================
# 日誌與快取 (Logging & Caching)
# ============================================
LOG_LEVEL=error
LOG_FILE_PATH=@runtime/logs/app.log
CACHE_DRIVER=file

# Redis 設定（若使用）
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_DATABASE=0

# ============================================
# Session 設定 (Session Configuration)
# ============================================
SESSION_TIMEOUT=7200

# ============================================
# 外部服務 (External Services)
# ============================================
API_ENDPOINT=https://api.example.com
API_KEY=your-api-key
API_TIMEOUT=30

# ============================================
# 其他設定 (Other Settings)
# ============================================
ALLOWED_HOSTS=*.example.com,localhost
```

---

## 總結

本配置系統提供：

✅ **靈活性** - 支援多環境部署
✅ **安全性** - 機敏參數加密保護
✅ **自動化** - 環境自動檢測
✅ **可維護性** - 清晰的配置層次

**最佳實踐：**
1. 使用 `.env` 管理部署特定設定
2. 機敏參數必須加密
3. 正式環境關閉除錯模式
4. 定期更換安全金鑰
5. 嚴格控制 `.env` 檔案權限

---

*文檔版本：1.0*
*最後更新：2026-01-13*
*維護者：Development Team*
