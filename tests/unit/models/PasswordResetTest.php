<?php

namespace tests\unit\models;

use app\models\PasswordReset;
use app\models\Users;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\UsersFixture;

class PasswordResetTest extends \Codeception\Test\Unit
{
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
        ]);
    }

    public function testIssueConsumeAndKeepTotp()
    {
        $user = Users::findOne(['cn' => 'test_gm']);
        $this->assertNotNull($user);
        $user->email = 'gm@example.com';
        $user->totp_secret = 'JBSWY3DPEHPK3PXP';
        $user->totp_enabled = '1';
        $user->auth_epoch = 0;
        $this->assertTrue($user->save(false, ['email', 'totp_secret', 'totp_enabled', 'auth_epoch']));

        $token = PasswordReset::issue($user->cn);
        $this->assertSame(64, strlen($token));
        $row = PasswordReset::findValid($token);
        $this->assertNotNull($row);
        $this->assertSame('test_gm', $row->cn);

        $this->assertNull(PasswordReset::findValid('not-a-token'));
        $this->assertNull(PasswordReset::findValid(str_repeat('a', 64)));

        $epoch = (int) $user->auth_epoch;
        $this->assertTrue($user->replacePassword('NewPass123'));
        $user->refresh();
        $this->assertSame($epoch + 1, (int) $user->auth_epoch);
        $this->assertTrue($user->validatePassword('NewPass123'));
        $this->assertSame('1', $user->totp_enabled);
        $this->assertSame('JBSWY3DPEHPK3PXP', $user->totp_secret);

        $row->markUsed();
        $this->assertNull(PasswordReset::findValid($token));
    }

    public function testSecondIssueVoidsPreviousToken()
    {
        $first = PasswordReset::issue('test_gm');
        $second = PasswordReset::issue('test_gm');
        $this->assertNull(PasswordReset::findValid($first));
        $this->assertNotNull(PasswordReset::findValid($second));
    }
}
