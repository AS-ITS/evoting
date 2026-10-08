<?php

use app\models\Votes;
use yii\widgets\Pjax;
use app\models\Config;
use yii\grid\GridView;
use app\models\Parties;
use yii\bootstrap5\Html;
use app\components\Model;
use app\models\CandiData;
use app\models\FormVotes;
use app\models\FormManageCount;
use yii\bootstrap5\Dropdown;
use app\models\FormManageVote;
use app\components\helper\ArrayHelper;
use rmrevin\yii\fontawesome\FAS;

/**
 * 要修改請注意: 前臺投票者、後臺投票管理和後臺開票作業共用一個計票單的View
 */

$parties = $model->PartyAry;
$modelParties = Parties::find()->where(['voteID' => $model->voteID])->indexBy('party')->all();
$voteQuestions = $model->voteQuestions;
$sort = is_null(Yii::$app->request->get('sort')) ? FormManageCount::COUNT_BY_BALLOTS : Yii::$app->request->get('sort');

echo \app\widgets\Alert::widget();
if (empty($this->params['showVoteInfo'])) {
    echo Html::tag('div', Html::tag('h2', $title).Html::tag('hr'), ['class' => 'text-center hide-print']);;
}
// 組別得票匯出
if ($showExportModal) {
    // echo $this->render('_export_modal', compact('ballotCountSort', 'model', 'parties', 'voteQuestions'));
}
if (Yii::$app->controller->id == 'vote') {
    // echo Html::a(FAS::icon('chart-bar', ['class' => 'mr-2']).Yii::t('app', '計票單匯出(csv)'), ['vote/count-export', 'voteID' => $model->voteID], ['class' => 'btn btn-info hide-print']);
}
else {
    // export items
    foreach (Yii::$app->params['ct.result.sortAry'] as $exportSort => $sortText) {
        $exportItems[] = [
            'label' => $sortText,
            'url' => ['count/export', 'voteID' => $model->voteID, 'sort' => $exportSort],
        ];
    }
    echo Html::tag('div',
        Html::a(FAS::icon('chart-bar', ['class' => 'me-2']).Yii::t('app', '計票單匯出(csv)'), '#', ['class' => 'btn btn-info dropdown-toggle me-2', 'data-bs-toggle' => 'dropdown']).
        Dropdown::widget(['items' => $exportItems])
    , ['class' => 'btn-group hide-print']);
    echo Html::button('列印模式', ['class' => 'btn render-type float-end', 'onclick' => 'renderType("print")']);
    echo Html::dropDownList(
        'sort', 
        $sort,
        (Yii::$app->language == 'en-US' ? Yii::$app->params['ct.result.sortAryE'] : Yii::$app->params['ct.result.sortAry']), 
        ['class' => 'form-select col-md-2 float-end hide-print', 'id' => 'count-sort', 'style' => 'width: auto']
    );
}
/**
 * 導航列
 */
$partyAry = array_keys($ballotCountSort);// 將所有分組變成一個 array
$systemConfig = Yii::$app->session->get('System.config');
$sortTextAry = ($systemConfig['homeLayout'] ?? null) == Config::LAYOUT_MEETING ? Yii::$app->params['ct.result.sortPrintText'] : Yii::$app->params['ct.result.sortAry'];

echo Html::tag('h3', 
    $model->voteInfo->getVoteName(false, '計票單').'<br>'.$sortTextAry[$sort]
    , ['class' => 'text-center mt-2 mb-0 fw-bold vote-title']
);
echo Html::tag('span', 
    $model->voteInfo->getRocDate($model->voteInfo->openEnd)
    , ['class' => 'text-center fw-bold float-end']
);
echo Html::tag('br');

