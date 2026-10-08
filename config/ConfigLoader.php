<?php
namespace app\config;

use yii\helpers\Json;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\base\InvalidArgumentException;
use app\components\KeyContextProvider;

/**
 * ConfigLoader 用於加載並解密配置文件
 */
class ConfigLoader extends BaseObject
{
    /**
     * @var string JSON 檔案路徑
     */
    public $jsonFilePath;

    /**
     * @var string 解密用的密鑰
     */
    public $secretKey;
    /**
     * @var string 解密用的初始化向量（IV）
     */
    public $iv;
    /**
     * @var string 加密方法
     */
    public $encryptionMethod = null;
    /**
     * @var array 需要解密的欄位列表
     */
    public $encryptedFields = [];

    /**
     * @var array 存儲配置數據
     */
    protected $configData = [];

    /**
     * @var string AES-256-CBC作為加解密底層cipher
     */
    public $cipher = null;

    /**
     * 初始化方法，加載配置數據
     */
    public function init()
    {
        parent::init();

        // 設定加密演算法（如果未指定則使用預設值）
        if ($this->cipher === null) {
            $this->cipher = KeyContextProvider::aesCipher();
        }
        if ($this->encryptionMethod === null) {
            $this->encryptionMethod = KeyContextProvider::aesCipher();
        }

        if(!empty($this->jsonFilePath))
        {
            $this->loadConfig();
        }
    }

    /**
     * 加載並處理配置檔案
     *
     * @throws InvalidConfigException 如果檔案不存在或 JSON 解析失敗
     */
    protected function loadConfig()
    {
        // 檢查檔案是否存在
        if (!file_exists($this->jsonFilePath))
        {
            throw new InvalidConfigException("配置檔案不存在: {$this->jsonFilePath}");
        }

        // 讀取檔案
        $jsonContent = file_get_contents($this->jsonFilePath);
        try
        {
            // 解析 JSON
            $this->configData = Json::decode($jsonContent, true);
        }
        catch (InvalidArgumentException $e)
        {
            // 處理 JSON 解析的錯誤
            throw new InvalidConfigException('JSON 解析錯誤: ' . $e->getMessage());
        }

        // 處理每項資料
        foreach ($this->configData as $key => $value)
        {
            if ($this->isEncryptedField($key))
            {
                $this->configData[$key] = $this->decryptData($value);
            }
        }
    }

    /**
     * 解密數據，支持多種加密方法，包括 AES-256-CBC-HMAC-SHA256
     *
     * @param string $encryptedData 加密的數據
     * @return string 解密後的數據
     * @throws InvalidConfigException 如果加密方法不支持或 HMAC 驗證失敗
     */
    protected function decryptData($encryptedData)
    {
        // 選擇適當的解密方法
        if ($this->encryptionMethod === 'AES-256-CBC-HMAC-SHA256')
        {
            // 特定於 AES-256-CBC-HMAC-SHA256 的解密處理
            $result = $this->decryptAes256CbcHmacSha256($encryptedData);
        }
        else
        {
            // 通用解密處理
            $result = $this->decryptGeneric($encryptedData);
        }
        // 移除字符串末尾的填充
        return $this->removePkcs7Padding($result);
    }

    /**
     * 使用 AES-256-CBC-HMAC-SHA256 方法進行加密
     *
     * @param string $data 要加密的數據
     * @return string 加密後的數據
     */
    public function encryptAes256CbcHmacSha256($data)
    {
        // 使用封裝方法取得 IV 長度
        $ivLength = KeyContextProvider::getCipherIvLength();
        $iv = openssl_random_pseudo_bytes($ivLength);

        $secretKey = KeyContextProvider::computeHash($this->secretKey); // 使用 hash 處理密鑰
        $encrypted = KeyContextProvider::encryptDataBase64($data, $secretKey, $iv);
        $hmac = KeyContextProvider::computeHmac($encrypted, $secretKey, true);
        return base64_encode($iv . $hmac . $encrypted);
    }

