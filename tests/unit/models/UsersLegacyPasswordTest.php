<?php

namespace app\tests\unit\models;

use app\models\Users;
use Codeception\Test\Unit;

class UsersLegacyPasswordTest extends Unit
{
    private ?string $originalFlag = null;

    protected function _before()
    {
        $this->originalFlag = getenv('DISABLE_LEGACY_ADMIN_PASSWORD') ?: null;
    }

    protected function _after()
    {
        if ($this->originalFlag === null) {
            putenv('DISABLE_LEGACY_ADMIN_PASSWORD');
        } else {
            putenv('DISABLE_LEGACY_ADMIN_PASSWORD=' . $this->originalFlag);
        }
    }

    public function testLooksLikePasswordHash()
    {
        $this->assertTrue(Users::looksLikePasswordHash('$2y$10$abcdefghijklmnopqrstuv'));
        $this->assertFalse(Users::looksLikePasswordHash('base64legacyblob'));
    }

    public function testLegacyDisabledSkipsAesPath()
    {
        putenv('DISABLE_LEGACY_ADMIN_PASSWORD=true');
        $user = new Users();
        $user->password = 'not-a-bcrypt-hash';

        $this->assertFalse($user->validatePassword('anything'));
    }

    /**
     * 迴歸：ConfigValidator 在 DISABLE_LEGACY_ADMIN_PASSWORD=true 時會呼叫此方法；
     * 修正前 select 不存在的 username 欄位，查詢直接失敗。
     */
    public function testCountLegacyPasswordUsersCountsNonHashPasswords()
    {
        $db = Users::getDb();
        $cn = 'ut_legacy_pw';
        $db->createCommand()->delete(Users::tableName(), ['cn' => $cn])->execute();

        $before = Users::countLegacyPasswordUsers();
        try {
            $db->createCommand()->insert(Users::tableName(), [
                'cn' => $cn,
                'name' => 'legacy',
                'password' => 'base64legacyblob',
            ])->execute();

            $this->assertSame($before + 1, Users::countLegacyPasswordUsers());
        } finally {
            $db->createCommand()->delete(Users::tableName(), ['cn' => $cn])->execute();
        }
    }
}
