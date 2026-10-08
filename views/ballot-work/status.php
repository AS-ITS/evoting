<?php

use yii\helpers\Url;
use yii\helpers\Html;

use app\models\Parties;
use app\components\Model;
use yii\bootstrap5\Modal;
use app\components\helper\ArrayHelper;
use rmrevin\yii\fontawesome\FAS;

$title = Yii::t('app', '檢票');
$this->title = $title;
$this->params['secNavType'] = 'ballotWork'; // 啟用共用之 vote 二級導航

$parties = $model->PartyAry;
$modelParties = Parties::find()->where(['voteID' => $model->voteID])->indexBy('party')->all();
$voteQuestions = $model->voteQuestions;

echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
echo Html::tag('hr');
echo Html::a(
    FAS::icon('chart-bar', ['class' => 'me-2']).Yii::t('app', '選票統計匯出(csv)'),
    '#collapseBallotExportStatus',
    [
        'class' => 'btn btn-info hide-print mb-2',
        'data-bs-toggle' => 'collapse',
        'aria-expanded' => 'false',
        'aria-controls' => 'collapseBallotExportStatus',
    ]
);
echo $this->render('@app/views/ballot/_export', [
    'voteID' => $model->voteInfo->voteID,
    'reauthRequired' => $reauthRequired ?? false,
    'useTotp' => $useTotp ?? false,
    'defaultType' => '1',
    'collapseId' => 'collapseBallotExportStatus',
    'returnUrl' => Yii::$app->request->url,
    'contentQuestions' => $contentQuestions ?? [],
]);
/**
 * 導航列
 */
$partyAry = array_keys($ballotCountSort);// 將所有分組變成一個 array
echo Html::tag('h3', $model->voteInfo->voteName, ['class' => 'text-center mt-2 fw-bold']);

echo Html::beginTag('ul', [
    'class' => 'nav nav-pills justify-content-center mt-4 h5',
    'id'    => 'pills-tab',
    'role'  => 'tablist'
]);

foreach($partyAry as $i => $party)
{
    $isActive = ($i > 0) ? '' : ' active';
    $isVisible = count($partyAry) > 1 ? '' : ' d-none';
    if($i == 0) $active = $party;
    $id = 'pills-'.$party;
    echo join( '', [
        Html::beginTag('div', ['class' => 'nav-item', 'role' => 'presentation']),
            Html::a(Html::encode($parties[$party], true), "#$id", [
                'class' => "nav-link{$isActive}{$isVisible}",
                'id' => "$id-tab",
                'role' => "tab",
                'data-bs-toggle' => 'pill',
                'aria-controls' => $id,
                'aria-selected' => ($i > 0) ? 'false' : 'true',
            ]),
        Html::endTag('div'),
    ]);
}
echo Html::endTag('ul');

