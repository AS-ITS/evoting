<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$title = '重啟投票';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

echo \app\widgets\Alert::widget();

echo \yii\bootstrap5\Alert::widget([
    'options' => [
        'class' => 'alert-warning text-center fw-bold',
    ],
    'closeButton' => false,
    'body' => '※重啟投票將會刪除選票、計票、開票及結果的資料※',
]);

echo Html::beginTag('div', ['class' => 'd-flex justify-content-center']);
ActiveForm::begin();
echo Html::submitButton(
    '確認重啟', 
    [
        'class' => 'btn btn-danger ',
        'data' => [
            'bs-confirm' => '確定重啟?'
        ]
    ]
);
ActiveForm::end();
echo Html::endTag('div');