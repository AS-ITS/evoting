<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$form = ActiveForm::begin([
    'enableClientValidation' => false,
]);
?>
<div class="row">
    <div class="col-md-6">
        <?= $form->field($model, 'cn') ?>
        <?= $form->field($model, 'name') ?>
        <?= $form->field($model, 'password_plain')->passwordInput([
            'placeholder' => '留空表示不修改密碼'
        ])->hint('密碼規則：<br>• 長度至少 8 個字元<br>• 必須包含英文字母和數字<br>• 不可與帳號相同或過於相似') ?>
    </div>
    <div class="col-md-6">
        <?= $form->field($model, 'roles')->checkboxList(Yii::$app->params['ct.apRoles']) ?>
    </div>
</div>
<!-- submit button -->
<div class="mb-3 text-center">
    <?= Html::submitButton($model->isNewRecord ? '新增' : '更新', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('返回列表', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>
<?php ActiveForm::end(); ?>