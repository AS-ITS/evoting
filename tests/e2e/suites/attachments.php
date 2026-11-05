<?php
/**
 * 投票附件：上傳、下載、路徑穿越、刪除
 *
 * @var Browser $admin
 * @var Browser $guest
 * @var callable $file 建立測試檔 ($name, $content) => 路徑
 */
$admin->section('attachments');
$vote = 'NoAuthVoteTest';
$edit = "/elect/edit-vote?voteID=$vote";
$detail = "/vote/vote-detail?voteID=$vote";
$uploadBtn = "[...document.querySelectorAll('button')].find(e => e.getAttribute('data-bs-target') === '#fileUpload')";
$listJs = "[...document.querySelectorAll('#fileUpload tbody tr td:first-child')].map(e => e.innerText.trim())";
$deleteLinksJs = "[...document.querySelectorAll('#fileUpload a#delete-file')].map(a => a.href)";

$doc1 = $file('e2e_doc1.txt', "E2E attachment one content\n");
$csvZh = $file('中文附件.csv', "姓名,單位\n王小明,資訊處\n");
$doc2 = $file('e2e_doc2.txt', "E2E attachment two content\n");
$html = $file('e2e_page.html', "<!doctype html><html><body><h1>x</h1></body></html>\n");
$fakePdf = $file('e2e_fake.pdf', "\x7FELF\x02\x01\x01\x00".str_repeat("\x00", 200));
$extra = [];
for ($i = 3; $i <= 5; $i++) {
    $extra[] = $file("e2e_extra$i.txt", "E2E extra file number $i\n");
}

$upload = function (array $paths) use ($admin, $edit, $uploadBtn) {
    $admin->go($edit);
    $admin->openModal($uploadBtn, '#fileUpload');
    $admin->setFiles('#fileUpload input[type=file]', $paths);
    $admin->click("[...document.querySelectorAll('#fileUpload button[type=submit]')].find(e => e.innerText.includes('上傳'))");
    $admin->waitLoad();
    return $admin->flashes();
};
$has = function (array $messages, ...$needles) {
    foreach ($messages as $m) {
        $all = true;
        foreach ($needles as $n) {
            $all = $all && mb_strpos($m, $n) !== false;
        }
        if ($all) {
            return true;
        }
    }
    return false;
};
$clearAll = function () use ($admin, $edit, $deleteLinksJs) {
    $admin->go($edit);
    foreach ($admin->js($deleteLinksJs) ?: [] as $href) {
        $admin->go($href, $edit);
    }
};

$clearAll();

// ---- 上傳 ----
$m = $upload([$doc1, $csvZh]);
$admin->check('一次選 2 個檔（含中文檔名）都上傳成功', $has($m, '檔案上傳成功', 'e2e_doc1.txt', '中文附件.csv'), implode(' | ', $m));
$admin->go($edit);
$list = $admin->js($listJs);
$admin->check('附件列表顯示兩個檔案', in_array('e2e_doc1.txt', $list) && in_array('中文附件.csv', $list), implode(', ', $list));

$m = $upload([$doc1, $doc2]);
$admin->check('同名檔被擋、另一檔照常上傳', $has($m, '檔案上傳成功', 'e2e_doc2.txt') && $has($m, 'e2e_doc1.txt', '已有同名檔案'), implode(' | ', $m));
$admin->shot('attachments-duplicate');

$m = $upload([$html]);
$admin->check('html 被擋並顯示原因', $has($m, '檔案上傳失敗', '擴充名'), implode(' | ', $m));
$m = $upload([$fakePdf]);
$admin->check('執行檔改名 .pdf 被擋（內容與副檔名不符）', $has($m, '內容與副檔名不符'), implode(' | ', $m));
$m = $upload($extra);
$admin->check('附件總數超過 5 個被擋', $has($m, '上限為5個'), implode(' | ', $m));

// ---- 投票說明頁下載（未登入的投票者）----
$guest->group = 'attachments';
$guest->check('投票說明頁 HTTP 200', $guest->go($detail) === 200);
$guest->check('有「參考附件」按鈕', $guest->js("[...document.querySelectorAll('a')].some(a => a.innerText.includes('參考附件'))"));
$rows = $guest->js("[...document.querySelectorAll('a[href*=\"download-file\"]')].map(a => ({name: a.closest('tr').querySelector('td').innerText.trim(), href: a.href}))");
$expect = ['e2e_doc1.txt' => file_get_contents($doc1), 'e2e_doc2.txt' => file_get_contents($doc2), '中文附件.csv' => file_get_contents($csvZh)];
$guest->check('附件連結共 3 個', count($rows) === 3, count($rows).' 個');
foreach ($rows as $r) {
    $got = $guest->js('fetch('.json_encode($r['href']).').then(r => r.text())');
    $guest->check("下載「{$r['name']}」內容正確", isset($expect[$r['name']]) && $got === $expect[$r['name']]);
}
$guest->shot('attachments-vote-detail');

// ---- 路徑穿越與不合法參數 ----
foreach ([
    ['guest', "/vote/download-file?voteID=$vote&file=0"],
    ['guest', "/vote/download-file?voteID=$vote&file=1"],
    ['guest', "/vote/download-file?voteID=$vote&file=9999"],
    ['guest', "/vote/download-file?voteID=$vote&file=-1"],
    ['guest', "/vote/download-file?voteID=$vote&file=".rawurlencode('../../../../etc/passwd')],
    ['guest', '/vote/download-file?voteID='.rawurlencode('../candidatePic').'&file=2'],
    ['guest', '/candi/view-photo?voteID=AnonNoPartyTest&file='.rawurlencode('../../../../etc/passwd')],
    ['guest', '/candi/view-photo?voteID=..&file=x.png'],
    ['admin', "/elect/delete-file?voteID=$vote&file=".rawurlencode('../e2e_not_here.txt')],
    ['admin', "/elect/delete-file?voteID=$vote&file=".rawurlencode('../../candidatePic/e2e_not_here.txt')],
    ['admin', '/elect/delete-file?voteID=..&file=e2e_doc1.txt'],
] as [$who, $u]) {
    $br = $who === 'admin' ? $admin : $guest;
    $status = $br->js('fetch('.json_encode($br->url($u)).", {redirect: 'manual'}).then(r => r.status)");
    $admin->check("回 404：$u", $status === 404, "HTTP $status");
}
$admin->go($edit);
$admin->check('攻擊後附件數量不變（3 個）', count($admin->js($listJs)) === 3);

// ---- 刪除 ----
$hrefs = $admin->js($deleteLinksJs);
$admin->check('刪除連結帶檔名（非序號）', $hrefs && !array_filter($hrefs, function ($h) { return preg_match('/[?&]file=\d+(&|$)/', $h); }));
$admin->go($hrefs[0], $edit);
$admin->check('刪除單一附件成功', $has($admin->flashes(), '檔案刪除成功'));
$clearAll();
$admin->go($edit);
$admin->check('附件全部刪光', count($admin->js($listJs)) === 0);
$admin->check('附件全刪後投票說明頁 HTTP 200', $guest->go($detail) === 200);
$admin->check('附件全刪後沒有「參考附件」按鈕', !$guest->js("[...document.querySelectorAll('a')].some(a => a.innerText.includes('參考附件'))"));
