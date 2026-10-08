<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\Pjax;

use app\models\Questions;
use kartik\grid\GridView;
use yii\bootstrap5\Modal;
use yii\bootstrap5\Dropdown;
use yii\helpers\HtmlPurifier;
use app\components\helper\ArrayHelper;
use app\models\Votes;

$title = '選票管理';
$this->title = $title;

$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

$currentReturnUrl = Yii::$app->request->url;
$plaintextUnlocked = $plaintextUnlocked ?? false;
$plaintextUnlockRemaining = $plaintextUnlockRemaining ?? null;
$reauthRequired = $reauthRequired ?? false;
$useTotp = $useTotp ?? false;

echo \app\widgets\Alert::widget();

$confirm = function($text)
{
    return ['confirm' => "確定要刪除【{$text}】選票？\n* 此操作將無法還原！"];
};

$delBallotItems[] = [
    'label' => '所有分組',
    'url' => ['ballot/delete-all', 'voteID' => $voteInfo->voteID],
    'linkOptions'=>['data' => $confirm('所有分組')]
];
foreach($parties as $party => $division)
{
    $delBallotItems[] = [
        'label' => $division,
        'url' => ['ballot/delete-all', 'voteID' => $voteInfo->voteID, 'party' => $party],
        'linkOptions'=>['data'=>$confirm($division)]
    ];
}

// 選票列印(問題)
$printBallotItems = [];

