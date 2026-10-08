<?php
use yii\helpers\Html;
use yii\helpers\Json;
use app\components\Model;
use app\models\CandiData;
use app\models\Questions;
use yii\widgets\ActiveForm;
use app\components\helper\ArrayHelper;
use app\components\helper\FileLoader;

/** @var \app\models\FormManageVote $model */

$title = $creator ? '新增選票' : '修改選票';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $model->voteInfo;

$FileLoader = new FileLoader(Yii::getAlias('@filePool'));

$dateFormat = function ($d) {
    if ($d === null || trim((string) $d) === '' || trim((string) $d) === '0000-00-00 00:00:00') {
        return '';
    }
    // 檢查日期是否有效
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
                $parties,
                [ 'class' => 'form-select', 'readonly'=>true, 'style'=>'pointer-events: none;']
            ) ?>
    </div>
    <div class="col-md">
        <?=$form
            ->field($voteBallot, 'isAdminAdd')
            ->dropDownList(
                Yii::$app->params['ct.yesOrNoAry'],
                [ 'class' => 'form-select', 'readonly'=>true, 'style'=>'pointer-events: none;']
            ) ?>
    </div>
    <div class="col-md">
        <?=$form->field($voteBallot, 'ip')->textInput(['readonly'=>true]) ?>
    </div>
