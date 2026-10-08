<?php

use app\models\CandiData;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\bootstrap5\ActiveForm;

$title = Yii::$app->params['ct.candi.genModeAry']['round'].'候選人';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

echo \app\widgets\Alert::widget();

// 取得投票組別
$parties = $FormVotes->getVoteParty($voteInfo->voteID, Yii::$app->language, $voteInfo->partyOrNot);

$form = ActiveForm::begin([
    'id' => 'round-import-form',
    'enableClientValidation' => false,
    'fieldConfig' => [
        'options' => ['class' => 'mb-0'],
        'labelOptions' => ['class' => 'mb-0 mt-2'],
    ],
]);

$round = $voteInfo->round - 1;
?>
<h4 id="bs">導入配置</h4>
<div class="row mb-4">
    <div class="col-md">
        <?=$form
            ->field($model, 'party')
            ->label('候選人組別')
            ->dropDownList($parties, ['prompt' => '請選擇', 'onchange' => 'getQuestions(this.value)']) ?>
    </div>
    <div class="col-md">
        <?=$form
            ->field($model, 'questionID')
            ->label('候選人問題')
            ->dropDownList(['' => '先選擇組別'], ['onchange' => 'getCandiData(this.value)'])
            ->hint("* 僅會出現輪次{$round}的問題") ?>
    </div>
    <div class="col-md">
        <?=$form
            ->field($model, 'importParty')
            ->dropDownList($parties, ['prompt' => '請選擇', 'onchange' => 'getImportQuestions(this.value)', 'required' => true])?>
    </div>
    <div class="col-md">
        <?=$form
            ->field($model, 'importQuestionID')
            ->dropDownList(['' => '先選擇導入組別'], ['required' => true])?>
    </div>
</div>
<h4 id="bs">候選人</h4>
<div class="mb-4">
    <?php Pjax::begin(['id' => 'candi']); ?>
    <?php if(!empty($candi)): ?>
    <div class="alert alert-info" role="alert">
        <i class="fas fa-info-circle"></i> 勾選的候選人為已達門檻，其餘直接導入。
        <font class="fw-bold" color='red'>紅字括號是輪次<?=$round?>的得票排名，沒有排名表示前一輪已達門檻。</font>
    </div>
    <div class="col-md" id="candi-selected">
        <?=$form
            ->field($model, 'id[]')
            ->inline(true)
            ->checkboxList($candi, [
                'item' => function ($index, $label, $name, $checked, $value) {
                    $checked = $label['isReachThreshold'] == CandiData::REACH_THRESHOLD;
                    $rank = $label['rank'] == 0 ? "(-)" : "({$label['rank']})";
                    return Html::tag('div', 
                        Html::checkbox($name, $checked, ['id' => "i{$index}", 'value' => $value, 'class' => 'custom-control-input']).
                        Html::label(
                            "{$label['Name']} ".Html::tag('span', $rank, ['class' => 'text-danger'])
                            , "i{$index}", ['class' => 'custom-control-label']
                        ), 
                    ['class' => 'custom-control custom-checkbox custom-control-inline col-md-2 col-sm-4 col-6']);
                }
            ])
            ->label(false) ?>
    </div>
    <?php else: ?>
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle"></i> 請先選擇候選人問題
    </div>
    <?php endif; ?>
    <?php Pjax::end(); ?>
</div>
<hr>
<?php
echo Html::tag(
    'div',
    Html::submitButton('導入候選人', ['class' => 'btn btn-primary me-2', 'style' => 'width: auto']).
    Html::a('返回列表', ['candi/data', 'voteID' => $voteInfo->voteID], ['class' => 'btn btn-secondary', 'style' => 'width: auto'])
    , ['class'=>'row justify-content-center m-0']
);
ActiveForm::end();

$questionsUrl = Url::to(['question/get-party-questions', 'voteID' => $voteInfo->voteID, 'round' => $round]);
$importQuestionsUrl = Url::to(['question/get-party-questions', 'voteID' => $voteInfo->voteID, 'round' => $voteInfo->round]);
$this->registerJs(<<<JS
    // 取得候選人問題
    function getQuestions(party) {
        $.ajax({
            type: "POST",
            url: '$questionsUrl',
            data: {party: party},
            success: function(result) {
                let questions = JSON.parse(result);
                $("#formcandidata-questionid").empty().append(new Option('請選擇', ''));
                questions.forEach(function(element){
                    $("#formcandidata-questionid").append(new Option(element.title, element.questionID));
                })
                $('#formcandidata-importparty').val(party).trigger('change');
            }, 
            error: function(result) {
                console.log(result);
            }
        });
    }
    // 取得導入問題
    function getImportQuestions(party) {
        $.ajax({
            type: "POST",
            url: '$importQuestionsUrl',
            data: {party: party},
            success: function(result) {
                let questions = JSON.parse(result);
                $("#formcandidata-importquestionid").empty().append(new Option('請選擇', ''));
                questions.forEach(function(element){
                    $("#formcandidata-importquestionid").append(new Option(element.title, element.questionID));
                })
            }, 
            error: function(result) {
                console.log(result);
            }
        });
    }
    // 取得候選人
    function getCandiData(questionID) {
        $.pjax.reload({ container: '#candi', url: updateURLParameter(window.location.href, "questionID", questionID) });
    }

    // 在表單提交之前，將表單數據存儲到localStorage
    $('#round-import-form').on('beforeSubmit', function (e) {
        var form = $(this).serialize();
        localStorage.setItem('{$voteInfo->voteID}-round-import', form);
        return true;
    });

    // 當頁面加載時，檢查localStorage中是否有數據
    $(document).ready(function() {
        var storedForm = localStorage.getItem('{$voteInfo->voteID}-round-import');
        if (storedForm) {
            // 將存儲的數據填充到表單中
            var formArray = storedForm.split('&');
            for (var i = 0; i < formArray.length; i++) {
                if (/questionID|importQuestionID|id/.test(formArray[i])) {
                    continue;
                }
                var formField = formArray[i].split('=');
                // 利用regex判斷formField是否包含questionID，有的話略過
                $("[name='" + decodeURI(formField[0]) + "']").val(decodeURIComponent(formField[1])).trigger('change');
            }
        }
    });
JS
, $this::POS_END);
