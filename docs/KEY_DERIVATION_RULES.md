# 金鑰派生規則 (Key Derivation Rules)

**版本**: 1.0
**日期**: 2026-01-15
**狀態**: 強制執行 (Mandatory)

---

## 概述

本系統使用 **HKDF (HMAC-based Key Derivation Function)** 從單一主金鑰派生所有加密金鑰，符合 RFC 5869 標準。

---

## 主金鑰 (MASTER_KEY)

### 規格

- **長度**: 64 bytes (512 bits)
- **編碼**: Base64
- **存放位置**: `.env` 檔案
- **環境變數名稱**: `MASTER_KEY`

### 生成方式

```bash
php -r "echo base64_encode(random_bytes(64)) . PHP_EOL;"
```

### 範例

```ini
# .env 檔案格式
MASTER_KEY=<REPLACE_WITH_BASE64_64_BYTE_KEY>
COOKIE_VALIDATION_KEY=<REPLACE_WITH_RANDOM_HEX_KEY>
```

**注意**: 所有環境變數使用大寫蛇形命名法（UPPER_SNAKE_CASE），與標準 .env 格式一致。

---

## 派生金鑰標準

### 金鑰派生函數

```php
hash_hkdf(
    'sha256',           // 演算法：SHA-256
    $masterKey,         // 輸入金鑰材料 (64 bytes)
    $length,            // 輸出長度 (bytes)
    $context,           // 應用特定上下文字串
    $salt               // 可選鹽值（預設為空字串）
);
```

### 上下文字串命名規則

**格式**: `<用途>-<類型>`

**規則**:
1. 使用小寫英文字母
2. 單字間用連字號 `-` 分隔
3. 用途描述要具體且唯一
4. 類型通常為 `encryption` 或 `iv`

**範例**:
- ✅ `config-encryption`
- ✅ `passwd-iv`
- ❌ `configEncryption` (不使用駝峰命名)
- ❌ `encryption` (不夠具體)

---

## 已定義的派生金鑰

所有上下文常數定義於 `app\interfaces\KeyContextInterface`。

### 1. 配置加密金鑰

| 屬性 | 值 |
|------|-----|
| **常數** | `KeyContextInterface::CONFIG_ENCRYPTION` |
| **上下文** | `config-encryption` |
| **長度** | 32 bytes (256 bits) |
| **用途** | 加密 .env 中的敏感配置資料 |
| **使用於** | ConfigManager::encrypt() / decrypt() |
| **加密演算法** | AES-256-CBC |

**派生代碼**:
```php
use app\interfaces\KeyContextInterface;

$encryptionKey = $keyDerivation->deriveKey(
    KeyContextInterface::CONFIG_ENCRYPTION,
    KeyContextInterface::KEY_LENGTH_AES256
);
```

### 2. 配置加密 IV

| 屬性 | 值 |
|------|-----|
| **常數** | `KeyContextInterface::CONFIG_IV` |
| **上下文** | `config-iv` |
| **長度** | 16 bytes (128 bits) |
| **用途** | AES-256-CBC 的初始化向量 |
| **使用於** | ConfigManager::encrypt() / decrypt() |
| **配對金鑰** | config-encryption |

**派生代碼**:
```php
$iv = $keyDerivation->deriveKey(
    KeyContextInterface::CONFIG_IV,
    KeyContextInterface::IV_LENGTH_AES_CBC
);
```

### 3. 投票密碼加密金鑰

| 屬性 | 值 |
|------|-----|
| **常數** | `KeyContextInterface::PASSWD_ENCRYPTION` |
| **上下文** | `passwd-encryption` |
| **長度** | 32 bytes (256 bits) |
| **用途** | 加密匿名投票密碼 |
| **使用於** | Passwd::encrypt() / decrypt() |
| **加密演算法** | AES-256-CBC |
| **資料表** | passwords.passwd |

**派生代碼**:
```php
use app\interfaces\KeyContextInterface;

$encryptionKey = $keyDerivation->deriveKey(
    KeyContextInterface::PASSWD_ENCRYPTION,
    KeyContextInterface::KEY_LENGTH_AES256
);
```

### 4. 投票密碼加密 IV

| 屬性 | 值 |
|------|-----|
| **常數** | `KeyContextInterface::PASSWD_IV` |
| **上下文** | `passwd-iv` |
| **長度** | 16 bytes (128 bits) |
| **用途** | AES-256-CBC 的初始化向量 |
| **使用於** | Passwd::encrypt() / decrypt() |
| **配對金鑰** | passwd-encryption |

**派生代碼**:
```php
$iv = $keyDerivation->deriveKey(
    KeyContextInterface::PASSWD_IV,
    KeyContextInterface::IV_LENGTH_AES_CBC
);
```

