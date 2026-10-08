<?php

namespace app\tests\fixtures;

use yii\test\ActiveFixture;

class VotesFixture extends ActiveFixture {

    public $modelClass = 'app\models\Votes';
    public $dataFile = 'tests/fixtures/data/votes.php';

}