<?php
use app\models\Votes;
use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use app\components\Model;
use rmrevin\yii\fontawesome\FAS;
use app\components\helper\ArrayHelper;

Pjax::begin(['id' => 'votes']);
$dataProvider->sort = false; // 關閉排序
echo GridView::widget([
    'id' => 'votes',
    'dataProvider' => $dataProvider,
    'filterModel' => $model,
    'summary' => Yii::$app->tablePag->getSummaryText('votes'),
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'columns' => [
        [
            'label' => '編號',
            'attribute' => 'voteID',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column)
            {
                return $model->voteID;
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 6rem'],
            'encodeLabel' => false,
        ],
        [
            'label' => '類型',
            'attribute' => 'type',
            'value' => function ($model, $key, $index, $column)
            {
                // 刪除括號及括號裡面的文字
                return trim(preg_replace('/\s*\([^)]*\)/', '', Yii::$app->params['ct.voteType'][$model->type]));
                // return Yii::$app->params['ct.voteType'][$type];
            },
            'filter' => ArrayHelper::addSpace(Yii::$app->params['ct.voteType']),
            'filterInputOptions' => ['class' => 'form-select'],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center ', 'style' => 'width: 6rem'],
        ],
        [
            'label' => '流程',
            'attribute' => 'process',
            'value' => function ($model, $key, $index, $column)
            {
                // TODO: 完成投票
                $toDate = strtotime(date('Y-m-d H:i:s')); // strtotime(date('Y-m-d H:i:s'))
                switch(true)
                {
                    // case $model->active == Votes::STATUS_TERMINATE: // 狀態中止
                    //     $process = '0';
                    //     break;
                    case ($model->active == Votes::STATUS_READY || $toDate < strtotime($model->openStart)):
                        $process = '1'; // 等待投票
                        break;

                    case ($model->active == Votes::STATUS_ACTIVE && $toDate >= strtotime($model->openStart) && $toDate < strtotime($model->openEnd)):
                        $process = '2'; // 開始投票
                        break;

                    case ($model->active == Votes::STATUS_ACTIVE && $toDate >= strtotime($model->openEnd) && $toDate < strtotime($model->verifyStart)): //1614673716
                        $process = '3'; // 等待驗證
                        break;

                    case ($model->active == Votes::STATUS_ACTIVE && $toDate >= strtotime($model->verifyStart) && $toDate < strtotime($model->verifyEnd)):
                        $process = '4'; // 開始驗證
                        break;

                    case ($model->active == Votes::STATUS_TERMINATE && $toDate >= strtotime($model->verifyEnd) && $model->isFinish == '0'):
                        $process = '5'; // 等待開票
                        break;

                    case ($toDate >= strtotime($model->verifyEnd) && $model->isFinish == '1'):
                        $process = '6'; // 完成投票
                        break;

                    case ($model->active == Votes::STATUS_BACKFILL):
                        $process = '7'; // 補登投票
                        break;

                    default:
                        $process = '0';
                        break;
                }
                return Yii::$app->params['ct.voteProcessAry'][$process];
            },
            'filter' => ArrayHelper::addSpace(Yii::$app->params['ct.voteProcessAry']),
            'filterInputOptions' => ['class' => 'form-select'],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 6rem'],
        ],
        [
            'label' => '名稱',
            'attribute' => 'Name',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column) use ($route)
            {
                $voteName = Model::i18n($model->NameE, $model->Name, false);
                return Html::a($voteName, [$route, 'voteID' => $model->voteID]);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '創辦人',
            'attribute' => 'creator',
            'value' => function ($model, $key, $index, $column) use ($sysidList)
            {
                return $sysidList[$model->creator];
            },
            'filter' => ArrayHelper::addSpace($sysidList),
            'filterInputOptions' => ['class' => 'form-select'],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 8rem'],
            'visible' => Yii::$app->user->identity->inUserRole('sa'),
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{delete}',
            'buttons' => [
                'delete' => function ($url, $model, $key) {
                    return Html::a(
                        FAS::icon('trash-alt'),
                        ['delete-vote', 'voteID' => $model->voteID],
                        [
                            'data-confirm' => '確定要刪除這個項目嗎？',
                            'data-method' => 'post',
                            'class' => 'text-decoration-none text-danger'
                        ]
                    );
                },
            ],
            'visibleButtons' => [
                'delete' => function ($model, $key, $index) {
                    return Yii::$app->user->identity->inUserRole('sa') || Yii::$app->user->can('voteInfo', ['voteID' => $model->voteID]);
                },
            ],
            'contentOptions' => ['class' => 'align-middle text-center'],
            'visible' => Yii::$app->user->identity->inUserRole('sa') && Yii::$app->requestedRoute == 'elect/index',
        ],
    ],
]);
Pjax::end();