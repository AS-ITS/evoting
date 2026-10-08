<?php

namespace app\tests\fixtures;

use yii\test\ActiveFixture;

class ConfigFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Config';
    public $dataFile = 'tests/fixtures/data/config.php';
}
