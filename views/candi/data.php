<?php
use yii\helpers\Html;
use yii\widgets\Pjax;
use app\components\Model;
use app\models\CandiData;
use app\models\FormVotes;
use kartik\grid\GridView;
use yii\bootstrap5\Dropdown;
use app\components\helper\ArrayHelper;
use rmrevin\yii\fontawesome\FAS;
use app\components\helper\FileLoader;

/** @var \app\models\FormCandiConfig $candiConfig */

$title = '候選人管理';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

$FileLoader = new FileLoader(Yii::getAlias('@filePool'));

$parties = $FormVotes->getVoteParty($voteInfo->voteID, Yii::$app->language, $voteInfo->partyOrNot);
$questions = $FormVotes->getVoteQuestion($voteInfo->voteID, $voteInfo->round, Yii::$app->language);
echo $this->render('_nav', [
    'voteID' => $voteID,
    'voteInfo' => $voteInfo,
    'questionID' => Yii::$app->request->get('questionID')
]);
echo \app\widgets\Alert::widget();

echo Html::tag('p',Html::tag('div',
    Html::a('新增候選名單 <b class="caret"></b>', '#', ['class' => 'btn btn-primary dropdown-toggle me-2', 'data-bs-toggle' => 'dropdown']).
    Dropdown::widget([
        'items' => [
            ['label' => Yii::$app->params['ct.candi.genModeAry']['manual'],    'url' => ['candi/manual', 'voteID' => $voteID, 'action' => 'create']],
            ['label' => Yii::$app->params['ct.candi.genModeAry']['csvfile'],   'url' => ['candi/csvfile',  'voteID' => $voteID]],
            ['label' => Yii::$app->params['ct.candi.genModeAry']['round'],     'url' => ['candi/round',  'voteID' => $voteID], 'visible' => $voteInfo->round > 1],
        ],
    ]).
    Html::a('刪除候選名單', '#collapseDelete', ['class' => 'btn btn-danger me-2','data-bs-toggle'=>'collapse','aria-expanded'=>'false','aria-controls'=>'collapseDelete']).
    Html::dropDownList(
        'question', 
        is_null(Yii::$app->request->get('questionID')) ? '' : Yii::$app->request->get('questionID'), 
        $questions, 
        [
            'prompt' => '請選擇問題', 
            'class' => 'form-select col-md-2 float-end'.($voteInfo->candiConfig != FormVotes::CANDI_CONFIG_BY_Q ? ' d-none' : ''), 
            'onchange' => 'getQuestionCandi(this.value)', 'style' => 'width: auto',
        ]
    ),
    ['class' => 'dropdown']
));
echo $this->render('_delete', ['model' => $searchModel, 'parties' => $parties, 'questions' => $questions, 'round' => $voteInfo->round]);
Pjax::begin(['id' => 'myGrid']);
echo GridView::widget([
    'id' => 'myGrid',
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'summary' => Yii::$app->tablePag->getSummaryText('myGrid'),
    'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
    'columns' => $candiConfig->getFieldSort([
        'autoId' => [
            'header' => '編號',
            'class' => 'yii\grid\SerialColumn',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 5rem'],
        ],
        'id' => [
            'label' => '編號',
            'attribute' => 'id',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 5rem'],
        ],
        'party' => [
            'label' => '分組',
            'attribute' => 'party',
            'value' => function ($model, $key, $index, $column) use ($parties)
            {
                return $parties[$model->party];
            },
            'filter' => $parties,
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'questionID' => [
            'label' => '問題',
            'attribute' => 'questionID',
            'value' => function ($model, $key, $index, $column) use ($questions)
            {
                return strip_tags($questions[$model->questionID]);
            },
            'filter' => ArrayHelper::addSpace($questions),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'jobLctn' => [
            'label' => '工作地點',
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
            'label' => '單位名稱',
            'attribute' => 'instName',
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->instNameE, $model->instName);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'title' => [
            'label' => '職稱',
            'attribute' => 'title',
            'value' => function ($model, $key, $index, $column)
            {
                if(trim($model->title) == '')
                    return '';
                return Model::i18n($model->titleE,$model->title);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'Name' => [
            'label' => Model::i18n($candiConfig->NameE, $candiConfig->Name),
            'attribute' => 'Name',
            'format' => 'raw',
            'value' => function ($model, $key, $index, $column)
            {
                return Html::a(
                    Model::i18n($model->NameE,$model->Name),
                    ['candi/manual','voteID'=>$model->voteID,'action'=>'update','id'=>$model->id]
                );
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'sex' => [
            'label' => '性別',
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
            'filter' => ArrayHelper::addSpace(Yii::$app->params['ct.candi.sexAry']),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'photo' => [
            'label' => '照片',
            'attribute' => 'photo',
            'content' => function ($model, $key, $index, $column)
            {
                return Html::img(['candi/view-photo', 'voteID' => $model->voteID, 'file' => $model->photo], ['width'=>'250', 'class'=>'img-fluid']);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColA' => [
            'header' => Model::i18n($candiConfig->otherColNameAE, $candiConfig->otherColNameA, false),
            'attribute' => 'otherColA',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColB' => [
            'header' => Model::i18n($candiConfig->otherColNameBE, $candiConfig->otherColNameB, false),
            'attribute' => 'otherColB',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColC' => [
            'header' => Model::i18n($candiConfig->otherColNameCE, $candiConfig->otherColNameC, false),
            'attribute' => 'otherColC',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColD' => [
            'header' => Model::i18n($candiConfig->otherColNameDE, $candiConfig->otherColNameD, false),
            'attribute' => 'otherColD',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColE' => [
            'header' => Model::i18n($candiConfig->otherColNameEE, $candiConfig->otherColNameE, false),
            'attribute' => 'otherColE',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColF' => [
            'header' => Model::i18n($candiConfig->otherColNameFE, $candiConfig->otherColNameF, false),
            'attribute' => 'otherColF',
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'isReachThreshold' => [
            'label' => '已達門檻',
            'attribute' => 'isReachThreshold',
            'value' => function ($model, $key, $index, $column)
            {
                return $model->isReachThreshold == CandiData::REACH_THRESHOLD ? FAS::icon('check', ['class' => 'text-success']) : '';
            },
            'format' => 'raw',
            'filter' => Yii::$app->params['ct.yesOrNoAry'],
            'headerOptions'  => ['class' => 'align-middle text-center', 'style' => 'white-space: nowrap;'],
            'contentOptions' => ['class' => 'align-middle text-center'],
            'visible' => $voteInfo->round > 1
        ],
        'genMode' => [
            'label' => '生成方式',
            'attribute' => 'genMode',
            'value' => function ($model, $key, $index, $column)
            {
                return Yii::$app->params['ct.candi.genModeAry'][$model->genMode];
            },
            'filter' => ArrayHelper::addSpace(Yii::$app->params['ct.candi.genModeAry']),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
    ],[
        'isReachThreshold' => $candiConfig::$FsAfter,
        'genMode' => $candiConfig::$FsAfter
    ]),
]);
Pjax::end();

$this->registerJs(<<<JS
    // 取得候選人
    function getQuestionCandi(questionID) {
        window.location.href = updateURLParameter(window.location.href, "questionID", questionID);
    }
JS
, $this::POS_END);
