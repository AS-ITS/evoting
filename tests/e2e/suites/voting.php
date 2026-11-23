<?php
/**
 * 投票流程畫面：建立候選設定與候選人後，投票者開啟投票說明、投票畫面，
 * 並由兩位投票者實際送出表決選票、驗證竄改的候選人被拒、檢查管理端選票列表與修改頁
 *
 * fixture 的表決投票（NoAuthVoteTest）有問題但沒有候選人，投票畫面會回 400，
 * 因此先以管理介面建立候選人，測完刪除本次送出的選票與候選人。
 *
 * @var string $cdpUrl
 * @var string $base
 * @var string $outDir
 * @var Browser[] $browsers
 *
 * @var Browser $admin
 * @var Browser $guest
 */
$admin->section('voting');
$vote = 'NoAuthVoteTest';
$name = 'E2E 測試候選人';

// 沒有候選設定時，新增候選人應導回設定頁（原本為 500）
$admin->go('/candi/config?voteID='.$vote);
$hasConfig = !$admin->js("[...document.querySelectorAll('button[type=submit]')].some(e => e.innerText.includes('建立'))");
if (!$hasConfig) {
    $status = $admin->go("/candi/manual?voteID=$vote&action=create");
    $admin->check('未建立候選設定時新增候選人導回設定頁', $status === 200 && strpos($admin->js('location.pathname'), 'candi/config') !== false, $admin->js('location.pathname'));
    $admin->go('/candi/config?voteID='.$vote);
    $admin->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.includes('建立'))");
    $admin->waitLoad();
    $admin->check('建立候選設定', (bool) array_filter($admin->flashes(), function ($m) { return mb_strpos($m, '建立完成') !== false; }));
}

// 新增候選人
$admin->go("/candi/manual?voteID=$vote&action=create");
$admin->js("(() => { const p = document.querySelector('#formcandidata-party'); p.value = 'def'; p.dispatchEvent(new Event('change', {bubbles: true})); })()");
// 選分組後以 AJAX 帶入問題清單（第一項為「請選擇」）
$admin->waitFor("[...document.querySelector('#formcandidata-questionid').options].some(o => o.value)");
$admin->js("(() => { const q = document.querySelector('#formcandidata-questionid'); q.value = [...q.options].find(o => o.value).value; document.querySelector('#formcandidata-name').value = ".json_encode($name)."; })()");
$admin->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.value === 'update')");
$admin->waitLoad();
parse_str((string) parse_url($admin->js('location.href'), PHP_URL_QUERY), $q);
$candId = $q['id'] ?? null;
$admin->check('新增候選人', !empty($candId), $admin->js('location.href'));

// 投票者：投票說明 → 投票畫面
$guest->group = 'voting';
PageCheck::run($guest, '投票說明（有候選人）', "/vote/vote-detail?voteID=$vote");
$admin->check('投票說明列出候選人', $guest->js('document.body.innerText.includes('.json_encode($name).')'));
$info = PageCheck::run($guest, '投票畫面', "/vote/start-vote?voteID=$vote");
$admin->check('投票畫面列出候選人', $guest->js('document.body.innerText.includes('.json_encode($name).')'));

// 圈選須知對話框 → 勾選 → 下一步（不送出選票，避免留下投票紀錄）
$okBtn = "[...document.querySelectorAll('.modal.show button')].find(e => e.innerText.includes('確定'))";
if ($guest->waitFor("!!($okBtn)", 3000)) {
    $admin->check('圈選須知：「確定」可點擊（未被遮罩蓋住）', $guest->js("(() => { const b = $okBtn; const r = b.getBoundingClientRect(); return document.elementFromPoint(r.left + r.width/2, r.top + r.height/2).closest('button') === b; })()"));
    $guest->click($okBtn);
    $guest->sleep(900);
    $admin->check('圈選須知：按「確定」後關閉', !$guest->js("!!document.querySelector('.modal.show')"));
}
$guest->click("[...document.querySelectorAll('table input[type=checkbox]')].find(e => e.closest('tr') && e.closest('tr').innerText.includes(".json_encode($name)."))");
$guest->sleep(500);
$admin->check('勾選候選人', $guest->js("[...document.querySelectorAll('table input[type=checkbox]')].some(e => e.checked)"));
$guest->shot('voting-selected');
$guest->click("[...document.querySelectorAll('button, a')].find(e => e.innerText.includes('下一步') && e.offsetParent !== null)");
$guest->sleep(1500);
// 確認頁的已圈選名單由 JS 產生（.badge），須確實看得到：文字顏色與實際背景不同
$selected = $guest->js("(() => {
  const el = [...document.querySelectorAll('.tab-pane.active .badge, .tab-pane.show .badge')].find(e => e.innerText.includes(".json_encode($name)."));
  if (!el) return null;
  let n = el, bg = 'rgba(0, 0, 0, 0)';
  while (n && (bg === 'rgba(0, 0, 0, 0)' || bg === 'transparent')) { bg = getComputedStyle(n).backgroundColor; n = n.parentElement; }
  if (bg === 'rgba(0, 0, 0, 0)' || bg === 'transparent') bg = 'rgb(255, 255, 255)';
  const r = el.getBoundingClientRect();
  return {visible: r.width > 0 && r.height > 0, color: getComputedStyle(el).color, bg};
})()");
$admin->check('下一步：確認頁顯示已圈選的候選人姓名（文字與背景顏色不同）', $selected && $selected['visible'] && $selected['color'] !== $selected['bg'], json_encode($selected));
$problems = PageCheck::inspect($guest);
$admin->check('下一步：確認頁共用檢查', !$problems, implode(' / ', $problems));
$guest->shot('voting-confirm');

// ===== 表決投票：多位投票者實際送出 =====

