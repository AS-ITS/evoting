<?php

use app\assets\AppAsset;

$this->beginContent('@app/useTemplate/main.php');    // HTML 基本樣版/開始

$this->title = $this->title.' - '.Yii::t( 'app', Yii::$app->name);

AppAsset::register($this);

// 輸出 VIEW 的 HTML
$this->registerCssFile('@web/css/site.css', [
	'depends' => [\app\assets\AppAsset::class],
]);

echo $content;

/* body - end */
$this->endContent();    // HTML 基本樣版/結束
