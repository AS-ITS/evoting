<?php
use yii\helpers\Html;
use kartik\grid\GridView;
use app\widgets\CardStyle;

/** @var yii\web\View $this */
/** @var yii\db\ActiveRecord $model */
/** @var yii\bootstrap5\ActiveForm $form */

$this->title = $title;
$this->params['navbar_left_items'] = [
    [
        'label' => '刪除查詢結果',
        'url' => $linkDeleteSearch,
        'linkOptions' => [
            'class' => 'text-danger',
            'data' => [
                'bs-confirm' => '請再次確認要刪除該查詢結果？',
                'method' => 'post',
            ],
        ],
    ],
    [
        'label' => '再查詢',
        'url' => $linkNewSearch,
        'linkOptions' => [
            'class' => 'text-success',
        ],
    ],
];

if ($addFirstColumn)
{
    array_unshift($columns, [
        'header' => '#',
        'value' => function (\app\models\UniversalActiveRecord $model, $key, $index, $column) use ($linkUpdate) {
            $pagination = $column->grid->dataProvider->getPagination();
            $link = array_merge($linkUpdate, ['id'=>join(',',$model->getPrimaryKey(true))]);
            $i = $index + 1;
            if ($pagination !== false) {
                $i += $pagination->getOffset();
            }
            return Html::a(Html::encode($i), $link, ['data-pjax'=>'0']);
        },
        'format' => 'raw',
        'headerOptions' => ['class' => 'align-middle text-center'],
        'contentOptions'=> ['class' => 'align-middle text-center'],
    ]);
}

// 顯示錯誤訊息
echo \app\widgets\Alert::widget();

if ($showSql)
{
    \app\widgets\HighlightJs::widget();
    $card = CardStyle::begin([
        'id' => 'showSql',
        'title' => '查詢的語法',
        'theme' => CardStyle::THEME_OUTLINE,
        'bodyOptions' => [
            'class'=>['card-body'],
            'style'=>['padding-top'=>'0.5rem','padding-bottom'=>'0.5rem'],
        ],
    ]);
    echo Html::tag('pre', Html::tag('code', $showSql, ['class'=>'language-sql']), ['class'=>'p-0 mb-0']);
    $card->end();
}
?>
<div class="data-table-modifier-result">
    <?= GridView::widget([
        'id' => 'myGrid',
        'tableOptions' => ['class' => 'table table-striped table-bordered table-responsive-md'],
        'dataProvider' => $dataProvider,
        'summary' => Yii::$app->tablePag->getSummaryText('myGrid'),
        'floatHeader' => true, // table header floats when you scroll
        'responsive' => false, // whether the grid will have a `responsive` style.
        // 'headerContainer' => ['style' => 'top:50px', 'class' => 'kv-table-header'], // offset from top
        'condensed' => true, // 這個選項可以使表格更緊湊
        'pjax' => true,
        'pjaxSettings'=>[
            'options' => [
                'id' => 'myGrid',
            ],
        ],
        'columns' => $columns,
    ]); ?>
</div>