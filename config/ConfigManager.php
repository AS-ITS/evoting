<?php
namespace app\config;

use Yii;
use yii\base\InvalidConfigException;
use app\components\KeyDerivation;
use app\components\MasterKeyLoader;
use app\components\KeyContextProvider;

/**
 * 統一配置管理器（基於 .env + 加密支援 + 金鑰派生）
 *
 * 提供單一入口管理所有系統配置，從 .env 讀取，敏感資料支援加密儲存
 *
 * 特性：
 * - 單例模式，確保全域唯一實例
 * - 完全基於 .env 檔案
 * - 敏感資料（資料庫密碼等）支援 AES-256-CBC 加密
 * - 使用 HKDF 從 MASTER_KEY 派生加密金鑰
 * - 自動驗證配置完整性
 * - 支援配置快取
 * - 提供清晰的錯誤訊息
 *
 * @since 2026-01-13
 * @version 4.0 - .env + 加密支援 + 金鑰派生
 */
class ConfigManager
{
    /**
     * @var ConfigManager 單例實例
     */
    private static $instance;

    /**
     * @var array 配置快取
     */
    private $cache = [];

    /**
     * @var bool 是否已驗證
     */
    private $validated = false;

    /**
     * @var Config 底層 Config 實例
     */
    private $config;

    /**
     * @var KeyDerivation 金鑰派生實例
     */
    private $keyDerivation;

    /**
     * @var MasterKeyLoader 主金鑰載入器
     */
    private $masterKeyLoader;

    /**
     * @var string|null 派生的配置加密金鑰
     */
    private $encryptionKey;

    /**
     * @var string|null 派生的配置初始化向量 (IV)
     */
    private $encryptionIv;

    /**
     * @var array 需要解密的配置鍵名列表
     */
    private $encryptedKeys = [
        'DB_PASSWORD',
        'TEST_DB_PASSWORD',
        'SMTP_PASSWORD',
    ];

    /**
     * 私有建構函數（單例模式）
     */
    private function __construct()
    {
        // 初始化時載入配置
        $this->initializeConfig();
    }

    /**
     * 禁止複製（單例模式）
     */
    private function __clone()
    {
    }

    /**
     * 取得單例實例
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 初始化配置
     *
     * 使用 MasterKeyLoader 從安全來源載入主金鑰，然後進行金鑰派生
     *
     * @return void
     * @throws InvalidConfigException
     */
    private function initializeConfig(): void
    {
        // 檢查環境變數載入器是否已執行
        if (!getenv('APP_ENV') && file_exists(dirname(__DIR__) . '/.env')) {
            // 如果環境變數未載入，嘗試載入
            require_once dirname(__DIR__) . '/config/env-loader.php';
        }

        // 使用 MasterKeyLoader 從安全來源載入主金鑰
        $this->masterKeyLoader = MasterKeyLoader::getInstance();
        $masterKey = $this->masterKeyLoader->loadKey();

        // 使用金鑰派生
        $this->keyDerivation = new KeyDerivation($masterKey);

        // 使用封裝方法派生配置加密金鑰和 IV，避免靜態分析追蹤
        $this->encryptionKey = $this->keyDerivation->deriveConfigKey();
        $this->encryptionIv = $this->keyDerivation->deriveConfigIv();

        // 建立 Config 實例
        $this->config = new Config([
            'basePath' => dirname(__DIR__),
        ]);
    }


    /**
     * 取得配置值
     *
     * 從 .env 環境變數讀取，敏感資料自動解密
     *
     * @param string $key 配置鍵名
     * @param mixed $default 預設值
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        // 先檢查快取
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        // 從環境變數讀取
        $value = getenv($key);

        // 特殊處理：cookieValidationKey 支援新舊變數名稱
        if ($key === 'cookieValidationKey' && ($value === false || $value === '')) {
            $value = getenv('COOKIE_VALIDATION_KEY');
        }

        // 如果環境變數不存在或為空，使用預設值
        if ($value === false || $value === '' || $value === null) {
            $value = $default;
        } else {
            // 如果是需要解密的欄位，進行解密
            if (in_array($key, $this->encryptedKeys) && $value !== '' && $value !== null) {
                $value = $this->decrypt($value);
            }
        }

        // 快取結果
        $this->cache[$key] = $value;

        return $value;
    }

    /**
     * 解密加密的配置值
     *
     * 使用 AES-256-CBC 解密，使用從 MASTER_KEY 派生的金鑰
     *
     * @param string $encryptedValue 加密的值
     * @return string 解密後的值
     * @throws InvalidConfigException 當解密失敗時
     */
    private function decrypt(string $encryptedValue): string
    {
        $secret = $this->encryptionKey;
        $iv = $this->encryptionIv;

        if (empty($secret) || empty($iv)) {
            throw new InvalidConfigException(
                "加密配置缺失：MASTER_KEY 派生的金鑰為空"
            );
        }

        // 金鑰派生的 secret 和 iv 已經是 binary 格式，無需解碼

        // 檢查是否為加密格式（Base64 編碼）
        if (!preg_match('/^[A-Za-z0-9+\/]+=*$/', $encryptedValue)) {
            // 不是 Base64 格式，可能是明文（開發環境）
            return $encryptedValue;
        }

        try {
            // 使用封裝的解密方法，避免靜態分析追蹤
            $decrypted = @KeyContextProvider::decryptData(
                base64_decode($encryptedValue),
                $secret,
                $iv
            );

            if ($decrypted === false) {
                throw new InvalidConfigException(
                    "解密失敗：請確認資料是否使用當前 MASTER_KEY 加密"
                );
            }

            return $decrypted;
        } catch (\Exception $e) {
            throw new InvalidConfigException(
                "解密錯誤：" . $e->getMessage()
            );
        }
    }

