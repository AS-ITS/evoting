<?php
// https://github.com/FortAwesome/Font-Awesome/issues/3778
use rmrevin\yii\fontawesome\FAS;
use app\widgets\FontSizeDropdown;
use app\actions\DataTableModifierAction;

$isGuest = Yii::$app->user->isGuest;
$isSysAdmin = !$isGuest && Yii::$app->user->identity->inUserRole('sa');
$actionId = Yii::$app->controller->action->id;
$controllerId = Yii::$app->controller->id;
if ($actionId == 'data-table-modifier') {
    $this->params['secNavType'] = 'data-table-modifier';
}

$this->params['navCustomHeaderCss'] = true; // Custom Header Css: true / false
$this->params['navColorSchemes'] = 'dark'; // Schemes: dark / light
$this->params['navBackGround'] = 'bg-dark'; // Background: https://getbootstrap.com/docs/4.5/utilities/colors/#background-color
$this->params['navPlacement'] = 'fixed-top'; // Placement: fixed-top / fixed-bottom
$this->params['navFirst'] = [
    [
        'label' => FAS::icon('home', ['class' => 'me-1']).Yii::t('app','主頁'),
        'url' => ['vote/index'],
        'active' => in_array("{$controllerId}/{$actionId}",[
            'vote/index', // 投票項目 >> 列表
            'vote/vote-detail', // 投票項目 >> 內容
        ]),
        // 'visible' => $isGuest,
    ],
    [
        'label' => FAS::icon('poll', ['class' => 'me-1']).Yii::t('app','投票結果'),
        'url' => ['vote/result-list'],
        'active' => in_array("{$controllerId}/{$actionId}",[
            'vote/result-list', // 投票結果 >> 列表
            'vote/result', // 投票結果 >> 內容
        ]),
        // 'visible' => $isGuest,
    ],
];
$this->params['navSecond'] = [
    [
        'label' => Yii::t('app','投票登出'),
        'url' => ['site/logout', 'type' => 'anon'],
        'linkOptions' => ['class' => 'js-csrf-sync-logout', 'data-method' => 'post'],
        'visible' => !Yii::$app->anon->isGuest,
    ],
    [
        'label' => '開票作業',
        'url' => ['ballot-work/index'],
        'active' => in_array($controllerId,[
            'ballot-work',
        ]) || in_array("{$controllerId}/{$actionId}", [
            'ballot-work/index', // 開票作業
        ]),
        'visible' => Yii::$app->user->can('ballotWorkIndex'),
    ],
    [
        'label' => '投票管理',
        'url' => ['elect/index'],
        'active' => in_array($controllerId,[
            'candi', 'passwd', 'ballot', 'question'
        ]) || in_array("{$controllerId}/{$actionId}", [
            'round/index', // 投票管理
            'elect/index', // 投票管理
            'elect/process', // 投票管理 >> 流程
            'elect/edit-vote', // 投票管理 >> 編輯
            'count/index', // 投票管理 >> 計票
            'count/result', // 投票管理 >> 開票
            'result/index', // 投票管理 >> 結果
            'elect/reset-vote', // 投票管理 >> 重啟
        ]),
        'visible' => Yii::$app->user->can('voteManag'),
    ],
    [
        'label' => '群組管理',
        'url' => ['group/index'],
        'active' => in_array($controllerId,[
            'group',
        ]) || in_array("{$controllerId}/{$actionId}",[
            'group/index', // 群組管理
        ]),
        'visible' => Yii::$app->user->can('va') || Yii::$app->user->can('ga') || Yii::$app->user->can('gm'),
    ],
    [
        'label' => '網站管理',
        'url' => ['manage/setting'],
        'active' => in_array($controllerId,[
            'manage',
        ]) || in_array("{$controllerId}/{$actionId}",[
            'manage/index', // 網站管理
        ]),
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
        // 'icon' => FAS::icon($isGuest?'sign-in-alt':'sign-out-alt'),
        'label' => $isGuest?Yii::t('app','管理員登入'):Yii::t('app','管理員登出'),
        'url' => [($isGuest? '/auth/login' : '/auth/logout'), 'type'=>'user'],
        'linkOptions' => $isGuest?[]:['class' => 'js-csrf-sync-logout', 'data-method' => 'post'],
        'visible' => !$isGuest,
    ],
    // ----- 切換角色 -----
    [
        'label' => FAS::icon('user-edit'),
        'url' => 'javascript:void(0);',
        'linkOptions' => [
            'data' => [
                'bs-toggle' => 'modal',
                'bs-target' => '#switch-role'
            ]
        ],
        'visible' => !$isGuest && Yii::$app->user->identity->isSwitchRoles(),
    ],
    // ----- 切換語言 -----
    [
        'label' => Yii::t('app','EN'),
        'url' => ['site/change-language'],
        // 'visible' => $isGuest,
    ],
    // ----- 切換文字大小 -----
    FontSizeDropdown::widget(),
];

