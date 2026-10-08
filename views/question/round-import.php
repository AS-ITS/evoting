<?php

use yii\helpers\Url;
use yii\bootstrap5\Html;
use kartik\form\ActiveForm;
use kartik\select2\Select2;
use app\components\helper\ArrayHelper;
use wbraganca\dynamicform\DynamicFormWidget;

$title = '匯入輪次問題';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

echo \app\widgets\Alert::widget();

$url = Url::to(['question/round-import', 'voteID' => $voteID], 'https');
echo Html::label('匯入輪次選擇', 'round', ['class' => 'control-label']);
echo Select2::widget([
    'name' => 'round',
    'data' => ArrayHelper::map($rounds ?: [], 'round', function($element) {
        return '第'.$element['round'].'輪';
    }),
    'value' => [$round],
    'pluginEvents' => [
        'select2:select' => "function(e) { 
            var data = e.params.data;
            window.location.href = '$url&round=' + data.id;
        }"
    ],
]);

$form = ActiveForm::begin([
    'id' => 'question-round-import',
    'fieldConfig' => [
        'options' => ['class' => 'mb-0'],
        'labelOptions' => ['class' => 'mb-0 mt-2'],
    ],
]);
?>

<h4 class="mt-2" id="vr">投票規則</h4>
<?php 
    DynamicFormWidget::begin([
        'widgetContainer' => 'dynamicform_wrapper', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
        'widgetBody' => '.container-items', // required: css class selector
        'widgetItem' => '.item', // required: css class
        'limit' => 99, // the maximum times, an element can be cloned (default 999)
        'min' => 1, // 0 or 1 (default 1)
        'insertButton' => '.add-item', // css class
        'deleteButton' => '.remove-item', // css class
        'model' => $questions[0],
        'usePjax' => true,
        'formId' => 'question-round-import',
        'formFields' => [
            'numBallots',
            'leastNumBallots',
            'maxElect',
            'numOfKeep',
            'numFemaleKeep',
            'party',
            'voteID',
            'title',
            'titleE',
            'description',
            'descriptionE',
            'round',
        ],
    ]); 
?>

<div class="panel-body container-items">
    <?php foreach ($questions as $index => $question): ?>
        <div class="item card card-outline card-dark mb-2 border-dark" style="border-width: 2px;">
            <div class="card-body py-2 pr-2">
                <h6 class="card-title">
                    <span class="panel-title fw-bold"><?= 
                        ($voteInfo->partyOrNot ? Html::tag('span', '分組：', ['class' => 'text-primary']).$parties[$question->party].'&nbsp;&nbsp;' : '').
                        Html::tag('span', '問題：', ['class' => 'text-primary']).$question->title ?>
                    </span>
                    <button type="button" class="float-end remove-item btn btn-danger btn-sm btn-xs d-none"><i class="fa fa-times"></i></button>
                </h6>
                <div class="row">
                    <div class="col-md">
                        <?= $form->field($question, "[{$index}]numBallots")->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-md">
                        <?= $form->field($question, "[{$index}]leastNumBallots")->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-md">
                        <?= $form->field($question, "[{$index}]maxElect")->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-md">
                        <?= $form->field($question, "[{$index}]numOfKeep")->textInput(['maxlength' => true]) ?>
                    </div>
                    <?php if($voteInfo->addiCondition == 'female'): ?>
                    <div class="col-md">
                        <?= $form->field($question, "[{$index}]numFemaleKeep")->textInput(['maxlength' => true]) ?>
                    </div>
                    <?php endif; ?>
                    <?= $form->field($question, "[{$index}]title")->hiddenInput()->label(false); ?>
                    <?= $form->field($question, "[{$index}]titleE")->hiddenInput()->label(false); ?>
                    <?= $form->field($question, "[{$index}]confirmTitle")->hiddenInput()->label(false); ?>
                    <?= $form->field($question, "[{$index}]confirmTitleE")->hiddenInput()->label(false); ?>
                    <?= $form->field($question, "[{$index}]description")->hiddenInput()->label(false); ?>
                    <?= $form->field($question, "[{$index}]descriptionE")->hiddenInput()->label(false); ?>
                    <?= $form->field($question, "[{$index}]ruleText")->hiddenInput()->label(false); ?>
                    <?= $form->field($question, "[{$index}]ruleTextE")->hiddenInput()->label(false); ?>
                    <?= $form->field($question, "[{$index}]population")->hiddenInput()->label(false); ?>
                    <?= $form->field($question, "[{$index}]party")->hiddenInput()->label(false); ?>
                    <?= $form->field($question, "[{$index}]voteID")->hiddenInput()->label(false); ?>
                    <?= $form->field($question, "[{$index}]round")->hiddenInput(['value' => $voteInfo->round])->label(false); ?>
                    <?= $form->field($question, "[{$index}]questionID")->hiddenInput()->label(false); ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php 
    DynamicFormWidget::end(); 
?>

<hr>
<?php
echo Html::tag(
    'div', 
    Html::submitButton('匯入', ['class' => 'btn btn-primary', 'style' => 'width: auto']).
    Html::a('返回列表', ['question/index', 'voteID' => $voteID], ['class' => 'btn btn-secondary ms-2', 'style' => 'width: auto']),
    ['class' => 'row justify-content-center m-0']
);
ActiveForm::end();

$this->registerJs(<<<JS

// 如果只有一個問題，隱藏刪除按鈕
if ($('.item').length > 1) {
    $('.remove-item').removeClass('d-none');
}
$(".dynamicform_wrapper").on("afterDelete", function(e) {
    if ($('.item').length == 1) {
        $('.remove-item').addClass('d-none');
    }
});

JS
);