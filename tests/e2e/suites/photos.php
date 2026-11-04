<?php
/**
 * 候選人照片：啟用照片欄位、上傳、預覽、各畫面顯示、刪除
 *
 * @var Browser $admin
 * @var Browser $guest
 * @var callable $file
 */
$admin->section('photos');
$vote = 'AnonNoPartyTest';
$cid = 57;
$config = "/candi/config?voteID=$vote";
$manual = "/candi/manual?voteID=$vote&action=update&id=$cid";

// 120x90 單色 PNG（以內建 zlib 產生）
$png = (function ($w, $h) {
    $chunk = function ($t, $d) { return pack('N', strlen($d)).$t.$d.pack('N', crc32($t.$d)); };
    $raw = str_repeat("\x00".str_repeat("\x2a\x7a\xc8", $w), $h);
    return "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $w, $h, 8, 2, 0, 0, 0)).$chunk('IDAT', gzcompress($raw)).$chunk('IEND', '');
})(120, 90);
$photo = $file('e2e_photo.png', $png);

/** 透過候選設定畫面設定顯示欄位（等同使用者拖曳後按「修改」） */
$setFields = function ($json) use ($admin, $config) {
    $admin->go($config);
    $admin->js('document.querySelector("#formcandiconfig-showfieldsort").value = '.json_encode($json));
    $admin->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.includes('修改'))");
    return $admin->waitLoad();
};

$admin->go($config);
$originalFields = $admin->js('document.querySelector("#formcandiconfig-showfieldsort").value');
$fields = json_decode($originalFields, true) ?: [];
if (!in_array('photo', array_column($fields, 'id'))) {
    $fields[] = ['id' => 'photo', 'children' => []];
}
$setFields(json_encode($fields));
$admin->go($manual);
$admin->check('候選設定加入照片欄位後，編輯頁出現照片上傳欄', $admin->js("!!document.querySelector('#formcandidata-photo')"));

// ---- 上傳 ----
$admin->setFiles('#formcandidata-photo', [$photo]);
$admin->click("[...document.querySelectorAll('form button[type=submit]')].find(e => e.innerText.includes('修改'))");
$admin->waitLoad();
$admin->check('照片上傳成功', (bool) array_filter($admin->flashes(), function ($m) { return mb_strpos($m, '修改完成') !== false; }), implode(' | ', $admin->flashes()));

// ---- 預覽對話框 ----
$admin->go($manual);
$admin->check('照片預覽：開啟', $admin->openModal("document.querySelector('[data-bs-target=\"#photo-view\"]')", '#photo-view'));
$admin->check('照片預覽：圖片載入', $admin->js("(() => { const i = document.querySelector('#photo-view img'); return !!i && i.complete && i.naturalWidth === 120; })()"));
$pos = $admin->js("(() => { const h = document.querySelector('#photo-view .modal-header'); const x = h.querySelector('.btn-close'); if (!x) return null; return {headerRight: Math.round(h.getBoundingClientRect().right), closeRight: Math.round(x.getBoundingClientRect().right)}; })()");
$admin->check('照片預覽：× 位於標題列右側', $pos && $pos['closeRight'] > $pos['headerRight'] - 60, json_encode($pos));
$admin->shot('photos-preview');
$admin->click("document.querySelector('#photo-view .btn-close')");
$admin->sleep(900);
$admin->check('照片預覽：點 × 關閉', !$admin->modalShown('#photo-view'));

// ---- 各畫面顯示照片 ----
$loaded = "(() => { const imgs = [...document.querySelectorAll('img')].filter(i => i.src.includes('view-photo') && i.src.includes('file=') && !/file=(&|$)/.test(i.src)); return {total: imgs.length, loaded: imgs.filter(i => i.complete && i.naturalWidth > 0).length}; })()";
foreach ([
    [$admin, '候選名單（後台）', "/candi/data?voteID=$vote"],
    [$guest, '投票說明頁（投票者）', "/vote/vote-detail?voteID=$vote"],
] as [$br, $label, $url]) {
    $status = $br->go($url);
    $br->sleep(600);
    $r = $br->js($loaded);
    $admin->check("{$label}：照片載入", $status === 200 && $r['total'] >= 1 && $r['loaded'] === $r['total'], "HTTP $status ".json_encode($r));
}
// 顯示照片欄位時，沒有照片的候選人不應出現破圖
PageCheck::run($admin, '候選名單（含照片欄位）', "/candi/data?voteID=$vote");
PageCheck::run($guest, '投票說明（含照片欄位）', "/vote/vote-detail?voteID=$vote");

// ---- 刪除 ----
$admin->go($manual);
$del = $admin->js("document.querySelector('#delete-photo') && document.querySelector('#delete-photo').href");
$admin->go($del, $manual);
$admin->check('刪除照片成功', (bool) array_filter($admin->flashes(), function ($m) { return mb_strpos($m, '刪除照片成功') !== false; }));
$admin->check('無照片時候選名單頁 HTTP 200', $admin->go("/candi/data?voteID=$vote") === 200);

// 還原候選設定
$setFields($originalFields);
