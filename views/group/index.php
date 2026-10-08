<?php
use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use app\components\helper\ArrayHelper;

$title = '群組管理';
$this->title = $title;

echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
echo Html::tag('hr');
if(Yii::$app->user->can('groupCreate'))
{
    // echo Html::tag('p', Html::a( '建立群組', ['group/create'], ['class' => 'btn btn-primary']));
    echo Html::tag('p',Html::tag('div',
        Html::a('建立群組', '#collapseNew', [
            'class' => 'btn btn-primary me-2',
            'data-bs-toggle'=>'collapse',
            'aria-expanded'=>'false',
            'aria-controls'=>'collapseNew'
        ])
        , ['class'=>'dropdown']
    ));
    echo $this->render('_new', ['model' => $model]);
}

// Pjax::begin(['linkSelector' => '.pjax']);
Pjax::begin(['id' => 'groups']);
$dataProvider->sort = false; // 關閉排序
echo GridView::widget([
    'id' => 'groups',
    'dataProvider' => $dataProvider,
    'filterModel' => $model,
    'summary' => Yii::$app->tablePag->getSummaryText('groups'),
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'columns' => [
        [
            'attribute' => 'groupName',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column)
            {
                return Html::a(Html::encode($model->groupName), ['group/view-vote','groupId' => $model->groupId]);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'attribute' => 'isRoutine',
            'value' => function ($model, $key, $index, $column)
            {
                return trim(Yii::$app->params['ct.group.routineAry'][$model->isRoutine]);
            },
            'filter' => ArrayHelper::addSpace(Yii::$app->params['ct.group.routineAry']),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 6rem'],
        ],
    ],
]);
Pjax::end();
