<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

/** @var bool $reauthRequired */
/** @var bool $useTotp */

$this->title = '同步 RBAC 權限';
$this->params['secNavType'] = 'manage';

echo \app\widgets\Alert::widget();
?>

<div class="container" style="max-width: 720px;">
    <h2 class="text-center"><?= Html::encode($this->title) ?></h2>
    <hr>

    <div class="alert alert-danger">
        <strong>警告：</strong>此操作將執行 <code>$auth-&gt;removeAll()</code> 並重建整個 RBAC 權限表。
        誤觸可能導致所有角色權限異常。僅在權限結構變更或修復時使用。
    </div>

    <?php $form = ActiveForm::begin(['method' => 'post', 'action' => ['site/sync-role-per']]); ?>

    <?= $this->render('_sensitive_reauth', [
        'reauthRequired' => $reauthRequired,
        'useTotp' => $useTotp,
    ]) ?>

    <div class="text-end mt-3">
        <?= Html::a('取消', ['users/index'], ['class' => 'btn btn-secondary me-2']) ?>
        <?= Html::submitButton('確認同步 RBAC', [
            'class' => 'btn btn-danger',
            'data' => ['confirm' => '最後確認：將重置全部 RBAC 權限，確定繼續？'],
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
