<?php 

use yii\bootstrap5\Html;

$voteNum = $validCount + $invalidCount;
$voteRate = floor($voteNum * 100 / $passwordCount);
echo Html::tag('div', 
    Html::tag('div', "開票進度：", ['class' => 'col-2 border-right progress-filed']).
    Html::tag('div', 
        Html::tag('div', 
            Html::tag('div', '', ['class' => 'progress-bar', 'role' => 'progressbar', 'style' => "width: {$voteRate}%; background-Color: green;", 'aria-valuenow' => $voteRate, 'aria-valuemin' => '0', 'aria-valuemax' => '100'])
            , ['class' => 'progress py-2', 'style' => 'height: 100%;']
        ),
        ['class' => 'col-4 border-right progress-filed']
    ).
    Html::tag('div', "{$voteRate}% (已開票數／應開票數＝{$voteNum}／{$passwordCount})", ['class' => 'col-6'])
, ['class' => 'row text-center mx-0 mb-2', 'id' => 'progress', 'style' => "font-size: {$progressFontSize};"]);