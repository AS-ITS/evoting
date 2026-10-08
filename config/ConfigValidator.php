<?php
namespace app\config;

use Yii;
use yii\base\InvalidConfigException;

/**
 * 配置驗證器（完全基於 .env）
 *
 * 用於驗證系統配置的完整性與正確性，確保所有必要的配置參數都已正確設定。
 * 完全基於 .env 環境變數 。
 *
 * @since 2026-01-13
 * @version 2.1 (2026-01-21)
 */
class ConfigValidator
{
    /**
     * 驗證配置陣列
     *
     * @param array $config 配置陣列
     * @param array $options 驗證選項
     *   - bool strict: 是否嚴格模式（預設 true，發現錯誤會拋出例外）
     *   - bool checkEncryption: 是否檢查加密相關設定（預設 true）
     * @return array 錯誤訊息陣列（空陣列表示無錯誤）
     * @throws InvalidConfigException 當 strict 模式且發現錯誤時
     */
    public static function validate(array $config, array $options = []): array
    {
        $strict = $options['strict'] ?? true;
        $checkEncryption = $options['checkEncryption'] ?? true;
        $errors = [];

        // 1. 檢查必要欄位
        $errors = array_merge($errors, self::validateRequiredFields($config));

        // 2. 檢查加密相關設定
        if ($checkEncryption) {
            $errors = array_merge($errors, self::validateEncryption());
        }

        // 2b. 正式環境：敏感 env 須為加密 Base64（Phase 2.2）
        if (self::isProductionEnvironment()) {
            $errors = array_merge($errors, self::validateProductionEncryptedEnvVars());
            $errors = array_merge($errors, self::validateProductionAllowedHosts());
            $errors = array_merge($errors, self::validateProductionSaDbTools());
            $errors = array_merge($errors, self::validateProductionLegacyAdminPassword());
        }

        // 3. 檢查資料庫配置
        $errors = array_merge($errors, self::validateDatabaseConfig($config));

        // 4. 檢查 Cookie 驗證金鑰
        $errors = array_merge($errors, self::validateCookieKey($config));

        // 嚴格模式：發現錯誤立即拋出例外
        if ($strict && !empty($errors)) {
            throw new InvalidConfigException(
                '配置驗證失敗：' . "\n" . implode("\n", $errors)
            );
        }

        return $errors;
    }

    /**
     * 是否為正式環境（依 APP_ENV）
     */
    public static function isProductionEnvironment(): bool
    {
        $appEnv = strtolower((string) (getenv('APP_ENV') ?: ''));
        return in_array($appEnv, ['production', 'prod', 'product'], true);
    }

    /**
     * 正式環境：DB_PASSWORD 等須為 ConfigManager 加密後的 Base64
     *
     * @return array 錯誤訊息陣列
     */
    protected static function validateProductionEncryptedEnvVars(): array
    {
        $errors = [];
        $encryptedFields = [
            'DB_PASSWORD' => '資料庫密碼',
        ];

        foreach ($encryptedFields as $key => $label) {
            $raw = getenv($key);
            if ($raw === false || $raw === '') {
                continue;
            }
            if (!self::looksLikeEncryptedValue($raw)) {
                $errors[] = "正式環境 {$key} ({$label}) 必須為加密後的 Base64 值（請執行 yii encrypt/db-password）";
            }
        }

        return $errors;
    }

    /**
     * 值是否像 ConfigManager 加密輸出（Base64，解碼後具備最小長度）
     */
    protected static function looksLikeEncryptedValue(string $value): bool
    {
        if (!preg_match('/^[A-Za-z0-9+\/]+=*$/', $value)) {
            return false;
        }

        $decoded = base64_decode($value, true);
        return $decoded !== false && strlen($decoded) >= 16;
    }

    /**
     * 正式環境：HostControl 白名單
     *
     * @return array 錯誤訊息陣列
     */
    protected static function validateProductionAllowedHosts(): array
    {
        $raw = getenv('ALLOWED_HOSTS');
        if ($raw === false || trim($raw) === '') {
            return ['正式環境必須設定 ALLOWED_HOSTS（逗號分隔 FQDN）'];
        }

        $hosts = array_filter(array_map('trim', explode(',', $raw)));
        if (empty($hosts)) {
            return ['ALLOWED_HOSTS 不可為空'];
        }

        $insecure = ['*', 'localhost', '127.0.0.1', '0.0.0.0'];
        foreach ($hosts as $host) {
            if (in_array(strtolower($host), $insecure, true)) {
                return ["正式環境 ALLOWED_HOSTS 不可包含不安全的 host：{$host}"];
            }
        }

        return [];
    }

