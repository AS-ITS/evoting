<?php

use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

// Define $isGuest if not already defined (needed for functional tests)
if (!isset($isGuest)) {
    $isGuest = Yii::$app->user->isGuest;
}

if (!$isGuest) {
    // 切換角色
    echo $this->render('@views/layouts/_switch-role');
    NavBar::begin([
        'options' => [
            'class' => 'navbar navbar-expand-lg fixed-bottom navbar-dark bg-dark fw-bold py-0',
        ],
    ]);
    echo Nav::widget([
        'items' => [
            [
                'label' => Yii::t('app','投票登出'),
                'url' => ['site/logout', 'type' => 'anon'],
                'linkOptions' => ['class' => 'js-csrf-sync-logout', 'data-method' => 'post'],
                'visible' => !Yii::$app->anon->isGuest,
            ],
            [
                'label' => '開票作業',
                'url' => ['ballot-work/index'],
                'visible' => Yii::$app->user->can('ballotWorkIndex'),
            ],
            [
                'label' => '投票管理',
                'url' => ['elect/index'],
                'visible' => Yii::$app->user->can('voteManag'),
            ],
            [
                'label' => '群組管理',
                'url' => ['group/index'],
                'visible' => Yii::$app->user->can('va') || Yii::$app->user->can('ga') || Yii::$app->user->can('gm'),
            ],
            [
                'label' => '網站管理',
                'url' => ['manage/setting'],
                'visible' => Yii::$app->user->can('sa'),
            ],
            // ----- 修改密碼 -----
            [
                'label' => Yii::t('app','修改密碼'),
                'url' => ['/auth/change-password'],
                'visible' => !$isGuest,
            ],
            // ----- 登入 / 登出 -----
            [
                'label' => $isGuest?Yii::t('app','管理員登入'):Yii::t('app','管理員登出'),
                'url' => [ ($isGuest? '/auth/login':'/auth/logout'), 'type' => 'user'],
                'linkOptions' => $isGuest?[]:['class' => 'js-csrf-sync-logout', 'data-method' => 'post'],
                'visible' => !$isGuest,
            ],
            // ----- 切換角色 -----
            [
                'label' => '切換角色',
                'url' => 'javascript:void(0);',
                'linkOptions' => [
                    'data' => [
                        'bs-toggle' => 'modal',
                        'bs-target' => '#switch-role'
                    ]
                ],
                'visible' => !$isGuest && Yii::$app->user->identity->isSwitchRoles(),
            ],
        ],
        'options' => ['class' => 'navbar-nav mx-auto'],
    ]);
    NavBar::end();
}