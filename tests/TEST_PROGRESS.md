# 測試進度報告

最後更新：2026-07-06

## 📊 整體統計

| 種類 | 測試數 | 狀態 |
|------|------|------|
| **單元測試 (Unit)** | ~1,075+ | ✅ 全數通過 |
| **功能測試 (Functional)** | ~185 | ✅ 全數通過 |
| **驗收測試 (Acceptance)** | ~119 | ✅ 全數通過（需 router-test + fixture） |
| **CI** | unit + functional | ✅ |
| **總計** | **~1,365+** | 略過 1（BallotsModelsTest::testBallotsSelectedCandiDataRelation） |

> **執行方式**：`tests/run-all.sh`；P0–P3 狀態見 [TEST_STATUS.md](TEST_STATUS.md)

## 🆕 2026-07-06 P2/P4 仍待補收尾

| 項目 | 產出 |
|------|------|
| P2 通過名單 | `CountControllerCest::testGetQuestionPassListIntegration` |
| P2 Round POST | `testCreateRoundWhenMaxRoundHasBallots` / `testCreateRoundFailsWhenMaxRoundNotVoted` |
| P4 VotingFlow | URL 絕對路徑、`active=2` 自動開票、CSV **87** 案例全綠 |

## 🆕 2026-07-06 P2/P4 加深（第三輪）

| 項目 | 產出 |
|------|------|
| P2 通過規則 | `FormManageCountTest`：`processPassRule` 三規則（1/2、2/3、名譽院士） |
| P2 Count 匯出 | `CountControllerCest`：sort N/L/I data provider |
| P4 短網址 | `VoteControllerCest::testShortUrlRedirectsToVoteDetail`（動態讀 fixture shortUrl） |
| P4 VotingFlow | `VotingFlowCest`：`index-test.php/test1` 取代 `/index.php/test1` |
| P4 補登 E2E | `MultiRoundVoteCest::testBackfillRound1Vote`（acceptance） |

| 優先級 | 完成度 |
|--------|--------|
| P2 | ✅ ~95% |
| P4 | ✅ 加深 |

## 🆕 2026-07-06 P1–P3 第二輪補強

| 項目 | 產出 |
|------|------|
| P2 BOM | `FormCsvFile::prepareCsvPath` + unit 測試 |
| P2 orderNum | `FormBallotsTest::testImportBallotsMapsCandidatesByOrderNum` |
| P2 批次刪密碼 | `PasswdControllerCest::testDeleteAllByMark` |
| P2 logoutAnon | `ManageControllerCest::testLogoutAnonPost` |
| P1 鎖定 | `LogsTest::testGetPasswordFailWaitWhenLimitReached` + `Logs.php` array_pop 修復 |
| P1 type=2 | `FormVotes` in 驗證 + `FormVotesTest::testCreateScenarioRejectsTypeVoter` |
| P3 deploy | `tests/deploy-smoke.sh` |
| run-all | unit 後 reload Ballots fixture |

| 優先級 | 完成度 |
|--------|--------|
| P0 | ✅ |
| P1 | ✅ ~95% |
| P2 | ✅ ~90% |
| P3 | ✅ |
| P4 | ✅ 最小路徑 | `MultiRoundVoteCest` functional(3) + acceptance(2) |

## 🆕 2026-07-06 P4 多輪 E2E

| 檔案 | 說明 |
|------|------|
| `tests/functional/MultiRoundVoteCest.php` | 同密碼雙輪投票、切換輪次、補登後台加票 |
| `tests/acceptance/MultiRoundVoteCest.php` | 瀏覽器：第1輪投票 → 切第2輪 → 再投 |

## 🆕 2026-07-06 P0–P3 補強（第一輪）

| 優先級 | 完成度 | 摘要 |
|--------|--------|------|
| P0 | ✅ | 基線修復、CI、run-all.sh |
| P1 | ⚠️ ~85% | FormAnon session/SQL；BoundaryTest 涵蓋 XSS/Parties |
| P2 | ⚠️ ~55% | Count CSV 匯出 + Controller smoke |
| P3 | ⚠️ ~85% | Console smoke + ConfigManager encrypt 修復 |
| P4 | ❌ | E2E 待辦 |

## 📈 測試覆蓋進度

### 第一階段：高優先級測試（已完成 ✅）

**完成日期**：2025-12-XX（前次工作階段）

#### 新增測試檔案（54 個測試）

1. **RoleBasedAccessTest.php** - 12 tests, 65 assertions
   - SA, VA, GA, GM 角色權限測試
   - 多角色使用者測試
   - 角色切換功能測試

