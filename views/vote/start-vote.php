<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\Json;
use app\components\Model;
use app\models\CandiData;
use app\models\FormVotes;
use app\models\Questions;
use kartik\grid\GridView;
use yii\bootstrap5\Modal;
use yii\widgets\ActiveForm;
use app\components\helper\ArrayHelper;
use yii\helpers\HtmlPurifier;
use rmrevin\yii\fontawesome\FAS;
use app\assets\BootstrapStepsAsset;
use app\components\helper\FileLoader;
use app\models\CandiConfig;
use yii\data\ArrayDataProvider;

/** @var yii\web\View $this */

$this->title = \app\components\Branding::siteTitle();
$language = strtolower(Yii::$app->language);
$FileLoader = new FileLoader(Yii::getAlias('@filePool'));

$parties = $model->getVoteParty($model->voteID, Yii::$app->language);
$isInformation = strip_tags(trim(Model::i18n($voteInfo->informationE, $voteInfo->information, false))) !== '';

// if (!Yii::$app->anon->isGuest && $voteInfo->partyOrNot == 1) {
//     echo Html::tag('span', $parties[Yii::$app->anon->identity->getAuthNData('party')], ['class' => 'ml-2 font-weight-bold']);
// }
$questionNums = count($questions);
$form = ActiveForm::begin([
    'enableAjaxValidation' => true, 
    'validateOnSubmit' => true,
    'options' => ['class' => 'text-center'],
    'action' => ['vote/save-vote', 'voteID' => $model->voteID],
]);

