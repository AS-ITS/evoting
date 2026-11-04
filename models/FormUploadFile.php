<?php
namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use app\components\helper\FileLoader;

class FormUploadFile extends Model
{
    /**
     * 上傳檔案總數
     */
    const MAX_UPLOAD_COUNT = 5;
    
    public $files;

    /**
     * 上傳失敗的檔名
     *
     * @var string[]
     */
    public $failedFiles = [];

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
            // voteID 須為存在的場次，目錄不存在時建立
            $filePath = FileLoader::voteDir(FileLoader::DIR_FILE, $voteID, true);
            $this->failedFiles = [];
            if (!$filePath) {
                $this->failedFiles = array_map(function ($file) { return $file->name; }, (array) $this->files);
                Logs::add(Logs::VOTE_FILE_UPLOAD_FAIL, Json::encode(compact('voteID') + ['error' => 'directory not writable'], 336));
                return false;
            }
            foreach ($this->files as $file) {
                // 檔名消毒：僅取純檔名並移除控制字元，防止路徑穿越與特殊字元
                $safeName = basename(str_replace('\\', '/', $file->name));
                $safeName = preg_replace('/[\x00-\x1F\x7F]/u', '', $safeName);
                if (!FileLoader::isSafeSegment($safeName) || !$file->saveAs($filePath.DIRECTORY_SEPARATOR.$safeName)) {
                    $this->failedFiles[] = $file->name;
                }
            }
            if (!empty($this->failedFiles)) {
                Logs::add(Logs::VOTE_FILE_UPLOAD_FAIL, Json::encode(compact('voteID') + ['failed' => $this->failedFiles], 336));
                return false;
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
