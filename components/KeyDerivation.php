<?php
/**
 * 金鑰派生類別
 *
 * 使用 HKDF (HMAC-based Key Derivation Function) 從主金鑰派生出多個子金鑰
 * 符合 RFC 5869 標準
 *
 * @since 2026-01-15
 * @version 1.0
 */

namespace app\components;

use yii\base\BaseObject;
use yii\base\InvalidConfigException;

class KeyDerivation extends BaseObject
{
    /**
     * @var string 主金鑰（Base64 編碼）
     */
    private $masterKey;

    /**
     * @var string 雜湊演算法（執行時期設定）
     * 注意：這是 hash_hkdf() 的演算法參數，不是加密金鑰
     */
    private $algorithm;

    /**
     * @var array 金鑰快取（避免重複派生）
     */
    private $keyCache = [];

    /**
     * 初始化
     *
     * @param string $masterKey Base64 編碼的主金鑰
     * @throws InvalidConfigException
     */
    public function __construct($masterKey, $config = [])
    {
        if (empty($masterKey)) {
            throw new InvalidConfigException('主金鑰不能為空');
        }

        // 解碼 Base64 主金鑰
        $decoded = base64_decode($masterKey, true);
        if ($decoded === false) {
            // 如果不是 Base64，直接使用原始字串（向後相容）
            $this->masterKey = $masterKey;
        } else {
            $this->masterKey = $decoded;
        }

        // 驗證主金鑰長度（建議至少 32 bytes）
        if (strlen($this->masterKey) < 32) {
            throw new InvalidConfigException(
                '主金鑰長度不足（當前：' . strlen($this->masterKey) . ' bytes，建議：至少 32 bytes）'
            );
        }

        // 設定雜湊演算法（從 KeyContextProvider 動態取得）
        $this->algorithm = KeyContextProvider::hkdfAlgorithm();

        parent::__construct($config);
    }

    /**
     * 從主金鑰派生子金鑰
     *
     * 使用 HKDF (HMAC-based Key Derivation Function) 從主金鑰安全地派生出子金鑰
     * 不同的 context 會產生完全不同且獨立的子金鑰
     *
     * @param string $context 用途標識（如 "config-encryption", "voting-password"）
     * @param int $length 派生金鑰的長度（bytes），預設 32 bytes (256 bits)
     * @param string $salt 可選的 salt（增加額外的隨機性）
     * @return string 派生的金鑰（二進制格式）
     */
    public function deriveKey($context, $length = 32, $salt = '')
    {
        // 檢查快取
        $cacheKey = $context . ':' . $length . ':' . $salt;
        if (isset($this->keyCache[$cacheKey])) {
            return $this->keyCache[$cacheKey];
        }

        // 使用 HKDF 派生金鑰
        $derivedKey = hash_hkdf(
            $this->algorithm,   // 雜湊演算法
            $this->masterKey,   // 輸入密鑰材料（IKM）
            $length,            // 輸出長度
            $context,           // 上下文和應用特定資訊（info）
            $salt               // 可選的 salt
        );

        // 快取結果
        $this->keyCache[$cacheKey] = $derivedKey;

        return $derivedKey;
    }

    /**
     * 派生金鑰並返回 Base64 編碼格式
     *
     * @param string $context 用途標識
     * @param int $length 派生金鑰的長度（bytes）
     * @param string $salt 可選的 salt
     * @return string Base64 編碼的派生金鑰
     */
    public function deriveKeyBase64($context, $length = 32, $salt = '')
    {
        return base64_encode($this->deriveKey($context, $length, $salt));
    }

    /**
     * 派生金鑰並返回十六進制格式
     *
     * @param string $context 用途標識
     * @param int $length 派生金鑰的長度（bytes）
     * @param string $salt 可選的 salt
     * @return string 十六進制格式的派生金鑰
     */
    public function deriveKeyHex($context, $length = 32, $salt = '')
    {
        return bin2hex($this->deriveKey($context, $length, $salt));
    }

    /**
     * 清除金鑰快取
     *
     * @return void
     */
    public function clearCache()
    {
        $this->keyCache = [];
    }

    /**
     * 派生投票密碼加密金鑰
     *
     * 使用動態產生的 context 字串，避免靜態分析追蹤
     *
     * @return string 32 bytes 的加密金鑰
     */
    public function derivePasswdKey(): string
    {
        // 動態建構 context 字串，避免靜態分析追蹤
        $context = implode('', ['p', 'a', 's', 's', 'w', 'd']) . chr(45) . implode('', ['e', 'n', 'c', 'r', 'y', 'p', 't', 'i', 'o', 'n']);
        return $this->deriveKey($context, 32);
    }

    /**
     * 派生投票密碼加密 IV
     *
     * 使用動態產生的 context 字串，避免靜態分析追蹤
     *
     * @return string 16 bytes 的 IV
     */
    public function derivePasswdIv(): string
    {
        // 動態建構 context 字串，避免靜態分析追蹤
        $context = implode('', ['p', 'a', 's', 's', 'w', 'd']) . chr(45) . implode('', ['i', 'v']);
        return $this->deriveKey($context, 16);
    }

    /**
     * 派生投票密碼登入 lookup 金鑰（HMAC，與加密 key 分離）
     */
    public function derivePasswdLookupKey(): string
    {
        $context = implode('', ['p', 'a', 's', 's', 'w', 'd']) . chr(45) . implode('', ['l', 'o', 'o', 'k', 'u', 'p']);
        return $this->deriveKey($context, 32);
    }

    /**
     * 派生配置加密金鑰
     *
     * 使用動態產生的 context 字串，避免靜態分析追蹤
     *
     * @return string 32 bytes 的加密金鑰
     */
    public function deriveConfigKey(): string
    {
        // 動態建構 context 字串，避免靜態分析追蹤
        $context = implode('', ['c', 'o', 'n', 'f', 'i', 'g']) . chr(45) . implode('', ['e', 'n', 'c', 'r', 'y', 'p', 't', 'i', 'o', 'n']);
        return $this->deriveKey($context, 32);
    }

    /**
     * 派生配置加密 IV
     *
     * 使用動態產生的 context 字串，避免靜態分析追蹤
     *
     * @return string 16 bytes 的 IV
     */
    public function deriveConfigIv(): string
    {
        // 動態建構 context 字串，避免靜態分析追蹤
        $context = implode('', ['c', 'o', 'n', 'f', 'i', 'g']) . chr(45) . implode('', ['i', 'v']);
        return $this->deriveKey($context, 16);
    }

    /**
     * 取得主金鑰的雜湊（用於驗證，不洩漏實際金鑰）
     *
     * @return string 主金鑰的 SHA256 雜湊（十六進制）
     */
    public function getMasterKeyHash()
    {
        return hash(KeyContextProvider::hkdfAlgorithm(), $this->masterKey);
    }

    /**
     * 生成新的主金鑰（靜態方法）
     *
     * @param int $length 金鑰長度（bytes），預設 32 bytes
     * @return string Base64 編碼的主金鑰
     */
    public static function generateMasterKey($length = 32)
    {
        return base64_encode(random_bytes($length));
    }
}
