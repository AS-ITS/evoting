<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$title = '網站設定';
$this->title = $title;
$this->params['showVoteInfo'] = false;
$this->params['secNavType'] = 'manage'; // 啟用共用之 manage 二級導航

echo \app\widgets\Alert::widget();

// echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
// echo Html::tag('hr');
$form = ActiveForm::begin([
    'action' => \yii\helpers\Url::to(['edit-setting']),
    'enableClientValidation' => true
]);
?>
<h4>投票</h4>
<div class="row">
    <div class="col-md">
        <?=$form->field($model, 'countColumnNum')->textInput(['type' => 'number']) ?>
    </div>
    <div class="col-md">
        <?=$form->field($model, 'partyLimit')->textInput(['type' => 'number']) ?>
    </div>
    <div class="col-md">
        <?=$form->field($model, 'canDeletePassword')->dropdownList(Yii::$app->params['ct.yesOrNoAry']) ?>
    </div>
</div>
<h4>匿名登入防護</h4>
<div class="row">
    <div class="col-md">
        <?=$form->field($model, 'anonPasswordErrorTimes')->textInput(['type' => 'number']) ?>
    </div>
    <div class="col-md">
        <?=$form->field($model, 'anonLoginLockPeriod')->textInput(['type' => 'number']) ?>
    </div>
    <div class="col-md">
        <?=$form->field($model, 'anonLoginWaiting')->textInput(['type' => 'number']) ?>
    </div>
</div>
<h4>其他</h4>
<div class="row">
    <div class="col-md">
        <?=$form->field($model, 'homeLayout')->dropDownList(Yii::$app->params['ct.homeLayoutAry']) ?>
    </div>
    <div class="col-md">
        <?=$form->field($model, 'homeTitle')->textInput() ?>
    </div>
    <div class="col-md">
        <?=$form->field($model, 'homeTitleE')->textInput() ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?=$form->field($model, 'indexUrl')->textInput() ?>
    </div>
</div>
<h4>品牌設定</h4>
<p class="text-muted small">路徑相對於網站根目錄（@web），例如 <code>img/logo.png</code>、<code>favicon.ico</code>。Logo 留空則不顯示。</p>
<div class="row">
    <div class="col-md">
        <?=$form->field($model, 'logoPath')->textInput(['placeholder' => 'img/logo.png']) ?>
    </div>
    <div class="col-md">
        <?=$form->field($model, 'faviconPath')->textInput(['placeholder' => 'favicon.ico']) ?>
    </div>
    <div class="col-md">
        <?=$form->field($model, 'copyright')->textInput(['placeholder' => '空白則顯示「應用名稱 © 年份」']) ?>
    </div>
</div>
<?php
echo Html::tag(
    'div',
    Html::submitButton('修改', 
    ['class' => 'btn btn-primary me-2', 'style' => 'width: auto']),
    ['class'=>'row justify-content-center m-0']
);
ActiveForm::end();
