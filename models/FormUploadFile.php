<?php
namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use yii\web\UploadedFile;
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
                'extensions' => implode(', ', array_keys(self::MIME_MAP)),
                // 改由 validateMimeType() 依對照表檢查內容，避免 csv、Office 檔被 Yii 內建反查誤擋
                'checkExtensionByMimeType' => false,
                'maxFiles' => 5,
                'maxSize' => 6144000
            ],
            [['files'], 'validateMimeType'],
        ];
    }

    /**
     * 允許的副檔名與其檔案內容 (MIME) 對照
     *
     * OOXML / ODF 實為 zip 封裝，部分產生器的檔案會被偵測為 application/zip；
     * 舊版 Office (OLE) 檔依 libmagic 版本可能偵測為 vnd.ms-office / CDFV2。
     */
    const MIME_MAP = [
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'gif'  => ['image/gif'],
        'pdf'  => ['application/pdf', 'application/x-pdf'],
        'txt'  => ['text/plain'],
        'csv'  => ['text/plain', 'text/csv', 'application/csv', 'text/x-csv', 'application/vnd.ms-excel'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/cdfv2', 'application/x-ole-storage'],
        'xls'  => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/cdfv2', 'application/x-ole-storage'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/cdfv2', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'odt'  => ['application/vnd.oasis.opendocument.text', 'application/zip'],
        'ods'  => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip'],
        'odp'  => ['application/vnd.oasis.opendocument.presentation', 'application/zip'],
    ];

    /**
     * 依檔案實際內容檢查是否與副檔名相符，防止偽造副檔名上傳惡意檔案
     *
     * @param  string $attribute
     * @return void
     */
    public function validateMimeType($attribute)
    {
        foreach ((array) $this->$attribute as $file) {
            if (!$file instanceof UploadedFile || $file->error !== UPLOAD_ERR_OK) {
                continue;
            }
            $mime = FileHelper::getMimeType($file->tempName, null, false);
            if (!self::isAllowedMime($file->extension, $mime)) {
                $this->addError($attribute, "檔案「{$file->name}」的內容與副檔名不符，無法上傳。");
            }
        }
    }

    /**
     * 副檔名與偵測到的 MIME 是否相符
     *
     * @param  string $extension
     * @param  string|null $mime
     * @return bool
     */
    public static function isAllowedMime($extension, $mime)
    {
        $extension = strtolower((string) $extension);
        $mime = strtolower(trim(explode(';', (string) $mime)[0]));
        return isset(self::MIME_MAP[$extension]) && in_array($mime, self::MIME_MAP[$extension], true);
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
