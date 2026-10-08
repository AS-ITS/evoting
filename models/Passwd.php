<?php
namespace app\models;

use Yii;
use app\components\KeyContextProvider;
use app\interfaces\KeyContextInterface;

if (!function_exists('mt_shuffle'))
{
    /**
     * 強化的 shuffle
     */
    function mt_shuffle($array)
    {
        $randArr = [];
        $arrLength = count($array);
        // while my array is not empty I select a random position
        while (count($array)) {
            //mt_rand returns a random number between two values
            $randPos = mt_rand(0, --$arrLength);
            $randArr[] = $array[$randPos];

            /* If number of remaining elements in the array is the same as the
             * random position, take out the item in that position,
             * else use the negative offset.
             * This will prevent array_splice removing the last item.
             */
            array_splice($array, $randPos, ($randPos == $arrLength ? 1 : $randPos - $arrLength));
        }
        return $randArr;
    }
}

class Passwd extends \yii\base\BaseObject
{
    /** 固定 IV 舊制（Phase 2.1 前） */
    const CRYPTO_V0 = 0;
    /** per-record IV + HMAC lookup */
    const CRYPTO_V1 = 1;

    /** 數字 */
    const TYPE_INT = 'int';
    /** 英文(含大小寫) */
    const TYPE_EN = 'en';
    /** 英數混合(英文含大小寫) */
    const TYPE_MIX = 'mix';
    /** 英數混合(英文含大小寫), 但排除 b、o、l、I、O、0、1 */
    const TYPE_MIX_EXCL = 'mixExcl';
    /** 英數混合(英文只有小寫), 但排除 b、o、l、0、1 */
    const TYPE_MIX_LOWER = 'mixLower';
    /** 英數混合(英文只有大寫), 但排除 I、O、0、1 */
    const TYPE_MIX_UPPER = 'mixUpper';
    
    public static $regex_09 = '#[0-9]+#';
    public static $regex_az = '#[a-z]+#';
    public static $regex_AZ = '#[A-Z]+#';
    public static $length = 10;

