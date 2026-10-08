<?php

namespace app\tests\fixtures;

use yii\test\ActiveFixture;

/**
 * GroupMember Fixture
 * 群組成員測試資料
 */
class GroupMemberFixture extends ActiveFixture
{
    public $modelClass = 'app\models\GroupMember';
    public $dataFile = 'tests/fixtures/data/groupMember.php';

    public $depends = [
        'app\tests\fixtures\GroupFixture',
    ];
}
