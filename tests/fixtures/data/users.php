<?php

/**
 * Users 測試資料
 *
 * 密碼使用 Yii2 的 password_hash 格式
 * 測試密碼統一為: TestPass123
 *
 * 注意：不要在這裡定義 gm_* 用戶，因為 GroupVotePermissionTest 會動態建立這些用戶
 */
return [
    // 系統管理員 (sa)
    'admin' => [
        'cn' => 'admin',
        'name' => '系統管理員',
        'roles' => 'sa',
        'password' => password_hash('TestPass123', PASSWORD_DEFAULT),
    ],
    // 測試用系統管理員 (sa)
    'test_admin' => [
        'cn' => 'test_admin',
        'name' => '測試管理員',
        'roles' => 'sa',
        'password' => password_hash('TestPass123', PASSWORD_DEFAULT),
    ],
    // 投票管理員 (va)
    'vote_admin' => [
        'cn' => 'voteadmin',
        'name' => '投票管理員',
        'roles' => 'va',
        'password' => password_hash('TestPass123', PASSWORD_DEFAULT),
    ],
    // 測試用投票管理員 (va)
    'test_va' => [
        'cn' => 'test_va',
        'name' => '測試投票管理員',
        'roles' => 'va',
        'password' => password_hash('TestPass123', PASSWORD_DEFAULT),
    ],
    // 群組管理員 (ga)
    'test_ga' => [
        'cn' => 'test_ga',
        'name' => '測試群組管理員',
        'roles' => 'ga',
        'password' => password_hash('TestPass123', PASSWORD_DEFAULT),
    ],
    // 額外投票管理員（驗收測試用）
    'test_va2' => [
        'cn' => 'test_va2',
        'name' => '測試投票管理員2',
        'roles' => 'va',
        'password' => password_hash('TestPass123', PASSWORD_DEFAULT),
    ],
    // 群組成員 (gm) - 用於權限測試
    'test_gm' => [
        'cn' => 'test_gm',
        'name' => '測試群組成員',
        'roles' => 'gm',
        'password' => password_hash('TestPass123', PASSWORD_DEFAULT),
    ],
];
