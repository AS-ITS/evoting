<?php
use yii\helpers\Url;
use yii\helpers\Html;
use app\components\Model;
use app\models\CandiData;
use kartik\grid\GridView;
use yii\bootstrap5\Dropdown;
use app\components\helper\ArrayHelper;
use kartik\editable\Editable;
use yii\helpers\HtmlPurifier;
use rmrevin\yii\fontawesome\FAS;
use app\components\helper\FileLoader;

$title = Yii::t('app', '開票結果');
$this->title = $title;
$this->params['secNavType'] = 'ballotWork'; // 啟用共用之 vote 二級導航

$FileLoader = new FileLoader(Yii::getAlias('@filePool'));
$instEAry = array_merge(
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'instName','einstName'),
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'abb_instName','einstName')
);

echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', Yii::t('app', $title)), ['class'=>'text-center mb-2']);
echo '<hr>';
echo Html::tag('h3', $voteInfo->voteName, ['class' => 'text-center my-2 fw-bold']);

foreach (Yii::$app->params['ct.result.electedAry'] as $elected => $electedText) {
    $items[] =  [
        'label' => $electedText,
        'url' => 'javascript:void(0)',
        'linkOptions' => ['class' => 'multi-edit', 'data-action' => $elected],
    ];
}
echo \app\widgets\Alert::widget();

