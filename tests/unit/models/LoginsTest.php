<?php

namespace app\tests\unit\models;

use Yii;
use app\models\Logins;
use app\tests\fixtures\VotesFixture;
use Codeception\Test\Unit;

/**
 * Logins 模型測試
 * 測試 Logins 模型的 CRUD 操作和驗證規則
 */
class LoginsTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        // 清除 Logins 表
        Logins::deleteAll();
    }

    protected function _after()
    {
        // 清除 Logins 表
        Logins::deleteAll();
    }

    /**
     * 載入測試數據
     *
     * @return array
     */
    public function _fixtures()
    {
        return [
            'votes' => VotesFixture::class,
        ];
    }

    /**
     * 測試：Logins 模型結構
     */
    public function testLoginsModelStructure()
    {
        $login = new Logins();

        // 驗證表名
        $this->assertEquals('logins', Logins::tableName(), 'Table name should be logins');

        // 驗證屬性存在
        $this->assertTrue($login->hasAttribute('voteID'), 'Should have voteID attribute');
        $this->assertTrue($login->hasAttribute('party'), 'Should have party attribute');
        $this->assertTrue($login->hasAttribute('creator'), 'Should have creator attribute');
        $this->assertTrue($login->hasAttribute('session'), 'Should have session attribute');

        // 驗證標籤
        $labels = $login->attributeLabels();
        $this->assertEquals('投票識別碼', $labels['voteID'], 'VoteID label should match');
        $this->assertEquals('投票者組別', $labels['party'], 'Party label should match');
        $this->assertEquals('登入對象(投票者)', $labels['creator'], 'Creator label should match');
        $this->assertEquals('登入session', $labels['session'], 'Session label should match');
    }

    /**
     * 測試：創建有效的 Logins 記錄
     */
    public function testCreateValidLogin()
    {
        $login = new Logins();
        $login->voteID = 'test_vote_001';
        $login->party = '0';
        $login->creator = 1;
        $login->session = 'test_session_123';

        $this->assertTrue($login->save(), 'Valid login should be saved');
        $this->assertNotNull($login->voteID, 'VoteID should be set');
        $this->assertNotNull($login->party, 'Party should be set');
        $this->assertNotNull($login->creator, 'Creator should be set');
        $this->assertNotNull($login->session, 'Session should be set');
    }

    /**
     * 測試：必填欄位驗證
     */
    public function testRequiredFields()
    {
        $login = new Logins();

        // 不設定任何欄位
        $this->assertFalse($login->save(), 'Should not save without required fields');

        // 驗證錯誤訊息
        $this->assertArrayHasKey('voteID', $login->errors, 'Should have error on voteID');
        $this->assertArrayHasKey('party', $login->errors, 'Should have error on party');
        $this->assertArrayHasKey('creator', $login->errors, 'Should have error on creator');
    }

    /**
     * 測試：voteID 必填
     */
    public function testVoteIdRequired()
    {
        $login = new Logins();
        $login->party = '0';
        $login->creator = 1;
        $login->session = 'test_session';

        $this->assertFalse($login->save(), 'Should not save without voteID');
        $this->assertArrayHasKey('voteID', $login->errors, 'Should have error on voteID');
    }

    /**
     * 測試：party 必填
     */
    public function testPartyRequired()
    {
        $login = new Logins();
        $login->voteID = 'test_vote_001';
        $login->creator = 1;
        $login->session = 'test_session';

        $this->assertFalse($login->save(), 'Should not save without party');
        $this->assertArrayHasKey('party', $login->errors, 'Should have error on party');
    }

    /**
     * 測試：creator 必填
     */
    public function testCreatorRequired()
    {
        $login = new Logins();
        $login->voteID = 'test_vote_001';
        $login->party = '0';
        $login->session = 'test_session';

        $this->assertFalse($login->save(), 'Should not save without creator');
        $this->assertArrayHasKey('creator', $login->errors, 'Should have error on creator');
    }

    /**
     * 測試：session 可選
     */
    public function testSessionOptional()
    {
        $login = new Logins();
        $login->voteID = 'test_vote_002';
        $login->party = '0';
        $login->creator = 2;
        // 不設定 session

        $this->assertTrue($login->save(), 'Should save without session');
        $this->assertNull($login->session, 'Session should be null');
    }

    /**
     * 測試：creator 必須是整數
     */
    public function testCreatorMustBeInteger()
    {
        $login = new Logins();
        $login->voteID = 'test_vote_003';
        $login->party = '0';
        $login->creator = 'not_an_integer';
        $login->session = 'test_session';

        $this->assertFalse($login->save(), 'Should not save with non-integer creator');
        $this->assertArrayHasKey('creator', $login->errors, 'Should have error on creator');
    }

    /**
     * 測試：voteID 長度限制 (最大 20)
     */
    public function testVoteIdMaxLength()
    {
        $login = new Logins();
        $login->voteID = str_repeat('a', 21); // 21 字元
        $login->party = '0';
        $login->creator = 3;
        $login->session = 'test_session';

        $this->assertFalse($login->save(), 'Should not save with voteID longer than 20 characters');
        $this->assertArrayHasKey('voteID', $login->errors, 'Should have error on voteID');
    }

    /**
     * 測試：party 長度限制 (最大 12)
     */
    public function testPartyMaxLength()
    {
        $login = new Logins();
        $login->voteID = 'test_vote_004';
        $login->party = str_repeat('a', 13); // 13 字元
        $login->creator = 4;
        $login->session = 'test_session';

        $this->assertFalse($login->save(), 'Should not save with party longer than 12 characters');
        $this->assertArrayHasKey('party', $login->errors, 'Should have error on party');
    }

    /**
     * 測試：session 長度限制 (最大 100)
     */
    public function testSessionMaxLength()
    {
        $login = new Logins();
        $login->voteID = 'test_vote_005';
        $login->party = '0';
        $login->creator = 5;
        $login->session = str_repeat('a', 101); // 101 字元

        $this->assertFalse($login->save(), 'Should not save with session longer than 100 characters');
        $this->assertArrayHasKey('session', $login->errors, 'Should have error on session');
    }

    /**
     * 測試：唯一性約束 (voteID + party + creator)
     */
    public function testUniqueConstraint()
    {
        // 創建第一個記錄
        $login1 = new Logins();
        $login1->voteID = 'test_vote_006';
        $login1->party = '0';
        $login1->creator = 6;
        $login1->session = 'session_1';
        $this->assertTrue($login1->save(), 'First login should be saved');

        // 嘗試創建重複的記錄
        $login2 = new Logins();
        $login2->voteID = 'test_vote_006';
        $login2->party = '0';
        $login2->creator = 6;
        $login2->session = 'session_2';

        $this->assertFalse($login2->save(), 'Duplicate login should not be saved');
        $this->assertArrayHasKey('voteID', $login2->errors, 'Should have error on voteID');
    }

    /**
     * 測試：不同 voteID 可以有相同 creator
     */
    public function testDifferentVoteIdSameCreator()
    {
        // voteID = A, creator = 7
        $login1 = new Logins();
        $login1->voteID = 'vote_A';
        $login1->party = '0';
        $login1->creator = 7;
        $login1->session = 'session_A';
        $this->assertTrue($login1->save(), 'First login should be saved');

        // voteID = B, creator = 7 (相同 creator，不同 voteID)
        $login2 = new Logins();
        $login2->voteID = 'vote_B';
        $login2->party = '0';
        $login2->creator = 7;
        $login2->session = 'session_B';
        $this->assertTrue($login2->save(), 'Second login should be saved (different voteID)');

        // 驗證兩個記錄都存在
        $this->assertEquals(2, Logins::find()->where(['creator' => 7])->count(),
            'Should have 2 logins for same creator');
    }

    /**
     * 測試：不同 party 可以有相同 creator
     */
    public function testDifferentPartySameCreator()
    {
        // party = 0, creator = 8
        $login1 = new Logins();
        $login1->voteID = 'test_vote_007';
        $login1->party = '0';
        $login1->creator = 8;
        $login1->session = 'session_1';
        $this->assertTrue($login1->save(), 'First login should be saved');

        // party = 1, creator = 8 (相同 creator，不同 party)
        $login2 = new Logins();
        $login2->voteID = 'test_vote_007';
        $login2->party = '1';
        $login2->creator = 8;
        $login2->session = 'session_2';
        $this->assertTrue($login2->save(), 'Second login should be saved (different party)');

        // 驗證兩個記錄都存在
        $this->assertEquals(2, Logins::find()->where(['voteID' => 'test_vote_007', 'creator' => 8])->count(),
            'Should have 2 logins for same creator in different parties');
    }

    /**
     * 測試：查詢 Logins 記錄
     */
    public function testFindLogin()
    {
        // 創建記錄
        $login = new Logins();
        $login->voteID = 'test_vote_008';
        $login->party = '0';
        $login->creator = 9;
        $login->session = 'test_session_find';
        $this->assertTrue($login->save());

        // 查詢記錄
        $found = Logins::findOne(['creator' => 9]);
        $this->assertNotNull($found, 'Should find the login');
        $this->assertEquals('test_vote_008', $found->voteID, 'VoteID should match');
        $this->assertEquals('0', $found->party, 'Party should match');
        $this->assertEquals(9, $found->creator, 'Creator should match');
        $this->assertEquals('test_session_find', $found->session, 'Session should match');
    }

    /**
     * 測試：更新 Logins 記錄
     */
    public function testUpdateLogin()
    {
        // 創建記錄
        $login = new Logins();
        $login->voteID = 'test_vote_009';
        $login->party = '0';
        $login->creator = 10;
        $login->session = 'old_session';
        $this->assertTrue($login->save());

        // 更新 session
        $login->session = 'new_session';
        $this->assertTrue($login->save(), 'Should update successfully');

        // 驗證更新
        $updated = Logins::findOne(['creator' => 10]);
        $this->assertEquals('new_session', $updated->session, 'Session should be updated');
    }

    /**
     * 測試：刪除 Logins 記錄
     */
    public function testDeleteLogin()
    {
        // 創建記錄
        $login = new Logins();
        $login->voteID = 'test_vote_010';
        $login->party = '0';
        $login->creator = 11;
        $login->session = 'test_session_delete';
        $this->assertTrue($login->save());

        // 驗證存在
        $this->assertEquals(1, Logins::find()->where(['creator' => 11])->count(),
            'Login should exist');

        // 刪除
        $this->assertEquals(1, $login->delete(), 'Should delete successfully');

        // 驗證已刪除
        $this->assertEquals(0, Logins::find()->where(['creator' => 11])->count(),
            'Login should be deleted');
    }

    /**
     * 測試：批量查詢
     */
    public function testBatchQuery()
    {
        // 創建多個記錄
        for ($i = 20; $i < 25; $i++) {
            $login = new Logins();
            $login->voteID = 'test_vote_batch';
            $login->party = '0';
            $login->creator = $i;
            $login->session = "session_$i";
            $login->save();
        }

        // 查詢所有記錄
        $logins = Logins::find()->where(['voteID' => 'test_vote_batch'])->all();
        $this->assertCount(5, $logins, 'Should find 5 logins');
    }

    /**
     * 測試：條件查詢
     */
    public function testConditionalQuery()
    {
        // 創建測試數據
        $login1 = new Logins();
        $login1->voteID = 'vote_condition_1';
        $login1->party = '0';
        $login1->creator = 30;
        $login1->session = 'session_30';
        $login1->save();

        $login2 = new Logins();
        $login2->voteID = 'vote_condition_2';
        $login2->party = '1';
        $login2->creator = 31;
        $login2->session = 'session_31';
        $login2->save();

        // 按 voteID 查詢
        $count1 = Logins::find()->where(['voteID' => 'vote_condition_1'])->count();
        $this->assertEquals(1, $count1, 'Should find 1 login with vote_condition_1');

        // 按 party 查詢
        $count2 = Logins::find()->where(['party' => '1'])->count();
        $this->assertEquals(1, $count2, 'Should find 1 login with party 1');
    }
}
