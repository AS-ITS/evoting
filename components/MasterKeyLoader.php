<?php
/**
 * 主金鑰載入器
 *
 * 實作分離式金鑰管理架構，從安全的外部來源載入主金鑰。
 * 金鑰與加密資料完全分離，即使 .env 外洩也無法解密。
 *
 * 載入優先順序：
 * 1. 系統環境變數 VOTING_MASTER_KEY（最高優先權，適合 Docker/K8s）
 * 2. 外部金鑰檔案（Linux: /etc/voting/master.key, Windows: C:\ProgramData\voting\master.key）
 * 3. 環境變數 MASTER_KEY（僅限開發環境，正式環境會記錄警告）
 *
 * @since 2026-01-16
 * @version 1.0
 */

namespace app\components;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;

class MasterKeyLoader extends BaseObject
{
    /**
     * 系統環境變數名稱（最高優先權）
     */
    const ENV_VAR_NAME = 'VOTING_MASTER_KEY';

    /**
     * 備用環境變數名稱（向後相容，僅開發環境）
     */
    const ENV_VAR_FALLBACK = 'MASTER_KEY';

    /**
     * Linux 金鑰檔案路徑
     */
    const KEY_FILE_LINUX = '/etc/voting/master.key';

    /**
     * Windows 金鑰檔案路徑
     */
    const KEY_FILE_WINDOWS = 'C:\\ProgramData\\voting\\master.key';

    /**
     * @var MasterKeyLoader 單例實例
     */
    private static $instance;

    /**
     * @var string|null 快取的金鑰
     */
    private $cachedKey;

    /**
     * @var string|null 金鑰來源（用於除錯）
     */
    private $keySource;

    /**
     * @var bool 是否允許開發環境使用 .env 中的 MASTER_KEY
     */
    public $allowEnvFallback = true;

    /**
     * @var string|null 自訂金鑰檔案路徑（可透過環境變數 VOTING_KEY_FILE 設定）
     */
    public $customKeyFile;

