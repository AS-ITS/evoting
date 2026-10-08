<?php

use app\models\Results;
use app\models\CandiData;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;

class ResultsTest extends \Codeception\Test\Unit
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

        // 清空 results 表以確保測試的乾淨環境
        Results::deleteAll();
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
            'parties' => PartiesFixture::class,
            'questions' => QuestionsFixture::class,
            'candiData' => CandiDataFixture::class,
        ];
    }

    /**
     * 測試 Results 模型結構
     */
    public function testResultsModelStructure()
    {
        $result = new Results();

        $this->assertNotNull($result);
        $this->assertEquals('results', Results::tableName());

        // 檢查屬性存在
        $this->assertTrue($result->hasProperty('voteID'));
        $this->assertTrue($result->hasProperty('candID'));
        $this->assertTrue($result->hasProperty('party'));
        $this->assertTrue($result->hasProperty('ballotCounts'));
        $this->assertTrue($result->hasProperty('elected'));
        $this->assertTrue($result->hasProperty('rank'));
        $this->assertTrue($result->hasProperty('comment'));
    }

    /**
     * 測試必填欄位驗證
     */
    public function testRequiredFieldsValidation()
    {
        $result = new Results();

        // 不設定任何值
        $this->assertFalse($result->validate());

        // 檢查必填欄位錯誤（注意：questionID 在資料庫層級是必填，但不在模型驗證規則中）
        $this->assertArrayHasKey('voteID', $result->errors);
        $this->assertArrayHasKey('candID', $result->errors);
        $this->assertArrayHasKey('party', $result->errors);
        $this->assertArrayHasKey('jobLctn', $result->errors);
        $this->assertArrayHasKey('ballotCounts', $result->errors);
        $this->assertArrayHasKey('elected', $result->errors);
        $this->assertArrayHasKey('rank', $result->errors);
        $this->assertArrayHasKey('comment', $result->errors);
    }

    /**
     * 測試字串長度驗證
     */
    public function testStringLengthValidation()
    {
        $result = new Results();

        // voteID 最大長度 20
        $result->voteID = str_repeat('a', 20);
        $result->validate(['voteID']);
        $this->assertArrayNotHasKey('voteID', $result->errors);

        $result->voteID = str_repeat('a', 21);
        $result->validate(['voteID']);
        $this->assertArrayHasKey('voteID', $result->errors);

        // party 最大長度 12
        $result->party = str_repeat('a', 12);
        $result->validate(['party']);
        $this->assertArrayNotHasKey('party', $result->errors);

        $result->party = str_repeat('a', 13);
        $result->validate(['party']);
        $this->assertArrayHasKey('party', $result->errors);

        // comment 最大長度 500
        $result->comment = str_repeat('測', 500);
        $result->validate(['comment']);
        $this->assertArrayNotHasKey('comment', $result->errors);

        $result->comment = str_repeat('測', 501);
        $result->validate(['comment']);
        $this->assertArrayHasKey('comment', $result->errors);
    }

    /**
     * 測試整數欄位驗證
     */
    public function testIntegerFieldsValidation()
    {
        $result = new Results();

        // candID 必須是整數
        $result->candID = 123;
        $this->assertTrue($result->validate(['candID']));

        $result->candID = 'abc';
        $this->assertFalse($result->validate(['candID']));

        // ballotCounts 必須是整數
        $result->ballotCounts = 100;
        $this->assertTrue($result->validate(['ballotCounts']));

        $result->ballotCounts = '100.5';
        $this->assertFalse($result->validate(['ballotCounts']));

        // rank 必須是整數
        $result->rank = 1;
        $this->assertTrue($result->validate(['rank']));

        $result->rank = 'first';
        $this->assertFalse($result->validate(['rank']));
    }

    /**
     * 測試唯一性約束 - voteID + candID
     */
    public function testUniquenessConstraint()
    {
        $candi = $this->tester->grabFixture('candiData', 'candiData730');

        // 建立第一筆資料
        $result1 = new Results();
        $result1->voteID = $candi->voteID;
        $result1->candID = $candi->id;
        $result1->party = '0';
        $result1->questionID = $candi->questionID;
        $result1->jobLctn = 'T';
        $result1->ballotCounts = 100;
        $result1->elected = 'Y';
        $result1->rank = 1;
        $result1->comment = 'Test';

        $this->assertTrue($result1->save());

        // 嘗試建立相同 voteID + candID 的資料
        $result2 = new Results();
        $result2->voteID = $candi->voteID;
        $result2->candID = $candi->id;  // 相同的 candID
        $result2->party = '1';  // 不同的 party
        $result2->questionID = $candi->questionID;
        $result2->jobLctn = 'F';
        $result2->ballotCounts = 200;
        $result2->elected = 'N';
        $result2->rank = 2;
        $result2->comment = 'Test2';

        try {
            $saved = $result2->save();
            $this->assertFalse($saved, 'Expected save to fail due to uniqueness constraint');
            $hasError = isset($result2->errors['voteID']) || isset($result2->errors['candID']);
            $this->assertTrue($hasError, 'Expected uniqueness error, got: ' . print_r($result2->errors, true));
        } catch (\yii\db\IntegrityException $e) {
            $this->assertStringContainsString('Integrity constraint', $e->getMessage());
        }

        // 清理
        Results::deleteAll(['voteID' => $candi->voteID, 'candID' => $candi->id]);
    }

    /**
     * 測試 getCandiData 關聯
     */
    public function testGetCandiDataRelation()
    {
        $candiData = $this->tester->grabFixture('candiData', 'candiData730');

        // 建立 Results 資料
        $result = new Results();
        $result->voteID = $candiData->voteID;
        $result->candID = $candiData->id;
        $result->party = $candiData->party;
        $result->questionID = $candiData->questionID;
        $result->jobLctn = 'T';
        $result->ballotCounts = 50;
        $result->elected = 'Y';
        $result->rank = 1;
        $result->comment = 'Test relation';

        $this->assertTrue($result->save());

        // 測試關聯
        $relatedCandi = $result->candiData;
        $this->assertNotNull($relatedCandi);
        $this->assertInstanceOf(CandiData::class, $relatedCandi);
        $this->assertEquals($candiData->id, $relatedCandi->id);
        $this->assertEquals($candiData->Name, $relatedCandi->Name);

        // 清理
        $result->delete();
    }

    /**
     * 測試 deleteResultsAll 方法 - 刪除所有結果
     */
    public function testDeleteResultsAllWithoutParty()
    {
        $voteID = 'AnonPartyTest';
        $candis = CandiData::find()->where(['voteID' => $voteID])->orderBy(['id' => SORT_ASC])->limit(3)->all();
        $this->assertCount(3, $candis);

        // 建立多筆測試資料
        foreach ($candis as $i => $candi) {
            $result = new Results();
            $result->voteID = $voteID;
            $result->candID = $candi->id;
            $result->party = strval(($i + 1) % 2);
            $result->questionID = $candi->questionID;
            $result->jobLctn = 'T';
            $result->ballotCounts = ($i + 1) * 10;
            $result->elected = 'N';
            $result->rank = $i + 1;
            $result->comment = "Test {$i}";
            $this->assertTrue($result->save(), json_encode($result->errors));
        }

        // 驗證資料已建立
        $count = Results::find()->where(['voteID' => $voteID])->count();
        $this->assertEquals(3, $count);

        // 刪除所有結果
        $resultModel = new Results();
        $deleted = $resultModel->deleteResultsAll($voteID, 1);

        // 驗證已刪除
        $count = Results::find()->where(['voteID' => $voteID])->count();
        $this->assertEquals(0, $count);
        $this->assertEquals(3, $deleted);
    }

    /**
     * 測試 deleteResultsAll 方法 - 刪除特定 party 的結果
     */
    public function testDeleteResultsAllWithParty()
    {
        $voteID = 'AnonPartyTest';
        $candis = CandiData::find()->where(['voteID' => $voteID])->orderBy(['id' => SORT_ASC])->limit(3)->all();
        $this->assertCount(3, $candis);
        $parties = ['0', '1', '2'];

        foreach ($candis as $i => $candi) {
            $result = new Results();
            $result->voteID = $voteID;
            $result->candID = $candi->id;
            $result->party = $parties[$i];
            $result->questionID = $candi->questionID;
            $result->jobLctn = 'T';
            $result->ballotCounts = 100;
            $result->elected = 'N';
            $result->rank = 1;
            $result->comment = "Party {$parties[$i]}";
            $this->assertTrue($result->save(), json_encode($result->errors));
        }

        // 驗證資料已建立
        $count = Results::find()->where(['voteID' => $voteID])->count();
        $this->assertEquals(3, $count);

        // 只刪除 party '0' 的結果
        $resultModel = new Results();
        $deleted = $resultModel->deleteResultsAll($voteID, 1, '0');

        // 驗證只刪除了 party '0'
        $count = Results::find()->where(['voteID' => $voteID])->count();
        $this->assertEquals(2, $count);
        $this->assertEquals(1, $deleted);

        // 驗證剩下的是 party '1' 和 '2'
        $remaining = Results::find()
            ->where(['voteID' => $voteID])
            ->select('party')
            ->column();
        $this->assertContains('1', $remaining);
        $this->assertContains('2', $remaining);
        $this->assertNotContains('0', $remaining);

        // 清理
        Results::deleteAll(['voteID' => $voteID]);
    }

    /**
     * 測試建立完整的開票結果
     */
    public function testCreateCompleteResult()
    {
        $candiData = $this->tester->grabFixture('candiData', 'candiData730');

        $result = new Results();
        $result->voteID = $candiData->voteID;
        $result->candID = $candiData->id;
        $result->party = $candiData->party;
        $result->questionID = $candiData->questionID;
        $result->jobLctn = 'T';
        $result->ballotCounts = 150;
        $result->elected = 'Y';  // 當選
        $result->rank = 1;       // 第一名
        $result->comment = '最高票當選';

        if (!$result->save()) {
            $this->fail('Failed to save result: ' . print_r($result->errors, true));
        }

        // 驗證儲存的資料
        $saved = Results::findOne([
            'voteID' => $candiData->voteID,
            'candID' => $candiData->id
        ]);

        $this->assertNotNull($saved);
        $this->assertEquals(150, $saved->ballotCounts);
        $this->assertEquals('Y', $saved->elected);
        $this->assertEquals(1, $saved->rank);
        $this->assertEquals('最高票當選', $saved->comment);

        // 清理
        $saved->delete();
    }

    /**
     * 測試 tbnField 靜態方法
     */
    public function testTbnFieldMethod()
    {
        $field = Results::tbnField('voteID');
        $this->assertEquals('results.voteID', $field);

        $field = Results::tbnField('candID');
        $this->assertEquals('results.candID', $field);

        $field = Results::tbnField('ballotCounts');
        $this->assertEquals('results.ballotCounts', $field);
    }

    /**
     * 測試當選狀態值
     */
    public function testElectedStatusValues()
    {
        $result = new Results();
        $result->voteID = 'StatusTest';
        $result->candID = 1;
        $result->party = '0';
        $result->questionID = 1;
        $result->jobLctn = 'T';
        $result->ballotCounts = 100;
        $result->rank = 1;
        $result->comment = 'Test';

        // 測試當選 'Y'
        $result->elected = 'Y';
        $this->assertTrue($result->validate(['elected']));

        // 測試未當選 'N'
        $result->elected = 'N';
        $this->assertTrue($result->validate(['elected']));

        // 清理（如果有儲存的話）
        Results::deleteAll(['voteID' => 'StatusTest']);
    }
}
