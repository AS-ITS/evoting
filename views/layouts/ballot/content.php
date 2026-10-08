<?php

use yii\bootstrap5\Html;

$this->beginContent('@app/useTemplate/main.php');    // HTML 基本樣版/開始
/* body - start */

$this->title = $this->title . ' - ' . Yii::t('app', Yii::$app->name);
?>

<main role="main" class="flex-shrink-0 mt-3">
    <?php
    echo Html::tag(
        'div',
        // 主體
        $content,
        ['class' => 'container-fluid']
    );
    ?>
</main>

<?php
// 整個網站字體變大、自定義部分樣式
$this->registerCssFile('@web/css/ballot/content.css', [
    'depends' => [\app\assets\AppAsset::class],
]);
// 全局JS
$this->registerJsFile('@web/js/global.js', [
    'depends' => [\app\assets\AppAsset::class],
]);
/* body - end */
$this->endContent();    // HTML 基本樣版/結束
