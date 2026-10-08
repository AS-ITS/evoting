<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\bootstrap5\Dropdown;
use app\components\helper\ArrayHelper;
use app\models\PasswordDisplay;

$title = '密碼管理';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

$confirm = function($text) {
    return ['confirm' => "確定要刪除【{$text}】投票密碼？\n* 此操作將無法還原！"];
};
$systemConfig = Yii::$app->session->get('System.config');
$canDeletePassword = $systemConfig['canDeletePassword'] ?? false;
$currentReturnUrl = Yii::$app->request->url;
echo \app\widgets\Alert::widget();

if ($voteInfo->isBindVote) {
    echo Html::tag('div',
        Html::tag('span', '此投票與<font color="red">'.$bindVoteInfo->voteName.'</font>共用密碼', ['class' => 'h4']),
    ['class' => 'text-center alert alert-info']);
    echo Html::tag('div', '批次變更狀態將套用至共用密碼池，影響所有綁定此池的場次。', ['class' => 'alert alert-warning']);
}

$unlockBtn = ($reauthRequired && !$plaintextUnlocked)
    ? Html::a('顯示明文', '#collapseUnlockPlaintext', [
        'class' => 'btn btn-outline-danger me-2',
        'data-bs-toggle' => 'collapse',
        'aria-expanded' => 'false',
        'aria-controls' => 'collapseUnlockPlaintext',
    ])
    : '';

$toolbar = Html::a('設定狀態', '#collapseStatus', [
    'class' => 'btn btn-warning me-2',
    'data-bs-toggle' => 'collapse',
    'aria-expanded' => 'false',
    'aria-controls' => 'collapseStatus'
]).
Html::a('密碼匯出', '#collapseExport', [
    'class' => 'btn btn-info me-2',
    'data-bs-toggle' => 'collapse',
    'aria-expanded' => 'false',
    'aria-controls' => 'collapseExport'
]).
Html::a('生成密碼函', '#collapsePrint', [
    'class' => 'btn btn-info me-2',
    'data-bs-toggle'=>'collapse',
    'aria-expanded'=>'false',
    'aria-controls'=>'collapsePrint'
]);

if (!$voteInfo->isBindVote) {
    $toolbar = Html::a('生成密碼', '#collapseNew', [
        'class' => 'btn btn-primary me-2',
        'data-bs-toggle' => 'collapse',
        'aria-expanded' => 'false',
        'aria-controls' => 'collapseNew'
    ]).$unlockBtn.$toolbar.
    ($canDeletePassword ?
    Html::a('刪除密碼 <b class="caret"></b>', '#', [
        'class' => 'btn btn-danger dropdown-toggle me-2',
        'data-bs-toggle' => 'dropdown'
    ]).
    Dropdown::widget([
        'items' => $model->deleteDropDownItems($voteInfo->voteID, $parties, $marks, $confirm),
    ]) : '');
} else {
    $toolbar = $unlockBtn.$toolbar;
}

echo Html::tag('p', Html::tag('div', $toolbar, ['class' => 'dropdown']));

echo $this->render('_unlock_plaintext', [
    'voteID' => $voteInfo->voteID,
    'returnUrl' => $currentReturnUrl,
    'unlockAction' => 'passwd/unlock-plaintext',
    'lockAction' => 'passwd/lock-plaintext',
    'plaintextUnlocked' => $plaintextUnlocked,
    'plaintextUnlockRemaining' => $plaintextUnlockRemaining,
    'reauthRequired' => $reauthRequired,
    'useTotp' => $useTotp,
    'buttonInToolbar' => true,
]);

if (!$voteInfo->isBindVote) {
    echo $this->render('_new', ['voteID' => $voteInfo->voteID, 'parties' => $parties]);
}
echo $this->render('_status', [
    'voteID' => $voteInfo->voteID,
    'parties' => $parties,
    'marks' => $marks,
    'isBindVote' => (bool) $voteInfo->isBindVote,
]);
echo $this->render('_export', [
    'model' => $model,
    'voteID' => $voteInfo->voteID,
    'parties' => $parties,
    'marks' => $marks,
    'reauthRequired' => $reauthRequired,
    'useTotp' => $useTotp,
]);
echo $this->render('_print', [
    'model' => $exportPasswd,
    'parties' => $parties,
    'voteInfo' => $voteInfo,
    'marks' => $marks,
    'reauthRequired' => $reauthRequired,
    'useTotp' => $useTotp,
]);

$resetGrid = Html::a('<i class="fas fa-redo"></i>', Url::to(['passwd/index', 'voteID' => $voteInfo->voteID]), [
    'class' => 'btn btn-sm btn-secondary btn-default ms-2', 
    'title' => Yii::t('app', 'Reset Grid')
]);
Yii::$app->tablePag->pageTitleFormat .= $resetGrid;

