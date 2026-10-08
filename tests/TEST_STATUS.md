# 測試狀態總結

> 最後更新：2026-10-07 16:11（開源前全套全綠）  
> 詳細規劃與優先級：見 [TEST_PLAN.md](TEST_PLAN.md)  
> 里程碑紀錄：見 [TEST_PROGRESS.md](TEST_PROGRESS.md)

---

## 開源前重跑（2026-10-07）

紀錄：`tests/_output/runs/20261007-1541/`

| 套件 | 結果 | Assertions | 時間 |
|------|------|------------|------|
| Unit | **1174 全綠** | 6659 | 22:12 |
| Functional | **190 全綠** | 395 | 00:18 |
| Acceptance | **120 全綠** | 254 | 01:21 |
| **合計** | **1484 全綠** | **7308** | — |

同次檢查已同步 `composer.lock` content hash，`composer validate --strict`
不再回報 lock 不同步；乾跑安裝（忽略本機缺少的 `ext-gd`）無套件異動。

首次 Unit 執行因 `scripts/open-source-prep.sh` 清掉
`runtime/sessions/test`，造成 1164 個同源環境錯誤。已修正腳本於清理後
重建必要 runtime 目錄，成功重跑；失敗與成功報告均保留：

- `unit.{log,html,xml}`：首次環境失敗
- `unit-retry.{log,html,xml}`：修復後全綠
- `functional.{log,html,xml}`
- `acceptance.{log,html,xml}`

注意：開源清理現在會在 runtime 檔案因 owner／權限無法刪除時正確失敗，
不再吞掉錯誤；本機 `runtime/cache` 尚有 PHP-FPM 所有的檔案，發布前須由
有權限者清空。測試使用 `.env` 指定的 `TEST_DB_NAME=evoting`。

---

## 本次跑測紀錄（2026-09-24，納管開發環境）

乾淨重跑產物：`tests/_output/runs/20260924-0916/`  
對照（未清 logs）：`tests/_output/runs/20260924-0902/`

| 套件 | 09:16 乾淨重跑 | 09:02 | 備註 |
|------|----------------|-------|------|
| Unit | 1174 / **15 errors** | 同 15 | FK；`BrandingTest` 4/4 |
| Functional | 190 / **12 errors** | 13 | 少了 config null；setting / delete-all 過 |
| Acceptance | **120 全綠** | 59 fail | 清 `logs` 後 429 消失 |

**09:53** 針對失敗修 fixture／測試後抽測全綠。  
**10:03 全套重跑全綠**：unit 1174、functional 190、acceptance 120。產物 `tests/_output/runs/20260924-1003/`。

前置：fixture 加 `evoting-test`；清 `logs`/`logins`/`results*`。與 `siteTitle`／死表清理無關。2026-07-09 基線見下方。

---

## 快速執行

```bash
# unit + functional
tests/run-all.sh

# 含 acceptance（需另開 php -S localhost:8080 -t web web/router-test.php + fixture）
RUN_ACCEPTANCE=1 tests/run-all.sh
```

---

## 整體結果（2026-07-09）

| 套件 | 狀態 | 備註 |
|------|------|------|
| Unit | ✅ 全綠 | ~1,090+（含 `ProductionConsoleGuardTest`） |
| Functional | ✅ 全綠 | ~187（run-all 前 reload fixture） |
| Acceptance | ✅ 全綠 | ~120（需 router-test + fixture 前置） |
| CI | ✅ `tests/run-all.sh` | fixture 預載 + unit + functional |

---

## P0–P3 進度摘要

| 優先級 | 狀態 | 說明 |
|--------|------|------|
| **P0** | ✅ 完成 | 基線修復、run-all.sh、CI、文件更新 |
| **P1** | ✅ 完成 | rank 整合（FormResultsTest + BallotsBoundaryTest） |
| **P2** | ✅ 完成 | CSV sort N/L/I、processPassRule、getQuestionPassList、Round create POST |
| **P3** | ✅ 完成 | deploy-smoke.sh + Console/HostControl |
| **P4** | ✅ 完成 | VoteToResult（路徑1）、MultiRound（路徑2）、VotingFlow CSV 87 案例 |

