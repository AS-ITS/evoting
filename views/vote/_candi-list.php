<?php
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\grid\GridView;

use app\components\Model;
use app\components\helper\ArrayHelper;
use app\components\helper\FileLoader;

if(is_null($candidateList))
{
    echo Html::tag('div', '尚未建立'.$candidateListName, ['class' => 'alert alert-warning', 'role' => 'alert']);
}
elseif (empty($candiConfig)) {
    echo Html::tag('span', '', ['class' => '', 'role' => 'alert']);
}
else
{
    $FileLoader = new FileLoader(Yii::getAlias('@filePool'));
    $instEAry = array_merge(
        ArrayHelper::map(Yii::$app->session->get('Share.instAry'), 'instName','einstName'),
        ArrayHelper::map(Yii::$app->session->get('Share.instAry'), 'abb_instName','einstName')
    );
    echo GridView::widget([
        'id' => 'myGrid',
        'dataProvider' => $candidateList,
        // 'headerRowOptions' => ['class'=> 'bg-primary text-white'],
        'summary' => Yii::$app->tablePag->getSummaryText('myGrid'),
        'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
        'rowOptions' => function ($model, $key, $index, $grid) use ($candiConfig) {
            $backgroundColor = !empty($model->backgroundColor) ? $model->backgroundColor : '#fff';
            return ['style' => "background-color: $backgroundColor;"];
        },
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
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 5rem'],
            ],
            'questionID' => [
                'label' => '',
                'attribute' => 'questionID',
                'value' => function ($model, $key, $index, $column) use ($questions)
                {
                    return strip_tags($questions[$model->questionID]);
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'party' => [
                'label' => Yii::t('app', '分組'),
                'attribute' => 'party',
                'value' => function ($model, $key, $index, $column) use ($parties)
                {
                    return $parties[$model->party];
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'jobLctn' => [
                'label' => Yii::t('app', '工作地點'),
                'attribute' => 'jobLctn',
                'value' => function ($model, $key, $index, $column)
                {
                    if(trim($model->jobLctn) == '')
                        return '';
                    return Model::i18n(
                        Yii::$app->params['ct.candi.jobLctnEAry'][$model->jobLctn],
                        Yii::$app->params['ct.candi.jobLctnAry'][$model->jobLctn], null);
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'instName' => [
                'label' => Yii::t('app', '單位名稱'),
                'attribute' => 'instName',
                'value' => function ($model, $key, $index, $column) use ($instEAry)
                {
                    if(trim($model->instName) == '')
                        return '';
                    if(trim($model->instNameE) == '')
                    {
                        if(ArrayHelper::keyExists($model->instName,$instEAry))
                        {
                            return Model::i18n($instEAry[$model->instName],$model->instName, null);
                        }
                    }
                    return Model::i18n($model->instNameE,$model->instName, null);
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'title' => [
                'label' => Yii::t('app', '職稱'),
                'attribute' => 'title',
                'value' => function ($model, $key, $index, $column)
                {
                    if(trim($model->title) == '')
                        return '';
                    return Model::i18n($model->titleE,$model->title, null);
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'Name' => [
                'encodeLabel' => false,
                'label' => Model::i18n($candiConfig->NameE, $candiConfig->Name),
                'attribute' => 'Name',
                'value' => function ($model, $key, $index, $column)
                {
                    return Model::i18n($model->NameE,$model->Name, null);
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center text-nowrap'],
            ],
            'sex' => [
                'label' => Yii::t('app', '性別'),
                'attribute' => 'sex',
                'value' => function ($model, $key, $index, $column)
                {
                    if(trim($model->sex) == '')
                        return '';
                    return Model::i18n(
                        Yii::$app->params['ct.candi.sexEAry'][$model->sex],
                        Yii::$app->params['ct.candi.sexAry'][$model->sex], null);
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'photo' => [
                'label' => Yii::t('app', '照片'),
                'attribute' => 'photo',
                'content' => function ($model, $key, $index, $column)
                {
                    return empty($model->photo) ? '' : Html::img(['candi/view-photo', 'voteID' => $model->voteID, 'file' => $model->photo], ['width'=>'250', 'class'=>'img-fluid']);
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'otherColA' => [
                'encodeLabel' => false,
                'label' => Model::i18n($candiConfig->otherColNameAE, $candiConfig->otherColNameA, false),
                'attribute' => Model::i18n('otherColAE', 'otherColA'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => [
                    'class' => 'align-middle text-start',
                    'onclick' => '$(this).children().toggleClass("text-wrap d-block text-truncate");',
                ],
                'content' => function ($model, $key, $index, $column)
                {
                    return Html::tag('span', HtmlPurifier::process($model[$column->attribute], Yii::$app->params['HtmlPurifier.config']), [
                        'class' => 'd-block text-truncate text-mw', 
                    ]);
                },
            ],
            'otherColB' => [
                'encodeLabel' => false,
                'label' => Model::i18n($candiConfig->otherColNameBE, $candiConfig->otherColNameB, false),
                'attribute' => Model::i18n('otherColBE', 'otherColB'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'otherColC' => [
                'encodeLabel' => false,
                'label' => Model::i18n($candiConfig->otherColNameCE, $candiConfig->otherColNameC, false),
                'attribute' => Model::i18n('otherColCE', 'otherColC'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'otherColD' => [
                'encodeLabel' => false,
                'label' => Model::i18n($candiConfig->otherColNameDE, $candiConfig->otherColNameD, false),
                'attribute' => Model::i18n('otherColDE', 'otherColD'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'otherColE' => [
                'encodeLabel' => false,
                'label' => Model::i18n($candiConfig->otherColNameEE, $candiConfig->otherColNameE, false),
                'attribute' => Model::i18n('otherColEE', 'otherColE'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'otherColF' => [
                'encodeLabel' => false,
                'label' => Model::i18n($candiConfig->otherColNameFE, $candiConfig->otherColNameF, false),
                'attribute' => Model::i18n('otherColFE', 'otherColF'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
        ])
    ]);
}
