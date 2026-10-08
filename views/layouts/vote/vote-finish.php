<?php

use yii\bootstrap5\Html;

$this->beginContent('@app/useTemplate/main.php');    // HTML 基本樣版/開始
/* body - start */

$this->title = $this->title.' - '.Yii::t( 'app', Yii::$app->name);
// 輸出 VIEW 的 HTML
?>
<main role="main" class="flex-shrink-0 mt-3">
    <?php
    echo Html::tag('div', 
        // 主體
        $content,
        ['class' => 'container-fluid']
    );
    ?>
</main>

<?php
echo app\widgets\ScrollToTop::widget();
// 捲軸美化
\app\assets\OverlayScrollbarsAsset::register($this);

// 整個網站字體變大、自定義部分樣式
$this->registerCssFile('@web/css/site.css', [
	'depends' => [\app\assets\AppAsset::class],
]);
$this->registerCssFile('@web/css/login/meeting.css', [
	'depends' => [\app\assets\AppAsset::class],
]);
/* body - end */
$this->endContent();    // HTML 基本樣版/結束
