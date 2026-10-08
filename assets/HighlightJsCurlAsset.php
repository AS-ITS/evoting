<?php
/**
 * 資產包 | assets/HighlightJsCurlAsset.php
 *
 * @author Mario Ranftl
 * @license MIT
 */
namespace app\assets;

/**
 * highlightjs-curl
 *
 * 支持使用 highlight.js 語法高亮 cURL 命令。
 *
 * @link github https://github.com/highlightjs/highlightjs-curl
 */
class HighlightJsCurlAsset extends \yii\web\AssetBundle
{
    /** @var string $sourcePath 包含此資產包原始資產檔案的目錄。 */
    public $sourcePath = '@frontend/highlightjs-curl/dist';

    /** @var array $css 此資產包中包含的 CSS 檔案清單。 */
    public $css = [];

    /** @var array $js 此資產包中包含的 JavaScript 檔案清單。 */
    public $js = ['curl.min.js'];

    /** @var array $depends 此資產包依賴的其他資產包類別名稱清單。 */
    public $depends = [\app\assets\HighlightJsAsset::class];
}
