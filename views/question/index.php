<?php

use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\bootstrap5\Html;
use app\components\helper\ArrayHelper;

$title = '問題管理';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
    $this->params['title'] = $title;
    $this->params['voteInfo'] = $voteInfo;

echo Html::tag('div',
    Html::a('建立問題', ['question/create', 'voteID' => $voteID], ['class' => 'btn btn-primary mb-2 me-2']).
    ($voteInfo->round > 1 ? Html::a('匯入輪次問題', ['question/round-import', 'voteID' => $voteID], ['class' => 'btn btn-primary mb-2']) : '')
);
echo \app\widgets\Alert::widget();

Pjax::begin(['id' => 'questions']);
echo GridView::widget([
    'id' => 'questions',
    'dataProvider' => $dataProvider,
    'filterModel' => $questions,
    'summary' => Yii::$app->tablePag->getSummaryText('questions'),
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'columns' => [
        'autoId' => [
            'header' => '編號',
            'class' => 'yii\grid\SerialColumn',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'title' => [
            'label' => '標題',
            'attribute' => 'title',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
            'visible' => strtolower(Yii::$app->language) == 'zh-tw'
        ],
        'titleE' => [
            'label' => '英文標題',
            'attribute' => 'titleE',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
            'visible' => strtolower(Yii::$app->language) != 'zh-tw'
        ],
        'party' => [
            'label' => '分組',
            'attribute' => 'party',
            'value' => function ($model, $key, $index, $column) use ($parties)
            {
                return $parties[$model->party];
            },
            'filter' => ArrayHelper::addSpace($parties),
            'filterInputOptions' => ['class' => 'form-select'],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '操作',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column)
            {
                return join(' ',
                    [
                        Html::a('編輯', [
                            'question/update',
                            'voteID' => $model->voteID,
                            'questionID' => $model->questionID
                        ]),
                        Html::a('刪除', [
                            'question/delete',
                            'voteID' => $model->voteID,
                            'questionID' => $model->questionID
                        ],[
                            'class' => 'text-danger',
                            'data-confirm' => '確定要刪除這個項目嗎？',
                            'data-method' => 'post',
                        ])
                    ]
                );
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
    ],
]);
Pjax::end();