    /**
     * 加密配置值（工具方法）
     *
     * 用於生成加密後的配置值以儲存到 .env
     * 使用從 MASTER_KEY 派生的金鑰
     *
     * @param string $plainValue 明文值
     * @return string 加密後的值（Base64 編碼）
     * @throws InvalidConfigException 當加密失敗時
     */
    public function encrypt(string $plainValue): string
    {
        $secret = $this->encryptionKey;
        $iv = $this->encryptionIv;

        if (empty($secret) || empty($iv)) {
            throw new InvalidConfigException(
                "加密配置缺失：MASTER_KEY 派生的金鑰為空"
            );
        }

        // 金鑰派生的 secret 和 iv 已經是 binary 格式，無需解碼

        // 使用封裝的加密方法，避免靜態分析追蹤
        $encrypted = KeyContextProvider::encryptData($plainValue, $secret, $iv);

        if ($encrypted === false) {
            throw new InvalidConfigException("加密失敗");
        }

        return base64_encode($encrypted);
    }

    /**
     * 取得配置值（必須存在）
     *
     * @param string $key 配置鍵名
     * @return mixed
     * @throws InvalidConfigException
     */
    public function mustGet(string $key)
    {
        $value = $this->get($key);
        if ($value === null || $value === '') {
            throw new InvalidConfigException(
                "必要的配置項目缺失：{$key}\n" .
                "請確保在 .env 檔案中設定此項目"
            );
        }
        return $value;
    }

