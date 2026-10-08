<?php
/**
 * 資產包 | assets/ComVerLibAsset.php
 */
namespace app\assets;

use Yii;

/**
 * Com Ver 自行客製化，此為客製化的內容包
 *
 * @since 1.0.0
 */
class ComVerLibAsset extends \yii\web\AssetBundle
{
    /** @var string $sourcePath 包含此資產包原始資產檔案的目錄。 */
    public $sourcePath = '@frontend/AdminLTE3_ComVer';

    /** @var array $css 此資產包中包含的 CSS 檔案清單。 */
    public $css = [];

    /** @var array $js 此資產包中包含的 JavaScript 檔案清單。 */
    public $js = ['js/main.js'];

    /** @var array $depends 此資產包依賴的其他資產包類別名稱清單。 */
    public $depends = [\app\assets\AppAsset::class];
}
