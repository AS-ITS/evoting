# 測試文件索引

本目錄包含投票系統的所有測試程式與相關文件。

## 📚 文件導覽

### 核心文件

| 文件 | 說明 | 適用對象 |
|------|------|---------|
| [TEST_PROGRESS.md](TEST_PROGRESS.md) | 📊 測試進度追蹤與統計 | 專案管理者、開發者 |
| [TEST_GUIDE.md](TEST_GUIDE.md) | 📖 測試撰寫指南與最佳實踐 | 測試撰寫者、新成員 |
| [FIXTURE_COMMANDS.md](FIXTURE_COMMANDS.md) | 🔧 Fixture 指令參考 | 測試撰寫者 |

### 快速連結

- **我要撰寫新測試** → [TEST_GUIDE.md](TEST_GUIDE.md)
- **我要查看測試進度** → [TEST_PROGRESS.md](TEST_PROGRESS.md)
- **我要了解 Fixture 指令** → [FIXTURE_COMMANDS.md](FIXTURE_COMMANDS.md)

## 📂 目錄結構

```
tests/
├── README.md                      # 本文件
├── TEST_PROGRESS.md               # 測試進度報告
├── TEST_GUIDE.md                  # 測試撰寫指南
├── FIXTURE_COMMANDS.md            # Fixture 指令參考
│
├── unit/                          # 單元測試
│   ├── components/                # 元件測試
│   │   └── AnonTest.php          # Anon 元件測試
│   ├── models/                    # 模型測試
│   │   ├── VotesTest.php         # Votes 模型測試
│   │   ├── BallotsModelsTest.php # Ballots/BallotsSelected 模型測試
│   │   ├── QuestionsModelTest.php # Questions 模型測試
│   │   ├── LoginsTest.php        # Logins 模型測試
│   │   ├── UserIdentityTest.php  # UserIdentity 基類測試
│   │   └── FormAnonTest.php      # FormAnon 表單測試
│   ├── AdminIdentityTest.php     # AdminIdentity 測試
│   ├── RoleBasedAccessTest.php   # RBAC 權限測試
│   ├── PasswordsTest.php         # Passwords 測試
│   ├── QuestionTest.php          # Question 操作測試
│   ├── BallotTest.php            # Ballot 操作測試
│   └── ...
│
├── acceptance/                    # 驗收測試
│   ├── AnonVoteCest.php          # 匿名投票流程測試
│   ├── AnonVoteEditCest.php      # 投票編輯測試（已略過）
│   └── ...
│
├── fixtures/                      # 測試資料
│   ├── VotesFixture.php
│   ├── PartiesFixture.php
│   ├── QuestionsFixture.php
│   ├── PasswordsFixture.php
│   ├── RbacFixture.php
│   └── data/                      # Fixture 資料檔
│       ├── votes.php
│       ├── parties.php
│       ├── questions.php
│       └── ...
│
└── _support/                      # 測試輔助
    ├── UnitTester.php            # 單元測試輔助類別
    ├── AcceptanceTester.php      # 驗收測試輔助類別
    └── ...
```

## 🎯 測試統計（截至 2026-02-26）

| 種類 | 數量 | 狀態 |
|------|------|------|
| **單元測試 (Unit)** | 1,064 | ✅ 全數通過（含 data provider 展開） |
| **功能測試 (Functional)** | 160 | ✅ 全數通過（需預熱 Fixture） |
| **驗收測試 (Acceptance)** | 116 | ✅ 全數通過 |
| **總計** | **1,340** | 略過 1（合理跳過） |

### 測試覆蓋進度

- ✅ **第一階段（高優先級）**：完成 54 個測試
  - RBAC 認證授權測試
  - Anon 元件測試
  - Logins 模型測試
  - UserIdentity 基類測試

- ✅ **第二階段（中優先級）**：完成 68 個測試
  - Votes 模型完整測試（33 tests）
  - Ballots/BallotsSelected 模型測試（15 tests）
  - Questions 模型測試（20 tests）

- ⏳ **第三階段（低優先級）**：規劃中
  - FormBallots 補充測試
  - CandiData 模型測試
  - Round 模型測試
  - Results 相關測試

## 🚀 快速開始

### 執行所有測試

```bash
# 執行所有單元測試
php vendor/bin/codecept run unit

# 執行所有驗收測試（需先載入 fixtures）
# ⚠️ 重要：使用 --appconfig 確保載入到測試資料庫
# ⚠️ 必須包含 Config,Users,Rbac，否則登入會回傳 403 Forbidden
echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac" --appconfig=config/console-test.php
php vendor/bin/codecept run acceptance
```

### 執行特定測試

```bash
# 執行特定測試檔案
php vendor/bin/codecept run unit models/VotesTest

# 執行特定測試方法
php vendor/bin/codecept run unit models/VotesTest:testVotesModelStructure

# 執行特定測試並顯示詳細資訊
php vendor/bin/codecept run unit FormAnonTest --debug
```

### 產生測試報告

