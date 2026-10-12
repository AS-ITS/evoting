# 更新日誌

本文件記錄各版本間的變更，格式遵循 [Keep a Changelog](https://keepachangelog.com/zh-TW/1.1.0/)。
本專案遵循 [Semantic Versioning](https://semver.org/lang/zh-TW/)。

類型：`Added` · `Changed` · `Deprecated` · `Removed` · `Fixed` · `Security`

## [Unreleased]

### Changed

* 將最低 PHP 版本與 lock file 統一為 PHP 8.2，並明列必要 extensions
* 公開文件改用通用發布、架構、環境權限與版本支援說明
* Web 測試入口改為僅在明確測試環境啟用

### Removed

* 移除組織內部發布作業手冊、主機識別、未使用品牌圖檔與過時測試紀錄
* 移除含特定機構、姓名與選舉資料的操作手冊截圖

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
