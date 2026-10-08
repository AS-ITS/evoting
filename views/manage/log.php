<?php

use yii\helpers\Url;
use yii\bootstrap5\Html;
use kartik\grid\GridView;
use yii\bootstrap5\Modal;
use rmrevin\yii\fontawesome\FAS;

$title = 'Log';
$this->title = $title;
$this->params['secNavType'] = 'manage'; // 啟用共用之 manage 二級導航
$users += ['Client' => '遊客'];

Modal::begin([
    'id' => 'log-detail',
    'title' => "LOG詳細資料()",
    'options' => ['class' => 'bg-dark'],
    'clientOptions' => ['backdrop' => false],
    'scrollable' => true,
    'size' => 'modal-xl',
    'footer' => Html::button('返回', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal'])
]);

echo Html::tag('div', '', ['id' => 'modal-log-detail']);

Modal::end();

$this->registerJs(<<<JS
    $('#log-detail').on('show.bs.modal', function (event) {
        let button = $(event.relatedTarget);
        let id = button.data('id');
        let url = button.data('url');
        let user = button.data('user');
        $.get(url, function(data) {
            $('#modal-log-detail').html(data)
        });
        $('#log-detail').find('.modal-title').text('LOG詳細資料('+id+') - '+user)
    })
JS
);

echo Html::tag('div', '', ['class'=>'mb-3']);

echo Html::button(FAS::icon('calendar-alt', ['class' => 'me-2']).'日期搜尋', [
    'class' => 'btn btn-info my-2',
    'type' => 'button',
    'data-bs-toggle' => 'collapse',
    'data-bs-target' => '#date-search',
    'aria-expanded' => false,
    'aria-controls' => 'date-search'
]);

echo Html::tag('div', 
    $this->render('_log_filter', ['model' => $model]),
    ['class' => 'collapse', 'id' => 'date-search']
);

echo GridView::widget([
    'id' => 'logs',
    'dataProvider' => $dataProvider,
    'filterModel' => $model,
    'pjax' => true,
    'pjaxSettings' => [
        'loadingCssClass' => false,
        'options' => [
            'id' => 'logs'
        ]
    ],
    'condensed' => true,
    'summary' => Yii::$app->tablePag->getSummaryText('logs'),
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'panel' => [
        'type' => GridView::TYPE_DEFAULT,
        'heading' => '',
        'after' => false,
        // 'footer' => false
    ],
    'toolbar' => [
        [
            'content'=>
                Html::a('<i class="fas fa-redo"></i>', Url::to(['manage/log']), [
                    'class' => 'btn btn-secondary btn-default ms-2', 
                    'title' => Yii::t('app', 'Reset Grid')
                ]),
        ],
    ],
    'columns' => [
        [
            'label' => '編號',
            'attribute' => 'id',
            'value' => function ($model, $key, $index, $column)
            {
                return $model->id;
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '類型',
            'attribute' => 'type',
            'value' => function ($model, $key, $index, $column)
            {
                return Yii::$app->params['log.type'][$model->type] ?? $model->type;
            },
            'filter' => Yii::$app->params['log.type'],
            'filterType' => GridView::FILTER_SELECT2,
            'filterWidgetOptions' => [
                'options' => ['prompt' => ''],
                'pluginOptions' => [
                    'allowClear' => true,
                ],
            ],
            'headerOptions'  => ['class' => 'align-middle text-center col-3'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '使用者',
            'attribute' => 'user',
            'value' => function ($model, $key, $index, $column) use ($users)
            {
                return $users[$model->user] ?? $model->user;
            },
            'filter' => $users,
            'filterType' => GridView::FILTER_SELECT2,
            'filterWidgetOptions' => [
                'options' => ['prompt' => ''],
                'pluginOptions' => [
                    'allowClear' => true,
                ],
            ],
            'headerOptions'  => ['class' => 'align-middle text-center col-2'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => 'IP',
            'attribute' => 'ip',
            'value' => function ($model, $key, $index, $column)
            {
                return $model->ip;
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '建立時間',
            'attribute' => 'created_at',
            'value' => function ($model, $key, $index, $column)
            {
                if(is_null($model->created_at) || trim($model->created_at) == '0000-00-00 00:00:00')
                    return '無';
                return $model->created_at;
            },
            'filter' => false,
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '操作',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column) use ($users)
            {
                return Html::button(FAS::icon('info', ['class' => 'text-info']), 
                    [
                        'class' => 'border-0 bg-transparent',
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#log-detail',
                        'data-id' => $model->id,
                        'data-url' => Url::to(['manage/log-detail', 'id' => $model->id]),
                        'data-user' => $users[$model->user] ?? $model->user,
                    ]
                );
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
    ],
]);