2. **components/AnonTest.php** - 11 tests, 44 assertions
   - Anon 元件結構測試
   - afterLogin/afterLogout 事件測試
   - Logins 記錄管理測試
   - 多使用者同時登入測試
   - Session ID 變更處理測試

3. **models/LoginsTest.php** - 19 tests, 59 assertions
   - Logins 模型結構測試
   - 必填欄位驗證
   - 唯一性約束測試
   - CRUD 操作測試
   - 批次查詢測試

4. **models/UserIdentityTest.php** - 24 tests, 52 assertions
   - UserIdentity 基類測試
   - Session-based storage 測試
   - 角色管理功能測試
   - Auth data 存取測試
   - 多實例隔離測試

**成果**：
- ✅ 所有 54 個新測試 100% 通過
- ✅ RBAC 認證與授權機制完整測試
- ✅ 匿名投票核心元件測試
- ✅ 基礎 Identity 類別測試

### 第二階段：中優先級測試（已完成 ✅）

**完成日期**：2026-01-05（本次工作階段）

#### 新增測試檔案（68 個測試）

1. **models/VotesTest.php** - 33 tests, 157 assertions
   - 投票模型完整測試
   - 常數定義測試（TYPE_NO_AUTH, TYPE_ANON, STATUS_*）
   - 驗證規則測試（必填欄位、唯一性、字串長度）
   - 關聯查詢測試（Parties, Rounds, Questions）
   - 業務邏輯測試（checkVoteReady, checkVoteExpired, getVoteName）
   - 輔助方法測試（getRocDate, getFinishPageUrl, 徽章生成）

2. **models/BallotsModelsTest.php** - 15 tests, 67 assertions
   - Ballots 模型基礎測試
   - BallotsSelected 模型測試
   - 唯一性約束測試
   - 查詢方法測試（getBallotList, getVoteBallot, getBallotId）
   - 批次刪除交易測試（deleteAllBallot）
   - 關聯測試（CandiData, Questions）

3. **models/QuestionsModelTest.php** - 20 tests, 184 assertions
   - Questions 模型基礎測試
   - 驗證規則測試（必填欄位、數值最小值、字串長度）
   - HTML 淨化過濾器測試
   - 查詢方法測試（getQuestionInfo, getQuestionsInfo, getPartyQuestionsInfo）
   - 搜尋功能測試（search with filters）
   - 輔助方法測試（getQuestionsIdList, getQuestionNumBallots）

**成果**：
- ✅ 所有 68 個新測試 100% 通過
- ✅ 核心業務模型完整測試覆蓋
- ✅ 驗證、查詢、關聯、業務邏輯全面測試

### 第三階段：低優先級測試（已完成 ✅）

**完成日期**：2026-01-05（本次工作階段）

#### 新增測試檔案（25 個測試）

1. **models/RoundTest.php** - 14 tests, 42 assertions
   - Round 模型結構測試
   - 必填欄位驗證
   - 字串長度驗證
   - createRound() 方法測試（含/不含英文名稱）
   - getMaxRound() 方法測試
   - checkMaxRoundVoted() 方法測試
   - checkQuestion() 方法測試（含/不含 alert）
   - checkCandi() 方法測試（含/不含 alert）
   - 多輪次建立測試
   - 輪次編號驗證測試

2. **models/ResultsTest.php** - 11 tests, 56 assertions
   - Results 模型結構測試
   - 必填欄位驗證
   - 字串長度驗證
   - 整數欄位驗證
   - 唯一性約束測試（voteID + candID）
   - getCandiData() 關聯測試
   - deleteResultsAll() 方法測試（全部刪除/特定 party）
   - 完整開票結果建立測試
   - tbnField() 靜態方法測試
   - 當選狀態值測試

3. **CandiDataTest.php** - 8 tests, 1228 assertions (既有測試已涵蓋)
   - FormCandiData CRUD 操作測試
   - 候選人手動建立/更新/刪除測試
   - 驗證規則完整測試（必填欄位、字串長度限制）
   - 錯誤處理測試

**成果**：
- ✅ 新增 25 個單元測試（Round: 14, Results: 11）
- ✅ 所有測試 100% 通過
- ✅ 輪次管理、開票結果完整測試覆蓋
- ✅ 驗證規則、關聯、業務邏輯全面測試

**特別說明**：
- CandiDataTest 已於前次工作階段完成，涵蓋候選人 CRUD 與驗證規則（8 tests, 1228 assertions）
- FormBallots 補充測試暫緩，因現有 BallotsModelsTest 已涵蓋選票建立與驗證邏輯

