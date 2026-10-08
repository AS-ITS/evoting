# 測試充足性評估與補強規劃

> 評估日期：2026-07-06（P1-2 / P4 路徑 1 補強：2026-07-06）  
> 評估方式：靜態盤點（`tests/` 目錄、`TEST_COVERAGE_ANALYSIS.md`）+ 實際執行 Codeception

---

## 1. 結論（TL;DR）

| 層級 | 現況 | 是否足夠 |
|------|------|----------|
| **Unit** | ~1,088 測試，全數通過 | ✅ 核心模型與邊界覆蓋**足夠**；可按模組持續補強 |
| **Functional** | ~187 場景；**整包執行全綠** | ✅ run-all 前 reload fixture |
| **Acceptance** | ~120 場景；**整包執行全綠** | ✅ 需 router-test + fixture 前置 |
| **CI / 自動化** | `.github/workflows/tests.yml`（`tests/run-all.sh`） | ✅ acceptance 建議 nightly / 本地 |

**總評**：P0–P4 規劃項目**均已完成**。Unit / Functional / Acceptance 基線可信任（需 fixture 前置）。已知限制見 [TEST_STATUS.md](TEST_STATUS.md)。

---

## 2. 實際執行結果（2026-07-06 更新）

```bash
php vendor/bin/codecept run unit                    # OK (~1088 tests)
php vendor/bin/codecept run functional              # OK (~187 tests)
php vendor/bin/codecept run acceptance              # OK (~120 tests) — 需 fixture + localhost:8080

# 一鍵（unit + functional，含 fixture reload）
tests/run-all.sh

# 含 acceptance（需另開測試伺服器）
RUN_ACCEPTANCE=1 tests/run-all.sh
```

### Phase 0 已修復項目

| 項目 | 修復方式 |
|------|----------|
| ManageController / PermissionDenial log 頁 error | `params.php` 補 `'418' => '管理員修改密碼'`；`log.php` 未知 type fallback |
| CandiControllerCest::testViewPhoto 404 | 測試前建立 `@filePool/candidatePic/AnonPartyTest/test.jpg` |
| Acceptance failures | 環境/fixture 就緒後已全綠 |

### Acceptance 前置條件

1. 測試 DB：`voting_test` 已建立且 `.env` 的 `TEST_DB_*` 正確  
2. **測試 Web Server**（`acceptance.suite.yml` 預設 `http://localhost:8080/`）：
   ```bash
   php -S localhost:8080 -t web web/router-test.php
   ```
3. Fixture 預載（含 Config, Users, Rbac）：
   ```bash
   echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac" --appconfig=config/console-test.php
   ```
4. `VotingFlowCest`（87 案例）、`MultiRoundVoteCest`、`VoteToResultCest` 已穩定；`AnonVoteEditCest` 仍因 PhpBrowser session 限制 skip

---

## 3. 覆蓋矩陣

### 3.1 Controller → Functional Cest

| Controller | Functional Cest | 深度評估 |
|------------|-------------------|----------|
| AuthController | ✅ AuthControllerCest (15) | 佳 |
| ElectController | ✅ ElectControllerCest (24) | 佳 |
| PermissionDenial | ✅ PermissionDenialCest (45) | 佳（RBAC 拒絕） |
| VoteController | ✅ VoteControllerCest | 中 |
| BallotController | ✅ BallotControllerCest | 中 |
| PasswdController | ✅ PasswdControllerCest | 中 |
| CandiController | ✅ CandiControllerCest | 中 |
| QuestionController | ✅ QuestionControllerCest (10) | 中 |
| RoundController | ✅ RoundControllerCest (10) | 佳（含 POST 建立/拒絕） |
| ResultController | ✅ ResultControllerCest (5) | 中 |
| CountController | ✅ CountControllerCest (7) | 佳（CSV N/L/I + getQuestionPassList） |
| ManageController | ✅ ManageControllerCest (6) | 中 |
| SiteController | ✅ SiteControllerCest | 薄 |
| Group / GroupMember / Users | ✅ 各有 Cest | 中 |
| BallotWorkController | ✅ BallotWorkControllerCest | 中 |
| **VoteToResult** | ✅ VoteToResultCest (1) | 佳（P4 路徑 1 完整鏈） |
| MultiRoundVote | ✅ MultiRoundVoteCest (3) | 佳（P4 路徑 2 functional） |
| TestController | — | 可忽略（非正式功能） |

### 3.2 Unit 模組覆蓋（摘要）

