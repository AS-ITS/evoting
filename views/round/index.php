<?php

use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\bootstrap5\Html;

$title = '輪次管理';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

echo Html::tag('div',
    Html::a('建立新一輪投票', ['round/create', 'voteID' => $voteID], ['class' => 'btn btn-primary mb-2'])
, ['class' => 'btn-group']);
echo \app\widgets\Alert::widget();
$dataProvider->sort = false;
Pjax::begin(['id' => 'round']);
echo GridView::widget([
    'id' => 'round',
    'dataProvider' => $dataProvider,
    'summary' => Yii::$app->tablePag->getSummaryText('round'),
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'columns' => [
        'round' => [
            'label' => '輪次',
            'attribute' => 'round',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center col-1'],
        ],
        'name' => [
            'label' => '輪次名稱',
            'attribute' => 'name',
            'value' => function ($model, $key, $index, $column)
            {
                return Html::a($model->{$column->attribute}, [
                    'round/update',
                    'voteID' => $model->voteID,
                    'round' => $model->round,
                ]);
            },
            'format' => 'raw',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '操作',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column) use ($voteInfo)
            {
                if ($voteInfo->round == $model->round) {
                    return '當前輪次';
                }
                else {
                    return Html::a('切換', [
                        'round/switch',
                        'voteID' => $model->voteID,
                        'round' => $model->round,
                    ]);
                }
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center col-3'],
        ],
    ],
]);
Pjax::end();