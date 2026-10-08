<?php
use yii\helpers\Html;
use app\components\helper\ArrayHelper;

// Stored XSS
use HTMLPurifier;
use HTMLPurifier_Config;

/** @var \yii\web\View $this View 自己的 params */
/** @var string $modalId 浮動視窗的 id */
/** @var array $file 檔案的詳細資料 */

$this->registerJs(<<<JS
$('#{$modalId}-contact').animate(
    { scrollTop: $('#{$modalId}-contact').height() },
    1000
);
JS
, $this::POS_LOAD);
?>
<div class="row" id="yii-log-view-modal">
    <div class="col">
        <pre class="font-monospace text-break" style="white-space: pre-wrap;"><?php
            if($encode!='F')
            {
                // Stored XSS
                $config = HTMLPurifier_Config::createDefault();
                $purifier = new HTMLPurifier($config);
                $purifiedContent = $purifier->purify($content);
                echo Html::encode($purifiedContent);
            }
            else
            {
                // Stored XSS
                $config = HTMLPurifier_Config::createDefault();
                $purifier = new HTMLPurifier($config);
                echo $purifier->purify($content);
            }
        ?></pre>
    </div>
</div>
