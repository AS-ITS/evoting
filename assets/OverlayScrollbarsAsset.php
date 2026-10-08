<?php
namespace app\assets;

use Yii;

/**
 * 一個javascript滾動條插件，可隱藏本機滾動條，
 * 提供自定義樣式的覆蓋滾動條，並保留本機功能和感覺
 * 
 * @see https://github.com/KingSora/OverlayScrollbars
 */
class OverlayScrollbarsAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@frontend/OverlayScrollbars';

    public $css = ['css/OverlayScrollbars.css'];

    public $js = ['js/jquery.overlayScrollbars.js'];

    /** 只發佈實際使用的 css/js，略過 packages/（vue/react/ngx 範例） */
    public $publishOptions = [
        'only' => [
            'css/*',
            'js/*',
        ],
    ];

    public $depends = ['app\assets\AppAsset'];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        $view->registerJs('var osInstance = $("body").overlayScrollbars({ }).overlayScrollbars();', $view::POS_LOAD);
        parent::registerAssetFiles($view);
    }
}
