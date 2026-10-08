# 主金鑰設定指南 (Master Key Setup Guide)

## 概述

本系統使用分離式金鑰管理架構，主金鑰（Master Key）必須從安全的外部來源載入，不與加密資料存放在同一檔案中。這樣的設計確保即使 `.env` 檔案外洩，攻擊者也無法解密敏感資料。

## 金鑰用途

主金鑰透過 HKDF (HMAC-based Key Derivation Function) 派生以下子金鑰：

| 派生金鑰 | 長度 | 用途 |
|----------|------|------|
| `config-encryption` | 32 bytes | 加密 .env 中的 DB_PASSWORD 等配置 |
| `config-iv` | 16 bytes | 配置加密的初始向量 |
| `passwd-encryption` | 32 bytes | 加密匿名投票者密碼 |
| `passwd-iv` | 16 bytes | 投票密碼加密的初始向量 |

## 載入優先順序

MasterKeyLoader 依照以下優先順序載入金鑰：

```
1. 系統環境變數 VOTING_MASTER_KEY（最高優先權）
      ↓ 未設定
2. 外部金鑰檔案
   a. 若已設定 VOTING_KEY_FILE → 使用該絕對路徑
   b. 否則依作業系統預設：
      - Linux:   /etc/voting/master.key
      - Windows: C:\ProgramData\voting\master.key
      ↓ 檔案不存在
3. 環境變數 MASTER_KEY（僅限開發／測試；正式環境禁止）
```

金鑰檔**不在**專案目錄。`VOTING_KEY_FILE` 必須是絕對路徑，且不可指向 repo 內檔案。

---

## 正式環境設定

### 方式一：系統環境變數（推薦）

適合 Docker、Kubernetes、雲端平台等容器化部署。

#### Linux (systemd 服務)

```ini
# /etc/systemd/system/php-fpm.service.d/voting.conf
[Service]
Environment="VOTING_MASTER_KEY=<REPLACE_WITH_BASE64_64_BYTE_KEY>"
```

```bash
sudo systemctl daemon-reload
sudo systemctl restart php-fpm
```

#### Apache (httpd.conf 或 VirtualHost)

```apache
<VirtualHost *:443>
    ServerName voting.example.com
    SetEnv VOTING_MASTER_KEY "<REPLACE_WITH_BASE64_64_BYTE_KEY>"
    # 其他配置...
</VirtualHost>
```

#### Nginx + PHP-FPM

```ini
# /etc/php-fpm.d/www.conf 或 /etc/php/8.x/fpm/pool.d/www.conf
[www]
env[VOTING_MASTER_KEY] = "<REPLACE_WITH_BASE64_64_BYTE_KEY>"
```

#### Docker

```yaml
# docker-compose.yml
version: '3.8'
services:
  app:
    image: voting-app:latest
    environment:
      - VOTING_MASTER_KEY=${VOTING_MASTER_KEY}
    # 使用 Docker secrets（更安全）
    # secrets:
    #   - voting_master_key
```

```bash
# 執行時傳入
docker run -e VOTING_MASTER_KEY="<REPLACE_WITH_BASE64_64_BYTE_KEY>" voting-app
```

#### Kubernetes

```yaml
# voting-secret.yaml
apiVersion: v1
kind: Secret
metadata:
  name: voting-secrets
  namespace: voting
type: Opaque
stringData:
  VOTING_MASTER_KEY: "<REPLACE_WITH_BASE64_64_BYTE_KEY>"
---
# deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: voting-app
spec:
  template:
    spec:
      containers:
        - name: app
          envFrom:
            - secretRef:
                name: voting-secrets
```

---

### 方式二：外部金鑰檔案

適合傳統伺服器部署。

#### Linux

```bash
# 建立目錄
sudo mkdir -p /etc/voting

# 寫入金鑰
echo "<REPLACE_WITH_BASE64_64_BYTE_KEY>" | sudo tee /etc/voting/master.key

# 設定權限（重要！）
sudo chmod 600 /etc/voting/master.key
sudo chown www-data:www-data /etc/voting/master.key  # 根據 web server 使用者調整
```

#### Windows

```powershell
# 建立目錄
New-Item -ItemType Directory -Path "C:\ProgramData\voting" -Force

# 寫入金鑰
"<REPLACE_WITH_BASE64_64_BYTE_KEY>" | Out-File -FilePath "C:\ProgramData\voting\master.key" -Encoding UTF8 -NoNewline

# 設定 ACL 權限
$acl = Get-Acl "C:\ProgramData\voting\master.key"
$acl.SetAccessRuleProtection($true, $false)
$rule = New-Object System.Security.AccessControl.FileSystemAccessRule("IIS_IUSRS", "Read", "Allow")
$acl.AddAccessRule($rule)
$rule = New-Object System.Security.AccessControl.FileSystemAccessRule("Administrators", "FullControl", "Allow")
$acl.AddAccessRule($rule)
Set-Acl "C:\ProgramData\voting\master.key" $acl
```

#### 自訂金鑰檔案路徑

若設定 `VOTING_KEY_FILE`，會**優先於**上述 Linux／Windows 預設路徑（仍低於 `VOTING_MASTER_KEY`）。必須為絕對路徑，且不要放在專案目錄內。

```bash
export VOTING_KEY_FILE="/var/lib/voting/master.key"
# PHP-FPM 請寫入 pool 設定，例如：
# env[VOTING_KEY_FILE] = /var/lib/voting/master.key
```

