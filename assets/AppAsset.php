<?php
/**
 * 資產包 | assets/AppAsset.php
 *
 * @version     v1.3 (2022/09/19)
 */
namespace app\assets;

/**
 * [主要應用程序資產包]
 * 共用項目預設AP常用的資產包
 * 包含 Yii 框架基本資產、BootStrap 5、Font Awesome
 *
 * @link YiiAsset https://github.com/yiisoft/yii2/blob/master/framework/web/YiiAsset.php
 * @link Bootstrap 5 https://github.com/yiisoft/yii2-bootstrap5/blob/master/src/BootstrapAsset.php
 * @link Free Font Awesome https://github.com/rmrevin/yii2-fontawesome/blob/master/NpmFreeAssetBundle.php
 */
class AppAsset extends \yii\web\AssetBundle
{
    /** @var string $basePath the Web-accessible directory that contains the asset files in this bundle. */
    public $basePath = '@webroot';

    /** @var string $baseUrl the base URL for the relative asset files listed in [[js]] and [[css]]. */
    public $baseUrl = '@web';

    /** @var array $css 此資產包中包含的 CSS 檔案清單。 */
    public $css = [];

    /** @var array $js 此資產包中包含的 JavaScript 檔案清單。 */
    public $js = [];

    /** @var array $depends 此資產包依賴的其他資產包類別名稱清單。 */
    public $depends = [
        // Yii 2 Asset
        \yii\web\YiiAsset::class,
        // BootStrap 5
        \yii\bootstrap5\BootstrapAsset::class,
        \yii\bootstrap5\BootstrapPluginAsset::class,
        // Font Awesome Asset Bundle
        \rmrevin\yii\fontawesome\NpmFreeAssetBundle::class,
    ];
}
