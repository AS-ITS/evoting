<?php

use yii\helpers\Url;
use yii\bootstrap5\Html;
use yii\bootstrap5\Modal;

// 切換角色
Modal::begin([
	'id' => 'switch-role',
    'title' => '切換角色',
    'options' => ['class' => 'bg-dark'],
    'clientOptions' => ['backdrop' => false],
]);
$roles = !Yii::$app->user->isGuest ? Yii::$app->user->identity->getRoles() : [];

echo Html::beginTag('div', ['class' => 'list-group text-center']);
foreach ($roles as $role) {
    if (Yii::$app->authManager->getRole($role)) {
        $currentApRole = Yii::$app->user->identity->getRole() == $role ? 'list-group-item-info disabled active' : '';
        echo Html::a(
            Yii::$app->params['ct.apRoles'][$role]." ($role)", 
            Url::to(['/site/switch-role', 'role' => $role]),
            [
                'class' => 'list-group-item list-group-item-action '.$currentApRole 
            ]
        );
    }
}
echo Html::endTag('div');

Modal::end();