/** 管理端選票列表上的選票編號（取自刪除連結） */
$ballotIds = function () use ($admin, $vote) {
    PageCheck::run($admin, '選票列表（表決）', "/ballot/index?voteID=$vote");
    return $admin->js("[...new Set([...document.querySelectorAll('a[href*=\"ballot/delete?\"]')].map(a => new URL(a.href).searchParams.get('ballotID')))].filter(Boolean)");
};
$before = $ballotIds();

/** 在確認頁送出選票，回傳是否進入投票完成頁 */
$submit = function (Browser $b) {
    $b->click("[...document.querySelectorAll('button')].find(e => e.innerText.trim().includes('確認') && e.offsetParent !== null && !e.closest('.modal'))");
    $b->sleep(1200);
    $dialog = "[...document.querySelectorAll('.modal.show button, .bootstrap-dialog button')].find(e => /^(投票|確定|確認|送出)$/.test(e.innerText.trim()) && e.offsetParent !== null)";
    if ($b->waitFor("!!($dialog)", 5000)) {
        $b->click($dialog);
    }
    $b->waitFor("!location.pathname.includes('start-vote')", 15000);
    $b->sleep(800);
    return strpos($b->js('location.pathname'), 'vote-finish') !== false;
};

/** 從投票畫面圈選測試候選人到確認頁 */
$selectAndNext = function (Browser $b) use ($vote, $name, $okBtn) {
    PageCheck::run($b, '投票畫面', "/vote/start-vote?voteID=$vote");
    if ($b->waitFor("!!($okBtn)", 3000)) {
        $b->click($okBtn);
        $b->sleep(900);
    }
    $b->click("[...document.querySelectorAll('table input[type=checkbox]')].find(e => e.closest('tr') && e.closest('tr').innerText.includes(".json_encode($name)."))");
    $b->sleep(500);
    $b->click("[...document.querySelectorAll('button, a')].find(e => e.innerText.includes('下一步') && e.offsetParent !== null)");
    $b->sleep(1500);
};

// 投票者 A（沿用上面已到確認頁的 guest）
$admin->check('表決投票：投票者 A 送出選票進入投票完成頁', $submit($guest), $guest->js('location.pathname'));
$problems = PageCheck::inspect($guest);
$admin->check('表決投票：投票完成頁共用檢查', !$problems, implode(' / ', $problems));
$guest->shot('voting-finish');

// 投票者 B（另一個瀏覽器環境）：修正前第 2 位投票者會被唯一鍵擋下、導回投票畫面
$voterB = new Browser($cdpUrl, $base, $outDir, true);
$browsers[] = $voterB;
$voterB->group = 'voting';
$selectAndNext($voterB);
$admin->check('表決投票：投票者 B 也能送出選票', $submit($voterB), $voterB->js('location.pathname'));

// 竄改：送出不屬於本場次的候選人（AnonNoPartyTest 的候選人 57）應被拒絕
$voterC = new Browser($cdpUrl, $base, $outDir, true);
$browsers[] = $voterC;
$voterC->group = 'voting';
$voterC->go("/vote/start-vote?voteID=$vote");
$tampered = $voterC->js("(async () => {
  const form = document.querySelector('form[action*=\"save-vote\"]');
  if (!form) return 'no-form';
  const fd = new FormData(form); // 含表單的 CSRF 欄位
  fd.delete('selection[]');
  fd.append('selection[]', '57');
  const r = await fetch(form.action, {method: 'POST', body: fd, credentials: 'same-origin'});
  return new URL(r.url).pathname;
})()");
$admin->check('表決投票：竄改送出他場次候選人被拒（導回投票畫面）', is_string($tampered) && strpos($tampered, 'start-vote') !== false, (string) $tampered);

// 管理端：選票數量 +2、列表與修改頁正常、投票者顯示「—」
$after = $ballotIds();
$newIds = array_values(array_diff($after, $before));
$admin->check('表決投票：管理端選票列表多出 2 張（竄改的不算）', count($newIds) === 2, json_encode(['before' => $before, 'after' => $after]));
$admin->check('表決投票：選票列表不提供「建立選票」', !$admin->js("[...document.querySelectorAll('a')].some(a => a.innerText.trim() === '建立選票')"));
$admin->check('表決投票：選票列表投票者顯示「—」', (bool) $admin->js("[...document.querySelectorAll('table tbody td')].some(td => td.innerText.trim() === '—')"));
$admin->shot('voting-ballot-list');
if ($newIds) {
    PageCheck::run($admin, '選票修改（表決）', "/ballot/edit?voteID=$vote&party=def&ballotID={$newIds[0]}");
    $admin->check('表決投票：選票修改頁投票者顯示「—」', $admin->js("(() => { const s = document.querySelector('#formballots-creator, select[name*=\"[creator]\"]'); const o = s && s.options[s.selectedIndex]; return o ? o.text : null; })()") === '—');
    $admin->check('表決投票：選票修改頁勾選了測試候選人', (bool) $admin->js("[...document.querySelectorAll('table input[type=checkbox]')].some(e => e.checked && e.closest('tr') && e.closest('tr').innerText.includes(".json_encode($name)."))"));
}

// 清除本次送出的選票
foreach ($newIds as $id) {
    $admin->go("/ballot/delete?voteID=$vote&ballotID=$id");
}
$admin->check('表決投票：刪除測試選票', !array_diff($ballotIds(), $before));

// 清除測試候選人
if ($candId) {
    $admin->go("/candi/delete?voteID=$vote&id=$candId", "/candi/data?voteID=$vote");
    $admin->go("/candi/data?voteID=$vote");
    $admin->check('刪除測試候選人', !$admin->js('document.body.innerText.includes('.json_encode($name).')'));
}
