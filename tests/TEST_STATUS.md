# 測試狀態

> 最後公開驗證：2026-10-07（開源前全套全綠）
> 規劃文件：見 [TEST_PLAN.md](TEST_PLAN.md)
> 執行方式：見 [README.md](README.md)

## 最近公開驗證

| 套件 | 結果 | Assertions |
|------|------|------------|
| Unit | 1174 通過 | 6659 |
| Functional | 190 通過 | 395 |
| Acceptance | 120 通過 | 254 |
| **合計** | **1484 通過** | **7308** |

GitHub CI 執行 unit + functional。Acceptance 需本機測試伺服器，見 [README.md](README.md)。

## 快速執行

```bash
# unit + functional
tests/run-all.sh

# 含 acceptance（需另開 php -S localhost:8080 -t web web/router-test.php + fixture）
RUN_ACCEPTANCE=1 tests/run-all.sh
```

測試資料庫操作一律加上 `--appconfig=config/console-test.php`。

## 已知限制

- 通過名單 view 匯出（`_export-pass.php`）仍無 HTTP route；邏輯以 `processPassRule` + `getQuestionPassList` 覆蓋。
- VotingFlow 中 link 欄為「不顯示」的 TC01–09 未產生案例。
- Acceptance 與 functional 共用測試資料庫；跑 acceptance 後需重新載入 fixture 再跑 functional。
- `AnonPartyTest` fixture 的分組與選票 party 資料不一致，會讓畫面顯示選票數 0；屬測試資料問題，不是正式投票邏輯。

## 相關文件

- [TEST_PLAN.md](TEST_PLAN.md) — 測試規劃
- [TEST_GUIDE.md](TEST_GUIDE.md) — 測試撰寫指南
- [README.md](README.md) — 執行命令
