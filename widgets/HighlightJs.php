<?php
/**
 * Highlight.js v11.6.0
 *
 * @see https://highlightjs.org/
 */
namespace app\widgets;

use yii\base\Widget;

/**
 * Highlight.js 是用 JavaScript 編寫的語法高亮器。
 */
class HighlightJs extends Widget
{
    /** 標籤名 */

    /** @var bool $themeBase16 是否使用Base16主題 */
    public $themeBase16=false;
    /** @var string $themeName 主題名稱 */
    public $themeName;
    /** @var bool $useCurl 導入Curl樣式 */
    public $useCurl=false;

    /**
     * 初始化
     *
     * @return void
     */
    public function init()
    {
        $this->registerJs();
    }

    /**
     * 主程式
     *
     * @return string the result of widget execution to be outputted.
     */
    public function run()
    {
        $this->getView()->registerJs("hljs.highlightAll();");
    }

    /**
     * 註冊JS、Assets
     *
     * @return void
     */
    private function registerJs()
    {
        $view = $this->getView();

        // 導入資產
        $bundle = \app\assets\HighlightJsAsset::register($view);
        if(!is_null($this->themeName) && preg_match("/[0-9a-z-.]+/i", $this->themeName))
        {
            // 導入主題
            if($this->themeBase16)
            {
                $themeFileName = "styles/base16/{$this->themeName}.min.css";
            }
            else
            {
                $themeFileName = "styles/{$this->themeName}.min.css";
            }
            if(file_exists($bundle->sourcePath.'/'.$themeFileName))
            {
                $bundle->css = [$themeFileName];
            }
        }

        // 導入CURL樣式
        if($this->useCurl)
        {
            \app\assets\HighlightJsCurlAsset::register($view);
        }
    }
}
