<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$title = '匯入選票';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

echo \app\widgets\Alert::widget();

// 注意事項
echo Html::tag('div', 
    Html::ol([
        '用於投票拆成兩場、須合併計票時：由 A 場匯入 B 場後一併開計票。',
        '兩個場次必須共用密碼，且問題及候選數量必須相同，否則無法匯入。',
        '兩個場次的候選人orderNum必須一致，選票圈選的候選人靠orderNum對應。',
        '如果問題的順序不同，請手動選擇問題對應；反之直接點選「按順序自動載入」的按鈕。',
        '匯入會保留來源選票的「代為輸入」標記。',
    ], ['class' => 'mb-0']),
    ['class' => 'alert alert-info', 'role' => 'alert']
);

// 取得投票組別
$parties = $voteInfo->getVoteParty($voteInfo->voteID, Yii::$app->language, $voteInfo->partyOrNot);

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
<h4>匯入配置</h4>
<div class="row mb-4">
    <div class="col-md">
        <?=$form->field($model, 'voteID')
            ->label('選擇匯入的投票場次')
            ->dropDownList($bindVotes, ['prompt' => '請選擇', 'onchange' => 'getImportQuestions(this.value)']) ?>
    </div>
</div>
<h4>問題對應</h4><button id="auto-questions" class="btn btn-sm btn-success">按順序自動載入</button>
<div class="row mb-4">
    <?php foreach ($questions as $questionID => $question): ?>
    <div class="col-md-3">
        <?=$form->field($model, 'questionID[]')
            ->label($question)
            ->dropDownList([], ['id' => "question-{$questionID}", 'class' => 'questionID form-control', 'prompt' => '請選擇']) ?>
    </div>
    <?php endforeach; ?>
</div>
<hr>
<?php
echo Html::tag(
    'div',
    Html::submitButton('匯入選票', ['class' => 'btn btn-primary me-2', 'style' => 'width: auto']).
    Html::a('返回列表', ['ballot/index', 'voteID' => $voteInfo->voteID], ['class' => 'btn btn-secondary', 'style' => 'width: auto'])
    , ['class'=>'row justify-content-center m-0']
);
ActiveForm::end();

$importQuestionsUrl = Url::to(['ballot/get-import-questions', 'voteID' => $voteInfo->voteID, 'round' => $voteInfo->round]);
$this->registerJs(<<<JS
    // 取得匯入場次的問題
    function getImportQuestions(voteID) {
        $.ajax({
            type: "POST",
            url: '$importQuestionsUrl',
            data: {voteID: voteID},
            success: function(result) {
                let questions = JSON.parse(result);
                console.log(Object.entries(questions));
                
                $(".questionID").empty().append(new Option('請選擇', ''));
                questions.forEach(function(element) {
                    $(".questionID").append(new Option(element.title, element.questionID));
                })
            }, 
            error: function(result) {
                console.log(result);
            }
        });
    }
    // 如果FormBallots[questionID]有重複選擇的option，彈出提醒
    $('#round-import-form').on('beforeSubmit', function(e) {
        e.preventDefault();
        let questionID = [];
        $('.questionID').each(function() {
            questionID.push($(this).val());
        });

        // 檢查是否有未選擇的問題
        if (questionID.includes('')) {
            appDialog.warn('有問題尚未選擇，請確認。');
            return false;
        }
        
        let questionIDUnique = [...new Set(questionID)];

        if (questionID.length !== questionIDUnique.length) {
            appDialog.warn('問題對應不能重複。');
            return false;
        }
        else {
            return true;
        }
    });

    $('#auto-questions').click(function(e) {
        e.preventDefault();
        if ($('#formballots-voteid').val() === '') {
            appDialog.warn('請先選擇匯入的投票場次。');
            return false;
        }
        let autoQuestions = [];
        $('.questionID').first().find('option').each(function() {
            if ($(this).val() !== '') {
                autoQuestions.push($(this).val());
            }
        });
        console.log(autoQuestions);
        // 按照順序自動載入問題
        $('.questionID').each(function(index) {
            $(this).val(autoQuestions[index]);
        });
    });
JS
, $this::POS_END);
