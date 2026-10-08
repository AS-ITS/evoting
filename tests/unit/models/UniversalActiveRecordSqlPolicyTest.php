<?php

namespace app\tests\unit\models;

use app\models\UniversalActiveRecord;
use Codeception\Test\Unit;

class UniversalActiveRecordSqlPolicyTest extends Unit
{
    /** @var UniversalActiveRecord */
    private $model;

    protected function _before()
    {
        $this->model = new UniversalActiveRecord();
    }

    public function testAllowsSelectShowDescribeExplain()
    {
        $this->assertTrue($this->model->isAllowedSelectSql('SELECT * FROM users'));
        $this->assertTrue($this->model->isAllowedSelectSql('SHOW TABLES'));
        $this->assertTrue($this->model->isAllowedSelectSql('DESCRIBE users'));
        $this->assertTrue($this->model->isAllowedSelectSql('EXPLAIN SELECT 1'));
    }

    public function testBlocksDmlAndDangerousSelect()
    {
        $this->assertFalse($this->model->isAllowedSelectSql('DELETE FROM users'));
        $this->assertFalse($this->model->isAllowedSelectSql('DROP TABLE users'));
        $this->assertFalse($this->model->isAllowedSelectSql("SELECT * INTO OUTFILE '/tmp/x' FROM users"));
        $this->assertFalse($this->model->isAllowedSelectSql('SELECT LOAD_FILE("/etc/passwd")'));
    }
}