| 領域 | 代表測試 | 狀態 |
|------|----------|------|
| RBAC / 群組權限 | RoleBasedAccessTest, GroupVotePermissionTest, RbacRulesTest | ✅ 完整 |
| 投票場次 / 問題 / 輪次 | VotesTest, QuestionsModelTest, RoundTest | ✅ 完整 |
| 選票 / 邊界 | BallotsModelsTest, **BallotsBoundaryTest** | ✅ 已補強 |
| 匿名登入 | FormAnonTest, **FormAnonBoundaryTest** | ✅ 已補強 |
| 密碼產生/加密 | PasswdTest, FormPasswordsTest | ✅ 佳 |
| 候選人 | CandiDataTest, CandiDataBoundaryTest | 中 |
| 計票邏輯 | FormManageCountTest | ✅ 佳（processNormalRule、_countSort、processPassRule） |
| **序位 (rank)** | FormManageCountTest + **FormResultsTest::testCreateResultsWritesRankFromBallotCountSort** | ✅ rank 在 `results` 表，非 ballotsSelected |
| 設定載入 | ConfigLoaderTest | 薄 |
| **問題特殊規則** | — | ➖ 模型已移除；計票改走 processNormalRule |
| **MasterKey / HostControl** | HostControlTest, MasterKeyLoaderTest | ✅ |
| Console（encrypt / migrate-passwords） | ConsoleCommandSmokeTest | ✅ |

### 3.3 Acceptance（E2E）場景

| Cest | 用途 | 狀態 |
|------|------|------|
| AnonVoteCest | 匿名登入 + 投票 | ✅ 登入與圈選送出 |
| NoAuthVoteCest | 表決（無驗證） | ✅ 基本頁面 |
| VotingFlowCest | CSV 驅動完整流程 | ✅ 87 案例 |
| MultiRoundVoteCest | 多輪 + 補登 | ✅ 3 案例 |
| **VoteToResultCest** | P4 路徑 1 瀏覽器投票 + 開票 | ✅ |
| AnonVoteEditCest | 管理者編輯 | ⚠️ skip（PhpBrowser session） |
| ChangePasswordCest | 改密碼 | ✅ |
| VoteCest / AnonVoteResultCest | 首頁/結果 | 部分 skip |

---

## 4. 缺口優先級（依開源 + 院士選舉業務）

### 🔴 P0 — 先讓測試「可信任」 ✅ 已完成（2026-07-06）

| # | 項目 | 狀態 |
|---|------|------|
| P0-1 | **修復 Functional 整包失敗** | ✅ ~187 全綠 |
| P0-2 | **修復 Acceptance 整包失敗** | ✅ ~120 全綠 |
| P0-3 | **更新 TEST_PROGRESS / TEST_STATUS** | ✅ |
| P0-4 | **建立 CI workflow** | ✅ unit + functional |

### 🔴 P1 — 核心投票正確性（Unit + Functional） ✅ 已完成

| # | 模組 | 狀態 | 備註 |
|---|------|------|------|
| P1-1 | ~~QuestionsGroupRule~~ | ➖ | 模型已移除 → `FormManageCountTest` |
| P1-2 | **Ballots / rank** | ✅ | `FormManageCountTest` + `FormResultsTest` 整合；ballotsSelected 無 rank 欄 |
| P1-3 | **FormParties** | ✅ | `FormPartiesTest` |
| P1-4 | **Questions** XSS | ✅ | `QuestionsBoundaryTest` |
| P1-5 | **Votes** type=2 / 日期 | ✅ | `FormVotesTest` |
| P1-6 | **FormAnon** session / SQL | ✅ | `FormAnonBoundaryTest` |
| P1-7 | **FormAnon** 鎖定次數 | ✅ | `LogsTest::testGetPasswordFailWaitWhenLimitReached` |

### 🟡 P2 — 管理流程 Functional 加深 ✅ 已完成

| # | Controller / 功能 | 狀態 |
|---|-------------------|------|
| P2-1 | **CountController** | ✅ index + CSV sort N/L/I + getQuestionPassList |
| P2-2 | **RoundController** | ✅ create 頁 + POST 建立/拒絕 |
| P2-3 | **CandiController** | ✅ csvfile + BOM UTF-8 |
| P2-4 | **BallotController** | ✅ import/print + orderNum unit |
| P2-5 | **PasswdController** | ✅ export + 批次刪除 |
| P2-6 | **ManageController** | ✅ setting/logins + logoutAnon POST |

