<?php
/**
 * 資產包 | assets/BootstrapSelectAsset.php
 *
 * @author KingSora
 * @license MIT
 */
namespace app\assets;

/**
 * bootstrap-select
 *
 * jQuery 插件，通過直觀的多選、搜索等功能將選擇元素
 *
 * @link github https://github.com/snapappointments/bootstrap-select
 */
class BootstrapSelectAsset extends \yii\web\AssetBundle
{
    /** @var string $sourcePath 包含此資產包原始資產檔案的目錄。 */
    public $sourcePath = '@frontend/bootstrap-select';

    /** @var array $css 此資產包中包含的 CSS 檔案清單。 */
    public $css = ['css/bootstrap-select.min.css'];

    /** @var array $js 此資產包中包含的 JavaScript 檔案清單。 */
    public $js = [
        'js/bootstrap-select.min.js',
        'js/i18n/defaults-zh_TW.min.js',
    ];

    /** @var array $depends 此資產包依賴的其他資產包類別名稱清單。 */
    public $depends = [
        \yii\web\YiiAsset::class,
        \yii\bootstrap5\BootstrapPluginAsset::class,
    ];
}
