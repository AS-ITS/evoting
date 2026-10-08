<?php
namespace app\components\helper;

use Exception;
use yii\base\InvalidArgumentException;
use yii\helpers\BaseStringHelper;

/**
 * 負責處理字串(String)的方法
 *
 * 開頭帶有 [uncertainty] 屬於開發階段不建議使用，功能名稱、參數或結果可能有巨大變化!!!
 */
class StringHelper extends BaseStringHelper
{
    /**
     * 使用星號比對字串
     *
     * 範例:
     *
     * ```php
     * StringHelper::matchWithAsterisk("*world*","hello world"); // true
     * StringHelper::matchWithAsterisk("world*","hello world"); // false
     * StringHelper::matchWithAsterisk("*world","hello world"); // true
     * StringHelper::matchWithAsterisk("world*","hello world"); // false
     * StringHelper::matchWithAsterisk("*ello*w*","hello world"); // true
     * StringHelper::matchWithAsterisk("*w*o*r*l*d*","hello world"); // true
     * ```
     *
     * @param string $pattern 比對規則
     * @param string $str 要比對的字串
     *
     * @return bool 是否符合
     *
     * @link 參考 https://stackoverflow.com/a/5622211
     */
    public static function matchWithAsterisk($pattern, $str)
    {
        // 使用preg_quote轉義整個字符串，除了"*"
        $pattern = preg_quote($pattern, '/');
        // 將"*"替換為正則表達式的".*"
        $pattern = str_replace('\*', '.*', $pattern);
        // 進行匹配
        return (bool)preg_match('/^' . $pattern . '$/i', $str);
    }

