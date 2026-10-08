<?php

$parties = ['0', '1', '2', '3'];
$ballots = [];
$creator = 1;
// 匿名分組
$ballotID = 1;
foreach ($parties as $party) {
    for ($i=1; $i < 11; $i++) {
        $ballots[] = [
            'voteID' => 'AnonPartyTest',
            'round' => '1',
            'ballotID' => $ballotID,
            'party' => $party,
            'isAdminAdd' => '0',
            'ip' => '127.0.0.1',
            'creator' => $creator,
            'modifier' => $creator,  // 新增 modifier 欄位
            'insTime' => date('Y-m-d H:i:s'),
            'updTime' => date('Y-m-d H:i:s'),
        ];
        $ballotID++;
        $creator++;
    }
}
$ballots[0]['isAdminAdd'] = '1';

// 匿名不分組
// $ballotID = 1;
// for ($i=1; $i < 11; $i++) {
//     $ballots[] = [
//         'voteID' => 'AnonNoPartyTest',
//         'round' => '1',
//         'ballotID' => $ballotID,
//         'party' => 'def',
//         'isAdminAdd' => '0',
//         'ip' => '127.0.0.1',
//         'creator' => $creator,
//         'modifier' => $creator,  // 新增 modifier 欄位
//         'insTime' => date('Y-m-d H:i:s'),
//         'updTime' => date('Y-m-d H:i:s'),
//     ];
//     $ballotID++;
//     $creator++;
// }

return $ballots;