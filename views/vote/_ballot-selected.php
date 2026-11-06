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
                    Yii::$app->params['ct.candi.jobLctnAry'][$model->candiData->jobLctn], null);
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
                        return Model::i18n($instEAry[$model->candiData->instName],$model->candiData->instName, null);
                    }
                }
                return Model::i18n($model->candiData->instNameE,$model->candiData->instName, null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
        ],
        'title' => [
            'label' => Yii::t('app', '職稱'),
            'attribute' => 'title',
            'value' => function ($model, $key, $index, $column)
            {
                if(trim(Model::i18n($model->candiData->titleE,$model->candiData->title, null)) == '')
                    return '';
                return Model::i18n($model->candiData->titleE,$model->candiData->title, null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
        ],
        'Name' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->NameE, $candiConfig->Name),
            'attribute' => 'Name',
            'format' => 'raw',
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
                    Yii::$app->params['ct.candi.sexAry'][$model->candiData->sex], null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'photo' => [
            'label' => Yii::t('app', '照片'),
            'attribute' => 'photo',
            'content' => function ($model, $key, $index, $column)
            {
                $photo = $model->candiData->photo ?? null; // 照片存於候選人資料
                return empty($photo) ? '' : Html::img(['candi/view-photo', 'voteID' => $model->voteID, 'file' => $photo], ['width'=>'250', 'class'=>'img-fluid']);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColA' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->otherColNameAE, $candiConfig->otherColNameA, false),
            'attribute' => Model::i18n('otherColAE', 'otherColA'),
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColAE, $model->candiData->otherColA, false);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColB' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->otherColNameBE, $candiConfig->otherColNameB, false),
            'attribute' => Model::i18n('otherColBE', 'otherColB'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColBE, $model->candiData->otherColB, null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColC' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->otherColNameCE, $candiConfig->otherColNameC, false),
            'attribute' => Model::i18n('otherColCE', 'otherColC'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColCE, $model->candiData->otherColC, null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColD' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->otherColNameDE, $candiConfig->otherColNameD, false),
            'attribute' => Model::i18n('otherColDE', 'otherColD'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColDE, $model->candiData->otherColD, null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColE' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->otherColNameEE, $candiConfig->otherColNameE, false),
            'attribute' => Model::i18n('otherColEE', 'otherColE'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColEE, $model->candiData->otherColE, null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColF' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->otherColNameFE, $candiConfig->otherColNameF, false),
            'attribute' => Model::i18n('otherColFE', 'otherColF'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColFE, $model->candiData->otherColF, null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
    ], [], [], $candiConfig->useBeforeHeader && $beforeHeader != false),
]);