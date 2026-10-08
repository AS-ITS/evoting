<?php

use yii\bootstrap5\Html;
use app\components\Model;
use app\models\CandiData;
use app\models\Questions;
use kartik\grid\GridView;
use app\components\helper\ArrayHelper;
use yii\helpers\Json;

/** @var app\models\FormCandiConfig $candiConfig */

$this->title = '選票列印';

$language = strtolower(Yii::$app->language);
$id = $question['questionID'];
$title = $language === 'zh-tw' ? $question['title'] : ($question['titleE'] ?? $question['title']);
$parties = $model->getVoteParty($model->voteID, Yii::$app->language);
$instEAry = array_merge(
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'instName','einstName'),
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'abb_instName','einstName')
);
$ballotlimit = ['mostNum' => $question['numBallots'], 'leastNum' => $question['leastNumBallots']];
$ballotlimitJson = Json::encode($ballotlimit);

// 候選人 id => 顯示名稱，供 GridView options / 若有 JS 使用
$candidateListAry = [];
foreach ($candidateLists as $candidateList) {
    foreach ($candidateList->query->all() as $t) {
        $candidateListAry[$t->id] = Model::i18n($t->NameE, $t->Name);
    }
}

// 投票名稱
echo Html::tag('div', 
    Html::tag('span', $voteInfo->getVoteName(false, '圈選票', 'zh-tw'), ['id' => 'voteTitle']).
    Html::tag('br').
    Html::tag('span', $voteInfo->getVoteName(false, '圈選票', 'en-us'), ['id' => 'voteTitleE'])
, ['class' => 'text-center border border-dark', 'id' => 'title']);

// 排序方法
echo Html::tag('div', 
    Html::tag('span', Yii::t('vote', '依姓氏筆劃排序', [], 'zh-TW'), ['id' => 'sortText']).
    Html::tag('br').
    Html::tag('span', Yii::t('vote', '依姓氏筆劃排序', [], 'en-US'), ['id' => 'sortTextE'])
, ['class' => 'text-center mt-2', 'id' => 'sort']);