$instEAry = array_merge(
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'instName','einstName'),
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'abb_instName','einstName')
);
echo Html::tag('div', 
    Html::tag('div', 
        Html::tag('span', '', ['class' => 'spinner']) 
    , ['class' => 'w-100 d-flex justify-content-center align-items-center'])
, ['class' => 'overlay', 'style' => 'display: flex;']);
// bootstrap steps 設定
BootstrapStepsAsset::register($this);
echo Html::beginTag('ul', ['class' => 'steps nav nav-pills', 'id' => 'pills-tab', 'role' => 'tablist', 'style' => 'overflow: hidden;']);
$activeKey = 1;
foreach ($questions as $id => $question) {
    if ($model->VoteInfo->candiConfig == FormVotes::CANDI_CONFIG_BY_Q) {
        $candiConfig = $model->getCandidateConfig($model->voteID, $id);
    }
    $width = $candiConfig->width == CandiConfig::WIDTH_NARROW ? 'container' : 'container-fluid';
    $title = $language == 'zh-tw' ? $question['title'] : $question['titleE'];
    $liOption = ['class'=>'step nav-item', 'role'=>'presentation'];
    $summaryOption = [
        'id' => "pills-{$id}-tab", 'href' => "#pills-{$id}", 'aria-controls' => "pills-{$id}",
        'class' => 'step-content', 'role' => 'tab', 'data-bs-toggle' => 'pill', 'aria-selected' => 'true',
        'data-bs-width' => $width
    ];
    // 有投票動作紀錄
    if ($recordStep !== 0 || $recordStepMax !== 0) {
        if ($activeKey == 1 && $activeKey == $recordStep) {
            Html::addCssClass($liOption, 'first');
            Html::addCssClass($liOption, 'step-active');
            Html::addCssClass($summaryOption, 'active');
        }
        elseif ($activeKey == 1) {
            Html::addCssClass($liOption, 'first');
            Html::addCssClass($liOption, 'step-success');
        }
        elseif ($activeKey == $recordStep)
        {
            Html::addCssClass($liOption, 'step-active');
            Html::addCssClass($summaryOption, 'active');
        }
        elseif ($activeKey != $recordStep && $activeKey <= $recordStepMax) 
        {
            Html::addCssClass($liOption, 'step-success');
        }
        else
        {
            Html::addCssClass($summaryOption, 'disabled');
            Html::addCssStyle($summaryOption, 'cursor: not-allowed;');
        }
    }
    // 無投票紀錄動作
    else {
        if($activeKey == 1)
        {
            Html::addCssClass($liOption, 'first');
            Html::addCssClass($liOption, 'step-active');
            Html::addCssClass($summaryOption, 'active');
        }
        else
        {
            Html::addCssClass($summaryOption, 'disabled');
            Html::addCssStyle($summaryOption, 'cursor: not-allowed;');
        }
    }
    
    echo Html::beginTag('li', $liOption);
    echo Html::beginTag('summary', $summaryOption);
    echo Html::tag('span', $activeKey, ['class'=>'step-circle']);
    echo Html::tag('span', $title, ['class'=>'step-text']);
    echo Html::endTag('summary');
    echo Html::endTag('li');

    $activeKey++;
}
// 流程步驟已到確認
if ($recordStep == ($questionNums + 1) || $recordStepMax > $questionNums) {
    $stepClass = ($recordStep == ($questionNums + 1)) ? 'step-active' : 'step-success';
    echo '
        <li class="step nav-item '.$stepClass.' end" role="presentation">
        <summary class="step-content '.(($recordStep == ($questionNums + 1)) ? 'active' : '').'" id="pills-submit-tab" data-bs-toggle="pill" href="#pills-submit" role="tab" aria-controls="pills-submit" aria-selected="true" data-bs-width="container-fluid">
            <span class="step-circle">'.$activeKey.'</span>
            <span class="step-text">'.Yii::t('app', '確認').'</span>
        </summary>
        </li>
    ';
} else {
    echo '
        <li class="step nav-item end" role="presentation">
        <summary style="cursor: not-allowed;" class="step-content disabled" id="pills-submit-tab" data-bs-toggle="pill" href="#pills-submit" role="tab" aria-controls="pills-submit" aria-selected="false" data-bs-width="container-fluid">
            <span class="step-circle">'.$activeKey.'</span>
            <span class="step-text">'.Yii::t('app', '確認').'</span>
        </summary>
        </li>
    ';
}
echo Html::endTag('ul');
// 根據問題分類候選人
echo Html::beginTag('div', ['id' => 'pills-tabContent']); // tab content
$questionkey = 1;
$limitStyle = $language == 'zh-tw' ? 'top: 10px;' : '';
foreach ($questions as $id => $question) {
    if ($model->VoteInfo->candiConfig == FormVotes::CANDI_CONFIG_BY_Q) {
        $candiConfig = $model->getCandidateConfig($model->voteID, $id);
    }
    // 有投票動作紀錄
    if ($recordStep !== 0 || $recordStepMax !== 0) {
        echo Html::beginTag('div', [
            'class' => 'card text-center tab-pane border-0 '.(($recordStep <= $questionNums && $questionkey == $recordStep) ? 'show active' : ''),
            'id' => 'pills-'.$id,
            'role' => 'tabpanel',
        ]);
    }
    // 無投票動作紀錄
    else {
        echo Html::beginTag('div', [
            'class' => 'card text-center tab-pane border-0 '.($questionkey == 1 ? 'show active' : ''),
            'id' => 'pills-'.$id,
            'role' => 'tabpanel',
        ]);
    }
    $questionkey++;
    $title = $language == 'zh-tw' ? $question['title'] : $question['titleE'];
    $confirmTitle = $language == 'zh-tw' ? $question['confirmTitle'] : $question['confirmTitleE'];

    // 候選人名單
    $candidateLists = $model->getDataProvider($model->voteID, $model->party, $id, false, $candiConfig->columnNum);
    $candidateListAry = [];
    foreach ($candidateLists as $candidateList) {
        $getCandidate = function () use ($candidateList)
        {
            $temp = $candidateList->query->all();
            $r = [];
            foreach($temp as $t)
            {
                $r[$t->id] = Model::i18n($t->NameE, $t->Name);
            }
            return $r;
        };
        $candidateListAry += $getCandidate();
    }

    // 圈選人數限制
    $lowerLimitUnit = Model::i18n($candiConfig->NameUnitE.($question['leastNumBallots'] > 1 ? 's' : ''), $candiConfig->NameUnit);
    $upperLimitUnit = Model::i18n($candiConfig->NameUnitE.($question['numBallots'] > 1 ? 's' : ''), $candiConfig->NameUnit);
    $limitText = Questions::getQuestionLimitText('本次投票', '。', count($candidateListAry), $question, $lowerLimitUnit, $upperLimitUnit);
    echo Html::tag('div', $limitText, ['class' => 'text-end question-limit position-relative', 'style' => $limitStyle]);

    // 圈選人數
    echo Html::beginTag('div', ['class' => 'd-flex vote-tool py-0 mb-1', 'style' => 'padding-left: 9px;']);
    if ($isInformation) {
        echo Html::button(
            Html::tag('span', FAS::icon('info-circle', ['class' => 'me-2']).Yii::t('app', '圈選須知'), ['class' => '']),
            [
                'class' => 'btn btn-info btn-sm text-start me-2',
                'data' => [
                    'bs-toggle' => 'modal',
                    'bs-target' => '#vote-information'
                ]
            ]
        );
    }
    if (Yii::$app->controller->layout == 'vote/meeting') {
        echo Html::a(
            Html::img(Yii::getAlias('@web/img/translation.png'), ['class' => 'rounded', 'width' => '35;']),
            ['site/change-language'],
            ['class' => 'btn btn-sm text-start', 'style' => 'background: darkorange;']
        );
    }
    echo Html::tag('div', 
        Yii::t('app', '本次投票，您共圈選 {0} {1}', [
            '0' => Html::tag('span', 0, ['class' => 'mx-2']), 
            '1' => Model::i18n($candiConfig->NameUnitE, $candiConfig->NameUnit)
        ]), 
        ['class' => 'ms-auto question-numVotes d-flex align-items-end numVotes'.$id]
    );
    echo Html::endTag('div');
    
    echo Html::beginTag('div', ['class' => 'card-body pt-0']);
    echo Html::tag('div', $title, ['class' => 'fw-bolder question-title d-none', 'style' => 'font-size:2em']);
    // 投票限制
    $ballotlimit = ['mostNum' => $question['numBallots'], 'leastNum' => $question['leastNumBallots']];
    $ballotlimitJson = JSON::encode($ballotlimit);
    // 問題中英描述
    $questionDescription = Model::i18n($question['descriptionE'], $question['description'], false);
    if (!empty($questionDescription)) {
        echo Html::tag('div', 
            HtmlPurifier::process($questionDescription, Yii::$app->params['HtmlPurifier.config'])
        ,['class'=> 'col-auto fw-bold']);
    }
    
    echo Html::beginTag('div', ['class' => 'row row-cols-sm-'.count($candidateLists)]); // row
    $colWidth = 12 / $candiConfig->columnNum;
    $fontSize = $language == 'zh-tw' ? $candiConfig->fontSize : $candiConfig->fontSizeE;
    $height = $language == 'zh-tw' ? $candiConfig->cellHeight : $candiConfig->cellHeightE;
    $this->registerCss(<<<CSS
        #pills-$id .table > tbody > tr > td {
            height: $height;
            padding: 0 3px 0 3px;
            border-color: #000; 
        }
        #pills-$id .table > thead > tr > th {
            padding: 3px 0 3px 0;
            border-top: 1px solid;
            border-color: #000; 
        }
