<?php

// AnonPartyTest 分組
// AnonNoPartyTest 不分組

return [
    'voteID' => 'AnonPartyTest',
    'party' => '',
    'questionID' => '',
    'isReachThreshold' => NULL,
    'jobLctn' => '1',
    'instCode' => '00',
    'tCode' => 'D05',
    'instName' => $faker->company,
    'instNameE' => $faker->company,
    'title' => $faker->jobTitle,
    'titleE' => $faker->jobTitle,
    'Name' => '測試'.$index,
    'NameE' => 'Tester'.$index,
    'sex' => strval($faker->numberBetween(0, 1)),
    'orderNum' => 0,
    'otherColA' => '自定義欄位1',
    'otherColAE' => '自定義欄位1(英文)',
    'otherColB' => '自定義欄位2',
    'otherColBE' => '自定義欄位2(英文)',
    'otherColC' => '自定義欄位3',
    'otherColCE' => '自定義欄位3(英文)',
    'otherColD' => '自定義欄位4',
    'otherColDE' => '自定義欄位4(英文)',
    'otherColE' => '自定義欄位 5',
    'otherColEE' => '自定義欄位 5(英文)',
    'otherColF' => '自定義欄位 6',
    'otherColFE' => '自定義欄位 6(英文)',
    'genMode' => 'manual',
    'backgroundColor' => $faker->hexColor,
    'other' => $index,
];