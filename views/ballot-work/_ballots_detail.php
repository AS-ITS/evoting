<?php

use app\components\Model;
use yii\bootstrap5\Html;

echo Html::beginTag('table', ['class' => 'table table-bordered table-sm mb-0']);
    // 表格標題
    echo Html::beginTag('thead'); // thead
        echo Html::tag('tr',
            Html::tag('th', Yii::t('app', '投票者'), ['class' => 'col-2 table-secondary']).
            Html::tag('th', Yii::t('app', '圈選名單'), ['class' => 'table-secondary'])
        );
    echo Html::endTag('thead'); // thead
    // 表格內容
    echo Html::beginTag('tbody'); // tbody
        foreach ($ballotsSelected as $ballot) {
            $ballotsCount = count($ballot->ballotsSelected);
            if ($valid) {
                if ($formManageCount->checkBallotValid($question->questionID, $ballotCountAry[$ballot->ballotID], $questions)) {
                    $selCandiName = [];
                    foreach ($ballot->ballotsSelected as $value) {
                        $selCandiName[] = Html::tag('font', 
                            Html::tag('span', Model::i18n($value->candiData->NameE, $value->candiData->Name), ['class' => 'badge'])
                        , ['size' => 5]) ;
                    }
                    echo Html::tag('tr',
                        Html::tag('td', Html::encode($sysidList[$ballot->creator] ?? '')).
                        Html::tag('td', implode("、", $selCandiName))
                    );
                }
            }
            else {
                if (!$formManageCount->checkBallotValid($question->questionID, $ballotCountAry[$ballot->ballotID], $questions)) {
                    $selCandiName = [];
                    foreach ($ballot->ballotsSelected as $value) {
                        $selCandiName[] = Html::tag('font', 
                            Html::tag('span', Model::i18n($value->candiData->NameE, $value->candiData->Name), ['class' => 'badge'])
                        , ['size' => 5]) ;
                    }
                    echo Html::tag('tr',
                        Html::tag('td', Html::encode($sysidList[$ballot->creator] ?? '')).
                        Html::tag('td', implode("、", $selCandiName))
                    );
                }
            }
        }
    echo Html::endTag('tbody'); // tbody
echo Html::endTag('table');