// 投票規則
Modal::begin([
    'id' => 'ballot-rule',
    'title' => Yii::t('app', '投票規則'),
    'options' => ['class' => 'bg-dark'],
    'clientOptions' => ['backdrop' => false],
    'scrollable' => true,
    'size' => 'modal-xl',
    'footer' => Html::button('確認', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal'])
]);
$questionByParty = ArrayHelper::map($questions ?: [], 'questionID', 'title', 'party');
foreach($questions as $key => $question)
{
    // 選票列印(問題)
    $printBallotItems[] = [
        'label' => $question['title'],
        'url' => ['ballot/print', 'voteID' => $voteInfo->voteID, 'questionID' => $question['questionID']],
        'linkOptions' => ['target' => '_blank', 'rel' => 'noopener noreferrer']
    ];
    // 投票規則
    echo Html::tag('div', Yii::$app->getI18n()->format(
        '組別【{party}】問題【{title}】圈選人數: 最多 {mostNum} 人，最少 {leastNum} 人，其餘皆為無效票！',
        [
            'party' => Html::encode($parties[$question['party']], true),
            'title' => Html::encode($question['title'], true),
            'mostNum' => $question['numBallots'],
            'leastNum' => $question['leastNumBallots'],
        ],
        Yii::$app->language
    ), ['class'=>'text-center fw-bold text-danger']);
}

Modal::end();

foreach($parties as $party => $division)
{
    $creatorItems[] = [
        'label' => $division,
        'url' => ['select-creator', 'voteID' => $voteInfo->voteID, 'party' => $party]
    ];
}

if($voteInfo->type == Votes::TYPE_ANON) {
    $creatorURL = 'select-passwd';
}

$unlockBtn = ($voteInfo->type == Votes::TYPE_ANON && $reauthRequired && !$plaintextUnlocked)
    ? Html::a('顯示明文', '#collapseUnlockPlaintext', [
        'class' => 'btn btn-outline-danger me-2',
        'data-bs-toggle' => 'collapse',
        'aria-expanded' => 'false',
        'aria-controls' => 'collapseUnlockPlaintext',
    ])
    : '';

// 功能按鈕
echo Html::tag('div',
    $unlockBtn.
    Html::tag( 'div',
        Html::a('建立選票', [$creatorURL, 'voteID'=>$voteInfo->voteID], ['class' => 'btn btn-primary me-2'])
        , ['class' => 'btn-group d-inline-block']
    ).
    Html::tag('div',
        Html::a('匯入選票', ['import', 'voteID'=>$voteInfo->voteID], ['class' => 'btn btn-primary me-2'])
        , ['class' => 'btn-group']
    ).
    Html::tag( 'div',
        Html::a('刪除全部選票 <b class="caret"></b>', '#', ['class' => 'btn btn-danger dropdown-toggle me-2','data-bs-toggle'=>'dropdown']).
        Dropdown::widget(['items' => $delBallotItems])
        , ['class' => 'btn-group']
    ).
    Html::tag( 'div',
        Html::a('選票統計匯出', '#collapseBallotExport', [
            'class' => 'btn btn-info me-2',
            'data-bs-toggle' => 'collapse',
            'aria-expanded' => 'false',
            'aria-controls' => 'collapseBallotExport',
        ])
        , ['class' => 'btn-group d-inline-block']
    ).
    Html::tag( 'div',
        Html::a('選票列印 <b class="caret"></b>', '#', ['class' => 'btn btn-secondary dropdown-toggle me-2','data-bs-toggle'=>'dropdown']).
        Dropdown::widget(['items' => $printBallotItems])
        , ['class' => 'btn-group']
    ).
    Html::button(Yii::t('app', '投票規則'), [
        'class' => 'btn btn-warning me-2',
        'data' => [
            'bs-toggle' => 'modal',
            'bs-target' => '#ballot-rule'
        ]
    ])
    , ['class' => 'mb-2']
);

if ($voteInfo->type == Votes::TYPE_ANON) {
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
}

echo $this->render('_export', [
    'voteID' => $voteInfo->voteID,
    'reauthRequired' => $reauthRequired,
    'useTotp' => $useTotp,
    'contentQuestions' => ArrayHelper::map($questions ?: [], 'questionID', 'title'),
]);

echo Html::tag('div', '', ['class' => 'mb-3']);

Pjax::begin(['id' => 'ballots']);
$dataProvider->sort = false; // 關閉排序
echo GridView::widget([
    'id' => 'ballots',
    'dataProvider' => $dataProvider,
    'filterModel' => $model,
    'summary' => Yii::$app->tablePag->getSummaryText('ballots'),
    'panel' => [
        'type' => GridView::TYPE_LIGHT,
        'heading' => '',
        'after' => false,
        // 'footer' => false
    ],
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'toolbar' => [
        '{export}',
        [
            'content'=>
                Html::a('<i class="fas fa-redo"></i>', Url::to(['ballot/index', 'voteID' => $voteInfo->voteID]), [
                    'class' => 'btn btn-secondary btn-default ms-2', 
                    'title' => Yii::t('app', 'Reset Grid')
                ]),
        ],
    ],
    'exportConfig' => [
        GridView::CSV => [
            'filename' => Yii::t('app', HtmlPurifier::process($voteInfo->Name).'選票列表_'.date('YmdHis')),
        ],
        GridView::HTML => [
            'filename' => Yii::t('app', HtmlPurifier::process($voteInfo->Name).'選票列表_'.date('YmdHis')),
        ],
        GridView::EXCEL => [
            'filename' => Yii::t('app', HtmlPurifier::process($voteInfo->Name).'選票列表_'.date('YmdHis')),
            'alertMsg' => '<b class="text-danger">'.Yii::t('app', '下載後檔案必須使用LibreOffice開啟').'</b>',
        ],
        GridView::JSON => [
            'filename' => Yii::t('app', HtmlPurifier::process($voteInfo->Name).'選票列表_'.date('YmdHis')),
        ],
    ],
    'columns' => [
        // toggle顯示問題/票數/是否為有效票
        [
            'class' => '\kartik\grid\ExpandRowColumn',
            // 'detailRowCssClass' => 'table-default',
            // 'expandOneOnly' => true,
            'value' => function ($model, $key, $index) {
                return GridView::ROW_COLLAPSED;
            },
            'detail' => function ($model, $key, $index, $column) use ($ballotCountAry, $questionByParty, $formManageCount, $questions) {
                $body = '';
                $key = 1;
                // 分組
                $partyQuestions = $questionByParty[$model->party] ?? [];
                foreach ($partyQuestions as $questionID => $title) {
                    $num = empty($ballotCountAry[$model->ballotID][$questionID]) ? 0 : $ballotCountAry[$model->ballotID][$questionID];
                    $body .= sprintf('
                            <tr>
                                <th scope="row">%s</th>
                                <td>%s</td>
                                <td>%s</td>
                                <td>%s</td>
                            </tr>
                        ', $key, $title, $num,
                        $formManageCount->checkBallotValid($questionID, $ballotCountAry[$model->ballotID] ?? [], $questions, $model->ballotID) ?
                        Yii::$app->params['ct.ballots.selectNum']['1'] : Html::tag('span', Yii::$app->params['ct.ballots.selectNum']['0'], ['class' => 'text-danger fw-bolder'])
                    );
                    $key++;
                }
                // 全部分組N
                $allPartyQuestions = $questionByParty[Questions::ALL_PARTY_CODE] ?? [];
                foreach ($allPartyQuestions as $questionID => $title) {
                    $num = empty($ballotCountAry[$model->ballotID][$questionID]) ? 0 : $ballotCountAry[$model->ballotID][$questionID];
                    $body .= sprintf('
                            <tr>
                                <th scope="row">%s</th>
                                <td>%s</td>
                                <td>%s</td>
                                <td>%s</td>
                            </tr>
                        ', $key, $title, $num, 
                        $formManageCount->checkBallotValid($questionID, $ballotCountAry[$model->ballotID] ?? [], $questions, $model->ballotID) ?
                        Yii::$app->params['ct.ballots.selectNum']['1'] : Html::tag('span', Yii::$app->params['ct.ballots.selectNum']['0'], ['class' => 'text-danger fw-bolder'])
                    );
                    $key++;
                }
                return sprintf('
                    <table class="table table-light mb-0">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">問題</th>
                                <th scope="col">投票數</th>
                                <th scope="col">狀態</th>
                            </tr>
                        </thead>
                        <tbody>
                            %s
                        </tbody>
                    </table>', $body);
            },
        ],
        // [
        //     'label' => '選票編號',
        //     'attribute' => 'ballotID',
        //     'headerOptions'  => ['class' => 'align-middle text-center'],
        //     'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 3rem'],
        // ],
        [
            'label' => '代為輸入',
            'attribute' => 'isAdminAdd',
            'value' => function ($model, $key, $index, $column)
            {
                return \app\models\Ballots::isAdminAddLabel($model->isAdminAdd);
            },
            'filter' => ArrayHelper::addSpace(Yii::$app->params['ct.yesOrNoAry']),
            'filterInputOptions' => ['class' => 'form-select'],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap', 'style' => 'width: 6rem'],
        ],
        [
            'label' => '組別',
            'attribute' => 'party',
            'value' => function ($model, $key, $index, $column) use ($voteInfo, $parties)
            {
                return $parties[$model->party];
            },
            'filter' => $parties,
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap'],
        ],
        [
            'label' => '投票者',
            'attribute' => 'creator',
            'value' => function ($model, $key, $index, $column) use ($sysidList)
            {
                if(is_null($sysidList))
                    return $model->creator;
                return $sysidList[$model->creator];
            },
            'filter' => ArrayHelper::addSpace($sysidList),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap'],
        ],
        [
            'label' => 'IP',
            'attribute' => 'ip',
            'value' => function ($model, $key, $index, $column)
            {
                return $model->ip;
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 3rem'],
            'visible' => Yii::$app->user->can('sa')
        ],
        [
            'label' => '投票時間',
            'attribute' => 'insTime',
            'value' => function ($model, $key, $index, $column)
            {
                return $model->insTime;
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap'],
        ],
        [
            'label' => '修改者',
            'attribute' => 'modifier',
            'value' => function ($model, $key, $index, $column) use ($sysidList)
            {
                // 未修改：空白 modifier／零日期，或 updTime 仍等於新增時間（含舊資料）
                $upd = trim((string) $model->updTime);
                $ins = trim((string) $model->insTime);
                if ($model->modifier === null || trim((string) $model->modifier) === ''
                    || $upd === '' || $upd === '0000-00-00 00:00:00'
                    || ($ins !== '' && $upd === $ins)) {
                    return '無';
                }
                if (is_null($sysidList)) {
                    return $model->modifier;
                }
                return $sysidList[$model->modifier] ?? $model->modifier;
            },
            'filter' => ArrayHelper::addSpace($sysidList),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap'],
        ],
        [
            'label' => '修改時間',
            'attribute' => 'updTime',
            'value' => function ($model, $key, $index, $column)
            {
                $upd = trim((string) $model->updTime);
                $ins = trim((string) $model->insTime);
                if ($upd === '' || $upd === '0000-00-00 00:00:00'
                    || ($ins !== '' && $upd === $ins)) {
                    return '無';
                }
                return $model->updTime;
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap'],
        ],
        [
            'label' => '操作',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column)
            {
                return join(' ',
                    [
                        Html::a( '編輯', [
                            'ballot/edit',
                            'voteID' => $model->voteID,
                            'party'  => $model->party,
                            'ballotID' => $model->ballotID
                        ]),
                        Html::a( '刪除', [
                            'ballot/delete',
                            'voteID' => $model->voteID,
                            'ballotID' => $model->ballotID
                        ],[
                            'class' => 'text-danger',
                            'data' => ['bs-confirm' => "確定要刪除此選票？\n* 此操作將無法還原！"],
                        ])
                    ]
                );
            },
            'headerOptions'  => ['class' => 'align-middle text-center skip-export'],
            'contentOptions' => ['class' => 'align-middle text-center skip-export text-nowrap'],
        ],
    ],
]);
Pjax::end();
