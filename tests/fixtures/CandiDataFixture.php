<?php

namespace app\tests\fixtures;

class CandiDataFixture extends \yii\test\ActiveFixture {

    public $modelClass = 'app\models\CandiData';
    
    public $dataFile = 'tests/fixtures/data/candiData.php';

    public $depends = [
        VotesFixture::class,
        QuestionsFixture::class,
    ];

    /**
     * 測試庫帳號無 ALTER，AUTO_INCREMENT 不會歸零。
     * 寫死 id 才能對上 ballotsSelected 的 1–56。
     */
    protected function getData()
    {
        $data = parent::getData();
        $id = 1;
        foreach ($data as $alias => $row) {
            if (!isset($row['id'])) {
                $data[$alias]['id'] = $id++;
            }
        }
        return $data;
    }
}