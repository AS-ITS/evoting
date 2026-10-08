<?php
use yii\bootstrap5\Html;

/** @var bool $reauthRequired */
/** @var bool $useTotp */
/** @var string|null $passwordFieldId */
/** @var string|null $totpFieldId */

$passwordFieldId = $passwordFieldId ?? 'adminPassword';
$totpFieldId = $totpFieldId ?? 'totpCode';
?>
<?php if (!empty($reauthRequired)): ?>
<div class="row border-top pt-3 mt-2">
    <div class="col-12 text-start fw-bold text-danger mb-2">二次驗證</div>
    <div class="col-md-4">
        <?= Html::label('管理員密碼', $passwordFieldId, ['class' => 'form-label']) ?>
        <?= Html::passwordInput('adminPassword', null, [
            'id' => $passwordFieldId,
            'class' => 'form-control',
            'autocomplete' => 'current-password',
            'required' => true,
        ]) ?>
    </div>
    <?php if (!empty($useTotp)): ?>
    <div class="col-md-4">
        <?= Html::label('驗證器代碼（6 碼）', $totpFieldId, ['class' => 'form-label']) ?>
        <?= Html::textInput('totpCode', null, [
            'id' => $totpFieldId,
            'class' => 'form-control',
            'inputmode' => 'numeric',
            'pattern' => '\d{6}',
            'maxlength' => 6,
            'autocomplete' => 'one-time-code',
            'required' => true,
        ]) ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
