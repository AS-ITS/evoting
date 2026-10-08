<?php

use yii\i18n\Formatter;
use yii\bootstrap5\Html;
use kartik\grid\GridView;

$questionTitle = $questions[Yii::$app->requestedParams['questionID']]['title'];
$this->title = '投票結果封存單_'.$questionTitle;

// 投票名稱
echo Html::tag('div', 
    Html::tag('div', $voteInfo->getVoteName(false).'<br>投票結果封存單<br>', ['id' => 'voteTitle']).
    Html::tag('div', $voteInfo->getRocDate($voteInfo->openEnd), ['class' => 'text-end', 'id' => 'voteDate'])
, ['class' => 'text-center border border-dark py-1', 'id' => 'title']);

// 問題名稱
echo Html::tag('div', 
    Html::tag('div', $questionTitle, ['id' => 'questionTitle'])
, ['class' => 'text-center my-2']);

// 選票內容
echo GridView::widget([
    'id' => 'ballot-content',
    'tableOptions' => ['class' => 'table table-striped table-bordered table-responsive-md table-sm'],
    'headerRowOptions' => ['class' => 'border-bottom-0'],
    'dataProvider' => $dataProvider,
    'formatter' => ['class' => Formatter::className(), 'nullDisplay' => ''],
    'summary' => '',
    'rowOptions' => function ($model, $key, $index, $grid)
    {
        if ($key > 0 && $key % 15 == 0) {
            return [
                'style' => "break-before: page;"
            ];
        }
    },
    'columns' => [
        [
            'header' => '序號',
            'class' => \yii\grid\SerialColumn::className(),
            'headerOptions' => ['class' => 'align-middle text-center text-nowrap'],
            'contentOptions'=> ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '投票者',
            'attribute' => 'creator',
            'value' => function ($model, $key, $index, $column) use ($sysidList) {
                return $sysidList[$model[$column->attribute]];
            },
            'headerOptions' => ['class' => 'align-middle text-center text-nowrap'],
            'contentOptions'=> ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '投票內容',
            'value' => function ($model, $key, $index, $column) use ($ballots) {
                // 未圈選
                if(empty($ballots[$model['ballotID']])) {
                    return '';
                }
                // 圈選的候選人清單
                foreach ($ballots[$model['ballotID']] as $candi) {
                    $selCandiName[] = '<span class="badge fw-lighter" style="font-size: 1rem">'.$candi['Name'].'</span>';
                }
                return implode("、", $selCandiName);
            },
            'format' => 'raw',
            'headerOptions' => ['class' => 'align-middle text-center'],
            'contentOptions'=> ['class' => 'align-middle text-start'],
        ],
        [
            'label' => '備註',
            'value' => '',
            'headerOptions' => ['class' => 'align-middle text-center text-nowrap'],
            'contentOptions'=> ['class' => 'align-middle text-start'],
        ],
    ],
]);

/**
 * 簽名區域
 */
echo $this->render('@app/views/partials/print-mode');

$this->registerJs(<<<JS
    // 簽名區域
    renderType('print'); 
    // 列印模式刪除.table class
    removeBootstrapPrint();
JS
, $this::POS_END);

$this->registerCss(<<<CSS
    .table > thead > tr > th {
        border-top: 1px solid !important;
    }
CSS
    );