---

## P0 明細

| # | 項目 | 狀態 |
|---|------|------|
| P0-1 | Functional 整包修復 | ✅ |
| P0-2 | Acceptance 整包修復 | ✅ |
| P0-3 | TEST_PROGRESS / TEST_STATUS 更新 | ✅ |
| P0-4 | CI workflow | ✅ `tests/run-all.sh`（fixture 預載） |

---

## P1 明細

| # | 項目 | 狀態 | 備註 |
|---|------|------|------|
| P1-1 | QuestionsGroupRule | ➖ | 模型已移除；`FormManageCountTest` 涵蓋 |
| P1-2 | Ballots rank / 特殊規則 | ✅ | `FormManageCountTest` + `FormResultsTest::testCreateResultsWritesRankFromBallotCountSort`；ballotsSelected 無 rank 欄 |
| P1-3 | FormParties 交叉驗證 | ✅ | `FormPartiesTest` |
| P1-4 | Questions XSS | ✅ | `QuestionsBoundaryTest` |
| P1-5 | Votes type=2 / 日期邊界 | ✅ | `VotesTest` + `FormVotesTest::testCreateScenarioRejectsTypeVoter` |
| P1-6 | FormAnon session / SQL | ✅ | `FormAnonBoundaryTest` |
| P1-7 | FormAnon 鎖定次數 | ✅ | `LogsTest::testGetPasswordFailWaitWhenLimitReached` |

---

## P2 明細

| # | 項目 | 狀態 |
|---|------|------|
| P2-1 | CountController 加深 | ✅ index + CSV N/L/I + `getQuestionPassList` 整合 |
| P2-2 | RoundController 多輪 | ✅ create 頁 + max 輪有票建立 / 無票拒絕 |
| P2-3 | CandiController CSV | ✅ csvfile 頁 + `FormCsvFile::prepareCsvPath` BOM |
| P2-4 | BallotController 匯入 | ✅ `FormBallotsTest::testImportBallotsMapsCandidatesByOrderNum` |
| P2-5 | PasswdController | ✅ export + `testDeleteAllByMark` |
| P2-6 | ManageController | ✅ setting/logins + `testLogoutAnonPost` |

---

## P3 明細

| # | 項目 | 狀態 |
|---|------|------|
| P3-1 | HostControlTest | ✅ `tests/unit/components/HostControlTest.php` |
| P3-2 | MasterKeyLoaderTest | ✅ |
| P3-2b | Console smoke | ✅ `ConsoleCommandSmokeTest`（encrypt/test、encrypt/text、migrate-passwords/verify） |
| P3-3 | 部署 smoke | ✅ `tests/deploy-smoke.sh` + Auth/Site Cest |

---

## P4 明細

| # | 項目 | 狀態 |
|---|------|------|
| P4-1 | 路徑 1：匿名+分組+單輪 | ✅ `VoteToResultCest`（functional + acceptance） |
| P4-2 | 路徑 2：多輪+補登 | ✅ `MultiRoundVoteCest`（functional + acceptance） |
| P4-3 | VotingFlow CSV | ✅ 87 案例 |

---

## 已知限制

- 通過名單 **view 匯出**（`_export-pass.php`）仍無 HTTP route；邏輯以 `processPassRule` + `getQuestionPassList` 覆蓋
- VotingFlow **link 欄為「不顯示」** 的 TC01–09 未產生案例（僅 url 或 link 有步驟者執行）
- acceptance 與 functional 共用 `voting_test`；跑 acceptance 後需 reload fixture 再跑 functional

---

## 相關文件

- [TEST_PLAN.md](TEST_PLAN.md) — 缺口規劃與分階段計畫
- [TEST_PROGRESS.md](TEST_PROGRESS.md) — 歷史里程碑
- [README.md](../README.md) — 執行命令
