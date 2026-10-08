<?php

use app\components\helper\ArrayHelper;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use app\widgets\DynamicTabbed;
use yii\data\Sort;

/** @var \app\models\FormCsvfile $model */

$title = Yii::$app->params['ct.candi.genModeAry']['csvfile'].'候選人';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

$attr = [
    ['tag' => 'uploadFile','name' => '上傳CSV',],
    ['tag' => 'example','name' => '範例下載',],
    ['tag' => 'columnDesc','name' => '欄位說明',],
    ['tag' => 'questionID','name' => '問題代碼',],
];
$indexAttrTag = array_flip(ArrayHelper::getColumn($attr,'tag'));

// 上傳
$this->beginBlock('uploadFile');
$form = ActiveForm::begin(['enableClientValidation'=>false]);
?>
<div class="row align-items-center">
    <div class="col-md">
        <div class="card list-group-item-warning">
            <div class="card-body">
                <h5 class="card-title">注意事項</h5>
                <p class="card-text">檔案編碼必須為 <?=join('、',$model::$fileEncoding) ?></p>
                <p class="card-text">欄位名稱、投票組別(中文)、問題代碼、單位代碼及職稱代碼必須完全相同</p>
                <p class="card-text">排序欄位必須為數字</p>
                <p class="card-text">
                    * 詳細請參考
                    <a href="#" onclick="const tabElement = document.querySelector('body > div.os-padding > div > div > main > div > ul > li:nth-child(<?=$indexAttrTag['columnDesc'] + 1 ?>) > a'); if (tabElement) { const tab = bootstrap.Tab.getOrCreateInstance(tabElement); tab.show(); }">
                        <?=$attr[$indexAttrTag['columnDesc']]['name']; ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md ms-3">
        <?=$form->field($model, 'csvFile')->fileInput(['multiple' => true, 'accept' => '.csv']) ?>
    </div>
</div>
<div class="row justify-content-center mt-3">
    <?=Html::submitButton('開始匯入', ['class' => 'btn btn-primary me-2', 'style' => 'width: auto']).
       Html::a('返回列表', ['candi/data', 'voteID'=>$voteInfo->voteID], ['class' => 'btn btn-secondary me-3', 'style' => 'width: auto'])?>
</div>
<?php
ActiveForm::end();
$this->endBlock();

// 範例
$this->beginBlock('example');
echo Html::tag('div', '注意!! 檔案編碼必須為 '.join('、',$model::$fileEncoding).'，建議使用另存後再將檔案匯入。',['class'=>'alert alert-warning','role'=>'alert']);
?>
<div class="row justify-content-center mt-3">
    <?=Html::a('下載', ['candi/csv-example', 'voteID'=>$voteInfo->voteID], ['class' => 'btn btn-primary me-2', 'style' => 'width: auto']).
       Html::a('返回列表', ['candi/data', 'voteID'=>$voteInfo->voteID], ['class' => 'btn btn-secondary me-3', 'style' => 'width: auto'])?>
</div>
<?php
$this->endBlock();

// 欄位說明
$FieldCP = ArrayHelper::index($model::$FieldCP, 'column');
if($CandiConfig->Name == '1')
{
    unset($FieldCP['名稱']);
    unset($FieldCP['名稱英']);
}
else
{
    unset($FieldCP['名字']);
    unset($FieldCP['名字英']);
}
unset($FieldCP['工作地點']);
$this->beginBlock('columnDesc');
echo Html::tag('div', '注意!! 欄位名稱必須完全相同、排序欄位必須為數字！', ['class' => 'alert alert-warning', 'role' => 'alert']);
echo GridView::widget([
    'layout'=> '{items}',
    'dataProvider' => new ArrayDataProvider([
        'allModels' => array_values($FieldCP),
        'sort' => false,
        'pagination' => false,
    ]),
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'columns' => [
        [
            'label' => '欄位名稱',
            'attribute' => 'column',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column) {
                return Html::tag('smap', $model['column']);
            },
            'headerOptions' => ['class' => 'align-middle text-start'],
            'contentOptions'=> ['class' => 'align-middle text-start' ,'style' => 'width: 10rem;'],
        ],
        [
            'label' => '說明',
            'attribute' => 'desc',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column) {
                return $model['desc'];
            },
            'headerOptions' => ['class' => 'align-middle text-center'],
            'contentOptions'=> ['class' => 'align-middle text-center'],
        ],
        [
            'label' => '欄位數值限制',
            'attribute' => 'fieldLimit',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column) use ($indexAttrTag, $parties) {
                return Yii::$app->getI18n()->format(
                    $model['fieldLimit'],
                    [
                        'party' => Html::tag('kbd', join('</kbd>/<kbd>', array_values($parties))),
                        'partyNoN' => Html::tag('kbd', join('</kbd>/<kbd>', array_values(ArrayHelper::forget($parties, 'N')))),
                        'instCode' => '<a href="#" onclick="const tabElement = document.querySelector(\'body > div.os-padding > div > div > main > div > ul > li:nth-child('.(($indexAttrTag['instCode'] ?? 0) + 1).') > a\'); if (tabElement) { const tab = bootstrap.Tab.getOrCreateInstance(tabElement); tab.show(); }">單位代碼</a>',
                        'tCode' => '<a href="#" onclick="const tabElement = document.querySelector(\'body > div.os-padding > div > div > main > div > ul > li:nth-child('.(($indexAttrTag['tCode'] ?? 0) + 1).') > a\'); if (tabElement) { const tab = bootstrap.Tab.getOrCreateInstance(tabElement); tab.show(); }">職稱代碼</a>',
                        'questionID' => '<a href="#" onclick="const tabElement = document.querySelector(\'body > div.os-padding > div > div > main > div > ul > li:nth-child('.($indexAttrTag['questionID'] + 1).') > a\'); if (tabElement) { const tab = bootstrap.Tab.getOrCreateInstance(tabElement); tab.show(); }">問題代碼</a>',
                    ],
                    Yii::$app->language
                );
            },
            'headerOptions' => ['class' => 'align-middle text-center'],
            'contentOptions'=> ['class' => 'align-middle text-center'],
        ],
    ]
]);
$this->endBlock();

