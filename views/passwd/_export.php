<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Url;

$this->registerJs("$('#collapseExport').collapse('hide');");
$form = ActiveForm::begin([
    'action' => Url::to(['export', 'voteID' => $voteID]),
    'enableClientValidation' => false
]);
?>
<div class="collapse" id="collapseExport">
    <div class="card card-body list-group-item-info mb-3">
        <div class="row align-items-center">
            <div class="col-lg-12 text-start fw-bold">匯出條件：</div>
        </div>
        <div class="row">
            <div class="col-lg-4">
                <?=$form->field($model, 'party')->dropdownList($parties, ['prompt' => '']) ?>
            </div>
            <div class="col-lg">
                <?=$form->field($model, 'mark')->dropdownList($marks, ['prompt' => '']) ?>
            </div>
            <div class="col-lg-2">
                <?=$form->field($model, 'dtrack')->dropdownList(Yii::$app->params['ct.passwd.dtrackAry'], ['prompt' => '']) ?>
            </div>
            <div class="col-lg-2">
                <?=$form->field($model, 'voted')->dropdownList(Yii::$app->params['ct.yesOrNoAry'], ['prompt' => ''])->label('本輪已投票') ?>
            </div>
        </div>
        <?php if (!empty($reauthRequired)): ?>
        <div class="row border-top pt-3 mt-2">
            <div class="col-lg-12 text-start fw-bold text-danger">二次驗證（匯出明文密碼前必填）</div>
            <div class="col-lg-4">
                <?= Html::label('管理員密碼', 'adminPassword', ['class' => 'form-label']) ?>
                <?= Html::passwordInput('adminPassword', null, ['class' => 'form-control', 'autocomplete' => 'current-password', 'required' => true]) ?>
            </div>
            <?php if (!empty($useTotp)): ?>
            <div class="col-lg-4">
                <?= Html::label('驗證器代碼（6 碼）', 'totpCode', ['class' => 'form-label']) ?>
                <?= Html::textInput('totpCode', null, ['class' => 'form-control', 'inputmode' => 'numeric', 'pattern' => '\d{6}', 'maxlength' => 6, 'autocomplete' => 'one-time-code', 'required' => true]) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="text-end mt-2">
            <?=Html::hiddenInput('exportAcknowledged', '1')?>
            <?=Html::submitButton('匯出', [
                'class' => 'btn btn-info',
                'data' => [
                    'confirm' => '將匯出含明文密碼的 CSV，此操作會記錄於操作日誌。確定繼續？',
                ],
            ]) ?>
        </div>
    </div>
</div>
<?php
ActiveForm::end();
