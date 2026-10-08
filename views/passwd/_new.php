<?php
use yii\helpers\Url;
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->registerJs("$('#collapseNew').collapse('hide');");

$form = ActiveForm::begin([
    'action' => Url::to(['create-password', 'voteID' => $voteID]),
    'enableClientValidation' => false
]);
?>
<div class="collapse" id="collapseNew">
    <div class="card card-body list-group-item-primary mb-3">
        <div class="row align-items-center">
            <div class="col-lg-12 text-start fw-bold">生成條件：</div>
            <div class="col-lg-4">
                <?=join('', [
                    Html::tag('label', '組別', ['class' => 'mb-0', 'for' => 'labelParty']),
                    Html::dropDownList(
                        'party', '', 
                        $parties,
                        ['class' => 'form-select', 'id' => 'labelParty', 'required' => true]
                    )
                ]) ?>
            </div>
            <div class="col-lg-4">
                <?=join('', [
                    Html::tag('label', '密碼組成', ['class' => 'mb-0', 'for' => 'labelType']),
                    Html::dropDownList(
                        'type', '', 
                        Yii::$app->params['ct.passwd.typeAry'],
                        ['class' => 'form-select', 'id' => 'labelType', 'required' => true]
                    )
                ]) ?>
            </div>
            <div class="col-lg-2">
                <?=join('', [
                    Html::tag('label', '密碼長度', ['class' => 'mb-0', 'for' => 'labelLength']),
                    Html::input('number', 'length', '6', [
                        'required' => true,
                        'class' => 'form-control',
                        'id'    => 'labelLength',
                        'min'   => '6',
                        'max'   => '99',
                        // 'max'   => '12',
                    ])
                ]) ?>
            </div>
            <div class="col-lg-2">
                <?=join('', [
                    Html::tag('label', '密碼數量', ['class' => 'mb-0', 'for' => 'labelNum']),
                    Html::input('number', 'num', '1', [
                        'required' => true,
                        'class' => 'form-control',
                        'id'    => 'labelNum',
                        'min'   => '1',
                        'max'   => '9999',
                    ])
                ]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg">
                <?=join('', [
                    Html::tag('label', '格式', ['class' => 'mb-0', 'for' => 'labelFormat']),
                    Html::textInput(
                        'format', '',
                        ['class' => 'form-control', 'id' => 'labelFormat', 'disabled' => true]
                    ),
                    Html::tag('small', 'Ex: iissSS，無輸入就隨機排列，僅英數混和有效。<br>數字=i，小寫英文=s，大寫英文=S', ['class' => 'form-text text-muted'])
                ]) ?>
            </div>
            <div class="col-lg">
                <?=join('', [
                    Html::tag('label', '標記', ['class' => 'mb-0', 'for' => 'labelMark']),
                    Html::textInput(
                        'mark', '',
                        ['class' => 'form-control', 'id' => 'labelMark']
                    ),
                    Html::tag('small', '可以操作相同標記的密碼資料。', ['class' => 'form-text text-muted'])
                ]) ?>
            </div>
            <div class="col-lg-2">
                <?=join('', [
                    Html::tag('label', '雙軌投票', ['class' => 'mb-0', 'for' => 'labelDtrack']),
                    Html::dropDownList(
                        'dtrack', '1', 
                        Yii::$app->params['ct.passwd.dtrackAry'],
                        ['class' => 'form-select', 'id' => 'labelDtrack', 'required' => true]
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
            <?=Html::submitButton('生成密碼', ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
</div>
<?php
ActiveForm::end();

$this->registerJs(<<<JS
    // 特定密碼組成不能填寫格式
    $('#labelType').change(function() {
        var typeAry = ['int', 'en'];
        if (!typeAry.includes(this.value)) {
            $('#labelFormat').prop('disabled', false);
        }
        else {
            $('#labelFormat').prop('disabled', true);
        }
    });
    // 格式只能輸入i、s、S的字串
    $('#labelFormat').on('keyup', function() {
        var format = this.value;
        var format = format.replace(/[^isS]/g, '');
        this.value = format;
    });
JS
);
