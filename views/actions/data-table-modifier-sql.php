<?php
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */

$this->title = "執行 SQL 查詢";
$this->params['navbar_left_items'] = [
    [
        'label' => '返回資料表條列',
        'url' => $linkIndex,
    ],
];

echo \app\widgets\Alert::widget();
?>
<div class="data-table-modifier-sql">
    <?php $form = ActiveForm::begin([
        'id' => 'data-table-modifier-sql',
        'enableClientValidation' => false,
        'enableAjaxValidation' => false,
        'fieldConfig' => [
            'options' => ['class' => 'mb-0'],
            'labelOptions' => ['class' => 'mb-0 mt-2'],
        ],
    ]); ?>
    <div class="row">
        <div class="col-md">
            <div class="form-group">
                <label for="sqlTextarea">批次SQL語法(請使用分號分隔每段指令)</label>
                <?=Html::textarea('sql', $inputSql, ['class'=>'form-control', 'id'=>'sqlTextarea', 'rows'=>'20']);?>
            </div>
        </div>
    </div>
    <div class="row mb-3 align-items-center">
        <div class="col-md-auto"> <!-- 使用 col-md-auto 使元素根據內容自動調整寬度 -->
            <?= Html::submitButton('查詢', ['class' => 'btn btn-primary']); ?>
        </div>
        <div class="col-md">
            <div class="custom-control custom-checkbox">
                <?=Html::checkbox('remember', true, [
                    'id' => 'rememberSql',
                    'class' => 'custom-control-input',
                    'uncheck'=>'0',
                ]);?>
                <?=Html::label('記住 SQL','rememberSql', [
                    'class'=>'custom-control-label',
                ]);?>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>