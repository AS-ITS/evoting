<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->registerJs("$('#collapseDelete').collapse('hide');");

$form = ActiveForm::begin(['enableClientValidation'=>false]);
$genModeAry = Yii::$app->params['ct.candi.genModeAry'];
if ($round <= 1) {
    unset($genModeAry['round']);
}
?>
<div class="collapse" id="collapseDelete">
    <div class="card card-body list-group-item-danger mb-3">
        <div class="row align-items-center">
            <div class="col-lg-2 text-center">刪除條件：</div>
            <div class="col-lg-3">
                <?=$form
                    ->field($model, 'party')
                    ->dropDownList($parties, ['prompt' => '全部']) ?>
            </div>
            <div class="col-lg-3">
                <?=$form
                    ->field($model, 'questionID')
                    ->dropDownList($questions, ['prompt' => '全部']) ?>
            </div>
            <div class="col-lg-2">
                <?=$form
                    ->field($model, 'genMode')
                    ->dropDownList($genModeAry, ['prompt' => '全部']) ?>
            </div>
            <div class="col-lg-2 text-center">
                <?=Html::submitButton('刪除', ['class' => 'btn btn-danger','data' => ['bs-confirm' => "請確認候選人刪除條件？\n* 刪除後資料無法還原！"],]) ?>
            </div>
        </div>
    </div>
</div>
<?php

ActiveForm::end();