// 表格
Pjax::begin(['id' => 'myGrid']);
echo GridView::widget([
    'id' => 'myGrid',
    'dataProvider' => $dataProvider,
    'filterModel' => $model,
    'summary' => Yii::$app->tablePag->getSummaryText('myGrid'),
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'columns' => [
        [
            'label' => '編號',
            'attribute' => 'sn',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 5rem'],
        ],
        [
            'label' => '組別',
            'attribute' => 'party',
            'value' => function ($model, $key, $index, $column) use ($parties)
            {
                if(!isset($parties[$model->party]))
                    return '(異常)';
                return $parties[$model->party];
            },
            'filter' => ArrayHelper::addSpace($parties),
            'filterInputOptions' => ['class' => 'form-select'],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 7rem'],
        ],
        [
            'attribute' => 'passwd',
            'encodeLabel' => false,
            'label' => Html::tag('span', '密碼', [
                'data-bs-toggle' => 'tooltip',
                'data-bs-placement' => 'top',
                'title' => $plaintextUnlocked ? '' : '遮罩中；可「顯示明文」解鎖，或使用匯出/密碼函',
                'tabindex' => '0',
            ]),
            'value' => static function ($model) use ($plaintextUnlocked) {
                return Html::encode(PasswordDisplay::formatGridValue($model->attributes, $plaintextUnlocked));
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '雙軌投票',
            'attribute' => 'dtrack',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column) use ($voteInfo)
            {
                $url = Url::to(['passwd/toggle', 'voteID' => $voteInfo->voteID]);
                return Html::a(Yii::$app->params['ct.passwd.dtrackAry'][$model->dtrack], '', [
                    'data-id' => $model->id,
                    'onclick' => "postToggle('$url', 'dtrack', this);"
                ]);
            },
            'filter' => Yii::$app->params['ct.passwd.dtrackAry'],
            'filterInputOptions' => ['class' => 'form-select'],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '狀態',
            'attribute' => 'status',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column) use ($voteInfo)
            {
                $url = Url::to(['passwd/toggle', 'voteID' => $voteInfo->voteID]);
                return Html::a(Yii::$app->params['ct.passwd.validAry'][$model->status], '', [
                    'data-id' => $model->id,
                    'onclick' => "postToggle('$url', 'status', this);"
                ]);
            },
            'filter' => Yii::$app->params['ct.passwd.validAry'],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
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
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '標記',
            'attribute' => 'mark',
            'value' => function ($model, $key, $index, $column)
            {
                return $model->{$column->attribute};
            },
            'filter' => $marks,
            'filterInputOptions' => ['class' => 'form-select'],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '操作',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column)
            {
                return Html::a('刪除', '', [
                    'data-id' => $model->id,
                    'onclick' => 'deletePasswd(this)',
                    'class' => 'btn btn-danger btn-sm'
                ]);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 6rem'],
            'visible' => $canDeletePassword
        ],
    ],
]);
Pjax::end();

$this->registerJs(<<<'JS'
document.querySelectorAll('#myGrid [data-bs-toggle="tooltip"]').forEach(function (el) {
    bootstrap.Tooltip.getOrCreateInstance(el);
});
JS
, \yii\web\View::POS_READY);

// 以下是 JS
$deleteUrl = Url::to(['/passwd/delete', 'voteID' => $voteInfo->voteID]);
$this->registerJs(<<<JS
function postToggle(url, action, elmnt)
{
    var originalColor = $(elmnt).css("color"); // 儲存原始顏色

    $(elmnt).css("color", "green"); // 改變元素顏色
    $(elmnt).append('<i class="fa fa-spinner fa-spin ms-1"></i>'); // 添加旋轉的圖標

    $.ajax({
        type: "POST",
        url: url,
        data: {
            action: action,
            id: elmnt.dataset.id
        }, 
        success: function(result) {
            $(elmnt).css("color", originalColor); // 恢復元素顏色
            $(elmnt).find('.fa-spinner').remove(); // 移除旋轉的圖標
            elmnt.outerHTML = result;
        }, 
        error: function(result) {
            $(elmnt).css("color", originalColor); // 恢復元素顏色
            $(elmnt).find('.fa-spinner').remove(); // 移除旋轉的圖標
            window.location.reload();
        }
    });
    return false;
}

function deletePasswd(elmnt)
{
    appDialog.confirm('確定刪除此密碼?', function(ok) {
        if (!ok) {
            return;
        }
        $.ajax({
            type: "POST",
            url: "{$deleteUrl}",
            data: {id: elmnt.dataset.id},
            success: function(result) {
                if(result == 'success')
                {
                    $(elmnt.parentElement.parentElement).addClass('d-none');
                    appDialog.info('已刪除');
                }
                else
                {
                    appDialog.error('異常');
                }
            },
            error: function(result) {
                window.location.reload();
            }
        });
    });
    return false;
}
JS
, $this::POS_BEGIN);
