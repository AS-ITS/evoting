<?php

use yii\helpers\Url;
use yii\bootstrap5\Html;
use kartik\form\ActiveForm;

$title = '清除登入狀態';
$this->title = $title;
$this->params['secNavType'] = 'manage'; // 啟用共用之 manage 二級導航

echo \app\widgets\Alert::widget();

$form = ActiveForm::begin([
    'id' => 'login-form',
    'type' => ActiveForm::TYPE_HORIZONTAL,
    'formConfig' => ['labelSpan' => 2, 'deviceSize' => ActiveForm::SIZE_SMALL],
    'options' => ['class' => 'form-horizontal'],
    'action' => ['logout-anon']
]);

echo Html::tag(
    'div',
    Html::tag(
        'div',
        Html::tag('label', '投票場次', ['for' => 'voteID']) .
            Html::dropDownList('voteID', null, $votes, ['class' => 'form-select', 'id' => 'voteID', 'prompt' => '']),
        ['class' => 'col']
    ) . Html::tag(
        'div',
        Html::tag('label', '組別', ['for' => 'party']) .
            Html::dropDownList('party', null, [], ['class' => 'form-select', 'id' => 'party', 'prompt' => '']),
        ['class' => 'col']
    ) . Html::tag(
        'div',
        Html::tag('label', '標記', ['for' => 'mark']) .
            Html::dropDownList('mark', null, [], ['class' => 'form-select', 'id' => 'mark', 'prompt' => '']),
        ['class' => 'col']
    ),
    ['class' => 'row mb-4']
);
echo Html::tag(
    'div',
        Html::submitButton('清除session', ['class' => 'btn btn-primary me-2', 'style' => 'width: auto']),
    ['class' => 'row justify-content-center mb-4']
);

ActiveForm::end();

$url = Url::to(['passwd-info'], 'https');

$this->registerJs(<<<JS
    $('#voteID').change(function() {
        getPasswdInfo($(this).val());
    });
    // 取得候選人問題
    function getPasswdInfo(voteID) {
        $.ajax({
            type: "POST",
            url: '$url',
            data: {voteID: voteID},
            success: function(result) {
                console.log(result);
                let parties = JSON.parse(result.parties);
                let marks = JSON.parse(result.marks);
                $("#party").empty();
                $("#mark").empty();
                $("#party").append(new Option('請選擇', ''));
                $("#mark").append(new Option('請選擇', ''));
                parties.forEach(function(element) {
                    $("#party").append(new Option(element.name, element.party));
                })
                marks.forEach(function(element) {
                    $("#mark").append(new Option(element.mark, element.mark));
                })
            }, 
            error: function(result) {
                console.log(result);
            }
        });
    }
JS
);