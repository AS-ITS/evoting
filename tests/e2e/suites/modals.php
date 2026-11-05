<?php
/**
 * 對話框：關閉鈕位置、Esc／背景、遮罩不可蓋住對話框
 *
 * @var Browser $admin
 */
$admin->section('modals');
$edit = '/elect/edit-vote?voteID=NoAuthVoteTest';
$uploadBtn = "[...document.querySelectorAll('button')].find(e => e.getAttribute('data-bs-target') === '#fileUpload')";

/** × 是否為 .btn-close 且位於標題列右側 */
$closeAtRight = function ($modal) use ($admin) {
    $pos = $admin->js("(() => { const h = document.querySelector('$modal .modal-header'); const x = h && h.querySelector('.btn-close'); if (!x) return null; const hr = h.getBoundingClientRect(), r = x.getBoundingClientRect(); return {headerRight: Math.round(hr.right), closeRight: Math.round(r.right), closeWidth: Math.round(r.width)}; })()");
    return [$pos && $pos['closeRight'] > $pos['headerRight'] - 60 && $pos['closeWidth'] > 0, json_encode($pos)];
};

// ---- 附件上傳對話框 ----
$admin->go($edit);
$admin->check('附件上傳：開啟', $admin->openModal($uploadBtn, '#fileUpload'));
[$ok, $d] = $closeAtRight('#fileUpload');
$admin->check('附件上傳：× 位於標題列右側', $ok, $d);
$admin->shot('modal-upload');
$admin->key('Escape', 'Escape', 27);
$admin->sleep(600);
$admin->check('附件上傳：按 Esc 不關閉', $admin->modalShown('#fileUpload'));
$admin->mouse(8, 450);
$admin->sleep(1500);
$admin->check('附件上傳：點背景不關閉', $admin->modalShown('#fileUpload'));
$admin->click("document.querySelector('#fileUpload .btn-close')");
$admin->sleep(900);
$admin->check('附件上傳：點 × 關閉', !$admin->modalShown('#fileUpload'));

// ---- 遮罩不可蓋住對話框（OverlayScrollbars 初始化順序造成的間歇問題）----
$hit = "(() => { const s = [...document.querySelectorAll('#fileUpload button[type=submit]')][0]; const r = s.getBoundingClientRect(); return document.elementFromPoint(r.left + r.width/2, r.top + r.height/2) === s; })()";
$okCount = 0;
$tries = 8;
for ($i = 0; $i < $tries; $i++) {
    $admin->go($edit);
    $admin->openModal($uploadBtn, '#fileUpload');
    $okCount += $admin->js($hit) ? 1 : 0;
}
$admin->check("遮罩不蓋住對話框：重新載入 $tries 次「上傳」鈕都可點擊", $okCount === $tries, "$okCount/$tries");

// ---- 照片預覽對話框（需有照片，於 photos 組測試）----

// ---- 新增群組成員對話框 ----
$admin->go('/group-member/clear-search?groupId=1');

$admin->go('/group-member/create?groupId=1');
$admin->js("document.querySelector('#labelCn').value = 'test_va2'");
$admin->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.includes('查詢'))");
$admin->waitLoad();
$trigger = "document.querySelector('[data-bs-target=\"#createMember\"]')";
if ($admin->check('新增群組成員：查詢 test_va2 有結果', $admin->js("!!$trigger"))) {
    $admin->check('新增群組成員：開啟', $admin->openModal($trigger, '#createMember'));
    [$ok, $d] = $closeAtRight('#createMember');
    $admin->check('新增群組成員：× 位於標題列右側', $ok, $d);
    $admin->check('新增群組成員：對話框內的欄位可點擊', $admin->js("(() => { const el = document.querySelector('#createMember select, #createMember input:not([type=hidden])'); if (!el) return false; const r = el.getBoundingClientRect(); return document.elementFromPoint(r.left + 5, r.top + r.height/2) === el; })()"));
    $admin->shot('modal-group-member');
    $admin->click("document.querySelector('#createMember .btn-close')");
    $admin->sleep(900);
    $admin->check('新增群組成員：點 × 關閉', !$admin->modalShown('#createMember'));
}
$admin->go('/group-member/clear-search?groupId=1');

// 使用不存在的 inode 驗證真正的 AJAX 回應，不讀取主機既有日誌內容。
$admin->go('/site/yii-log-view');
$logModal = '#details-modal-yii-log-view';
$admin->js("(() => { const b = document.createElement('button'); b.id = 'e2e-url-modal'; b.type = 'button'; b.textContent = 'E2E UrlModal'; b.dataset.bsToggle = 'modal'; b.dataset.bsTarget = '$logModal'; b.dataset.id = '0'; b.dataset.encode = 'T'; document.body.appendChild(b); })()");
$admin->check('Yii log：開啟內容對話框', $admin->openModal("document.querySelector('#e2e-url-modal')", $logModal));
$loaded = "document.querySelector('$logModal-contact').textContent.trim() === '無此檔案'";
$admin->check('Yii log：AJAX 取得無檔案回應', $admin->waitFor($loaded, 10000));
$admin->js("document.querySelector('$logModal-contact').textContent = 'refresh pending'");
$admin->click("document.querySelector('$logModal #refresh')");
$admin->check('Yii log：重新整理取得回應', $admin->waitFor($loaded, 10000));
$admin->click("document.querySelector('$logModal .btn-close')");
$admin->sleep(900);
$admin->check('Yii log：關閉對話框', !$admin->modalShown($logModal));
