<?php

namespace app\tests\fixtures;

use yii\test\ActiveFixture;

class UsersFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Users';
    public $dataFile = 'tests/fixtures/data/users.php';
}
