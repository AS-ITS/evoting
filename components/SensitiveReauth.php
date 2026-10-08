<?php

namespace app\components;

use app\models\Users;
use Yii;

/**
 * 高敏感操作二次驗證（管理員密碼；若已啟用 TOTP 則一併驗證）
 */
class SensitiveReauth
{
    private const SESSION_PLAINTEXT_UNLOCK_UNTIL = 'sensitive.plaintext_unlock_until';

    public static function isRequired(): bool
    {
        return filter_var(getenv('SENSITIVE_REAUTH') ?: 'true', FILTER_VALIDATE_BOOLEAN);
    }

    public static function plaintextUnlockTtlSeconds(): int
    {
        $ttl = (int) (getenv('SENSITIVE_PLAINTEXT_TTL') ?: 900);

        return max(60, $ttl);
    }

    /**
     * 列表明文顯示是否已解鎖（SENSITIVE_REAUTH=false 時視為永久解鎖）
     */
    public static function isPlaintextUnlocked(): bool
    {
        if (!static::isRequired()) {
            return true;
        }

        $until = Yii::$app->session->get(static::SESSION_PLAINTEXT_UNLOCK_UNTIL, 0);

        return is_int($until) && time() < $until;
    }

    /**
     * @return int|null 剩餘秒數；未解鎖為 0；SENSITIVE_REAUTH=false 為 null
     */
    public static function plaintextUnlockRemainingSeconds(): ?int
    {
        if (!static::isRequired()) {
            return null;
        }

        $until = Yii::$app->session->get(static::SESSION_PLAINTEXT_UNLOCK_UNTIL, 0);
        if (!is_int($until) || time() >= $until) {
            return 0;
        }

        return $until - time();
    }

    public static function grantPlaintextUnlock(): void
    {
        Yii::$app->session->set(
            static::SESSION_PLAINTEXT_UNLOCK_UNTIL,
            time() + static::plaintextUnlockTtlSeconds()
        );
    }

    public static function revokePlaintextUnlock(): void
    {
        Yii::$app->session->remove(static::SESSION_PLAINTEXT_UNLOCK_UNTIL);
    }

    /**
     * @param string|null $password 管理員目前密碼
     * @param string|null $totpCode 6 位 TOTP（使用者已啟用時必填）
     */
    public static function verify(?string $password, ?string $totpCode = null): bool
    {
        if (!static::isRequired()) {
            return true;
        }

        if ($password === null || $password === '') {
            return false;
        }

        if (Yii::$app->user->isGuest) {
            return false;
        }

        /** @var Users|null $user */
        $user = Users::findOne(Yii::$app->user->id);
        if ($user === null || !$user->validatePassword($password)) {
            return false;
        }

        if (TotpService::isEnabledForUser($user)) {
            return TotpService::verifyForUser($user, (string) $totpCode);
        }

        return true;
    }

    public static function failureMessage(Users $user): string
    {
        if (TotpService::isEnabledForUser($user)) {
            return Yii::t('app', '請輸入正確的管理員密碼與驗證器代碼。');
        }

        return Yii::t('app', '請輸入正確的管理員密碼以確認此操作。');
    }
}
