# 測試撰寫指南

本文件提供投票系統測試撰寫的最佳實踐與規範。

## 📋 目錄

- [測試類型](#測試類型)
- [測試結構](#測試結構)
- [命名規範](#命名規範)
- [常用測試模式](#常用測試模式)
- [Fixture 使用](#fixture-使用)
- [測試最佳實踐](#測試最佳實踐)
- [常見問題排解](#常見問題排解)

## 測試類型

### 1. 單元測試 (Unit Tests)

**位置**：`tests/unit/`

**用途**：測試單一類別、方法或元件的功能

**範例**：
```php
// tests/unit/models/VotesTest.php
public function testVotesModelStructure()
{
    $vote = new Votes();
    $this->assertNotNull($vote);
    $this->assertEquals('votes', Votes::tableName());
}
```

**執行**：
```bash
php vendor/bin/codecept run unit
```

### 2. 驗收測試 (Acceptance Tests)

**位置**：`tests/acceptance/`

**用途**：模擬使用者行為，測試完整流程

**範例**：
```php
// tests/acceptance/AnonVoteCest.php
public function tryToVote(AcceptanceTester $I)
{
    $I->amOnPage('/vote/index');
    $I->see('投票系統');
    $I->fillField('password', 'test123');
    $I->click('登入');
    $I->see('投票頁面');
}
```

**執行**：
```bash


```

## 測試結構

### 基本測試類別結構

```php
<?php

use app\models\ModelName;
use app\tests\fixtures\ModelFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

class ModelNameTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * 測試前置作業
     */
    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // 載入必要的 fixtures
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,  // 如果需要登入
        ]);

        // 執行登入（如果需要）
        $this->tester->userLogin();
    }

    /**
     * 測試後清理
     */
    protected function _after()
    {
        // 清理測試資料
    }

    /**
     * 載入數據 fixtures
     *
     * @return array
     */
    public function _fixtures()
    {
        return [
            'model' => ModelFixture::class,
        ];
    }

    /**
     * 測試方法範例
     */
    public function testSomething()
    {
        // Arrange (準備)
        $model = new ModelName();

        // Act (執行)
        $result = $model->someMethod();

        // Assert (驗證)
        $this->assertTrue($result);
    }
}
```

## 命名規範

### 測試檔案命名

- **單元測試**：`{ModelName}Test.php` 或 `{ComponentName}Test.php`
- **驗收測試**：`{Feature}Cest.php`

### 測試方法命名

使用描述性名稱，清楚表達測試目的：

```php
// ✅ 好的命名
public function testVotesModelStructure()
public function testRequiredFieldsValidation()
public function testGetVotePartyWithAllParty()
public function testDeleteAllBallotWithTransaction()

// ❌ 不好的命名
public function test1()
public function testVotes()
public function testMethod()
```

**命名格式**：`test{動作}{對象}{條件}`

- `testCreate{Model}WithValidData`
- `testUpdate{Model}WithInvalidData`
- `testDelete{Model}Success`
- `testGet{Method}Returns{ExpectedResult}`

## 常用測試模式

### 1. AAA 模式（Arrange-Act-Assert）

```php
public function testCreateVote()
{
    // Arrange - 準備測試資料
    $vote = new Votes();
    $vote->voteID = 'test001';
    $vote->Name = '測試投票';
    $vote->type = Votes::TYPE_ANON;

    // Act - 執行測試動作
    $result = $vote->save();

    // Assert - 驗證結果
    $this->assertTrue($result);
    $this->assertNotNull(Votes::findOne('test001'));
}
```

### 2. 資料提供者模式（Data Provider）

```php
/**
 * @dataProvider voteTypeProvider
 */
public function testVoteTypeValidation($type, $expected)
{
    $vote = new Votes();
    $vote->type = $type;

    $vote->validate(['type']);

    if ($expected) {
        $this->assertArrayNotHasKey('type', $vote->errors);
    } else {
        $this->assertArrayHasKey('type', $vote->errors);
    }
}

public function voteTypeProvider()
{
    return [
        'valid_no_auth' => [Votes::TYPE_NO_AUTH, true],
        'valid_anon' => [Votes::TYPE_ANON, true],
        'invalid_type' => ['invalid', false],
    ];
}
```

### 3. 例外測試模式

```php
public function testDeleteNonExistentBallotThrowsException()
{
    $this->expectException(\yii\web\NotFoundHttpException::class);
    $this->expectExceptionMessage('選票不存在');

    $ballot = new FormBallots();
    $ballot->deleteBallot('invalid_vote', 999);
}
```

### 4. 關聯測試模式

```php
public function testVoteHasManyParties()
{
    $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');
    $parties = $vote->parties;

    $this->assertIsArray($parties);
    $this->assertNotEmpty($parties);

    foreach ($parties as $party) {
        $this->assertInstanceOf(Parties::class, $party);
        $this->assertEquals($vote->voteID, $party->voteID);
    }
}
```

## Fixture 使用

### 基本 Fixture 載入

```php
public function _fixtures()
{
    return [
        'votes' => VotesFixture::class,
        'parties' => PartiesFixture::class,
        'questions' => QuestionsFixture::class,
    ];
}
```

### 使用 Fixture 資料

```php
public function testUsingFixtureData()
{
    // 取得特定 fixture 記錄
    $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

    $this->assertEquals('AnonPartyTest', $vote->voteID);
    $this->assertEquals('1', $vote->type);
}
```

### 動態載入 Fixture

```php
protected function _before()
{
    // 在測試前動態載入
    $this->tester->haveFixtures([
        'users' => UsersFixture::class,
        'rbac' => RbacFixture::class,
    ]);
}
```

### 必要的 Fixtures

**基本測試需要**：
- `UsersFixture` - 提供測試使用者
- `ConfigFixture` - 提供系統配置

**需要登入的測試額外需要**：
- `RbacFixture` - 提供角色權限資料

**投票相關測試需要**：
- `VotesFixture` - 投票場次資料
- `PartiesFixture` - 組別資料
- `QuestionsFixture` - 問題資料
- `PasswordsFixture` - 密碼資料（匿名投票）
- `CandiDataFixture` - 候選人資料

## 測試最佳實踐

### 1. 獨立性原則

每個測試應該獨立執行，不依賴其他測試的結果：

```php
// ✅ 好的做法
public function testCreateBallot()
{
    $ballot = $this->createTestBallot();  // 每次都建立新的
    $this->assertNotNull($ballot->ballotID);
}

// ❌ 不好的做法
public static $sharedBallot;  // 避免共享狀態

public function testCreateBallot()
{
    self::$sharedBallot = new Ballots();
}

public function testUpdateBallot()  // 依賴上一個測試
{
    self::$sharedBallot->update();
}
```

### 2. 清晰的斷言訊息

```php
// ✅ 提供清晰的失敗訊息
$this->assertEquals(
    3,
    count($ballots),
    'Should return exactly 3 ballots for party 0'
);

$this->assertTrue(
    $vote->checkVoteReady(),
    'Vote should be in ready status when active=0'
);
```

### 3. 測試邊界情況

```php
public function testStringLengthValidation()
{
    $vote = new Votes();

    // 測試邊界值
    $vote->voteID = str_repeat('a', 20);  // 最大長度
    $this->assertTrue($vote->validate(['voteID']));

    $vote->voteID = str_repeat('a', 21);  // 超過最大長度
    $this->assertFalse($vote->validate(['voteID']));
}
```

### 4. 避免魔術數字

```php
// ❌ 不好的做法
$vote->type = '1';
$vote->active = '0';

// ✅ 好的做法
$vote->type = Votes::TYPE_ANON;
$vote->active = Votes::STATUS_READY;
```

### 5. 使用輔助方法減少重複

```php
/**
 * 創建測試用選票
 */
private function createTestBallot($voteID, $round, $party, $ballotID, $creator = 'test')
{
    $ballot = new Ballots();
    $ballot->voteID = $voteID;
    $ballot->round = $round;
    $ballot->party = $party;
    $ballot->ballotID = $ballotID;
    $ballot->creator = $creator;
    $ballot->save();
    return $ballot;
}

// 在測試中使用
public function testBallotCreation()
{
    $ballot = $this->createTestBallot('test001', 1, '0', 1);
    $this->assertNotNull($ballot);
}
```

### 6. 測試正向與負向情況

```php
public function testValidLogin()
{
    // 正向測試：有效的登入
    $model = new FormAnon();
    $model->voteID = 'test001';
    $model->password = 'valid_password';

    $this->assertTrue($model->login());
}

public function testInvalidLogin()
{
    // 負向測試：無效的登入
    $model = new FormAnon();
    $model->voteID = 'test001';
    $model->password = 'wrong_password';

    $this->assertFalse($model->login());
}
```

## 常見問題排解

### 問題 1: RBAC 測試失敗 - authManager 快取問題

**症狀**：角色分配成功，但 `Yii::$app->user->can()` 或 `getRolesByUser()` 返回錯誤結果

**原因**：Yii2 的 DbManager 使用快取來提高性能。在測試環境中，角色分配後快取沒有自動更新，導致 `getRolesByUser()` 返回舊資料（空陣列）。

**解決方案**：在測試的 `_before()` 方法中清除 RBAC 快取

```php
protected function _before()
{
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    // 確保管理員登出
    if (!Yii::$app->user->isGuest) {
        Yii::$app->user->logout();
    }

    // 清除 session
    Yii::$app->session->removeAll();

    // ⭐ 清除 RBAC 快取 - 非常重要！
    if (Yii::$app->cache) {
        Yii::$app->cache->flush();
    }
}
```

**說明**：
- DbManager 在 `config/web.php` 和 `config/test.php` 中配置了 `cache` 組件
- 快取用於加速權限檢查，避免頻繁查詢資料庫
- 測試之間的快取隔離需要手動清理
- 此問題特別容易出現在涉及登入和角色分配的測試中

### 問題 2: 測試失敗 "Failed asserting that true is false"

**原因**：通常是 `userLogin()` 失敗，無法建立登入狀態。

**解決方案**：確保載入了 RbacFixture

```php
protected function _before()
{
    $this->tester->haveFixtures([
        'users' => UsersFixture::class,
        'config' => ConfigFixture::class,
        'rbac' => RbacFixture::class,  // ← 加入這行
    ]);
    $this->tester->userLogin();
}
```

### 問題 3: Integrity constraint violation (唯一性約束錯誤)

**原因**：嘗試插入重複的記錄。

**解決方案**：使用唯一的測試資料

```php
// ✅ 每個測試使用不同的 ID
$this->createTestBallot($voteID, 1, '0', 1, 'user1');
$this->createTestBallot($voteID, 1, '0', 2, 'user2');  // 不同的 ballotID 和 creator
```

### 問題 4: Fixture 資料未載入

**原因**：Fixture 檔案路徑錯誤或 fixture 未正確定義。

**解決方案**：檢查 fixture 類別定義

```php
// Fixture 類別應該正確指定 model 和 data file
class VotesFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Votes';
    public $dataFile = '@tests/fixtures/data/votes.php';
}
```

### 問題 5: Session 相關錯誤

**原因**：某些測試需要實際的 session 管理。

**解決方案**：
1. 使用 Yii2 模組內部測試（推薦）
2. 使用 WebDriver 進行完整 E2E 測試
3. 參考 [TESTING_ISSUES.md](TESTING_ISSUES.md)

### 問題 6: 關聯查詢返回 null

**原因**：關聯的 fixture 資料未載入或關聯定義錯誤。

**解決方案**：確保載入所有相關的 fixtures

```php
public function _fixtures()
{
    return [
        'votes' => VotesFixture::class,
        'parties' => PartiesFixture::class,  // ← 確保載入關聯的 fixture
    ];
}
```

## 測試檢查清單

撰寫新測試時，確認以下項目：

- [ ] 測試名稱清楚描述測試目的
- [ ] 使用 AAA 模式（Arrange-Act-Assert）
- [ ] 載入必要的 fixtures
- [ ] 需要登入時載入 RbacFixture
- [ ] 測試正向與負向情況
- [ ] 包含邊界值測試
- [ ] 使用常數代替魔術數字
- [ ] 提供清晰的斷言訊息
- [ ] 測試後清理資料
- [ ] 測試獨立執行不依賴其他測試

## 參考資源

- [Codeception Documentation](https://codeception.com/docs/01-Introduction)
- [Yii2 Testing Guide](https://www.yiiframework.com/doc/guide/2.0/en/test-overview)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [TEST_PROGRESS.md](TEST_PROGRESS.md) - 測試進度追蹤
- [TESTING_ISSUES.md](TESTING_ISSUES.md) - 測試問題與處理方式

---

**撰寫良好的測試是確保程式碼品質的關鍵！** 🎯
