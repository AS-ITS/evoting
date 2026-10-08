<?php
/**
 * Logs 模型單元測試
 *
 * 測試日誌記錄功能，包含新增日誌、搜尋日誌、密碼失敗等待時間計算等。
 */
namespace tests\unit\models;

use Yii;
use Codeception\Test\Unit;
use app\models\Logs;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\VotesFixture;
use app\interfaces\LogInterface;

class LogsTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * 載入 fixture
     */
    public function _fixtures()
    {
        return [
            'users' => UsersFixture::class,
            'votes' => VotesFixture::class,
        ];
    }

    /**
     * 每個測試前執行
     */
    protected function _before()
    {
        parent::_before();
        // 模擬管理員登入
        $this->tester->userLogin();
    }

    /**
     * 測試 Logs 模型可以實例化
     */
    public function testCanInstantiate()
    {
        $logs = new Logs();
        $this->assertInstanceOf(Logs::class, $logs);
    }

    /**
     * 測試 Logs 模型實作 LogInterface
     */
    public function testImplementsLogInterface()
    {
        $logs = new Logs();
        $this->assertInstanceOf(LogInterface::class, $logs);
    }

    /**
     * 測試資料表名稱
     */
    public function testTableName()
    {
        $this->assertEquals('logs', Logs::tableName());
    }

    /**
     * 測試屬性標籤
     */
    public function testAttributeLabels()
    {
        $logs = new Logs();
        $labels = $logs->attributeLabels();

        $this->assertArrayHasKey('id', $labels);
        $this->assertArrayHasKey('voteID', $labels);
        $this->assertArrayHasKey('type', $labels);
        $this->assertArrayHasKey('user', $labels);
        $this->assertArrayHasKey('ip', $labels);
        $this->assertArrayHasKey('browser', $labels);
        $this->assertArrayHasKey('context', $labels);
        $this->assertArrayHasKey('created_at', $labels);
    }

    /**
     * 測試驗證規則 - 必填欄位
     */
    public function testValidationRulesRequired()
    {
        $logs = new Logs();

        // 不設定任何值，驗證應該失敗
        $this->assertFalse($logs->validate());

        $this->assertArrayHasKey('type', $logs->errors);
        $this->assertArrayHasKey('user', $logs->errors);
        $this->assertArrayHasKey('ip', $logs->errors);
        $this->assertArrayHasKey('browser', $logs->errors);
        $this->assertArrayHasKey('context', $logs->errors);
    }

    /**
     * 測試驗證規則 - 有效資料
     */
    public function testValidationWithValidData()
    {
        $logs = new Logs();
        $logs->type = Logs::VOTE_CREATE;
        $logs->user = 'test_user';
        $logs->ip = '127.0.0.1';
        $logs->browser = 'Chrome';
        $logs->context = '{"test": "data"}';

        $this->assertTrue($logs->validate());
    }

    /**
     * 測試新增日誌 - 直接透過模型
     */
    public function testAddLogDirectly()
    {
        $log = new Logs();
        $log->type = Logs::VOTE_CREATE;
        $log->voteID = 'TestVote';
        $log->user = 'test_user';
        $log->ip = '127.0.0.1';
        $log->browser = 'Chrome';
        $log->context = '{"voteID": "TestVote"}';

        $this->assertTrue($log->save());
        $this->assertNotNull($log->id);
    }

    /**
     * 測試新增日誌 - 帶 voteID 屬性
     */
    public function testAddLogWithVoteId()
    {
        $log = new Logs();
        $log->type = Logs::VOTE_EDIT;
        $log->voteID = 'AnonPartyTest';
        $log->user = 'test_user';
        $log->ip = '127.0.0.1';
        $log->browser = 'Chrome';
        $log->context = '{"action": "edit"}';

        $this->assertTrue($log->save());
        $this->assertEquals('AnonPartyTest', $log->voteID);
        $this->assertEquals(Logs::VOTE_EDIT, $log->type);
    }

    /**
     * 測試搜尋功能
     */
    public function testSearch()
    {
        $logs = new Logs();
        // 傳入完整的參數結構
        $query = $logs->search([
            'Logs' => [
                'createdFrom' => null,
                'createdTo' => null,
            ]
        ]);

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query);
    }

    /**
     * 測試搜尋功能 - 按類型篩選
     */
    public function testSearchByType()
    {
        // 先新增測試資料
        $log = new Logs();
        $log->type = Logs::LOGIN_ADMIN;
        $log->user = 'test_admin';
        $log->ip = '127.0.0.1';
        $log->browser = 'Chrome';
        $log->context = '{"admin": "login"}';
        $log->save();

        $logs = new Logs();
        $logs->type = Logs::LOGIN_ADMIN;

        $query = $logs->search([
            'Logs' => [
                'type' => Logs::LOGIN_ADMIN,
                'createdFrom' => null,
                'createdTo' => null,
            ]
        ]);
        $results = $query->all();

        $this->assertGreaterThan(0, count($results));
        foreach ($results as $result) {
            $this->assertEquals(Logs::LOGIN_ADMIN, $result->type);
        }
    }

    /**
     * 測試密碼失敗等待時間 - 無失敗記錄
     */
    public function testGetPasswordFailWaitNoFailures()
    {
        $voteID = 'TestVote123';

        // 沒有失敗記錄時應該返回 null
        $wait = Logs::getPasswordFailWait($voteID);
        $this->assertNull($wait);
    }

    /**
     * 測試密碼失敗等待時間 - 達到鎖定次數（P1-6）
     */
    public function testGetPasswordFailWaitWhenLimitReached()
    {
        $voteID = 'AnonPartyTest';
        $ip = '192.0.2.99';
        $_SERVER['REMOTE_ADDR'] = $ip;

        for ($i = 0; $i < 3; $i++) {
            $log = new Logs();
            $log->type = Logs::LOGIN_PASSWORD_FAIL;
            $log->voteID = $voteID;
            $log->ip = $ip;
            $log->user = 'anon';
            $log->browser = 'test';
            $log->context = '{}';
            $log->created_at = date('Y-m-d H:i:s');
            $log->save(false);
        }

        $wait = Logs::getPasswordFailWait($voteID, 300, 3);
        $this->assertIsInt($wait);
        $this->assertGreaterThan(0, $wait);
        $this->assertLessThanOrEqual(300, $wait);

        Logs::deleteAll([
            'type' => Logs::LOGIN_PASSWORD_FAIL,
            'voteID' => $voteID,
            'ip' => $ip,
        ]);
    }

    /**
     * 測試 LogInterface 常數值
     */
    public function testLogInterfaceConstants()
    {
        // 投票管理相關 (100-199)
        $this->assertEquals('100', Logs::VOTE_CREATE);
        $this->assertEquals('101', Logs::VOTE_CREATE_FAIL);
        $this->assertEquals('102', Logs::VOTE_EDIT);

        // 問題管理相關 (200-299)
        $this->assertEquals('200', Logs::QUESTION_CREATE);
        $this->assertEquals('204', Logs::QUESTION_DELETE);

        // 群組管理相關 (300-399)
        $this->assertEquals('300', Logs::GROUP_CREATE);
        $this->assertEquals('310', Logs::GROUP_MEMBER_CREATE);

        // 系統設定與使用者 (400-499)
        $this->assertEquals('400', Logs::MANAGE_CONFIG_EDIT);
        $this->assertEquals('410', Logs::USERS_CREATE);
        $this->assertEquals('416', Logs::LOGIN_ADMIN);

        // 投票者登入 (500-599)
        $this->assertEquals('500', Logs::LOGIN_PASSWORD);
        $this->assertEquals('501', Logs::LOGIN_PASSWORD_FAIL);

        // 輪次管理 (600-699)
        $this->assertEquals('601', Logs::VOTE_ROUND_CREATE);
        $this->assertEquals('605', Logs::VOTE_ROUND_SWITCH);

        // 樣板管理 (800+)
        $this->assertEquals('801', Logs::TEMPLATE_CREATE);
    }

    /**
     * 測試 type 欄位長度限制
     */
    public function testTypeFieldMaxLength()
    {
        $logs = new Logs();
        $logs->type = '1234'; // 超過 3 個字元
        $logs->user = 'test';
        $logs->ip = '127.0.0.1';
        $logs->browser = 'Chrome';
        $logs->context = 'test';

        $this->assertFalse($logs->validate(['type']));
    }

    /**
     * 測試 user 欄位長度限制
     */
    public function testUserFieldMaxLength()
    {
        $logs = new Logs();
        $logs->type = '100';
        $logs->user = str_repeat('a', 31); // 超過 30 個字元
        $logs->ip = '127.0.0.1';
        $logs->browser = 'Chrome';
        $logs->context = 'test';

        $this->assertFalse($logs->validate(['user']));
    }

    /**
     * 測試 ip 欄位長度限制
     */
    public function testIpFieldMaxLength()
    {
        $logs = new Logs();
        $logs->type = '100';
        $logs->user = 'test';
        $logs->ip = str_repeat('1', 16); // 超過 15 個字元
        $logs->browser = 'Chrome';
        $logs->context = 'test';

        $this->assertFalse($logs->validate(['ip']));
    }
}
