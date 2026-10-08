<?php

use yii\helpers\Html;

$title = '使用者修改';
$this->title = $title;
$this->params['secNavType'] = 'manage'; // 啟用共用之 manage 二級導航

echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', $title), ['class' => 'text-center']);
echo Html::tag('hr');

echo $this->render('_form', compact('model'));