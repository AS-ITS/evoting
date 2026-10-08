# passwords.voted 欄位同步策略 — 另案評估

> **狀態**：待詳細評估（本輪「本輪已投票」功能不實作 Phase B/C）

## 背景

- UI／篩選／匯出已改以 **ballots**（`voteID` + `round` + `creator`）判定「本輪已投票」。
- `passwords.voted` 為 legacy 欄位，部分流程仍寫入（如 `FormPasswords::setVote()`、`creationPasswd` 設 `'0'`）。
- 登入防重複與實際投票狀態以 **ballots** 為準（`isVoteBallot()`），非 `passwords.voted`。

## 待評估項目

1. **寫入點盤點**：`setVote()`、補登、刪票、輪次切換等是否仍應更新 `passwords.voted`。
2. **讀取點盤點**：除已移除的 `FormPasswords::search()` 外，是否仍有報表／API 依賴該欄位。
3. **一致性策略**：
   - **A. 廢止欄位**：停止寫入，migration 標記 deprecated。
   - **B. 同步寫入**：投票成功時同步 `passwords.voted='1'`（需定義刪票／補登／多輪語意）。
   - **C. 僅維運查詢**：保留欄位但不保證即時一致，文件明示以 ballots 為準。
4. **共用密碼場次**：`passwordVoteID` ≠ `ballotVoteID` 時，`passwords.voted` 無法表達「子場次本輪已投」。
5. **測試與回滾**：若採 B，需補 integration test 與資料修復 script。

## 本輪已確認（Phase A，已實作）

- 列表篩選、設定狀態、CSV 匯出、密碼函：皆以 ballots 本輪已投票為準。
- 共用密碼子場次：開放設定狀態／匯出／密碼函；仍禁生成／刪除。
- **不修改** `setVote()` 與 DB schema。

## 建議下一步（評估完成後）

1. 產出讀寫點完整清單（grep + 手動確認業務語意）。
2. 與維運確認是否有離線報表依賴 `passwords.voted`。
3. 選定 A/B/C 並撰寫 migration／runbook（若需要）。