---

## 開發環境設定

開發環境可在 `.env` 檔案中設定 `MASTER_KEY`：

```env
# .env（僅限開發環境）
MASTER_KEY=<REPLACE_WITH_BASE64_64_BYTE_KEY>
```

> **警告**：若在正式環境（`APP_ENV=production`）使用此方式，系統會記錄警告訊息。

---

## 生成新金鑰

```bash
# 生成 64 bytes (512 bits) 的 Base64 編碼金鑰
php -r "echo base64_encode(random_bytes(64)) . PHP_EOL;"

# 每次執行輸出不同；請使用您自己生成的值，勿複製文件範例。
```

---

## 金鑰管理最佳實踐

### 安全性

1. **永不將主金鑰提交到版本控制**
   - 確保 `.env` 在 `.gitignore` 中
   - 金鑰檔案路徑不應在專案目錄內

2. **限制金鑰存取權限**
   - 金鑰檔案權限：`0600`（僅 owner 可讀寫）
   - 只有必要的服務帳號可存取

3. **定期輪換金鑰**
   - 建議每 1-2 年輪換一次
   - 輪換時需重新加密所有資料

### 備份

1. **安全備份金鑰**
   - 備份到與應用程式分開的安全位置
   - 考慮使用 HSM 或金鑰管理服務（AWS KMS、Azure Key Vault 等）

2. **災難復原計畫**
   - 確保有多個金鑰副本
   - 記錄金鑰復原程序

### 監控

1. **記錄金鑰存取**
   - 啟用金鑰檔案的稽核記錄
   - 監控異常存取模式

2. **健康檢查**
   - 系統啟動時驗證金鑰是否正確載入
   - 定期檢查金鑰狀態

---

## 故障排除

### 錯誤：無法載入主金鑰

```
InvalidConfigException: 無法載入主金鑰 (MASTER_KEY)。
```

**解決方式**：

1. 檢查系統環境變數是否設定：
   ```bash
   echo $VOTING_MASTER_KEY
   ```

2. 檢查金鑰檔案是否存在且可讀：
   ```bash
   ls -la /etc/voting/master.key
   cat /etc/voting/master.key
   ```

3. 檢查 PHP-FPM 是否重啟以載入新環境變數：
   ```bash
   sudo systemctl restart php-fpm
   ```

### 錯誤：主金鑰長度不足

```
InvalidConfigException: 主金鑰長度不足（當前：16 bytes，建議：至少 32 bytes）
```

**解決方式**：

重新生成足夠長度的金鑰：
```bash
php -r "echo base64_encode(random_bytes(64)) . PHP_EOL;"
```

### 檢查金鑰載入狀態

可透過 PHP 程式碼檢查：

```php
use app\components\MasterKeyLoader;

$loader = MasterKeyLoader::getInstance();
$report = $loader->getStatusReport();
print_r($report);
```

輸出範例：
```php
Array
(
    [system_env] => Array
        (
            [name] => VOTING_MASTER_KEY
            [configured] => true
        )
    [key_file] => Array
        (
            [path] => /etc/voting/master.key
            [exists] => false
            [readable] => false
        )
    [env_fallback] => Array
        (
            [name] => MASTER_KEY
            [configured] => false
            [warning] => 僅建議用於開發環境
        )
    [current_source] => system_env:VOTING_MASTER_KEY
    [is_production] => true
)
```

---

## 架構圖

```
┌─────────────────────────────────────────────────────────────┐
│                        執行環境                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │ 環境變數        │  │ 金鑰檔案        │  │ .env 檔案   │ │
│  │ VOTING_MASTER_  │  │ VOTING_KEY_FILE │  │ MASTER_KEY  │ │
│  │ KEY             │  │ 否則 OS 預設    │  │ (僅開發)    │ │
│  └────────┬────────┘  └────────┬────────┘  └──────┬──────┘ │
│           │                    │                   │        │
│           │    優先順序: 1     │         2         │    3   │
│           └────────────────────┼───────────────────┘        │
│                                ↓                            │
│                    ┌───────────────────────┐                │
│                    │   MasterKeyLoader     │                │
│                    │   (金鑰載入器)        │                │
│                    └───────────┬───────────┘                │
│                                ↓                            │
│                    ┌───────────────────────┐                │
│                    │   KeyDerivation       │                │
│                    │   (HKDF 金鑰派生)     │                │
│                    └───────────┬───────────┘                │
│                                │                            │
│           ┌────────────────────┼────────────────────┐       │
│           ↓                    ↓                    ↓       │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │ config-         │  │ config-iv       │  │ passwd-     │ │
│  │ encryption      │  │ (16 bytes)      │  │ encryption  │ │
│  │ (32 bytes)      │  │                 │  │ (32 bytes)  │ │
│  └────────┬────────┘  └────────┬────────┘  └──────┬──────┘ │
│           │                    │                   │        │
│           └────────────────────┼───────────────────┘        │
│                                ↓                            │
│           ┌────────────────────────────────────────┐        │
│           │ .env 檔案                              │        │
│           │ DB_PASSWORD=<encrypted_value> │        │
│           │ (已加密，無金鑰無法解密)               │        │
│           └────────────────────────────────────────┘        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 相關文件

- [KEY_DERIVATION_RULES.md](KEY_DERIVATION_RULES.md) - 金鑰派生規則
- [CONFIGURATION.md](CONFIGURATION.md) - 系統配置指南
