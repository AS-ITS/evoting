<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = Yii::t('app', '重設密碼');
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4"><?= Html::encode($this->title) ?></h3>

                    <?php if (Yii::$app->session->hasFlash('error')): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= Yii::$app->session->getFlash('error') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$valid): ?>
                        <div class="alert alert-warning" role="alert">
                            <?= Yii::t('app', '重設連結無效或已過期') ?>
                        </div>
                        <div class="text-center">
                            <?= Html::a(Yii::t('app', '重新申請'), ['/auth/forgot-password'], ['class' => 'btn btn-primary']) ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            <ul class="mb-0">
                                <li><?= Yii::t('app', '長度至少 8 個字元') ?></li>
                                <li><?= Yii::t('app', '必須包含至少一個字母和一個數字') ?></li>
                                <li><?= Yii::t('app', '不可與帳號相同') ?></li>
                            </ul>
                        </div>

                        <?php $form = ActiveForm::begin([
                            'id' => 'reset-password-form',
                            'method' => 'post',
                            'enableClientValidation' => false,
                        ]); ?>

                        <?= $form->field($model, 'new_password')->passwordInput([
                            'autofocus' => true,
                        ])->label(Yii::t('app', '新密碼')) ?>

                        <?= $form->field($model, 'confirm_password')->passwordInput()
                            ->label(Yii::t('app', '確認新密碼')) ?>

                        <div class="form-group text-center mt-4">
                            <?= Html::submitButton(Yii::t('app', '確認重設'), [
                                'class' => 'btn btn-primary w-100',
                            ]) ?>
                        </div>

                        <?php ActiveForm::end(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
