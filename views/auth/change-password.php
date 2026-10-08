<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = Yii::t('app', '修改密碼');
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4"><?= Html::encode($this->title) ?></h3>

                    <div class="alert alert-info" role="alert">
                        <strong><?= Yii::t('app', '密碼要求') ?>:</strong>
                        <ul class="mb-0 mt-2">
                            <li><?= Yii::t('app', '長度至少 8 個字元') ?></li>
                            <li><?= Yii::t('app', '必須包含至少一個字母和一個數字') ?></li>
                            <li><?= Yii::t('app', '不可與帳號相同') ?></li>
                            <li><?= Yii::t('app', '不可與目前密碼相同') ?></li>
                        </ul>
                    </div>

                    <?php $form = ActiveForm::begin([
                        'id' => 'change-password-form',
                        'method' => 'post',
                        'action' => ['auth/change-password'],
                        'enableClientValidation' => true,
                        'enableAjaxValidation' => false,
                    ]); ?>

                    <?= $form->field($model, 'old_password')->passwordInput([
                        'autofocus' => true,
                        'placeholder' => Yii::t('app', '請輸入目前密碼'),
                    ])->label(Yii::t('app', '目前密碼')) ?>

                    <?= $form->field($model, 'new_password')->passwordInput([
                        'placeholder' => Yii::t('app', '請輸入新密碼'),
                    ])->label(Yii::t('app', '新密碼')) ?>

                    <?= $form->field($model, 'confirm_password')->passwordInput([
                        'placeholder' => Yii::t('app', '請再次輸入新密碼'),
                    ])->label(Yii::t('app', '確認新密碼')) ?>

                    <div class="form-group text-center mt-4">
                        <?= Html::submitButton(Yii::t('app', '確認修改'), [
                            'class' => 'btn btn-primary btn-block w-100',
                            'name' => 'change-password-button',
                        ]) ?>
                    </div>

                    <div class="text-center mt-3">
                        <?= Html::a(Yii::t('app', '返回首頁'), ['/site/index'], ['class' => 'btn btn-link']) ?>
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
