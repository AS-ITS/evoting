<?php
/**
 * 會變更資料的操作：只接受 POST、刪除前一定會先確認
 *
 * - 直接以 GET 開啟刪除、切換網址回 405，資料不變
 * - 刪除選票、刪除候選人、重啟投票、切換輪次：點擊後出現確認對話框，按「取消」資料不變
 *   （原本 data-bs-confirm 不被 yii.js 認得，按下即執行）
 * - 刪除選票、切換輪次：按「確定」後實際以 POST 執行成功（CSRF 開啟時也驗證 token 有帶到）
 *
 * 會刪除 AnonPartyTest 的 1 張選票；AnonNoPartyTest 的輪次會切換後還原。
 *
 * @var Browser $admin
 */
$admin->section('post-only');

$dialogBtn = function ($pattern) {
    return "[...document.querySelectorAll('.modal.show button')].find(e => /^($pattern)$/i.test(e.innerText.trim()) && e.offsetParent !== null)";
};
$okBtn = $dialogBtn('確定|OK|Ok');
$cancelBtn = $dialogBtn('取消|Cancel');

/** 點擊後應出現確認對話框；$answer 為 true 按確定、false 按取消。回傳是否出現對話框 */
$confirmClick = function ($triggerJs, $answer) use ($admin, $okBtn, $cancelBtn) {
    $admin->click($triggerJs);
    if (!$admin->waitFor("!!($okBtn) && !!($cancelBtn)", 4000)) {
        return false;
    }
    $admin->sleep(400); // 等淡入動畫結束
    $admin->click($answer ? $okBtn : $cancelBtn);
    if ($answer) {
        $admin->waitLoad();
    } else {
        $admin->sleep(800);
    }
    return true;
};

// ===== 選票 =====
$vote = 'AnonPartyTest';
$ballotIds = function () use ($admin, $vote) {
    $admin->go("/ballot/index?voteID=$vote");
    return $admin->js("[...new Set([...document.querySelectorAll('a[href*=\"ballot/delete?\"]')].map(a => new URL(a.href).searchParams.get('ballotID')))].filter(Boolean)");
};
$ids = $ballotIds();
$admin->check('選票列表有選票可測', count($ids) > 0, count($ids).' 張');
$id = $ids[0] ?? null;

if ($id) {
    $status = $admin->status("/ballot/delete?voteID=$vote&ballotID=$id");
    $admin->check('GET 刪除選票網址回 405', $status === 405, "HTTP $status");
    $admin->check('GET 後選票仍在', in_array($id, $ballotIds(), true));

    $link = "[...document.querySelectorAll('a[href*=\"ballot/delete?\"]')].find(a => new URL(a.href).searchParams.get('ballotID') === ".json_encode($id).")";
    $admin->check('刪除選票：出現確認對話框', $confirmClick($link, false));
    $admin->check('刪除選票：按取消後選票仍在', in_array($id, $ballotIds(), true));

    $admin->check('刪除選票：按確定（對話框）', $confirmClick($link, true));
    $admin->check('刪除選票：按確定後成功（POST）', !in_array($id, $ballotIds(), true) && !$admin->js("document.body.innerText.includes('Method Not Allowed')"), implode(' | ', $admin->flashes()));
}

// 選票修改頁的「刪除選票」按鈕
$ids = $ballotIds();
if ($ids) {
    $admin->go("/ballot/edit?voteID=$vote&party=N&ballotID={$ids[0]}");
    $btn = "[...document.querySelectorAll('a')].find(a => a.innerText.trim() === '刪除選票')";
    if ($admin->js("!!($btn)")) {
        $admin->check('選票修改頁刪除：出現確認對話框', $confirmClick($btn, false));
        $admin->check('選票修改頁刪除：按取消後選票仍在', in_array($ids[0], $ballotIds(), true));
    }
}

// ===== 重啟投票（會刪除全部選票與開票結果）：只測取消 =====
$before = count($ballotIds());
$admin->go("/elect/reset-vote?voteID=$vote");
$resetBtn = "[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.includes('確認重啟'))";
$admin->check('重啟投票：出現確認對話框', $confirmClick($resetBtn, false));
$admin->check('重啟投票：按取消後選票數不變', count($ballotIds()) === $before, "$before → ".count($ballotIds()));

