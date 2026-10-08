<?php
use yii\helpers\Html;
use yii\helpers\Json;
use app\components\Model;
use yii\widgets\ActiveForm;
use app\components\helper\ArrayHelper;

/** @var \app\models\FormManageVote $model */

$title = $creator?'新增選票':'修改選票';
$this->title = $title;

$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航

$this->registerJsFile('@scriptFile/OverlayScrollbars/js/jquery.overlayScrollbars.js', ['depends'=> [\app\assets\AppAsset::class]]);
$this->registerCssFile('@scriptFile/OverlayScrollbars/css/OverlayScrollbars.css');

$dateFormat = function ($d) {
    if ($d === null || trim((string) $d) === '' || trim((string) $d) === '0000-00-00 00:00:00') {
        return '';
    }
    $timestamp = strtotime($d);
    if ($timestamp === false) {
        return '';
    }
    return date('Y-m-d\TH:i', $timestamp);
};


$isBallotUnmodified = static function ($ballot): bool {
    $upd = trim((string) ($ballot->updTime ?? ''));
    $ins = trim((string) ($ballot->insTime ?? ''));
    $modifier = trim((string) ($ballot->modifier ?? ''));
    return $modifier === ''
        || $upd === ''
        || $upd === '0000-00-00 00:00:00'
        || ($ins !== '' && $upd === $ins);
};
$ballotUnmodified = $isBallotUnmodified($voteBallot);


echo \app\widgets\Alert::widget();
echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);

$form = ActiveForm::begin([
    'enableAjaxValidation' => false, 
    'validateOnSubmit' => false,
    'fieldConfig' => [
        'options' => ['class' => 'mb-0'],
        'labelOptions' => ['class' => 'mb-0 mt-2'],
    ],
]);
?>
<div class="row mt-4">
    <div class="col-md">
        <?=$form
            ->field($voteBallot, 'party')
            ->dropDownList(
                Yii::$app->params["ct.division{$model->pattern}Ary"],
                [ 'readonly'=>true, 'style'=>'pointer-events: none;']
            ) ?>
    </div>
    <div class="col-md">
        <?=$form
            ->field($voteBallot, 'isAdminAdd')
            ->dropDownList(
                Yii::$app->params['ct.yesOrNoAry'],
                [ 'readonly'=>true, 'style'=>'pointer-events: none;']
            ) ?>
    </div>
    <div class="col-md">
        <?=$form->field($voteBallot, 'ip')->textInput(['readonly'=>true]) ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?=$form
            ->field($voteBallot, 'creator')
            ->dropDownList(
                $ballotChanger,
                ['readonly'=>true, 'style'=>'pointer-events: none;'
            ]) ?>
    </div>
    <div class="col-md">
        <?php if ($ballotUnmodified): ?>
            <?=$form->field($voteBallot, 'modifier')->textInput(['value' => '無', 'readonly' => true]) ?>
        <?php else: ?>
            <?=$form
                ->field($voteBallot, 'modifier')
                ->dropDownList(
                    $ballotChanger,
                    ['readonly'=>true, 'style'=>'pointer-events: none;'
                ]) ?>
        <?php endif; ?>
    </div>
    <div class="col-md">
        <?=$form
            ->field($voteBallot, 'insTime')
            ->textInput(['value'=>$dateFormat($voteBallot->insTime),'type'=>'datetime-local','readonly'=>true]) ?>
    </div>
    <div class="col-md">
        <?php if ($ballotUnmodified): ?>
            <?=$form->field($voteBallot, 'updTime')->textInput(['value' => '無', 'readonly' => true]) ?>
        <?php else: ?>
            <?=$form
                ->field($voteBallot, 'updTime')
                ->textInput(['value'=>$dateFormat($voteBallot->updTime),'type'=>'datetime-local','readonly'=>true]) ?>
        <?php endif; ?>
    </div>
</div>
<?php
$instEAry = array_merge(
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'instName','einstName'),
    ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'abb_instName','einstName')
);

echo Html::tag('div', Yii::$app->getI18n()->format(
    '圈選人數: 最多 {mostNum} 人，最少 {leastNum} 人，其餘皆為無效票！',
    [
        'mostNum' => $ballotlimit['mostNum'],
        'leastNum' => $ballotlimit['leastNum'],
    ],
    Yii::$app->language
), ['class'=>'text-center fw-bold text-danger mb-3']);

