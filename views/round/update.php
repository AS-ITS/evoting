<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$title = '編輯輪次';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

echo \app\widgets\Alert::widget();
$form = ActiveForm::begin([
    'enableClientValidation' => true
]);
?>
<div class="row">
    <div class="col-lg-5">
        <?=$form->field($model, 'name')->textInput() ?>
    </div>
    <div class="col-lg-5">
        <?=$form->field($model, 'nameE')->textInput() ?>
    </div>
    <div class="col-lg-2">
        <?=$form->field($model, 'showName')->dropDownList(Yii::$app->params['ct.yesOrNoAry']) ?>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <?=$form->field($model, 'enablePasswords')->textInput() ?>
    </div>
</div>
<?php
echo Html::tag(
    'div',
        Html::submitButton('更新', ['class' => 'btn btn-primary me-2', 'style' => 'width: auto']).
        Html::a('返回列表', ['round/index', 'voteID' => $model->voteID], ['class' => 'btn btn-secondary', 'style' => 'width: auto']),
    ['class'=>'row justify-content-center m-0']
);

ActiveForm::end();