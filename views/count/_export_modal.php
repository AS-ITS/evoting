<?php

use yii\bootstrap5\Html;
use app\components\Model;
use kartik\grid\GridView;
use yii\bootstrap5\Modal;
use app\components\helper\ArrayHelper;
use yii\helpers\HtmlPurifier;
use rmrevin\yii\fontawesome\FAS;

Modal::begin([
    'id' => 'export-modal',
    'title' => FAS::icon('file-export').'組別得票匯出',
    'toggleButton' => [
        'label' => FAS::icon('file-export').'組別得票匯出', 'class' => 'btn btn-secondary hide-print'
    ],
    'options' => ['class' => 'bg-dark'],
    'clientOptions' => ['backdrop' => false],
    'scrollable' => true,
    'size' => 'modal-xl',
    'footer' => Html::button('返回', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal'])
]);

// 把根據組別及問題分類的候選人名單變成同一個array
$exportDataProviderModels = [];
foreach ($ballotCountSort as $party => $questions) {
    foreach ($questions as $questionID => $data) {
        foreach ($data['ranking'] as $key => $rank) {
            $rank['party'] = $party;
            $rank['questionID'] = $questionID;
            $exportDataProviderModels[] = $rank;
        }
    }
}

$questions = ArrayHelper::map($voteQuestions ?: [], 'questionID', 'title');

// 取得匯出資料的DataProvider
$exportDataProvider = $model->exportDataProvider($exportDataProviderModels, Yii::$app->request->getQueryParams());

echo GridView::widget([
    'id' => 'export',
    'dataProvider' => $exportDataProvider,
    'filterModel' => $model,
    'pjax'=>true,
    'striped' => true,
    'hover' => true,
    'panel' => [
        'type' => GridView::TYPE_DEFAULT,
        'heading' => '可以篩選組別及問題。',
        'headingOptions' => ['class' => 'card-header text-danger'],
        'after' => false,
        'footer' => false
    ],
    'tableOptions' => ['class' => 'table-sm'],
    'toolbar'=>[
        '{export}'
    ],
    'exportConfig' => [
        GridView::CSV => [
            'filename' => Yii::t('app', HtmlPurifier::process($model->voteName).'計票單'),
        ],
        GridView::HTML => [
            'filename' => Yii::t('app', HtmlPurifier::process($model->voteName).'計票單'),
        ],
        GridView::EXCEL => [
            'filename' => Yii::t('app', HtmlPurifier::process($model->voteName).'計票單'),
            'alertMsg' => '<b class="text-danger">'.Yii::t('app', '下載後檔案必須使用LibreOffice開啟').'</b>',
        ],
        GridView::JSON => [
            'filename' => Yii::t('app', HtmlPurifier::process($model->voteName).'計票單'),
        ],
    ],
    'columns' => [
        'party' => [
            'label' => '組別',
            'value' => function ($model, $key, $index, $column) use ($parties)
            {
                return $parties[$model['party']];
            },
            'filter' => Html::dropDownList('party', Yii::$app->request->getQueryParam('party'), $parties, ['class'=>'form-control','prompt' => '選擇組別']),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'questionID' => [
            'label' => '問題',
            'value' => function ($model, $key, $index, $column) use ($questions)
            {
                return $questions[$model['questionID']];
            },
            'filter' => Html::dropDownList('questionID', Yii::$app->request->getQueryParam('questionID'), $questions, ['class'=>'form-control','prompt' => '選擇問題']),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'Name' => [
            'label' => Model::i18n($model->candiConfig->NameE, $model->candiConfig->Name),
            'value' => function ($model, $key, $index, $column)
            {
                return $model['Name'];
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'Votes' => [
            'label' => '得票數',
            'value' => function ($model, $key, $index, $column)
            {
                return $model['count'];
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'VoteSort' => [
            'label' => '得票排序',
            'value' => function ($model, $key, $index, $column)
            {
                return $model['rank'];
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
    ],
]);
Modal::end();
