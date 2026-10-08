<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * FormExportPasswd 生成密碼函模組
 */
class FormExportPasswd extends Model
{
    /**
     * @var UploadedFile docx 檔案
     */
    public $template;

    /**
     * 檔案匯入規則，僅許可docx
     */
    public function rules()
    {
        return [
            [
                ['template'],
                'file',
                'skipOnEmpty' => false,
                'extensions' => 'docx',
                'checkExtensionByMimeType' => true,
            ],
        ];
    }

    /**
     * 返回指定屬性的文本標籤
     * 
     * @param string $attribute the attribute name
     * @return string the attribute label
     */
    public function attributeLabels()
    {
        return [
            'template' => '樣板',
        ];
    }
}
