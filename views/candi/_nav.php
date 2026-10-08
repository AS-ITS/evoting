<?php
use yii\bootstrap5\Nav;

echo Nav::widget([
    'items' => [
        [
            'label' => '候選名單管理',
            'url' => ['candi/data', 'voteID' => $voteID, 'questionID' => $questionID],
        ],
        [
            'label' => '候選名單配置',
            'url' => ['candi/config', 'voteID' => $voteID, 'questionID' => $questionID],
        ],
    ],
    'options' => ['class' =>'nav nav-pills justify-content-center mt-4 mb-3'],
]);