### 第四階段：群組投票權限測試（進行中 🚧）

**完成日期**：2026-01-06（本次工作階段）

#### 新增測試檔案（19 個測試，部分待修正）

1. **rules/GroupVotePermissionTest.php** - 19 tests, 21 assertions (10 passing, 9 failing)
   - 群組投票權限規則測試（groupManagOwnVoteRule）
   - 群組查看權限測試（groupViewOwnRule）
   - 群組成員管理權限測試（groupManagByMemberRule）
   - isWrite='Y'/'N' 權限差異測試
   - isOwner='Y'/'N' 權限差異測試
   - GA/GM/VA 角色權限整合測試
   - 4 種權限組合測試（YY, YN, NY, NN）
   - 群組成員 vs 非成員權限隔離測試

#### 新增測試 Fixtures

2. **GroupFixture.php** - 群組測試資料（3 個群組）
3. **GroupMemberFixture.php** - 群組成員測試資料（5 個成員，含 4 種權限組合）
4. **RbacFixture 擴充** - 加入 3 個群組權限 Rules 和 Permissions

**成果**：
- ✅ 建立完整的群組權限測試框架
- ✅ 首次測試 isWrite/isOwner 權限控制
- ✅ 首次測試群組-投票權限整合
- ⚠️ 10/19 測試通過，9 個測試待修正（Rule 執行邏輯需調整）

**通過的測試** (10 tests):
- ✅ GA 可管理有 groupId 的投票
- ✅ GA 不能通過 manageOwnVote 管理無 groupId 的投票
- ✅ GM isWrite='N' 不能管理投票
- ✅ 非群組成員不能管理投票
- ✅ VA 沒有 manageOwnVote 權限
- ✅ 無 groupId 時 GM 不能管理
- ✅ 非成員不能查看群組
- ✅ GA 可管理任何群組成員
- ✅ isOwner='N' 不能管理成員
- ✅ 非成員不能管理群組

**待修正的測試** (9 tests):
- ⏳ GM isWrite='Y' 可管理投票
- ⏳ VA+GM 組合權限
- ⏳ 成員可查看群組
- ⏳ GA 可查看群組
- ⏳ 查看權限不受 isWrite/isOwner 影響
- ⏳ isOwner='Y' 可管理成員
- ⏳ 禁止編輯/刪除自己
- ⏳ isWrite 不影響成員管理
- ⏳ 多群組權限隔離

### 第五階段：Form 模型擴充測試（已完成 ✅）

**完成日期**：2026-01-06

#### 新增測試檔案（44 個測試）

1. **models/FormGroupTest.php** - 21 tests, 41 assertions
   - FormGroup 模型結構測試
   - 驗證規則測試（create/update/search scenarios）
   - getGroupList() 方法測試（all/member 類型）
   - getGroups() 方法測試
   - getGroupInfo() 方法測試
   - createBase() 方法測試
   - updateBase() 方法測試
   - 群組名稱長度驗證
   - isRoutine 值範圍驗證

2. **models/FormGroupMemberTest.php** - 23 tests, 52 assertions
   - FormGroupMember 模型結構測試
   - 驗證規則測試（create/update scenarios）
   - createMember() 方法測試
   - updateMember() 方法測試
   - deleteMember() 方法測試
   - getGroupMemberList() 方法測試
   - 權限組合測試（isWrite/isOwner: YY, YN, NY, NN）
   - 重複成員檢查
   - 無效值驗證

**成果**：
- ✅ 44 個新測試，全部通過 (100%)
- ✅ 測試覆蓋率：Form 模型 CRUD 操作
- ✅ 測試所有 4 種權限組合（isWrite/isOwner）
- ✅ 發現並修復 FormGroup 中 3 個 getId() vs getCn() 業務邏輯問題

**修復狀態**：
- ✅ FormGroup::createBase() (Line 129) - 已由 Alan 於 2024-06-12 修復
- ✅ FormGroup::getGroupList() (Line 59) - 已由 Fisher 於 2026-01-06 修復
- ✅ FormGroup::getGroups() (Line 89) - 已由 Fisher 於 2026-01-06 修復
- ✅ 所有 FormGroupTest 測試通過 (21/21)
- ✅ 所有 FormGroupMemberTest 測試通過 (23/23)

## 🐛 已知問題列表

### ✅ FormGroup::createBase() 業務邏輯問題 - 已修復

