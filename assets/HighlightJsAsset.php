<?php
/**
 * 資產包 | assets/HighlightJsAsset.php
 *
 * @author Mario Ranftl
 * @license MIT
 */
namespace app\assets;

/**
 * Highlight.js
 *
 * Highlight.js 是一個用 JavaScript 編寫的語法高亮器。
 * 它可以在瀏覽器和服務器上工作。
 * 它幾乎可以使用任何標記，不依賴於任何其他框架，並且具有自動語言檢測功能。
 *
 * @link github https://github.com/highlightjs/highlight.js
 */
class HighlightJsAsset extends \yii\web\AssetBundle
{
    /** @var string $sourcePath 包含此資產包原始資產檔案的目錄。 */
    public $sourcePath = '@frontend/highlight.js';

    /** @var array $css 此資產包中包含的 CSS 檔案清單。 */
    public $css = ['styles/default.min.css'];

    /** @var array $js 此資產包中包含的 JavaScript 檔案清單。 */
    public $js = ['highlight.min.js'];

    /** @var array $depends 此資產包依賴的其他資產包類別名稱清單。 */
    public $depends = [\yii\web\YiiAsset::class];
}
