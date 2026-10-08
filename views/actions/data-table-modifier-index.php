<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\helper\StringHelper;
use app\widgets\BootstrapSelect;
use kartik\grid\GridView;

/** @var yii\web\View $this */
/** @var \app\models\UniversalActiveRecord $model */
/** @var yii\bootstrap5\ActiveForm $form */

$this->title = "資料表條列";
$this->params['navbar_left_items'] = [
    [
        'label' => '執行 SQL 查詢',
        'url' => $linkSQL,
    ],
];
$getDbNames = $model->getDbNames();

$resetGrid = Html::a('<i class="fas fa-redo"></i>', Url::current(), [
    'class' => 'btn btn-secondary btn-default ms-2',
    'title' => '刷新表格'
]);
Yii::$app->tablePag->pageTitleFormat  = '第 {page} 頁，共 {pageCount} 頁 ({totalCount} 筆)';
Yii::$app->tablePag->pageTitleFormat .= '&nbsp;&nbsp;'.$resetGrid;

echo \app\widgets\Alert::widget();
?>
<div class="data-table-modifier-result">
    <?php if(count($getDbNames) > 1) { ?>
    <p><code>可切換的資料庫</code>：<?=BootstrapSelect::widget([
        'value' => $model::$dbName,
        'items' => $getDbNames,
        'onchange' => ['db'],
        'options' => [
            'search' => 1,
        ]
    ]);?></p>
    <?php } ?>
    <p><code>資料筆數</code>: 某些存儲引擎（如 MyISAM）存儲確切計數。
        對於其他存儲引擎（如 InnoDB），此值是近似值，可能與實際值相差 40% 至 50%。在這種情況下，
        使用 SELECT COUNT(*) 獲取準確計數。
        TABLE_ROWS 對於 INFORMATION_SCHEMA 表為 NULL。
        對於 InnoDB 表，行計數僅是用於 SQL 優化的粗略估計。
        （如果 InnoDB 表被分區，也是如此。）</p>
    <?= GridView::widget([
        'id' => 'myGrid',
        'tableOptions' => ['class' => 'table table-striped table-bordered table-responsive-md'],
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'summary' => Yii::$app->tablePag->getSummaryText('myGrid'),
        'floatHeader' => true, // table header floats when you scroll
        'responsive' => false, // whether the grid will have a `responsive` style.
        // 'headerContainer' => ['style' => 'top:50px', 'class' => 'kv-table-header'], // offset from top
        'condensed' => true, // 這個選項可以使表格更緊湊
        'pjax' => true,
        'pjaxSettings' => [
            'options' => [
                'id' => 'myGrid',
            ],
        ],
        'rowOptions' => function ($model, $key, $index, $grid) {
            return [
                'class' => 'table-highlight', // 此處添加用於高亮的 CSS 類
            ];
        },
        'columns' => [
            [
                'header' => '#',
                'mergeHeader' => true,
                'value' => function ($model, $key, $index, $column) use ($linkUpdate) {
                    $pagination = $column->grid->dataProvider->getPagination();
                    $i = $index + 1;
                    if ($pagination !== false) {
                        $i += $pagination->getOffset();
                    }
                    return $i;
                },
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center'],
            ],
            [
                'attribute' => 'TABLE_NAME',
                'value' => function ($model, $key, $index, $column) use ($linkSearch) {
                    if (is_null($model[$column->attribute]))
                    {
                        return '-';
                    }
                    return Html::a(
                        Html::encode($model[$column->attribute]),
                        array_merge($linkSearch,[
                            'tableName' => $model[$column->attribute],
                        ]),
                        [
                            'data-pjax' => '0'
                        ]
                    );
                },
                'format' => 'raw',
                'enableSorting' => true,
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-start font-monospace'],
            ],
            [
                'attribute' => 'ENGINE',
                'value' => function ($model, $key, $index, $column) {
                    return $model[$column->attribute];
                },
                'enableSorting' => true,
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center font-monospace'],
            ],
            [
                'attribute' => 'TABLE_ROWS',
                'value' => function ($model, $key, $index, $column) {
                    return $model[$column->attribute];
                },
                'enableSorting' => true,
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center font-monospace'],
            ],
            [
                'attribute' => 'DATA_LENGTH',
                'value' => function ($model, $key, $index, $column) {
                    $dataLength = StringHelper::getVerboseSize($model[$column->attribute]);
                    // $avgRowLength = StringHelper::getVerboseSize($model['AVG_ROW_LENGTH']);
                    return nl2br(Html::encode("{$dataLength}"));
                },
                'format' => 'raw',
                'enableSorting' => true,
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center font-monospace'],
            ],
            [
                'attribute' => 'DATA_FREE',
                'value' => function ($model, $key, $index, $column) {
                    return StringHelper::getVerboseSize($model[$column->attribute]);
                },
                'enableSorting' => true,
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center font-monospace'],
            ],
            [
                'attribute' => 'AUTO_INCREMENT',
                'value' => function ($model, $key, $index, $column) {
                    if (is_null($model[$column->attribute]))
                    {
                        return '-';
                    }
                    return $model[$column->attribute];
                },
                'enableSorting' => true,
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center font-monospace'],
            ],
            [
                'attribute' => 'UPDATE_TIME',
                'value' => function ($model, $key, $index, $column) {
                    if (is_null($model[$column->attribute]))
                    {
                        return '-';
                    }
                    return $model[$column->attribute];
                },
                'enableSorting' => true,
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center font-monospace'],
            ],
            [
                'attribute' => 'TABLE_COMMENT',
                'value' => function ($model, $key, $index, $column) {
                    return $model[$column->attribute];
                },
                'enableSorting' => true,
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-start font-monospace'],
            ],
        ],
    ]); ?>
</div>