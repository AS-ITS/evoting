<?php

namespace app\components\helper;

use Yii;
use yii\base\BaseObject;
use yii\helpers\FileHelper;
use app\components\helper\CsvHelper;
use app\models\Votes;

class FileLoader extends BaseObject
{
    /**
     * FilePool 下以投票場次分目錄存放的子目錄
     */
    const DIR_PIC = 'candidatePic';
    const DIR_FILE = 'candidateFile';

    /**
     * FilePool路徑
     *
     * @var string
     */
    private $filepoolPath;

    public function __construct($filepoolPath) {
        $this->filepoolPath = $filepoolPath;
    }
    
    /**
     * 取得FilePool路徑
     *
     * @return string
     */
    public function getFilepoolPath()
    {
        return $this->filepoolPath;
    }
    
    /**
     * 設定FilePool路徑
     *
     * @param  string $path 路徑
     * @return string
     */
    public function setFilepoolPath($path = null)
    {
        if (is_null($path)) {
            return Yii::getAlias('@filePool');
        }
        return $this->filepoolPath = $path;
    }
    
    /**
     * 取得FilePool中的圖片轉換成base64的資料
     *
     * @param  string $image 圖片名稱
     * @param  string $imageDir 存放圖片的資料夾
     * @return string
     */
    public function imageData($image, $imageDir = self::DIR_PIC)
    {
        $filePath = $this->resolve($imageDir, $image);
        if ($filePath) {
            $imageInfo = pathinfo($filePath);
            $extension = $imageInfo['extension'];
            return "data:image/{$extension};base64,".base64_encode(file_get_contents($filePath));
        }
        return false;
    }
    
    /**
     * 下載指定檔案
     *
     * @param  string $file
     * @param  string $fileDir
     * @return bool
     */
    public function downloadFile($file, $fileDir)
    {
        $filePath = $this->resolve($fileDir, $file);
        if ($filePath) {
            return Yii::$app->response->sendFile($filePath);
        }
        else {
            return false;
        }
    }
    
    /**
     * 刪除指定路徑的檔案
     *
     * @param  string $path
     * @return bool
     */
    public function removeImage($image, $imageDir = self::DIR_PIC)
    {
        $imagePath = $this->resolve($imageDir, $image);
        if ($imagePath) {
            return FileHelper::unlink($imagePath);
        }
        return false;
    }

    /**
     * 解析 FilePool 下 $subDir 內的相對路徑，並確認結果仍位於 $subDir 之內
     *
     * @param  string $subDir FilePool 下的子目錄
     * @param  string $relPath 相對於 $subDir 的檔案路徑
     * @return string|null 實際檔案路徑；不存在或超出 $subDir 時回傳 null
     */
    private function resolve($subDir, $relPath)
    {
        if (!is_scalar($subDir) || !is_scalar($relPath)
            || self::hasNullByte($subDir) || self::hasNullByte($relPath)) {
            return null;
        }
        $poolDir = realpath($this->filepoolPath);
        $baseDir = realpath($this->filepoolPath.DIRECTORY_SEPARATOR.$subDir);
        if ($poolDir === false || $baseDir === false || !is_dir($baseDir)
            || !self::isInside($baseDir, $poolDir)) {
            return null;
        }
        $filePath = realpath($baseDir.DIRECTORY_SEPARATOR.$relPath);
        if ($filePath === false || !is_file($filePath) || !self::isInside($filePath, $baseDir)) {
            return null;
        }
        return $filePath;
    }

    /**
     * 取得 $baseDir 目錄內指定檔名的實際路徑（防止路徑穿越）
     *
     * $name 只取純檔名，任何目錄成分（../、..\、絕對路徑）都會被去除；
     * 解析後的路徑必須是 $baseDir 內的一般檔案。
     *
     * @param  string $baseDir 允許的目錄
     * @param  string $name 檔名
     * @return string|null 實際檔案路徑；不合法或不存在時回傳 null
     */
    public static function safePath($baseDir, $name)
    {
        if (!is_scalar($baseDir) || !is_scalar($name)
            || self::hasNullByte($baseDir) || self::hasNullByte($name)) {
            return null;
        }
        $baseDir = realpath((string) $baseDir);
        if ($baseDir === false || !is_dir($baseDir)) {
            return null;
        }

        $name = basename(str_replace('\\', '/', (string) $name));
        if ($name === '' || $name === '.' || $name === '..') {
            return null;
        }

        $filePath = realpath($baseDir.DIRECTORY_SEPARATOR.$name);
        if ($filePath === false || !is_file($filePath) || !self::isInside($filePath, $baseDir)) {
            return null;
        }
        return $filePath;
    }

