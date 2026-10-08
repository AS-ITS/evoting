<?php

use rmrevin\yii\fontawesome\FAS;
use yii\helpers\Url;
use yii\bootstrap5\Html;

$this->registerJs("$('#collapsePrint').collapse('hide');");

echo Html::beginForm(
    ['export-passwd', 'voteID' => $voteInfo->voteID], 
    'post', 
    ['id' => 'exportPasswdForm', 'enctype' => 'multipart/form-data']
);
?>
<div class="collapse" id="collapsePrint">
    <div class="card card-body list-group-item-info mb-3">
        <?php
            echo Html::tag('div', 
                Html::ol([
                    Html::a(FAS::icon('download', ['class' => 'mx-1']).'點我下載樣板範例', Url::to(['export-default', 'voteID' => $voteInfo->voteID]), ['class' => '']),
                    '請保留範例檔內的<code>${content}</code>、<code>${password}</code>、<code>${/content}</code>，並依需求修改其餘文字。', 
                    '打開密碼函檔案如出現<code>很抱歉，無法開啟...</code>，點擊確定，<code>...是否復原本文件內容...</code>，再點擊是。'
                ], ['encode' => false, 'class' => 'mb-0']),
                ['class' => 'alert alert-warning', 'role' => 'alert']
            );
        ?>

        <div class="row align-items-center">
            
            <div class="col-lg-12 text-start fw-bold">生成條件：</div>
        </div>
        <?php
            echo Html::beginTag('div', ['class' => 'row']);

            if ($voteInfo->partyOrNot) {
                echo Html::tag('div', 
                    Html::tag('label', '組別：', ['class' => 'me-2 mb-0']).
                    Html::dropDownList('party', '', $parties, ['id' => 'party', 'class' => 'form-select', 'required' => false, 'prompt' => '所有組別']), 
                ['class' => 'col-lg-3']);
            }

            echo Html::tag('div', 
                Html::tag('label', '標記：', ['class' => 'me-2 mb-0']).
                Html::dropDownList('mark', '', $marks, ['id' => 'mark', 'class' => 'form-select', 'required' => false, 'prompt' => '所有標記']), 
            ['class' => 'col-lg-3']);
            
            echo Html::tag('div', 
                Html::tag('label', '起始編號：', ['class' => 'me-2 mb-0']).
                Html::textInput('start', '', ['id' => 'snStart', 'class' => 'form-control', 'required' => false, 'min' => 1, 'type' => 'number', 'step' => 1, 'value' => 1]), 
            ['class' => 'col-lg-3']);

            echo Html::tag('div', 
                Html::tag('label', '結束編號：', ['class' => 'me-2 mb-0']).
                Html::textInput('end', '', ['id' => 'snEnd', 'class' => 'form-control', 'required' => false, 'min' => 1, 'type' => 'number', 'step' => 1, 'value' => 1]), 
            ['class' => 'col-lg-3']);

            echo Html::tag('div',
                Html::tag('label', '本輪已投票：', ['class' => 'me-2 mb-0']).
                Html::dropDownList('voted', '', Yii::$app->params['ct.yesOrNoAry'], ['id' => 'roundVoted', 'class' => 'form-select', 'prompt' => '']),
            ['class' => 'col-lg-3']);
        
            echo Html::endTag('div');

            echo Html::tag('div', 
                Html::tag('label', '樣板：', ['class' => 'me-2 mb-0']).
                Html::activeFileInput($model, 'template', ['id' => 'template', 'class' => 'form-control', 'required' => true]), 
            ['class' => 'mt-2']);
        ?>
        <?php if (!empty($reauthRequired)): ?>
            <?= $this->render('@app/views/site/_sensitive_reauth', [
                'reauthRequired' => true,
                'useTotp' => !empty($useTotp),
            ]) ?>
        <?php endif; ?>
        <div class="text-end mt-2">
            <?=Html::hiddenInput('exportAcknowledged', '1')?>
            <?=Html::submitButton('生成密碼函', [
                'class' => 'btn btn-info',
                'data' => [
                    'confirm' => '將生成含明文密碼的密碼函，此操作會記錄於操作日誌。確定繼續？',
                ],
            ]) ?>
        </div>
    </div>
</div>
<?php
echo Html::endForm();