echo Html::beginTag('div', ['class' => 'mt-2 px-3 row row-cols-sm-'.count($candidateLists)]); // row
$colWidth = 12 / $candiConfig->columnNum;
$fontSize = $language == 'zh-tw' ? $candiConfig->fontSize : $candiConfig->fontSizeE;
$height = $language == 'zh-tw' ? $candiConfig->cellHeight : $candiConfig->cellHeightE;
$this->registerCss(<<<CSS
    .table > tbody > tr > td {
        height: $height;
        padding: 5px 3px 5px 3px;
        border-color: #000; 
    }
    .table > thead > tr > th {
        padding: 3px 0 3px 0;
        border-top: 1px solid !important;
        border-bottom: 1px solid;
        border-color: #000; 
    }
CSS
);
foreach ($candidateLists as $key => $candidateList) {
    // 排序
    $candidateList->sort = false;
    //$candidateListJson = Json::encode($candidateListAry); 應該沒有用?
    $beforeHeader = $candiConfig->getBeforeHeader(true, ['class' => 'text-center', 'style' => "background-color: {$candiConfig->headerColor};"], ['questionID'], $candidateList->sort, true, $key);
    $dNone = ($candiConfig->useBeforeHeader && $beforeHeader != false) ? 'd-none' : '';
    echo Html::beginTag('div', ['class' => "col-md-$colWidth px-0"]); // col-lg
    echo GridView::widget([
        'id' => 'myGrid'.$id.$key,
        'dataProvider' => $candidateList,
        'summary' => '',
        'tableOptions' => ['class' => 'table mb-1 cell'],
        'options' => [
            'class' => 'myGrid'.$id,
            'data-selected' => Yii::t('app', '本次投票，您共圈選 {0} {1}'),
            'data-unit' => Model::i18n($candiConfig->NameUnitE, $candiConfig->NameUnit),
            'data-question-title' => !empty(Yii::$app->params['acadmn.question'][$title]) ? Yii::$app->params['acadmn.question'][$title] : $title
        ],
        'headerRowOptions' => [
            'class' => 'text-dark border-top',
            'style' => "font-size: 18px; line-height: normal; background-color: {$candiConfig->headerColor};"
        ],
        // 'containerOptions' => ['style' => 'overflow: hidden'], // only set when $responsive = false
        'beforeHeader' => $candiConfig->useBeforeHeader ? $beforeHeader : false,
        'condensed' => false,
        'responsive' => true,
        'striped' => false,
        'rowOptions' => function ($model, $key, $index, $grid) use ($fontSize)
        {
            $backgroundColor = !empty($model->backgroundColor) ? $model->backgroundColor : '#fff';
            return [
                'style' => "background-color: $backgroundColor; font-size: $fontSize;"
            ];
        },
        'columns' => $candiConfig->getFieldSort([
            'checkbox' => [
                'class' => 'yii\grid\CheckboxColumn',
                'header' => Html::tag('span', Yii::t('app', '圈選欄', [], 'zh-TW'), ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', Yii::t('app', '圈選欄', [], 'en-US'), ['class' => 'text-en']),
                'content' => function ($model, $key, $index, $column) {
                    if ($model->isReachThreshold == CandiData::REACH_THRESHOLD) {
                        return Html::tag('span', Yii::t('app', '已達門檻'), ['class' => 'fw-bold']);
                    }
                    else {
                        return '';
                    }
                },
                'headerOptions'  => [
                    'class' => 'align-middle text-center '.$dNone,
                    'style' => 'width: 8rem; white-space: nowrap;'
                ],
                'contentOptions' => [
                    'class' => 'align-middle text-center', 
                    'style' => 'width: 8rem; white-space: nowrap;'
                ],
            ],
            'autoId' => [
                'header' => Html::tag('span', Yii::t('app', '編號', [], 'zh-TW'), ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', Yii::t('app', '編號', [], 'en-US'), ['class' => 'text-en']),
                'class' => 'yii\grid\SerialColumn',
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 5rem'],
            ],
            'id' => [
                'header' => Html::tag('span', Yii::t('app', '編號', [], 'zh-TW'), ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', Yii::t('app', '編號', [], 'en-US'), ['class' => 'text-en']),
                'attribute' => 'id',
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 5rem'],
            ],
            'party' => [
                'header' => Html::tag('span', Yii::t('app', '分組', [], 'zh-TW'), ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', Yii::t('app', '分組', [], 'en-US'), ['class' => 'text-en']),
                'attribute' => 'party',
                'value' => function ($model2, $key, $index, $column) use ($parties)
                {
                    if(empty($parties[$model2->party]) && $model2->party == Questions::ALL_PARTY_CODE)
                        return Model::i18n('All Parties', '共同投票');
                    return $parties[$model2->party];
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'jobLctn' => [
                'header' => Html::tag('span', Yii::t('app', '工作地點', [], 'zh-TW'), ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', Yii::t('app', '工作地點', [], 'en-US'), ['class' => 'text-en']),
                'attribute' => 'jobLctn',
                'value' => function ($model, $key, $index, $column)
                {
                    if(trim($model->jobLctn) == '')
                        return '';
                    return Model::i18n(
                        Yii::$app->params['ct.candi.jobLctnEAry'][$model->jobLctn],
                        Yii::$app->params['ct.candi.jobLctnAry'][$model->jobLctn]
                    );
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'instName' => [
                'header' => Html::tag('span', Yii::t('app', '單位名稱', [], 'zh-TW'), ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', Yii::t('app', '單位名稱', [], 'en-US'), ['class' => 'text-en']),
                'attribute' => 'instName',
                'value' => function ($model, $key, $index, $column) use ($instEAry)
                {
                    if(trim($model->instName) == '')
                        return '';
                    if(trim($model->instNameE) == '')
                    {
                        if(ArrayHelper::keyExists($model->instName, $instEAry)) {
                            return Model::i18n($instEAry[$model->instName], $model->instName);
                        }
                    }
                    return Model::i18n($model->instNameE,$model->instName);
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'title' => [
                'header' => Html::tag('span', Yii::t('app', '職稱', [], 'zh-TW'), ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', Yii::t('app', '職稱', [], 'en-US'), ['class' => 'text-en']),
                'attribute' => 'title',
                'value' => function ($model, $key, $index, $column)
                {
                    if(trim($model->title) == '')
                        return '';
                    return Model::i18n($model->titleE, $model->title);
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'Name' => [
                'header' => Html::tag('span', $candiConfig->Name, ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', $candiConfig->NameE, ['class' => 'text-en']),
                'attribute' => 'id',
                'value' => function ($model, $key, $index, $column)
                {
                    return Html::tag('span', $model->Name, ['class' => 'text-ch']).'<br>'.
                           Html::tag('span', $model->NameE, ['class' => 'text-en']);
                    return Model::i18n($model->NameE, $model->Name, false);
                },
                'format' => 'raw',
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'sex' => [
                'header' => Html::tag('span', Yii::t('app', '性別', [], 'zh-TW'), ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', Yii::t('app', '性別', [], 'en-US'), ['class' => 'text-en']),
                'attribute' => 'sex',
                'value' => function ($model, $key, $index, $column)
                {
                    if(trim($model->sex) == '')
                        return '';
                    return Model::i18n(
                        Yii::$app->params['ct.candi.sexEAry'][$model->sex],
                        Yii::$app->params['ct.candi.sexAry'][$model->sex]
                    );
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'photo' => [
                'header' => Html::tag('span', Yii::t('app', '照片', [], 'zh-TW'), ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', Yii::t('app', '照片', [], 'en-US'), ['class' => 'text-en']),
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
                'header' => Html::tag('span', $candiConfig->otherColNameA, ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', $candiConfig->otherColNameAE, ['class' => 'text-en']),
                'attribute' => Model::i18n('otherColAE', 'otherColA'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => [
                    'class' => 'align-middle text-start',
                ],
                'content' => function ($model, $key, $index, $column)
                {
                    if (is_numeric($model[$column->attribute])) {
                        return Html::tag('span', $model[$column->attribute]);
                    }
                    return Html::tag('span', $model[$column->attribute], ['class' => 'text-ch']).'<br>'.
                           Html::tag('span', $model[$column->attribute.'E'], ['class' => 'text-en']);
                },
            ],
            'otherColB' => [
                'header' => Html::tag('span', $candiConfig->otherColNameB, ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', $candiConfig->otherColNameBE, ['class' => 'text-en']),
                'encodeLabel' => false,
                'attribute' => Model::i18n('otherColBE', 'otherColB'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
                'content' => function ($model, $key, $index, $column)
                {
                    if (is_numeric($model[$column->attribute])) {
                        return Html::tag('span', $model[$column->attribute]);
                    }
                    return Html::tag('span', $model[$column->attribute], ['class' => 'text-ch']).'<br>'.
                           Html::tag('span', $model[$column->attribute.'E'], ['class' => 'text-en']);
                },
            ],
            'otherColC' => [
                'header' => Html::tag('span', $candiConfig->otherColNameC, ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', $candiConfig->otherColNameCE, ['class' => 'text-en']),
                'encodeLabel' => false,
                'attribute' => Model::i18n('otherColCE', 'otherColC'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
                'content' => function ($model, $key, $index, $column)
                {
                    if (is_numeric($model[$column->attribute])) {
                        return Html::tag('span', $model[$column->attribute]);
                    }
                    return Html::tag('span', $model[$column->attribute], ['class' => 'text-ch']).'<br>'.
                           Html::tag('span', $model[$column->attribute.'E'], ['class' => 'text-en']);
                },
            ],
            'otherColD' => [
                'header' => Html::tag('span', $candiConfig->otherColNameD, ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', $candiConfig->otherColNameDE, ['class' => 'text-en']),
                'attribute' => Model::i18n('otherColDE', 'otherColD'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
                'content' => function ($model, $key, $index, $column)
                {
                    if (is_numeric($model[$column->attribute])) {
                        return Html::tag('span', $model[$column->attribute]);
                    }
                    return Html::tag('span', $model[$column->attribute], ['class' => 'text-ch']).'<br>'.
                           Html::tag('span', $model[$column->attribute.'E'], ['class' => 'text-en']);
                },
            ],
            'otherColE' => [
                'header' => Html::tag('span', $candiConfig->otherColNameE, ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', $candiConfig->otherColNameEE, ['class' => 'text-en']),
                'attribute' => Model::i18n('otherColEE', 'otherColE'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
                'content' => function ($model, $key, $index, $column)
                {
                    if (is_numeric($model[$column->attribute])) {
                        return Html::tag('span', $model[$column->attribute]);
                    }
                    return Html::tag('span', $model[$column->attribute], ['class' => 'text-ch']).'<br>'.
                           Html::tag('span', $model[$column->attribute.'E'], ['class' => 'text-en']);
                },
            ],
            'otherColF' => [
                'header' => Html::tag('span', $candiConfig->otherColNameF, ['class' => 'text-ch']).'<br>'.
                            Html::tag('span', $candiConfig->otherColNameFE, ['class' => 'text-en']),
                'attribute' => Model::i18n('otherColFE', 'otherColF'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
                'content' => function ($model, $key, $index, $column)
                {
                    if (is_numeric($model[$column->attribute])) {
                        return Html::tag('span', $model[$column->attribute]);
                    }
                    return Html::tag('span', $model[$column->attribute], ['class' => 'text-ch']).'<br>'.
                           Html::tag('span', $model[$column->attribute.'E'], ['class' => 'text-en']);
                },
            ],
        ],[
            'checkbox' => \app\models\FormCandiConfig::$FsBefore
        ], ['questionID'], ($candiConfig->useBeforeHeader && $beforeHeader) != false),
    ]);
    echo Html::endTag('div'); // col-lg
}
echo Html::endTag('div'); // row

$this->registerJs(<<<JS
// 刪除bootstrap.css中的@media print
removeBootstrapPrint();
JS
);