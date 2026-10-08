<?php

use yii\helpers\Url;
use yii\bootstrap5\Html;

$this->beginContent('@app/useTemplate/main.php');    // HTML 基本樣版/開始
/* body - start */

$this->title = $this->title . ' - ' . Yii::t('app', Yii::$app->name);
// 輸出 VIEW 的 HTML
echo $this->render('@views/partials/_navbar');
?>
<header class="header-container">
    <nav class="navbar pl-0">
        <?php if ($logoUrl = \app\components\Branding::logoUrl()): ?>
        <img id="logo" src="<?= \yii\helpers\Html::encode($logoUrl) ?>" alt="logo">
        <?php endif; ?>
        <div class="mb-2 my-lg-0">
            <a id="lang_select" class="btn my-2 my-sm-0" href=<?= Url::to(['site/change-language']) ?>>
                <img src="<?= Yii::getAlias('@web/img/translation.png'); ?>" alt="translation">
                <span><?= Yii::t('app', 'English Version'); ?></span>
            </a>
        </div>
    </nav>
</header>

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
echo app\widgets\ScrollToTop::widget();
// 捲軸美化
\app\assets\OverlayScrollbarsAsset::register($this);

// 整個網站字體變大、自定義部分樣式
$this->registerCssFile('@web/css/meeting.css', [
    'depends' => [\app\assets\AppAsset::class],
]);
// 全局JS
$this->registerJsFile('@web/js/global.js', [
    'depends' => [\app\assets\AppAsset::class],
]);
/* body - end */
$this->endContent();    // HTML 基本樣版/結束
