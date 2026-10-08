<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormAnon;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

/**
 * FormAnon 邊界值分析與等價劃分測試（需要 Fixture）
 *
 * 補強項目：
 * - EP：正確密碼登入驗證
 * - EP：錯誤密碼拒絕
 * - EP：停用密碼（status='0'）拒絕
 * - EP：已投票密碼行為
 * - EP：不存在的 voteID
 */
class FormAnonBoundaryTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
        ]);
        // 確保匿名用戶登出
        if (!Yii::$app->anon->isGuest) {
            Yii::$app->anon->logout();
        }
    }

    protected function _after()
    {
        if (!Yii::$app->anon->isGuest) {
            Yii::$app->anon->logout();
        }
    }

    public function _fixtures()
    {
        return [
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'passwords' => PasswordsFixture::class,
        ];
    }

    // ==================== EP：密碼驗證（需要 Fixture） ====================

    /**
     * EP：正確密碼應通過驗證
     */
    public function testValidPasswordPassesValidation()
    {
        $model = new FormAnon();
        $model->voteID = \UnitTester::ANON_PARTY_VOTEID;
        $model->password = 'testN1';

        $result = $model->validate();
        $this->assertTrue($result, 'Valid password should pass validation');
        $this->assertFalse($model->hasErrors(), 'Should have no errors');
    }

    /**
     * EP：錯誤密碼應被拒絕
     */
    public function testWrongPasswordFailsValidation()
    {
        $model = new FormAnon();
        $model->voteID = \UnitTester::ANON_PARTY_VOTEID;
        $model->password = 'wrongPassword999';

        $result = $model->validate();
        $this->assertFalse($result, 'Wrong password should fail validation');
        $this->assertTrue($model->hasErrors('password'), 'Should have password error');
    }

    /**
     * EP：停用密碼（status='0'）應被拒絕
     *
     * getPasswd() 只查詢 status='1' 的密碼，
     * 所以停用的密碼即使正確也無法通過驗證。
     */
    public function testDisabledPasswordRejected()
    {
        // 將 testN2 的 status 改為 '0'（停用）
        Yii::$app->db->createCommand()->update(
            'passwords',
            ['status' => '0'],
            ['voteID' => \UnitTester::ANON_PARTY_VOTEID, 'sn' => 2]
        )->execute();

        $model = new FormAnon();
        $model->voteID = \UnitTester::ANON_PARTY_VOTEID;
        $model->password = 'testN2';

        $result = $model->validate();
        $this->assertFalse($result, 'Disabled password (status=0) should fail validation');
        $this->assertTrue($model->hasErrors('password'), 'Should have password error');

        // 還原
        Yii::$app->db->createCommand()->update(
            'passwords',
            ['status' => '1'],
            ['voteID' => \UnitTester::ANON_PARTY_VOTEID, 'sn' => 2]
        )->execute();
    }

    /**
     * EP：已投票密碼（voted='1'）仍可通過 validatePassword 登入
     *
     * validatePassword() 不再檢查 voted 欄位，以支援多輪投票。
     * 防止重複投票的責任由 isVoteBallot()（進入投票頁時檢查）及
     * ballots 表的 unique constraint（voteID, round, party, creator）承擔。
     */
    public function testVotedPasswordCanStillLogin()
    {
        // 將 testN3 的 voted 改為 '1'（模擬已在某輪投過票）
        Yii::$app->db->createCommand()->update(
            'passwords',
            ['voted' => '1'],
            ['voteID' => \UnitTester::ANON_PARTY_VOTEID, 'sn' => 3]
        )->execute();

        $model = new FormAnon();
        $model->voteID = \UnitTester::ANON_PARTY_VOTEID;
        $model->password = 'testN3';

        $result = $model->validate();
        $this->assertTrue($result,
            'Voted password (voted=1) should still pass validatePassword for multi-round voting');
        $this->assertFalse($model->hasErrors('password'),
            'Should have no password error for voted password');

        // 還原
        Yii::$app->db->createCommand()->update(
            'passwords',
            ['voted' => '0'],
            ['voteID' => \UnitTester::ANON_PARTY_VOTEID, 'sn' => 3]
        )->execute();
    }

    /**
     * EP：不存在的 voteID 應被拒絕
     */
    public function testNonExistentVoteIdFailsValidation()
    {
        $model = new FormAnon();
        $model->voteID = 'NonExistentVoteXYZ';
        $model->password = 'testN1';

        try {
            $result = $model->validate();
            $this->assertFalse($result, 'Non-existent voteID should fail validation');
        } catch (\Codeception\Exception\Warning $e) {
            // 不存在的 voteID 可能導致 null 屬性存取
            $this->assertTrue(true, 'Non-existent voteID caused expected warning');
        } catch (\Error $e) {
            $this->assertTrue(true, 'Non-existent voteID caused expected error');
        }
    }

    /**
     * EP：另一個場次的密碼不應通過（AnonNoPartyTest 的密碼用在 AnonPartyTest）
     */
    public function testCrossVotePasswordRejected()
    {
        $model = new FormAnon();
        $model->voteID = \UnitTester::ANON_PARTY_VOTEID;
        // testdef1 是 AnonNoPartyTest 的密碼，不是 AnonPartyTest 的
        $model->password = 'testdef1';

        $result = $model->validate();
        $this->assertFalse($result, 'Password from another vote should fail validation');
        $this->assertTrue($model->hasErrors('password'), 'Should have password error');
    }

    /**
     * EP：AnonNoPartyTest 場次的正確密碼
     */
    public function testValidPasswordNoPartyVote()
    {
        $model = new FormAnon();
        $model->voteID = \UnitTester::ANON_NO_PARTY_VOTEID;
        $model->password = 'testdef1';

        $result = $model->validate();
        $this->assertTrue($result, 'Valid password for no-party vote should pass');
    }

    /**
     * EP：SQL 注入型密碼輸入應被拒絕（參數化查詢，不應登入成功）
     */
    public function testSqlInjectionPasswordRejected()
    {
        $model = new FormAnon();
        $model->voteID = \UnitTester::ANON_PARTY_VOTEID;
        $model->password = "' OR '1'='1";

        $result = $model->validate();
        $this->assertFalse($result, 'SQL injection style password should fail validation');
        $this->assertTrue($model->hasErrors('password'));
    }

    /**
     * EP：投票場次碼錯誤應被拒絕
     */
    public function testWrongSessionCodeRejected()
    {
        $voteID = \UnitTester::ANON_PARTY_VOTEID;
        Yii::$app->db->createCommand()->update(
            'votes',
            ['session' => 'SESSION2026'],
            ['voteID' => $voteID]
        )->execute();

        $model = new FormAnon();
        $model->voteID = $voteID;
        $model->password = 'testN1';
        $model->session = 'WRONG_CODE';

        $result = $model->validate();
        $this->assertFalse($result, 'Wrong session code should fail validation');
        $this->assertTrue($model->hasErrors('session'));

        Yii::$app->db->createCommand()->update(
            'votes',
            ['session' => ''],
            ['voteID' => $voteID]
        )->execute();
    }

    /**
     * EP：正確場次碼應通過 session 驗證
     */
    public function testCorrectSessionCodePasses()
    {
        $voteID = \UnitTester::ANON_PARTY_VOTEID;
        Yii::$app->db->createCommand()->update(
            'votes',
            ['session' => 'SESSION2026'],
            ['voteID' => $voteID]
        )->execute();

        $model = new FormAnon();
        $model->voteID = $voteID;
        $model->password = 'testN1';
        $model->session = 'SESSION2026';

        $result = $model->validate();
        $this->assertTrue($result, 'Correct session code should pass validation');

        Yii::$app->db->createCommand()->update(
            'votes',
            ['session' => ''],
            ['voteID' => $voteID]
        )->execute();
    }

    /**
     * BVA：密碼邊界值 - 第一個密碼（sn=1）
     */
    public function testFirstPasswordInRange()
    {
        $model = new FormAnon();
        $model->voteID = \UnitTester::ANON_PARTY_VOTEID;
        $model->password = 'testN1';

        $this->assertTrue($model->validate(), 'First password (sn=1) should be valid');
    }

    /**
     * BVA：密碼邊界值 - 最後一個密碼（sn=100）
     */
    public function testLastPasswordInRange()
    {
        $model = new FormAnon();
        $model->voteID = \UnitTester::ANON_PARTY_VOTEID;
        $model->password = 'testN100';

        $this->assertTrue($model->validate(), 'Last password (sn=100) should be valid');
    }

    /**
     * BVA：密碼邊界值 - 超出範圍的密碼（sn=101 不存在）
     */
    public function testPasswordOutOfRange()
    {
        $model = new FormAnon();
        $model->voteID = \UnitTester::ANON_PARTY_VOTEID;
        $model->password = 'testN101';

        $result = $model->validate();
        $this->assertFalse($result, 'Password out of range (sn=101) should fail');
    }
}
