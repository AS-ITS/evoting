# 程式異動討論清單

> 上層：[README.md](README.md)　決議與已落地項目並陳；未勾選者不要當已改行為。

原則：匿名模型維持「人↔票不可得、密碼↔票保留」。**不**為 unlinkability 拿掉 `ballots.creator`。

---

## 已決議／已落地

### C1. 開票改當選加 Sensitive Reauth — **不做**

選務：開票結果需要人為判斷（門檻、排序、手動標記當選），屬有權者（`voteResult`）的正常作業，不應再擋一層管理員密碼。

STRIDE T4 改列為「授權人員可改當選」殘餘，用權限與 logs 約束，不加 Reauth。

### C5. `.env` 未寫 `SENSITIVE_REAUTH=true` — **現況正確，不必補寫**

`SensitiveReauth::isRequired()`：

```php
return filter_var(getenv('SENSITIVE_REAUTH') ?: 'true', FILTER_VALIDATE_BOOLEAN);
```

| `.env` | `getenv` | 二次驗證 |
|--------|----------|----------|
| 未設定／註解掉 | `false`（PHP 未設） | **開**（`?: 'true'`） |
| 空字串 | `''`（falsy） | **開** |
| `true` / `1` / `yes` / `on` | 該字串 | 開 |
| `false` / `0` / `no` / `off` | 該字串 | **關** |

`.env.example` 把該行註解掉，就是「靠程式預設開啟」。未寫 `=true` 仍會要二次驗證，與觀察一致。

仍可討論（非必須）：正式環境若有人**顯式**寫 `SENSITIVE_REAUTH=false`，`ConfigValidator` 是否要擋（目前只擋 `SA_DB_TOOLS`）。測試環境維持可關。

### C6. 匿名負向測試 — **已寫（2026-09-18）**

目標：鎖住「系統不出現人↔票／人↔密碼」，**不是**測密碼↔票（那是設計）。

| 指向 | 測什麼 | 測試 |
|------|--------|------|
| C6.1 schema | `passwords`、`ballots`、`logins`、`results` 無姓名類欄 | `AnonymityNegativeTest::testSchemaHasNoVoterIdentityColumns` |
| C6.2 登入 log | context 無明文；`LogSanitizer` redacted | `testLoginLogContextOmitsPlaintextPassword`、`testLogsAddRedactsPasswordKeys` |
| C6.3 列表遮罩 | 未解鎖顯示 `密碼#sn` | `testPasswordGridMasksWhenLocked` |
| C6.4 公開結果 | HTML 不含投票密碼、不含 `ballots.creator` | `testPublicResultViewOmitsCredentials`；`BallotControllerCest::testPublicResultOmitsCredentials` |
| C6.5 投票者顯示 | 匿名列表不把 `Users.name` 當投票者 | `testAnonBallotVoterLabelIsPasswordMaskNotUserName` |
| C6.6 匯出門檻 | 不帶管理員密碼不得取得明文 | `BallotControllerCest::testExportRequiresReauth` |
| C6.7 回歸 | 記名已移除 | `testVoteTypeHasNoNamedVoting` |

**不要測**：拿掉 `creator`、管理員解鎖後看見密碼、封存單印密碼。那些不是負向保證。

### C7. 失敗登入是否記錄「打錯的明文密碼」— **不建議記明文**

現況 501：`voteID`、IP、時間、`errors`（錯誤訊息），**無明文**（2026-07 起；`LogSanitizer` 會擋 `password`／`passwd` 鍵）。手冊也寫比對請走密碼匯出（有 audit）。

打錯的字串常常是真密碼差一個字，寫進 `logs` 等於多一份憑證庫，且 logs 備份／查 log 權限比「解鎖明文」寬。與 A2 職務分離也不合：發放名冊若再疊上失敗明文，分析價值被風險吃掉。

若分析不夠，應加**原因分類**而非明文，例如：`unknown`／`disabled`（對到 `sn` 但未啟用，只記編號）／`concurrent_session`／`bad_session_code`／輸入長度。**暫不實作。**

### 代投可查（A3，2026-09-18）

後台依密碼編號代填為合法作業，必須可與本人投區分：

- 列表「代為輸入」欄 + 篩選（`FormBallots::getBallotList` 精確比對 `isAdminAdd`）
- 匯出含「代為輸入」
- 選票匯入＝兩場合併計票，保留來源標記，不是代投入口

---

## 仍可討論

### C2. CSP 從 report-only 收斂

工期大，另開前端專案。本輪不動。

### C3. `FileLoader` 路徑限制 — **已測（2026-09-18）**

隔離目錄實測（與 `realpath(filePool + dir + file)` 相同拼接；`imageData` 真的 `file_get_contents`）：

| 情境 | 結果 |
|------|------|
| 正常 `fileDir` + 檔名 | 留在 pool 內 |
| `$file` 含 `../` 指向 pool 外 canary | **helper 解析到池外**；`imageData` 在層數對得上時會讀到 canary |
| `VoteController::actionDownloadFile`：`scandir` 索引 → `basename` 再交給 helper | **無法**用參數讀出池外 |
| `CandiController::actionViewPhoto`：`basename` + 路徑必須在該場次 `candidatePic` 下 | 池外 **DENY**（且不走 FileLoader） |
| 上傳照片檔名 | `generateRandomString(6)+副檔名`，不是使用者路徑 |

結論：helper **本身**沒有「解析後仍須在 filePool 內」檢查，惡意 `$file`／`$fileDir` 可以出池。**現有 HTTP 讀檔入口有另做消毒，測不到對外讀池外。** `imageData()` 目前無 view 呼叫。`removeImage()` 用 DB 的 `photo`；若欄位被寫成 `../...` 才可能出池（正常上傳不會）。

程式是否加 prefix check：防禦縱深，**非選舉前必須**。要做再另開。

### C4. `passwords.mark` 誤當身分欄

程序優先，見 [06-election-decisions.md](06-election-decisions.md) A2。

---

## 不建議做

### X1. 刪除或雜湊 `ballots.creator`

否決（一密一票／封存／匿名定義）。

### X2. 投票後立刻抹 `ballots.ip`

暫緩。見 06 A3-4／指紋。

### X3. 強制所有管理員 TOTP

政策可要求 sa／va，不必先改 code。

### X4. 拿掉表決類型

產品決定，見 06 A5。

### X5. 改成 Helios／盲簽

範圍外。

---

## 已另案

- `passwords.voted`：[PASSWORDS_VOTED_SYNC_EVALUATION.md](../PASSWORDS_VOTED_SYNC_EVALUATION.md)
- 日誌保留草案：[LOG_RETENTION_POLICY.md](../manual/LOG_RETENTION_POLICY.md)

---

## 建議討論順序（更新）

1. A4 部署檢查（其餘 A 已接受，見 06）。
2. C7 失敗登入原因分類（若現有 501 不夠分析；**不記明文**）。
3. C5 顯式 `false` 硬擋／C3 prefix（可選）。
