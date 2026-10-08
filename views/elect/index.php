<?php
use yii\helpers\Html;

$title = '投票管理';
$this->title = $title;

echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
echo Html::tag('hr');
echo Html::tag('p', Html::a( '建立投票', ['elect/create-vote'], ['class' => 'btn btn-primary']));

$route = 'elect/edit-vote';
echo $this->render('@views/partials/_vote_list', compact('dataProvider', 'model', 'sysidList', 'route'));