**問題描述**：
- Line 129: `Yii::$app->user->identity->getId()` 返回 name（姓名）而非 cn（帳號）
- GroupMember::add() 應接收 cn，但實際接收到的是 name
- 導致創建群組時無法正確添加創建者為成員

**修復日期**: 2024-06-12 (Alan)

**修復內容**：
```php
// Line 129 of FormGroup.php
// 修改前:
return $GroupMember->add($this->groupId, Yii::$app->user->identity->getId(), true, true);

// 修改後:
return $GroupMember->add($this->groupId, Yii::$app->user->identity->getCn(), true, true);
```

**驗證狀態**: ✅ FormGroupTest::testCreateBaseSuccess 通過

### ✅ FormGroup 成員查詢方法業務邏輯問題 - 已修復

**問題描述**：
- Line 58 & 87: 使用 `Yii::$app->user->id` 查詢 GroupMember.cn
- `Yii::$app->user->id` 返回 name（姓名）而非 cn（帳號）
- 導致查詢成員所在群組時返回空結果

**影響範圍**：
1. FormGroup::getGroupList('member') - Line 58 → 59
2. FormGroup::getGroups('member') - Line 87 → 89

**修復日期**: 2026-01-06 (Fisher)

**修復內容**：
```php
// Line 59 of FormGroup.php (getGroupList)
// 修改前:
->where(['groupMember.cn' => Yii::$app->user->id])

// 修改後:
->where(['groupMember.cn' => Yii::$app->user->identity->getCn()])

// Line 89 of FormGroup.php (getGroups)
// 修改前:
->where(['groupMember.cn' => Yii::$app->user->id])

// 修改後:
->where(['groupMember.cn' => Yii::$app->user->identity->getCn()])
```

**驗證狀態**:
- ✅ FormGroupTest::testGetGroupListMember 通過
- ✅ FormGroupTest::testGetGroupsMember 通過
- ✅ 所有 FormGroupTest 測試通過 (21/21)

**詳細分析**：修正與驗證結果已記錄於本節。

### 🎉 舊有單元測試失敗（0 個）- 全部修復完成！

**2026-01-05 修復完成**：原有的 43 個失敗測試已全部修復
**2026-01-06 修復完成**：GroupVotePermissionTest 9 個失敗測試已全部修復

**修復項目**：
1. ✅ **AnonNoPartyVoteTest** - 加入 RbacFixture
2. ✅ **AnonPartyVoteTest** - 加入 RbacFixture
3. ✅ **BallotTest** - 加入 RbacFixture
4. ✅ **CandiConfigTest** - 加入 RbacFixture
5. ✅ **CandiDataTest** - 加入 RbacFixture
6. ✅ **PasswordsTest** - 加入 RbacFixture
7. ✅ **QuestionTest** - 加入 RbacFixture

**修復方法**：
```php
// 1. 加入 use 語句
use app\tests\fixtures\RbacFixture;

// 2. 在 _before() 中載入 RbacFixture
protected function _before()
{
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $this->tester->haveFixtures([
        'users' => UsersFixture::class,
        'config' => ConfigFixture::class,
        'rbac' => RbacFixture::class,  // ← 加入這行
    ]);
    $this->tester->userLogin();
}
```

### 略過測試（1 個）

**BallotsModelsTest::testBallotsSelectedCandiDataRelation** (1 skipped)
- 原因：測試執行時無候選人資料可用
- 狀態：合理的跳過，非錯誤

## 📝 測試指南

### 執行所有單元測試

```bash
php vendor/bin/codecept run unit
```

### 執行特定測試檔案

```bash
# 執行 Votes 模型測試
php vendor/bin/codecept run unit models/VotesTest

# 執行 Ballots 模型測試
php vendor/bin/codecept run unit models/BallotsModelsTest

# 執行 Questions 模型測試
php vendor/bin/codecept run unit models/QuestionsModelTest
```

### 執行特定測試方法

```bash
php vendor/bin/codecept run unit models/VotesTest:testVotesModelStructure
```

### 執行驗收測試

```bash
# 需要先載入 fixtures（⚠️ 使用測試資料庫配置）
php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig" --appconfig=config/console-test.php

# 執行驗收測試
php vendor/bin/codecept run acceptance AnonVoteCest

# 清理
php yii fixture/unload "Ballots,BallotsSelected" --appconfig=config/console-test.php
```

### 產生測試覆蓋率報告

```bash
php vendor/bin/codecept run unit --coverage --coverage-html
```

## 🎯 下一步行動

### 🎉 高優先任務 - 全部完成！

