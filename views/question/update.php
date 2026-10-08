<?php

use yii\helpers\Url;
use yii\bootstrap5\Html;
use kartik\form\ActiveForm;

$title = '問題修改';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

echo $this->render('_all-party-alert');

echo \app\widgets\Summernote::widget(Yii::$app->params['summernote']);
echo \app\widgets\Alert::widget();

$question->scenario = 'update';

$form = ActiveForm::begin([
    'action' => ['/question/edit', 'voteID' => $question->voteID, 'questionID' => $question->questionID],
    'fieldConfig' => [
        'options' => ['class' => 'mb-0'],
        'labelOptions' => ['class' => 'mb-0 mt-2'],
    ],
]);
?>
<div class="alert alert-warning" role="alert">
    更換組別後，將會自動帶入組別投票規則；如需自訂請勾選下方【自訂規則】
</div>
<div class="row">
    <div class="col-lg">
        <?= $form->field($question, 'party')->dropDownList($parties, ['id' => 'party', 'required' => true, 'prompt' => '', 'onchange' => "getPartyRule(this)", 'class' => 'form-select']) ?>
    </div>
    <div class="col-lg">
        <?= $form->field($question, 'title')->textInput(['required' => true]) ?>
    </div>
    <div class="col-lg">
        <?= $form->field($question, 'titleE')->textInput(['required' => true]) ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?= $form->field($question, 'description')->textarea(['class' => 'form-control summernote']); ?>
    </div>
    <div class="col-md">
        <?= $form->field($question, 'descriptionE')->textarea(['class' => 'form-control summernote']); ?>
    </div>
</div>
<div class="row">
    <div class="col-lg">
        <?= $form->field($question, 'confirmTitle')->textInput()->hint('* 未圈選候選名單時，在確認步驟顯示，預設是顯示標題') ?>
    </div>
    <div class="col-lg">
        <?= $form->field($question, 'confirmTitleE')->textInput()->hint('* 未圈選候選名單時，在確認步驟顯示，預設是顯示英文標題') ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?= $form->field($question, 'ruleText')->textInput(['class' => 'form-control'])->hint('* 預設根據投票規則上下限決定文字'); ?>
    </div>
    <div class="col-md">
        <?= $form->field($question, 'ruleTextE')->textInput(['class' => 'form-control'])->hint('* 預設根據投票規則上下限決定文字'); ?>
    </div>
</div>

<hr class="mb-4">
<h4 class="mb-4" id="vr">投票規則</h4>
<div class="row">
    <div class="col-lg">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" name="vote-rule-check" id="vote-rule-check" onclick="voteRule(this)">
            <label class="custom-control-label" for="vote-rule-check">自訂規則</label>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg vote-rule">
        <?= $form->field($question, 'numBallots')->textInput(['required' => true, 'readonly' => true]) ?>
    </div>
    <div class="col-lg vote-rule">
        <?= $form->field($question, 'leastNumBallots')->textInput(['required' => true, 'readonly' => true]) ?>
    </div>
    <div class="col-lg vote-rule">
        <?= $form->field($question, 'maxElect')->textInput(['required' => true, 'readonly' => true]) ?>
    </div>
    <div class="col-lg vote-rule">
        <?= $form->field($question, 'numOfKeep')->textInput(['required' => true, 'readonly' => true]) ?>
    </div>
    <?php if($voteInfo->addiCondition == 'female'): ?>
    <div class="col-lg vote-rule">
        <?= $form->field($question, 'numFemaleKeep')->textInput(['required' => true, 'readonly' => true]) ?>
    </div>
    <?php endif; ?>
    <div class="col-lg vote-rule">
        <?= $form->field($question, 'population')->textInput(['required' => true, 'readonly' => true])->hint('* 紀錄應有投票人數（非啟用密碼數）') ?>
    </div>
</div>
<hr>
<?php
echo $form->field($question, 'voteID')->hiddenInput(['value' => $voteID])->label(false);
echo Html::tag(
    'div', 
    Html::submitButton('修改', ['class' => 'btn btn-primary', 'style' => 'width: auto']).
    Html::a('返回列表', ['question/index', 'voteID' => $voteID], ['class' => 'btn btn-secondary ms-2', 'style' => 'width: auto']),
    ['class' => 'row justify-content-center m-0']
);
ActiveForm::end();

$url = Url::to(['get-party-info', 'voteID' => $voteID]);

$this->registerJs(<<<JS
    $('.modal').appendTo('body');
JS
);

$this->registerJs(<<<JS
    function voteRule(element) {
        if (!$('#vote-rule-check').is(":checked")) {
            $('.vote-rule input').attr('readonly', true)
        }
        else {
            $('.vote-rule input').attr('readonly', false)
        }
    }
    function getPartyRule(element)
    {
        if (element.value && element.value != 'N') {
            $.ajax({
                type: "POST",
                url: '$url&party='+element.value,
                success: function(result) {
                    let party = JSON.parse(result);
                    $('#questions-numballots').val(Number(party.numBallots));
                    $('#questions-leastnumballots').val(Number(party.leastNumBallots));
                    $('#questions-maxelect').val(Number(party.maxElect));
                    $('#questions-numofkeep').val(Number(party.numOfKeep));
                    $('#questions-numfemalekeep').val(Number(party.numFemaleKeep));
                    $('#questions-population').val(0);
                }, 
                error: function(result) {
                    // console.log(result);
                }
            });
        }
        else if (element.value == 'N') {
            $('#all-party-alert').modal('show');
        }
        else {
            $('#questions-numballots').val(0);
            $('#questions-leastnumballots').val(0);
            $('#questions-maxelect').val(0);
            $('#questions-numofkeep').val(0);
            $('#questions-numfemalekeep').val(0);
            $('#questions-population').val(0);
        }
    }
JS
, $this::POS_BEGIN);