<?php

$def = [
    'isShow' => '1',
    'isLogin' => '0',
    'isParty' => '0',
    'sort' => '0',
    'showFieldSort' => 'instName,Name,ballotCounts,elected',
    'showElectedStatus' => null,
];

return [
    'AnonPartyTest' => array_merge(['voteID' => 'AnonPartyTest'], $def),
    'AnonNoPartyTest' => array_merge(['voteID' => 'AnonNoPartyTest'], $def),
    'NoAuthVoteTest' => array_merge(['voteID' => 'NoAuthVoteTest'], $def),
];
