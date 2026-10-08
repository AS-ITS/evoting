<?php

namespace app\tests\fixtures;

class BallotsSelectedFixture extends \yii\test\ActiveFixture {

    public $modelClass = 'app\models\BallotsSelected';
    
    public $dataFile = 'tests/fixtures/data/ballotsSelected.php';

    public $depends = [
        CandiDataFixture::class,
        QuestionsFixture::class,
    ];
}