CSS
    );
    foreach ($candidateLists as $key => $candidateList) {
        /** @var app\models\CandiConfig $candiConfig */
        $headerColor = $candiConfig->getHeaderColor($key);
        // 排序
        $candidateList->sort = ($candiConfig->columnNum > 1 || !$candiConfig->sort) ? false : $candiConfig->getDataSort();
        $candidateListJson = JSON::encode($candidateListAry);
        $beforeHeader = $candiConfig->getBeforeHeader(true, ['class' => 'border text-center', 'style' => "background: {$headerColor};"], ['questionID'], $candidateList->sort, false, $key);
        $dNone = ($candiConfig->useBeforeHeader && $beforeHeader != false) ? 'd-none' : '';
        $alignLeftCols = Json::decode($candiConfig->alignLeft);
        $alignNameText = (!is_null($alignLeftCols) && in_array('Name', $alignLeftCols)) ? 'text-start' : 'text-center';
        echo Html::beginTag('div', ['class' => "col-md-$colWidth px-0", 'style' => 'padding-right: 5px;padding-left: 5px;']); // col-lg
        echo GridView::widget([
            'id' => 'myGrid'.$id.$key,
            'dataProvider' => $candidateList,
            'summary' => '',
            'tableOptions' => ['class' => 'table mb-1 rounded'],
            'options' => [
                'class' => 'myGrid'.$id,
                'data-selected' => Yii::t('app', '本次投票，您共圈選 {0} {1}'),
                'data-unit' => Model::i18n($candiConfig->NameUnitE, $candiConfig->NameUnit),
                'data-question-title' => !empty($confirmTitle) ? $confirmTitle : $title,
            ],
            'headerRowOptions' => [
                'class' => 'text-dark border-top',
                'style' => "font-size: 18px; line-height: normal; background: {$headerColor};"
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
                    'data'=>[
                        'key'     => $model->id,
                        'cand-id' => $model->id,
                    ],
                    'style' => "background-color: $backgroundColor; cursor: pointer; font-size: $fontSize;"
                ];
            },
            'columns' => $candiConfig->getFieldSort([
                'checkbox' => [
                    'class' => 'yii\grid\CheckboxColumn',
                    'header' => Yii::t('app', '圈選欄'),
                    'content' => function ($model, $key, $index, $column) use ($ballotSelected) {
                        if ($model->isReachThreshold == CandiData::REACH_THRESHOLD) {
                            return Html::tag('span', Yii::t('app', '已達門檻'), ['class' => 'fw-bold']);
                        }
                        else {
                            $checked = (in_array($model->id, $ballotSelected)) ? true : false;
                            return Html::input('checkbox', 'selection[]', $model->id, [
                                'id'=> 'c'.$model->id, 
                                'value' => $model->id, 
                                'checked' => $checked,
                                'class' => 'align-middle',
                                'style' => 'height: 1.5rem; width: 1.5rem;'
                            ]);
                        }
                    },
                    'headerOptions'  => ['class' => 'align-middle text-center '.$dNone],
                    'contentOptions' => function ($model, $key, $index, $grid) use ($id, $candidateListJson, $ballotlimitJson, $fontSize)
                    {
                        return [
                            'onClick' => 'chkClick(event, "#c'.$model->id.'", this, '.$id.', '.$candidateListJson.','.$ballotlimitJson.');',
                            'data'=>[
                                'key'     => $model->id,
                                'cand-id' => $model->id,
                            ],
                            'class' => 'align-middle text-center', 
                            'style' => 'width: 6rem; white-space: nowrap; line-height: 0;'
                        ];
                    },
                ],
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
                'party' => [
                    'label' => Yii::t('app', '分組'),
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
                    'label' => Yii::t('app', '工作地點'),
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
                    'label' => Yii::t('app', '單位名稱'),
                    'attribute' => 'instName',
                    'value' => function ($model, $key, $index, $column) use ($instEAry)
                    {
                        if(trim($model->instName) == '')
                            return '';
                        if(trim($model->instNameE) == '')
                        {
                            if(ArrayHelper::keyExists($model->instName, $instEAry))
                            {
                                return Model::i18n($instEAry[$model->instName], $model->instName);
                            }
                        }
                        return Model::i18n($model->instNameE,$model->instName);
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
                        return Model::i18n($model->titleE, $model->title);
                    },
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center'],
                ],
                'Name' => [
                    'label' => Model::i18n($candiConfig->NameE, $candiConfig->Name),
                    'attribute' => 'id',
                    'format' => 'raw',
                    'value' => function ($model, $key, $index, $column)
                    {
                        return Model::i18n($model->NameE, $model->Name);
                    },
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => function ($model, $key, $index, $grid) use ($id, $candidateListJson, $ballotlimitJson, $alignNameText)
                    {
                        return [
                            'onClick' => 'chkClick(event, "#c'.$model->id.'", this, '.$id.', '.$candidateListJson.','.$ballotlimitJson.');',
                            'class' => "align-middle {$alignNameText}", 
                            'style' => 'min-width: 5.5rem;'
                        ];
                    },
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
                            Yii::$app->params['ct.candi.sexAry'][$model->sex]
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
                    'label' => Model::i18n($candiConfig->otherColNameAE, $candiConfig->otherColNameA, null),
                    'encodeLabel' => false,
                    'attribute' => Model::i18n('otherColAE', 'otherColA'),
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => [
                        'class' => 'align-middle text-center',
                        'onclick' => '$(this).children().toggleClass("text-wrap d-block text-truncate");',
                    ],
                    'content' => function ($model, $key, $index, $column)
                    {
                        return Html::tag('span', $model[$column->attribute], [
                            'class' => 'd-block text-truncate text-mw', 
                        ]);
                    },
                ],
                'otherColB' => [
                    'label' => Model::i18n($candiConfig->otherColNameBE, $candiConfig->otherColNameB, null),
                    'encodeLabel' => false,
                    'attribute' => Model::i18n('otherColBE', 'otherColB'),
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center'],
                ],
                'otherColC' => [
                    'label' => Model::i18n($candiConfig->otherColNameCE, $candiConfig->otherColNameC, null),
                    'encodeLabel' => false,
                    'attribute' => Model::i18n('otherColCE', 'otherColC'),
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center'],
                ],
                'otherColD' => [
                    'label' => Model::i18n($candiConfig->otherColNameDE, $candiConfig->otherColNameD, null),
                    'encodeLabel' => false,
                    'attribute' => Model::i18n('otherColDE', 'otherColD'),
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center'],
                ],
                'otherColE' => [
                    'label' => Model::i18n($candiConfig->otherColNameEE, $candiConfig->otherColNameE, null),
                    'encodeLabel' => false,
                    'attribute' => Model::i18n('otherColEE', 'otherColE'),
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center'],
                ],
                'otherColF' => [
                    'label' => Model::i18n($candiConfig->otherColNameFE, $candiConfig->otherColNameF, null),
                    'encodeLabel' => false,
                    'attribute' => Model::i18n('otherColFE', 'otherColF'),
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center'],
                ],
            ],[
                'checkbox' => \app\models\FormCandiConfig::$FsBefore
            ], ['questionID'], ($candiConfig->useBeforeHeader && $beforeHeader) != false),
        ]);
        echo Html::endTag('div'); // col-lg
    }
    echo Html::endTag('div'); // row
    /** @var \app\models\FormManageVote $model */
    $invalidBallotJs = $model::$invalidBallot?'true':'false';// 是否可投無效票?
    $nameSeparator = Model::i18n(', ', '、');

    $this->registerJs(<<<JS
        showSubmitButton($id); // 顯示投票按鈕
        showCircled($id, true, {$candidateListJson}, {$ballotlimitJson}); // 顯示圈選資訊(初始)

        setTimeout(function () {
            showCircled($id, false, {$candidateListJson}, {$ballotlimitJson}); // 顯示圈選資訊(已有選項加載)
        }, 500);

        // 已圈選的上色
        $(".myGrid$id td[onclick]").each(function(index, value) {
            if(this.firstChild.checked == true) {
                value.parentElement.classList.toggle("highlight-color");
            }
        });
        // 預設尚未排序的欄位，加入可以排序的icon
        $('[data-sort]').each(function() {
            var element = $(this);
            if(element.children('span').length == 0) {
                element.append(' <span class="fas fa-sort"></span>');
            }
        });
JS
    , $this::POS_LOAD
    );
    echo Html::tag('div', '', ['class' => 'd-none text-center candidatesVoted'.$id]);
    echo Html::tag('div', Yii::t('app', '未圈選，將視為廢票。'), ['class' => 'd-none text-center fw-bold text-danger invalidMsgEachVotes'.$id]);
    
    echo Html::endTag('div'); // card-body
    $candiComment = strip_tags(Model::i18n($model->VoteInfo->candCommentE, $model->VoteInfo->candComment, false));
    if (!empty($candiComment)) {
        echo Html::tag('div', Model::i18n($model->VoteInfo->candCommentE, $model->VoteInfo->candComment, false), ['class' => 'card-footer']);
    }
    echo Html::endTag('div'); // card
}
// 調整寬度
$this->registerJs(<<<JS
    if ($('.step-active').children().data('width') != 'container') {
        $('main > div').removeClass('container').addClass('container-fluid');
    }
    else {
        $('main > div').removeClass('container-fluid').addClass('container');
    }
    $('summary[data-bs-toggle="pill"]').on('shown.bs.tab', function (event) {
        $('main > div').removeClass('container container-fluid').addClass($('#'+event.target.id).data('width'));
    });
    // 取消按enter送出投票
    $(document).keypress(function(event){
        if (event.which == '13') {
            event.preventDefault();
        }
    });
JS
, $this::POS_LOAD);
// 投票統計
echo Html::beginTag('div', [
    'class' => 'card border-dark mt-0 text-center tab-pane fade border-0 '.($recordStep == ($questionNums+1) ? 'show active' : ''),
    'id' => 'pills-submit',
    'role' => 'tabpanel'
]);
echo Html::tag('div', 
        Html::tag('span', Yii::t('app', '以下為您已圈選的名單，請確認您的圈選名單與圈選人數'))
,['class'=> 'd-flex justify-content-center fw-bold text-danger mt-3']);
echo Html::tag('div', FAS::icon('check-double', ['class' => 'me-2']).Yii::t('app', '確認'), ['class' => 'card-header text-white bg-dark fw-bolder question-title d-none']);
    echo Html::beginTag('div', ['class' => 'card-body pt-0']);
    foreach ($questions as $id => $question) {
        echo Html::beginTag('div', ['class' => 'card text-center mt-2']);
            echo Html::tag('div', $language == 'zh-tw' ? $question['title'] : $question['titleE'], ['class' => 'card-header fw-bolder']);
            echo Html::beginTag('div', ['class' => 'card-body py-1']);
                echo Html::tag('div', Yii::t('app', '廢票'), ['class' => 'text-center h1 fw-bold text-danger invalidVotesTitle'.$id]);
                echo Html::tag('div', Yii::t('app', '未圈選'), ['class' => 'text-center fw-bold text-danger invalidMsgVotes'.$id]);
                echo Html::tag('div', '', ['class' => 'text-center numVotes'.$id]);
                echo Html::tag('div', '', ['class' => 'text-center candidatesVoted'.$id]);
            echo Html::endTag('div'); // card-body
        echo Html::endTag('div'); // card
    }
    echo Html::endTag('div'); // card-body