$dataProvider->key = 'candID';
echo GridView::widget([
    'id' => 'result-grid',
    'dataProvider' => $dataProvider,
    'filterModel' => $model,
    'pjax' => true,
    'striped' => true,
    'hover' => true,
    'panel' => ['type' => false, 'heading' => false, 'after' => false, 'footer' => false],
    'summary' => false,
    'showPageSummary' => false,
    'toggleDataContainer' => ['class' => 'btn-group me-2'],
    'tableOptions' => ['class' => 'table-sm'],
    'toolbar'=>[
        '{export}',
        [
            'content' => Html::a('批次操作 <b class="caret"></b>', '#', ['class' => 'btn btn-secondary dropdown-toggle ms-2', 'data-bs-toggle' => 'dropdown']).
            Dropdown::widget(['items' => $items])
        ],
        [
            'content'=>
                Html::a(Yii::t('app', '當選名單'), Url::to(['ballot-work/result', 'voteID' => $voteInfo->voteID, 'type' => 'preview']), [
                    'title' => Yii::t('app', '當選名單'), 
                    'class'=>'btn btn-outline-success ms-2'
                ]).
                Html::a('<i class="fas fa-redo"></i>', Url::to(['result/index', 'voteID' => $voteInfo->voteID]), [
                    'class' => 'btn btn-secondary btn-default', 
                    'title' => Yii::t('app', '重新載入')
                ])
        ],
    ],
    'exportConfig' => [
        GridView::CSV => [
            'filename' => Yii::t('app', HtmlPurifier::process($voteInfo->Name).'投票結果'),
        ],
        GridView::HTML => [
            'filename' => Yii::t('app', HtmlPurifier::process($voteInfo->Name).'投票結果'),
        ],
        GridView::EXCEL => [
            'filename' => Yii::t('app', HtmlPurifier::process($voteInfo->Name).'投票結果'),
            'alertMsg' => '<b class="text-danger">'.Yii::t('app', '下載後檔案必須使用LibreOffice開啟').'</b>',
        ],
        GridView::JSON => [
            'filename' => Yii::t('app', HtmlPurifier::process($voteInfo->Name).'投票結果'),
        ],
    ],
    'columns' => $resultConfig->getFieldSort($candiConfig, [
        'checkbox' => [
            'class' => \kartik\grid\CheckboxColumn::className(),
            'rowSelectedClass' => GridView::BS_TABLE_INFO,
        ],
        // 投票結果
        'rank' => [
            'class'=>'kartik\grid\EditableColumn',
            'header' => '排序',
            'filter' => false,
            'attribute' => 'rank',
            'refreshGrid' => true,
            'editableOptions'=> function ($model, $key, $index) {
                return [
                    'preHeader' => FAS::icon('edit'),
                    'header' => '排序設定',
                    // 'size'   => 'md',
                    'formOptions' => [
                        'action' => ['/result/edit', 'voteID' => $model->voteID], 
                        'enableClientValidation' => false
                    ], // point to the new action
                    'inputType' => Editable::INPUT_TEXT, // point to the new action
                ];
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'ballotCounts' => [
            'header' => Yii::t('app', '得票數'),
            'filter' => false,
            'attribute' => 'ballotCounts',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
            'value' => function ($model, $key, $index, $column)
            {
                if ($model->candiData->isReachThreshold == CandiData::REACH_THRESHOLD) {
                    return '已達門檻';
                }
                return $model->{$column->attribute};
            },
        ],
        'elected' => [
            'class'=>'kartik\grid\EditableColumn',
            'header' => Yii::t('app', '當選與否'),
            'attribute' => 'elected',
            'value' => function ($model, $key, $index, $column)
            {
                return Yii::t('app', Yii::$app->params['ct.result.electedAry'][$model->elected]);
            },
            // 'readonly' => true,
            'editableOptions'=> function ($model, $key, $index) {
                return [
                    'preHeader' => FAS::icon('edit'),
                    'header' => '當選設定',
                    // 'size'   => 'md',
                    'formOptions' => [
                        'action' => ['/result/edit', 'voteID' => $model->voteID],
                        'enableClientValidation' => false
                    ], // point to the new action
                    'inputType' => Editable::INPUT_DROPDOWN_LIST, // point to the new action
                    'data' => Yii::$app->params['ct.result.electedAry'], // point to the new action
                    // 'afterInput'  => function ($form, $widget) use ($model, $index) {
                    //     return $form->field($model, 'elected')->dropDownList(Yii::$app->params['ct.result.electedAry']);
                    // }
                ];
            },
            'filter'=> Yii::$app->params['ct.result.electedAry'],
            'filterType' => GridView::FILTER_SELECT2,
            'filterWidgetOptions' => [
                'options' => ['prompt' => ''],
                'pluginOptions' => [
                    'allowClear' => true,
                ],
            ],
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'comment' => [
            'header' => Yii::t('app', '備註'),
            'filter' => false,
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
            'filter'=> $parties,
            'filterType' => GridView::FILTER_SELECT2,
            'filterWidgetOptions' => [
                'options' => ['prompt' => ''],
                'pluginOptions' => [
                    'allowClear' => true,
                ],
            ],
            'value' => function ($model, $key, $index, $column) use ($parties)
            {
                return $parties[$model->candiData->party];
            },
            // 'group' => true,
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'questionID' => [
            'label' => '',
            'attribute' => 'questionID',
            'filter'=> $questions,
            'filterType' => GridView::FILTER_SELECT2,
            'filterWidgetOptions' => [
                'options' => ['prompt' => ''],
                'pluginOptions' => [
                    'allowClear' => true,
                ],
            ],
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
                return Model::i18n($model->candiData->instNameE, $model->candiData->instName);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'title' => [
            'label' => Yii::t('app', '職稱'),
            'attribute' => 'title',
            'value' => function ($model, $key, $index, $column)
            {
                if(trim($model->candiData->title) == '')
                    return '';
                return Model::i18n($model->candiData->titleE, $model->candiData->title);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'Name' => [
            'label' => Model::i18n($candiConfig->NameE, $candiConfig->Name),
            'attribute' => 'Name',
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->candiData->NameE, $model->candiData->Name, false);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
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
                return Html::img(['candi/view-photo', 'voteID' => $model->voteID, 'file' => $model->photo], ['width'=>'250', 'class'=>'img-fluid']);
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
    ], ['checkbox' => \app\models\FormCandiConfig::$FsBefore]),
]);

$multiActionUrl = Url::to(['result/multi-edit', 'voteID' => $voteInfo->voteID]);
$this->registerJs(<<<JS
    // 批次操作（委派綁定，避免 Pjax 換 DOM 後失效）
    $(document).off('click.multiEdit').on('click.multiEdit', '.multi-edit', function(e) {
        e.preventDefault();
        var candis = $('#result-grid').yiiGridView('getSelectedRows');
        var action = $(this).data('action');
        if (candis.length === 0) {
            appDialog.warn('請先勾選要批次操作的資料');
        }
        else {
            $.ajax({
                url: '$multiActionUrl',
                async: false,
                type: 'POST',
                data: {
                    'ids': candis,
                    'action': action
                },
                dataType: "json",
                success: function (response) {
                    console.log(response);
                    window.location.reload();
                    return true;
                },
                error: function (response) {
                    appDialog.error('批次操作失敗');
                    console.log(response.responseText);
                }
            });
        }
    });
JS
);
