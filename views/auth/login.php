<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = Yii::t('app', '管理員登入');
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4"><?= Html::encode($this->title) ?></h3>

                    <?php $form = ActiveForm::begin([
                        'id' => 'admin-login-form',
                        'method' => 'post',
                        'action' => ['auth/login'],
                        'enableClientValidation' => false,
                        'enableAjaxValidation' => false,
                        'validateOnSubmit' => false,
                    ]); ?>

                    <?= $form->field($model, 'username')->textInput([
                        'autofocus' => true,
                        'placeholder' => Yii::t('app', '請輸入帳號'),
                    ])->label(Yii::t('app', '帳號')) ?>

                    <?= $form->field($model, 'password')->passwordInput([
                        'placeholder' => Yii::t('app', '請輸入密碼'),
                    ])->label(Yii::t('app', '密碼')) ?>

                    <div class="form-group text-center mt-4">
                        <?= Html::submitButton(Yii::t('app', '登入'), [
                            'class' => 'btn btn-primary btn-block w-100',
                            'name' => 'login-button',
                            'type' => 'submit'
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                    <?php if (Yii::$app->session->hasFlash('error')): ?>
                        <div class="alert alert-danger mt-3" role="alert">
                            <?= Yii::$app->session->getFlash('error') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (Yii::$app->session->hasFlash('success')): ?>
                        <div class="alert alert-success mt-3" role="alert">
                            <?= Yii::$app->session->getFlash('success') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
