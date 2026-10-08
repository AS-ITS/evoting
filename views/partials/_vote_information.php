<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\Modal;
use rmrevin\yii\fontawesome\FAS;

Modal::begin([
    'id' => $id,
    'dialogOptions' => ['class' => 'modal-xl modal-dialog-centered'],
    'options' => ['style' => 'background-color: rgba(0, 0, 0, 0.5); '],
    'clientOptions' => ['backdrop' => false],
    'title' => FAS::icon('info-circle', ['class' => 'me-2']).Yii::t('app', '圈選須知'),
    'titleOptions' => ['class' => 'fw-bolder'],
    'headerOptions' => ['class' => 'bg-info text-light'],
    'closeButton' => false,
    'footer' => Html::tag('div', 
        Html::tag('span',
            Html::Button(FAS::icon('check', ['class' => 'me-2']).
            Yii::t('app', '確定'), ['class' => 'btn btn-info', 'data-bs-dismiss' => 'modal'])
        ),
        ['class'=>'text-center']
    ),
    'footerOptions' => ['class' => 'justify-content-center']
]);

echo Html::tag('div', $voteInfo->replaceQuestionRule($information ?? ''));

Modal::end();