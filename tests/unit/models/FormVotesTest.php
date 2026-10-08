<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormVotes;
use app\models\Votes;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\ConfigFixture;
use app\components\AdminIdentity;

/**
 * FormVotes 模型測試
 * 測試投票表單模型的各項功能
 */
class FormVotesTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // 載入 fixtures - 使用與 FormResultsTest 相同的模式
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'round' => RoundFixture::class,
            'questions' => QuestionsFixture::class,
            'candiData' => CandiDataFixture::class,
            'candiConfig' => CandiConfigFixture::class,
        ]);

        // 確保登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }
        Yii::$app->session->removeAll();
    }

    protected function _after()
    {
        // 清理：登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }
        Yii::$app->session->removeAll();
    }

    /**
     * 輔助方法：建立並登入管理員
     */
    private function loginAdmin($cn, $name, $roles)
    {
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => $cn,
            'name' => $name,
            'roles' => $roles,
        ]);
                Yii::$app->user->switchIdentity($identity);

        $auth = Yii::$app->authManager;
        $auth->revokeAll($cn);
        $role = $auth->getRole($roles);
        if ($role) {
            $auth->assign($role, $cn);
        }

        $this->assertNotNull(Yii::$app->user->identity, 'User identity should be set after login');
    }

    // ==================== 基本結構測試 ====================

    /**
     * 測試：FormVotes 繼承 Votes
     */
    public function testFormVotesExtendsVotes()
    {
        $model = new FormVotes();
        $this->assertInstanceOf(Votes::class, $model, 'FormVotes should extend Votes');
    }

    /**
     * 測試：tableName 正確
     */
    public function testTableName()
    {
        $this->assertEquals('votes', FormVotes::tableName(), 'Table name should be "votes"');
    }

    /**
     * 測試：process 屬性存在
     */
    public function testProcessPropertyExists()
    {
        $model = new FormVotes();
        $this->assertTrue(property_exists($model, 'process'), 'Should have process property');
        $this->assertNull($model->process, 'process should be null by default');
    }

    // ==================== 驗證規則測試 ====================

    /**
     * 測試：search scenario 允許所有欄位
     */
    public function testSearchScenarioAllowsAllFields()
    {
        $model = new FormVotes();
        $model->scenario = 'search';
        $model->voteID = 'TestVote';
        $model->Name = '測試投票';
        $model->type = '1';
        $model->active = '1';

        $this->assertTrue($model->validate(), 'Search scenario should allow all fields');
    }

    /**
     * 測試：create scenario 必填欄位
     */
    public function testCreateScenarioRequiredFields()
    {
        $model = new FormVotes();
        $model->scenario = 'create';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('creator', $model->errors);
        $this->assertArrayHasKey('Name', $model->errors);
        $this->assertArrayHasKey('type', $model->errors);
        $this->assertArrayHasKey('hosted', $model->errors);
        $this->assertArrayHasKey('openStart', $model->errors);
        $this->assertArrayHasKey('openEnd', $model->errors);
    }

    /**
     * 測試：update scenario 必填欄位
     */
    public function testUpdateScenarioRequiredFields()
    {
        $model = new FormVotes();
        $model->scenario = 'update';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('voteID', $model->errors);
        $this->assertArrayHasKey('creator', $model->errors);
        $this->assertArrayHasKey('Name', $model->errors);
    }

    /**
     * 測試：voteID 最大長度驗證
     */
    public function testVoteIdMaxLength()
    {
        $model = new FormVotes();
        $model->voteID = str_repeat('A', 21); // 超過 20 字元

        $this->assertFalse($model->validate(['voteID']), 'Should fail with voteID > 20 chars');
        $this->assertArrayHasKey('voteID', $model->errors);
    }

    /**
     * 測試：session 最大長度驗證
     */
    public function testSessionMaxLength()
    {
        $model = new FormVotes();
        $model->session = str_repeat('A', 21); // 超過 20 字元

        $this->assertFalse($model->validate(['session']), 'Should fail with session > 20 chars');
        $this->assertArrayHasKey('session', $model->errors);
    }

    /**
     * 測試：creator 最大長度驗證
     */
    public function testCreatorMaxLength()
    {
        $model = new FormVotes();
        $model->creator = str_repeat('A', 21); // 超過 20 字元

        $this->assertFalse($model->validate(['creator']), 'Should fail with creator > 20 chars');
        $this->assertArrayHasKey('creator', $model->errors);
    }

    /**
     * 測試：groupId 必須為整數
     */
    public function testGroupIdMustBeInteger()
    {
        $model = new FormVotes();
        $model->groupId = 'not_integer';

        $this->assertFalse($model->validate(['groupId']), 'Should fail with non-integer groupId');
        $this->assertArrayHasKey('groupId', $model->errors);
    }

    /**
     * 測試：round 必須為整數
     */
    public function testRoundMustBeInteger()
    {
        $model = new FormVotes();
        $model->round = 'not_integer';

        $this->assertFalse($model->validate(['round']), 'Should fail with non-integer round');
        $this->assertArrayHasKey('round', $model->errors);
    }

    /**
     * 測試：round 預設值
     */
    public function testRoundDefaultValue()
    {
        $model = new FormVotes();
        $model->validate();

        $this->assertEquals(1, $model->round, 'round should default to 1');
    }

    // ==================== attributeLabels() 測試 ====================

    /**
     * 測試：attributeLabels 包含所有欄位
     */
    public function testAttributeLabels()
    {
        $model = new FormVotes();
        $labels = $model->attributeLabels();

        $this->assertArrayHasKey('type', $labels);
        $this->assertArrayHasKey('voteID', $labels);
        $this->assertArrayHasKey('Name', $labels);
        $this->assertArrayHasKey('creator', $labels);
        $this->assertArrayHasKey('active', $labels);
        $this->assertArrayHasKey('openStart', $labels);
        $this->assertArrayHasKey('openEnd', $labels);
    }

    // ==================== getVoteInfo() 測試 ====================

    /**
     * 測試：getVoteInfo() 查詢存在的投票
     */
    public function testGetVoteInfoExists()
    {
        $model = new FormVotes();
        $result = $model->getVoteInfo('AnonPartyTest');

        $this->assertNotNull($result, 'Should return vote info for existing vote');
        $this->assertEquals('AnonPartyTest', $result->voteID);
    }

    /**
     * 測試：getVoteInfo() 查詢不存在的投票
     */
    public function testGetVoteInfoNonExistent()
    {
        $model = new FormVotes();
        $result = $model->getVoteInfo('NonExistentVote');

        $this->assertNull($result, 'Should return null for non-existent vote');
    }

    /**
     * 測試：getVoteInfo() 帶 useResult 參數
     */
    public function testGetVoteInfoWithUseResult()
    {
        $model = new FormVotes();
        $result = $model->getVoteInfo('AnonPartyTest', true);

        // 即使沒有 resultsConfig，也應該返回投票資訊
        if ($result !== null) {
            $this->assertEquals('AnonPartyTest', $result->voteID);
        }
    }

    // ==================== isVoteOpen() 測試 ====================

    /**
     * 測試：isVoteOpen() 查詢不存在的投票
     */
    public function testIsVoteOpenNonExistent()
    {
        $model = new FormVotes();
        $result = $model->isVoteOpen('NonExistentVote');

        $this->assertNull($result, 'Should return null for non-existent vote');
    }

    /**
     * 測試：isVoteOpen() 返回布林值
     */
    public function testIsVoteOpenReturnsBool()
    {
        $model = new FormVotes();
        $result = $model->isVoteOpen('AnonPartyTest');

        // 結果應該是 null 或 bool
        $this->assertTrue($result === null || is_bool($result), 'Should return null or bool');
    }

    // ==================== getVoteList() 測試 ====================

    /**
     * 測試：getVoteList() 需要登入
     */
    public function testGetVoteListRequiresLogin()
    {
        $this->loginAdmin('admin', '系統管理員', 'sa');

        $model = new FormVotes();
        $query = $model->getVoteList('all');

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query, 'Should return ActiveQuery');
    }

    /**
     * 測試：getVoteList('open') 返回查詢物件
     */
    public function testGetVoteListOpen()
    {
        $this->loginAdmin('admin', '系統管理員', 'sa');

        $model = new FormVotes();
        $query = $model->getVoteList('open');

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query);
    }

    /**
     * 測試：getVoteList('result') 返回查詢物件
     */
    public function testGetVoteListResult()
    {
        $this->loginAdmin('admin', '系統管理員', 'sa');

        $model = new FormVotes();
        $query = $model->getVoteList('result');

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query);
    }

    // ==================== getCnByVote() 測試 ====================

    /**
     * 測試：getCnByVote() 返回陣列
     */
    public function testGetCnByVote()
    {
        $this->loginAdmin('admin', '系統管理員', 'sa');

        $model = new FormVotes();
        $query = $model->getVoteList('all');
        $result = $model->getCnByVote($query);

        $this->assertIsArray($result, 'Should return array');
    }

    // ==================== getVoteAllAry() 測試 ====================

    /**
     * 測試：getVoteAllAry() 返回陣列
     */
    public function testGetVoteAllAry()
    {
        $this->loginAdmin('admin', '系統管理員', 'sa');

        $model = new FormVotes();
        $result = $model->getVoteAllAry();

        $this->assertIsArray($result, 'Should return array');
    }

    // ==================== getCanBindVoteAry() 測試 ====================

    /**
     * 測試：getCanBindVoteAry() 返回陣列
     */
    public function testGetCanBindVoteAry()
    {
        $this->loginAdmin('admin', '系統管理員', 'sa');

        $model = new FormVotes();
        $result = $model->getCanBindVoteAry();

        $this->assertIsArray($result, 'Should return array');
    }

    // ==================== getVoteCreatorAry() 測試 ====================

    /**
     * 測試：getVoteCreatorAry() 返回陣列
     */
    public function testGetVoteCreatorAry()
    {
        $model = new FormVotes();
        $result = $model->getVoteCreatorAry();

        $this->assertIsArray($result, 'Should return array');
    }

    // ==================== createVote() 測試 ====================

    /**
     * 測試：createVote() 返回新模型
     */
    public function testCreateVote()
    {
        $this->loginAdmin('admin', '系統管理員', 'va');

        $model = new FormVotes();
        $newVote = $model->createVote();

        $this->assertInstanceOf(FormVotes::class, $newVote);
        $this->assertEquals('admin', $newVote->creator);
        $this->assertEquals(Votes::TYPE_ANON, $newVote->type);
    }

    // ==================== genVoteId() 測試 ====================

    /**
     * 測試：genVoteId() 生成唯一 ID
     */
    public function testGenVoteId()
    {
        $model = new FormVotes();
        $voteId = $model->genVoteId();

        $this->assertIsString($voteId);
        $this->assertEquals(6, strlen($voteId), 'Default length should be 6');
    }

    /**
     * 測試：genVoteId() 自訂長度
     */
    public function testGenVoteIdCustomLength()
    {
        $model = new FormVotes();
        $voteId = $model->genVoteId(1, 10);

        $this->assertIsString($voteId);
        $this->assertEquals(10, strlen($voteId));
    }

    /**
     * 測試：genVoteId() 生成的 ID 是唯一的
     */
    public function testGenVoteIdUnique()
    {
        $model = new FormVotes();
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = $model->genVoteId();
        }

        $uniqueIds = array_unique($ids);
        $this->assertCount(10, $uniqueIds, 'All generated IDs should be unique');
    }

    // ==================== genShortUrl() 測試 ====================

    /**
     * 測試：genShortUrl() 生成唯一短網址
     */
    public function testGenShortUrl()
    {
        $model = new FormVotes();
        $shortUrl = $model->genShortUrl();

        $this->assertIsString($shortUrl);
        $this->assertEquals(8, strlen($shortUrl), 'Default length should be 8');
    }

    /**
     * 測試：genShortUrl() 生成的短網址是唯一的
     */
    public function testGenShortUrlUnique()
    {
        $model = new FormVotes();
        $urls = [];
        for ($i = 0; $i < 10; $i++) {
            $urls[] = $model->genShortUrl();
        }

        $uniqueUrls = array_unique($urls);
        $this->assertCount(10, $uniqueUrls, 'All generated URLs should be unique');
    }

    // ==================== getBindVotes() 測試 ====================

    /**
     * 測試：getBindVotes() 返回陣列
     */
    public function testGetBindVotes()
    {
        $model = new FormVotes();
        $result = $model->getBindVotes('AnonPartyTest');

        $this->assertIsArray($result, 'Should return array');
    }

    // ==================== getVotePartyQuestion() 測試 ====================

    /**
     * 測試：getVotePartyQuestion() 返回陣列
     */
    public function testGetVotePartyQuestion()
    {
        $model = new FormVotes();
        $result = $model->getVotePartyQuestion('AnonPartyTest', 1, '0');

        $this->assertIsArray($result, 'Should return array');
    }

    /**
     * 測試：getVotePartyQuestion() 英文語系
     */
    public function testGetVotePartyQuestionEnglish()
    {
        $model = new FormVotes();
        $result = $model->getVotePartyQuestion('AnonPartyTest', 1, '0', 'en-us');

        $this->assertIsArray($result, 'Should return array');
    }

    /**
     * 測試：getVotePartyQuestion() 不存在的投票
     */
    public function testGetVotePartyQuestionNonExistent()
    {
        $model = new FormVotes();
        $result = $model->getVotePartyQuestion('NonExistentVote', 1, 'def');

        $this->assertIsArray($result, 'Should return empty array for non-existent vote');
        $this->assertEmpty($result);
    }

    // ==================== 常數測試 ====================

    /**
     * 測試：create scenario 拒絕已移除的 type=2（P1-5）
     */
    public function testCreateScenarioRejectsTypeVoter()
    {
        $model = new FormVotes();
        $model->scenario = 'create';
        $model->type = '2';
        $model->creator = 'admin';
        $model->Name = '記名投票測試';
        $model->hosted = 'test';
        $model->openStart = '2026-01-01 00:00:00';
        $model->openEnd = '2026-12-31 23:59:59';

        $this->assertFalse($model->validate(['type']));
        $this->assertArrayHasKey('type', $model->errors);
    }

    /**
     * 測試：投票類型常數
     */
    public function testVoteTypeConstants()
    {
        $this->assertEquals('0', Votes::TYPE_NO_AUTH, 'TYPE_NO_AUTH should be 0');
        $this->assertEquals('1', Votes::TYPE_ANON, 'TYPE_ANON should be 1');
    }

    /**
     * 測試：投票狀態常數
     */
    public function testVoteStatusConstants()
    {
        $this->assertTrue(defined('app\models\Votes::STATUS_READY'), 'STATUS_READY should be defined');
        $this->assertTrue(defined('app\models\Votes::STATUS_ACTIVE'), 'STATUS_ACTIVE should be defined');
        $this->assertTrue(defined('app\models\Votes::STATUS_TERMINATE'), 'STATUS_TERMINATE should be defined');
    }
}
