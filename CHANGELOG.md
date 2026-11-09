# 更新日誌

本文件記錄各版本間的變更，格式遵循 [Keep a Changelog](https://keepachangelog.com/zh-TW/1.1.0/)。
本專案遵循 [Semantic Versioning](https://semver.org/lang/zh-TW/)。

類型：`Added` · `Changed` · `Deprecated` · `Removed` · `Fixed` · `Security`

## [Unreleased]

### Fixed

* 開發／測試環境的 `DB_PASSWORD`、`TEST_DB_PASSWORD`、`SMTP_PASSWORD` 若是純英數明文，會被誤判為密文而解密失敗，`admin/create` 與 `config/validate` 都會出錯；改為解密不成功時在非正式環境沿用明文，正式環境仍必須是目前主金鑰加密的值
* `php yii` 一律以 exit code 0 結束，`config/validate` 等指令失敗時 shell 腳本收不到；改為回傳指令的 exit code（未知指令回傳 1；載入設定失敗，例如正式環境密碼無法解密，與驗證失敗同樣顯示 ❌ 並回傳 65）
* `scripts/install.sh` 非互動安裝時，先 `export` 的明文 `DB_PASSWORD` 會蓋過 `.env` 的加密值，讓正式模式的 `config/validate` 誤判失敗；執行 yii 指令時改以 `.env` 為準。另偵測 `php-fpm8.5`
* `scripts/ubuntu-upgrade-test.sh` 的 `encrypt/test` 補上必要的鍵名參數
* `install.sh` 結尾的「下一步」不分模式都說主金鑰在 `/etc/voting/master.key`；改依實際來源（金鑰檔、`VOTING_MASTER_KEY` 或開發模式的 `.env`）說明
* `install.sh` 非 root 安裝時，即使可寫目錄已屬於 PHP 執行身分仍提示 `sudo chown`；改為只列出擁有者不符的目錄

### Security

* `install.sh` 的 admin 密碼與資料庫密碼不再出現在 `ps` 看得到的命令列：`admin/create` 新增 `--password-stdin`，`scripts/encrypt-config-value.php` 接受 `-` 從 stdin 讀取，mysql（含安裝開頭的資料庫存在檢查）改以 0600 暫存設定檔傳密碼，建立資料庫帳號的 SQL 經 stdin 傳入並跳脫引號
* 測試資料庫初始化腳本 `tests/_data/init_test_db.sh` 同樣改以 0600 暫存設定檔傳密碼，測試使用者密碼改由 `TEST_DB_PASSWORD` 或提示輸入（不再寫死、結尾不再印出），並移除會以正式設定執行的 `php yii migrate` 步驟、新增 `DB_PORT`；`tests/README_TEST_DATABASE.md` 等測試文件同步更新，`fixture/load` 範例補上 `--appconfig=config/console-test.php`

### Changed

* `config/validate` 在設定檢查通過後實際連線資料庫，帳號密碼錯誤即回報失敗（`--db=0` 可略過）。開發模式下 `DB_PASSWORD` 形式像密文卻無法以目前主金鑰解密時（例如別把金鑰加密的值），會一併提示
* 測試說明：README 功能測試數量改為概數；SETUP.md 測試資料庫設定補上 `TEST_DB_PORT`

* PHP 8.5 的 CLI 忽略 `register_argc_argv`，Codeception 5.1 因檢查此設定而拒絕執行；新增 `composer test`，`tests/run-all.sh` 也一併加上 `-d register_argc_argv=On`（Codeception 5.3.4 起已修正，但升級需先處理 `yii2-dynamicform` 對 `symfony/css-selector` 4.x 的限制）

## [1.1.0] - 2026-11-09

### Changed

* Composer 依最低 PHP 8.2.0 解析依賴，CI 另檢查實際平台需求；Dependabot 暫緩需較高 PHP 的 Yii2 測試模組主要版本與 QR Code 6.1 以上更新
* 將最低 PHP 版本與 lock file 統一為 PHP 8.2，並明列必要 extensions
* 公開文件改用通用發布、架構、環境權限與版本支援說明
* Web 測試入口改為僅在明確測試環境啟用
* 操作手冊截圖對人名、專長與 IP 等欄位去識別化
* 安裝說明標明 `composer install --prefer-dist` 與 `composer check-platform-reqs`
* 測試文件 fixture 清單對齊 `tests/run-all.sh`（含 Group／Ballots／ResultsConfig）
* 測試文件：測試伺服器啟動指令加上 `APP_ENV=testing`；說明 `fixture/unload` 會連同相依 Fixture 清除，重設資料改用 `fixture/load`
* 安裝說明改為 clone 正式版 tag（`v1.1.0` 以後含 `scripts/install.sh`），`main` 為開發中版本
* 釋出前檢查 `scripts/open-source-prep.sh` 不再把檢查 Webix 已移除的測試本身誤判為 Webix 殘留
* `scripts/install.sh` 缺少必要 PHP 擴展時提示套件安裝指令，並另外檢查 php-fpm 是否缺少 gd 等擴展
* SBOM 依目前 lock 檔重新產生；`docs/generate_sbom.php` 改為必須指定版本號，並固定輸出到 `docs/`

### Added

* `CSP_POLICY=strict`（不允許 inline 與 eval，僅供測試環境搭配 report-only 使用）；瀏覽器 E2E 收集 CSP 違規並輸出 `tests/_output/e2e/csp-violations.json`，作為改用強制 CSP 前的盤點。測試設定改為與正式設定相同送出安全標頭
* 管理員忘記密碼：登入頁自助重設（須先跑 `docs/migrations/20261021_password_reset.sql`；`users.email`、30 分鐘單次連結、既有 session 作廢；不關閉 TOTP）
* 新裝精靈 `scripts/install.sh`（composer、可寫目錄、`.env`、可選建庫／`init_db.sql`／admin）
* `install.sh` 環境檢查（PHP 硬擋；DB／Web 版本僅警示）、既有 DB／`master.key`／`.env`／admin 沿用
* `scripts/encrypt-config-value.php`、`scripts/rebuild-yii-extensions.php`（正式密文與 extensions.php 修復）
* `scripts/ensure-writable-dirs.sh`；composer post-install 自動建立 `runtime/sessions` 等目錄
* 瀏覽器 E2E 測試 `tests/e2e/`：以 PHP 透過 CDP 操作 Headless Chrome（不需 Node.js），涵蓋全畫面巡檢、對話框、附件、照片、投票流程
* E2E 新增 `groups` 組（群組成員新增、重複新增、修改、刪除）；`anon` 組加上短網址、已投票密碼重新登入的已投票頁、清除登入狀態後重新登入

### Security

* Content-Security-Policy 預設改為強制套用（`CSP_MODE=enforce`），`compat` 政策移除 `'unsafe-eval'`（E2E 盤點與實際載入的前端套件均未使用 eval），仍允許 inline script／style。**升級注意**：若正式環境有畫面功能被擋，可暫於 `.env` 設 `CSP_MODE=report-only` 還原，不需重新部署
* HSTS 改由安全標頭元件於正式環境的每個回應送出，預設 `max-age=31536000`，不再帶難以撤回的 `preload`；`includeSubDomains` 需確認所有子網域皆為 HTTPS 後以 `HSTS_INCLUDE_SUBDOMAINS=true` 開啟。原本僅在部分存檔動作才送出
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
* 移除 jQuery UI 1.12.1（`yiisoft/yii2-jui`、`bower-asset/jquery-ui`；CVE-2021-41182、CVE-2021-41183、CVE-2021-41184）。原本只有操作紀錄的日期篩選使用，改為瀏覽器內建日期欄位，格式同為 `yyyy-mm-dd`，起日不可晚於迄日
* 移除未使用的 `Config::getProxyIp()` 與 `PROXY_IP_*` 常數
* 移除 `AdminLTE3_ComVer/js/main.js` 未使用的 `changeBlock()`（以 AJAX 回應直接寫入 DOM）；E2E 工具的 CDP 連線失敗訊息不再帶出對端回應內容（Checkmarx 白箱掃描嚴重／高風險各 1 項）

### Fixed

* 開票結果、投票結果、候選名單與選票編輯表格消除重複 HTML 編碼；自訂標題及欄 A 保留經淨化的換行與排版（管理端候選人管理列表的欄 A 一併適用），欄 B–F 維持純文字，既有 entity 不作資料轉換
* 操作紀錄日期搜尋：迄日改為包含當天整天（原本只算到當天 00:00，當天的紀錄查不到）；查詢後保留輸入的日期；格式不符的日期不參與查詢
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

[Unreleased]: https://github.com/AS-ITS/evoting/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/AS-ITS/evoting/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/AS-ITS/evoting/releases/tag/v1.0.0