// 該組別問題投票內容
Modal::begin([
    'id' => 'ballots-detail',
    'title' => "",
    'dialogOptions' => ['class' => 'modal-dialog-centered'],
    'headerOptions' => ['class' => 'text-light'],
    'clientOptions' => ['backdrop' => false],
    'scrollable' => true,
    'size' => 'modal-xl',
    'footer' => Html::button(Yii::t('app', '關閉'), ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal'])
]);

echo Html::tag('div', '<i class="fas fa-2x fa-sync fa-spin"></i>', ['class' => 'overlay d-none text-center']);
echo Html::tag('div', '', ['id' => 'modal-ballots-detail']);

Modal::end();

/**
 * 內容頁
 */
echo Html::beginTag('div', ['class'=>'tab-content', 'id'=>'pills-tabContent']); // tab-content
$ballotListGroupByParty = ArrayHelper::map($ballotList ?: [], 'ballotID', '', 'party');
foreach($ballotCountSort as $party => $questions)
{
    $dataProviders = $model->getArrayProvider($questions, Yii::$app->session->get('System.config')['countColumnNum']);
    $isActive = ($active !== $party) ? '' : ' show active';
    $id = 'pills-'.$party;
    echo Html::beginTag('div', [ // tab-pane fade
        'class' => "tab-pane fade$isActive",
        'id'    => $id,
        'role'  => 'tabpanel',
        'aria-labelledby' => "$id-tab",
    ]);
        echo Html::beginTag('table', ['class' => 'table table-bordered table-sm text-center mt-3']); // table
            // 表格標題
            echo Html::beginTag('thead'); // thead
                echo Html::tag('tr',
                    Html::tag('th', Yii::t('app', '')).
                    Html::tag('th', Yii::t('app', '有效票'), ['class' => 'text-success']).
                    Html::tag('th', Yii::t('vote', '廢票'), ['class' => 'text-danger']).
                    Html::tag('th', Yii::t('app', '尚未投票'), ['class' => 'text-warning']).
                    Html::tag('th', Yii::t('app', '選票數量'))
                );
            echo Html::endTag('thead'); // thead
            // 表格內容
            echo Html::beginTag('tbody'); // tbody
                echo Html::beginTag('tr'); // tr
                foreach ($dataProviders as $questionID => $data) {

                    // 問題
                    echo Html::tag('td', Model::i18n($voteQuestions[$questionID]->titleE, $voteQuestions[$questionID]->title));

                    $validCount = [];
                    $validCount[$questionID] = ['valid' => 0, 'invalid' => 0];
                    // 計算有效票及廢票
                    // 全部分組共同問題
                    $getPasswordAndValidCount = $model->getPasswordAndValidCount($party, $questionID, $validCount, $ballotList, $ballotCountAry, $modelParties, $passwordList, $voteQuestions);
                    $passwordCount = $getPasswordAndValidCount['password'];

                    // 有效票
                    echo Html::tag(
                        'td', 
                        Html::a(
                            isset($validCount[$questionID]['valid']) ? $validCount[$questionID]['valid'] : 0,
                            'javascript:void(0)',
                            [
                                'class' => 'border-0 bg-transparent text-success',
                                'style' => 'text-decoration: underline; text-underline-offset: 4px;',
                                'data-bs-toggle' => 'modal',
                                'data-bs-target' => '#ballots-detail',
                                'data-party' => $parties[$party],
                                'data-question' => Model::i18n($voteQuestions[$questionID]->titleE, $voteQuestions[$questionID]->title),
                                'data-valid' => Yii::t('app', '有效票'),
                                'data-url' => Url::to([
                                    'ballot-work/ballots-detail', 
                                    'voteID' => $model->voteID, 
                                    'party' => $party, 
                                    'questionID' => $questionID, 
                                    'valid' => true
                                ]),
                            ]
                        )
                    );
                    // 廢票
                    echo Html::tag(
                        'td', 
                        Html::a(
                            isset($validCount[$questionID]['invalid']) ? $validCount[$questionID]['invalid'] : 0,
                            'javascript:void(0)',
                            [
                                'class' => 'border-0 bg-transparent text-danger',
                                'style' => 'text-decoration: underline; text-underline-offset: 4px;',
                                'data-bs-toggle' => 'modal',
                                'data-bs-target' => '#ballots-detail',
                                'data-party' => $parties[$party],
                                'data-question' => Model::i18n($voteQuestions[$questionID]->titleE, $voteQuestions[$questionID]->title),
                                'data-valid' => Yii::t('vote', '廢票'),
                                'data-url' => Url::to([
                                    'ballot-work/ballots-detail', 
                                    'voteID' => $model->voteID, 
                                    'party' => $party, 
                                    'questionID' => $questionID, 
                                    'valid' => false
                                ]),
                            ]
                        )
                    );
                    // 尚未投票數量 = 選票數量 - 有效票 - 廢票
                    if ($model->voteInfo->type == 0)    // 表決投票
                        $notVotedCount = 0;
                    else
                        $notVotedCount = $passwordCount - (isset($validCount[$questionID]['valid']) ? $validCount[$questionID]['valid'] : 0) - (isset($validCount[$questionID]['invalid']) ? $validCount[$questionID]['invalid'] : 0);
                    echo Html::tag('td', $notVotedCount, ['class' => 'text-warning']);

                    // 選票數量
                    echo Html::tag('td', $passwordCount);

                    echo Html::endTag('tr');// tr
                }
                echo Html::endTag('tr');// tr
            echo Html::endTag('tbody'); // tbody
        echo Html::endTag('table'); // table
    echo Html::endTag('div');// tab-pane fade
}
echo Html::endTag('div'); // tab-content

/**
 * 簽名區域
 */
echo $this->render('@app/views/partials/print-mode');

echo Html::tag('div', 
    Html::a(
        '→ '.Yii::t('app', '計票'), 
        ['ballot-work/count', 'voteID' => $model->voteInfo->voteID], 
        ['class' => 'btn btn-primary hide-print']
    )
, ['class' => 'text-end']);

$this->registerJs(<<<JS
    $('a[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        // 獲取整個導航欄
        $('a[data-bs-toggle="pill"]').each(function(index) {
            // 如果不是當前圈選的按鈕
            if(this != e.target)
            {
                // 消除 class 中的 active
                $(this).removeClass('active');

                // 修改 tag 中的 aria-selected 為 false
                $(this).attr('aria-selected', 'false');
            }

        });
    });

    /**
     * 顯示選資詳細資訊
     */
    $('#ballots-detail').on('show.bs.modal', function (event) {
        let target = $(event.relatedTarget);
        let party = target.data('party');
        let question = target.data('question');
        let valid = target.data('valid');
        let url = target.data('url');
        $.ajax({
            type: 'GET',
            url: url,
            beforeSend: function() {
                $('.overlay').removeClass('d-none');
                if (target.hasClass('text-success')) {
                    $('.modal-header').removeClass('bg-danger').addClass('bg-success');
                }
                else {
                    $('.modal-header').addClass('bg-danger').removeClass('bg-success');
                }
                $('#modal-ballots-detail').text('');
            },
            success: function(data) {
                $('#modal-ballots-detail').html(data);
            },
            error: function(xhr) {
                console.log(xhr);
            },
            complete: function() {
                $('.overlay').addClass('d-none');
                $('#ballots-detail').find('.modal-title').text(party+' - '+question+' - '+valid);
            },
        });
    })
JS
, $this::POS_END);

// CSS
// $this->registerCss(<<<CSS
//     .table-striped tbody tr:nth-of-type(odd) { /* 替換表格基數欄背景色 */
//         /* color: #0c5460; */
//         /* background-color: #bee5eb; */
//         color: #000;
//         background-color: #fff;
//     }
//     .table-bordered, .table-bordered th, .table-bordered td {
//         /* border: 1px solid #0c5460; */
//         border: 1px solid #000;
//     }
//     .table thead th {
//         /* border-bottom: 1.5px solid #0c5460; */
//         border-bottom: 1.5px solid #000;
//     }
// CSS
// );