    /**
     * 正式環境禁止啟用 SA 資料庫工具
     *
     * @return array
     */
    protected static function validateProductionSaDbTools(): array
    {
        if (filter_var(getenv('SA_DB_TOOLS_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN)) {
            return ['正式環境不得設定 SA_DB_TOOLS_ENABLED=true'];
        }

        if (filter_var(getenv('SA_DB_TOOLS_WRITABLE') ?: 'false', FILTER_VALIDATE_BOOLEAN)) {
            return ['正式環境不得設定 SA_DB_TOOLS_WRITABLE=true'];
        }

        return [];
    }

    /**
     * 正式環境應關閉舊制 AES 管理員密碼（遷移完成後）
     *
     * @return array
     */
    protected static function validateProductionLegacyAdminPassword(): array
    {
        if (!\app\models\Users::isLegacyAdminPasswordDisabled()) {
            return [];
        }

        $legacyCount = \app\models\Users::countLegacyPasswordUsers();
        if ($legacyCount > 0) {
            return ["DISABLE_LEGACY_ADMIN_PASSWORD=true 但仍有 {$legacyCount} 個管理員使用舊制 AES 密碼，請先重設密碼或執行 php yii migrate-passwords/admin-legacy-report"];
        }

        return [];
    }

    /**
     * 驗證環境變數配置（完全基於 .env）
     *
     * @return array 錯誤訊息陣列
     */
    public static function validateEnvironment(): array
    {
        $errors = [];

        // 檢查資料庫配置（支援 DB_DSN 或個別參數）
        $dbDsn = getenv('DB_DSN');
        if ($dbDsn === false || $dbDsn === '') {
            // 沒有 DB_DSN，檢查個別參數
            $required = [
                'DB_HOST' => '資料庫主機',
                'DB_NAME' => '資料庫名稱',
                'DB_USERNAME' => '資料庫使用者名稱',
            ];

            foreach ($required as $key => $description) {
                $value = getenv($key);
                if ($value === false || $value === '' || $value === null) {
                    $errors[] = "環境變數缺失或為空：{$key} ({$description})";
                }
            }
        } else {
            // 有 DB_DSN，檢查使用者名稱和密碼
            if (!getenv('DB_USERNAME')) {
                $errors[] = "環境變數缺失或為空：DB_USERNAME (資料庫使用者名稱)";
            }
        }

        // 資料庫密碼可以為空（本地開發環境）
        // 但在正式環境應該要有
        $appEnv = getenv('APP_ENV');
        $dbPassword = getenv('DB_PASSWORD');
        if ($appEnv === 'production' || $appEnv === 'product') {
            if ($dbPassword === false || $dbPassword === '') {
                $errors[] = "正式環境必須設定 DB_PASSWORD";
            }
        }

        // Cookie 驗證金鑰是必需的（支援新舊變數名稱）
        $cookieKey = getenv('COOKIE_VALIDATION_KEY') ?: getenv('cookieValidationKey');
        if ($cookieKey === false || $cookieKey === '') {
            $errors[] = "環境變數缺失或為空：COOKIE_VALIDATION_KEY (Cookie 驗證金鑰)";
        }

        return $errors;
    }

    /**
     * 驗證必要欄位
     *
     * @param array $config 配置陣列
     * @return array 錯誤訊息陣列
     */
    protected static function validateRequiredFields(array $config): array
    {
        $errors = [];
        $required = [
            'db_password' => '資料庫密碼',
            'cookieValidationKey' => 'Cookie 驗證金鑰',
        ];

        foreach ($required as $field => $description) {
            if (!isset($config[$field]) || $config[$field] === '' || $config[$field] === null) {
                $errors[] = "必要欄位缺失或為空：{$field} ({$description})";
            }
        }

        return $errors;
    }

    /**
     * 驗證加密相關設定
     *
     * 驗證用於解密 .env 中敏感資料的加密金鑰
     * 必須使用 MASTER_KEY 進行金鑰派生 (HKDF)
     *
     * @return array 錯誤訊息陣列
     */
    protected static function validateEncryption(): array
    {
        $errors = [];

        // 檢查 MASTER_KEY
        $masterKey = getenv('MASTER_KEY');
        if (!$masterKey) {
            $errors[] = '環境變數 MASTER_KEY 未設定（用於解密 .env 中的敏感資料）' . "\n" .
                        '請使用以下命令生成：php -r "echo base64_encode(random_bytes(64)) . PHP_EOL;"';
        } else {
            // 驗證 MASTER_KEY 格式
            $decoded = base64_decode($masterKey, true);
            if ($decoded === false) {
                $errors[] = 'MASTER_KEY 不是有效的 Base64 編碼';
            } elseif (strlen($decoded) < 32) {
                $errors[] = sprintf(
                    'MASTER_KEY 解碼後長度不足（當前：%d bytes，建議：至少 32 bytes）',
                    strlen($decoded)
                );
            }
        }

        // 檢查 OpenSSL 擴充是否可用
        if (!extension_loaded('openssl')) {
            $errors[] = 'PHP OpenSSL 擴充未啟用，無法進行加密解密操作';
        }

        return $errors;
    }

    /**
     * 驗證資料庫配置
     *
     * @param array $config 配置陣列
     * @return array 錯誤訊息陣列
     */
    protected static function validateDatabaseConfig(array $config): array
    {
        $errors = [];

        // 檢查資料庫密碼
        if (isset($config['db_password'])) {
            $password = $config['db_password'];

            // 檢查密碼長度
            if (strlen($password) < 8) {
                $errors[] = '資料庫密碼長度過短（建議至少 8 字元）';
            }

            // 檢查是否為常見弱密碼
            $weakPasswords = ['password', '12345678', 'admin123', 'root123'];
            if (in_array(strtolower($password), $weakPasswords)) {
                $errors[] = '資料庫密碼過於簡單，建議使用更複雜的密碼';
            }
        }

        // 檢查資料庫主機設定
        if (isset($config['db_host'])) {
            $host = $config['db_host'];
            if (empty($host)) {
                $errors[] = '資料庫主機未設定';
            }
        }

        // 檢查資料庫名稱
        if (isset($config['db_name'])) {
            $dbName = $config['db_name'];
            if (empty($dbName)) {
                $errors[] = '資料庫名稱未設定';
            }
        }

        return $errors;
    }

    /**
     * 驗證 Cookie 驗證金鑰
     *
     * @param array $config 配置陣列
     * @return array 錯誤訊息陣列
     */
    protected static function validateCookieKey(array $config): array
    {
        $errors = [];

        if (!isset($config['cookieValidationKey'])) {
            return $errors;
        }

        $key = $config['cookieValidationKey'];

        // 檢查長度
        if (strlen($key) < 32) {
            $errors[] = sprintf(
                'cookieValidationKey 長度不足（當前：%d，建議：至少 32）',
                strlen($key)
            );
        }

        // 檢查是否為範例值或明顯的不安全值
        $insecureKeys = [
            'your-secret-cookie-key-here',
            'change-this-key',
            'example-key',
            '12345678901234567890123456789012',
        ];

        if (in_array($key, $insecureKeys)) {
            $errors[] = 'cookieValidationKey 使用了不安全的範例值，請更換為隨機生成的金鑰';
        }

        return $errors;
    }

    /**
     * 快速驗證（寬鬆模式，不拋出例外）
     *
     * @param array $config 配置陣列
     * @return bool 是否通過驗證
     */
    public static function quickValidate(array $config): bool
    {
        $errors = self::validate($config, ['strict' => false]);
        return empty($errors);
    }

    /**
     * 取得配置健康狀態報告
     *
     * @param array $config 配置陣列
     * @return array 包含 status, errors, warnings 的陣列
     */
    public static function getHealthReport(array $config): array
    {
        $errors = self::validate($config, ['strict' => false]);
        $warnings = [];

        // 檢查可選但建議設定的項目
        if (!isset($config['smtp_password']) || empty($config['smtp_password'])) {
            $warnings[] = 'SMTP 密碼未設定，郵件功能可能無法使用';
        }

        // 判定整體健康狀態
        $status = 'healthy';
        if (!empty($errors)) {
            $status = 'critical';
        } elseif (!empty($warnings)) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 將驗證結果記錄到日誌
     *
     * @param array $config 配置陣列
     * @return void
     */
    public static function logValidationResult(array $config): void
    {
        $report = self::getHealthReport($config);

        switch ($report['status']) {
            case 'critical':
                Yii::error(
                    '配置驗證失敗：' . implode('; ', $report['errors']),
                    __CLASS__
                );
                break;
            case 'warning':
                Yii::warning(
                    '配置存在警告：' . implode('; ', $report['warnings']),
                    __CLASS__
                );
                break;
            case 'healthy':
                Yii::info('配置驗證通過', __CLASS__);
                break;
        }
    }
}
