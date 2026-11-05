<?php
/**
 * 全畫面巡檢：逐一開啟各功能畫面並執行共用檢查（PageCheck）
 *
 * 僅包含 GET 時不會變更資料的畫面；刪除、重設、匯出、切換等動作不在此列。
 *
 * @var Browser $admin 已以 sa 登入
 * @var Browser $guest 未登入（獨立瀏覽器環境）
 */

$NA = 'NoAuthVoteTest';   // 表決投票（不分組）
$AP = 'AnonPartyTest';    // 匿名分組投票（含選票）
$AN = 'AnonNoPartyTest';  // 匿名不分組投票

$guest->section('pages-public');
foreach ([
    '登入' => '/auth/login',
    '忘記密碼' => '/auth/forgot-password',
    '投票列表' => '/vote/index',
    '投票說明（表決）' => "/vote/vote-detail?voteID=$NA",
    '投票結果列表' => '/vote/result-list',
    '投票密碼登入' => "/site/password?voteID=$AN",
] as $label => $path) {
    PageCheck::run($guest, $label, $path);
}

$admin->section('pages-admin');
$pages = [
    // 投票管理
    '投票管理列表' => '/elect/index',
    '建立投票' => '/elect/create-vote',
    '編輯投票（表決）' => "/elect/edit-vote?voteID=$NA",
    '編輯投票（匿名分組）' => "/elect/edit-vote?voteID=$AP",
    '編輯投票（匿名不分組）' => "/elect/edit-vote?voteID=$AN",
    '投票流程' => "/elect/process?voteID=$AP",
    '重啟投票' => "/elect/reset-vote?voteID=$AP",
    // 輪次
    '輪次列表' => "/round/index?voteID=$AP",
    '輪次編輯' => "/round/update?voteID=$AP&round=1",
    // 問題
    '問題列表' => "/question/index?voteID=$AP",
    '新增問題' => "/question/create?voteID=$AP",
    '編輯問題' => "/question/edit?voteID=$AP&questionID=1",
    '問題輪次匯入' => "/question/round-import?voteID=$AP",
    // 候選
    '候選設定' => "/candi/config?voteID=$AN",
    '候選名單' => "/candi/data?voteID=$AN",
    '候選名單（分組）' => "/candi/data?voteID=$AP",
    '新增候選人' => "/candi/manual?voteID=$AN&action=create",
    '編輯候選人' => "/candi/manual?voteID=$AN&action=update&id=57",
    '候選輪次匯入' => "/candi/round?voteID=$AP",
    '候選檔案匯入' => "/candi/csvfile?voteID=$AN",
    // 密碼
    '投票密碼' => "/passwd/index?voteID=$AN",
    // 選票
    '選票列表' => "/ballot/index?voteID=$AP",
    '選票密碼查詢' => "/ballot/select-passwd?voteID=$AN",
    '選票建立者查詢' => "/ballot/select-creator?voteID=$AP",
    '選票匯入' => "/ballot/import?voteID=$AP",
    '紙本選票列印' => "/ballot/print?voteID=$AP&questionID=1",
    // 開票作業
    '開票作業列表' => '/ballot-work/index',
    '開票狀態' => "/ballot-work/status?voteID=$AP",
    '開票設定' => "/ballot-work/setting?voteID=$AP",
    '開票計票' => "/ballot-work/count?voteID=$AP",
    '開票結果' => "/ballot-work/result?voteID=$AP",
    '開票選票明細' => "/ballot-work/ballots-detail?voteID=$AP&party=0&questionID=1&valid=1",
    // 計票與結果
    '計票' => "/count/index?voteID=$AP",
    '計票結果設定' => "/count/result?voteID=$AP",
    '投票結果' => "/result/index?voteID=$AP",
    '投票結果（前台）' => "/vote/result?voteID=$AP",
    '計票（前台）' => "/vote/count?voteID=$AP",
    // 群組
    '群組列表' => '/group/index',
    '新增群組' => '/group/create',
    '群組資料' => '/group/view-base?groupId=1',
    '編輯群組' => '/group/edit-base?groupId=1',
    '群組投票' => '/group/view-vote',
    '群組成員' => '/group-member/index?groupId=1',
    '新增群組成員' => '/group-member/create?groupId=1',
    '編輯群組成員' => '/group-member/edit?groupId=1&cn=gm_test',
    // 網站管理
    '網站管理' => '/manage/index',
    '系統設定' => '/manage/setting',
    '操作紀錄' => '/manage/log',
    '登入紀錄' => '/manage/logins',
    '密碼資訊' => '/manage/passwd-info',
    'Yii log 檢視' => '/site/yii-log-view',
    // 使用者
    '使用者列表' => '/users/index',
    '新增使用者' => '/users/create',
    '編輯使用者' => '/users/update?cn=test_va2',
    '兩步驟驗證' => '/users/totp',
    '修改密碼' => '/auth/change-password',
];
foreach ($pages as $label => $path) {
    PageCheck::run($admin, $label, $path);
}
