<?php
use yii\helpers\Url;

use yii\helpers\Html;
use app\components\Model;
use app\models\CandiData;
use kartik\grid\GridView;
use app\components\helper\ArrayHelper;
use app\components\helper\FileLoader;

$title = Yii::$app->controller->id == 'ballot-work' ? Yii::t('app', '開票結果') : Yii::t('app', '投票結果');
$this->title = $title;

$FileLoader = new FileLoader(Yii::getAlias('@filePool'));
$instEAry = array_merge(
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'instName','einstName'),
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'abb_instName','einstName')
);
echo \app\widgets\Alert::widget();

// 後臺投票結果預覽
if (in_array(Yii::$app->controller->id, ['result', 'ballot-work'])) {
    $route = (Yii::$app->controller->id == 'result') ? 'result/index' : 'ballot-work/result';
    $exit = Html::a(Yii::t('app', '退出'), Url::to([$route, 'voteID' => $voteInfo->voteID]), [
        'class' => 'btn btn-sm btn-secondary btn-default float-end hide-print', 
    ]);
}
else {
    $exit = '';
}
echo Html::tag('div', 
    Html::tag('div', '', ['class' => 'col-4']).
    Html::tag('div', Html::tag('h2', $title), ['class' => 'col-4 text-center']).
    Html::tag('div', $exit, ['class' => 'col-4 d-flex justify-content-end']),
['class' => 'row flex-nowrap justify-content-between align-items-center']);

echo '<hr>';
echo Html::tag('h3', $voteInfo->voteName, ['class' => 'text-center my-2 fw-bold']);

echo GridView::widget([
    'dataProvider' => $dataProvider,
    'panel' => false,
    'summary' => false,
    'showPageSummary' => false,
    'toggleDataContainer' => ['class' => 'btn-group me-2'],
    'tableOptions' => ['class' => 'table-sm'],
    'columns' => $resultConfig->getFieldSort($candiConfig ,[
        // 投票結果
        'rank' => [
            'header' => Yii::t('app', '排序'),
            'attribute' => 'rank',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'ballotCounts' => [
            'header' => Yii::t('app', '得票數'),
            'attribute' => 'ballotCounts',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
            'value' => function ($model, $key, $index, $column)
            {
                if ($model->candiData->isReachThreshold ?? '' == CandiData::REACH_THRESHOLD) {
                    return '已達門檻';
                }
                return $model->{$column->attribute};
            },
        ],
        'elected' => [
            'header' => Yii::t('app', '當選與否'),
            'attribute' => 'elected',
            'value' => function ($model, $key, $index, $column)
            {
                return Yii::t('app', Yii::$app->params['ct.result.electedAry'][$model->elected]);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'comment' => [
            'header' => Yii::t('app', '備註'),
            'attribute' => 'comment',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],

        // 候選人
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
            'value' => function ($model, $key, $index, $column) use ($parties)
            {
                return $parties[$model->candiData->party ?? null] ?? '';
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
                return strip_tags($questions[$model->questionID]);
            },
            // 'group' => true,
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'jobLctn' => [
            'label' => Yii::t('app', '工作地點'),
            'attribute' => 'jobLctn',
            'value' => function ($model, $key, $index, $column)
            {
                if(trim($model->candiData->jobLctn ?? '') == '')
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
                if(trim($model->candiData->instName ?? '') == '')
                    return '';
                if(trim($model->candiData->instNameE ?? '') == '')
                {
                    if(ArrayHelper::keyExists($model->candiData->instName,$instEAry))
                    {
                        return Model::i18n($instEAry[$model->candiData->instName],$model->candiData->instName, null);
                    }
                }
                return Model::i18n($model->candiData->instNameE,$model->candiData->instName, null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'title' => [
            'label' => Yii::t('app', '職稱'),
            'attribute' => 'title',
            'value' => function ($model, $key, $index, $column)
            {
                if(trim($model->candiData->title ?? '') == '')
                    return '';
                return Model::i18n($model->candiData->titleE,$model->candiData->title, null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'Name' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->NameE, $candiConfig->Name),
            'attribute' => 'Name',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->NameE ?? '', $model->candiData->Name ?? '', false);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'sex' => [
            'label' => Yii::t('app', '性別'),
            'attribute' => 'sex',
            'value' => function ($model, $key, $index, $column)
            {
                if(trim($model->candiData->sex ?? '') == '')
                    return '';
                return Model::i18n(
                    Yii::$app->params['ct.candi.sexEAry'][$model->candiData->sex ?? ''],
                    Yii::$app->params['ct.candi.sexAry'][$model->candiData->sex ?? ''], null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'photo' => [
            'label' => Yii::t('app', '照片'),
            'attribute' => 'photo',
            'content' => function ($model, $key, $index, $column)
            {
                $photo = $model->candiData->photo ?? null; // 照片存於候選人資料，results 無此欄位
                return empty($photo) ? '' : Html::img(['candi/view-photo', 'voteID' => $model->voteID, 'file' => $photo], ['width'=>'250', 'class'=>'img-fluid']);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColA' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->otherColNameAE ?? '', $candiConfig->otherColNameA ?? '', false),
            'attribute' => Model::i18n('otherColAE', 'otherColA'),
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColAE ?? '', $model->candiData->otherColA ?? '', false);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColB' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->otherColNameBE ?? '', $candiConfig->otherColNameB ?? '', false),
            'attribute' => Model::i18n('otherColBE', 'otherColB'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColBE ?? '', $model->candiData->otherColB ?? '', null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColC' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->otherColNameCE ?? '', $candiConfig->otherColNameC ?? '', false),
            'attribute' => Model::i18n('otherColCE', 'otherColC'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColCE ?? '', $model->candiData->otherColC ?? '', null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColD' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->otherColNameDE ?? '', $candiConfig->otherColNameD ?? '', false),
            'attribute' => Model::i18n('otherColDE', 'otherColD'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColDE ?? '', $model->candiData->otherColD ?? '', null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColE' => [
            'encodeLabel' => false,
            'label' => Model::i18n($candiConfig->otherColNameEE ?? '', $candiConfig->otherColNameE ?? '', false),
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
            'label' => Model::i18n($candiConfig->otherColNameFE ?? '', $candiConfig->otherColNameF ?? '', false),
            'attribute' => Model::i18n('otherColFE', 'otherColF'),
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->otherColFE ?? '', $model->candiData->otherColF ?? '', null);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
    ]),
]);

/**
 * 簽名區域
 */
echo $this->render('@app/views/partials/print-mode');