### 🟡 P3 — 部署 / 安全（開源必備） ✅ 已完成

| # | 項目 | 狀態 |
|---|------|------|
| P3-1 | **HostControlTest** | ✅ |
| P3-2 | **MasterKeyLoaderTest** | ✅ |
| P3-3 | **Web 部署 smoke** | ✅ `tests/deploy-smoke.sh` |

### 🟢 P4 — E2E 完整場景（Acceptance + Functional） ✅ 已完成

對照 `docs/manual/系統使用手冊.html` 案例，兩條自動化路徑：

1. **匿名 + 分組 + 單輪**：`VoteToResultCest`（functional + acceptance）— 登入 → 圈選 → 計票 → 開票  
2. **多輪 + 補登**：`MultiRoundVoteCest`（functional + acceptance）

---

## 5. 分階段執行計畫

### Phase 0：測試基線修復 — **已完成 2026-07-06**

- [x] 修復 log.type 418、log.php fallback
- [x] 修復 CandiControllerCest::testViewPhoto
- [x] Functional / Acceptance 整包全綠
- [x] 撰寫 `tests/run-all.sh`
- [x] 新增 `.github/workflows/tests.yml`
- [x] 更新 `TEST_PROGRESS.md`

### Phase 1：P1 核心 Unit — **已完成 2026-07-06**

| 順序 | 新增/擴充檔案 | 狀態 |
|------|---------------|------|
| 1 | ~~QuestionsGroupRuleTest~~ | ➖ 模型已移除 |
| 2 | rank 整合 | ✅ `FormResultsTest` + `BallotsBoundaryTest` |
| 3 | FormPartiesTest | ✅ |
| 4 | QuestionsBoundaryTest | ✅ |
| 5 | FormVotesTest type=2 | ✅ |
| 6 | FormAnonBoundaryTest | ✅ |

### Phase 2：P2 Functional 加深 — **已完成 2026-07-06**

| 順序 | 檔案 | 狀態 |
|------|------|------|
| 1 | CountControllerCest | ✅ |
| 2 | RoundControllerCest | ✅ |
| 3 | CandiControllerCest | ✅ |
| 4 | BallotControllerCest | ✅ |
| 5 | PasswdControllerCest | ✅ |
| 6 | ManageControllerCest | ✅ |

### Phase 3：P3 部署安全 + CI — **已完成 2026-07-06**

- [x] CI：`tests/run-all.sh`（fixture 預載 + unit + functional）
- [x] HostControlTest、MasterKeyLoaderTest
- [x] deploy-smoke.sh、ConsoleCommandSmokeTest

### Phase 4：P4 Acceptance E2E — **已完成 2026-07-06**

- [x] `AnonVoteCest` 圈選場景
- [x] `MultiRoundVoteCest`（functional + acceptance）
- [x] `VotingFlowCest` CSV 87 案例
- [x] **`VoteToResultCest`**（P4 路徑 1）
- [x] 文件化：acceptance 需雙 terminal（README / run-all.sh）

---

## 6. 建議的 CI 最小門檻（開源後）

```yaml
# 現行 gate
- php vendor/bin/codecept run unit          # 必須 100% pass
- php vendor/bin/codecept run functional    # 必須 100% pass
# acceptance 可 nightly，不擋 PR
```

本地開發最小檢查：

```bash
php vendor/bin/codecept run unit
php vendor/bin/codecept run functional AuthControllerCest PermissionDenialCest VoteToResultCest
```

---

## 7. 與現有文件的關係

| 文件 | 用途 |
|------|------|
| [TEST_COVERAGE_ANALYSIS.md](TEST_COVERAGE_ANALYSIS.md) | BVA/EP 缺口明細 |
| [TEST_PROGRESS.md](TEST_PROGRESS.md) | 歷史里程碑 |
| [TEST_STATUS.md](TEST_STATUS.md) | **現況摘要**（與本文件同步） |
| [README.md](../README.md) | 測試執行命令 |

---

## 8. 後續可選補強（非阻塞）

若資源允許，可再加深：

1. **AnonVoteEditCest** 改 WebDriver 或維持 Functional 替代  
2. **通過名單 view 匯出** HTTP route（`_export-pass.php`）  
3. **VotingFlow** TC01–09（link=不顯示）補 url 步驟案例  
4. CI nightly acceptance job

---

**結論**：P0–P4 規劃已落地；測試基線可信任。後續以回歸維護與可選加深為主。
