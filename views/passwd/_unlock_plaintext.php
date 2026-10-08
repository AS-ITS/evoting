<?php
use yii\bootstrap5\Html;

/** @var string $voteID */
/** @var string $returnUrl */
/** @var string $unlockAction 例如 passwd/unlock-plaintext */
/** @var string $lockAction */
/** @var bool $plaintextUnlocked */
/** @var int|null $plaintextUnlockRemaining */
/** @var bool $reauthRequired */
/** @var bool $useTotp */
/** @var bool $buttonInToolbar 按鈕由工具列嵌入時設 true */

$this->registerJs("$('#collapseUnlockPlaintext').collapse('hide');");

if ($plaintextUnlocked && $reauthRequired): ?>
    <div class="alert alert-success py-2 mb-2">
        密碼明文已解鎖
        <?php if ($plaintextUnlockRemaining !== null && $plaintextUnlockRemaining > 0): ?>
            （約 <?= (int) ceil($plaintextUnlockRemaining / 60) ?> 分鐘後自動鎖定）
        <?php endif; ?>
        <?= Html::beginForm([$lockAction, 'voteID' => $voteID], 'post', ['class' => 'd-inline ms-2']) ?>
            <?= Html::hiddenInput('returnUrl', $returnUrl) ?>
            <?= Html::submitButton('立即鎖定', ['class' => 'btn btn-sm btn-outline-secondary']) ?>
        <?= Html::endForm() ?>
    </div>
<?php elseif ($reauthRequired && empty($buttonInToolbar)): ?>
    <?= Html::a('顯示明文', '#collapseUnlockPlaintext', [
        'class' => 'btn btn-outline-danger me-2',
        'data-bs-toggle' => 'collapse',
        'aria-expanded' => 'false',
        'aria-controls' => 'collapseUnlockPlaintext',
    ]) ?>
<?php endif; ?>

<?php if ($reauthRequired && !$plaintextUnlocked): ?>
    <div class="collapse" id="collapseUnlockPlaintext">
        <div class="card card-body border-danger mb-3">
            <?= Html::beginForm([$unlockAction, 'voteID' => $voteID], 'post') ?>
            <?= Html::hiddenInput('returnUrl', $returnUrl) ?>
            <p class="mb-2 text-muted small">解鎖後列表將顯示密碼明文（時效內有效），此操作會記錄於操作日誌。</p>
            <?= $this->render('@app/views/site/_sensitive_reauth', [
                'reauthRequired' => true,
                'useTotp' => $useTotp,
            ]) ?>
            <div class="text-end mt-2">
                <?= Html::submitButton('確認解鎖', ['class' => 'btn btn-danger']) ?>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>
<?php endif; ?>
