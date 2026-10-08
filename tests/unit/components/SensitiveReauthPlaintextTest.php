<?php

namespace app\tests\unit\components;

use Yii;
use app\components\SensitiveReauth;

class SensitiveReauthPlaintextTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        Yii::$app->session->remove('sensitive.plaintext_unlock_until');
        putenv('SENSITIVE_REAUTH=true');
    }

    protected function _after()
    {
        Yii::$app->session->remove('sensitive.plaintext_unlock_until');
    }

    public function testPlaintextLockedByDefaultWhenReauthRequired()
    {
        $this->assertTrue(SensitiveReauth::isRequired());
        $this->assertFalse(SensitiveReauth::isPlaintextUnlocked());
        $this->assertSame(0, SensitiveReauth::plaintextUnlockRemainingSeconds());
    }

    public function testGrantAndRevokePlaintextUnlock()
    {
        SensitiveReauth::grantPlaintextUnlock();
        $this->assertTrue(SensitiveReauth::isPlaintextUnlocked());
        $this->assertGreaterThan(0, SensitiveReauth::plaintextUnlockRemainingSeconds());

        SensitiveReauth::revokePlaintextUnlock();
        $this->assertFalse(SensitiveReauth::isPlaintextUnlocked());
    }

    public function testPlaintextAlwaysUnlockedWhenReauthDisabled()
    {
        putenv('SENSITIVE_REAUTH=false');
        $this->assertFalse(SensitiveReauth::isRequired());
        $this->assertTrue(SensitiveReauth::isPlaintextUnlocked());
        $this->assertNull(SensitiveReauth::plaintextUnlockRemainingSeconds());
        putenv('SENSITIVE_REAUTH=true');
    }
}