echo Html::endTag('div');

echo Html::endTag('div'); // tab content

// 上一題或下一題按鈕
echo Html::tag('li', 
    Html::button(Yii::t('app', '上一步：'), ['class' => 'btn btn-info prev-step', 'id' => 'prev-btn', 'type' => 'button']),
    ['class' => 'list-inline-item '.($recordStep <= 1 ? 'd-none' : ''), 'id' => 'prev']
);
echo Html::tag('li', 
    Html::button(Yii::t('app', '下一步：'), ['class' => 'btn btn-info next-step', 'id' => 'next-btn', 'type' => 'button']),
    ['class' => 'list-inline-item '.($recordStep == ($questionNums+1) ? 'd-none' : ''), 'id' => 'next']
);
echo Html::tag('hr');

echo Html::button(
    FAS::icon('check-double').'&nbsp;'.Model::i18n('Confirm', '確認'),
    [
        'id' => 'cast-ballot',
        'class' => 'btn btn-primary '.($recordStep == ($questionNums+1) ? '' : 'd-none')
    ]
);
// ----------投票統計Modal begin----------
Modal::begin([
    'id' => 'vote-submit',
    'dialogOptions' => ['class' => 'modal-xl text-center modal-dialog-scrollable'],
    'options' => ['style' => 'background-color: rgba(0, 0, 0, 0.5); '],
    'clientOptions' => ['backdrop' => false],
    'title' => Yii::t('app', '您已圈選之投票統計'),
    'titleOptions' => ['class' => 'fw-bolder'],
    'closeButton' => false,
    'footer' => Html::tag('div',
        Html::tag('h4', Yii::t('app', '※ 投票名單確定後即無法更改，您確定要投出嗎？'), ['class' => 'text-danger fw-bolder']).
        Html::tag('span',
            Html::submitButton(Yii::t('app', '投票'), ['class' => 'btn btn-primary'])
        , ['id' => 'submitButton']).
        Html::tag('span',
            Html::a(
                Html::tag('span', '', [
                    'class' => 'spinner-border spinner-border-sm me-1', 
                    'style' => 'margin-bottom: 0.1rem !important;', 
                    'role' => 'status', 'aria-hidden' => 'true'
                ]).
                Yii::t('app', '投票')
            , null, ['class' => 'btn btn-primary disabled'])
        , ['id' => 'spinButton', 'style' => 'display: none;']).
        Html::tag('span',
            Html::button(Yii::t('app', '取消'), ['class' => 'btn btn-danger', 'data-bs-dismiss' => 'modal'])
        ),
        ['class'=>'text-center']),
    'footerOptions' => ['class' => 'justify-content-center']
]);

