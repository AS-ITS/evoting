# 開源遷移說明

本文件記錄了 Academia Sinica Academician Election System（院士選舉系統）從院內專屬系統改造為通用開源系統的變更內容。

## 📅 遷移日期

2026-01-08

## 🎯 遷移目標

將系統從中研院內部專屬配置改為通用的開源配置，使其他機構可以輕鬆部署和使用。

---

## 🔄 主要變更

### 1. 環境參數簡化

**變更前（4 個環境）:**
- `product` - 正式區
- `test` - 測試區
- `alpha` - 開發區
- `ws` - 開發區 WS

**變更後（3 個標準環境）:**
- `production` - 正式環境
- `testing` - 測試環境
- `development` - 開發環境

### 2. 配置系統重構

#### 移除的院內特定設定

**IP 地址:**
- 移除中研院內部 IP 對照表（`10.109.x.x`, `140.109.x.x`）
- 改為可選的 IP 對照功能

**域名:**
- 移除 `*.sinica.edu.tw` 硬編碼域名
- 改為通用的域名格式（`example.com`）

**服務端點:**
- 移除 FISA API 院內端點
- 移除 OIDC 院內認證端點
- 移除中研院專屬 MSSQL、Proxy 設定

#### 新增的通用配置

**環境變數支援:**
- 新增 `.env.example` 範例文件
- 支援完整的環境變數配置
- 優先順序：環境變數 > 預設值

**路徑配置:**
- 從 `/home/vhost/` 改為通用的 `/var/www/`
- 支援透過環境變數自訂所有路徑

### 3. 文件更新

#### [ConfigInterface.php](interfaces/ConfigInterface.php)

**主要變更:**
- 環境常數從 4 個簡化為 3 個
- 所有硬編碼的院內 IP、域名改為預設值或空字串
- 所有常數添加環境變數設定說明
- 資料庫主機從內部 IP 改為 `localhost`
- SMTP 設定從院內伺服器改為通用設定

#### [EnvPathConfigTrait.php](traits/EnvPathConfigTrait.php)

**主要變更:**
- 新增環境變數讀取功能（`getenv('APP_ENV')`）
- 支援多種環境值寫法的標準化
- 移除院內 IP 對照表，改為空的範例
- 路徑正則表達式支援 `/var/www/` 和 `/home/vhost/` 兩種格式
- 域名判斷改用關鍵字匹配（`prod`, `test`, `dev`, `staging` 等）
- `getPath()` 方法支援環境變數覆蓋路徑

### 4. 新增文件

#### [.env.example](.env.example)
完整的環境變數範例文件，包含：
- 應用程式基本設定
- 路徑配置
- 資料庫設定
- SMTP 郵件設定
- Proxy 設定
- 安全性設定
- 除錯模式設定
- 快取和日誌設定

#### [SETUP.md](SETUP.md)
詳細的環境設定指南，包含：
- 系統需求
- 快速開始步驟
- 環境配置說明
- 資料庫設定
- 目錄結構設定
- Web Server 配置範例
- 常見問題解答

#### [.gitignore](.gitignore)
完整的 Git 忽略規則，確保：
- 環境變數文件不被提交
- IDE 配置不被提交
- 日誌和快取不被提交
- 上傳文件和備份不被提交

---

## 🔐 安全性改進

### 1. 敏感資訊保護

**變更前:**
- 硬編碼院內 IP 地址
- 硬編碼院內服務端點
- 配置文件直接包含在代碼庫中

**變更後:**
- 所有敏感資訊透過環境變數配置
- 提供 `.env.example` 範例文件，實際配置文件不納入版控
- `.gitignore` 確保敏感文件不會被意外提交

### 2. 環境變數優先
改為完全基於環境變數的配置管理，簡化部署流程。

---

## 📦 部署變更

### 變更前（院內部署）

1. 固定路徑：`/home/vhost/html/{app}`
2. 固定機敏參數路徑：`/home/vhost/.dore/{app}`
3. 依賴院內 IP 自動判斷環境
4. 依賴特定域名格式（`*.sinica.edu.tw`）

### 變更後（通用部署）

1. 可自訂路徑（預設 `/var/www/html/{app}`）
2. 透過環境變數指定環境類型
4. 支援任意域名格式

---

## 🔧 向下相容性

### 保留的功能

2. **路徑格式:**
   - 同時支援 `/home/vhost/html/` 和 `/var/www/html/`
   - 正則表達式匹配兩種格式

3. **IP 對照功能:**
   - 保留 `$_ipEnvInfo` 結構
   - 提供範例格式說明
   - 可選擇性使用

### 需要調整的部分

如果您要將院內現有系統遷移到新配置：

1. **更新環境變數:**
   ```bash
   # 舊設定（在程式碼中）
   'envRmt' => 'product'

   # 新設定（在 .env 中）
   APP_ENV=production
   ```

2. **更新路徑配置:**
   ```bash
   # 在 .env 中指定原有路徑
   APP_PATH_PROGRAM=/home/vhost/html
   APP_PATH_CONFIG=/home/vhost/.dore
   ```

3. **保留 IP 對照:**
   ```php
   // 在 EnvPathConfigTrait.php 的 $_ipEnvInfo 中
   // 填入院內 IP 對照表（範例已在程式碼註解中）
   ```

---

## 📝 遷移檢查清單

### 開源前準備

- [x] 移除所有硬編碼的院內 IP 地址
- [x] 移除所有硬編碼的院內域名
- [x] 移除所有硬編碼的院內服務端點
- [x] 建立環境變數範例文件
- [x] 建立完整的設定指南
- [x] 更新 .gitignore 排除敏感文件
- [x] 環境參數標準化（3 個環境）
- [x] 添加向下相容性支援

### 使用者部署清單

已移至 [SETUP.md](SETUP.md) §快速開始、§首次部署檢查清單 與 §正式環境部署檢查表。

---

## 後續文件

本文件僅保留 **2026-01-08 院內→通用化遷移紀錄**。部署 onboarding 與維運指引已拆分至：

| 文件 | 內容 |
|------|------|
| [README.md](README.md) | 快速開始、文件索引、維護等級 |
| [SETUP.md](SETUP.md) | 完整安裝、首次部署檢查清單、部署最佳實踐、正式環境檢查表 |
| [SECURITY.md](SECURITY.md) | 資安政策、部署安全提醒、釋出前檢查 |
| [CONTRIBUTING.md](CONTRIBUTING.md) | 貢獻與 Issue／PR |
| [LICENSE](LICENSE) | MIT |
| [docs/授權合規評估檢核表.md](docs/授權合規評估檢核表.md) | 授權合規檢核表 |


專案授權為 [MIT License](LICENSE)。對話框使用 kartik-v/yii2-dialog 與 Bootstrap 5 Modal；Webix 已自程式與資產樹移除。