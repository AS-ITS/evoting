<?php

namespace app\components;

use app\models\Users;
use OTPHP\InternalClock;
use OTPHP\TOTP;
use Yii;

/**
 * TOTP 驗證服務（Google Authenticator 相容；spomky-labs/otphp ^11）
 */
class TotpService
{
    public static function isEnabledForUser(Users $user): bool
    {
        if (!$user->hasAttribute('totp_enabled') || !$user->hasAttribute('totp_secret')) {
            return false;
        }

        return (string) $user->totp_enabled === '1' && trim((string) $user->totp_secret) !== '';
    }

    public static function verifyForUser(Users $user, string $code): bool
    {
        if (!static::isEnabledForUser($user)) {
            return true;
        }

        $code = preg_replace('/\s+/', '', $code);
        if ($code === null || !preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $totp = static::createFromSecret((string) $user->totp_secret);
        $totp->setLabel($user->cn);
        $totp->setIssuer(Yii::$app->name ?? 'Voting System');

        return $totp->verify($code, null, 1);
    }

    public static function generateSecret(): string
    {
        // 20 bytes（160-bit）相容常見 Authenticator；otphp 11 預設 64 bytes 過長
        return TOTP::generate(new InternalClock(), 20)->getSecret();
    }

    public static function getProvisioningUri(Users $user, string $secret): string
    {
        $totp = static::createFromSecret($secret);
        $totp->setLabel($user->cn);
        $totp->setIssuer(Yii::$app->name ?? 'Voting System');

        return $totp->getProvisioningUri();
    }

    private static function createFromSecret(string $secret): TOTP
    {
        return TOTP::createFromSecret($secret, new InternalClock());
    }
}
