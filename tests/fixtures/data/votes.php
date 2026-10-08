<?php

$openStart = date('Y-m-d H:i:s');

return [
    // 匿名分組
    'AnonPartyTest' => [
        'voteID' => 'AnonPartyTest',

        'Name' => '測試分組匿名投票',
        'NameE' => 'Test Party Anon',
        'creator' => 'admin',

        'openStart' => $openStart,
        'openEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+24 hour')),
        'verifyStart' => date('Y-m-d H:i:s', strtotime($openStart . '+25 hour')),
        'verifyEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+26 hour')),

        'type' => '1',
        'partyOrNot' => '1',
        'addiCondition' => 'n',
        'isByParty' => '0',
        'active' => '1',
        'isFinish' => '0',
        'finishPage' => '1',
        'pattern' => 'meeting',
        'loginLayout' => 'meeting',
        'themeColor' => '',
        'sort' => '1',
        'session' => '',
        'shortUrl' => 'test1',
        'groupId' => NULL,
        'authBeforeDetail' => '0',
        'round' => '1',
        'candiConfig' => '2',
        
        'hosted' => '主辦單位',
        'hostedE' => '主辦單位(英)',
        'contact' => '聯絡人',
        'contactE' => '聯絡人(英)',
        'tel' => '聯絡電話',
        'email' => '聯絡信箱',

        'isBindVote' => '0',
        'bindWhichVote' => '',
        
        'notice' => '投票要點',
        'noticeE' => '投票要點(英)',
        'information' => '投票須知',
        'informationE' => '投票須知(英)',
        'candComment' => '候選人名單備註',
        'candCommentE' => '候選人名單備註(英)',
// 20250613 Add by Fisher 不可為null
        'skipDetail' => '0',
        'skipCheck' => '0',
        'isShow' => '1',  // 在首頁顯示
    ],

    // 匿名不分組 - 共用密碼
    'AnonNoPartyTest' => [
        'voteID' => 'AnonNoPartyTest',

        'Name' => '測試不分組匿名投票',
        'NameE' => 'Test No Party Anon',
        'creator' => 'admin',

        'openStart' => $openStart,
        'openEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+24 hour')),
        'verifyStart' => date('Y-m-d H:i:s', strtotime($openStart . '+25 hour')),
        'verifyEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+26 hour')),

        'type' => '1',
        'partyOrNot' => '0',
        'addiCondition' => 'n',
        'isByParty' => '0',
        'active' => '1',
        'isFinish' => '0',
        'finishPage' => '1',
        'pattern' => 'meeting',
        'loginLayout' => 'meeting',
        'sort' => '2',
        'session' => '',
        'shortUrl' => 'test2',
        'groupId' => NULL,
        'authBeforeDetail' => '0',
        'round' => '1',
        
        'hosted' => '主辦單位',
        'hostedE' => '主辦單位(英)',
        'contact' => '聯絡人',
        'contactE' => '聯絡人(英)',
        'tel' => '聯絡電話',
        'email' => '聯絡信箱',

        'isBindVote' => '0',
        'bindWhichVote' => '',
        'themeColor' => '',
        'candiConfig' => NULL,

        'notice' => '投票要點',
        'noticeE' => '投票要點(英)',
        'information' => '投票須知',
        'informationE' => '投票須知(英)',
        'candComment' => '候選人名單備註',
        'candCommentE' => '候選人名單備註(英)',
// 20250613 Add by Fisher 不可為null
        'skipDetail' => '0',
        'skipCheck' => '0',
        'isShow' => '1',  // 在首頁顯示

    ],

    // 表決投票 (TYPE_NO_AUTH='0') - 無須驗證
    'NoAuthVoteTest' => [
        'voteID' => 'NoAuthVoteTest',

        'Name' => '測試表決投票',
        'NameE' => 'Test No Auth Vote',
        'creator' => 'admin',

        'openStart' => $openStart,
        'openEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+24 hour')),
        'verifyStart' => date('Y-m-d H:i:s', strtotime($openStart . '+25 hour')),
        'verifyEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+26 hour')),

        'type' => '0',  // TYPE_NO_AUTH - 表決投票
        'partyOrNot' => '0',
        'addiCondition' => 'n',
        'isByParty' => '0',
        'active' => '1',
        'isFinish' => '0',
        'finishPage' => '1',
        'pattern' => 'meeting',
        'loginLayout' => 'meeting',
        'sort' => '3',
        'session' => '',
        'shortUrl' => 'test3',
        'groupId' => NULL,
        'authBeforeDetail' => '0',
        'round' => '1',

        'hosted' => '主辦單位',
        'hostedE' => 'Host Organization',
        'contact' => '聯絡人',
        'contactE' => 'Contact Person',
        'tel' => '02-12345678',
        'email' => 'test@example.com',

        'isBindVote' => '0',
        'bindWhichVote' => '',

        'notice' => '表決投票說明',
        'noticeE' => 'No Auth Vote Notice',
        'information' => '表決投票須知',
        'informationE' => 'No Auth Vote Information',
        'candComment' => '',
        'candCommentE' => '',
        'skipDetail' => '0',
        'skipCheck' => '0',
        'isShow' => '1',
    ]
];