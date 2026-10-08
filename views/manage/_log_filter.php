<?php

use yii\helpers\Html;
use yii\jui\DatePicker;
use yii\bootstrap5\ActiveForm;

/**
 * 官方提供的日期區間範例
 * @see https://jqueryui.com/datepicker/#date-range
 */
$this->registerJs(<<<JS
    $(function() {
        let dateFormat = "yy-mm-dd",
        from = $("#from")
            .datepicker({
                defaultDate: "+1w",
                changeMonth: true,
                changeYear: true,
                dateFormat: dateFormat
            })
            .on("change", function() {
                to.datepicker("option", "minDate", getDate(this));
            }),
        to = $("#to").datepicker({
            defaultDate: "+1w",
            changeMonth: true,
            changeYear: true,
            dateFormat: dateFormat
        })
        .on("change", function() {
            from.datepicker("option", "maxDate", getDate(this));
        });
    
        function getDate(element) {
            let date;
            try {
                date = $.datepicker.parseDate(dateFormat, element.value);
            } catch(error) {
                date = null;
            }
        
            return date;
        }
    });
JS
, $this::POS_END);

?>

<div class="logs-search">
    <?php $form = ActiveForm::begin([
        'action' => ['log'],
        'method' => 'get',
    ]); ?>
    <div class="row">
        <div class="col-6">
            <?= $form->field($model, 'createdFrom')->widget(DatePicker::className(), [
                'dateFormat' => 'yyyy-MM-dd',
                'options' => [
                    'id' => 'from',
                    'class' => 'form-control', 
                ],
                'clientOptions' => [
                    'autoclose' => true,
                ]
            ]); ?>
        </div>
        
        <div class="col-6">
            <?= $form->field($model, 'createdTo')->widget(DatePicker::className(), [
                'dateFormat' => 'yyyy-MM-dd',
                'options' => [
                    'id' => 'to',
                    'class' => 'form-control', 
                ],
                'clientOptions' => [
                    'autoclose' => true,
                ]
            ]); ?>
        </div>
        
    </div>
    <div class="mb3 text-end">
        <?= Html::submitButton('查詢', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>