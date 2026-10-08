<?php
use yii\helpers\Html;

$title = '群組投票管理';
$this->title = $title;
$this->params['secNavType'] = 'group';

echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
echo Html::tag('hr');

$route = 'elect/edit-vote';
echo $this->render('@views/partials/_vote_list', compact('dataProvider', 'model', 'sysidList', 'route'));