<?php
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Url;

$this->registerJs("$('#collapseStatus').collapse('hide');");
$form = ActiveForm::begin([
    'action' => Url::to(['switch-status', 'voteID' => $voteID]),
    'enableClientValidation' => false
]);
?>
<div class="collapse" id="collapseStatus">
    <div class="card card-body list-group-item-warning mb-3">
        <?php if (!empty($isBindVote)): ?>
        <div class="alert alert-warning">批次變更將套用至共用密碼池，影響所有綁定場次。</div>
        <?php endif; ?>
        <div class="row align-items-center">
            <div class="col-lg-12 text-start fw-bold">設定條件：</div>
        </div>
        <div class="row">
            <div class="col-lg-4">
                <?=join('', [
                    Html::tag('label', '組別', ['class' => 'mb-0', 'for' => 'labelParty']),
                    Html::dropDownList(
                        'party', '', 
                        $parties,
                        ['class' => 'form-select', 'id' => 'labelParty', 'prompt' => '']
                    )
                ]) ?>
            </div>
            <div class="col-lg">
                <?=join('', [
                    Html::tag('label', '標記', ['class' => 'mb-0', 'for' => 'labelMark']),
                    Html::dropDownList(
                        'mark', '', 
                        $marks,
                        ['class' => 'form-select', 'id' => 'labelMark', 'prompt' => '']
                    )
                ]) ?>
            </div>
            <div class="col-lg-2">
                <?=join('', [
                    Html::tag('label', '雙軌投票', ['class' => 'mb-0', 'for' => 'labelDtrack']),
                    Html::dropDownList(
                        'dtrack', '', 
                        Yii::$app->params['ct.passwd.dtrackAry'],
                        ['class' => 'form-select', 'id' => 'labelDtrack', 'prompt' => '']
                    )
                ]) ?>
            </div>
            <div class="col-lg-2">
                <?=join('', [
                    Html::tag('label', '本輪已投票', ['class' => 'mb-0', 'for' => 'labelRoundVoted']),
                    Html::dropDownList(
                        'voted', '', 
                        Yii::$app->params['ct.yesOrNoAry'],
                        ['class' => 'form-select', 'id' => 'labelRoundVoted', 'prompt' => '']
                    )
                ]) ?>
            </div>
            <div class="col-lg-2">
                <?=join('', [
                    Html::tag('label', '狀態', ['class' => 'mb-0', 'for' => 'labelStatus']),
                    Html::dropDownList(
                        'status', '1', 
                        Yii::$app->params['ct.passwd.validAry'],
                        ['class' => 'form-select', 'id' => 'labelStatus']
                    )
                ]) ?>
            </div>
        </div>
        <div class="text-end mt-2">
            <?=Html::submitButton('設定狀態', ['class' => 'btn btn-warning']) ?>
        </div>
    </div>
</div>
<?php
ActiveForm::end();
