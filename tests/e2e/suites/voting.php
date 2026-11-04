<?php
/**
 * 投票流程畫面：建立候選設定與候選人後，投票者開啟投票說明、投票畫面
 *
 * fixture 的表決投票（NoAuthVoteTest）有問題但沒有候選人，投票畫面會回 400，
 * 因此先以管理介面建立候選人，測完再刪除。
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

// 清除測試候選人
if ($candId) {
    $admin->go("/candi/delete?voteID=$vote&id=$candId", "/candi/data?voteID=$vote");
    $admin->go("/candi/data?voteID=$vote");
    $admin->check('刪除測試候選人', !$admin->js('document.body.innerText.includes('.json_encode($name).')'));
}
