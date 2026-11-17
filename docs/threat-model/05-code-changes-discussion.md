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

### C3. `FileLoader` 路徑限制 — **已落地（v1.1.0）**

2026-09-18 實測發現 helper 本身沒有「解析後仍須在 filePool 內」檢查（`$file` 含 `../` 可解析到池外），當時 HTTP 讀檔入口另有 `basename`／目錄限制，測不到對外讀池外，列為防禦縱深。v1.1.0 改為在 helper 層強制：

| 位置 | 檢查 |
|------|------|
| `FileLoader::voteDir()` | 只接受 `candidatePic`／`candidateFile`；voteID 以資料庫值組路徑，解析後必須是該子目錄的直接下一層 |
| `FileLoader::safePath()` | 檔名只取 `basename`（去除 `../`、`..\`、絕對路徑），拒絕 NUL byte；`realpath` 後須為基底目錄內的一般檔案（擋 symlink 出界、同字首兄弟目錄） |
| `FileLoader` 實例方法（`imageData`／`downloadFile`／`removeImage`） | 經 `resolve()`：`realpath` 後須在 filePool 指定子目錄內 |
| HTTP 入口 | `candi/view-photo`、`vote/download-file`、`elect/upload-file`、`elect/delete-file` 皆以 `voteDir()` + `safePath()` 解析；照片存檔另以 `isSafeSegment()` 檢查檔名 |
| 刪除投票 | 照片與附件目錄須經 `voteDir()` 驗證才移除 |

測試：`tests/unit/components/FileLoaderTest.php`（穿越、symlink、同字首、未知場次、未知子目錄、無寫入權限）。

剩餘：`removeImage()` 以 DB 的 `photo` 組路徑，`resolve()` 只限制在 `candidatePic` 內、不限同場次；`photo` 欄位只由上傳流程以亂數檔名寫入，需能直接改 DB 才可能指到他場次照片，屬 DBA 權限（06 A6）。

---

## 仍可討論

### C2. CSP 收斂到不允許 inline

v1.1.0 起預設 `CSP_MODE=enforce`（`compat` 政策，已移除 `'unsafe-eval'`，仍允許 inline script／style）；`CSP_POLICY=strict` 僅供測試環境搭配 E2E 盤點違規。移除 `'unsafe-inline'` 需改寫前端 inline script，工期大，另開前端專案。

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
3. C5 顯式 `false` 硬擋（可選）。C3 已於 v1.1.0 落地。