// 共用二級導航
if(isset($this->params['secNavType']))
{
    /**
     * use `$this->params['secNavType'] = 'vote';`
     */
    switch($this->params['secNavType'])
    {
        case 'ballotWork':
            $voteID = Yii::$app->request->get('voteID');
            $this->params['navItems'] = [
                [
                    'label' => Yii::t('app', '開票'),
                    'url' => ['ballot-work/setting', 'voteID' => $voteID],
                    'activeAction' => 'ballot-work/setting',
                ],
                [
                    'label' => Yii::t('app', '檢票'),
                    'url' => ['ballot-work/status', 'voteID' => $voteID],
                    'activeAction' => 'ballot-work/status',
                ],
                [
                    'label' => Yii::t('app', '計票'),
                    'url' => ['ballot-work/count', 'voteID' => $voteID],
                    'activeAction' => 'ballot-work/count',
                ],
                [
                    'label' => Yii::t('app', '開票結果'),
                    'url' => ['ballot-work/result', 'voteID' => $voteID],
                    'activeAction' => 'ballot-work/result',
                ],
            ];
            break;
        case 'vote':
            $voteID = Yii::$app->request->get('voteID');
            $this->params['navItems'] = [
                [
                    'label' => '流程',
                    'url' => ['elect/process', 'voteID'=>$voteID],
                    'activeAction' => 'elect/process',
                ],
                [
                    'label' => '輪次',
                    'url' => ['round/index', 'voteID'=>$voteID],
                    'active' => $controllerId == 'round',
                ],
                [
                    'label' => '編輯',
                    'url' => ['elect/edit-vote', 'voteID'=>$voteID],
                    'activeAction' => 'elect/edit-vote',
                ],
                [
                    'label' => '問題',
                    'url' => ['question/index', 'voteID'=>$voteID],
                    'active' => $controllerId == 'question',
                ],
                [
                    'label' => '候選',
                    'url' => ['candi/index', 'voteID'=>$voteID],
                    'active' => $controllerId == 'candi',
                ],
                [
                    'label' => '密碼',
                    'url' => ['passwd/index', 'voteID'=>$voteID],
                    'active' => $controllerId == 'passwd',
                ],
                [
                    'label' => '選票',
                    'url' => ['ballot/index', 'voteID'=>$voteID],
                    'active' => $controllerId == 'ballot',
                ],
                [
                    'label' => '計票',
                    'url' => ['count/index', 'voteID'=>$voteID],
                    // 'activeAction' => 'count/index',
                    'active' => $controllerId == 'count' && $actionId != 'result',
                ],
                [
                    'label' => '開票',
                    'url' => ['count/result', 'voteID'=>$voteID],
                    'activeAction' => 'count/result',
                ],
                [
                    'label' => '結果',
                    'url' => ['result/index', 'voteID'=>$voteID],
                    'active' => $controllerId == 'result',
                ],
                [
                    'label' => '重啟',
                    'url' => ['elect/reset-vote','voteID'=>$voteID],
                    'activeAction' => 'elect/reset-vote',
                ],
            ];
            break;
        case 'group':
            $groupID = Yii::$app->request->get('groupId');
            $this->params['navItems'] = [
                [
                    'label' => '基本資料',
                    'url' => ['group/view-base', 'groupId' => $groupID],
                    'activeAction' => 'group/view-base',
                ],
                [
                    'label' => '成員管理',
                    'url' => ['group-member/index', 'groupId' => $groupID],
                    'activeAction' => 'group/group-member/index',
                ],
                [
                    'label' => '投票管理',
                    'url' => ['group/view-vote', 'groupId' => $groupID],
                    'activeAction' => 'group/view-vote',
                ],
            ];
            break;
        case 'manage':
            $this->params['navItems'] = [
                [
                    'label' => '儀表板',
                    'url' => ['manage/index'],
                    'activeAction' => 'manage/index',
                    'visible' => false
                ],
                [
                    'label' => '網站設定',
                    'url' => ['manage/setting'],
                    'activeAction' => 'manage/setting',
                ],
                [
                    'label' => '清除登入狀態',
                    'url' => ['manage/logins'],
                    'activeAction' => 'manage/logins',
                ],
                [
                    'label' => 'LOG',
                    'url' => ['manage/log'],
                    'activeAction' => 'manage/log',
                ],
                [
                    'label' => '使用者管理',
                    'url' => ['users/index'],
                    'active' => $controllerId == 'users',
                ],
                [
                    'label' => '雙因素驗證',
                    'url' => ['users/totp'],
                    'active' => $controllerId == 'users' && Yii::$app->controller->action->id === 'totp',
                ],
            ];
            break;
        case 'data-table-modifier':
            switch(Yii::$app->request->get('action'))
            {
                case DataTableModifierAction::ACTION_UPDATE:
                    $submitName = '修改';
                    $this->params['navItems'] = [
                        [
                            'label' => '資料刪除',
                            'url' => [ // 資料刪除連結
                                'action' => DataTableModifierAction::ACTION_DELETE,
                                'tableName' => Yii::$app->request->get('tableName'),
                                'sid' => Yii::$app->request->get('sid'),
                                'id' => Yii::$app->request->get('id'),
                            ],
                            'linkOptions' => [
                                'class' => 'text-danger',
                                'data' => [
                                    'bs-confirm' => '請再次確認要刪除該筆資料？',
                                    'method' => 'post',
                                ],
                            ],
                        ],
                    ];
                    break;
            }
            break;
    }
    
}

$this->beginBlock('footer'); ?>
<hr>
<p class="text-center hide-print">
    <?= \yii\helpers\Html::encode(\app\components\Branding::copyright()) ?>
</p>
<?php $this->endBlock(); ?>