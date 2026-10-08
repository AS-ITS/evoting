<?php
namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\helpers\Json;

class FormUploadFile extends Model
{
    /**
     * 上傳檔案總數
     */
    const MAX_UPLOAD_COUNT = 5;
    
    public $files;

    /**
     * Returns the validation rules for attributes.
     * 
     * @return array validation rules
     */
    public function rules()
    {
        return [
            // [['files'], 'required'],
            [
                ['files'], 
                'file', 
                'extensions' => 'png, jpg, jpeg, gif, docx, doc, pdf, pptx, ppt, csv, xlsx, xls, txt, odt, ods, odp',
                // 依檔案實際內容 (MIME) 驗證副檔名，防止偽造副檔名上傳惡意檔案
                'checkExtensionByMimeType' => true,
                'maxFiles' => 5,
                'maxSize' => 6144000
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'files' => '檔案',
        ];
    }
    
    /**
     * 檔案上傳處理
     *
     * @param  string $voteID
     * @return bool
     */
    public function upload($voteID)
    {
        if ($this->validate()) { 
            $filePath = realpath(Yii::getAlias('@filePool').DIRECTORY_SEPARATOR.'candidateFile');
            // 判斷資料夾是否存在
            if (!file_exists($filePath.DIRECTORY_SEPARATOR.$voteID)) {
                FileHelper::createDirectory($filePath.DIRECTORY_SEPARATOR.$voteID, 0755);
            }
            foreach ($this->files as $file) {
                // 檔名消毒：僅取純檔名並移除控制字元，防止路徑穿越與特殊字元
                $safeName = basename(str_replace('\\', '/', $file->name));
                $safeName = preg_replace('/[\x00-\x1F\x7F]/u', '', $safeName);
                $file->saveAs($filePath.DIRECTORY_SEPARATOR.$voteID.DIRECTORY_SEPARATOR.$safeName);
            }
            Logs::add(Logs::VOTE_FILE_UPLOAD, Json::encode(compact('voteID')+$this->files, 336));
            return true;
        } 
        else {
            Logs::add(Logs::VOTE_FILE_UPLOAD_FAIL, Json::encode(compact('voteID')+$this->errors, 336));
            return false;
        }
    }
}
