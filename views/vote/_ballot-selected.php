<?php

use yii\bootstrap5\Html;
use app\components\Model;
use kartik\grid\GridView;
use app\components\helper\ArrayHelper;
use app\components\helper\FileLoader;

$instEAry = array_merge(
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'instName','einstName'),
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'abb_instName','einstName')
);
$FileLoader = new FileLoader(Yii::getAlias('@filePool'));

// 候選欄位表頭
$beforeHeader = $candiConfig->getBeforeHeader(false, ['class' => 'bg-primary text-white border text-center']);
$ballotCandi->sort = false; // 關閉排序
$ballotCandi->pagination = false; // 關閉分頁
echo GridView::widget([
    'id' => $gridID,
    'dataProvider' => $ballotCandi,
    'summary' => '',//table-responsive
    'tableOptions' => ['class'=> 'table table-striped table-bordered mb-1 table-sm'],
    'options' => ['style' => 'font-size: 1.2rem;'],
    'headerRowOptions' => ['class' => 'bg-primary text-white'],
    'containerOptions' => ['style' => 'overflow: hidden'], // only set when $responsive = false
    'beforeHeader' => $candiConfig->useBeforeHeader ? $beforeHeader : false,
    'condensed' => false,
    'responsive' => false,
    'striped' => false,
    'columns' => $candiConfig->getFieldSort([
        'autoId' => [
            'header' => Yii::t('app', '編號'),
            'class' => 'yii\grid\SerialColumn',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 5rem'],
        ],
        'id' => [
            'label' => Yii::t('app', '編號'),
            'attribute' => 'id',
            'value' => function ($model, $key, $index, $column)
            {
                return $model->candiData->id;
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 5rem'],
        ],
        'party' => [
            'label' => Yii::t('app', '分組'),
            'attribute' => 'party',
            'value' => function ($model2, $key, $index, $column) use ($model)
            {
                $party = $model->getVoteParty($model->voteID, Yii::$app->language, true)[$model2->candiData->party];
                if (is_null($party)) {
                    return '';
                }
                return $party;
            },
            'group' => true,
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'questionID' => [
            'label' => Yii::t('app', ''),
            'attribute' => 'questionID',
            'value' => function ($model, $key, $index, $column) use ($questions)
            {
                return $questions[$model->questionID];
            },
            'group' => true,
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'jobLctn' => [
            'label' => Yii::t('app', '工作地點'),
            'attribute' => 'jobLctn',
            'value' => function ($model, $key, $index, $column)
            {
                if(trim($model->candiData->jobLctn) == '')
                    return '';
                return Model::i18n(
                    Yii::$app->params['ct.candi.jobLctnEAry'][$model->candiData->jobLctn],
                    Yii::$app->params['ct.candi.jobLctnAry'][$model->candiData->jobLctn]
                );
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'instName' => [
            'label' => Yii::t('app', '單位名稱'),
            'attribute' => 'instName',
            'value' => function ($model, $key, $index, $column) use ($instEAry)
            {
                if(trim($model->candiData->instName) == '')
                    return '';
                if(trim($model->candiData->instNameE) == '')
                {
                    if(ArrayHelper::keyExists($model->candiData->instName,$instEAry))
                    {
                        return Model::i18n($instEAry[$model->candiData->instName],$model->candiData->instName);
                    }
                }
                return Model::i18n($model->candiData->instNameE,$model->candiData->instName);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
        ],
        'title' => [
            'label' => Yii::t('app', '職稱'),
            'attribute' => 'title',
            'value' => function ($model, $key, $index, $column)
            {
                if(trim(Model::i18n($model->candiData->titleE,$model->candiData->title)) == '')
                    return '';
                return Model::i18n($model->candiData->titleE,$model->candiData->title);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
        ],
        'Name' => [
            'label' => Model::i18n($candiConfig->NameE, $candiConfig->Name),
            'attribute' => 'Name',
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->NameE, $model->candiData->Name, false);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
        ],
        'sex' => [
            'label' => Yii::t('app', '性別'),
            'attribute' => 'sex',
            'value' => function ($model, $key, $index, $column)
            {
                if(trim($model->candiData->sex) == '')
                    return '';
                return Model::i18n(
                    Yii::$app->params['ct.candi.sexEAry'][$model->candiData->sex],
                    Yii::$app->params['ct.candi.sexAry'][$model->candiData->sex]
                );
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'photo' => [
            'label' => Yii::t('app', '照片'),
            'attribute' => 'photo',
            'content' => function ($model, $key, $index, $column)
            {
                if (isset($model->photo)) {
                    return Html::img(['candi/view-photo', 'voteID' => $model->voteID, 'file' => $model->photo], ['width'=>'250', 'class'=>'img-fluid']);
                }
                return '';
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColA' => [
            'label' => Model::i18n($candiConfig->otherColNameAE, $candiConfig->otherColNameA),
            'attribute' => Model::i18n('otherColAE', 'otherColA'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColAE, $model->candiData->otherColA);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColB' => [
            'label' => Model::i18n($candiConfig->otherColNameBE, $candiConfig->otherColNameB),
            'attribute' => Model::i18n('otherColBE', 'otherColB'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColBE, $model->candiData->otherColB);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColC' => [
            'label' => Model::i18n($candiConfig->otherColNameCE, $candiConfig->otherColNameC),
            'attribute' => Model::i18n('otherColCE', 'otherColC'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColCE, $model->candiData->otherColC);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColD' => [
            'label' => Model::i18n($candiConfig->otherColNameDE, $candiConfig->otherColNameD),
            'attribute' => Model::i18n('otherColDE', 'otherColD'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColDE, $model->candiData->otherColD);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColE' => [
            'label' => Model::i18n($candiConfig->otherColNameEE, $candiConfig->otherColNameE),
            'attribute' => Model::i18n('otherColEE', 'otherColE'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColEE, $model->candiData->otherColE);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColF' => [
            'label' => Model::i18n($candiConfig->otherColNameFE, $candiConfig->otherColNameF),
            'attribute' => Model::i18n('otherColFE', 'otherColF'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColFE, $model->candiData->otherColF);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
    ], [], [], $candiConfig->useBeforeHeader && $beforeHeader != false),
]);