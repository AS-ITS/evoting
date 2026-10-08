<?php

use app\components\helper\ArrayHelper;
use yii\helpers\Url;
use yii\bootstrap5\Html;
use kartik\form\ActiveForm;
use kartik\select2\Select2;

$title = '問題新增';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

echo $this->render('_all-party-alert');

echo \app\widgets\Summernote::widget(Yii::$app->params['summernote']);
echo \app\widgets\Alert::widget();

$questions->scenario = 'create';

$form = ActiveForm::begin([
    'action' => ['/question/store', 'voteID' => $voteID],
    'fieldConfig' => [
        'options' => ['class' => 'mb-0'],
        'labelOptions' => ['class' => 'mb-0 mt-2'],
    ],
]);
?>
<div class="row align-items-center mb-2">
    <div class="col-sm">
        <?php if ($voteInfo->partyOrNot): ?>
        <?= 
        $form->field($questions, 'party', ['options' => ['class' => 'party-select']])
            ->widget(Select2::classname(), [
                'data' => $parties,
                'options' => [
                    'placeholder' => '請選擇組別',
                    'multiple' => true,
                    'required' => true
                ],
                'showToggleAll' => false,
                'pluginOptions' => [
                    'allowClear' => true
                ],
                'pluginEvents' => [
                    // 如果有選到全部分組，刪除其他已選項目
                    "select2:select" => "function() {
                        if($(this).val().includes('N')) {
                            $(this).val(['N']).trigger('change');
                            $('#all-party-alert').modal('show');
                            $('.relate-party').show();
                        }
                        else {
                            $('.relate-party').hide();
                        }
                    }",
                    "select2:unselect" => "function() { 
                        $('.relate-party').hide();
                    }"
                ]
            ])->hint('* 可以複選，點擊x可以刪除選項')
        ?>
        <?php else: ?>
        <?= $form->field($questions, 'party[]')->hiddenInput(['value' => 'def'])->label(false) ?>
        <?php endif; ?>
    </div>
</div>
<div class="row">
    <div class="col-lg">
        <?= $form->field($questions, 'title')->textInput(['required' => true]) ?>
    </div>
    <div class="col-lg">
        <?= $form->field($questions, 'titleE')->textInput(['required' => true]) ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?= $form->field($questions, 'description')->textarea(['class' => 'form-control summernote']); ?>
    </div>
    <div class="col-md">
        <?= $form->field($questions, 'descriptionE')->textarea(['class' => 'form-control summernote']); ?>
    </div>
</div>
<div class="row">
    <div class="col-lg">
        <?= $form->field($questions, 'confirmTitle')->textInput()->hint('* 未圈選候選名單時，在確認步驟顯示，預設是顯示標題') ?>
    </div>
    <div class="col-lg">
        <?= $form->field($questions, 'confirmTitleE')->textInput()->hint('* 未圈選候選名單時，在確認步驟顯示，預設是顯示英文標題') ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?= $form->field($questions, 'ruleText')->textInput(['class' => 'form-control'])->hint('* 預設根據投票規則上下限決定文字'); ?>
    </div>
    <div class="col-md">
        <?= $form->field($questions, 'ruleTextE')->textInput(['class' => 'form-control'])->hint('* 預設根據投票規則上下限決定文字'); ?>
    </div>
</div>

<hr class="mb-4">
<h4 class="mb-4" id="vr">投票規則</h4>
<div class="alert alert-warning" role="alert">
    下方右側選單選擇組別後，將會自動帶入組別投票規則；如需自訂請勾選下方左側【自訂規則】
</div>
<div class="row align-items-center mb-2">
    <div class="col-auto">
        <div class="custom-control custom-checkbox me-sm-2">
            <input type="checkbox" class="custom-control-input" name="vote-rule-check" id="vote-rule-check" onclick="voteRule(this)">
            <label class="custom-control-label" for="vote-rule-check">自訂規則</label>
        </div>
    </div>
    <div class="col-sm">
        <?=
            Select2::widget([
                'name' => 'party-rule-select',
                'data' => ArrayHelper::forget($parties, 'N'),
                'size' => Select2::SMALL,
                'options' => [
                    'id' => 'party-rule-select',
                    'placeholder' => '選擇組別後自動帶入該組別投票規則',
                    'onchange' => "getPartyRule(this)"
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
        ?>
    </div>
</div>
<div class="row">
    <div class="col-lg vote-rule">
        <?= $form->field($questions, 'numBallots')->textInput(['required' => true, 'readonly' => true]) ?>
    </div>
    <div class="col-lg vote-rule">
        <?= $form->field($questions, 'leastNumBallots')->textInput(['required' => true, 'readonly' => true]) ?>
    </div>
    <div class="col-lg vote-rule">
        <?= $form->field($questions, 'maxElect')->textInput(['required' => true, 'readonly' => true]) ?>
    </div>
    <div class="col-lg vote-rule">
        <?= $form->field($questions, 'numOfKeep')->textInput(['required' => true, 'readonly' => true]) ?>
    </div>
    <?php if($voteInfo->addiCondition == 'female'): ?>
    <div class="col-lg vote-rule">
        <?= $form->field($questions, 'numFemaleKeep')->textInput(['required' => true, 'readonly' => true]) ?>
    </div>
    <?php endif; ?>
    <div class="col-lg vote-rule">
        <?= $form->field($questions, 'population')->textInput(['required' => true, 'readonly' => true])->hint('* 紀錄應有投票人數（非啟用密碼數）') ?>
    </div>
</div>
<hr>
<?php
echo $form->field($questions, 'voteID')->hiddenInput(['value' => $voteID])->label(false);
echo $form->field($questions, 'round')->hiddenInput(['value' => $voteInfo->round])->label(false);
echo Html::tag(
    'div', 
    Html::submitButton('新增', ['class' => 'btn btn-primary', 'style' => 'width: auto']).
    Html::a('返回列表', ['question/index', 'voteID' => $voteID], ['class' => 'btn btn-secondary ms-2', 'style' => 'width: auto']),
    ['class' => 'row justify-content-center m-0']
);
ActiveForm::end();

$url = Url::to(['get-party-info', 'voteID' => $voteID]);

$this->registerJs(<<<JS
    function voteRule(element) {
        if (!$('#vote-rule-check').is(":checked")) {
            $('.vote-rule input').attr('readonly', true);
        }
        else {
            $('.vote-rule input').attr('readonly', false);
        }
    }
    function noParty(element) {
        if (!$('#no-party-check').is(":checked")) {
            $('.party-select').parent().removeClass('d-none');
        }
        else {
            $('#all-party-alert').modal('show');
            $('.party-select').parent().addClass('d-none');
        }
    }
    function getPartyRule(element)
    {
        if (element.value) {
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

$this->registerJs(<<<JS
    $('.modal').appendTo('body');
JS
);