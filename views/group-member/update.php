<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = '群組成員更新';

echo \app\widgets\Alert::widget();

$form = ActiveForm::begin([
    'action' => \yii\helpers\Url::to(['edit', 'groupId' => $member->groupId, 'cn' => $member->cn])
]);
$member->scenario = 'update';
?>
<div class="card">
    <div class="card-header">
        <?= Html::encode($memberInfo[0]['name'] ?? $member->cn) ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg">
                <?= $form->field($member, 'isWrite')->dropDownList(Yii::$app->params['ct.group.writeAry']) ?>
            </div>
            <div class="col-lg">
                <?= $form->field($member, 'isOwner')->dropDownList(Yii::$app->params['ct.group.ownerAry']) ?>
            </div>
        </div>
    </div>
    <div class="card-footer bg-transparent text-center">
        <?=Html::submitButton('修改', ['class' => 'btn btn-sm btn-primary'])?>
        <?=Html::a('返回列表', ['index', 'groupId' => $member->groupId, 'cn' => $member->cn], ['class' => 'btn btn-sm btn-secondary me-3']) ?>
    </div>
</div>
<?php
ActiveForm::end();
