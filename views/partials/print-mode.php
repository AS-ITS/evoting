<?php

use yii\bootstrap5\Html;
/* 總投票數、有效票、廢票區域: 僅有計票展示的樣板選擇分組或分組一張表時會顯示 */
$params = $params ?? [];
$template = $params['template'] ?? '';
$voteValidate = in_array($template, ['party', 'party_group']);

// 這些變數可能沒有從呼叫端傳入
$ruleAllEmptyOrOneInvalid = $ruleAllEmptyOrOneInvalid ?? false;
$passwordCount = $passwordCount ?? 0;
$validCount = $validCount ?? 0;

if ($ruleAllEmptyOrOneInvalid) {
    $invalidCount = $passwordCount - $validCount;
    $voteValidateText = Html::tag('span', "總投票數： {$passwordCount} 張；投票結果：有效票 {$validCount} 張，廢票 {$invalidCount} 張。", ['class' => 'h3 mt-2']);
}
else {
    $voteValidateText = Html::tag('span', "總投票數：____張；投票結果：有效票____張，廢票____張。", ['class' => 'h3 mt-2']);
}

echo Html::tag('div',
    $voteValidate ? $voteValidateText : ''
, ['id' => 'validate-area', 'class' => 'text-start', 'style' => 'display: none;']);

/* 簽名區域 */
echo Html::tag('div',
    Html::tag('h3', '', ['class' => 'mt-4'])
, ['id' => 'sign-area', 'class' => 'text-start', 'style' => 'display: none;']);

$signChoices = [
    'chair-monitor-v' => '監票人+主席(上下)',
    'chair-monitor-h' => '監票人+主席(左右)',
    'chair' => '僅主席',
    'monitor' => '僅監票人',
    'none' => '不需要',
];
$choiceButtons = '';
foreach ($signChoices as $layout => $label) {
    $choiceButtons .= Html::button($label, [
        'type' => 'button',
        'class' => 'btn btn-primary m-1 btn-print-sign',
        'data-sign-layout' => $layout,
    ]);
}

echo Html::tag('div',
    Html::tag('div',
        Html::tag('div',
            Html::tag('div',
                Html::tag('h5', '請選擇簽名欄位', [
                    'class' => 'modal-title',
                    'id' => 'print-sign-modal-label',
                ]) .
                Html::button('', [
                    'type' => 'button',
                    'class' => 'btn-close',
                    'data-bs-dismiss' => 'modal',
                    'aria-label' => 'Close',
                ]),
                ['class' => 'modal-header']
            ) .
            Html::tag('div', $choiceButtons, ['class' => 'modal-body text-center']),
            ['class' => 'modal-content']
        ),
        ['class' => 'modal-dialog modal-lg modal-dialog-centered']
    ),
    [
        'class' => 'modal fade',
        'id' => 'print-sign-modal',
        'tabindex' => '-1',
        'aria-labelledby' => 'print-sign-modal-label',
        'aria-hidden' => 'true',
    ]
);

$this->registerJs(<<<JS
    document.onkeydown = detectPrintMode;

    /**
     * 點擊CTRL+ALT+P進入列印模式
     */
    function detectPrintMode(event) {
        if (event.ctrlKey && event.altKey && event.key === 'p') {
            renderType('print');
        }
    }

    /**
     * 掛到 body，避免內容區 stacking context 讓 backdrop 蓋住按鈕（看起來全黑、點不到）
     */
    function printSignModalEl() {
        var el = document.getElementById('print-sign-modal');
        if (el && el.parentNode !== document.body) {
            document.body.appendChild(el);
        }
        return el;
    }

    function printSignModal() {
        var el = printSignModalEl();
        if (el && window.bootstrap && bootstrap.Modal) {
            return bootstrap.Modal.getOrCreateInstance(el, { backdrop: true, keyboard: true });
        }
        return null;
    }

    /**
     * 頁面顯示模式: print(列印模式)
     */
    function renderType(type) {
        if (type == 'print') {
            $('main').children().first().removeClass('container').addClass('container-fluid');
            $('.render-type, header, footer, .hide-print, #sign-area, #validate-area, .os-scrollbar-vertical, .show-print').toggle();
            var modal = printSignModal();
            if (modal) {
                modal.show();
            } else {
                $('#print-sign-modal').modal('show');
            }
        }
        else {
            $('main').children().first().removeClass('container-fluid').addClass('container');
            $('.render-type, header, footer, .hide-print, #sign-area, #validate-area, .os-scrollbar-vertical').toggle();
            var modal = printSignModal();
            if (modal) {
                modal.hide();
            }
        }
    }

    $(document).on('click', '#print-sign-modal [data-sign-layout]', function () {
        var layout = $(this).data('sign-layout');
        switch (layout) {
            case 'chair-monitor-v':
                $('#sign-area h3').html(`
                    <span class='signature-title'>監票人：</span><div class='signature-area'></div><br>
                    <span class='signature-title'>主　席：</span><div class='signature-area'></div>
                `);
                break;
            case 'chair-monitor-h':
                $('#sign-area h3').html(`
                    <div class="row pb-4">
                        <div class="col-8">
                            <span class='signature-title'>監票人：</span>
                        </div>
                        <div class="col-4">
                            <span class='signature-title'>主　席：</span>
                        </div>
                    </div>
                `);
                break;
            case 'chair':
                $('#sign-area h3').html(`<span class='signature-title'>主　席：</span><div class='signature-area'></div>`);
                break;
            case 'monitor':
                $('#sign-area h3').html(`<span class='signature-title'>監票人：</span><div class='signature-area'></div>`);
                break;
            case 'none':
                $('#sign-area h3').text('');
                break;
        }
        var modal = printSignModal();
        if (modal) {
            modal.hide();
        } else {
            $('#print-sign-modal').modal('hide');
        }
        appDialog.info('ESC或重新整理退出列印模式', '列印模式');
    });

    /**
     * 退出列印模式
     */
    function exitRenderPrint(e) {
        if (e.key === 'Escape' && $('main').children().first().hasClass('container-fluid')) {
            renderType('view');
        }
    }
    $(document).keyup(exitRenderPrint);
JS
, $this::POS_END);

$this->registerCss(<<<CSS
    /* 簽名區域 */
    .signature-title {
        padding: 10px 0 10px 0;
        background-color: #fff;
    }
    .signature-area {
        width: 100%;
        border-bottom: 1px solid black;
    }
    /* 蓋過內容區 stacking context；按鈕維持可點、不被列印頁 CSS 染成黑色 */
    #print-sign-modal {
        z-index: 2000;
    }
    #print-sign-modal .modal-dialog {
        pointer-events: auto;
        z-index: 2001;
    }
    #print-sign-modal .btn-print-sign {
        pointer-events: auto;
        color: #fff !important;
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
    }
CSS
);
