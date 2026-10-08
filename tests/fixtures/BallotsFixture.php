<?php

namespace app\tests\fixtures;

class BallotsFixture extends \yii\test\ActiveFixture {

    public $modelClass = 'app\models\Ballots';

    public $dataFile = 'tests/fixtures/data/ballots.php';

    /**
     * ballots 為複合主鍵 (voteID, ballotID)，auto_increment 在 ballotID。
     * Yii2 ActiveFixture 預設以第一個主鍵欄位 (voteID，字串) 計算 MAX+1，
     * 在 PHP 8 會觸發 TypeError: Unsupported operand types: string + int。
     */
    public function load()
    {
        $this->data = [];
        $table = $this->getTableSchema();
        foreach ($this->getData() as $alias => $row) {
            $primaryKeys = $this->db->schema->insert($table->fullName, $row);
            $this->data[$alias] = array_merge($row, $primaryKeys);
        }
        if ($table->sequenceName !== null) {
            $maxBallotId = (int) $this->db->createCommand(
                'SELECT MAX([[ballotID]]) FROM ' . $table->fullName
            )->queryScalar();
            $this->db->createCommand()->executeResetSequence($table->fullName, $maxBallotId + 1);
        }
    }
}