foreach ($questions as $id => $question) {
    echo Html::beginTag('div', ['class' => 'card text-center mt-2']);
        echo Html::tag('div', $language == 'zh-tw' ? $question['title'] : $question['titleE'], ['class' => 'card-header fw-bolder']);
        echo Html::beginTag('div', ['class' => 'card-body py-1']);
        echo Html::tag('div', Yii::t('app', '廢票'), ['class'=>'text-center h1 fw-bold text-danger invalidVotesTitleModal'.$id]);
        echo Html::tag('div', Yii::t('app', '未圈選'), ['class'=>'text-center fw-bold text-danger invalidMsgVotesModal'.$id]);
        echo Html::tag('div', '', ['class'=>'text-center numVotesModal'.$id]);
        echo Html::tag('div', '', ['class'=>'text-center candidatesVotedModal'.$id]);
        echo Html::endTag('div'); // card-body
    echo Html::endTag('div'); // card
}
Modal::end();
// ----------投票統計Modal end----------

// ----------無勾選Modal begin----------
Modal::begin([
    'id' => 'empty-candi-alert',
    'dialogOptions' => ['class' => 'modal-xl modal-dialog-centered text-center'],
    'options' => ['style' => 'background-color: rgba(0, 0, 0, 0.5); '],
    'clientOptions' => ['backdrop' => false],
    'headerOptions' => ['class' => 'd-none'],
    'bodyOptions' => ['class' => 'pb-0'],
    'footer' => Html::tag('div', 
        Html::tag('span',
            Html::Button(Yii::t('app', '確定'), [
                'class' => 'btn btn-primary',
                'data' => [
                    'bs-toggle' => 'modal',
                    'bs-target' => '#vote-submit',
                    'bs-dismiss' => 'modal'
                ]
            ])
        ).
        Html::tag('span',
            Html::Button(Yii::t('app', '取消'), ['class' => 'btn btn-danger']), ['class' => 'ms-2', 'data-bs-dismiss' => 'modal']
        ),
        ['class'=>'text-center']
    ),
    'footerOptions' => ['class' => 'justify-content-center']
]);

echo Html::tag('h5', '', ['class' => 'text-danger fw-bolder', 'id' => 'empty-candi-warning-text']);

Modal::end();
// ----------無勾選Modal end----------

ActiveForm::end();

// ----------圈選須知Modal begin----------
$information = $language == 'zh-tw' ? $voteInfo->information : $voteInfo->informationE;
echo $this->renderFile('@app/views/partials/_vote_information.php', [
    'id' => 'vote-information',
    'voteInfo' => $model->VoteInfo, 
    'information' => $information
]);
// ----------圈選須知Modal end----------

// CSS
$this->registerCss(<<<CSS
    .highlight-color { /* 選擇後高亮 */
        background-color: #ffc107 !important;
    }
    .highlight-color:hover {
        background-color: #ffc107 !important;
    }
    /* .table tbody tr:hover:not(.highlight-color) {
        background: #fff !important;
    } */
    .table-striped tbody tr:nth-of-type(odd) { /* 替換表格基數欄背景色 */
        background-color: rgb(0 123 255 / 10%);
    }
    .table th, .table td { /* checkbox 上下保留空間 */
        padding: 0.4rem;
    }
CSS
);

