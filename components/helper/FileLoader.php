<?php

namespace app\components\helper;

use Yii;
use yii\base\BaseObject;
use yii\helpers\FileHelper;
use app\components\helper\CsvHelper;

class FileLoader extends BaseObject
{    
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
    public function imageData($image, $imageDir = 'candidatePic')
    {
        $filePath = realpath($this->filepoolPath.DIRECTORY_SEPARATOR.$imageDir.$image);
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
        $filePath = realpath($this->filepoolPath.DIRECTORY_SEPARATOR.$fileDir.$file);
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
    public function removeImage($image, $imageDir = 'candidatePic')
    {
        $imagePath = realpath($this->filepoolPath.DIRECTORY_SEPARATOR.$imageDir.$image);
        if ($imagePath) {
            return FileHelper::unlink($imagePath);
        }
        return false;
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