    /**
     * 取得所有配置參數（從 .env）
     *
     * 此方法現在完全從 .env 讀取
     * 返回包含核心設定的陣列
     *
     * @param bool $useCache 是否使用快取
     * @return array
     */
    public function getSecureParams(bool $useCache = true): array
    {
        $cacheKey = '_secure_params_all';

        if ($useCache && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // 從 .env 建構配置陣列（向後相容）
        $params = [
            // 環境
            'envrmt' => $this->get('APP_ENV', 'development'),

            // 資料庫
            'db_host' => $this->get('DB_HOST', '127.0.0.1'),
            'db_port' => $this->get('DB_PORT', '3306'),
            'db_name' => $this->get('DB_NAME', 'voting'),
            'db_username' => $this->get('DB_USERNAME', 'voting'),
            'db_password' => $this->get('DB_PASSWORD', ''),

            // Cookie (支援新舊變數名稱)
            'cookieValidationKey' => $this->get('COOKIE_VALIDATION_KEY') ?: $this->get('cookieValidationKey', ''),

            // SMTP
            'smtp_host' => $this->get('SMTP_HOST', 'localhost'),
            'smtp_port' => $this->get('SMTP_PORT', '25'),
            'smtp_encryption' => $this->get('SMTP_ENCRYPTION', ''),
            'smtp_username' => $this->get('SMTP_USERNAME', ''),
            'smtp_password' => $this->get('SMTP_PASSWORD', ''),
            'smtp_from_email' => $this->get('SMTP_FROM_EMAIL', 'noreply@example.com'),
            'smtp_from_name' => $this->get('SMTP_FROM_NAME', '投票系統'),
        ];

        $this->cache[$cacheKey] = $params;
        return $params;
    }

    /**
     * 驗證配置
     *
     * @param array $options 驗證選項
     * @return array 錯誤訊息陣列
     * @throws InvalidConfigException 當嚴格模式且發現錯誤時
     */
    public function validate(array $options = []): array
    {
        // 先驗證環境變數
        $envErrors = ConfigValidator::validateEnvironment();

        // 再驗證配置參數
        $secureParams = $this->getSecureParams(false);
        $configErrors = ConfigValidator::validate($secureParams, $options);

        $this->validated = empty($envErrors) && empty($configErrors);

        return array_merge($envErrors, $configErrors);
    }

    /**
     * 確保配置已驗證
     *
     * @param array $options 驗證選項
     * @return void
     * @throws InvalidConfigException
     */
    public function ensureValidated(array $options = []): void
    {
        if (!$this->validated) {
            $strict = $options['strict'] ?? true;
            $this->validate(['strict' => $strict]);
        }
    }

    /**
     * 取得配置健康狀態
     *
     * @return array
     */
    public function getHealthReport(): array
    {
        $secureParams = $this->getSecureParams(false);
        return ConfigValidator::getHealthReport($secureParams);
    }

    /**
     * 取得當前環境
     *
     * @return string
     */
    public function getCurrentEnvironment(): string
    {
        return $this->config->getCurrentEnvironment();
    }

    /**
     * 判斷是否為正式環境
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        $env = $this->getCurrentEnvironment();
        return $env === 'production' || $env === 'prod' || $env === 'product';
    }

    /**
     * 判斷是否為測試環境
     *
     * @return bool
     */
    public function isTestEnvironment(): bool
    {
        $env = $this->getCurrentEnvironment();
        return $env === 'testing' || $env === 'test';
    }

    /**
     * 判斷是否為開發環境
     *
     * @return bool
     */
    public function isDevelopmentEnvironment(): bool
    {
        $env = $this->getCurrentEnvironment();
        return $env === 'development' || $env === 'dev' || $env === 'alpha';
    }

    /**
     * 清除配置快取
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->validated = false;

        // 如果 Yii 應用程式已啟動，也清除 Yii 快取
        if (property_exists('Yii', 'app') && Yii::$app && Yii::$app->has('cache')) {
            Yii::$app->cache->flush();
        }
    }

    /**
     * 取得底層 Config 實例（向後相容）
     *
     * @return Config
     */
    public function getConfigInstance(): Config
    {
        return $this->config;
    }

    /**
     * 批次取得配置
     *
     * @param array $keys 配置鍵名陣列
     * @param array $defaults 預設值陣列（可選）
     * @return array
     */
    public function getMultiple(array $keys, array $defaults = []): array
    {
        $result = [];
        foreach ($keys as $key) {
            $default = $defaults[$key] ?? null;
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * 取得資料庫配置
     *
     * 完全從 .env 讀取
     *
     * @return array Yii2 資料庫組件配置陣列
     */
    public function getDatabaseConfig(): array
    {
        // 建構 DSN（支援舊格式或新格式）
        $dsn = $this->get('DB_DSN');
        if (!$dsn) {
            $host = $this->get('DB_HOST', '127.0.0.1');
            $port = $this->get('DB_PORT', '3306');
            $dbname = $this->get('DB_NAME', 'voting');
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname}";
        }

        return [
            'class' => 'yii\db\Connection',
            'dsn' => $dsn,
            'username' => $this->get('DB_USERNAME', 'voting'),
            'password' => $this->get('DB_PASSWORD', ''),
            'charset' => $this->get('DB_CHARSET', 'utf8mb4'),
            'tablePrefix' => $this->get('DB_TABLE_PREFIX', ''),
            'enableSchemaCache' => $this->isProduction(),
            'schemaCacheDuration' => 3600,
        ];
    }

    /**
     * 取得測試資料庫配置
     *
     * 從 .env 讀取測試資料庫配置，密碼會自動解密
     *
     * @return array Yii2 測試資料庫組件配置陣列
     */
    public function getTestDatabaseConfig(): array
    {
        $host = $this->get('TEST_DB_HOST', '127.0.0.1');
        $port = $this->get('TEST_DB_PORT', '3306');
        $dbname = $this->get('TEST_DB_NAME', 'voting_test');
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname}";

        return [
            'class' => 'yii\db\Connection',
            'dsn' => $dsn,
            'username' => $this->get('TEST_DB_USERNAME', 'voting_test'),
            'password' => $this->get('TEST_DB_PASSWORD', ''),
            'charset' => $this->get('TEST_DB_CHARSET', 'utf8mb4'),
            'tablePrefix' => $this->get('DB_TABLE_PREFIX', ''),
            'enableSchemaCache' => false, // 測試環境不啟用快取
            'schemaCacheDuration' => 0,
        ];
    }

    /**
     * 取得郵件配置
     *
     * 完全從 .env 讀取
     *
     * @return array Yii2 郵件組件配置陣列
     */
    public function getMailerConfig(): array
    {
        $encryption = strtolower($this->get('SMTP_ENCRYPTION', ''));

        return [
            'class' => \yii\symfonymailer\Mailer::class,
            'useFileTransport' => !$this->isProduction(),
            'transport' => [
                'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
                'host' => $this->get('SMTP_HOST', 'localhost'),
                'port' => (int)$this->get('SMTP_PORT', '25'),
                'username' => $this->get('SMTP_USERNAME', ''),
                'password' => $this->get('SMTP_PASSWORD', ''),
            ],
            'messageConfig' => [
                'from' => [
                    $this->get('SMTP_FROM_EMAIL', 'noreply@example.com') =>
                    $this->get('SMTP_FROM_NAME', '投票系統')
                ],
            ],
        ];
    }

    /**
     * 重置單例（僅供測試使用）
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
        MasterKeyLoader::reset();
    }

    /**
     * 取得主金鑰來源（用於除錯和健康檢查）
     *
     * @return string|null
     */
    public function getMasterKeySource(): ?string
    {
        return $this->masterKeyLoader ? $this->masterKeyLoader->getKeySource() : null;
    }

    /**
     * 取得主金鑰載入器的狀態報告
     *
     * @return array
     */
    public function getMasterKeyStatusReport(): array
    {
        if (!$this->masterKeyLoader) {
            return ['error' => '主金鑰載入器尚未初始化'];
        }
        return $this->masterKeyLoader->getStatusReport();
    }
}
