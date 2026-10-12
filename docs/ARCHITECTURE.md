# 系統架構

本文件提供電子投票系統的技術架構與主要擴充點。安裝與環境設定見 [SETUP.md](../SETUP.md)，安全設計見 [SECURITY.md](../SECURITY.md) 與 [threat-model](threat-model/README.md)。

## 技術基礎

- PHP 8.2+、Yii 2、MariaDB 10.6+
- Bootstrap 5、AdminLTE 3
- Composer 管理 PHP 相依；`frontend/` 為已納管的前端執行期資產
- Codeception 執行 unit、functional 與 acceptance tests

## 投票類型

- **表決投票**（`TYPE_NO_AUTH = '0'`）：不需投票者驗證。
- **匿名投票**（`TYPE_ANON = '1'`）：以一次性亂數密碼驗證。

已移除記名投票（舊 `TYPE_VOTER = '2'`）；新功能不得重新建立選民身分與匿名選票的連結。

## 身分與授權

系統有兩個獨立 identity component：

- `Yii::$app->user`：後台管理員，使用本機帳號密碼與 RBAC。
- `Yii::$app->anon`：匿名投票者，以投票密碼登入；`Logins` 防止重複 session。

兩者皆基於 `models/UserIdentity.php` 的 session identity。後台角色包含 `sa`、`va`、`ga`、`gm`；權限規則位於 `rules/`。

## 核心資料模型

```text
Votes
├── Round
│   └── Questions
│       ├── CandiConfig
│       ├── CandiData
│       └── QuestionsGroupRule
├── Parties
├── Passwords
├── Ballots
│   └── BallotsSelected
└── Results
```

許多資料表使用複合主鍵；修改查詢或關聯前應先確認 model 的 `primaryKey()` 與 `get*()` relation。動態資料表操作使用 `models/UniversalActiveRecord.php`。

## 主要流程

1. 建立投票場次與分組。
2. 設定輪次及問題。
3. 設定候選人產生方式與名單。
4. 匿名投票產生並發放一次性密碼。
5. 投票時以 transaction 建立 `Ballots` 與 `BallotsSelected`。
6. 開票、計算結果並依 `ResultsConfig` 顯示。

投票建立與計票規則屬高風險區域；修改時需涵蓋分組、多輪、補登、序位及匿名性測試。

## 設定與金鑰

- `.env` 由 `config/env-loader.php` 載入。
- `APP_ENV` 支援 `production`、`testing`、`development`。
- `components/MasterKeyLoader.php` 依序讀取 `VOTING_MASTER_KEY`、外部金鑰檔、非正式環境的 `MASTER_KEY`。
- 正式環境不得把主金鑰提交至 repository 或只存於 `.env`。

詳細鍵名與優先順序見 [CONFIGURATION.md](CONFIGURATION.md) 與 [MASTER_KEY_SETUP.md](MASTER_KEY_SETUP.md)。

## 擴充慣例

- Controller 應繼承 `components/Controller.php`。
- Form/model 基類使用 `components/Model.php`。
- 可重用 UI 放在 `widgets/`；前端 asset bundle 放在 `assets/`。
- 使用 `Yii::t()` 與 `messages/` 維持中英文支援。
- 重要管理操作使用 `models/Logs::log()` 留下操作紀錄。
- 對話框沿用 `kartik-v/yii2-dialog` 或 Bootstrap 5 Modal。

## 測試邊界

- 測試資料庫必須與正式資料庫分離。
- Fixture 指令一律加上 `--appconfig=config/console-test.php`。
- Unit 與 functional tests：`bash tests/run-all.sh`。
- Acceptance tests：依 [tests/README.md](../tests/README.md) 啟動本機測試伺服器。
- `web/index-test.php` 與 `web/router-test.php` 只允許在明確測試環境執行。

## 目錄

- `controllers/`：HTTP controller
- `models/`：ActiveRecord、form model 與 domain logic
- `components/`：應用元件與安全服務
- `rules/`：RBAC rule
- `views/`、`widgets/`、`assets/`：介面
- `config/`：Web、console、test 設定
- `docs/migrations/`：增量 SQL
- `tests/`：測試、fixture 與測試工具