// ===== 候選人：只測取消 =====
$candVote = 'AnonNoPartyTest';
$admin->go("/candi/data?voteID=$candVote");
$candId = $admin->js("(() => { const a = [...document.querySelectorAll('a[href*=\"candi/manual\"]')].find(a => new URL(a.href).searchParams.get('action') === 'update'); return a ? new URL(a.href).searchParams.get('id') : null; })()");
if ($candId) {
    $status = $admin->status("/candi/delete?voteID=$candVote&id=$candId");
    $admin->check('GET 刪除候選人網址回 405', $status === 405, "HTTP $status");
    $admin->go("/candi/manual?voteID=$candVote&action=update&id=$candId");
    $del = "[...document.querySelectorAll('a')].find(a => a.innerText.trim() === '刪除候選人')";
    $admin->check('刪除候選人：出現確認對話框', $confirmClick($del, false));
    $admin->check('刪除候選人：按取消後候選人仍在', $admin->go("/candi/manual?voteID=$candVote&action=update&id=$candId") === 200
        && $admin->js("!!document.querySelector('#formcandidata-name')"));
}

// ===== 輪次：建立鈕為 POST；切換需確認，確定後切換並還原 =====
$admin->go("/round/index?voteID=$candVote");
$admin->check('建立新一輪按鈕以 POST 送出', (bool) $admin->js("[...document.querySelectorAll('a')].some(a => a.innerText.includes('建立新一輪投票') && a.dataset.method === 'post')"));
$status = $admin->status("/round/switch?voteID=$candVote&round=2");
$admin->check('GET 切換輪次網址回 405', $status === 405, "HTTP $status");

$currentRound = function () use ($admin, $candVote) {
    $admin->go("/round/index?voteID=$candVote");
    // 目前輪次那一列沒有「切換」連結：列數 n 時，1..n 中沒有連結的即為目前輪次
    return $admin->js("(() => {
  const linked = [...document.querySelectorAll('a[href*=\"round/switch\"]')].map(a => new URL(a.href).searchParams.get('round'));
  const n = document.querySelectorAll('table tbody tr').length;
  for (let i = 1; i <= n; i++) { if (!linked.includes(String(i))) return String(i); }
  return null;
})()");
};
$switchLink = function ($round) use ($candVote) {
    return "[...document.querySelectorAll('a[href*=\"round/switch\"]')].find(a => new URL(a.href).searchParams.get('round') === '$round')";
};
$start = $currentRound();
$target = $start === '2' ? '1' : '2';
$admin->check('切換輪次：出現確認對話框', $confirmClick($switchLink($target), false));
$admin->check('切換輪次：按取消後輪次不變', $currentRound() === $start, (string) $currentRound());
$admin->check('切換輪次：按確定', $confirmClick($switchLink($target), true));
$admin->check('切換輪次：按確定後已切換（POST）', $currentRound() === $target, (string) $currentRound());
// 還原
$confirmClick($switchLink($start), true);
$admin->check('切換輪次：還原', $currentRound() === $start, (string) $currentRound());

// ===== 其餘刪除網址：GET 回 405 =====
// 以不存在的對象測試：即使在尚未限制 POST 的程式上執行也不會刪到資料
foreach ([
    '/users/delete?cn=e2e_none',
    '/elect/delete-vote?voteID=E2E_NONE',
    '/elect/delete-file?voteID=NoAuthVoteTest&file=e2e_none.txt',
    '/passwd/delete-all?voteID=AnonPartyTest&mark=e2e_none',
    "/ballot/delete-all?voteID=$vote&party=e2e_none",
    "/candi/delete-photo?voteID=$candVote&id=0",
    "/round/create?voteID=E2E_NONE",
] as $u) {
    $status = $admin->status($u);
    $admin->check("GET 回 405：$u", $status === 405, "HTTP $status");
}