```bash
# HTML 報告
php vendor/bin/codecept run unit --html

# 覆蓋率報告 (需要先安裝 Xdebug 或 PCOV 擴展)
php vendor/bin/codecept run unit --coverage --coverage-html
```

## 📖 測試撰寫流程

1. **閱讀測試指南** → [TEST_GUIDE.md](TEST_GUIDE.md)
2. **檢查測試進度** → [TEST_PROGRESS.md](TEST_PROGRESS.md)
3. **選擇測試項目**（未完成的功能）
4. **撰寫測試程式**（遵循指南規範）
5. **執行測試驗證**
6. **更新測試文件**

## 🐛 問題修復流程

1. **檢查測試輸出** → 查看 `tests/_output/` 目錄
2. **執行失敗測試** → 使用 `--debug` 參數
3. **分析問題根因**
4. **修復並驗證**
5. **更新測試進度文件**

## 🔧 常用指令速查

```bash
# Fixture 管理（⚠️ 務必加上 --appconfig=config/console-test.php）
php yii fixture/load "*" --appconfig=config/console-test.php                    # 載入所有 fixtures
php yii fixture/load "Votes,Parties" --appconfig=config/console-test.php        # 載入特定 fixtures
php yii fixture/unload "*" --appconfig=config/console-test.php                  # 卸載所有 fixtures

# 測試執行
php vendor/bin/codecept run unit            # 執行所有單元測試
php vendor/bin/codecept run acceptance      # 執行所有驗收測試
php vendor/bin/codecept run --steps         # 顯示執行步驟

# 測試報告
php vendor/bin/codecept run --html          # 產生 HTML 報告
php vendor/bin/codecept run --coverage      # 產生覆蓋率報告

# 測試除錯
php vendor/bin/codecept run unit --debug    # 除錯模式
php vendor/bin/codecept run -v              # 詳細輸出
```

**⚠️ 資料庫安全提醒：**
- 測試 fixture 命令務必加上 `--appconfig=config/console-test.php`
- 這確保使用 `voting_test` 測試資料庫，而非正式的 `voting` 資料庫
- 沒有此參數會影響正式資料庫，可能造成資料遺失！

## 📝 測試命名規範

### 測試檔案命名
- 單元測試：`{ModelName}Test.php` 或 `{ComponentName}Test.php`
- 驗收測試：`{Feature}Cest.php`

### 測試方法命名
- 使用描述性名稱：`testVotesModelStructure`
- 格式：`test{動作}{對象}{條件}`
- 範例：
  - `testCreateVoteWithValidData`
  - `testDeleteBallotWithTransaction`
  - `testGetVotePartyWithAllParty`

## 🎯 當前重點任務

### 🎉 高優先級 - 全部完成！
1. ✅ 修復 AdminIdentityTest 失敗（8 tests）→ 已加入 RbacFixture
2. ✅ 修復 BallotTest 失敗 → 已加入 RbacFixture
3. ✅ 修復 QuestionTest 失敗（6 tests）→ 已加入 RbacFixture
4. ✅ 修復 CandiConfigTest 失敗 → 已加入 RbacFixture
5. ✅ 修復 CandiDataTest 失敗 → 已加入 RbacFixture
6. ✅ 修復 PasswordsTest 失敗 → 已加入 RbacFixture
7. ✅ 修復 AnonPartyVoteTest 失敗 → 已加入 RbacFixture
8. ✅ 修復 AnonNoPartyVoteTest 失敗 → 已加入 RbacFixture

**結果**：43 個失敗測試全部修復完成！測試通過率達到 99.6% 🎊

### 🟡 中優先級（短期規劃）
1. 完成第三階段測試（預估 32-40 tests）
2. 提升測試覆蓋率至 100%
3. 整合 CI/CD 自動測試

### 🟢 低優先級（長期規劃）
4. 實施 WebDriver E2E 測試
5. 效能與壓力測試
6. 安全性測試

## 📚 學習資源

- [Codeception Documentation](https://codeception.com/docs/01-Introduction)
- [Yii2 Testing Guide](https://www.yiiframework.com/doc/guide/2.0/en/test-overview)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [專案開發文件 (CLAUDE.md)](../CLAUDE.md)

## 🤝 貢獻指南

1. 撰寫新測試前，先閱讀 [TEST_GUIDE.md](TEST_GUIDE.md)
2. 確保測試符合命名規範與最佳實踐
3. 測試通過後更新 [TEST_PROGRESS.md](TEST_PROGRESS.md)
4. 提交前執行完整測試套件

## 📞 需要協助？

- **測試撰寫問題** → 參考 [TEST_GUIDE.md](TEST_GUIDE.md) 的「常見問題排解」章節
- **測試進度查詢** → 參考 [TEST_PROGRESS.md](TEST_PROGRESS.md)
- **Fixture 指令** → 參考 [FIXTURE_COMMANDS.md](FIXTURE_COMMANDS.md)

---

**測試是品質的保證！持續改進測試覆蓋率，確保系統穩定可靠。** 🚀
