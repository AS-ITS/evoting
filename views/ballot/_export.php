<?php
use yii\bootstrap5\Html;

/** @var string $voteID */
/** @var bool $reauthRequired */
/** @var bool $useTotp */
/** @var string|null $defaultType 預設匯出類型 */
/** @var string $collapseId collapse 元素 id */
/** @var string|null $returnUrl 匯出失敗時返回路徑 */
/** @var array<string, string> $contentQuestions questionID => 標題（封存單） */

$collapseId = $collapseId ?? 'collapseBallotExport';
$contentQuestions = $contentQuestions ?? [];
$this->registerJs("$('#{$collapseId}').collapse('hide');");
$defaultType = $defaultType ?? '';
$formId = 'ballotExportForm-' . preg_replace('/[^a-z0-9]/i', '', $collapseId);
?>
<div class="collapse" id="<?= Html::encode($collapseId) ?>">
    <div class="card card-body list-group-item-info mb-3">
        <?= Html::beginForm(['/ballot/export', 'voteID' => $voteID], 'post', ['id' => $formId]) ?>
        <?php if (!empty($returnUrl)): ?>
            <?= Html::hiddenInput('returnUrl', $returnUrl) ?>
        <?php endif; ?>
        <div class="row align-items-center">
            <div class="col-lg-12 text-start fw-bold">選票統計匯出 / 投票結果封存單</div>
        </div>
        <div class="row mt-2">
            <div class="col-lg-6">
                <?= Html::label('匯出格式', 'ballotExportType-' . $collapseId, ['class' => 'form-label']) ?>
                <?= Html::dropDownList('type', $defaultType, [
                    '' => '全部欄位 (csv)',
                    '1' => '無 IP、修改者、修改時間 (csv)',
                    'content' => '投票結果封存單 (列印)',
                ], [
                    'class' => 'form-select ballot-export-type',
                    'id' => 'ballotExportType-' . $collapseId,
                    'data-collapse-id' => $collapseId,
                ]) ?>
            </div>
            <div class="col-lg-6 d-none ballot-export-question-row" id="ballotExportQuestionRow-<?= Html::encode($collapseId) ?>">
                <?= Html::label('問題', 'ballotExportQuestion-' . $collapseId, ['class' => 'form-label']) ?>
                <?= Html::dropDownList('questionID', '', $contentQuestions, [
                    'class' => 'form-select',
                    'id' => 'ballotExportQuestion-' . $collapseId,
                    'prompt' => '請選擇問題',
                ]) ?>
            </div>
        </div>
        <p class="text-muted small mt-2 mb-0">匿名投票時「投票者」含明文密碼（紙本封存用）。封存單將於新分頁開啟以便列印。</p>
        <?php if (!empty($reauthRequired)): ?>
            <?= $this->render('@app/views/site/_sensitive_reauth', [
                'reauthRequired' => true,
                'useTotp' => !empty($useTotp),
            ]) ?>
        <?php endif; ?>
        <div class="text-end mt-2">
            <?= Html::hiddenInput('exportAcknowledged', '1') ?>
            <?= Html::submitButton('匯出 CSV', [
                'class' => 'btn btn-info ballot-export-submit',
                'id' => 'ballotExportSubmit-' . $collapseId,
                'data' => [
                    'confirm' => '將匯出含明文密碼的選票資料，此操作會記錄於操作日誌。確定繼續？',
                ],
            ]) ?>
        </div>
        <?= Html::endForm() ?>
    </div>
</div>
<?php
$this->registerJs(<<<'JS'
document.querySelectorAll('.ballot-export-type').forEach(function (typeEl) {
    var collapseId = typeEl.getAttribute('data-collapse-id');
    var questionRow = document.getElementById('ballotExportQuestionRow-' + collapseId);
    var submitBtn = document.getElementById('ballotExportSubmit-' + collapseId);
    var form = typeEl.closest('form');
    var questionSelect = document.getElementById('ballotExportQuestion-' + collapseId);

    function syncExportForm() {
        var isContent = typeEl.value === 'content';
        if (questionRow) {
            questionRow.classList.toggle('d-none', !isContent);
        }
        if (questionSelect) {
            questionSelect.required = isContent;
        }
        if (submitBtn) {
            submitBtn.textContent = isContent ? '開啟封存單' : '匯出 CSV';
        }
        if (form) {
            if (isContent) {
                form.setAttribute('target', '_blank');
            } else {
                form.removeAttribute('target');
            }
        }
    }

    typeEl.addEventListener('change', syncExportForm);
    syncExportForm();
});
JS
, \yii\web\View::POS_READY);
