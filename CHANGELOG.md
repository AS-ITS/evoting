# 更新日誌

本文件記錄各版本間的變更，格式遵循 [Keep a Changelog](https://keepachangelog.com/zh-TW/1.1.0/)。
本專案遵循 [Semantic Versioning](https://semver.org/lang/zh-TW/)。

類型：`Added` · `Changed` · `Deprecated` · `Removed` · `Fixed` · `Security`

## [Unreleased]

### Security

* 送出或修改選票時，只檢查候選人屬於本場次。竄改送出的候選人 id，可以把票投給同一輪其他組別的候選人，並計入該組得票。現在每位候選人都必須屬於選票的輪次，以及投票者組別或共同投票（N）的問題；只要有一位不符，整張選票不寫入。投票者送出、後台建立選票、後台修改選票都適用
* 表決投票不支援分組、共用投票、女性保留名額、場次碼與「查看投票資訊須驗證」。原本只在前端鎖住這些欄位，現在後端也會拒絕這些組合
* 刪除投票、使用者、投票密碼、選票、候選人、候選人照片、附件，以及建立輪次、切換輪次、管理員登出，原本用 GET 就能執行。Yii 只對 POST 驗證 CSRF token，而 SameSite=Strict 以網域判定是否同站，其他 `*.sinica.edu.tw` 網頁上的連結或圖片就能讓已登入的管理者執行這些操作。現在一律只接受 POST，GET 回 405。各 controller 原本的 `'https' => ['post', 'get']` 並不對應任何 action，已移除
* symfony/mailer 與 symfony/mime 升至 7.4.24，修正 CVE-2026-133751（中間人可繞過 `require_tls`）與 CVE-2026-133755（STARTTLS 之後信任中間人注入的回應）。以 SMTP 寄信時適用；egulias/email-validator 一併升至 4.0.5

### Fixed

* 表決投票同一輪只能收到 1 張選票：每張選票的投票者識別碼都是空字串，第 2 位投票者會撞到 `(voteID, round, party, creator)` 唯一鍵而被導回投票畫面。改為每張選票產生不重複的隨機識別碼，與投票者沒有關聯
* 表決投票的後台選票列表：「建立選票」連結使用未定義的變數，修改頁的投票者下拉選單沒有選項。表決投票沒有投票者身分，因此不提供後台建立選票，列表與修改頁的投票者顯示「—」
* 開票「女性保留名額」：沒有任何女性候選人得票時會出錯；第 2 位起的女性保留當選人誤用前一個迴圈留下的票數，全部拿到相同名次
* 開票時同票超出剩餘當選名額（待當選）的人數扣減遞補名額時，多扣了剩餘的當選名額，遞補名額還可能變成負數，下一位候選人因此被誤判為待遞補（CW），應為遞補（W）或落選（LE）
* 寫入圈選紀錄與開票結果時，沒有檢查每一筆的存檔結果，有一筆失敗時其餘照常寫入。改為任一筆失敗就全部回滾；後台修改選票也包在同一個交易內，失敗時保留原本的圈選
* 從其他場次匯入選票時，若候選人或題目找不到對應，原本整批失敗，畫面只顯示匯入 0 張。現在會說明是哪個場次找不到對應
* 後台建立、修改選票失敗時，畫面沒有任何提示
* 選票的建立者、修改者驗證長度上限是 8，與資料表的 `varchar(50)` 不符
* 設定 `DISABLE_LEGACY_ADMIN_PASSWORD=true` 時，正式環境的啟動檢查會查詢不存在的 `users.username` 欄位而失敗
* 8 處確認提示寫成 `data-bs-confirm`，`yii.js` 不認得，按下就直接執行、不會先問：刪除選票（列表、修改頁）、刪除候選人、依條件刪除候選人、刪除附件、資料表編輯器的刪除（2 處），以及**重啟投票的「確認重啟」**（會刪除全部選票與開票結果）。已改為 `data-confirm`。切換輪次也新增確認提示。另刪除從未使用的舊檢視 `views/ballot/edit.php`（建立、修改選票都使用 `ballot.php`）
* 投票結果「依得票高低排序」時，同票候選人改依候選名單順序，與計票頁一致（原本因 `switch` 少了 `break`，會依姓名筆劃）；「依候選名單排序」原本沒有實作，結果依資料寫入順序顯示，現在依候選名單排序（自動編號時依候選人編號，否則依自訂排序）
* 從已開啟的對話框內再跳出確認提示時（如附件上傳視窗內的刪除），確認提示被下層對話框蓋住、按不到。BootstrapDialog 固定使用 Bootstrap 3 的 z-index 1050，低於 Bootstrap 5 對話框的 1055；現在確認提示與遮罩會疊在所有已開啟的對話框之上

### Changed

* E2E `voting` 組改為實際送出：兩位投票者送出表決選票，竄改送出的他場次候選人必須被拒，並檢查後台選票列表與修改頁；`pages` 組新增表決投票的選票列表
* 新增 E2E `post-only` 組：檢查以 GET 呼叫回 405、確認提示會出現、按「取消」資料不變、按「確定」後以 POST 執行。測試伺服器可用 `TEST_CSRF=1` 開啟 CSRF 驗證，確認瀏覽器送出的 POST 有帶到 token
* 位於表單內的刪除連結（選票、候選人、候選人照片、附件）改以獨立的隱藏表單送出，不會連同所在的表單一起送出並驗證

### Removed

* 記名投票遺留的「選擇記名選票建立者」畫面（`ballot/select-creator`、`ballot/select-clear`）與代碼 `ct.ballots.onJobAry`。介面上沒有任何入口；查詢條件的單位、職稱原由院內人事系統提供，現已為空，且查詢的是管理員帳號而非投票者。匿名投票的建立選票（`ballot/select-passwd` → `ballot/creator`）不受影響

## [1.1.2] - 2026-11-17

### Security

* 投票的「主題色」原本只限制長度，會原樣寫入投票完成頁與投票密碼頁的 `<style>`，投票管理者可藉此對投票者注入樣式或指令碼；改為只接受色碼（`#fff`、`#ffffff`）、顏色名稱與 `rgb()`／`hsl()`，既有資料格式不符時輸出改用預設白色。投票完成頁、清除登入狀態頁與輪次匯入頁寫進 JavaScript 的網址改以 JSON 編碼輸出
* 產生投票密碼時打亂字元表的 `mt_shuffle()` 改用 `random_int()`（白箱掃描 Use of Non Cryptographic Random）。密碼字元本來就以 `random_int()` 選取，強度不變

### Fixed

* 表決投票開啟「略過圈選結果頁」時，投票完成頁會因取用已移除的記名投票元件而顯示 500 錯誤
* SA 資料表編輯器（`SA_DB_TOOLS_WRITABLE=true`，僅非正式環境）新增或修改有外鍵的資料表時，外鍵驗證仍是未完成的除錯程式碼，參數數目不符而發生錯誤；改為實際查詢參照表確認值存在
* `scripts/install.sh` 以 `MASTER_KEY_FILE` 指定非預設路徑時，安裝驗證會通過，但應用程式只讀 `/etc/voting/master.key` 或 `VOTING_MASTER_KEY`，上線後 PHP-FPM 與 cron 讀不到主金鑰；改為一開始就警告，並在「下一步」提醒 PHP-FPM 與 cron 須設定 `VOTING_MASTER_KEY`
* 6 處網址固定為 https（投票完成後的導向、投票者頁首 logo 連結、清除登入狀態頁的組別／標記清單、輪次匯入切換、表格每頁筆數），在 http 環境會失效；改為相對網址。投票編輯頁顯示的短網址與匯出密碼單上的連結是要交給投票者的，仍維持 https 絕對網址

### Changed

* 升級 `phpoffice/phpspreadsheet` 至 5.11.0，新增必要相依 `phpoffice/wmf` 0.2.1。此版加強加密 Xlsx 讀取時的 XXE 防護；本系統只讀取 CSV，不受影響
* 升級 `yiisoft/yii2-bootstrap5` 至 2.0.52，連帶升級 `twbs/bootstrap` 至 5.3.9（tooltip／popover 改為保留 data 屬性文字原樣）
* 升級 `spomky-labs/otphp` 至 11.5.1、`paragonie/constant_time_encoding` 至 3.1.4；Symfony contracts 3.8.1、`symfony/console` 7.4.23 等開發／間接相依隨之更新
* 安裝說明改為 clone `v1.1.2`；SBOM 以 1.1.2 重新產生
* 威脅模型文件：`FileLoader` 路徑限制（C3）標為 v1.1.0 已落地並列出現行檢查；CSP（C2）改寫為現行 enforce 狀態
* 移除程式中已過時的 TODO 註記，改寫為現行行為說明

## [1.1.1] - 2026-11-10

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
* 安裝說明改為 clone `v1.1.1`；SBOM 以 1.1.1 重新產生（依賴未變更）
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

[Unreleased]: https://github.com/AS-ITS/evoting/compare/v1.1.2...HEAD
[1.1.2]: https://github.com/AS-ITS/evoting/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/AS-ITS/evoting/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/AS-ITS/evoting/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/AS-ITS/evoting/releases/tag/v1.0.0
