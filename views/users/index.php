<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use rmrevin\yii\fontawesome\FAS;

$title = '使用者管理';
$this->title = $title;
$this->params['secNavType'] = 'manage'; // 啟用共用之 manage 二級導航

echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
echo Html::tag('hr');
echo Html::tag('p',
    Html::a('建立帳號', ['users/create'], ['class' => 'btn btn-primary me-2'])
    . Html::a('雙因素驗證', ['users/totp'], ['class' => 'btn btn-outline-secondary me-2'])
    . (Yii::$app->user->can('sa')
        ? Html::a('同步 RBAC 權限', ['site/sync-role-per'], ['class' => 'btn btn-outline-danger'])
        : '')
);

Pjax::begin(['id' => 'users']);
$dataProvider->sort = false; // 關閉排序
echo GridView::widget([
    'id' => 'users',
    'dataProvider' => $dataProvider,
    'summary' => Yii::$app->tablePag->getSummaryText('users'),
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'columns' => [
        [
            'label' => '帳號',
            'attribute' => 'cn',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column)
            {
                return Html::a($model->cn, ['users/update', 'cn' => $model->cn]);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
            'encodeLabel' => false,
        ],
        [
            'label' => '姓名',
            'attribute' => 'name',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '角色',
            'attribute' => 'roles',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '密碼',
            'attribute' => 'password_plain',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column)
            {
                return $model->password ? '********' : '<span class="text-muted">未設定</span>';
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{delete}',
            'buttons' => [
                'delete' => function ($url, $model, $key) {
                    return Html::a(
                        FAS::icon('trash-alt'),
                        ['delete', 'cn' => $model->cn],
                        [
                            'data-confirm' => '確定要刪除這個使用者嗎？',
                            'data-method' => 'post',
                            'class' => 'text-decoration-none text-danger'
                        ]
                    );
                },
            ],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
    ],
]);
Pjax::end();