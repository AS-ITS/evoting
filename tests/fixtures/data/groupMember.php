<?php

/**
 * GroupMember 測試資料
 *
 * isWrite: 可否修改群組投票 (Y/N)
 * isOwner: 可否修改群組成員 (Y/N)
 *
 * 注意：
 * 1. gm_* 用戶不存在於 users fixture 中，由 GroupVotePermissionTest 動態建立
 * 2. fgm_* 用戶用於 FormGroupMemberTest 測試，同樣不在 users fixture 中
 */
return [
    // ========================================
    // GroupVotePermissionTest 使用的成員
    // ========================================
    // 群組1 - GM 使用者 (完整權限)
    'gm_test' => [
        'groupId' => 1,
        'cn' => 'gm_test',
        'isWrite' => 'Y',
        'isOwner' => 'Y',
    ],
    // 群組1 - GM 使用者 (僅投票管理)
    'gm_write_only' => [
        'groupId' => 1,
        'cn' => 'gm_write_only',
        'isWrite' => 'Y',
        'isOwner' => 'N',
    ],
    // 群組1 - GM 使用者 (僅成員管理)
    'gm_owner_only' => [
        'groupId' => 1,
        'cn' => 'gm_owner_only',
        'isWrite' => 'N',
        'isOwner' => 'Y',
    ],
    // 群組1 - GM 使用者 (僅查看)
    'gm_view_only' => [
        'groupId' => 1,
        'cn' => 'gm_view_only',
        'isWrite' => 'N',
        'isOwner' => 'N',
    ],
    // 群組2 - GM 使用者
    'gm_group2' => [
        'groupId' => 2,
        'cn' => 'gm_group2',
        'isWrite' => 'Y',
        'isOwner' => 'Y',
    ],

    // ========================================
    // FormGroupMemberTest / FormGroupTest 使用的成員
    // ========================================
    'fgm_member1' => [
        'groupId' => 1,
        'cn' => 'fgm_member1',
        'isWrite' => 'Y',
        'isOwner' => 'Y',
    ],
    'fgm_member2' => [
        'groupId' => 1,
        'cn' => 'fgm_member2',
        'isWrite' => 'Y',
        'isOwner' => 'N',
    ],
    'fgm_member3' => [
        'groupId' => 2,
        'cn' => 'fgm_member3',
        'isWrite' => 'N',
        'isOwner' => 'Y',
    ],
];
