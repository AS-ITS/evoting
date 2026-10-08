<?php

use app\models\Passwd;
use app\models\Passwords;
use app\models\FormPasswords;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

class PasswordsTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        // 載入 UsersFixture, ConfigFixture 和 RbacFixture 以提供登入所需的資料
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
        ]);
        $this->tester->userLogin();
    }

    protected function _after()
    {
        (new PasswordsFixture)->unload();
    }

    /**
     * 載入數據
     *
     * @return array
     */
    public function _fixtures() {
        return [
            'votes'   => VotesFixture::className(),
            'parties'   => PartiesFixture::className(),
            'questions'   => QuestionsFixture::className(),
            'candiConfig'   => CandiConfigFixture::className(),
            'candiData'   => CandiDataFixture::className(),
        ];
    }

    /**
     * 測試 genPasswd 函數是否返回一個字符串數組
     */
    public function testGenPasswdReturnsArrayOfStrings()
    {
        $formPasswords = new FormPasswords();
        $result = $formPasswords->genPasswd(1, 5, 8, Passwd::TYPE_MIX_EXCL);
        $this->assertIsArray($result);
        foreach ($result as $password) {
            $this->assertIsString($password);
        }
    }

    /**
     * 測試 genPasswd 函數是否返回指定數量的密碼
     */
    public function testGenPasswdReturnsCorrectNumberOfPasswords()
    {
        $formPasswords = new FormPasswords();
        $result = $formPasswords->genPasswd(1, 5, 8, Passwd::TYPE_MIX_EXCL);
        $this->assertCount(5, $result);
    }

    /**
     * 測試 genPasswd 函數返回的密碼是否都是指定長度
     */
    public function testGenPasswdReturnsPasswordsOfCorrectLength()
    {
        $formPasswords = new FormPasswords();
        $result = $formPasswords->genPasswd(1, 5, 8, Passwd::TYPE_MIX_EXCL);
        $passwd = new Passwd();
        foreach ($result as $password) {
            $decrypt = $passwd->decrypt($password, null, null, Passwd::CRYPTO_V1);
            $this->assertEquals(8, strlen($decrypt));
        }
    }

    /**
     * 測試 genShuffleStr 函數返回的密碼格式是否符合預期
     * 
     * @dataProvider formatProvider
     */
    public function testGenShuffleStrReturnsPasswordsOfCorrectFormat($type, $format, $regex)
    {
        $passwd = new Passwd();
        $result = $passwd->genShuffleStr(5, 6, $type, $format);
        foreach ($result as $password) {
            $this->assertRegExp($regex, $password);
        }
    }
    
    /**
     * 測試 genShuffleStr 函數返回的密碼格式是否不符合預期
     */
    public function testGenShuffleStrReturnsPasswordsOfIncorrectFormat()
    {
        $passwd = new Passwd();
        $result = $passwd->genShuffleStr(5, 6, Passwd::TYPE_MIX_EXCL, 'SSssii');
        // 格式應該是ssSSii而非SSssii
        foreach ($result as $password) {
            $this->assertNotRegExp('/^[a-z]{2}[A-Z]{2}\d{2}$/', $password);
        }
    }

    /**
     * 測試 genPasswd 函數返回的密碼是否都是由指定字符集中的字符組成
     */
    public function testGenPasswdReturnsPasswordsOfCorrectType()
    {
        $formPasswords = new FormPasswords();
        $result = $formPasswords->genPasswd(1, 5, 8, Passwd::TYPE_MIX_EXCL);
        foreach ($result as $password) {
            $this->assertRegExp('/^[a-zA-Z0-9!@#$%^&*()_+-=,.<>?;:{}\[\]]+$/', $password);
        }
    }

    /**
     * 測試 genPasswd 函數返回的密碼是否都是唯一的
     */
    public function testGenPasswdReturnsUniquePasswords()
    {
        $formPasswords = new FormPasswords();
        $result = $formPasswords->genPasswd(1, 10, 8, Passwd::TYPE_MIX_EXCL);
        $this->assertCount(10, array_unique($result));
    }

    /**
     * 測試方法是否能夠正確地創建指定數量的密碼
     *
     * @dataProvider voteProvider
     */
    public function testCreationPasswdCreatesCorrectNumberOfPasswords($voteID)
    {
        // 創建一個新的 FormPasswords 對象
        $formPasswords = new FormPasswords();
        // 生成 3 個長度為 8 的密碼
        $passwdAry = $formPasswords->genPasswd($voteID, 3, 8, Passwd::TYPE_MIX_EXCL);
        // 從 fixture 中獲取所有的 party
        $parties = $this->tester->grabFixture('parties');
        // 記錄已處理的 party，避免重複建立（因為不同 voteID 可能有相同的 party 值如 'def'）
        $processedParties = [];
        foreach ($parties as $party) {
            // 只處理屬於當前 voteID 的 party，或者尚未處理的 party
            if ($party['voteID'] !== $voteID && in_array($party['party'], $processedParties)) {
                continue;
            }
            // 只處理屬於當前 voteID 的 party
            if ($party['voteID'] !== $voteID) {
                continue;
            }
            $processedParties[] = $party['party'];

            $model = new FormPasswords;
            // 生成 10 個長度為 6 的密碼
            $passwdAry = $model->genPasswd($party['voteID'], 10, 6, Passwd::TYPE_MIX_EXCL);
            $this->assertNotEmpty($passwdAry);
            // 創建密碼
            $result = $formPasswords->creationPasswd($voteID, $passwdAry, $party['party'], '1', '1', 'test');
            $this->assertTrue($result);
            // 驗證創建的密碼數量是否正確
            $passwords = Passwords::find()->where(['voteID' => $voteID, 'party' => $party['party']])->orderBy('sn')->all();
            $this->assertCount(10, $passwords);
            // 驗證創建的密碼是否符合預期
            $sn = 1;
            foreach ($passwords as $password) {
                $this->assertEquals($voteID, $password->voteID);
                $this->assertEquals($party['party'], $password->party);
                $this->assertContains($password->passwd, $passwdAry);
                $this->assertEquals('1', $password->status);
                $this->assertEquals('1', $password->dtrack);
                $this->assertEquals('0', $password->voted);
                $this->assertEquals('test', $password->mark);
                $this->assertEquals($sn++, $password->sn);
            }
        }
    }

    /**
     * 測試 DeletePasswd 方法是否能夠正確刪除指定 ID 的密碼
     * 
     * @dataProvider voteProvider
     */
    public function testDeletePasswdDeletesPasswordWithSpecifiedId($voteID)
    {
        // 創建一個新的 FormPasswords 對象
        $formPasswords = new FormPasswords();
        // 從 fixture 中獲取所有的 party
        $parties = $this->tester->grabFixture('parties');
        // 創建一個新的密碼對象
        foreach ($parties as $party) {
            $password = new Passwords();
            $password->voteID = $voteID;
            $password->party = $party['party'];
            $password->passwd = 'password';
            $password->status = '1';
            $password->dtrack = '1';
            $password->voted = '0';
            $password->sn = 1;
            $password->save();
            // 刪除剛剛創建的密碼
            $result = $formPasswords->DeletePasswd($password->id);
            // 驗證刪除操作是否成功
            $this->assertEquals(1, $result);
            $this->tester->dontSeeRecord(Passwords::className(), ['id' => $password->id]);
        }
    }

    /**
     * 測試 DeletePasswdAll 方法是否能夠正確刪除指定投票 ID 和 party 的所有密碼
     */
    public function testDeletePasswdAllDeletesAllPasswordsForSpecifiedVoteIdAndParty()
    {
        $voteID = UnitTester::ANON_PARTY_VOTEID;
        $formPasswords = new FormPasswords();
        $password1 = $this->createPassword($voteID, '0', 'password1', 1, '0');
        $password2 = $this->createPassword($voteID, '0', 'password2', 2, '0');
        $password3 = $this->createPassword($voteID, '1', 'password3', 1, '1');
        $result = $formPasswords->DeletePasswdAll($voteID, '0');
        $this->assertEquals(2, $result);
        $this->assertNull(Passwords::findOne($password1->id));
        $this->assertNull(Passwords::findOne($password2->id));
        $this->assertNotNull(Passwords::findOne($password3->id));
    }

    /**
     * 測試 DeletePasswdAll 方法是否能夠正確刪除指定投票 ID 的所有密碼
     */
    public function testDeletePasswdAllDeletesAllPasswordsForSpecifiedVoteId()
    {
        // 獲取投票場次
        $voteID = UnitTester::ANON_PARTY_VOTEID;
        $voteID2 = UnitTester::ANON_NO_PARTY_VOTEID;
        // 創建一個新的 FormPasswords 對象
        $formPasswords = new FormPasswords();
        // 創建一些新的密碼對象
        $password1 = $this->createPassword($voteID, '0', 'password1', 1, '0');
        $password2 = $this->createPassword($voteID, '0', 'password2', 2, '0');
        $password3 = $this->createPassword($voteID2, 'def', 'password3', 1, 'def');
        // 刪除指定投票 ID 的所有密碼
        $result = $formPasswords->DeletePasswdAll($voteID);
        // 驗證刪除操作是否成功
        $this->assertEquals(2, $result);
        $this->assertNull(Passwords::findOne($password1->id));
        $this->assertNull(Passwords::findOne($password2->id));
        $this->assertNotNull(Passwords::findOne($password3->id));
    }

    /**
     * 測試 DeletePasswdAll 方法是否能夠正確刪除指定標記的所有密碼
     */
    public function testDeletePasswdAllDeletesAllPasswordsForMark()
    {
        // 獲取投票場次
        $voteID = UnitTester::ANON_PARTY_VOTEID;
        $voteID2 = UnitTester::ANON_NO_PARTY_VOTEID;
        // 創建一個新的 FormPasswords 對象
        $formPasswords = new FormPasswords();
        // 創建一些新的密碼對象
        $password1 = $this->createPassword($voteID, '0', 'password1', 1);
        $password2 = $this->createPassword($voteID, '0', 'password2', 2, '0');
        $password3 = $this->createPassword($voteID2, 'def', 'password3', 1);
        $password4 = $this->createPassword($voteID2, 'def', 'password4', 1, 'def');
        // 刪除指定標記 的所有密碼
        $result = $formPasswords->DeletePasswdAll($voteID, null, '0');
        $result2 = $formPasswords->DeletePasswdAll($voteID2, null, 'def');
        // 驗證刪除操作是否成功
        $this->assertEquals(1, $result);
        $this->assertEquals(1, $result2);
        $this->assertNotNull(Passwords::findOne($password1->id));
        $this->assertNull(Passwords::findOne($password2->id));
        $this->assertNotNull(Passwords::findOne($password3->id));
        $this->assertNull(Passwords::findOne($password4->id));
    }

    /**
     * 創建一個新的密碼對象
     *
     * @param int $voteID 投票場次
     * @param string $party 投票方
     * @param string $passwd 密碼
     * @param int $sn 序號
     * @param string $mark 標記
     * @return Passwords
     */
    private function createPassword($voteID, $party, $passwd, $sn, $mark=null)
    {
        $password = new Passwords();
        $password->voteID = $voteID;
        $password->party = $party;
        $password->passwd = $passwd;
        $password->status = '1';
        $password->dtrack = '1';
        $password->voted = '0';
        $password->sn = $sn;
        $password->mark = $mark;
        $password->save();
        return $password;
    }

    /**
     * 密碼格式
     * 
     * @return array
     */
    public function formatProvider()
    {
        return [
            [
                'type' => Passwd::TYPE_MIX_LOWER,
                'format' => 'ssssii',
                'regex' => '/^[a-z]{4}\d{2}$/',
            ],
            [
                'type' => Passwd::TYPE_MIX_UPPER,
                'format' => 'SSSSii',
                'regex' => '/^[A-Z]{4}\d{2}$/',
            ],
            [
                'type' => Passwd::TYPE_MIX_EXCL,
                'format' => 'ssSSii',
                'regex' => '/^[a-z]{2}[A-Z]{2}\d{2}$/',
            ],
        ];
    }

    /**
     * 投票場次
     * 
     * @return array
     */
    public function voteProvider()
    {
        return [
            [UnitTester::ANON_PARTY_VOTEID],
            [UnitTester::ANON_NO_PARTY_VOTEID],
        ];
    }
}