// body top
$noEachSelected = Yii::t('app', '未圈選，將視為廢票。');
$noSelected = Yii::t('app', '您未圈選{0}');
$noSelectedCondition = Yii::t('app', '您未圈選');
$selectedBelowLimit = Yii::t('app', '您目前所圈選者不足 {0} {1}，將被視為廢票！'); // 未達下限者
$selectedBelowLimitCondition = Yii::t('app', '您目前所圈選者不足'); // 未達下限者判斷語句
$selectedExceedsLimit = Yii::t('app', '您目前所圈選者已超過 {0} {1}，將被視為廢票！'); // 超過上限者
$selectedExceedsLimitCondition = Yii::t('app', '您目前所圈選者已超過'); // 超過上限者判斷語句
$i18nRemind = Yii::t('app', '提醒您');
$i18nNoSelected = '<p>'.Yii::t('app', '{0}<br>您未圈選{1}').'</p>';
$i18nSelectedExceedsLimit = '<p>'.Yii::t('app', '{0}<br>您目前所圈選者已超過 {1} {2}，將被視為廢票！').'</p>';
$i18nSelectedBelowLimit = '<p>'.Yii::t('app', '{0}<br>您目前所圈選者不足 {1} {2}，將被視為廢票！').'</p>'; // 未達下限者
$prev = Yii::t('app', '上一步：');
$next = Yii::t('app', '下一步：');

$questionsJson = Json::encode($questions);
$recordBallotUrl = Url::to(['vote/record-ballot', 'voteID' => $model->voteID]);
$recordStepUrl = Url::to(['vote/record-step', 'voteID' => $model->voteID]);
$csrfToken = Yii::$app->request->csrfToken;
$voteID = $model->voteID;
$this->registerJs(<<<JS
    $('#submitButton button').one('click', function() {
        $('#submitButton').toggle();
        $('#spinButton').toggle();
    });
    // add csrf prevent
    $.ajaxSetup({
        headers:{
            'x-csrf-token': "$csrfToken",
        },
    });
    if($recordStep == 0)
    {
        let isInformation = '$isInformation';
        // 進入頁面先顯示圈選須知
        if (isInformation) {
            $('#vote-information').modal('show');
        }
        // 紀錄最後一部流程，刻意讓流程由0轉1
        $.get('$recordStepUrl', {
            step: 1, 
            voteID: "$voteID"
        });
    }

    // 圈選確認，若有未圈選則跳出提醒，否則跳出投票最後確認
    $('#cast-ballot').click(function(e){
        let delimiter = '';
        let warning = "<h2>{0}</h2>".format("$i18nRemind");
        let remind = false;// 預設不在額外提示
        let emptySelected = [];// 未圈選
        let selectedBelowLimit = [];// 圈選數未達下限
        let selectedExceedsLimit = [];// 圈選數超過上限
        Object.entries($questionsJson).forEach(function(question) {
            let unit = $('.myGrid'+question[1].questionID).data('unit');
            // 未圈選
            if ($(".invalidMsgVotes"+question[0]).text().startsWith("$noSelectedCondition")) {
                warning += "$i18nNoSelected".format(
                    ('$language' == 'zh-tw') ? question[1].title : question[1].titleE,
                    $('.myGrid'+question[1].questionID).data('question-title'),
                );
                remind = true;
            }
            // 未達下限
            if ($(".invalidMsgVotes"+question[0]).text().startsWith("$selectedBelowLimitCondition")) {
                // console.log(question[1].leastNumBallots);
                warning += "$i18nSelectedBelowLimit".format(
                    ('$language' == 'zh-tw') ? question[1].title : question[1].titleE,
                    question[1].leastNumBallots,
                    ('$language' == 'zh-tw') ? unit : (question[1].leastNumBallots > 1 ? unit+"s" : unit),
                );
                remind = true;
            }
            // 超過上限
            if ($(".invalidMsgVotes"+question[0]).text().startsWith("$selectedExceedsLimitCondition")) {
                // console.log(question[1]);
                warning += "$i18nSelectedExceedsLimit".format(
                    ('$language' == 'zh-tw') ? question[1].title : question[1].titleE,
                    question[1].numBallots,
                    ('$language' == 'zh-tw') ? unit : (question[1].leastNumBallots > 1 ? unit+"s" : unit),
                );
                remind = true;
            }
        });

        if(remind)
        {
            $('#empty-candi-alert .modal-body #empty-candi-warning-text').html(warning);
            $('#empty-candi-alert').modal('show');
        }
        else {
            $('#vote-submit').modal('show');
        }
    })

    $('#pills-tabContent').addClass('tab-content');
    var activeQ = $('.tab-content div.active');
    $('#next-btn').text('$next '+activeQ.next().find('.question-title').text()+' →');
    $('#prev-btn').text('← $prev '+activeQ.prev().find('.question-title').text());
    // 當新的tab顯示觸發時
    $('summary[data-bs-toggle="pill"]').on('show.bs.tab', function (e) {
        let target = $(e.target);
        // 上下一題按鈕文字
        activeQ = $(target.attr('href'));
        $('#next-btn').text('$next '+activeQ.next().find('.question-title').text()+' →');
        $('#prev-btn').text('← $prev '+activeQ.prev().find('.question-title').text());
        target.css({'cursor' : ''});
        target.parent().removeClass('step-success');
        target.parent().addClass('step-active');
        if (target.hasClass('disabled')) {
            return false;
        }
        if (target.parent().hasClass('first')) {
            $('#prev').addClass('d-none');
        }
        else {
            $('#prev').removeClass('d-none');
        }
        if (target.parent().hasClass('end')) {
            $('#next').addClass('d-none');
            $('#cast-ballot').removeClass('d-none');
        }
        else {
            $('#next').removeClass('d-none');
            $('#cast-ballot').addClass('d-none');
        }
        // 紀錄最後一部流程
        // $.get('$recordStepUrl', {step: $(target.children().get(0)).text()});
        if(pageGeneration === false)
        {
            $.ajax({
                cache: false,
                method: "GET",
                url: "$recordStepUrl",
                data: {
                    step: $(target.children().get(0)).text(), 
                    voteID: "$voteID"
                }
            });
        }
    });

    // 當要顯示新tab時
    $('summary[data-bs-toggle="pill"]').on('hide.bs.tab', function (e) {
        let target = $(e.target);
        target.parent().addClass('step-success');
        if (target.hasClass('disabled')) {
            return false;
        }
    });

    $(".next-step").click(function (e) {
        let active = $('.steps li>summary.active');
        active.parent().next().find('.step-content').removeClass('disabled');
        active.parent().removeClass('step-active');
        nextTab(active);
        // https://github.com/KingSora/OverlayScrollbars/issues/100
        OverlayScrollbars(document.body).scroll({top : 0}, 400);
        // 紀錄最後一部流程
        // $.get('$recordStepUrl', {step: $($('.steps li>summary.active').children().get(0)).text()});
    });

    $(".prev-step").click(function (e) {
        let active = $('.steps li>summary.active');
        active.parent().removeClass('step-active');
        prevTab(active);
        // https://github.com/KingSora/OverlayScrollbars/issues/100
        OverlayScrollbars(document.body).scroll({top : 0}, 400);
        // 紀錄最後一部流程
        // $.get('$recordStepUrl', {step: $($('.steps li>summary.active').children().get(0)).text()});
    });

    // 下一題
    function nextTab(element) {
        $(element).parent().next().find('summary[data-bs-toggle="pill"]').click();
    }
    // 上一題
    function prevTab(element) {
        $(element).parent().prev().find('summary[data-bs-toggle="pill"]').click();
    }

    // Overlay Scrollbars for Bootstrap Modals
    // ex: https://jsfiddle.net/djwmyur4/1/
    $('.modal-body').each(function( index ) {
        var osInstance = OverlayScrollbars(this, {});
        osInstance.getElements().host.classList.add('os-host-flexbox');
    });
JS
);