---

## 新增派生金鑰流程

### 步驟 1: 定義上下文字串

選擇一個唯一的上下文字串，遵循命名規則。

**範例**: 假設要為 Session 資料加密新增金鑰
```
上下文字串：
- session-encryption (加密金鑰)
- session-iv (初始化向量)
```

### 步驟 2: 確定金鑰長度

根據加密演算法選擇適當長度：

| 演算法 | 金鑰長度 | IV 長度 |
|--------|---------|---------|
| AES-128-CBC | 16 bytes | 16 bytes |
| AES-256-CBC | 32 bytes | 16 bytes |
| ChaCha20 | 32 bytes | 12 bytes |

### 步驟 3: 實作派生代碼

```php
use app\components\KeyDerivation;

$masterKey = getenv('MASTER_KEY');
$kd = new KeyDerivation($masterKey);

// 派生加密金鑰
$encryptionKey = $kd->deriveKey('session-encryption', 32);

// 派生 IV
$iv = $kd->deriveKey('session-iv', 16);
```

### 步驟 4: 更新文件

在本文件的「已定義的派生金鑰」章節新增條目。

### 步驟 5: 程式碼審查

確保：
- ✅ 上下文字串唯一且不與現有金鑰衝突
- ✅ 金鑰長度符合演算法要求
- ✅ 使用 `OPENSSL_RAW_DATA` 選項
- ✅ 不在版本控制中存放派生後的金鑰

---

## 使用範例

### 基本加密/解密

```php
use app\components\KeyDerivation;

// 1. 初始化 KeyDerivation
$masterKey = getenv('MASTER_KEY');
$kd = new KeyDerivation($masterKey);

// 2. 派生金鑰
$key = $kd->deriveKey('my-app-encryption', 32);
$iv = $kd->deriveKey('my-app-iv', 16);

// 3. 加密
$plaintext = "敏感資料";
$encrypted = openssl_encrypt(
    $plaintext,
    'aes-256-cbc',
    $key,
    OPENSSL_RAW_DATA,
    $iv
);
$encryptedBase64 = base64_encode($encrypted);

// 4. 解密
$decrypted = openssl_decrypt(
    base64_decode($encryptedBase64),
    'aes-256-cbc',
    $key,
    OPENSSL_RAW_DATA,
    $iv
);
```

### 在 ConfigManager 中使用

```php
// ConfigManager 自動使用派生金鑰
$configManager = ConfigManager::getInstance();

// 加密配置值
$encrypted = $configManager->encrypt('secret_value');

// 解密配置值
$decrypted = $configManager->get('DB_PASSWORD'); // 自動解密
```

### 在 Passwd 模型中使用

```php
$passwd = new Passwd();

// 加密密碼（自動使用派生金鑰）
$encrypted = $passwd->encrypt('user_password');

// 解密密碼
$decrypted = $passwd->decrypt($encrypted);
```

---

## 安全性要求

### ✅ 必須遵守

1. **MASTER_KEY 保護**
   - 不可提交到版本控制 (`.gitignore` 必須包含 `.env`)
   - 檔案權限設為 `640` (owner: rw, group: r)
   - 定期備份到安全位置

2. **金鑰派生**
   - 所有加密金鑰必須從 MASTER_KEY 派生
   - 不可在程式碼中硬編碼任何加密金鑰或 IV
   - 不可在 params.php 或其他配置檔中設定加密金鑰

3. **上下文隔離**
   - 不同用途必須使用不同的上下文字串
   - 上下文字串一旦定義不可更改（會導致無法解密舊資料）

4. **加密參數**
   - 必須使用 `OPENSSL_RAW_DATA` 選項
   - 結果必須經過 Base64 編碼再儲存

### ❌ 嚴禁

1. **不可使用自訂密碼**
   ```php
   // ❌ 錯誤
   $passwd->encrypt($text, 'my-custom-password');

   // ✅ 正確
   $passwd->encrypt($text); // 使用派生金鑰
   ```

2. **不可硬編碼 IV**
   ```php
   // ❌ 錯誤
   public static $iv = 'hardcoded_iv';

   // ✅ 正確
   $iv = $keyDerivation->deriveKey('my-app-iv', 16);
   ```

3. **不可重複使用上下文**
   ```php
   // ❌ 錯誤 - 重複使用相同上下文
   $key1 = $kd->deriveKey('encryption', 32);
   $key2 = $kd->deriveKey('encryption', 32); // 與 key1 相同

   // ✅ 正確 - 使用不同上下文
   $key1 = $kd->deriveKey('config-encryption', 32);
   $key2 = $kd->deriveKey('passwd-encryption', 32); // 不同金鑰
   ```

---

## 金鑰輪換 (Key Rotation)

