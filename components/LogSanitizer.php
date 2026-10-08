<?php

namespace app\components;

use yii\helpers\Json;

/**
 * 操作日誌 context 敏感欄位脫敏
 */
class LogSanitizer
{
    /** @var string[] */
    private const SENSITIVE_KEYS = [
        'password',
        'password_plain',
        'passwd',
        'secret',
        'token',
        'api_key',
        'apikey',
        'cookievalidationkey',
        'cookie_validation_key',
        'master_key',
        'voting_master_key',
        'totp_secret',
        'smtp_password',
        'db_password',
        'test_db_password',
    ];

    /** @var string[] */
    private const ENV_LINE_PATTERNS = [
        '/\b(MASTER_KEY|VOTING_MASTER_KEY|COOKIE_VALIDATION_KEY|DB_PASSWORD|TEST_DB_PASSWORD|SMTP_PASSWORD)\s*=\s*[^\s"\']+/i',
    ];

    /**
     * @param array|string $context
     * @return string
     */
    public static function sanitizeContext($context): string
    {
        if (is_array($context)) {
            return Json::encode(static::redactArray($context), 336);
        }

        if (!is_string($context) || $context === '') {
            return (string) $context;
        }

        try {
            $decoded = Json::decode($context, true);
        } catch (\Throwable $e) {
            return static::sanitizeString($context);
        }

        if (!is_array($decoded)) {
            return static::sanitizeString($context);
        }

        return Json::encode(static::redactArray($decoded), 336);
    }

    /**
     * @param array $data
     * @return array
     */
    public static function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = static::redactArray($value);
                continue;
            }

            if (static::isSensitiveKey((string) $key)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }

    public static function sanitizeString(string $context): string
    {
        foreach (self::ENV_LINE_PATTERNS as $pattern) {
            $context = preg_replace($pattern, '$1=[REDACTED]', $context) ?? $context;
        }

        return $context;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));
        $compact = str_replace('_', '', $normalized);

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if ($normalized === $sensitive || $compact === str_replace('_', '', $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