    /**
     * 將字節大小轉換為詳細大小。
     *
     * 範例：
     *
     * ```php
     * \app\components\helper\StringHelper::getVerboseSize(5*1024); // 5 KB
     * ```
     *
     * @param int $bytes 字節大小
     * @param int $precision 精確度取自小數點後幾位
     * @param int $kUnit 千的單位，國際單位制(SI)規定:1kB = 1000B，國際電工委員會(IEC)規定:1KiB = 1024B
     *
     * @return string 詳細大小
     */
    public static function getVerboseSize($bytes, $precision=2, $kUnit=1024)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log($kUnit));
        $pow = min($pow, count($units) - 1);

        // 取消註釋以下選項之一
        $bytes /= pow($kUnit, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * 將詳細大小轉換為字節大小。
     *
     * 範例：
     *
     * ```php
     * \app\components\helper\StringHelper::getByteSize('5K'); // 5*1024
     * ```
     *
     * @param string $verboseSize 詳細大小
     *
     * @return int 字節大小
     * @param int $kUnit 千的單位，國際單位制(SI)規定:1kB = 1000B，國際電工委員會(IEC)規定:1KiB = 1024B
     *
     * @see \yii\web\MultipartFormDataParser::getByteSize
     */
    public static function getByteSize($verboseSize, $kUnit=1024)
    {
        $pregSize = ArrayHelper::matchPregAll(
            '/^(\d+(?:\.\d+)?)[ ]*((?:K|M|G|T|P)?(?:Bytes|Byte|B)?)$/mi',
            trim($verboseSize),
            true
        );
        if (is_null($pregSize)) {
            return 0;
        }
        $intSize = (float) $pregSize[1];
        switch (strtolower($pregSize[2])) {
            case 'kb':
            case 'k':
                return $intSize * $kUnit;
            case 'mb':
            case 'm':
                return $intSize * pow($kUnit, 2);
            case 'gb':
            case 'g':
                return $intSize * pow($kUnit, 3);
            case 'tb':
            case 't':
                return $intSize * pow($kUnit, 4);
            case 'pb':
            case 'p':
                return $intSize * pow($kUnit, 5);
            case 'bytes':
            case 'byte':
            case 'b':
            case '':
                return $intSize;
            default:
                return 0;
        }
    }

    /**
     * 在字串中找字串
     *
     * @param string $haystack 要搜索的字串
     * @param string $needle 要在 `$haystack` 中搜索的子字串
     *
     * @return bool 是否找到對應字串
     */
    public static function str_contains($haystack, $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }

    /**
     * 結合相似度百分比和Levenshtein距離計算綜合相似度評分
     *
     * @param string $str1 第一個字符串
     * @param string $str2 第二個字符串
     *
     * @return float 綜合相似度評分
     */
    public static function combinedSimilarityScore($str1, $str2)
    {
        // 計算相似度百分比的平均值
        similar_text($str1, $str2, $percent1);
        similar_text($str2, $str1, $percent2);
        $averagePercent = ($percent1 + $percent2) / 2;

        // 計算Levenshtein距離
        $levenshteinDistance = levenshtein($str1, $str2);

        // 正規化Levenshtein距離（示例中這僅是一個示例正規化方法，需要根據實際情況調整）
        $maxLen = max(strlen($str1), strlen($str2));
        $normalizedLevenshtein = 100 * (1 - ($levenshteinDistance / $maxLen));

        // 結合這兩個分數（這裡我們簡單地取平均，但可以根據需要調整權重）
        $combinedScore = ($averagePercent + $normalizedLevenshtein) / 2;

        return $combinedScore;
    }

    /**
     * 根據定義之IP規則，判斷IP是否符合
     *
     * @param string $ipRangeStr 多項IP規則
     * @param string $ip 要判斷之IP
     *
     * @return bool 是否符合
     */
    public static function chkIpRange($ipRangeStr, $ip)
    {
        $ipRangeAry = explode(',', $ipRangeStr);
        foreach($ipRangeAry as $ipRange)
        {
            // IP規則過濾
            $cIpRange = preg_replace( "/[^0-9\.~]/i", '', $ipRange);
            // IP規則審查
            $re = '/^(\d+\~\d+|\d+)(?:\.(\d+\~\d+|\d+))?(?:\.(\d+\~\d+|\d+))?(?:\.(\d+\~\d+|\d+))?$/';
            if (!preg_match($re, $cIpRange))
            {
                // IP 規則格式錯誤
                return false;
            }
            preg_match($re, $ip, $ipMatches, PREG_OFFSET_CAPTURE, 0);
            preg_match($re, $cIpRange, $ipRangeMatches, PREG_OFFSET_CAPTURE, 0);

            if (empty($ipMatches)) {
                return false;
            }

            $status = true;
            foreach($ipMatches as $i => $ipMatche)
            {
                if($i == 0)
                {
                    // 匹配字串跳過
                    continue;
                }
                else if(!isset($ipRangeMatches[$i]) || $status === false)
                {
                    // 規則未定義跳過或上一次檢查有不符合的則算不符合...
                    break;
                }
                /** @var int $ipMatcheVal 要判斷之IP的某段(哪一段根據 $i 決定) */
                $ipMatcheVal = intval($ipMatche[0]);
                /** @var string $ipRangeMatche IP規則的某段(哪一段根據 $i 決定) */
                $ipRangeMatche = &$ipRangeMatches[$i];
                if(strpos($ipRangeMatche[0], '~')) // 處理範圍
                {
                    /** @var string[] $rcAllowIpMatche IP規則的某段的範圍 */
                    $rcAllowIpMatche = array_map('intval', explode('~', $ipRangeMatche[0]));
                    if($rcAllowIpMatche[0] <= $rcAllowIpMatche[1])
                    {
                        list($min, $max) = $rcAllowIpMatche;
                    }
                    else
                    {
                        // 大寫小寫反...
                        list($max, $min) = $rcAllowIpMatche;
                    }
                    // 檢查IP範圍是否與要判斷之IP相等
                    $status = $ipMatcheVal >= $min && $ipMatcheVal <= $max;
                    continue;
                }
                // 檢查單一IP規則之數值是否與要判斷之IP相等
                $status = $ipMatcheVal == intval($ipRangeMatche[0]);
            }
            if($status) // 要判斷之IP全數通過某個IP規則，則通過
            {
                return $status;
            }
        }
        return false;
    }

    /**
     * 將半形字符轉為全形字符
     *
     * @param string $str 半型字符字串
     *
     * @return string 全形字符字串
     */
    public function convertHalfToFullWidth($str)
    {
        $str = mb_convert_kana($str, "ASKV", 'UTF-8');
        $replace = array(
            // "." => "。",
            "\\" => "＼",
            "'" => "＇",
            "\"" => "＂"
        );
        return str_replace(array_keys($replace), array_values($replace), $str);
    }

    /**
     * 生成具有指定長度的隨機字符串。
     *
     * 根據輸入參數包括小寫字母、大寫字母和/或數字來生成隨機字符串。
     * 如果所有的包含選項都被設定為 false，則拋出 InvalidArgumentException。
     *
     * @param int $len 要生成的隨機字符串的長度。預設值為 10。
     * @param bool $incLower 是否包括小寫字母。預設為 true。
     * @param bool $incUpper 是否包括大寫字母。預設為 true。
     * @param bool $incNum 是否包括數字。預設為 true。
     *
     * @return string 生成的隨機字符串。
     *
     * @throws InvalidArgumentException 如果沒有選擇任何字符類型。
     * @throws Exception 如果在生成隨機數時發生錯誤。
     */
    public static function genRandStr($len = 10, $incLower = true, $incUpper = true, $incNum = true)
    {
        $chars = '';
        $chars .= $incLower ? 'abcdefghijklmnopqrstuvwxyz' : '';
        $chars .= $incUpper ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : '';
        $chars .= $incNum ? '0123456789' : '';

        if (empty($chars))
        {
            throw new InvalidArgumentException('至少選擇一種字符。');
        }

        $charsLen = strlen($chars);
        $randStr = '';

        try
        {
            for ($i = 0; $i < $len; $i++)
            {
                $randStr .= $chars[random_int(0, $charsLen - 1)];
            }
        }
        catch (Exception $e)
        {
            echo "無法生成隨機字符串: ", $e->getMessage();
            return null;
        }

        return $randStr;
    }

    /**
     * 取得多用途網際網路郵件擴展(Media type, MIME)說明文字
     *
     * @param string $mime Media type
     *
     * @return string
     */
    public static function getMediaTypeName($mime)
    {
        $result = null;
        $mimeMatch = ArrayHelper::matchPregAll('/^(.*?)\/(.*?)(?:;[ ]*(.*?))?$/m', $mime, true);
        switch(strtolower($mimeMatch[1]))
        {
            case 'text': // 用於標準化地表示的文字訊息，文字訊息可以是多種字元集和或者多種格式的
                switch(strtolower($mimeMatch[2]))
                {
                    case 'plain':
                        $result = '純文字';
                        break;
                    case 'html':
                        $result = '網頁';
                        break;
                    default:
                        $result = '標準化文字類型';
                        break;
                }
                break;
            case 'application': // 用於傳輸應用程式資料或者二進位資料
                switch($mimeMatch[2])
                {
                    case 'xhtml+xml':
                        $result = 'XHTML檔案';
                        break;
                    case 'octet-stream':
                        $result = '任意的二進位資料';
                        break;
                    case 'pdf':
                        $result = 'PDF檔案';
                        break;
                    case 'msword':
                        $result = 'Microsoft Word檔案';
                        break;
                    case 'vnd.openxmlformats-officedocument.wordprocessingml.document':
                        $result = 'Microsoft Word 2007檔案';
                        break;
                    case 'vnd.wap.xhtml+xml':
                        $result = 'wap1.0+';
                        break;
                    case 'xhtml+xml':
                        $result = 'wap2.0+';
                        break;
                    case 'x-www-form-urlencoded':
                        $result = '使用HTTP的POST方法送出的表單';
                        break;
                    default:
                        $result = '應用程式或二進位資料類型';
                        break;
                }
                break;
            case 'image': // 用於傳輸靜態圖片資料
                switch($mimeMatch[2])
                {
                    case 'gif':
                        $result = 'GIF圖片';
                        break;
                    case 'png':
                    case 'x-png':
                        $result = 'PNG圖片';
                        break;
                    case 'jpeg':
                    case 'pjpeg':
                        $result = 'JPEG圖片';
                        break;
                    default:
                        $result = '靜態圖片類型';
                        break;
                }
                break;
            case 'audio': // 用於傳輸音訊或者音聲資料
                switch($mimeMatch[2])
                {
                    case 'mpeg':
                        $result = 'MP3音訊';
                        break;
                    case 'aac':
                        $result = 'AAC音訊';
                        break;
                    default:
                        $result = '音訊類型';
                        break;
                }
                break;
            case 'video': // 用於傳輸動態影像資料，可以是與音訊編輯在一起的視訊資料格式
                switch($mimeMatch[2])
                {
                    case 'mpeg':
                        $result = 'MPEG影片';
                        break;
                    case 'mp4':
                        $result = 'MPEG-4影片';
                        break;
                    default:
                        $result = 'MPEG-4影片類型';
                        break;
                }
                break;
            case 'message': // 用於包裝一個E-mail訊息
                switch($mimeMatch[2])
                {
                    case 'rfc822':
                        $result = 'RFC 822形式';
                        break;
                    default:
                        $result = '訊息類型';
                        break;
                }
                break;
            case 'multipart': // 用於連接訊息體的多個部分構成一個訊息，這些部分可以是不同類型的資料
                switch($mimeMatch[2])
                {
                    case 'alternative':
                        $result = 'HTML郵件的HTML形式和純文字形式';
                        break;
                    case 'form-data':
                        $result = '主要用於表單送出時伴隨檔案上傳的場合';
                        break;
                    default:
                        $result = '多個類型的訊息';
                        break;
                }
                break;
            case 'font': // 用於傳輸字型檔案
                switch($mimeMatch[2])
                {
                    default:
                        $result = '字型類型';
                        break;
                }
                break;
            case 'model': // 用於傳輸3D模型檔案
                switch($mimeMatch[2])
                {
                    default:
                        $result = '3D模型類型';
                        break;
                }
                break;
            default:
                $result = '未知類型: '.$mime;
                break;
        }
        return $result;
    }
}