</div>
<div class="row mb-2">
    <div class="col-md">
        <?=$form
            ->field($voteBallot, 'creator')
            ->dropDownList(
                $ballotChanger,
                ['class' => 'form-select', 'readonly'=>true, 'style'=>'pointer-events: none;'
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
                    ['class' => 'form-select', 'readonly'=>true, 'style'=>'pointer-events: none;'
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

// 根據問題分類候選人
foreach ($questions as $id => $question) {
    echo Html::beginTag('div', ['class' => 'card mb-4 text-center']);
    echo Html::tag('div', $question['title'], ['class' => 'card-header fw-bolder', 'style' => 'font-size:2.5em']);
    echo Html::beginTag('div', ['class' => 'card-body']);
    // 投票限制
    $ballotlimit = ['mostNum' => $question['numBallots'], 'leastNum' => $question['leastNumBallots']];
    // 判定問題組別是否為全部分組
    if (is_null($ballotlimit) && array_key_exists(Questions::ALL_PARTY_CODE, $parties)) {
        $ballotlimit = $model->getQuestionBallotLimit($model->voteID, Questions::ALL_PARTY_CODE, $id);
    }

    // 圈選人數限制
    $candidateList = $model->getDataProvider($model->voteID, $model->party, $id)[0];
    $lowerLimitUnit = Model::i18n($candiConfig->NameUnitE.($question['leastNumBallots'] > 1 ? 's' : ''), $candiConfig->NameUnit);
    $upperLimitUnit = Model::i18n($candiConfig->NameUnitE.($question['numBallots'] > 1 ? 's' : ''), $candiConfig->NameUnit);
    $limitText = Questions::getQuestionLimitText('本次投票', '。', $candidateList->totalCount, $question, $lowerLimitUnit, $upperLimitUnit);
    echo Html::tag('div', $limitText, ['class'=>'text-center fw-bold text-danger mb-3']);
    
    $candidateList->sort = false; // 關閉排序
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
    $candidateListJson = JSON::encode($getCandidate());

    echo \yii\grid\GridView::widget([
        'id' => 'myGrid'.$id,
        'dataProvider' => $candidateList,
        'summary' => '',//table-responsive
        'tableOptions' => ['class'=> 'table table-striped table-bordered mb-1'],
        'options' => ['class'=> 'table-responsive'],
        'headerRowOptions' => ['class'=> 'bg-primary text-white'],
        'rowOptions' => function ($model, $key, $index, $grid) use ($id, $candidateListJson)
        {
            return [
                'onClick' => 'chkClick(event,"#c'.$model->id.'", this, '.$id.', '.$candidateListJson.');',
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
                'content' => function ($model, $key, $index, $column) use ($ballotSelected) {
                    if ($model->isReachThreshold == CandiData::REACH_THRESHOLD) {
                        return Html::tag('span', '已達門檻', ['class' => 'fw-bold']);
                    }
                    else {
                        $checked = (in_array($model->id, $ballotSelected)) ? true : false;
                        return Html::input('checkbox', 'selection[]', $model->id, [
                            'id'=> 'c'.$model->id, 
                            'value' => $model->id, 
                            'checked' => $checked,
                            'style' => 'zoom:2.5; margin:0;'
                        ]);
                    }
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center','style' => 'line-height: 0;'],
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
                    return Model::i18n(
                        $parties[$model2->party],
                        $parties[$model2->party]
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
                'contentOptions' => ['class' => 'align-middle text-center'],
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
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'Name' => [
                'label' => Model::i18n($candiConfig->NameE, $candiConfig->Name),
                'attribute' => 'Name',
                'value' => function ($model, $key, $index, $column)
                {
                    return Model::i18n($model->NameE,$model->Name);
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
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
                    return Html::img(['candi/view-photo', 'voteID' => $model->voteID, 'file' => $model->photo], ['width'=>'250', 'class'=>'img-fluid']);
                },
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'otherColA' => [
                'label' => Model::i18n($candiConfig->otherColNameAE, $candiConfig->otherColNameA),
                'attribute' => Model::i18n('otherColAE', 'otherColA'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'otherColB' => [
                'label' => Model::i18n($candiConfig->otherColNameBE, $candiConfig->otherColNameB),
                'attribute' => Model::i18n('otherColBE', 'otherColB'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'otherColC' => [
                'label' => Model::i18n($candiConfig->otherColNameCE, $candiConfig->otherColNameC),
                'attribute' => Model::i18n('otherColCE', 'otherColC'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
            ],
            'otherColD' => [
                'label' => Model::i18n($candiConfig->otherColNameDE, $candiConfig->otherColNameD),
                'attribute' => Model::i18n('otherColDE', 'otherColD'),
                'headerOptions'  => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center'],
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
            'checkbox' => \app\models\FormCandiConfig::$FsBefore
        ]),
    ]);

    $eVotingExceedsLimit = sprintf('您只能投 %s 票',$ballotlimit['mostNum']);

    $invalidBallotJs = $model::$invalidBallot?'true':'false';// 是否可投無效票?
    $nameSeparator = Model::i18n( ', ', '、');

    $this->registerJs(<<<EOT
        $("#myGrid$id").overlayScrollbars({});
        showSubmitButton($id); // 顯示投票按鈕
        showCircled($id, {$candidateListJson}); // 顯示圈選資訊

        // 已圈選的上色（僅傳統投票）
        $("#myGrid$id tr[onclick]").each(function(index, value) {
            if($(value)[0].firstChild.firstChild.checked == true)
            {
                value.classList.toggle("highlight-color");
            }
        });
EOT
    , $this::POS_LOAD
    );
    
    echo Html::endTag('div'); // card-body
    echo Html::beginTag('div', ['class' => 'card-footer']);
    echo Html::tag('div', '', ['class'=>'text-center mt-2', 'id'=>'numVotes'.$id]);
    echo Html::tag('div', '', ['class'=>'text-center', 'id'=>'candidatesVoted'.$id]);
    echo Html::endTag('div'); // card-footer
    echo Html::endTag('div'); // card
}

echo Html::tag( 'div',
    Html::submitButton( $creator ? '新增選票' : '修改選票', ['class' => 'btn btn-primary me-2', 'style' => 'width: auto'] ).
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
        background-color: #ffc107 !important;
    }
    .table tbody tr:hover:not(.highlight-color) { /* 未選擇時滑鼠高亮 */
        background: #fff !important;
    }
    .table-striped tbody tr:nth-of-type(odd) { /* 替換表格基數欄背景色 */
        background-color: rgb(0 123 255 / 10%);
    }

    .table th, .table td { /* checkbox 上下保留空間 */
        padding: 0.4rem;
    }
CSS
);

// body top
$this->registerJs(<<<JS

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
        var gridSelected = $('#myGrid').yiiGridView('getSelectedRows');
        console.log(gridSelected.length);
        if(gridSelected.length < {$ballotlimit['leastNum']}) {
            return false;
        }
        return true;
    }

    // 最多票數
    function canVote(id) {
        var gridSelected = $('#myGrid'+id).yiiGridView('getSelectedRows');
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
        try {
            var gridSelected = $('#myGrid'+id).yiiGridView('getSelectedRows');
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
        } catch (e) {
            // 如果 getSelectedRows 失敗，使用手動檢查
            var checkedBoxes = $('#myGrid'+id+' input[type="checkbox"]:checked');
            $("button[type='submit']").attr('disabled', (checkedBoxes.length == 0));
            if(checkedBoxes.length == 0)
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
        }
        $('[data-bs-toggle="popover"]').popover();
    }

    // 顯示圈選資訊
    function showCircled(id, candidateList) {
        function Circled(candidateList) {
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
        try {
            let circled = new Circled(candidateList);
            let gridSelected = $('#myGrid'+id).yiiGridView('getSelectedRows');
            $("#numVotes"+id).text("{0} 位已被圈選".format(gridSelected.length));
            $("#candidatesVoted"+id).html(circled.showText(gridSelected));
        } catch (e) {
            // 如果 getSelectedRows 失敗，使用手動檢查
            var checkedBoxes = $('#myGrid'+id+' input[type="checkbox"]:checked');
            var selectedIds = [];
            checkedBoxes.each(function() {
                selectedIds.push($(this).val());
            });
            let circled = new Circled(candidateList);
            $("#numVotes"+id).text("{0} 位已被圈選".format(selectedIds.length));
            $("#candidatesVoted"+id).html(circled.showText(selectedIds));
        }
    }

    // 檢查票數
    function chkClick( event, target_id, clientThis, id, candidateList) {
        /* 非 checkbox 則相反 checkbox 圈選狀態，如果不排除 checkbox 圈選時會將圈選的狀態再次相反 */
        if (event.target.type != 'checkbox') {
            var element = document.querySelector(target_id);
            element.checked = !element.checked;
        }
        if(canVote(id) == false && !{$invalidBallotJs}) {
            appDialog.warn("您只能投 {0} 票".format("{$ballotlimit['mostNum']}"));
            document.querySelector(target_id).checked = false;
            return false;
        }
        clientThis.classList.toggle("highlight-color");// 上色狀態相反
        showSubmitButton(id);
        showCircled(id, candidateList);
    }
JS
, $this::POS_BEGIN
);