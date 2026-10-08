<?php
use yii\helpers\Html;
use kartik\grid\GridView;
use rmrevin\yii\fontawesome\FAS;

$title = '投票群組';
$this->title = $title;

echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
