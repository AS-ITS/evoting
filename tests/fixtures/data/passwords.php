<?php

use app\models\Passwd;

/**
 * 密碼 Fixture 資料
 *
 * AnonPartyTest: 使用 party='N' (匹配 candiData 的 party)
 * AnonNoPartyTest: 使用 party='def' (匹配 candiData 的 party)
 */
$passwords = [];
$passwd = new Passwd();
$id = 1;

// AnonPartyTest - 所有候選人都在 party='N'
for ($i=1; $i < 101; $i++) {
    $passwords[] = [
        'id' => $id++,
        'voteID' => 'AnonPartyTest',
        'sn' => $i,
        'passwd' => $passwd->encrypt("testN{$i}"),
        'party' => 'N',
        'status' => '1',
        'dtrack' => '1',
        'voted' => '0',
    ];
}

// AnonNoPartyTest - 所有候選人都在 party='def'
for ($i=1; $i < 101; $i++) {
    $passwords[] = [
        'id' => $id++,
        'voteID' => 'AnonNoPartyTest',
        'sn' => $i,
        'passwd' => $passwd->encrypt("testdef{$i}"),
        'party' => 'def',
        'status' => '1',
        'dtrack' => '1',
        'voted' => '0',
    ];
}

return $passwords;