// 返回按鈕
$this->beginBlock('backButton');
?>
<div class="row justify-content-center mt-3">
    <?=Html::a($attr[$indexAttrTag['uploadFile']]['name'], '#', ['class' => 'btn btn-primary me-2', 'style' => 'width: auto', 'onclick' => "const tabElement = document.querySelector('body > div.os-padding > div > div > main > div > ul > li:nth-child(".($indexAttrTag['uploadFile'] + 1).") > a'); if (tabElement) { const tab = bootstrap.Tab.getOrCreateInstance(tabElement); tab.show(); }"]).
       Html::a($attr[$indexAttrTag['columnDesc']]['name'], '#', ['class' => 'btn btn-secondary me-2','style' => 'width: auto', 'onclick' => "const tabElement = document.querySelector('body > div.os-padding > div > div > main > div > ul > li:nth-child(".($indexAttrTag['columnDesc'] + 1).") > a'); if (tabElement) { const tab = bootstrap.Tab.getOrCreateInstance(tabElement); tab.show(); }"]).
       Html::a('返回列表', ['candi/data', 'voteID'=>$voteInfo->voteID], ['class' => 'btn btn-secondary', 'style' => 'width: auto'])?>
</div>
<?php
$this->endBlock();

// 問題代碼
$this->beginBlock('questionID');
echo Html::tag('div', '注意!! 問題代碼必須對應分組！',['class'=>'alert alert-warning','role'=>'alert']);
echo GridView::widget([
    'layout'=> '{items}',
    'dataProvider' => new ArrayDataProvider([
        'allModels' => $questions,
        'sort' => new Sort([
            'attributes' => [
                'party' => [
                    'default' => SORT_DESC,
                ],
            ],
        ]),
        'pagination' => false,
    ]),
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'columns' => [
        [
            'label' => '問題代碼',
            'attribute' => 'questionID',
            'value' => function ($model, $key, $index, $column) {
                return $model['questionID'];
            },
            'headerOptions' => ['class' => 'align-middle text-center'],
            'contentOptions'=> ['class' => 'align-middle text-start font-monospace'],
        ],
        [
            'label' => '分組',
            'attribute' => 'party',
            'value' => function ($model, $key, $index, $column) use ($parties) {
                return $parties[$model['party']];
            },
            'headerOptions' => ['class' => 'align-middle text-center'],
            'contentOptions'=> ['class' => 'align-middle text-start font-monospace'],
        ],
        [
            'label' => '標題',
            'attribute' => 'title',
            'value' => function ($model, $key, $index, $column) {
                return $model['title'];
            },
            'headerOptions' => ['class' => 'align-middle text-center'],
            'contentOptions'=> ['class' => 'align-middle text-center'],
        ],
    ]
]);
echo $this->blocks['backButton'];
$this->endBlock();


foreach($attr as $k => $a)
{
    $a['html'] = $this->blocks[$a['tag']];
    $attributes[$k] = $a;
}
echo DynamicTabbed::widget([
    'active' => 'uploadFile',
    'navAlign' => 'center',
    'attributes' => $attributes,
]);
