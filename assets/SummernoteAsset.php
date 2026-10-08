<?php
namespace app\assets;

use Yii;

/**
 * 超級簡單的所見即所得編輯器
 * 
 * @see https://github.com/summernote/summernote/
 */
class SummernoteAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@frontend/summernote';

    public $css = ['summernote-bs4.min.css'];

    public $js = ['summernote-bs4.min.js'];// lang/summernote-zh-TW.min.js

    public $depends = ['app\assets\AppAsset'];

    public $lang = 'zh-TW';

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        if ($this->lang != '')
        {
            $fallbackLanguage = substr($this->lang, 0, 2);
            if ($fallbackLanguage !== $this->lang && !file_exists(Yii::getAlias($this->sourcePath . "/lang/summernote-{$this->lang}.min.js"))) {
                $this->lang = $fallbackLanguage;
            }
            $this->js[] = "lang/summernote-{$this->lang}.min.js";
        }
        parent::registerAssetFiles($view);
    }
}
