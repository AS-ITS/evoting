<?php

$parties = ['0', '1', '2', '3'];
$questionIDs = ['1', '2', '3', '4', '5'];
$selCandiIDs = [
    '1' => [1, 16],
    '2' => [17, 29],
    '3' => [30, 44],
    '4' => [45, 53],
    '5' => [54, 56],
];
$ballotsSelected = [];

// 投票腳本 - 隨機產生
$script = [];
$scriptID = 1;
foreach ($parties as $key => $party) {
    $minCandiID = $key * 20 + 1;
    $maxCandiID = ($key + 1) * 20;
    for ($i=1; $i < 11; $i++) { 
        foreach ($questionIDs as $questionID) {
            // 分組問題
            $script[$party][$scriptID][$questionID]['selCandiID'] = $selCandiIDs[$questionID];
            // $script[$party][$scriptID][$questionID]['questionID'] = $questionID;
        }
        
        // 共同問題
        // if ($party != 'def') {
        //     $script['N'][$scriptID]['selCandiID'] = random_int(26, 28);
        //     $script['N'][$scriptID]['questionID'] = '5';
        // }
        $scriptID++;
    }
}

$ballotID = 1;
$scriptID = 1;
foreach ($parties as $party) {
    if ($party === 'def') {
        $voteID = 'AnonNoPartyTest';
        $ballotID = 1;
    }
    else {
        $voteID = 'AnonPartyTest';
    }
    for ($i=1; $i < 11; $i++) {
        foreach ($questionIDs as $questionID) {
            $start = $script[$party][$scriptID][$questionID]['selCandiID'][0];
            $end = $script[$party][$scriptID][$questionID]['selCandiID'][1];
            for ($j=$start; $j <= $end; $j++) { 
                $ballotsSelected[] = [
                    'voteID' => $voteID,
                    'ballotID' => $ballotID,
                    'selCandiID' => $j,
                    'party' => 'N',
                    'questionID' => $questionID,
                    'jobLctn' => '1',
                    'isValiable' => '1',
                ];
            }
        }
        
        // 共同問題
        // if ($party != 'def') {
        //     $ballotsSelected[] = [
        //         'voteID' => $voteID,
        //         'ballotID' => $ballotID,
        //         'selCandiID' => $script['N'][$scriptID]['selCandiID'],
        //         'party' => 'N',
        //         'questionID' => $script['N'][$scriptID]['questionID'],
        //         'jobLctn' => '1',
        //         'isValiable' => '1',
        //     ];
        // }
        $ballotID++;
        $scriptID++;
    }
}

return $ballotsSelected;