echo Html::beginTag('ul', [
    'class' => 'nav nav-pills justify-content-center mt-2 h5',
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

/**
 * 內容頁
 */
Pjax::begin(['id' => 'pills-tabContent']);
echo Html::beginTag('div', ['class' => 'tab-content', 'id' => 'pills-tabContent']); // tab-content
$ballotListGroupByParty = ArrayHelper::map($ballotList ?: [], 'ballotID', '', 'party');
foreach($ballotCountSort as $party => $questions)
{
    $countColumnNum = $systemConfig['countColumnNum'] ?? 1;
    $dataProviders = $model->getArrayProvider(
        $questions,
        $countColumnNum,
        $sort
    );
    ksort($dataProviders);
    $isActive = ($active !== $party) ? '' : ' show active';
    $id = 'pills-'.$party;
    echo Html::beginTag('div', [ // tab-pane fade
        'class' => "tab-pane fade$isActive",
        'id'    => $id,
        'role'  => 'tabpanel',
        'aria-labelledby' => "$id-tab",
    ]);

    foreach ($dataProviders as $questionID => $data) {
        if ($model->voteInfo->candiConfig == Votes::CANDI_CONFIG_BY_Q) {
            $candiConfig = (new FormManageVote($model->voteID))->getCandidateConfig($model->voteID, $questionID);
        }
        else {
            $candiConfig = (new FormManageVote($model->voteID))->getCandidateConfig($model->voteID);
        }
        $validCount = [];
        $validCount[$questionID] = ['valid' => 0, 'invalid' => 0];
        // 計算有效票及廢票
        // 全部分組共同問題
        $getPasswordAndValidCount = $model->getPasswordAndValidCount($party, $questionID, $validCount, $ballotList, $ballotCountAry, $modelParties, $passwordList, $voteQuestions);
        $passwordCount = $getPasswordAndValidCount['password'];

        // 組別顯示
        echo Html::tag('h4', str_replace('<br>', '', Html::decode(Model::i18n($voteQuestions[$questionID]->titleE, $voteQuestions[$questionID]->title))),[
            'class'=>'text-primary fw-bold mt-2'
        ]);

        // 有效票、無效票、選票數量
        echo Html::beginTag('div', ['class'=>'row mt-2']); // row 1
        if ($model->voteInfo->type == FormVotes::TYPE_ANON) {
            echo join( '', [
                Html::beginTag('div', ['class'=>'col-12']),// row 2
                    Html::tag('h5',
                        Html::tag('span', Yii::t('app', '選票數量：{password}', ['password' => $passwordCount], Yii::$app->language), ['class' => 'text-dark']).'（'.
                        Html::tag('span', Yii::t('app', '有效票：{valid} 張', ['valid' => isset($validCount[$questionID]['valid']) ? $validCount[$questionID]['valid'] : 0], Yii::$app->language), ['class' => 'text-success']).'，'.
                        Html::tag('span', Yii::t('app', '廢票：{waste} 張', ['waste' => isset($validCount[$questionID]['invalid']) ? $validCount[$questionID]['invalid'] : 0], Yii::$app->language), ['class' => 'text-danger']).'，'.
                        Html::tag('span', Yii::t('app', '尚未投票：{notvote}', ['notvote' => $passwordCount - $validCount[$questionID]['valid'] - $validCount[$questionID]['invalid']], Yii::$app->language), ['class' => 'text-dark']).'）'
                    , ['class' => 'fw-bold']),
                Html::endTag('div'),// row 2
            ]);
        }

        echo Html::endTag('div'); // row 1

        echo Html::beginTag('div', ['class' => 'row']);// row 3
        // 分欄顯示
        $columnNum = 12 / ($systemConfig['countColumnNum'] ?? 1);
        foreach($data as $i => $dataProvider)
        {
            // 顯示表格
            echo Html::beginTag('div', ['class' => "col-md-{$columnNum}", 'style' => 'padding-right: 5px;padding-left: 5px;']);// col-lg
            echo GridView::widget([
                'id' => 'myGrid-'.$i,
                'dataProvider' => $dataProvider,
                'summary' => '',
                'options' => ['class' => 'table-responsive'],
                'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
                'columns' => [
                    'Name' => [
                        'label' => Model::i18n($candiConfig->NameE, $candiConfig->Name),
                        'value' => function ($model, $key, $index, $column)
                        {
                            return Model::i18n($model['NameE'], $model['Name']);
                        },
                        'headerOptions'  => ['class' => 'align-middle text-center col-5'],
                        'contentOptions' => ['class' => 'align-middle text-center'],
                    ],
                    'Votes' => [
                        'label' => Yii::t('app', '得票數'),
                        'value' => function ($model, $key, $index, $column)
                        {
                            // 已達門檻
                            if ($model['isReachThreshold'] == CandiData::REACH_THRESHOLD) {
                                return '-';
                            }
                            return $model['count'];
                        },
                        'headerOptions'  => ['class' => 'align-middle text-center col-3'],
                        'contentOptions' => ['class' => 'align-middle text-center'],
                    ],
                    'VoteSort' => [
                        'label' => Yii::t('app', '排序'),
                        'value' => function ($model, $key, $index, $column)
                        {
                            // 已達門檻
                            if ($model['isReachThreshold'] == CandiData::REACH_THRESHOLD) {
                                return '-';
                            }
                            return $model['rank'];
                        },
                        'headerOptions'  => ['class' => 'align-middle text-center col-4'],
                        'contentOptions' => ['class' => 'align-middle text-center'],
                    ],
                ],
            ]);
            echo Html::endTag('div');// col-lg
        }
        echo Html::endTag('div');// row 3
    }
    echo Html::endTag('div');// tab-pane fade
}
echo Html::endTag('div'); // tab-content

Pjax::end();

/**
 * 簽名區域
 */
echo $this->render('@app/views/partials/print-mode');

/**
 * 特殊處理!!
 * 這是 bootstrap 提供的一個 Components
 * 經測試，使用官方範例程式正常
 * 但自己組標籤部分功能卻是異常
 * 這是使用 bootstrap 的 API 輔助修正問題
 * 當觸發按鈕時會呼叫以下事件
 * 
 * ----------
 * 
 * 正常來說當圈選按鈕會觸發以下事件
 * e.target 應為當前按下的按鈕(正常)
 * e.relatedTarget 應為圈選前的按鈕(異常)
 * 
 * @link 套件 https://getbootstrap.com/docs/4.5/components/navs/#javascript-behavior
 * @link API https://getbootstrap.com/docs/4.5/components/navs/#events
 */
$this->registerJs(<<<JS
    $('a[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        // 獲取整個導航欄
        $('a[data-bs-toggle="pill"]').each(function( index ) {
            // 如果不是當前圈選的按鈕
            if(this != e.target)
            {
                // 消除 class 中的 active
                $(this).removeClass('active');

                // 修改 tag 中的 aria-selected 為 false
                $(this).attr('aria-selected','false');
            }
        });
    });

    // 切換排序
    $('#count-sort').change(function(e) {
        window.location.href = '$sortUrl&sort='+e.target.value
    });

    // 列印模式刪除.table class
    if (window.matchMedia) {
        window.matchMedia('print').addListener(function(mql) {
            if (mql.matches) {
                $('table').removeClass('table').css('width', '100%').css('margin-bottom', '1rem');
            }
        });
    }
JS
, $this::POS_END);

// CSS
$this->registerCss(<<<CSS
    .table-striped tbody tr:nth-of-type(odd) { /* 替換表格基數欄背景色 */
        /* color: #0c5460; */
        /* background-color: #bee5eb; */
        color: #000;
        background-color: #fff;
    }
    .table-bordered, .table-bordered th, .table-bordered td {
        /* border: 1px solid #0c5460; */
        border: 1px solid #000;
    }
    .table thead th {
        /* border-bottom: 1.5px solid #0c5460; */
        border-bottom: 1.5px solid #000;
    }
CSS
);