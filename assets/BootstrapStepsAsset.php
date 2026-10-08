<?php
namespace app\assets;

use Yii;

/**
 * A simple Bootstrap steps plugin, support mobile client.
 *
 * @see https://github.com/ycs77/bootstrap-steps
 */
class BootstrapStepsAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@frontend/bootstrap-steps';

    public $css = ['dist/bootstrap-steps.min.css'];

    public $depends = ['app\assets\AppAsset'];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        $view->registerCss(<<<CSS
            .step-content {
                padding-right: 3.8rem;
                padding-bottom: 5px;
            }

            .step-circle::before {
                width: calc(3.8rem + 5rem - 1.5rem);
            }

            .step-text {
                width: 9rem;
                line-height: 19px;
                margin-top: 0.5rem;
            }
CSS
        );
        parent::registerAssetFiles($view);
    }
}
