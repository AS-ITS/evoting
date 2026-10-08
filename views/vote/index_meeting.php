<?php

use app\components\Model;
use yii\helpers\Html;

$this->title = '首頁';

$class = Yii::$app->language == 'en-US' ? 'list-en' : 'list-tw'; 
$id = Yii::$app->language == 'en-US' ? 'enTitle' : 'voteTitle';
echo \app\widgets\Alert::widget();
echo Html::tag('h1', Model::i18n($config->homeTitleE, $config->homeTitle, false), [
    'id' => $id, 
    'class' => 'text-center'
]);
echo \yii\widgets\ListView::widget([
    'dataProvider' => $dataProvider,
    'layout' => '{items}',
    'itemView' => function ($model, $key, $index, $widget)
    {
        $parts = explode('<br />', nl2br($model->voteName));
        $voteName = end($parts);
        $html = '';
        $html .= Html::a($voteName, ['vote/vote-detail', 'voteID' => $model->voteID]);
        return Html::tag('blockquote', $html, ['class' => 'blockquote mb-0']);
    },
    'itemOptions' => [
        'tag'   => 'li',
        'class' => "list-group-item bg-transparent border-0 $class",
    ],
    'options' => [
        'tag' => 'ul',
        'class' => 'list-group list-group-flush text-center',
    ],
]);
