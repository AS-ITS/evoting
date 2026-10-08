<?php

$parties = ['數理科學組', '工程科學組', '生命科學組', '人文及社會科學組'];
$partiesE = [
    'Division of Mathematics and Physical Sciences',
    'Division of Engineering Sciences',
    'Division of Life Sciences',
    'Division of Humanities and Social Sciences',
    'N' => 'Common Questions',
];

// 匿名分組
$questions = [];
foreach ($parties as $key => $party) {
    $questions[] = [
        'questionID' => $key + 1,
        'voteID' => 'AnonPartyTest',
        'round' => 1,
        'party' => "N",
        'title' => $parties[$key],
        'titleE' => $partiesE[$key],
        'confirmTitle' => '自定義未圈選顯示名稱',
        'confirmTitleE' => '自定義未圈選顯示名稱(英)',
        'description' => '描述',
        'descriptionE' => '英文描述',
        'ruleText' => '<b>自定義投票規則文字</b>',
        'ruleTextE' => '<b>自定義投票規則文字(英)</b>',
        'numBallots' => '20',
        'leastNumBallots' => '0',
        'maxElect' => '1',
        'numOfKeep' => '1',
        'numFemaleKeep' => NULL,
        'population' => '100'
    ];
}

$questions[] = [
    'questionID' => 5,
    'voteID' => 'AnonPartyTest',
    'round' => 1,
    'party' => 'N',
    'title' => '名譽院士候選人',
    'titleE' => 'Candidate(s) of the Honorary Academicians',
    'confirmTitle' => '自定義未圈選顯示名稱',
    'confirmTitleE' => '自定義未圈選顯示名稱(英)',
    'description' => '描述',
    'descriptionE' => '英文描述',
    'ruleText' => '<b>自定義投票規則文字</b>',
    'ruleTextE' => '<b>自定義投票規則文字(英)</b>',
    'numBallots' => '3',
    'leastNumBallots' => '0',
    'maxElect' => '1',
    'numOfKeep' => '1',
    'numFemaleKeep' => NULL,
    'population' => '0'
];

// 匿名不分組
$questions[] = [
    'questionID' => 6,
    'voteID' => 'AnonNoPartyTest',
    'round' => 1,
    'party' => 'def',
    'title' => '預設',
    'titleE' => 'Default',
    'confirmTitle' => '自定義未圈選顯示名稱',
    'confirmTitleE' => '自定義未圈選顯示名稱(英)',
    'description' => '描述',
    'descriptionE' => '英文描述',
    'ruleText' => '<b>自定義投票規則文字</b>',
    'ruleTextE' => '<b>自定義投票規則文字(英)</b>',
    'numBallots' => '1',
    'leastNumBallots' => '1',
    'maxElect' => '1',
    'numOfKeep' => '1',
    'numFemaleKeep' => NULL,
];

// 表決投票 (NoAuth)
$questions[] = [
    'questionID' => 7,
    'voteID' => 'NoAuthVoteTest',
    'round' => 1,
    'party' => 'def',
    'title' => '表決問題一',
    'titleE' => 'NoAuth Question 1',
    'confirmTitle' => '未圈選顯示',
    'confirmTitleE' => 'Not Selected',
    'description' => '表決投票問題描述',
    'descriptionE' => 'NoAuth vote question description',
    'ruleText' => '<b>表決投票規則</b>',
    'ruleTextE' => '<b>NoAuth Vote Rules</b>',
    'numBallots' => '3',
    'leastNumBallots' => '1',
    'maxElect' => '2',
    'numOfKeep' => '1',
    'numFemaleKeep' => NULL,
    'population' => '50'
];

return $questions;