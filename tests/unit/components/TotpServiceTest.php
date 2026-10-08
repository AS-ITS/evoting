<?php

namespace app\tests\unit\components;

use app\components\TotpService;
use app\models\Users;
use Codeception\Test\Unit;
use OTPHP\InternalClock;
use OTPHP\TOTP;

class TotpServiceTest extends Unit
{
    public function testGenerateAndVerifyTotp()
    {
        $secret = TotpService::generateSecret();
        $this->assertNotEmpty($secret);

        $totp = TOTP::createFromSecret($secret, new InternalClock());
        $code = $totp->now();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue($totp->verify($code, null, 1));
    }

    public function testIsEnabledForUserRequiresColumnsAndFlag()
    {
        $user = new Users();
        if (!$user->hasAttribute('totp_enabled')) {
            $this->markTestSkipped('users.totp_enabled column not migrated');
        }

        $user->totp_enabled = '0';
        $user->totp_secret = 'JBSWY3DPEHPK3PXP';
        $this->assertFalse(TotpService::isEnabledForUser($user));

        $user->totp_enabled = '1';
        $this->assertTrue(TotpService::isEnabledForUser($user));
    }
}