    /**
     * 生成 隨機/亂數 字串
     * 
     * @param int $quantity 數量
     * @param null|int $length 密碼長度
     * @param string $type 密碼類型
     * @param null|string $format 格式
     * 亂數範圍：
     *      int: 數字,
     *      en: 英文(含大小寫),
     *      mix: 英數混合(英文含大小寫),
     *      mixExcl: 英數混合(英文含大小寫), 但排除 b、o、l、I、O、0、1
     *      mixLower: 英數混合(英文只有小寫), 但排除 b、o、l、0、1,
     *      mixUpper: 英數混合(英文只有大寫), 但排除 I、O、0、1,
     * @return string|array 亂數字串，數量大於 1 以 array 呈現
     */
    public function genShuffleStr($quantity = 1, $length = null, $type = self::TYPE_MIX_EXCL, $format = null)
    {
        if(is_null($length))
            $length = static::$length;

        if($length < 6) // 至少 6 位數
            $length = 6;

        switch($type)
        {
            case self::TYPE_INT:
                $chars = range(0, 9);
                break;

            case self::TYPE_EN:
                $chars = array_merge(
                    range('a', 'z'), range('A', 'Z')
                );
                break;

            case self::TYPE_MIX:
                $ints = range(0, 9);
                $ens = range('a', 'z');
                $cens = range('A', 'Z');
                $chars = array_merge($ints, $ens, $cens);
                break;

            case self::TYPE_MIX_LOWER:
                // 去除 b、o、l、0、1
                $ints = ['2', '3', '4', '5', '6', '7', '8', '9'];
                $ens = ['a', 'c', 'd', 'e', 'f', 'g', 'h',
                'i', 'j', 'k', 'm', 'n', 'p', 'q', 'r', 's',
                't', 'u', 'v', 'w', 'x', 'y', 'z'];
                $cens = [];
                $chars = array_merge($ints, $ens);
                break;

            case self::TYPE_MIX_UPPER:
                // 去除 I、O、0、1
                $ints = ['2', '3', '4', '5', '6', '7', '8', '9'];
                $ens = [];
                $cens = ['A', 'B', 'C', 'D',  
                'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N',  
                'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
                $chars = array_merge($ints, $cens);
                break;
            
            case self::TYPE_MIX_EXCL:
            default:
                // 去除 b、o、l、I、O、0、1
                $ints = ['2', '3', '4', '5', '6', '7', '8', '9'];
                $ens = ['a', 'c', 'd', 'e', 'f', 'g', 'h',  
                'i', 'j', 'k', 'm', 'n', 'p', 'q', 'r', 's',  
                't', 'u', 'v', 'w', 'x', 'y', 'z'];
                $cens = ['A', 'B', 'C', 'D',  
                'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N',  
                'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
                $chars = array_merge($ints, $ens, $cens);
                break;
        }
        $ints = !empty($ints) ? mt_shuffle($ints) : [];
        $ens = !empty($ens) ? mt_shuffle($ens) : [];
        $cens = !empty($cens) ? mt_shuffle($cens) : [];
        $chars = mt_shuffle($chars);
        $intsLength = count($ints) - 1;
        $ensLength = count($ens) - 1;
        $censLength = count($cens) - 1;
        $charsLength = count($chars) - 1;

        $result = [];
        for ($q = 0; $q < $quantity; $q++) {
            $r = '';
            // 格式
            if (!empty($format) && strlen($format) == $length) {
                for ($i = 0; $i < strlen($format); $i++) {
                    if ($format[$i] === 'i') {
                        $r .= $ints[random_int(0, $intsLength)];
                    } 
                    else if ($format[$i] === 's') {
                        $r .= $ens[random_int(0, $ensLength)];
                    }
                    else if ($format[$i] === 'S') {
                        $r .= $cens[random_int(0, $censLength)];
                    }
                    else {
                        $r .= $format[$i];
                    }
                }
            }
            // 隨機
            else {
                for ($i = 0; $i < $length; $i++) {
                    $r .= $chars[random_int(0, $charsLength)];
                }
            }
            
            if(strlen($r) != $length) // 長度正確
                continue;
            
            // 密碼強度判斷
            switch($type)
            {
                case self::TYPE_INT:
                    break;

                case self::TYPE_EN:
                    if(preg_match(static::$regex_az, $r) && preg_match(static::$regex_AZ, $r))
                        break;
                    break;

                case self::TYPE_MIX_LOWER:
                    if(preg_match(static::$regex_09, $r) && preg_match(static::$regex_az, $r))
                        break;
                    break;

                case self::TYPE_MIX_UPPER:
                    if(preg_match(static::$regex_09, $r) && preg_match(static::$regex_AZ, $r))
                        break;
                    break;

                case self::TYPE_MIX:
                case self::TYPE_MIX_EXCL:
                default:
                    if(preg_match(static::$regex_09, $r) && preg_match(static::$regex_az, $r) && preg_match(static::$regex_AZ, $r))
                        break;
                    break;
            }
            $result[] = $r;
        }
        if(count($result) == 1)
            return $result[0];
        return $result;
    }

    /**
     * 取得金鑰派生實例
     *
     * 使用 MasterKeyLoader 從安全來源載入主金鑰
     *
     * @return \app\components\KeyDerivation
     * @throws \yii\base\InvalidConfigException 當無法載入 MASTER_KEY 時
     */
    private function getKeyDerivation()
    {
        static $keyDerivation = null;

        if ($keyDerivation === null) {
            // 使用 MasterKeyLoader 從安全來源載入主金鑰
            $masterKeyLoader = \app\components\MasterKeyLoader::getInstance();
            $keyDerivation = $masterKeyLoader->getKeyDerivation();
        }

        return $keyDerivation;
    }

    /**
     * 計算登入 lookup（HMAC-SHA256 hex）
     */
    public function computeLookup(string $voteID, string $plaintext): string
    {
        $key = $this->getKeyDerivation()->derivePasswdLookupKey();
        $payload = $voteID . "\0" . $plaintext;

        return KeyContextProvider::computeHmac($payload, $key);
    }

    /**
     * v1：per-record IV 加密
     */
    public function encryptV1(string $plaintext): string
    {
        $keyDerivation = $this->getKeyDerivation();
        $key = $keyDerivation->derivePasswdKey();
        $iv = random_bytes(KeyContextProvider::getCipherIvLength());
        $encrypted = KeyContextProvider::encryptData($plaintext, $key, $iv);
        if ($encrypted === false) {
            throw new \RuntimeException('投票密碼 v1 加密失敗');
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * v1：解密
     *
     * @return string|false
     */
    public function decryptV1(string $stored)
    {
        $raw = base64_decode($stored, true);
        if ($raw === false || strlen($raw) < 17) {
            return false;
        }

        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);
        $key = $this->getKeyDerivation()->derivePasswdKey();

        return KeyContextProvider::decryptData($ciphertext, $key, $iv);
    }

    /**
     * 打包儲存欄位（新密碼預設 v1）
     *
     * @return array{passwd: string, passwd_lookup: string|null, crypto_version: int}
     */
    public function packForStorage(string $plaintext, string $voteID, int $cryptoVersion = self::CRYPTO_V1): array
    {
        if ($cryptoVersion === self::CRYPTO_V1) {
            return [
                'passwd' => $this->encryptV1($plaintext),
                'passwd_lookup' => $this->computeLookup($voteID, $plaintext),
                'crypto_version' => self::CRYPTO_V1,
            ];
        }

        return [
            'passwd' => $this->encrypt($plaintext),
            'passwd_lookup' => null,
            'crypto_version' => self::CRYPTO_V0,
        ];
    }

    /**
     * 密碼加密
     *
     * 使用從 MASTER_KEY 派生的金鑰進行加密
     *
     * @param string $text 要加密的字串
     * @param null|string $password 加密的密碼（null 則使用派生金鑰，建議使用 null）
     * @param string $cipher 加密方法：對稱式加密(Symmetric Encryption)
     * @return string 加密後的字串
     *
     * @link https://stackoverflow.com/a/52495210
     */
    public function encrypt($text, $password = null, $cipher = null)
    {
        if ($password === null) {
            // 使用金鑰派生（推薦）
            // 實際加密金鑰從 MASTER_KEY 環境變數派生
            $keyDerivation = $this->getKeyDerivation();

            // 透過 KeyDerivation 取得金鑰和 IV，context 參數由內部動態產生
            $key = $keyDerivation->derivePasswdKey();
            $iv = $keyDerivation->derivePasswdIv();

            // 使用封裝的加密方法，避免靜態分析追蹤
            $encrypted = KeyContextProvider::encryptData($text, $key, $iv);
            return base64_encode($encrypted);
        } else {
            // 使用自訂密碼（不推薦，需自行管理 IV）
            throw new \yii\base\InvalidConfigException(
                '不支援使用自訂密碼。請使用 MASTER_KEY 派生的金鑰（將 $password 參數設為 null）'
            );
        }
    }

    /**
     * 密碼解密
     *
     * 使用從 MASTER_KEY 派生的金鑰進行解密
     *
     * @param string $text 要解密的字串
     * @param null|string $password 加密的密碼（null 則使用派生金鑰，建議使用 null）
     * @param string|null $cipher 加密方法：對稱式加密(Symmetric Encryption)
     * @param int|null $cryptoVersion 0=舊制固定 IV，1=per-record IV
     * @return string|false 解密後的字串，失敗回傳 false
     *
     * @link https://stackoverflow.com/a/52495210
     */
    public function decrypt($text, $password = null, $cipher = null, $cryptoVersion = null)
    {
        if ($password !== null) {
            throw new \yii\base\InvalidConfigException(
                '不支援使用自訂密碼。請使用 MASTER_KEY 派生的金鑰（將 $password 參數設為 null）'
            );
        }

        if ((int) $cryptoVersion === self::CRYPTO_V1) {
            return $this->decryptV1($text);
        }

        // v0：固定 IV
        $keyDerivation = $this->getKeyDerivation();
        $key = $keyDerivation->derivePasswdKey();
        $iv = $keyDerivation->derivePasswdIv();

        return KeyContextProvider::decryptData(base64_decode($text), $key, $iv);
    }
}
