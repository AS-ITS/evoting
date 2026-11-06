# 更新日誌

本文件記錄各版本間的變更，格式遵循 [Keep a Changelog](https://keepachangelog.com/zh-TW/1.1.0/)。
本專案遵循 [Semantic Versioning](https://semver.org/lang/zh-TW/)。

類型：`Added` · `Changed` · `Deprecated` · `Removed` · `Fixed` · `Security`

## [Unreleased]

### Changed

* Composer 依最低 PHP 8.2.0 解析依賴，CI 另檢查實際平台需求；Dependabot 暫緩需較高 PHP 的 Yii2 測試模組主要版本與 QR Code 6.1 以上更新
* 將最低 PHP 版本與 lock file 統一為 PHP 8.2，並明列必要 extensions
* 公開文件改用通用發布、架構、環境權限與版本支援說明
* Web 測試入口改為僅在明確測試環境啟用
* 操作手冊截圖對人名、專長與 IP 等欄位去識別化
* 安裝說明標明 `composer install --prefer-dist` 與 `composer check-platform-reqs`
* 測試文件 fixture 清單對齊 `tests/run-all.sh`（含 Group／Ballots／ResultsConfig）
* 測試文件：測試伺服器啟動指令加上 `APP_ENV=testing`；說明 `fixture/unload` 會連同相依 Fixture 清除，重設資料改用 `fixture/load`

### Added

* 管理員忘記密碼：登入頁自助重設（須先跑 `docs/migrations/20261021_password_reset.sql`；`users.email`、30 分鐘單次連結、既有 session 作廢；不關閉 TOTP）
* 新裝精靈 `scripts/install.sh`（composer、可寫目錄、`.env`、可選建庫／`init_db.sql`／admin）
* `install.sh` 環境檢查（PHP 硬擋；DB／Web 版本僅警示）、既有 DB／`master.key`／`.env`／admin 沿用
* `scripts/encrypt-config-value.php`、`scripts/rebuild-yii-extensions.php`（正式密文與 extensions.php 修復）
* `scripts/ensure-writable-dirs.sh`；composer post-install 自動建立 `runtime/sessions` 等目錄
* 瀏覽器 E2E 測試 `tests/e2e/`：以 PHP 透過 CDP 操作 Headless Chrome（不需 Node.js），涵蓋全畫面巡檢、對話框、附件、照片、投票流程

### Security

* 測試專用登入路由移出正式程式目錄（`tests/_support/TestLoginController.php`），只由測試設定掛載，且須 Yii 測試模式加上 `APP_ENV=testing`／`test` 才可使用，其他情況回 404
* **升級注意**：正式環境（`APP_ENV=production`）必須為 `YII_ENV=prod`（或未設定）且 `YII_DEBUG=false`，否則系統拒絕啟動並列出原因；升級前請先檢查正式機 `.env`
* 投票頁自訂欄位標題、候選名單內容與紙本選票的中英文資料統一淨化原本直接輸出的 HTML；列印分段表頭也套用淨化，保留既有換行與合法排版
* 重設密碼回應不透露帳號是否存在；token 只存 SHA-256；同帳號／IP 15 分鐘最多 5 次申請
* 升級 `phpoffice/phpspreadsheet` 至 5.10.0（CVE-2026-108357、CVE-2026-108358、CVE-2026-108359、CVE-2026-108360）
* 升級 `symfony/mime`、`symfony/mailer` 至 7.4.22（CVE-2026-45070，郵件參數名稱標頭注入）
* 升級 `symfony/yaml` 至 7.4.23（GHSA-hwfg-qwvh-2wfx，YAML 解析時間指數成長；僅開發相依，經 Codeception 引入）
* 候選人照片與投票附件的檢視、下載、上傳、刪除統一由 `FileLoader::safePath()`／`voteDir()` 解析路徑，限制在該場次目錄內，防止路徑穿越（`candi/view-photo`、`vote/download-file`、`elect/upload-file`、`elect/delete-file`）
* 附件上傳改以副檔名對 MIME 對照表檢查實際內容，偽造副檔名的檔案仍會被擋
* 附件檔名在後台列表與投票說明頁輸出時跳脫 HTML
* 刪除投票時，照片與附件目錄須經 `voteDir()` 驗證才移除

### Fixed

* 開票結果、投票結果、候選名單與選票編輯表格消除重複 HTML 編碼；自訂標題及欄 A 保留經淨化的換行與排版（管理端候選人管理列表的欄 A 一併適用），欄 B–F 維持純文字，既有 entity 不作資料轉換
* 匿名投票未完成即關閉瀏覽器後，殘留的 session 檔會讓同一密碼持續顯示「已在其他裝置登入中」而無法重新登入；改為原裝置超過匿名登入閒置逾時（預設 30 分鐘）即可重新登入，仍在使用中的登入照常阻擋
* 英文介面的紙本選票自訂文字欄位誤讀多一個 E 的欄名而發生錯誤；保留中英並列與原有數字欄位的語言選擇
* Yii log 檢視頁的 UrlModal 資產指向未隨 Git 提供的目錄而發生 500；改為追蹤前端腳本，內容載入沿用目前頁面的協定與主機
* 殘留的 Bootstrap 4 樣式在 Bootstrap 5 下失效：輪次與投票狀態徽章看不見、對話框關閉鈕錯位，核取方塊、輸入群組、間距與邊框也一併改為 Bootstrap 5 寫法
* `app\widgets\Modal::renderCloseButton()` 缺少回傳型別，Yii log 檢視頁發生 fatal error
* 對話框有時被灰色遮罩蓋住、按鈕點了沒反應（OverlayScrollbars 晚於對話框初始化時，`.os-padding` 的堆疊環境讓 backdrop 壓在對話框上）
* Functional Cest 在 `cleanup: false` 下於 `_before` 強制 `haveFixtures`，避免後續 Cest 缺 Group／ResultsConfig／Ballots
* 附件刪除連結誤傳列表序號，刪除一律顯示「找不到該檔案」
* 照片或附件存檔失敗（如目錄無寫入權限）仍顯示上傳成功，照片檔名也照樣寫入資料庫；改為回報失敗原因與檔名
* CSV 與部分 Office 檔被 MIME 反查誤擋；不允許的檔案被前端驗證擋下時沒有任何提示，改由伺服器驗證並顯示原因
* 附件目錄不存在時，投票說明頁發生 `count()` TypeError
* 上傳同名附件會直接覆蓋舊檔且沒有提示；改為擋下並列出檔名，其他檔案照常上傳
* 投票確認頁的已圈選名單、選票內容與開票選票明細的候選人姓名為白字白底看不見（Bootstrap 5 的 `.badge` 預設白字）
* 顯示照片欄位時，沒有照片的候選人出現破圖並產生無效請求
* 開票結果、投票結果與投票確認頁的照片欄位讀取不存在的 `results.photo`，照片從未顯示；改由候選人資料取得
* 開票狀態頁在系統設定未載入或「每列欄數」為 0 時發生除以零錯誤（500）
* 尚未建立候選名單配置時開啟新增／編輯候選人頁面發生 500；改為導向候選設定頁

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