    /**
     * 使用 AES-256-CBC-HMAC-SHA256 方法解密數據
     *
     * @param string $encryptedData 加密的數據
     * @return string 解密後的數據
     * @throws InvalidConfigException 如果 HMAC 驗證失敗
     */
    public function decryptAes256CbcHmacSha256($encryptedData)
    {
        $decoded = base64_decode($encryptedData);
        // 使用封裝方法取得 IV 長度
        $ivLength = KeyContextProvider::getCipherIvLength();
        $hmacLength = 32; // HMAC SHA-256 的二進制長度
        // 取出 IV、HMAC 與密文
        $iv = substr($decoded, 0, $ivLength);
        $hmac = substr($decoded, $ivLength, $hmacLength);
        $cipherText = substr($decoded, $ivLength + $hmacLength);
        $secretKey = KeyContextProvider::computeHash($this->secretKey);

        // 重新計算 HMAC 以進行驗證
        $calculatedHmac = KeyContextProvider::computeHmac($cipherText, $secretKey, true);
        if (!hash_equals($calculatedHmac, $hmac))
        {
            throw new InvalidConfigException('HMAC verification failed.');
        }

        return KeyContextProvider::decryptDataBase64($cipherText, $secretKey, $iv);
    }

    /**
     * 對字符串進行時間攻擊安全比較
     *
     * @param string $known_string 已知長度的字符串用於比較。
     * @param string $user_string 用戶提供的字符串。
     * @return bool 如果兩個字符串相同則為 true，否則為 false。
     */
    protected function safeStrCmp($known_string, $user_string)
    {
        if (strlen($known_string) !== strlen($user_string))
        {
            return false;
        }
        $res = 0;
        for ($i = 0; $i < strlen($known_string); $i++)
        {
            $res |= ord($known_string[$i]) ^ ord($user_string[$i]);
        }
        return $res === 0;
    }

    /**
     * 使用通用方法進行加密
     *
     * @param string $data 要加密的數據
     * @return string 加密後的數據
     */
    public function encryptGeneric($data)
    {
        // 使用封裝方法取得 IV 長度
        $ivLength = KeyContextProvider::getCipherIvLength();
        // 產生隨機 IV
        $iv = openssl_random_pseudo_bytes($ivLength);

        $secretKey = KeyContextProvider::computeHash($this->secretKey); // 使用 hash 處理密鑰
        $encrypted = KeyContextProvider::encryptDataBase64($data, $secretKey, $iv);
        // 將 IV 附加於加密資料前，並以 base64 儲存
        return base64_encode($iv . $encrypted);
    }

    /**
     * 使用通用方法解密數據
     *
     * @param string $encryptedData 加密的數據
     * @return string 解密後的數據
     */
    public function decryptGeneric($encryptedData)
    {
        $decoded = base64_decode($encryptedData);
        // 使用封裝方法取得 IV 長度
        $ivLength = KeyContextProvider::getCipherIvLength();
        // 從 decoded 資料中拿出先前儲存的 IV
        $iv = substr($decoded, 0, $ivLength);
        $cipherText = substr($decoded, $ivLength);

        $secretKey = KeyContextProvider::computeHash($this->secretKey); // 使用 hash 處理密鑰
        return KeyContextProvider::decryptDataBase64($cipherText, $secretKey, $iv);
    }

    /**
     * 檢查給定的鍵是否為加密欄位
     *
     * @param string $key 配置中的鍵
     * @return bool 是否為加密欄位
     */
    protected function isEncryptedField($key)
    {
        return in_array($key, $this->encryptedFields);
    }

    /**
     * 獲取解密後的配置數據
     *
     * @return array 配置數據
     */
    public function getConfig()
    {
        return $this->configData;
    }

    /**
     * 移除字符串末尾的填充。
     *
     * 這個函數假設填充是根據 PKCS#7 標準進行的，會檢查並移除末尾的填充字符。
     * 填充字符是根據 PKCS#7 標準添加的，意味著每個填充字符的值都等於填充的長度。
     * 例如，如果填充了 4 個字符，那麼每個字符的值都是 0x04。
     * 這個函數會檢查並確認末尾的填充是否符合這個規則，如果是，則將其移除。
     *
     * @param string $text 需要移除填充的字符串。
     * @return string 移除填充後的字符串。
     */
    public function removePkcs7Padding($text)
    {
        $end = substr($text, -1);
        $last = ord($end);
        $len = strlen($text) - $last;
        if(substr($text, $len) == str_repeat($end, $last)){
            return substr($text, 0, $len);
        }
        return $text;
    }
}
