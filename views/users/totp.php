<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

/** @var app\models\FormTotpSetup $model */
/** @var app\models\Users $user */
/** @var string|null $provisioningUri */
/** @var string|null $qrDataUri */
/** @var bool $totpEnabled */

$this->title = '雙因素驗證（TOTP）';
$this->params['secNavType'] = 'manage';

echo \app\widgets\Alert::widget();
?>

<div class="container" style="max-width: 720px;">
    <h2 class="text-center"><?= Html::encode($this->title) ?></h2>
    <hr>

    <?php if (!\app\models\FormTotpSetup::supportsTotpColumns()): ?>
        <div class="alert alert-warning">
            資料庫尚未加入 TOTP 欄位。請由維運執行：
            <code>mysql voting &lt; docs/migrations/20260713_users_totp.sql</code>
        </div>
        <?= Html::a('返回', ['users/index'], ['class' => 'btn btn-secondary']) ?>
    <?php else: ?>

    <p>帳號：<strong><?= Html::encode($user->cn) ?></strong></p>
    <p>狀態：
        <?php if ($totpEnabled): ?>
            <span class="badge bg-success">已啟用</span>
        <?php else: ?>
            <span class="badge bg-secondary">未啟用</span>
        <?php endif; ?>
    </p>

    <?php if ($provisioningUri !== null): ?>
        <div class="card mb-3">
            <div class="card-header">步驟 2：掃描 QR Code 或手動輸入金鑰</div>
            <div class="card-body text-center">
                <?php if ($qrDataUri): ?>
                    <img src="<?= Html::encode($qrDataUri) ?>" alt="TOTP QR Code" class="mb-3" style="max-width: 240px;">
                <?php endif; ?>
                <p class="text-muted small text-break"><code><?= Html::encode($provisioningUri) ?></code></p>
            </div>
        </div>

        <?php $form = ActiveForm::begin(); ?>
        <?= Html::hiddenInput('FormTotpSetup[step]', \app\models\FormTotpSetup::STEP_CONFIRM) ?>
        <?= $form->field($model, 'totpCode')->textInput([
            'maxlength' => 6,
            'inputmode' => 'numeric',
            'autocomplete' => 'one-time-code',
        ]) ?>
        <div class="text-end">
            <?= Html::submitButton('確認啟用', ['class' => 'btn btn-primary']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    <?php elseif (!$totpEnabled): ?>
        <div class="card mb-3">
            <div class="card-header">步驟 1：驗證密碼並產生金鑰</div>
            <div class="card-body">
                <?php $form = ActiveForm::begin(); ?>
                <?= Html::hiddenInput('FormTotpSetup[step]', \app\models\FormTotpSetup::STEP_GENERATE) ?>
                <?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'current-password']) ?>
                <div class="text-end">
                    <?= Html::submitButton('產生 QR Code', ['class' => 'btn btn-primary']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-danger mb-3">
            <div class="card-header text-danger">停用雙因素驗證</div>
            <div class="card-body">
                <?php $form = ActiveForm::begin(); ?>
                <?= Html::hiddenInput('FormTotpSetup[step]', \app\models\FormTotpSetup::STEP_DISABLE) ?>
                <?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'current-password']) ?>
                <?= $form->field($model, 'totpCode')->textInput([
                    'maxlength' => 6,
                    'inputmode' => 'numeric',
                    'autocomplete' => 'one-time-code',
                ])->hint('請輸入驗證器目前顯示的 6 碼代碼') ?>
                <div class="text-end">
                    <?= Html::submitButton('停用 TOTP', [
                        'class' => 'btn btn-outline-danger',
                        'data' => ['confirm' => '確定要停用雙因素驗證？'],
                    ]) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    <?php endif; ?>

    <p class="mt-3"><?= Html::a('返回使用者管理', ['users/index'], ['class' => 'btn btn-secondary']) ?></p>
    <?php endif; ?>
</div>
