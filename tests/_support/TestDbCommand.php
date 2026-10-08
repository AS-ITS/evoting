<?php

namespace app\tests\_support;

/**
 * 納管環境測試帳號通常無 ALTER，Yii2 ActiveFixture 的
 * AUTO_INCREMENT reset 會整批炸掉。測試庫改為略過 sequence reset。
 */
class TestDbCommand extends \yii\db\Command
{
    public function resetSequence($table, $value = null)
    {
        return $this->setSql('SELECT 1');
    }
}
