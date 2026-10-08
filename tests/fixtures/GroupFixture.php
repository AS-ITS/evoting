<?php

namespace app\tests\fixtures;

use yii\test\ActiveFixture;

/**
 * Group Fixture
 * 群組測試資料
 */
class GroupFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Group';
    public $dataFile = 'tests/fixtures/data/group.php';
}
