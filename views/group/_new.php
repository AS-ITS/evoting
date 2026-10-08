<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->registerJs("$('#collapseNew').collapse('hide');");

$model->scenario = 'create';
$form = ActiveForm::begin([
    'action' => 'create',
    'enableClientValidation' => false
]);
?>
<div class="collapse" id="collapseNew">
    <div class="card card-body list-group-item-primary mb-3">
        <div class="row align-items-center">
            <!-- <div class="col-lg-12 text-left font-weight-bold">建立群組：</div> -->
            <div class="col-lg-7">
                <?= $form->field($model, 'groupName')->textInput() ?>
            </div>
            <div class="col-lg-3">
                <?= $form->field($model, 'isRoutine')->dropdownList(
                    Yii::$app->params['ct.group.routineAry'], ['value' => 'N']
                ) ?>
            </div>
            <div class="col-lg-2 text-end">
                <?=Html::submitButton('建立', ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    </div>
</div>
<?php
ActiveForm::end();
