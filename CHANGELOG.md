# 更新日誌

本文件記錄各版本間的變更，格式遵循 [Keep a Changelog](https://keepachangelog.com/zh-TW/1.1.0/)。
本專案遵循 [Semantic Versioning](https://semver.org/lang/zh-TW/)。

類型：`Added` · `Changed` · `Deprecated` · `Removed` · `Fixed` · `Security`

## [Unreleased]

### Changed

* 將最低 PHP 版本與 lock file 統一為 PHP 8.2，並明列必要 extensions
* 公開文件改用通用發布、架構、環境權限與版本支援說明
* Web 測試入口改為僅在明確測試環境啟用
* 操作手冊截圖對人名、專長與 IP 等欄位去識別化
* 安裝說明標明 `composer install --prefer-dist` 與 `composer check-platform-reqs`
* 測試文件 fixture 清單對齊 `tests/run-all.sh`（含 Group／Ballots／ResultsConfig）

### Added

* 管理員忘記密碼：登入頁自助重設（須先跑 `docs/migrations/20261021_password_reset.sql`；`users.email`、30 分鐘單次連結、既有 session 作廢；不關閉 TOTP）
* 新裝精靈 `scripts/install.sh`（composer、可寫目錄、`.env`、可選建庫／`init_db.sql`／admin）
* `install.sh` 環境檢查（PHP 硬擋；DB／Web 版本僅警示）、既有 DB／`master.key`／`.env`／admin 沿用
* `scripts/encrypt-config-value.php`、`scripts/rebuild-yii-extensions.php`（正式密文與 extensions.php 修復）
* `scripts/ensure-writable-dirs.sh`；composer post-install 自動建立 `runtime/sessions` 等目錄

### Security

* 重設密碼回應不透露帳號是否存在；token 只存 SHA-256；同帳號／IP 15 分鐘最多 5 次申請

### Fixed

* Functional Cest 在 `cleanup: false` 下於 `_before` 強制 `haveFixtures`，避免後續 Cest 缺 Group／ResultsConfig／Ballots

### Removed

* 移除組織內部發布作業手冊、主機識別、未使用品牌圖檔與過時測試紀錄

## [1.0.0] - 2026-10-08

開源初始版。

### Changed

* 安裝路徑改為 `git clone` + `composer install`（依 lock）；`patches/` 由 post-install 自動套用
* Cookie 金鑰只走 `.env` 的 `COOKIE_VALIDATION_KEY`，不再於 `composer install` 改 `config/web.php`
* 同步 `composer.lock`，移除已由目前 Gii／PHPUnit 版本修補的過期 audit ignore

### Removed

* Yii basic 模板殘件：`post-create-project-cmd`、`generateCookieValidationKey`、指向 Yii 官方的 `support`／placeholder `homepage`
* 移除未參與產品安裝或執行的 vendored 前端建置 lockfile；執行期資產維持預先建置版本

[Unreleased]: https://github.com/AS-ITS/evoting/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/AS-ITS/evoting/releases/tag/v1.0.0
