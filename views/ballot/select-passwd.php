<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use app\models\Parties;
use app\models\PasswordDisplay;
use app\components\helper\ArrayHelper;

$title = '選票建立者';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

$division = $parties;
$currentReturnUrl = Yii::$app->request->url;

echo \app\widgets\Alert::widget();

$unlockBtn = ($reauthRequired && !$plaintextUnlocked)
    ? Html::a('顯示明文', '#collapseUnlockPlaintext', [
        'class' => 'btn btn-outline-danger me-2 mb-2',
        'data-bs-toggle' => 'collapse',
        'aria-expanded' => 'false',
        'aria-controls' => 'collapseUnlockPlaintext',
    ])
    : '';

echo $unlockBtn;

echo $this->render('@app/views/passwd/_unlock_plaintext', [
    'voteID' => $voteInfo->voteID,
    'returnUrl' => $currentReturnUrl,
    'unlockAction' => 'ballot/unlock-plaintext',
    'lockAction' => 'ballot/lock-plaintext',
    'plaintextUnlocked' => $plaintextUnlocked,
    'plaintextUnlockRemaining' => $plaintextUnlockRemaining,
    'reauthRequired' => $reauthRequired,
    'useTotp' => $useTotp,
    'buttonInToolbar' => true,
]);

// 表格
Pjax::begin(['linkSelector' => '.pjax']);
echo GridView::widget([
    'id' => 'myGrid',
    'dataProvider' => $dataProvider,
    'filterModel' => $model,
    'summary' => Yii::$app->tablePag->getSummaryText('myGrid'),
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'columns' => [
        [
            'label' => '組別',
            'attribute' => 'party',
            'value' => function ($model, $key, $index, $column) use ($division)
            {
                if(!isset($division[$model->party]))
                    return '(異常)';
                return $division[$model->party];
            },
            'filter' => ArrayHelper::addSpace($division),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 7rem'],
        ],
        [
            'label' => '編號',
            'attribute' => 'sn',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 5rem'],
        ],
        [
            'label' => '密碼',
            'attribute' => 'passwd',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column) use ($plaintextUnlocked, $voteInfo)
            {
                $url = [
                    'creator',
                    'voteID' => $voteInfo->isBindVote ? $voteInfo->voteID : $model->voteID,
                    'party' => ($voteInfo->isBindVote && !$voteInfo->partyOrNot) ? Parties::DEF_PARTY : $model->party,
                    'sysId' => $model->id
                ];
                $text = Html::encode(PasswordDisplay::formatGridValue($model->attributes, $plaintextUnlocked));
                return Html::a($text, $url);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '雙軌投票',
            'attribute' => 'dtrack',
            'value' => function ($model, $key, $index, $column)
            {
                return Yii::$app->params['ct.passwd.dtrackAry'][$model->dtrack];
            },
            'filter' => Yii::$app->params['ct.passwd.dtrackAry'],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '狀態',
            'attribute' => 'status',
            'value' => function ($model, $key, $index, $column)
            {
                return Yii::$app->params['ct.passwd.validAry'][$model->status];
            },
            'filter' => Yii::$app->params['ct.passwd.validAry'],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => function($model, $key, $index, $column)
            {
                $options = ['class' => 'align-middle text-center'];
                if($model->status == 0)
                {
                    $options['class'] .= ' text-danger';
                }
                else if($model->status == 1)
                {
                    $options['class'] .= ' text-success';
                }
                return $options;
            },
        ],
        [
            'label' => '本輪已投票',
            'attribute' => 'roundVoted',
            'enableSorting' => false,
            'value' => function ($model, $key, $index, $column) use ($votedBallot)
            {
                $isVoted = in_array((int) $model->id, $votedBallot, true);
                return Yii::$app->params['ct.yesOrNoAry'][(int) $isVoted];
            },
            'filter' => Yii::$app->params['ct.yesOrNoAry'],
            'filterAttribute' => 'voted',
            'filterInputOptions' => ['class' => 'form-select', 'prompt' => ''],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => function($model, $key, $index, $column) use ($votedBallot)
            {
                $isVoted = in_array((int) $model->id, $votedBallot, true);
                $options = ['class' => 'align-middle text-center'];
                if(!$isVoted)
                {
                    $options['class'] .= ' text-danger';
                }
                else
                {
                    $options['class'] .= ' text-success';
                }
                return $options;
            },
        ],
        [
            'label' => '標記',
            'attribute' => 'mark',
            'value' => function ($model, $key, $index, $column)
            {
                return $model->{$column->attribute};
            },
            'filter' => $marks,
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
    ],
]);
Pjax::end();
