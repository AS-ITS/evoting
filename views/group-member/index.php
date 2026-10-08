<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use rmrevin\yii\fontawesome\FAS;

$title = '群組成員管理';
$this->title = $title;
$this->params['secNavType'] = 'group';

echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
echo Html::tag('hr');
if(Yii::$app->user->can('groupCreateMember', ['groupId' => $groupId]))
{
    echo Html::tag('p', Html::a('新增成員', ['group-member/create', 'groupId' => $groupId], ['class' => 'btn btn-primary']));
}

?>
<div class="row">
    <div class="col-md">
        <?php
        $groupMemberList->sort = false; // 關閉排序
        Pjax::begin(['id' => 'group-members']);
        echo GridView::widget([
            'id' => 'group-members',
            'dataProvider' => $groupMemberList,
            'summary' => Yii::$app->tablePag->getSummaryText('group-members'),
            'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
            'columns' => [
                [
                    'attribute' => 'cn',
                    'value' => function ($model, $key, $index, $column) use ($membersInfo)
                    {
                        return $membersInfo[Html::encode($model->cn)] ?? Html::encode($model->cn);
                    },
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center'],
                ],
                [
                    'attribute' => 'isWrite',
                    'format' => 'raw',
                    'value' => function ($model, $key, $index, $column)
                    {
                        return $model->isWrite == 'N' ? FAS::icon('times', ['class' => 'text-danger']) : FAS::icon('check', ['class' => 'text-success']);
                    },
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center'],
                ],
                [
                    'attribute' => 'isOwner',
                    'format' => 'raw',
                    'value' => function ($model, $key, $index, $column)
                    {
                        return $model->isOwner == 'N' ? FAS::icon('times', ['class' => 'text-danger']) : FAS::icon('check', ['class' => 'text-success']);
                    },
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{update}&nbsp;&nbsp;{delete}',
                    'buttons' => [
                        'update' => function ($url, $model, $key) use ($groupId) {
                            return Html::a(
                                FAS::icon('pen'),
                                $url,
                                ['class' => 'text-decoration-none text-secondary']
                            );
                        },
                        'delete' => function ($url, $model, $key) use ($groupId) {
                            return Html::a(
                                FAS::icon('trash-alt'),
                                $url,
                                [
                                    'data-confirm' => '確定要刪除這個項目嗎？',
                                    'data-method' => 'post',
                                    'class' => 'text-decoration-none text-secondary'
                                ]
                            );
                        },
                    ],
                    'visibleButtons' => [
                        'update' => function ($model, $key, $index) {
                            return \Yii::$app->user->can('groupEditMember', ['groupId' => $model->groupId]) && $model->cn != Yii::$app->user->id;
                        },
                        'delete' => function ($model, $key, $index) {
                            return \Yii::$app->user->can('groupDeleteMember', ['groupId' => $model->groupId]) && $model->cn != Yii::$app->user->id;
                        },
                    ],
                    'contentOptions' => ['class' => 'align-middle text-center'],
                    'visible' => Yii::$app->user->can('groupEditMember', ['groupId' => $groupId]) || Yii::$app->user->can('groupDeleteMember', ['groupId' => $groupId])
                ],
            ],
        ]);
        Pjax::end();
        ?>
    </div>
</div>