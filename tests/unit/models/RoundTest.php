<?php

use app\models\Round;
use app\models\Ballots;
use app\models\Questions;
use app\models\CandiData;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

class RoundTest extends \Codeception\Test\Unit
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
    }

    protected function _after()
    {
    }

    /**
     * 載入數據
     *
     * @return array
     */
    public function _fixtures()
    {
        return [
            'votes' => VotesFixture::class,
            'round' => RoundFixture::class,
            'parties' => PartiesFixture::class,
            'questions' => QuestionsFixture::class,
            'candiData' => CandiDataFixture::class,
            'ballots' => BallotsFixture::class,
        ];
    }

    /**
     * 測試 Round 模型結構
     */
    public function testRoundModelStructure()
    {
        $round = new Round();

        $this->assertNotNull($round);
        $this->assertEquals('round', Round::tableName());

        // 檢查屬性存在
        $this->assertTrue($round->hasProperty('id'));
        $this->assertTrue($round->hasProperty('voteID'));
        $this->assertTrue($round->hasProperty('round'));
        $this->assertTrue($round->hasProperty('name'));
        $this->assertTrue($round->hasProperty('nameE'));
        $this->assertTrue($round->hasProperty('showName'));
    }

    /**
     * 測試必填欄位驗證
     */
    public function testRequiredFieldsValidation()
    {
        $round = new Round();

        // 不設定任何值
        $this->assertFalse($round->validate());

        // 檢查必填欄位錯誤
        $this->assertArrayHasKey('voteID', $round->errors);
        $this->assertArrayHasKey('round', $round->errors);
        $this->assertArrayHasKey('name', $round->errors);
        $this->assertArrayHasKey('showName', $round->errors);
    }

    /**
     * 測試字串長度驗證
     */
    public function testStringLengthValidation()
    {
        $round = new Round();

        // voteID 最大長度 20
        $round->voteID = str_repeat('a', 20);
        $round->validate(['voteID']);
        $this->assertArrayNotHasKey('voteID', $round->errors);

        $round->voteID = str_repeat('a', 21);
        $round->validate(['voteID']);
        $this->assertArrayHasKey('voteID', $round->errors);

        // name 最大長度 255
        $round->name = str_repeat('測', 255);
        $round->validate(['name']);
        $this->assertArrayNotHasKey('name', $round->errors);

        $round->name = str_repeat('測', 256);
        $round->validate(['name']);
        $this->assertArrayHasKey('name', $round->errors);
    }

    /**
     * 測試 createRound 方法 - 成功建立輪次
     */
    public function testCreateRoundSuccess()
    {
        $roundModel = new Round();
        $voteID = $this->tester->grabFixture('votes', 'AnonPartyTest')->voteID;
        $round = 90;
        $name = '第一輪投票';
        $nameE = 'First Round';

        $result = $roundModel->createRound($voteID, $round, $name, $nameE);

        $this->assertTrue($result);

        // 驗證資料是否正確儲存
        $savedRound = Round::findOne(['voteID' => $voteID, 'round' => $round]);
        $this->assertNotNull($savedRound);
        $this->assertEquals($voteID, $savedRound->voteID);
        $this->assertEquals($round, $savedRound->round);
        $this->assertEquals($name, $savedRound->name);
        $this->assertEquals($nameE, $savedRound->nameE);
        $this->assertEquals('0', $savedRound->showName);

        // 清理
        $savedRound->delete();
    }

    /**
     * 測試 createRound 方法 - 不提供英文名稱
     */
    public function testCreateRoundWithoutEnglishName()
    {
        $roundModel = new Round();
        $voteID = $this->tester->grabFixture('votes', 'AnonPartyTest')->voteID;
        $round = 91;
        $name = '第一輪投票';

        $result = $roundModel->createRound($voteID, $round, $name);

        $this->assertTrue($result);

        $savedRound = Round::findOne(['voteID' => $voteID, 'round' => $round]);
        $this->assertNotNull($savedRound);
        $this->assertEquals($name, $savedRound->name);
        $this->assertNull($savedRound->nameE);

        // 清理
        $savedRound->delete();
    }

    /**
     * 測試 getMaxRound 方法
     */
    public function testGetMaxRound()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $roundModel = new Round();
        $maxRound = $roundModel->getMaxRound($vote->voteID);

        // 根據 fixture，應該有輪次資料
        $this->assertNotNull($maxRound);
        $this->assertIsNumeric($maxRound);
        $this->assertGreaterThanOrEqual(1, $maxRound);
    }

    /**
     * 測試 getMaxRound 方法 - 沒有輪次資料
     */
    public function testGetMaxRoundWithNoRounds()
    {
        $roundModel = new Round();
        $maxRound = $roundModel->getMaxRound('NonExistentVote');

        // 沒有資料應該返回 null
        $this->assertNull($maxRound);
    }

    /**
     * 測試 checkMaxRoundVoted 方法 - 有投票
     */
    public function testCheckMaxRoundVotedWithBallots()
    {
        $vote = $this->tester->grabFixture('votes', 'AnonPartyTest');

        $roundModel = new Round();
        $hasVoted = $roundModel->checkMaxRoundVoted($vote->voteID);

        // 根據 fixture 資料判斷
        $this->assertIsBool($hasVoted);
    }

    /**
     * 測試 checkQuestion 方法 - 有問題資料
     */
    public function testCheckQuestionExists()
    {
        $roundFixture = $this->tester->grabFixture('round', 'AnonPartyRound1');

        $result = $roundFixture->checkQuestion();

        // 根據 fixture，應該有問題資料
        $this->assertTrue($result);
    }

    /**
     * 測試 checkQuestion 方法 - 無問題資料（帶 alert）
     */
    public function testCheckQuestionNotExistsWithAlert()
    {
        $round = new Round();
        $round->voteID = 'NonExistentVote';
        $round->round = 99;

        $result = $round->checkQuestion(true);

        $this->assertFalse($result);

        // 檢查是否有設定 flash message
        $flashes = Yii::$app->session->getAllFlashes();
        $this->assertArrayHasKey('error', $flashes);
    }

    /**
     * 測試 checkCandi 方法 - 有候選人資料
     */
    public function testCheckCandiExists()
    {
        $roundFixture = $this->tester->grabFixture('round', 'AnonPartyRound1');

        $result = $roundFixture->checkCandi();

        // 根據 fixture，應該有候選人資料
        $this->assertTrue($result);
    }

    /**
     * 測試 checkCandi 方法 - 無候選人資料（帶 alert）
     */
    public function testCheckCandiNotExistsWithAlert()
    {
        $round = new Round();
        $round->voteID = 'NonExistentVote';
        $round->round = 99;

        $result = $round->checkCandi(true);

        $this->assertFalse($result);

        // 檢查是否有設定 flash message
        $flashes = Yii::$app->session->getAllFlashes();
        $this->assertArrayHasKey('error', $flashes);
        $this->assertStringContainsString('候選人', $flashes['error'][0]);
    }

    /**
     * 測試建立多個輪次
     */
    public function testCreateMultipleRounds()
    {
        $voteID = $this->tester->grabFixture('votes', 'AnonPartyTest')->voteID;
        $roundModel = new Round();

        // 建立三個輪次（避開 fixture 既有 round=1）
        for ($i = 92; $i <= 94; $i++) {
            $result = $roundModel->createRound(
                $voteID,
                $i,
                "第{$i}輪",
                "Round {$i}"
            );
            $this->assertTrue($result, "Failed to create round {$i}");
        }

        // 驗證最大輪次
        $maxRound = $roundModel->getMaxRound($voteID);
        $this->assertEquals(94, $maxRound);

        // 清理（只刪測試輪，保留 fixture round=1）
        Round::deleteAll(['voteID' => $voteID, 'round' => [92, 93, 94]]);
    }

    /**
     * 測試輪次編號驗證
     */
    public function testRoundNumberValidation()
    {
        $round = new Round();
        $round->voteID = 'TestVote';
        $round->name = 'Test Round';
        $round->showName = '0';

        // round 必須是整數
        $round->round = 1;
        $this->assertTrue($round->validate(['round']));

        $round->round = 'abc';
        $this->assertFalse($round->validate(['round']));

        $round->round = 1.5;
        $this->assertFalse($round->validate(['round']));
    }
}
