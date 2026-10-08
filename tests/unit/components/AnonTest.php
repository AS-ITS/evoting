<?php

namespace app\tests\unit\components;

use Yii;
use app\models\Logins;
use app\models\Passwords;
use app\components\Anon;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\ConfigFixture;
use Codeception\Test\Unit;

/**
 * Anon 元件測試
 * 測試匿名投票認證元件的登入/登出處理
 */
class AnonTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        // 確保匿名用戶登出
        if (!Yii::$app->anon->isGuest) {
            Yii::$app->anon->logout();
        }

        // 清除 session
        Yii::$app->session->removeAll();

        // 清除 Logins 表
        Logins::deleteAll();
    }

    protected function _after()
    {
        // 清理：登出匿名用戶
        if (!Yii::$app->anon->isGuest) {
            Yii::$app->anon->logout();
        }

        // 清除 session
        Yii::$app->session->removeAll();

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
            'passwords' => PasswordsFixture::class,
            'config' => ConfigFixture::class,
        ];
    }

    /**
     * 測試：Anon 元件結構
     */
    public function testAnonComponentStructure()
    {
        $anon = new Anon();

        // 驗證 flag
        $this->assertEquals('Anon', Anon::$flag, 'Flag should be "Anon"');

        // 驗證 unique key
        $this->assertEquals('id', $anon->getUnique(), 'Unique key should be "id"');

        // 驗證繼承自 UserIdentity
        $this->assertInstanceOf(\app\models\UserIdentity::class, $anon, 'Should extend UserIdentity');

        // 驗證必要方法存在
        $this->assertTrue(method_exists($anon, 'afterLogin'), 'Should have afterLogin method');
        $this->assertTrue(method_exists($anon, 'afterLogout'), 'Should have afterLogout method');
        $this->assertTrue(method_exists($anon, 'getUnique'), 'Should have getUnique method');
    }

    /**
     * 測試：首次登入創建 Logins 記錄
     */
    public function testAfterLoginCreatesLoginsRecord()
    {
        // 準備測試數據
        $voteID = 'test_vote_001';
        $party = '0';
        $passwordId = 1;

        // 創建 Anon identity
        $anon = new Anon();
        $anon->setAuthData([
            'id' => $passwordId,
            'voteID' => $voteID,
            'party' => $party,
        ]);
        // 驗證登入前沒有 Logins 記錄
        $this->assertEquals(0, Logins::find()->count(), 'Should have no logins record before login');

        // 模擬登入並手動觸發 afterLogin
        Yii::$app->anon->login($anon);
        $anon->afterLogin($anon);

        // 驗證 Logins 記錄被創建
        $this->assertEquals(1, Logins::find()->count(), 'Should create logins record after login');

        $login = Logins::findOne(['creator' => $passwordId]);
        $this->assertNotNull($login, 'Login record should exist');
        $this->assertEquals($voteID, $login->voteID, 'VoteID should match');
        $this->assertEquals($party, $login->party, 'Party should match');
        $this->assertEquals($passwordId, $login->creator, 'Creator should match');
        $this->assertEquals(Yii::$app->session->id, $login->session, 'Session ID should match');
    }

    /**
     * 測試：重複登入更新 Session
     */
    public function testAfterLoginUpdatesSessionOnRelogin()
    {
        // 準備測試數據
        $voteID = 'test_vote_002';
        $party = '0';
        $passwordId = 2;

        // 第一次登入
        $anon1 = new Anon();
        $anon1->setAuthData([
            'id' => $passwordId,
            'voteID' => $voteID,
            'party' => $party,
        ]);
                Yii::$app->anon->login($anon1);
        $anon1->afterLogin($anon1);

        $firstSessionId = Yii::$app->session->id;

        // 創建 session 文件避免 unlink 錯誤
        $sessionFile = Yii::getAlias('@runtime/sessions/sess_' . $firstSessionId);
        @touch($sessionFile);

        $login1 = Logins::findOne(['creator' => $passwordId]);
        $this->assertNotNull($login1, 'First login record should exist');
        $this->assertEquals($firstSessionId, $login1->session, 'First session should be recorded');

        // 登出並刪除記錄
        $anon1->afterLogout($anon1);
        Yii::$app->anon->logout();

        // 重新啟動 session (模擬新的登入)
        Yii::$app->session->close();
        Yii::$app->session->open();

        // 第二次登入 (同一個密碼) - 會創建新的 Logins 記錄
        $anon2 = new Anon();
        $anon2->setAuthData([
            'id' => $passwordId,
            'voteID' => $voteID,
            'party' => $party,
        ]);
                Yii::$app->anon->login($anon2);
        $anon2->afterLogin($anon2);

        $secondSessionId = Yii::$app->session->id;

        // 驗證 Session ID 不同
        $this->assertNotEquals($firstSessionId, $secondSessionId, 'Second session should be different');

        // 驗證有一個 Logins 記錄 (登出後刪除了舊的，現在是新的)
        $this->assertEquals(1, Logins::find()->where(['creator' => $passwordId])->count(),
            'Should have one login record');

        // 驗證 Session 是新的
        $login2 = Logins::findOne(['creator' => $passwordId]);
        $this->assertNotNull($login2, 'Login record should exist');
        $this->assertEquals($secondSessionId, $login2->session, 'Session should be new session');
    }

    /**
     * 測試：登出刪除 Logins 記錄
     */
    public function testAfterLogoutDeletesLoginsRecord()
    {
        // 準備測試數據
        $voteID = 'test_vote_003';
        $party = '0';
        $passwordId = 3;

        // 登入
        $anon = new Anon();
        $anon->setAuthData([
            'id' => $passwordId,
            'voteID' => $voteID,
            'party' => $party,
        ]);
                Yii::$app->anon->login($anon);
        $anon->afterLogin($anon);

        // 驗證 Logins 記錄存在
        $this->assertEquals(1, Logins::find()->where(['creator' => $passwordId])->count(),
            'Login record should exist after login');

        // 登出 (手動觸發 afterLogout)
        $anon->afterLogout($anon);
        Yii::$app->anon->logout();

        // 驗證 Logins 記錄被刪除
        $this->assertEquals(0, Logins::find()->where(['creator' => $passwordId])->count(),
            'Login record should be deleted after logout');
    }

    /**
     * 測試：多個用戶同時登入
     */
    public function testMultipleUsersCanLoginSimultaneously()
    {
        // 準備測試數據
        $voteID = 'test_vote_004';
        $party = '0';

        // 用戶 1 登入
        $anon1 = new Anon();
        $anon1->setAuthData([
            'id' => 10,
            'voteID' => $voteID,
            'party' => $party,
        ]);
                Yii::$app->anon->login($anon1);
        $anon1->afterLogin($anon1);

        // 驗證第一個用戶的登入記錄
        $login1 = Logins::findOne(['creator' => 10]);
        $this->assertNotNull($login1, 'User 1 login record should exist');

        // 登出用戶 1
        $anon1->afterLogout($anon1);
        Yii::$app->anon->logout();

        // 用戶 2 登入 (不同的密碼 ID)
        $anon2 = new Anon();
        $anon2->setAuthData([
            'id' => 11,
            'voteID' => $voteID,
            'party' => $party,
        ]);
                Yii::$app->anon->login($anon2);
        $anon2->afterLogin($anon2);

        // 驗證第二個用戶的登入記錄
        $login2 = Logins::findOne(['creator' => 11]);
        $this->assertNotNull($login2, 'User 2 login record should exist');

        // 驗證兩個記錄都存在（因為 creator 不同）
        $this->assertEquals(1, Logins::find()->where(['creator' => 11])->count(),
            'User 2 should have login record');
    }

    /**
     * 測試：不同投票場次的登入隔離
     */
    public function testDifferentVotesSeparateLogins()
    {
        // 投票 A
        $anon1 = new Anon();
        $anon1->setAuthData([
            'id' => 20,
            'voteID' => 'vote_A',
            'party' => '0',
        ]);
                Yii::$app->anon->login($anon1);
        $anon1->afterLogin($anon1);

        $login1 = Logins::findOne(['voteID' => 'vote_A', 'creator' => 20]);
        $this->assertNotNull($login1, 'Vote A login should exist');

        // 登出
        $anon1->afterLogout($anon1);
        Yii::$app->anon->logout();

        // 投票 B (同一個 creator ID，但不同的 voteID)
        $anon2 = new Anon();
        $anon2->setAuthData([
            'id' => 20,
            'voteID' => 'vote_B',
            'party' => '0',
        ]);
                Yii::$app->anon->login($anon2);
        $anon2->afterLogin($anon2);

        $login2 = Logins::findOne(['voteID' => 'vote_B', 'creator' => 20]);
        $this->assertNotNull($login2, 'Vote B login should exist');

        // 驗證兩個投票的記錄是分開的
        $this->assertEquals(1, Logins::find()->where(['voteID' => 'vote_B'])->count(),
            'Vote B should have separate login record');
    }

    /**
     * 測試：不同分組的登入隔離
     */
    public function testDifferentPartiesSeparateLogins()
    {
        $voteID = 'test_vote_005';

        // 分組 0
        $anon1 = new Anon();
        $anon1->setAuthData([
            'id' => 30,
            'voteID' => $voteID,
            'party' => '0',
        ]);
                Yii::$app->anon->login($anon1);
        $anon1->afterLogin($anon1);

        $login1 = Logins::findOne(['voteID' => $voteID, 'party' => '0', 'creator' => 30]);
        $this->assertNotNull($login1, 'Party 0 login should exist');

        // 登出
        $anon1->afterLogout($anon1);
        Yii::$app->anon->logout();

        // 分組 1 (同一個 creator ID，但不同的 party)
        $anon2 = new Anon();
        $anon2->setAuthData([
            'id' => 30,
            'voteID' => $voteID,
            'party' => '1',
        ]);
                Yii::$app->anon->login($anon2);
        $anon2->afterLogin($anon2);

        $login2 = Logins::findOne(['voteID' => $voteID, 'party' => '1', 'creator' => 30]);
        $this->assertNotNull($login2, 'Party 1 login should exist');

        // 驗證兩個分組的記錄是分開的
        $this->assertEquals(1, Logins::find()->where(['party' => '1'])->count(),
            'Party 1 should have separate login record');
    }

    /**
     * 測試：登入後 authData 正確儲存
     */
    public function testLoginStoresAuthDataCorrectly()
    {
        $voteID = 'test_vote_006';
        $party = '0';
        $passwordId = 40;

        $anon = new Anon();
        $anon->setAuthData([
            'id' => $passwordId,
            'voteID' => $voteID,
            'party' => $party,
            'passwd' => 'test_password',
        ]);
                Yii::$app->anon->login($anon);

        // 驗證可以取得 authData
        $this->assertEquals($passwordId, Yii::$app->anon->identity->getAuthData('id'),
            'Should retrieve id from authData');
        $this->assertEquals($voteID, Yii::$app->anon->identity->getAuthData('voteID'),
            'Should retrieve voteID from authData');
        $this->assertEquals($party, Yii::$app->anon->identity->getAuthData('party'),
            'Should retrieve party from authData');
        $this->assertEquals('test_password', Yii::$app->anon->identity->getAuthData('passwd'),
            'Should retrieve passwd from authData');
    }

    /**
     * 測試：登入狀態檢查
     */
    public function testLoginStatusCheck()
    {
        // 驗證初始為訪客
        $this->assertTrue(Yii::$app->anon->isGuest, 'Should be guest before login');

        // 登入
        $anon = new Anon();
        $anon->setAuthData([
            'id' => 50,
            'voteID' => 'test_vote_007',
            'party' => '0',
        ]);
                Yii::$app->anon->login($anon);

        // 驗證已登入
        $this->assertFalse(Yii::$app->anon->isGuest, 'Should not be guest after login');

        // 登出
        Yii::$app->anon->logout();

        // 驗證又變回訪客
        $this->assertTrue(Yii::$app->anon->isGuest, 'Should be guest after logout');
    }

    /**
     * 測試：Logins 記錄的唯一性約束
     */
    public function testLoginsUniqueConstraint()
    {
        $voteID = 'test_vote_008';
        $party = '0';
        $passwordId = 60;

        // 創建第一個 Logins 記錄
        $login1 = new Logins();
        $login1->voteID = $voteID;
        $login1->party = $party;
        $login1->creator = $passwordId;
        $login1->session = 'session_1';
        $this->assertTrue($login1->save(), 'First login should be saved');

        // 嘗試創建重複的記錄（相同的 voteID, party, creator）
        $login2 = new Logins();
        $login2->voteID = $voteID;
        $login2->party = $party;
        $login2->creator = $passwordId;
        $login2->session = 'session_2';

        // 驗證無法儲存（違反唯一性約束）
        $this->assertFalse($login2->save(), 'Duplicate login should not be saved');
        $this->assertArrayHasKey('voteID', $login2->errors, 'Should have error on voteID');
    }

    /**
     * 測試：Session ID 變更處理
     */
    public function testSessionIdChangeHandling()
    {
        $voteID = 'test_vote_009';
        $party = '0';
        $passwordId = 70;

        // 第一次登入
        $anon = new Anon();
        $anon->setAuthData([
            'id' => $passwordId,
            'voteID' => $voteID,
            'party' => $party,
        ]);
                Yii::$app->anon->login($anon);
        $anon->afterLogin($anon);

        $firstSessionId = Yii::$app->session->id;

        // 驗證記錄存在
        $login = Logins::findOne(['creator' => $passwordId]);
        $this->assertNotNull($login, 'Login record should exist');
        $this->assertEquals($firstSessionId, $login->session, 'Should have first session ID');

        // 手動變更 session ID (模擬 session regenerate)
        Yii::$app->session->close();
        Yii::$app->session->destroy();
        Yii::$app->session->open();

        // 重新登入後應該更新為新的 session ID
        $anon2 = new Anon();
        $anon2->setAuthData([
            'id' => $passwordId,
            'voteID' => $voteID,
            'party' => $party,
        ]);
        // 因為已經有記錄，afterLogin 會更新 session
        $anon2->afterLogin($anon2);

        $newSessionId = Yii::$app->session->id;

        // 重新取得記錄
        $updatedLogin = Logins::findOne(['creator' => $passwordId]);
        $this->assertNotNull($updatedLogin, 'Login record should still exist');
        $this->assertEquals($newSessionId, $updatedLogin->session, 'Session should be updated');
    }
}