1. ✅ **修復所有 RbacFixture 相關失敗** - 7 個測試檔案已修復
2. ✅ **修復 AdminIdentityTest 失敗** - 加入 RbacFixture
3. ✅ **修復 QuestionTest 失敗** - 加入 RbacFixture
4. ✅ **修復 BallotTest 失敗** - 加入 RbacFixture
5. ✅ **修復 CandiConfigTest 失敗** - 加入 RbacFixture
6. ✅ **修復 CandiDataTest 失敗** - 加入 RbacFixture
7. ✅ **修復 PasswordsTest 失敗** - 加入 RbacFixture
8. ✅ **修復 AnonPartyVoteTest 失敗** - 加入 RbacFixture
9. ✅ **修復 AnonNoPartyVoteTest 失敗** - 加入 RbacFixture

### 短期任務（中優先）

1. ✅ **實施第三階段測試** - Round, Results 模型完成（CandiData 已存在）
2. ✅ **建立群組權限測試框架** - 19 個測試已建立
3. ✅ **修復群組權限 Rules 邏輯問題** - 所有問題已修復，19/19 測試通過！
4. ⏳ **提升測試覆蓋率至 95%+** - 補充邊界情況測試
5. ⏳ **整合 CI/CD** - 自動執行測試

### 長期任務（低優先）

4. ⏳ **WebDriver 驗收測試** - 完整 E2E 測試
5. ⏳ **效能測試** - 壓力測試與效能基準
6. ⏳ **安全測試** - 滲透測試與漏洞掃描

## 📚 相關文件

- [TESTING_ISSUES.md](TESTING_ISSUES.md) - 測試問題與處理方式
- [TEST_GUIDE.md](TEST_GUIDE.md) - 測試撰寫指南
- [CLAUDE.md](../CLAUDE.md) - 專案開發文件

## 🏆 成就里程碑

- ✅ 2025-12-XX：完成高優先級測試（54 tests）
- ✅ 2026-01-05：完成中優先級測試（68 tests）
- ✅ 2026-01-05：**修復所有 43 個失敗測試** 🎉
- ✅ 2026-01-05：**完成第三階段測試**（25 tests）
- ✅ 2026-01-05：**總測試數突破 290 個**（294 tests）
- ✅ 2026-01-05：**測試通過率達到 99.7%**（293/294 通過）
- ✅ 2026-01-06 (上午)：**總測試數突破 310 個**（313 tests）🎉
- ✅ 2026-01-06 (上午)：**建立群組投票權限測試框架**（19 tests）
- ✅ 2026-01-06 (上午)：**首次測試 isWrite/isOwner 權限控制** ⭐
- ✅ 2026-01-06 (上午)：**完成群組權限失敗原因調查** 🔍
- ✅ 2026-01-06 (上午)：**識別並修復 3 個 RBAC Rules 的邏輯錯誤** 🐛
- ✅ 2026-01-06 (上午)：**群組權限測試 19/19 全部通過** 🎯
- ✅ 2026-01-06 (上午)：**測試通過率回升至 99.7%** (312/313) ✨
- ✅ 2026-01-06 (下午)：**總測試數突破 350 個**（357 tests）🎉
- ✅ 2026-01-06 (下午)：**新增 Form 模型測試**（44 tests）
- ✅ 2026-01-06 (下午)：**發現並記錄 FormGroup 業務邏輯問題** 🐛
- ✅ 2026-01-06 (下午)：**測試通過率維持 99.4%** (355/357) ✨
- ✅ 目標：總測試數達到 300 個 ✨ **已達成並超越！**
- ✅ 目標：測試通過率達到 95% ✨ **達到 99.4%！**
- ✅ 目標：群組權限測試全部通過 ✨ **已達成！**
- ✅ 目標：Form 模型測試覆蓋 ✨ **已達成！**

---

**測試是品質的保證！持續增加測試覆蓋率，確保系統穩定可靠。** 🚀

**2026-01-06 重要成就**：
1. ✅ 成功建立群組-投票權限測試框架（19 tests），這是系統中最關鍵的安全機制之一！
2. ✅ 通過測試發現並修復了生產環境的安全漏洞（群組權限功能無法正常運作）
3. ✅ 修復了 3 個 RBAC Rules 的邏輯錯誤：
   - `groupManagOwnVoteRule`: 使用 cn 而非 name 查詢
   - `groupViewOwnRule`: 使用 cn 而非 name 查詢
   - `groupManagByMemberRule`: 從 params 讀取參數，使用 cn 查詢
4. ✅ 補充了 GM 角色的 `manageByMember` 權限
5. ✅ 群組權限調查結果與修正摘要已記錄於本文件
