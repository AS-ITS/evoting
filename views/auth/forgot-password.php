<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = Yii::t('app', '忘記密碼');
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4"><?= Html::encode($this->title) ?></h3>
                    <p class="text-muted small">
                        <?= Yii::t('app', '請輸入帳號。若已設定信箱，系統會寄出 30 分鐘內有效的重設連結。') ?>
                    </p>

                    <?php $form = ActiveForm::begin([
                        'id' => 'forgot-password-form',
                        'method' => 'post',
                        'action' => ['auth/forgot-password'],
                        'enableClientValidation' => false,
                    ]); ?>

                    <?= $form->field($model, 'username')->textInput([
                        'autofocus' => true,
                        'placeholder' => Yii::t('app', '請輸入帳號'),
                    ])->label(Yii::t('app', '帳號')) ?>

                    <div class="form-group text-center mt-4">
                        <?= Html::submitButton(Yii::t('app', '寄出重設連結'), [
                            'class' => 'btn btn-primary w-100',
                        ]) ?>
                    </div>

                    <div class="text-center mt-3">
                        <?= Html::a(Yii::t('app', '返回登入'), ['/auth/login'], ['class' => 'btn btn-link']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