    /**
     * 私有建構函數（單例模式）
     */
    private function __construct($config = [])
    {
        parent::__construct($config);
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
     * 載入主金鑰
     *
     * 依優先順序從不同來源載入金鑰：
     * 1. 系統環境變數 VOTING_MASTER_KEY
     * 2. 外部金鑰檔案
     * 3. 環境變數 MASTER_KEY（僅開發環境）
     *
     * @return string Base64 編碼的主金鑰
     * @throws InvalidConfigException 當無法載入金鑰時
     */
    public function loadKey(): string
    {
        // 使用快取
        if ($this->cachedKey !== null) {
            return $this->cachedKey;
        }

        // 1. 系統環境變數（最高優先權）
        $key = $this->loadFromSystemEnv();
        if ($key !== null) {
            $this->cachedKey = $key;
            $this->keySource = 'system_env:' . self::ENV_VAR_NAME;
            return $key;
        }

        // 2. 外部金鑰檔案
        $key = $this->loadFromKeyFile();
        if ($key !== null) {
            $this->cachedKey = $key;
            return $key;
        }

        // 3. .env 中的 MASTER_KEY（僅非正式環境）
        if ($this->allowEnvFallback) {
            $key = $this->loadFromEnvFallback();
            if ($key !== null) {
                if ($this->isProduction()) {
                    throw new InvalidConfigException(
                        '正式環境禁止使用 .env 中的 MASTER_KEY。' .
                        '請設定系統環境變數 VOTING_MASTER_KEY 或使用外部金鑰檔案。'
                    );
                }

                $this->cachedKey = $key;
                $this->keySource = 'env_fallback:' . self::ENV_VAR_FALLBACK;

                return $key;
            }
        }

        // 無法載入金鑰
        throw new InvalidConfigException($this->getErrorMessage());
    }

    /**
     * 從系統環境變數載入金鑰
     *
     * @return string|null
     */
    private function loadFromSystemEnv(): ?string
    {
        $key = getenv(self::ENV_VAR_NAME);
        if ($key !== false && $key !== '') {
            return $key;
        }
        return null;
    }

    /**
     * 從外部金鑰檔案載入
     *
     * @return string|null
     */
    private function loadFromKeyFile(): ?string
    {
        // 檢查自訂金鑰檔案路徑（透過環境變數）
        $customPath = $this->customKeyFile ?: getenv('VOTING_KEY_FILE');
        if ($customPath && $customPath !== '') {
            $key = $this->readKeyFile($customPath);
            if ($key !== null) {
                $this->keySource = 'key_file:' . $customPath;
                return $key;
            }
        }

        // 根據作業系統選擇預設路徑
        $keyFile = $this->isWindows() ? self::KEY_FILE_WINDOWS : self::KEY_FILE_LINUX;
        $key = $this->readKeyFile($keyFile);
        if ($key !== null) {
            $this->keySource = 'key_file:' . $keyFile;
            return $key;
        }

        return null;
    }

    /**
     * 讀取金鑰檔案
     *
     * @param string $path 檔案路徑
     * @return string|null
     */
    private function readKeyFile(string $path): ?string
    {
        if (!file_exists($path)) {
            return null;
        }

        if (!is_readable($path)) {
            $this->logWarning("金鑰檔案存在但無法讀取: {$path}");
            return null;
        }

        // 檢查檔案權限（僅 Linux）
        if (!$this->isWindows()) {
            $perms = fileperms($path) & 0777;
            if ($perms > 0600) {
                $this->logWarning(
                    "金鑰檔案權限過於寬鬆（{$path}）：" . sprintf('%04o', $perms) .
                    "，建議設定為 0600"
                );
            }
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return null;
        }

        // 移除換行和空白
        $key = trim($content);
        if ($key === '') {
            return null;
        }

        return $key;
    }

    /**
     * 從 .env 的 MASTER_KEY 載入（備用，僅開發環境建議使用）
     *
     * @return string|null
     */
    private function loadFromEnvFallback(): ?string
    {
        $key = getenv(self::ENV_VAR_FALLBACK);
        if ($key !== false && $key !== '') {
            return $key;
        }
        return null;
    }

    /**
     * 取得金鑰來源（用於除錯和日誌）
     *
     * @return string|null
     */
    public function getKeySource(): ?string
    {
        return $this->keySource;
    }

    /**
     * 驗證金鑰是否有效
     *
     * @param string|null $key 要驗證的金鑰，null 則驗證已載入的金鑰
     * @return bool
     */
    public function validateKey(?string $key = null): bool
    {
        if ($key === null) {
            try {
                $key = $this->loadKey();
            } catch (InvalidConfigException $e) {
                return false;
            }
        }

        // 嘗試 Base64 解碼
        $decoded = base64_decode($key, true);
        if ($decoded === false) {
            // 不是有效的 Base64
            return strlen($key) >= 32;
        }

        // 驗證解碼後的長度（建議至少 32 bytes）
        return strlen($decoded) >= 32;
    }

    /**
     * 取得 KeyDerivation 實例
     *
     * @return KeyDerivation
     * @throws InvalidConfigException
     */
    public function getKeyDerivation(): KeyDerivation
    {
        return new KeyDerivation($this->loadKey());
    }

    /**
     * 判斷是否為正式環境
     *
     * @return bool
     */
    private function isProduction(): bool
    {
        $env = strtolower(getenv('APP_ENV') ?: '');
        return in_array($env, ['production', 'prod', 'product']);
    }

    /**
     * 判斷是否為 Windows 系統
     *
     * @return bool
     */
    private function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * 記錄警告訊息
     *
     * @param string $message
     */
    private function logWarning(string $message): void
    {
        if (class_exists('Yii') && Yii::$app !== null) {
            Yii::warning($message, __CLASS__);
        } else {
            error_log('[MasterKeyLoader Warning] ' . $message);
        }
    }

    /**
     * 取得錯誤訊息
     *
     * @return string
     */
    private function getErrorMessage(): string
    {
        $keyFile = $this->isWindows() ? self::KEY_FILE_WINDOWS : self::KEY_FILE_LINUX;

        return "無法載入主金鑰 (MASTER_KEY)。\n\n" .
            "請使用以下任一方式設定金鑰：\n\n" .
            "1. 設定系統環境變數（建議用於正式環境）：\n" .
            "   export " . self::ENV_VAR_NAME . "=\"您的金鑰\"\n\n" .
            "2. 建立金鑰檔案：\n" .
            "   echo \"您的金鑰\" > {$keyFile}\n" .
            "   chmod 600 {$keyFile}\n\n" .
            "3. 在 .env 設定（僅限開發環境）：\n" .
            "   MASTER_KEY=您的金鑰\n\n" .
            "生成新金鑰：\n" .
            "   php -r \"echo base64_encode(random_bytes(64)) . PHP_EOL;\"";
    }

    /**
     * 清除快取
     */
    public function clearCache(): void
    {
        $this->cachedKey = null;
        $this->keySource = null;
    }

    /**
     * 重置單例（僅供測試使用）
     */
    public static function reset(): void
    {
        if (self::$instance) {
            self::$instance->clearCache();
        }
        self::$instance = null;
    }

    /**
     * 取得金鑰設定狀態報告
     *
     * @return array
     */
    public function getStatusReport(): array
    {
        $keyFile = $this->isWindows() ? self::KEY_FILE_WINDOWS : self::KEY_FILE_LINUX;
        $customKeyFile = $this->customKeyFile ?: getenv('VOTING_KEY_FILE');

        return [
            'system_env' => [
                'name' => self::ENV_VAR_NAME,
                'configured' => getenv(self::ENV_VAR_NAME) !== false && getenv(self::ENV_VAR_NAME) !== '',
            ],
            'key_file' => [
                'path' => $keyFile,
                'exists' => file_exists($keyFile),
                'readable' => file_exists($keyFile) && is_readable($keyFile),
            ],
            'custom_key_file' => [
                'path' => $customKeyFile ?: '(未設定)',
                'exists' => $customKeyFile ? file_exists($customKeyFile) : false,
                'readable' => $customKeyFile ? (file_exists($customKeyFile) && is_readable($customKeyFile)) : false,
            ],
            'env_fallback' => [
                'name' => self::ENV_VAR_FALLBACK,
                'configured' => getenv(self::ENV_VAR_FALLBACK) !== false && getenv(self::ENV_VAR_FALLBACK) !== '',
                'warning' => '僅建議用於開發環境',
            ],
            'current_source' => $this->keySource,
            'is_production' => $this->isProduction(),
        ];
    }
}
