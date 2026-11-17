<?php

namespace app\tests\unit\models;

use app\models\TableSchemaRules;
use app\models\UniversalActiveRecord;
use app\tests\fixtures\VotesFixture;
use Codeception\Test\Unit;

/**
 * 萬用資料表的外鍵存在檢查（SA 資料表編輯器新增／修改時使用）
 */
class UniversalActiveRecordValidateExistTest extends Unit
{
    /** @var string|null */
    private $originalTable;

    public function _fixtures()
    {
        return [
            'votes' => VotesFixture::class,
        ];
    }

    protected function _before()
    {
        $this->originalTable = UniversalActiveRecord::$tableName;
        UniversalActiveRecord::$tableName = 'candiConfig';
    }

    protected function _after()
    {
        UniversalActiveRecord::$tableName = $this->originalTable;
    }

    private function params()
    {
        return ['table' => 'votes', 'targetAttribute' => ['voteID' => 'voteID']];
    }

    public function testExistingReferencePasses()
    {
        $model = new UniversalActiveRecord();
        $model->setAttribute('voteID', 'AnonPartyTest');

        $model->validateExist('voteID', $this->params());

        $this->assertFalse($model->hasErrors('voteID'));
    }

    public function testMissingReferenceAddsError()
    {
        $model = new UniversalActiveRecord();
        $model->setAttribute('voteID', 'NoSuchVote');

        $model->validateExist('voteID', $this->params());

        $this->assertTrue($model->hasErrors('voteID'));
    }

    public function testEmptyValueIsSkipped()
    {
        $model = new UniversalActiveRecord();
        $model->setAttribute('voteID', '');

        $model->validateExist('voteID', $this->params());

        $this->assertFalse($model->hasErrors('voteID'));
    }

    public function testMissingParamsIsSkipped()
    {
        $model = new UniversalActiveRecord();
        $model->setAttribute('voteID', 'NoSuchVote');

        $model->validateExist('voteID', null);
        $model->validateExist('voteID', ['table' => 'votes']);

        $this->assertFalse($model->hasErrors('voteID'));
    }

    public function testGeneratedRulePassesConfigAsParams()
    {
        $rules = (new TableSchemaRules(['universalForeignKeyExist' => true]))
            ->generateRules(UniversalActiveRecord::getDb()->getTableSchema('candiConfig'));

        $existRules = array_values(array_filter($rules, function ($rule) {
            return ($rule[1] ?? null) === 'validateExist';
        }));

        $this->assertNotEmpty($existRules, 'candiConfig 應有 voteID 外鍵規則');
        $this->assertSame('votes', $existRules[0]['params']['table']);
        $this->assertSame(['voteID' => 'voteID'], $existRules[0]['params']['targetAttribute']);
        $this->assertArrayNotHasKey('current', $existRules[0]);
    }

    public function testInlineValidatorRunsThroughValidate()
    {
        $model = new UniversalActiveRecord();
        $model->setAttribute('voteID', 'NoSuchVote');

        $validator = \yii\validators\Validator::createValidator(
            'validateExist',
            $model,
            ['voteID'],
            ['params' => $this->params()]
        );
        $validator->validateAttributes($model);

        $this->assertTrue($model->hasErrors('voteID'));
    }
}
