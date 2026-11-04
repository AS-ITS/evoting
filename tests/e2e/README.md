# 瀏覽器 E2E 測試

以 Headless Chrome 實際開啟各畫面，檢查 Codeception（PhpBrowser）測不到的部分：JS 行為、對話框、元素是否看得見、動態產生的內容。

- **不需要 Node.js 或其他套件**：以 PHP 直接透過 Chrome DevTools Protocol（CDP）控制 Chrome（`lib/Cdp.php`）。
- **只需要**：PHP 8.2 以上、Chrome（或 Edge／Chromium）、已載入 fixture 的測試伺服器。

## 執行

```bash
# 1. 載入 fixture（測試資料庫）
echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac,Group,GroupMember,Ballots,BallotsSelected,ResultsConfig" --appconfig=config/console-test.php

# 2. 啟動測試伺服器（APP_ENV 須為 testing，否則測試入口回 404）
APP_ENV=testing php -S localhost:8080 -t web web/router-test.php

# 3. 執行（另開終端機）；會自動啟動 Headless Chrome
php tests/e2e/run.php
```

伺服器與 Chrome 不在同一台時（例如伺服器在 dev 主機、Chrome 在本機），以 SSH tunnel 轉接：

```bash
ssh -L 18080:localhost:8080 <dev 主機> 'cd <專案目錄> && APP_ENV=testing php -S localhost:8080 -t web web/router-test.php'
php tests/e2e/run.php --base=http://localhost:18080
```

### 參數

| 參數 | 環境變數 | 預設 | 說明 |
|---|---|---|---|
| `--base` | `E2E_BASE_URL` | `http://localhost:8080` | 受測網站（測試入口） |
| `--cdp` | `E2E_CDP_URL` | `http://127.0.0.1:9222` | 已啟動的 Chrome 遠端除錯位址；連不上時自動啟動 Chrome |
| `--chrome` | `E2E_CHROME` | 自動偵測 | Chrome／Edge 執行檔路徑 |
| `--suite` | | 全部 | 只跑指定組別，逗號分隔 |

結束代碼：全部通過為 `0`，有失敗為 `1`，環境問題為 `2`。截圖與 `results.json` 輸出至 `tests/_output/e2e/`。

## 測試組別

| 組別 | 內容 |
|---|---|
| `pages` | 全畫面巡檢（投票者 6 頁、管理介面 56 頁），每頁檢查：HTTP 狀態、未被導向登入、無 PHP 錯誤訊息、無未捕捉的 JS 例外、無 Bootstrap 4 殘留 class、徽章看得見（非白字透明底）、無破圖，並截圖 |
| `header` | 頁首輪次／投票狀態徽章、捲動後的投票名稱徽章 |
| `modals` | 對話框 × 位置、Esc／點背景不關閉（static）、× 可關閉；重新載入 8 次確認遮罩不會蓋住對話框；新增群組成員對話框 |
| `attachments` | 附件上傳（多檔、中文檔名、同名、不允許格式、偽造副檔名、數量上限）、投票說明頁下載內容、路徑穿越網址回 404、刪除 |
| `photos` | 啟用照片欄位、上傳、預覽、後台與投票說明頁顯示、無照片時不破圖、刪除 |
| `voting` | 建立候選設定與候選人後，投票者開啟投票說明與投票畫面、關閉圈選須知、勾選、下一步到確認頁並確認已圈選姓名看得見（不送出選票） |
| `layout` | 投票密碼頁密碼欄與眼睛按鈕、候選設定頁標題徽章、「自訂規則」核取方塊 |

測試會在測試資料庫與 FilePool 建立資料（附件、照片、候選設定與候選人），結束前會刪除附件、照片與測試候選人並還原候選設定；建立的候選設定（`NoAuthVoteTest`）會保留，重新載入 fixture 即可還原。

## 新增畫面

在 `suites/pages.php` 的清單加一行即可套用全部共用檢查。只放 GET 時不會變更資料的畫面（刪除、重設、匯出、切換等動作不要放）。

## 注意事項

- 以測試專用路由 `/index-test.php/test/login?user=<cn>` 登入，僅測試環境可用。
- 點擊對話框內的按鈕前，先用 `Browser::openModal()` 等淡入動畫結束，否則結果不穩定。
- 只有一、兩個字元的文字檔會被偵測為 `application/octet-stream`，測試檔請用正常長度的內容。