### 輪換流程

當需要更換 MASTER_KEY 時：

#### 步驟 1: 生成新的 MASTER_KEY

```bash
php -r "echo base64_encode(random_bytes(64)) . PHP_EOL;"
```

#### 步驟 2: 解密所有資料（使用舊金鑰）

```bash
# 備份所有加密資料的明文
php yii key-rotation/backup-decrypt
```

#### 步驟 3: 更新 .env 中的 MASTER_KEY

```ini
# 舊的 MASTER_KEY（暫時保留）
OLD_MASTER_KEY=old_key_here...

# 新的 MASTER_KEY
MASTER_KEY=new_key_here...
```

#### 步驟 4: 重新加密所有資料（使用新金鑰）

```bash
# 使用新金鑰重新加密資料庫密碼
php yii encrypt/db-password "actual_password"

# 使用新金鑰重新加密投票密碼
php yii migrate-passwords/reencrypt
```

#### 步驟 5: 驗證

```bash
# 驗證所有加密資料可正常解密
php yii migrate-passwords/verify

# 執行測試
php vendor/bin/codecept run unit
```

#### 步驟 6: 移除舊金鑰

從 `.env` 移除 `OLD_MASTER_KEY`。

### 輪換頻率建議

- **正常情況**: 每 1-2 年
- **安全事件**: 立即輪換
- **人員異動**: 評估是否需要輪換

---

## 疑難排解

### 問題 1: "MASTER_KEY 未設定"

**錯誤訊息**:
```
環境變數 MASTER_KEY 未設定。
請使用以下命令生成：php -r "echo base64_encode(random_bytes(64)) . PHP_EOL;"
```

**解決方法**:
1. 生成 MASTER_KEY
2. 加入到 `.env` 檔案
3. 重新啟動應用程式

### 問題 2: "解密失敗"

**可能原因**:
- 資料使用不同的 MASTER_KEY 加密
- 資料已損壞
- 上下文字串錯誤

**解決方法**:
```bash
# 檢查 MASTER_KEY 是否正確
php -r "var_dump(getenv('MASTER_KEY'));"

# 驗證密碼加密
php yii migrate-passwords/verify
```

### 問題 3: "不支援使用自訂密碼"

**錯誤訊息**:
```
不支援使用自訂密碼。請使用 MASTER_KEY 派生的金鑰（將 $password 參數設為 null）
```

**解決方法**:
```php
// ❌ 錯誤
$passwd->encrypt($text, 'custom_password');

// ✅ 正確
$passwd->encrypt($text); // 或 $passwd->encrypt($text, null);
```

---

## 合規性與稽核

### 檢查清單

#### 開發階段
- [ ] 所有加密金鑰從 MASTER_KEY 派生
- [ ] 無硬編碼的金鑰或 IV
- [ ] .env 已加入 .gitignore
- [ ] 使用 OPENSSL_RAW_DATA 選項
- [ ] 上下文字串已記錄於本文件

#### 部署階段
- [ ] .env 檔案權限設為 640
- [ ] MASTER_KEY 已備份到安全位置
- [ ] 測試通過率 ≥ 85%
- [ ] 生產環境不使用範例金鑰

#### 維運階段
- [ ] 定期檢查 .env 權限
- [ ] MASTER_KEY 定期輪換
- [ ] 監控解密失敗日誌
- [ ] 保存金鑰輪換歷史

### 稽核命令

```bash
# 檢查 .env 權限
ls -la .env

# 檢查 MASTER_KEY 長度
php -r "echo strlen(base64_decode(getenv('MASTER_KEY'))) . ' bytes' . PHP_EOL;"

# 驗證所有加密功能
php yii migrate-passwords/verify

# 檢查是否有硬編碼金鑰
grep -r "encryptKey\|ENCRYPTION_KEY" config/ models/ --exclude-dir=vendor
```

---

## 版本歷史

| 版本 | 日期 | 變更內容 |
|------|------|----------|
| 1.0 | 2026-01-15 | 初版發布，定義 4 個派生金鑰 |

---

## 參考資料

- [RFC 5869 - HKDF](https://tools.ietf.org/html/rfc5869)
- [PHP hash_hkdf() 文件](https://www.php.net/manual/en/function.hash-hkdf.php)
- [NIST SP 800-108 - Key Derivation](https://csrc.nist.gov/publications/detail/sp/800-108/rev-1/final)
- [OWASP Key Management](https://cheatsheetseries.owasp.org/cheatsheets/Key_Management_Cheat_Sheet.html)

---

## 聯絡資訊

如有疑問或需要新增派生金鑰，請聯絡：

- **技術負責人**: Configuration Management Team
- **文件維護**: 系統管理員
- **更新日期**: 2026-01-15
