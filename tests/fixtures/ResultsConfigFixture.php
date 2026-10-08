<?php

namespace app\tests\fixtures;

class ResultsConfigFixture extends \yii\test\ActiveFixture
{
    public $modelClass = 'app\models\ResultsConfig';

    public $dataFile = 'tests/fixtures/data/resultsConfig.php';

    public $depends = [
        VotesFixture::class,
    ];
}
