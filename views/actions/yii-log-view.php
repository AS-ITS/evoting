<?php
use yii\helpers\Html;
use app\components\helper\ArrayHelper;
use app\components\helper\StringHelper;
use kartik\grid\GridView;

/** @var \yii\web\View $this View 自己的 params */
/** @var string $title 標題 */
/** @var string $contactAjaxAction 浮動視窗的網址 */
/** @var string $modalId 浮動視窗的 id */
/** @var \yii\data\ArrayDataProvider $dataProvider 表格 */
/** @var array $searchModel 表格搜尋 */

$this->title = $title;
$this->params['breadcrumbs'][] = $title;

if(!is_null($navItems))
    $this->params['navItems'] = $navItems;

// 瀏覽日誌的浮動視窗
echo \app\widgets\UrlModal::widget([
    'modalId' => $modalId,
    'contactAjaxAction' => $contactAjaxAction,
    'footer'=> '',
    'title' => '日誌內容',
    'options' => ['style'=>['z-index' => 1200]], // 設置定位元素及其後代項目的順序
    'clientOptions' => ['backdrop'=>false],
]);

$contentOptions = function($model, $key, $index, $column) {
    $options = ['class' => 'align-middle text-center'];
    if($model['isRead'] == false)
        Html::addCssClass($options, 'table-warning');
    if(in_array($column->attribute,['baseName','size','accessTime','modifyTime']))
        Html::addCssClass($options, 'font-monospace');
    return $options;
};

echo GridView::widget([
    'id' => 'myGrid',
    'tableOptions' => ['class'=>'table table-striped table-bordered table-responsive-sm table-sm'],
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'summary' => Yii::$app->tablePag->getSummaryText('myGrid'),
    'floatHeader' => true, // table header floats when you scroll
    'responsive' => false, // whether the grid will have a `responsive` style.
    // 'headerContainer' => ['style' => 'top:50px', 'class' => 'kv-table-header'], // offset from top
    'pjax' => true,
    'pjaxSettings'=>[
        'options' => [
            'id' => 'myGrid',
        ],
    ],
    'columns' => [
        [
            'label' => '檔名',
            'attribute' => 'baseName',
            'value' => function ($model, $key, $index, $column) use ($modalId, $nonHtmlEncodeFilesName) {
                $value = ArrayHelper::getValue($model, $column->attribute);
                $inode = ArrayHelper::getValue($model, 'inode');

                // 是否編碼
                $encode = true;
                foreach($nonHtmlEncodeFilesName as $nHEFN)
                {
                    if(StringHelper::matchWithAsterisk($nHEFN, $value))
                    {
                        $encode = false;
                        break;
                    }
                }
                return Html::a(
                    Html::encode($value), '#', [
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#'.$modalId,
                        'data-id' => $inode,
                        'data-encode' => $encode?'T':'F',
                    ]
                );
            },
            'format' => 'raw',
            'headerOptions' => ['class' => 'align-middle text-center'],
            'contentOptions'=> $contentOptions,
        ],
        [
            'label' => '檔案大小',
            'attribute' => 'size',
            'value' => function ($model, $key, $index, $column) {
                $value = ArrayHelper::getValue($model, $column->attribute);
                if(is_null($value))
                    return '-';
                return StringHelper::getVerboseSize($value);
            },
            'headerOptions' => ['class' => 'align-middle text-center'],
            'contentOptions'=> $contentOptions,
        ],
        [
            'label' => '訪問時間',
            'attribute' => 'accessTime',
            'value' => function ($model, $key, $index, $column) {
                $value = ArrayHelper::getValue($model, $column->attribute);
                if(is_null($value))
                    return '-';
                return $value;
            },
            'headerOptions' => ['class' => 'align-middle text-center'],
            'contentOptions'=> $contentOptions,
        ],
        [
            'label' => '修改時間',
            'attribute' => 'modifyTime',
            'value' => function ($model, $key, $index, $column) use ($statusAry) {
                $value = ArrayHelper::getValue($model, $column->attribute);
                if(is_null($value))
                    return '-';
                return $value;
            },
            'headerOptions' => ['class' => 'align-middle text-center'],
            'contentOptions'=> $contentOptions,
        ],
    ],
]);