$candidateList = $model->dataProvider;
$candidateList->sort = false; // 關閉排序
echo \yii\grid\GridView::widget([
    'id' => 'myGrid',
    'dataProvider' => $candidateList,
    'summary' => '',//table-responsive
    'tableOptions' => ['class' => 'table table-striped table-bordered mb-1 table-sm'],
    'options' => ['class' => 'table-responsive'],
    'headerRowOptions' => ['class' => 'bg-primary text-white'],
    'rowOptions' => function ($model, $key, $index, $grid)
    {
        return [
            'onClick' => 'chkClick(event,"#c'.$model->id.'", this);',
            'data'=>[
                'key'     => $model->id,
                'cand-id' => $model->id,
            ],
        ];
    },
    'columns' => $model->getFieldSort([
        'checkbox' => [
            'class' => 'yii\grid\CheckboxColumn',
            'header' => '',
            'checkboxOptions' => function ($model, $key, $index, $column) use ($ballotSelected) {
                if(in_array($model->id,$ballotSelected))
                    $checked = true;
                else
                    $checked = false;
                return [
                    'id'=> 'c'.$model->id,
                    'value' => $model->id,
                    'checked' => $checked,
                    'style' => 'zoom:2.5; margin:0;',
                ];
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center','style' => 'line-height: 0; width: 4rem;'],
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
            'value' => function ($model2, $key, $index, $column) use ($model)
            {
                return Model::i18n(
                    Yii::$app->params["ct.division{$model->pattern}EAry"][$model2->party],
                    Yii::$app->params["ct.division{$model->pattern}Ary"][$model2->party]
                );
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
                    if(ArrayHelper::keyExists($model->instName,$instEAry))
                    {
                        return Model::i18n($instEAry[$model->instName],$model->instName);
                    }
                }
                return Model::i18n($model->instNameE,$model->instName);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
        ],
        'title' => [
            'label' => Yii::t('app', '職稱'),
            'attribute' => 'title',
            'value' => function ($model, $key, $index, $column)
            {
                if(trim($model->title) == '')
                    return '';
                return Model::i18n($model->titleE,$model->title);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
        ],
        'Name' => [
            'label' => Model::i18n($candiConfig->NameE, $candiConfig->Name),
            'attribute' => 'Name',
            'value' => function ($model, $key, $index, $column)
            {
                return Model::i18n($model->NameE,$model->Name);
            },
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
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
        'otherColA' => [
            'label' => Model::i18n($candiConfig->otherColNameAE, $candiConfig->otherColNameA),
            'attribute' => Model::i18n('otherColAE', 'otherColA'),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
        ],
        'otherColB' => [
            'label' => Model::i18n($candiConfig->otherColNameBE, $candiConfig->otherColNameB),
            'attribute' => Model::i18n('otherColBE', 'otherColB'),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
        ],
        'otherColC' => [
            'label' => Model::i18n($candiConfig->otherColNameCE, $candiConfig->otherColNameC),
            'attribute' => Model::i18n('otherColCE', 'otherColC'),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
        ],
        'otherColD' => [
            'label' => Model::i18n($candiConfig->otherColNameDE, $candiConfig->otherColNameD),
            'attribute' => Model::i18n('otherColDE', 'otherColD'),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center text-nowrap text-wrap'],
        ],
        'otherColE' => [
            'label' => Model::i18n($candiConfig->otherColNameEE, $candiConfig->otherColNameE),
            'attribute' => Model::i18n('otherColEE', 'otherColE'),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
        'otherColF' => [
            'label' => Model::i18n($candiConfig->otherColNameFE, $candiConfig->otherColNameF),
            'attribute' => Model::i18n('otherColFE', 'otherColF'),
            'headerOptions'  => ['class' => 'align-middle text-center'],
            'contentOptions' => ['class' => 'align-middle text-center'],
        ],
    ],[
        'checkbox'=> \app\models\FormCandiConfig::$FsBefore
    ]),
]);

echo Html::tag( 'div', '', ['class'=>'text-center mt-2', 'id'=>'numVotes']);
echo Html::tag( 'div', '', ['class'=>'text-center', 'id'=>'candidatesVoted']);

echo Html::tag( 'div',
    Html::submitButton( $creator?'新增選票':'修改選票', ['class' => 'btn btn-primary me-2', 'style' => 'width: auto'] ).
    (
        $creator?'':
        Html::a('刪除選票', ['ballot/delete', 'voteID'=>$voteBallot->voteID, 'ballotID'=>$voteBallot->ballotID], [
            'class' => 'btn btn-danger me-2', 'style' => 'width: auto',
            'data' => ['bs-confirm' => "確定要刪除此選票？\n* 此操作將無法還原！"],
        ])
    ).
    Html::a('返回列表', ['ballot/index', 'voteID'=>$model->voteID], ['class' => 'btn btn-secondary', 'style' => 'width: auto'])
    , ['class'=>'row justify-content-center mb-0 mt-3']
);
ActiveForm::end();

// CSS
$this->registerCss(<<<CSS
    .highlight-color { /* 選擇後高亮 */
        background-color: #ffc107 !important;
    }
    .highlight-color:hover {
        background-color: #ffdf7e !important;
    }
    .table tbody tr:hover:not(.highlight-color) { /* 未選擇時滑鼠高亮 */
        background: #ffeeba !important;
    }
    .table-striped tbody tr:nth-of-type(odd) { /* 替換表格基數欄背景色 */
        background-color: rgb(0 123 255 / 10%);
    }

    .table th, .table td { /* checkbox 上下保留空間 */
        padding: 0.4rem;
    }
    .table td { /* 表格項目底部不足高度不要補足，會造成 checkbox 下留白 */
        line-height: 0;
    }
CSS
);

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

$eVotingExceedsLimit = sprintf('您只能投 %s 票',$ballotlimit['mostNum']);
$candidateListJson = JSON::encode($getCandidate());
$invalidBallotJs = $model::$invalidBallot?'true':'false';// 是否可投無效票?
$nameSeparator = Model::i18n( ', ', '、');

$this->registerJs(<<<JS
    $("#myGrid").overlayScrollbars({});
    showSubmitButton(); // 顯示投票按鈕
    showCircled(); // 顯示圈選資訊

    // 已圈選的上色
    $("#myGrid tr[onclick]").each(function(index, value) {
        if($(value)[0].firstChild.firstChild.checked == true)
        {
            value.classList.toggle("highlight-color");
        }
    });
JS
, $this::POS_LOAD
);

// body top
$this->registerJs(<<<JS
    candidateList = {$candidateListJson};

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
    function leastVote() {
        var gridSelected = $('#myGrid').yiiGridView('getSelectedRows');
        console.log(gridSelected.length);
        if(gridSelected.length < {$ballotlimit['leastNum']}) {
            return false;
        }
        return true;
    }

    // 最多票數
    function canVote() {
        var gridSelected = $('#myGrid').yiiGridView('getSelectedRows');
        if(gridSelected.length > {$ballotlimit['mostNum']}) {
            return false;
        }
        return true;
    }

    // 顯示投票按鈕
    function showSubmitButton() {
        if({$invalidBallotJs})
        {
            return true;
        }
        var gridSelected = $('#myGrid').yiiGridView('getSelectedRows');
        $("button[type='submit']").attr('disabled', (gridSelected.length == 0));
        if(gridSelected.length == 0)
        {
            $("button[type='submit']").attr('style', 'pointer-events: none;');
            $("#submitButton").attr('data-bs-toggle', "popover");
            $("#submitButton").attr('data-content', "未圈選候選人");
        }
        else
        {
            $("#submitButton").popover('hide');
            $("button[type='submit']").attr('style', '');
            $("#submitButton").attr('data-bs-toggle', "");
            $("#submitButton").attr('data-content', "");
        }
        $('[data-bs-toggle="popover"]').popover();
    }

    // 顯示圈選資訊
    function showCircled() {
        function Circled() {
            this.people = [];
            this.text = "";
        }
        Circled.prototype.showText = function(array) {
            array.forEach(function(item) {
                this.people.push(candidateList[item]);
            }, this);
            //  ^-- Since the thisArg parameter (this) is provided to forEach()
            //      it is passed to callback each time it's invoked, for use as its this value.

            // 切割成每 10 人換一行
            this.people = this.people.chunk(10);
            if(this.people.length == 1)
            {
                this.text += this.people[0].join('{$nameSeparator}');
            }
            else
            {
                this.people.forEach(function(item) {
                    this.text += item.join('{$nameSeparator}');
                    this.text += "<br>";
                }, this);
            }
            return this.text;
        };
        let circled = new Circled();
        let gridSelected = $('#myGrid').yiiGridView('getSelectedRows');
        $("#numVotes").text("{0} 位已被圈選".format(gridSelected.length));
        $("#candidatesVoted").html(circled.showText(gridSelected));
    }

    // 檢查票數
    function chkClick( event, target_id, clientThis) {
        /* 非 checkbox 則相反 checkbox 圈選狀態，如果不排除 checkbox 圈選時會將圈選的狀態再次相反 */
        if (event.target.type != 'checkbox') {
            var element = document.querySelector(target_id);
            element.checked = !element.checked;
        }
        if(canVote() == false && !{$invalidBallotJs}) {
            appDialog.warn("您只能投 {0} 票".format("{$ballotlimit['mostNum']}"));
            document.querySelector(target_id).checked = false;
            return false;
        }
        clientThis.classList.toggle("highlight-color");// 上色狀態相反
        showSubmitButton();
        showCircled();
    }
JS
, $this::POS_BEGIN
);
