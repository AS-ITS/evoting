<?php
/**
 * 匿名投票：以投票密碼登入 → 投票說明 → 投票畫面 → 圈選 → 確認 → 送出 → 投票完成
 *
 * fixture 密碼明碼：AnonNoPartyTest 為 testdef1～testdef100（見 tests/fixtures/data/passwords.php）。
 * 每次隨機取一組，避免重複執行時用到已投過的密碼；重新載入 fixture 即重置。
 *
 * @var string $cdpUrl
 * @var string $base
 * @var string $outDir
 * @var Browser[] $browsers
 */
$voter = new Browser($cdpUrl, $base, $outDir, true);
$browsers[] = $voter;
$voter->section('anon');
$vote = 'AnonNoPartyTest';
$passwd = 'testdef'.random_int(60, 100);

// 登入
PageCheck::run($voter, '投票密碼頁', "/site/password?voteID=$vote");
$voter->js('document.querySelector("#passwd").value = '.json_encode($passwd));
$voter->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.includes('登入'))");
$voter->waitLoad();
$path = $voter->js('location.pathname');
$voter->check("以投票密碼 $passwd 登入", strpos($path, 'site/password') === false, $path.' '.implode(' | ', $voter->flashes()));

// 投票說明 → 投票畫面
PageCheck::run($voter, '投票說明（已登入）', "/vote/vote-detail?voteID=$vote");
PageCheck::run($voter, '投票畫面（已登入）', "/vote/start-vote?voteID=$vote");
$okBtn = "[...document.querySelectorAll('.modal.show button')].find(e => e.innerText.includes('確定'))";
if ($voter->waitFor("!!($okBtn)", 3000)) {
    $voter->click($okBtn);
    $voter->sleep(900);
}
$candidate = $voter->js("(() => { const cb = document.querySelector('table input[type=checkbox]'); if (!cb) return null; const tr = cb.closest('tr'); return [...tr.querySelectorAll('td')].map(td => td.innerText.trim()).filter(t => t && !/^\\d+$/.test(t))[0] || null; })()");
$voter->click("document.querySelector('table input[type=checkbox]')");
$voter->sleep(500);
$voter->check('圈選一位候選人', $voter->js("[...document.querySelectorAll('table input[type=checkbox]')].some(e => e.checked)"), (string) $candidate);
$voter->click("[...document.querySelectorAll('button, a')].find(e => e.innerText.includes('下一步') && e.offsetParent !== null)");
$voter->sleep(1500);
$problems = PageCheck::inspect($voter);
$voter->check('確認頁共用檢查（含已圈選名單看得見）', !$problems, implode(' / ', $problems));
$voter->shot('anon-confirm');

// 送出選票（確認鈕後可能有確認對話框）
$voter->click("[...document.querySelectorAll('button')].find(e => e.innerText.trim().includes('確認') && e.offsetParent !== null && !e.closest('.modal'))");
$voter->sleep(1200);
// 送出前的「您已圈選之投票統計」對話框，按「投票」送出
$confirmDialog = "[...document.querySelectorAll('.modal.show button, .bootstrap-dialog button')].find(e => /^(投票|確定|確認|送出)$/.test(e.innerText.trim()) && e.offsetParent !== null)";
if ($voter->waitFor("!!($confirmDialog)", 5000)) {
    $voter->shot('anon-submit-dialog');
    $voter->click($confirmDialog);
}
$voter->waitFor("!location.pathname.includes('start-vote')", 15000);
$voter->sleep(800);
$finalPath = $voter->js('location.pathname');
$voter->check('送出選票後進入投票完成頁', strpos($finalPath, 'vote-finish') !== false || strpos($finalPath, 'vote-voted') !== false, $finalPath);
$problems = PageCheck::inspect($voter);
$voter->check('投票完成頁共用檢查', !$problems, implode(' / ', $problems));
$voter->shot('anon-finish');

// 同一密碼不可再投
$voter->go("/vote/start-vote?voteID=$vote");
$voter->check('已投票後再進投票畫面會被導開', strpos($voter->js('location.pathname'), 'start-vote') === false, $voter->js('location.pathname'));

