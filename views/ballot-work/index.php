<?php
use yii\helpers\Html;

$title = '開票作業';
$this->title = $title;

echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
echo Html::tag('hr');

$route = 'ballot-work/setting';
echo $this->render('@views/partials/_vote_list', compact('dataProvider', 'model', 'sysidList', 'route'));