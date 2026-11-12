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
 * @var Browser $admin
 */
$voter = new Browser($cdpUrl, $base, $outDir, true);
$browsers[] = $voter;
$voter->section('anon');
$vote = 'AnonNoPartyTest';
$passwd = 'testdef'.random_int(60, 100);

// 短網址（fixture shortUrl=test2）導向投票說明頁
$voter->go('/test2');
$voter->check('短網址導向投票說明頁', strpos($voter->js('location.pathname'), 'vote/vote-detail') !== false && strpos($voter->js('location.search'), "voteID=$vote") !== false, $voter->js('location.pathname + location.search'));

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

/**
 * 以投票密碼登入，回傳是否離開密碼頁
 */
$anonLogin = function (Browser $b, $password) use ($vote) {
    $b->go("/site/password?voteID=$vote");
    $b->js('document.querySelector("#passwd").value = '.json_encode($password));
    $b->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.includes('登入'))");
    $b->waitLoad();
    return strpos($b->js('location.pathname'), 'site/password') === false;
};

// 已投過的密碼在另一個瀏覽器重新登入：可登入，但進投票畫面顯示「已完成投票」
$again = new Browser($cdpUrl, $base, $outDir, true);
$browsers[] = $again;
$again->group = 'anon';
$voter->check("已投票密碼 $passwd 可重新登入", $anonLogin($again, $passwd), implode(' | ', $again->flashes()));
$again->go("/vote/start-vote?voteID=$vote");
$votedPath = $again->js('location.pathname');
$voter->check('已投票密碼進投票畫面導向已投票頁', strpos($votedPath, 'vote-voted') !== false, $votedPath);
$voter->check('已投票頁顯示「此密碼已完成投票」', $again->js("document.body.innerText.includes('此密碼已完成投票')"));
$problems = PageCheck::inspect($again);
$voter->check('已投票頁共用檢查', !$problems, implode(' / ', $problems));
$again->shot('anon-voted');

// 清除登入狀態：另一組密碼登入後未投票，管理者清除後須重新登入，且可再次登入（不會被「已在其他裝置登入中」擋住）
$pending = new Browser($cdpUrl, $base, $outDir, true);
$browsers[] = $pending;
$pending->group = 'anon';
$pendingPasswd = 'testdef'.random_int(41, 59);
$voter->check("以 $pendingPasswd 登入（不投票）", $anonLogin($pending, $pendingPasswd), implode(' | ', $pending->flashes()));

$admin->go('/manage/logins');
$hasVote = $admin->js("[...document.querySelectorAll('#voteID option')].some(o => o.value === '$vote')");
$voter->check('清除登入狀態頁列出登入中的投票場次', $hasVote);
if ($hasVote) {
    // 選擇場次會以 AJAX 載入組別／標記清單（網址為相對路徑，http 測試環境也可用）
    $admin->js("$('#voteID').val('$vote').trigger('change')");
    $loaded = $admin->waitFor("[...document.querySelectorAll('#party option')].some(o => o.text === '請選擇')");
    $voter->check('選擇場次後載入組別／標記清單', $loaded, $admin->js("document.querySelector('#party').options.length + ' 個組別選項'"));
    $admin->click("[...document.querySelectorAll('#login-form button[type=submit]')].find(e => e.innerText.includes('清除'))");
    $admin->waitLoad();
    $voter->check('清除 session 成功', (bool) array_filter($admin->flashes(), function ($m) { return mb_strpos($m, '清除session完成') !== false; }), implode(' | ', $admin->flashes()));
    $admin->shot('anon-clear-logins');

    $pending->go("/vote/start-vote?voteID=$vote");
    $pendingPath = $pending->js('location.pathname');
    $voter->check('被清除的投票者需重新登入', strpos($pendingPath, 'site/password') !== false, $pendingPath);
    $voter->check("清除後 $pendingPasswd 可再次登入", $anonLogin($pending, $pendingPasswd), implode(' | ', $pending->flashes()));
}