$this->registerJs(<<<JS

    // 頁面生成時，不觸發頁數切換的 ajax
    var pageGeneration = false;

    // 處理字串方法
    // https://stackoverflow.com/a/2648463
    String.prototype.format = String.prototype.f = function() {
        var s = this,
            i = arguments.length;

        while (i--) {
            s = s.replace(new RegExp('\\\\{' + i + '\\\\}', 'gm'), arguments[i]);
        }
        return s;
    };

    // Array處理
    // https://stackoverflow.com/a/10456644
    Object.defineProperty(Array.prototype, 'chunk', {
        value: function(chunkSize) {
            var R = [];
            for (var i = 0; i < this.length; i += chunkSize)
            R.push(this.slice(i, i + chunkSize));
            return R;
        }
    });

    // 最少票數
    function leastVote(id) {
        var gridSelected = $('.myGrid'+id).yiiGridView('getSelectedRows');
        if(gridSelected.length < {$ballotlimit['leastNum']}) {
            return false;
        }
        return true;
    }

    // 最多票數
    function canVote(id) {
        var gridSelected = $('.myGrid'+id).yiiGridView('getSelectedRows');
        if(gridSelected.length > {$ballotlimit['mostNum']}) {
            return false;
        }
        return true;
    }

    // 顯示投票按鈕
    function showSubmitButton(id) {
        if({$invalidBallotJs})
        {
            return true;
        }
        var gridSelected = $('.myGrid'+id).yiiGridView('getSelectedRows');
        $("button[type='submit']").attr('disabled', (gridSelected.length == 0));
        if(gridSelected.length == 0)
        {
            $("button[type='submit']").attr('style', 'pointer-events: none;');
            $("#submitButton").attr('data-bs-toggle', "popover");
            $("#submitButton").attr('data-bs-content', "$noSelected");
        }
        else
        {
            $("#submitButton").popover('hide');
            $("button[type='submit']").attr('style', '');
            $("#submitButton").attr('data-bs-toggle', "");
            $("#submitButton").attr('data-bs-content', "");
        }
        $('[data-bs-toggle="popover"]').popover();
    }

    // 顯示圈選資訊
    function showCircled(id, init, candidateList, ballotlimit) {
        function Circled(candidateList) {
            this.people = [];
            this.text = "";
        }
        Circled.prototype.showText = function(array) {
            array.forEach(function(item, index) {
                var candidate = candidateList[item];
                if (index !== array.length - 1) {
                    candidate += '、';
                }
                this.people.push('<span class="badge ps-1 pe-0" style="font-size: 1.2rem">' + candidate + '</span>');
            }, this);
            //  ^-- Since the thisArg parameter (this) is provided to forEach()
            //      it is passed to callback each time it's invoked, for use as its this value.

            // 切割成每 100 人換一行
            this.people = this.people.chunk(100);
            if(this.people.length == 1)
            {
                this.text += this.people[0].join('');
            }
            else
            {
                this.people.forEach(function(item) {
                    this.text += item.join('');
                    this.text += "<br>";
                }, this);
            }
            // console.log(this.text);
            return this.text;
        };

        // 預設 - 清除所有外框樣式
        var candVotedFrame = $(".candidatesVoted"+id).parent().parent();
        candVotedFrame.removeClass('border-success');
        candVotedFrame.removeClass('border-danger');
        var candVotedModalFrame = $(".candidatesVotedModal"+id).parent().parent();
        candVotedModalFrame.removeClass('border-success');
        candVotedModalFrame.removeClass('border-danger');
        // 預設 - 隱藏所有區塊
        $(".invalidVotesTitle"+id).hide();
        $(".invalidMsgVotes"+id).hide();
        $(".invalidMsgEachVotes"+id).hide();
        $(".invalidVotesTitleModal"+id).hide();
        $(".invalidMsgVotesModal"+id).hide();
        // $(".numVotes"+id).hide();
        $(".numVotesModal"+id).hide();
        $(".invalidMsgVotes"+id).text("");
        $(".invalidMsgVotesModal"+id).text("");
        try {
            let gridSelected = $('.myGrid'+id).yiiGridView('getSelectedRows');
            let unit = $('.myGrid'+id).data('unit');
            let error = false;
            if(gridSelected.length >= 0)
            {
                // 有圈選
                if(gridSelected.length > ballotlimit.mostNum)
                {
                    error = true;
                    // 超過上限
                    let warning = "$selectedExceedsLimit".format(
                        ballotlimit.mostNum,
                        ('$language' == 'zh-tw') ? unit : (ballotlimit.mostNum > 1 ? unit+"s" : unit)
                    );
                    $(".invalidVotesTitle"+id).show();
                    $(".invalidMsgVotes"+id).show();
                    $(".invalidMsgEachVotes"+id).show();
                    $(".invalidVotesTitleModal"+id).show();
                    $(".invalidMsgVotesModal"+id).show();
                    $(".invalidMsgVotes"+id).text(warning);
                    $(".invalidMsgEachVotes"+id).text(warning);
                    $(".invalidMsgVotesModal"+id).text(warning);
                    candVotedFrame.addClass('border-danger');
                    candVotedModalFrame.addClass('border-danger');
                }
                else if(gridSelected.length < ballotlimit.leastNum)
                {
                    error = true;
                    // 低於下限
                    let warning = "$selectedBelowLimit".format(
                        ballotlimit.leastNum, 
                        ('$language' == 'zh-tw') ? unit : (ballotlimit.leastNum > 1 ? unit+"s" : unit)
                    );
                    $(".invalidVotesTitle"+id).show();
                    $(".invalidMsgVotes"+id).show();
                    $(".invalidMsgEachVotes"+id).show();
                    $(".invalidVotesTitleModal"+id).show();
                    $(".invalidMsgVotesModal"+id).show();
                    $(".invalidMsgVotes"+id).text(warning);
                    $(".invalidMsgEachVotes"+id).text(warning);
                    $(".invalidMsgVotesModal"+id).text(warning);
                    candVotedFrame.addClass('border-danger');
                    candVotedModalFrame.addClass('border-danger');
                }
                else
                {
                    // 符合圈選範圍
                    if (gridSelected.length == 0) {
                        let text = "$noSelected".format($('.myGrid'+id).data('question-title'));
                        $(".invalidMsgVotes"+id).show();
                        $(".invalidMsgVotes"+id).text(text);
                        $(".invalidMsgVotesModal"+id).show();
                        $(".invalidMsgVotesModal"+id).text(text);
                    }
                    candVotedFrame.addClass('border-success');
                    candVotedModalFrame.addClass('border-success');
                }
                // $(".numVotes"+id).show();
                $(".numVotesModal"+id).show();
                let text = $('.myGrid'+id).data('selected').format('<span>'+gridSelected.length+'</span>', unit);
                if(gridSelected.length > 1) {
                    text += '$language' == 'zh-tw' ? "" : "s";
                }
                text += '$language' == 'zh-tw' ? "" : ".";
                $(".numVotes"+id).html(text);
                $(".numVotesModal"+id).html(text);
            }
            else
            {
                // 未圈選
                let text = $('.myGrid'+id).data('selected').format('<span>'+gridSelected.length+'</span>', unit);
                $(".numVotes"+id).html(text);
                $(".invalidVotesTitle"+id).show();
                $(".invalidMsgVotes"+id).show();
                $(".invalidMsgEachVotes"+id).show();
                $(".invalidVotesTitleModal"+id).show();
                $(".invalidMsgVotesModal"+id).show();
                $(".invalidMsgEachVotes"+id).text("{$noEachSelected}");
                $(".invalidMsgVotes"+id).text("{$noSelected}");
                $(".invalidMsgVotesModal"+id).text("{$noSelected}");
                candVotedFrame.addClass('border-danger');
                candVotedModalFrame.addClass('border-danger');
            }
            let circled = new Circled(candidateList);
            let circledModal = new Circled(candidateList);
            $(".candidatesVoted"+id).html(circled.showText(gridSelected));
            $(".candidatesVotedModal"+id).html(circledModal.showText(gridSelected));
            spinOff();
        } catch(e) {
            // 初始化，視同未圈選
            $(".invalidVotesTitle"+id).show();
            $(".invalidMsgVotes"+id).show();
            $(".invalidMsgEachVotes"+id).show();
            $(".invalidVotesTitleModal"+id).show();
            $(".invalidMsgVotesModal"+id).show();
            $(".invalidMsgEachVotes"+id).text("{$noEachSelected}");
            $(".invalidMsgVotes"+id).text("{$noSelected}");
            $(".invalidMsgVotesModal"+id).text("{$noSelected}");
            candVotedFrame.addClass('border-danger');
            candVotedModalFrame.addClass('border-danger');
        }
    }

    // 檢查票數
    function chkClick(event, target_id, clientThis, id, candidateList, ballotlimit) {
        /* 非 checkbox 則相反 checkbox 圈選狀態，如果不排除 checkbox 圈選時會將圈選的狀態再次相反 */
        if (event.target.type != 'checkbox') {
            var element = document.querySelector(target_id);
            element.checked = !element.checked;
        }
        var element = document.querySelector(target_id);
        // 紀錄所有圈選項目
        $.get('$recordBallotUrl', {
            id: target_id.substr(2), 
            check: element.checked ? "add" : "rem", 
            voteID: "$voteID"
        });
        
        if(canVote(id) == false && !{$invalidBallotJs}) {
            alert("您只能投 {0} 票".format("{$ballotlimit['mostNum']}"));
            document.querySelector(target_id).checked = false;
            return false;
        }
        clientThis.parentElement.classList.toggle("highlight-color");// 上色狀態相反
        showSubmitButton(id);
        showCircled(id, false, candidateList, ballotlimit);
    }
JS
, $this::POS_BEGIN
);