    /**
     * 取得投票場次在 FilePool 子目錄下的實際目錄
     *
     * voteID 必須是存在的投票場次，且一律以資料庫中的值組路徑。
     *
     * @param  string $subDir self::DIR_PIC 或 self::DIR_FILE
     * @param  string $voteID
     * @param  bool $create 目錄不存在時是否建立
     * @return string|null 實際目錄路徑；場次或目錄不存在時回傳 null
     */
    public static function voteDir($subDir, $voteID, $create = false)
    {
        if (!in_array($subDir, [self::DIR_PIC, self::DIR_FILE], true) || !is_scalar($voteID)) {
            return null;
        }
        $vote = Votes::findOne(['voteID' => (string) $voteID]);
        if (!$vote || !self::isSafeSegment($vote->voteID)) {
            return null;
        }

        $rootDir = Yii::getAlias('@filePool').DIRECTORY_SEPARATOR.$subDir;
        $dir = $rootDir.DIRECTORY_SEPARATOR.$vote->voteID;
        if ($create && !is_dir($dir)) {
            try {
                FileHelper::createDirectory($dir, 0755);
            } catch (\Exception $e) {
                // 無寫入權限等原因建立失敗，由下方檢查回傳 null
                Yii::error("建立目錄失敗：{$dir}，{$e->getMessage()}", __METHOD__);
            }
        }

        $rootDir = realpath($rootDir);
        $dir = realpath($dir);
        if ($rootDir === false || $dir === false || !is_dir($dir) || dirname($dir) !== $rootDir) {
            return null;
        }
        return $dir;
    }

    /**
     * $path 是否位於 $baseDir 之內（兩者皆須為 realpath 的結果）
     *
     * @param  string $path
     * @param  string $baseDir
     * @return bool
     */
    public static function isInside($path, $baseDir)
    {
        return strpos($path, rtrim($baseDir, '/\\').DIRECTORY_SEPARATOR) === 0;
    }

    /**
     * 是否可安全作為單一目錄名稱（非空、非 . / ..，且不含路徑分隔字元與控制字元）
     *
     * @param  string $segment
     * @return bool
     */
    public static function isSafeSegment($segment)
    {
        $segment = (string) $segment;
        return $segment !== '' && $segment !== '.' && $segment !== '..'
            && !preg_match('#[/\\\\\x00-\x1F\x7F]#', $segment);
    }

    /**
     * @param  mixed $value
     * @return bool
     */
    private static function hasNullByte($value)
    {
        return strpos((string) $value, "\0") !== false;
    }

    /**
     * 因為PHPExcel的Memory使用量太大，所以使用此方法
     * 
     * ```php
     * // headers欄位名稱為key，表頭名稱為value
     * $header = [
     *     'name' => '姓名'
     * ]
     * // 當欄位資料要進行特殊處理時可以使用callback
     * // $row: 原始資料
     * // $column: 欄位名稱
     * $callback = [
     *     'name' => function ($row, $column) {
     *         return 'custom...'
     *     }
     * ]
     * ```
     * @param  array $headers 標頭
     * @param  array $rows 內容
     * @param  string $fileName 檔案名稱
     * @param  string $callback 資料例外處理
     * @return void
     */
    public static function csvSimple($headers, $rows, $fileName, $callback=null)
    {
        $result = [array_values($headers)];
        $y = 1;
        $rows = ($rows instanceof \yii\db\ActiveQuery) ? $rows->each() : $rows;
        foreach ($rows as $row) {
            $x = 1;
            foreach ($headers as $column => $value) {
                // 處理callback例外資料
                if (isset($callback[$column]) && is_callable($callback[$column])) {
                    $data = call_user_func($callback[$column], $row, $column);
                    $result[$y][$x] = $data;
                }
                else {
                    $result[$y][$x] = $row[$column];
                }
                $x++;
            }
            $y++;
        }

        // 使用CSV格式儲存檔案
        $csv = CsvHelper::export($result, ',', '"', PHP_EOL);

        return Yii::$app->response->sendContentAsFile(chr(239).chr(187).chr(191).$csv, $fileName, ['mimeType' => 'text/csv']);
    }
}
