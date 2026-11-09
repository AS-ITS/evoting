<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/**
 * 日期區間：使用瀏覽器內建日期欄位（值為 yyyy-mm-dd），起日不可晚於迄日
 */
$this->registerJs(<<<JS
    (function () {
        const from = document.getElementById('from');
        const to = document.getElementById('to');
        if (!from || !to) {
            return;
        }
        const sync = function () {
            to.min = from.value;
            from.max = to.value;
        };
        from.addEventListener('change', sync);
        to.addEventListener('change', sync);
        sync();
    })();
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
            <?= $form->field($model, 'createdFrom')->input('date', ['id' => 'from']) ?>
        </div>

        <div class="col-6">
            <?= $form->field($model, 'createdTo')->input('date', ['id' => 'to']) ?>
        </div>

    </div>
    <div class="mb3 text-end">
        <?= Html::submitButton('查詢', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
