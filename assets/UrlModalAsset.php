<?php
/**
 * 資產包 | assets/UrlModalAsset.php
 */
namespace app\assets;

/**
 * 為 UrlModal Widget 提供資源包。
 *
 * @since 1.0.0
 */
class UrlModalAsset extends \yii\web\AssetBundle
{
    /** @var string $sourcePath 包含此資產包原始資產檔案的目錄。 */
    public $sourcePath = '@app/assets/files/urlModal';

    /** @var array $css 此資產包中包含的 CSS 檔案清單。 */
    public $css = [];

    /** @var array $js 此資產包中包含的 JavaScript 檔案清單。 */
    public $js = ['urlModal.js'];

    /** @var array $depends 此資產包依賴的其他資產包類別名稱清單。 */
    public $depends = [
        \app\assets\AppAsset::class,
        \app\assets\ComVerLibAsset::class,
    ];
}
