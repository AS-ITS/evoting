<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormBallots;
use app\models\Ballots;
use app\models\FormVotes;
use app\models\Votes;
use app\models\Parties;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\BallotsSelectedFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\ConfigFixture;
use app\components\AdminIdentity;

/**
 * FormBallots 模型測試
 * 測試選票表單模型的各項功能
 */
class FormBallotsTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // 載入 fixtures
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
            'passwords' => PasswordsFixture::class,
            'ballots' => BallotsFixture::class,
            'ballotsSelected' => BallotsSelectedFixture::class,
        ]);

        // 確保登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }
        if (!Yii::$app->anon->isGuest) {
            Yii::$app->anon->logout();
        }
        Yii::$app->session->removeAll();
    }

    protected function _after()
    {
        // 清理：登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }
        if (!Yii::$app->anon->isGuest) {
            Yii::$app->anon->logout();
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
     * 測試：FormBallots 繼承 Ballots
     */
    public function testFormBallotsExtendsBallots()
    {
        $model = new FormBallots();
        $this->assertInstanceOf(Ballots::class, $model, 'FormBallots should extend Ballots');
    }

    /**
     * 測試：tableName 正確
     */
    public function testTableName()
    {
        $this->assertEquals('ballots', FormBallots::tableName(), 'Table name should be "ballots"');
    }

    /**
     * 測試：額外屬性存在
     */
    public function testAdditionalAttributes()
    {
        $model = new FormBallots();

        $this->assertTrue(property_exists($model, 'selectNum'), 'Should have selectNum property');
        $this->assertTrue(property_exists($model, 'questionID'), 'Should have questionID property');
    }

    // ==================== 驗證規則測試 ====================

    /**
     * 測試：update scenario 必填欄位
     */
    public function testUpdateScenarioRequiredFields()
    {
        $model = new FormBallots();
        $model->scenario = 'update';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('voteID', $model->errors);
        $this->assertArrayHasKey('round', $model->errors);
    }

    /**
     * 測試：creator scenario 必填欄位
     */
    public function testCreatorScenarioRequiredFields()
    {
        $model = new FormBallots();
        $model->scenario = 'creator';

        $this->assertFalse($model->validate(), 'Should fail validation with empty fields');
        $this->assertArrayHasKey('voteID', $model->errors);
        $this->assertArrayHasKey('round', $model->errors);
    }

    /**
     * 測試：voteID 最大長度驗證
     */
    public function testVoteIdMaxLength()
    {
        $model = new FormBallots();
        $model->scenario = 'update';
        $model->voteID = str_repeat('A', 21); // 超過 20 字元
        $model->round = 1;

        $this->assertFalse($model->validate(['voteID']), 'Should fail with voteID > 20 chars');
        $this->assertArrayHasKey('voteID', $model->errors);
    }

    /**
     * 測試：party 最大長度驗證
     */
    public function testPartyMaxLength()
    {
        $model = new FormBallots();
        $model->scenario = 'update';
        $model->voteID = 'TestVote';
        $model->round = 1;
        $model->party = str_repeat('A', 13); // 超過 12 字元

        $this->assertFalse($model->validate(['party']), 'Should fail with party > 12 chars');
        $this->assertArrayHasKey('party', $model->errors);
    }

    /**
     * 測試：search scenario 允許所有欄位
     */
    public function testSearchScenarioAllowsAllFields()
    {
        $model = new FormBallots();
        $model->scenario = 'search';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->ballotID = 1;
        $model->isAdminAdd = '0';
        $model->ip = '127.0.0.1';
        $model->creator = 'test';
        $model->modifier = 'test';

        $this->assertTrue($model->validate(), 'Search scenario should allow all fields');
    }

    // ==================== getBallotList() 測試 ====================

    /**
     * 測試：getBallotList() 返回查詢物件
     */
    public function testGetBallotListReturnsQuery()
    {
        $model = new FormBallots();
        $query = $model->getBallotList('AnonPartyTest', 1);

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query, 'Should return ActiveQuery');
    }

    /**
     * 測試：getBallotList() 帶過濾參數
     */
    public function testGetBallotListWithFilters()
    {
        $model = new FormBallots();
        $params = [
            'FormBallots' => [
                'party' => 'def',
            ]
        ];
        $query = $model->getBallotList('AnonNoPartyTest', 1, $params);

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query);
    }

    // ==================== getVoteBallot() 測試 ====================

    /**
     * 測試：getVoteBallot() 查詢不存在的選票
     */
    public function testGetVoteBallotNonExistent()
    {
        $model = new FormBallots();
        $result = $model->getVoteBallot('NonExistent', 99999);

        $this->assertNull($result, 'Should return null for non-existent ballot');
    }

    // ==================== getNewVoteBallot() 測試 ====================

    /**
     * 測試：getNewVoteBallot() 建立新選票模型
     */
    public function testGetNewVoteBallot()
    {
        $model = new FormBallots();
        $newBallot = $model->getNewVoteBallot('TestVote', 'def', 'testuser', false);

        $this->assertInstanceOf(FormBallots::class, $newBallot);
        $this->assertEquals('def', $newBallot->party);
        $this->assertEquals('testuser', $newBallot->creator);
        $this->assertEquals('127.0.0.1', $newBallot->ip);
        $this->assertNotEmpty($newBallot->insTime);
    }

    /**
     * 測試：getNewVoteBallot() 管理員新增模式
     */
    public function testGetNewVoteBallotAdminAdd()
    {
        $this->loginAdmin('va_test', 'VA Test', 'va');

        $model = new FormBallots();
        $newBallot = $model->getNewVoteBallot('TestVote', 'def', 'testuser', true);

        $this->assertEquals('1', $newBallot->isAdminAdd);
        $this->assertSame('', $newBallot->modifier);
        $this->assertSame('0000-00-00 00:00:00', $newBallot->updTime);
    }

    // ==================== getBallotChanger() 測試 ====================

    /**
     * 測試：getBallotChanger() 查詢不存在的選票
     */
    public function testGetBallotChangerNonExistent()
    {
        $model = new FormBallots();
        $result = $model->getBallotChanger('NonExistent', 99999);

        $this->assertNull($result, 'Should return null for non-existent ballot');
    }

    // ==================== getNewBallotChanger() 測試 ====================

    /**
     * 測試：getNewBallotChanger() 返回格式化陣列
     */
    public function testGetNewBallotChanger()
    {
        $model = new FormBallots();
        $sysIds = ['user1', 'user2'];
        $result = $model->getNewBallotChanger($sysIds);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('', $result);
        $this->assertEquals('無', $result['']);
        $this->assertArrayHasKey('user1', $result);
        $this->assertArrayHasKey('user2', $result);
    }

    // ==================== 唯一性驗證測試 ====================

    /**
     * 測試：唯一性約束 (voteID, round, party, creator)
     */
    public function testUniqueConstraint()
    {
        $model = new FormBallots();
        $model->scenario = 'creator';
        $model->voteID = 'AnonPartyTest';
        $model->round = 1;
        $model->party = 'def';
        $model->creator = 'duplicate_test';
        $model->insTime = date('Y-m-d H:i:s');
        $model->updTime = date('Y-m-d H:i:s');
        $model->ip = '127.0.0.1';
        $model->isAdminAdd = '0';
        $model->modifier = '';

        // 第一次儲存應該成功
        $firstSave = $model->save();

        if ($firstSave) {
            // 嘗試建立重複記錄
            $model2 = new FormBallots();
            $model2->scenario = 'creator';
            $model2->voteID = 'AnonPartyTest';
            $model2->round = 1;
            $model2->party = 'def';
            $model2->creator = 'duplicate_test';
            $model2->insTime = date('Y-m-d H:i:s');
            $model2->updTime = date('Y-m-d H:i:s');
            $model2->ip = '127.0.0.1';
            $model2->isAdminAdd = '0';
            $model2->modifier = '';

            $this->assertFalse($model2->validate(), 'Should fail validation for duplicate record');
        }
    }

    // ==================== deleteBallot() 測試 ====================

    /**
     * 測試：deleteBallot() 刪除不存在的選票
     */
    public function testDeleteBallotNonExistent()
    {
        $model = new FormBallots();
        $result = $model->deleteBallot('NonExistent', 99999);

        $this->assertNull($result, 'Should return null for non-existent ballot');
    }

    // ==================== deleteAllBallot() 測試 ====================

    /**
     * 測試：deleteAllBallot() 成功執行
     */
    public function testDeleteAllBallotReturnsTrue()
    {
        $model = new FormBallots();
        // 使用存在的投票 ID
        $result = $model->deleteAllBallot('AnonPartyTest', 1);

        $this->assertTrue($result, 'Should return true for existing vote');
    }

    /**
     * 測試：deleteAllBallot() 帶分組參數
     */
    public function testDeleteAllBallotWithParty()
    {
        $model = new FormBallots();
        // 使用存在的投票 ID
        $result = $model->deleteAllBallot('AnonPartyTest', 1, 'def');

        $this->assertTrue($result, 'Should return true with party parameter');
    }

    /**
     * 測試：importBallots 依 orderNum 對應候選人（P2-4）
     */
    public function testImportBallotsMapsCandidatesByOrderNum()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $sourceVote = 'AnonPartyTest';
            $targetVote = 'AnonNoPartyTest';
            $sourceQuestion = '1';
            $targetQuestion = '6';
            $creator = 'imp99999';

            \app\models\BallotsSelected::deleteAll(['voteID' => $sourceVote]);
            FormBallots::deleteAll(['voteID' => $sourceVote]);

            $sourceCandis = \app\models\CandiData::find()
                ->where(['voteID' => $sourceVote, 'questionID' => $sourceQuestion])
                ->orderBy(['id' => SORT_ASC])
                ->limit(2)
                ->all();
            $this->assertCount(2, $sourceCandis);

            $sourceCandis[0]->orderNum = 1;
            $sourceCandis[0]->save(false);
            $sourceCandis[1]->orderNum = 2;
            $sourceCandis[1]->save(false);

            $targetCandis = \app\models\CandiData::find()
                ->where(['voteID' => $targetVote, 'questionID' => $targetQuestion])
                ->orderBy(['id' => SORT_ASC])
                ->limit(2)
                ->all();
            $this->assertCount(2, $targetCandis);

            $targetCandis[0]->orderNum = 1;
            $targetCandis[0]->save(false);
            $targetCandis[1]->orderNum = 2;
            $targetCandis[1]->save(false);
            $targetIdForOrder1 = $targetCandis[0]->id;

            $sourceBallot = new FormBallots();
            $sourceBallot->scenario = 'creator';
            $sourceBallot->voteID = $sourceVote;
            $sourceBallot->round = 1;
            $sourceBallot->party = 'N';
            $sourceBallot->creator = $creator;
            $sourceBallot->insTime = date('Y-m-d H:i:s');
            $sourceBallot->updTime = date('Y-m-d H:i:s');
            $sourceBallot->ip = '127.0.0.1';
            $sourceBallot->isAdminAdd = '1';
            $sourceBallot->modifier = '';
            $this->assertTrue($sourceBallot->save());

            $selection = new \app\models\BallotsSelected();
            $selection->voteID = $sourceVote;
            $selection->ballotID = $sourceBallot->ballotID;
            $selection->selCandiID = $sourceCandis[0]->id;
            $selection->party = 'N';
            $selection->questionID = $sourceQuestion;
            $selection->jobLctn = '1';
            $selection->isValiable = '1';
            $this->assertTrue($selection->save(false));

            $voteInfo = FormVotes::findOne(['voteID' => $targetVote]);
            $postData = [
                'FormBallots' => [
                    'voteID' => $sourceVote,
                    'questionID' => [$sourceQuestion],
                ],
            ];
            $imported = (new FormBallots())->importBallots($voteInfo, [$targetQuestion], $postData);

            $this->assertGreaterThan(0, $imported);

            $importedBallot = FormBallots::findOne([
                'voteID' => $targetVote,
                'round' => 1,
                'party' => 'N',
                'creator' => $creator,
            ]);
            $this->assertNotNull($importedBallot);
            $this->assertSame('1', $importedBallot->isAdminAdd);

            $mappedSelection = \app\models\BallotsSelected::findOne([
                'voteID' => $targetVote,
                'ballotID' => $importedBallot->ballotID,
                'questionID' => $targetQuestion,
            ]);
            $this->assertNotNull($mappedSelection);
            $this->assertEquals($targetIdForOrder1, $mappedSelection->selCandiID);
        } finally {
            $transaction->rollBack();
        }
    }

    public function testIsAdminAddLabel()
    {
        $this->assertSame('否', Ballots::isAdminAddLabel('0'));
        $this->assertSame('是', Ballots::isAdminAddLabel('1'));
        $this->assertSame('', Ballots::isAdminAddLabel(null));
        $this->assertSame('代為輸入', (new FormBallots())->getAttributeLabel('isAdminAdd'));
    }

    public function testGetBallotListFiltersByAdminAdd()
    {
        $model = new FormBallots();
        $all = $model->getBallotList('AnonPartyTest', 1)->count();
        $self = (new FormBallots())->getBallotList('AnonPartyTest', 1, [
            'FormBallots' => ['isAdminAdd' => '0'],
        ])->count();
        $proxy = (new FormBallots())->getBallotList('AnonPartyTest', 1, [
            'FormBallots' => ['isAdminAdd' => '1'],
        ])->count();
        $this->assertGreaterThan(0, $all);
        $this->assertSame($all, $self + $proxy);
        $sql = (new FormBallots())->getBallotList('AnonPartyTest', 1, [
            'FormBallots' => ['isAdminAdd' => '1'],
        ])->createCommand()->getRawSql();
        $this->assertMatchesRegularExpression("/`?isAdminAdd`?\\s*=/", $sql);
        $this->assertStringNotContainsString('like', strtolower($sql));
    }

    public function testExportHeadersIncludeAdminAdd()
    {
        $model = new FormBallots();
        $full = $model->exportHeaders(null);
        $short = $model->exportHeaders('1');
        $this->assertSame('代為輸入', $full[3]);
        $this->assertSame('代為輸入', $short[3]);
        $this->assertSame('投票者IP', $full[4]);
        $this->assertSame('投票時間', $short[4]);
    }

}
