<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\Modal;

Modal::begin([
    'id' => 'all-party-alert',
    'title' => '全部分組注意事項',
    'size' => 'modal-xl',
    'clientOptions' => ['backdrop' => false],
    'footer' => Html::button('我知道了', ['class' => 'btn btn-secondary btn-sm', 'data-bs-dismiss' => 'modal']),
    'headerOptions' => ['class' => 'bg-warning']
]);

echo Html::tag('h3', '選擇全部分組後，此問題會顯示於所有分組，全分組一起統計。', ['class' => 'text-danger text-center']) ;

Modal::end();