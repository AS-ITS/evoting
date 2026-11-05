<?php
/**
 * 開票結果頁的候選人照片
 *
 * results 資料表沒有照片欄位，照片須由候選人資料（candiData）取得。
 * 以有選票的 AnonPartyTest：開票 → 取結果中的第一位候選人 → 候選設定加入照片欄位並上傳照片
 * → 計票結果設定加入照片欄位 → 檢查後台開票結果、投票結果的照片都有載入，最後還原。
 *
 * 前台 /vote/result 以 fixture 投票密碼（明碼 testN*）登入後檢查。
 *
 * @var Browser $admin
 * @var callable $file
 */
$admin->section('results');
$vote = 'AnonPartyTest';
$resultConfigUrl = "/count/result?voteID=$vote";

$png = (function ($w, $h) {
    $chunk = function ($t, $d) { return pack('N', strlen($d)).$t.$d.pack('N', crc32($t.$d)); };
    $raw = str_repeat("\x00".str_repeat("\xc8\x5a\x2a", $w), $h);
    return "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $w, $h, 8, 2, 0, 0, 0)).$chunk('IDAT', gzcompress($raw)).$chunk('IEND', '');
})(100, 80);
$photo = $file('e2e_result_photo.png', $png);

/** 設定隱藏欄位後按「修改」 */
$saveHidden = function ($url, $inputId, $value) use ($admin) {
    $admin->go($url);
    $admin->js('document.querySelector("#'.$inputId.'").value = '.json_encode($value));
    $admin->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.trim() === '修改')");
    return $admin->waitLoad();
};

// 1. 開票（尚未開票時才有「開票」按鈕），取結果中的第一位候選人
$admin->go($resultConfigUrl);
$countBtn = "[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.trim() === '開票')";
if ($admin->js("!!($countBtn)")) {
    $admin->click($countBtn);
    $admin->waitLoad();
}
$admin->go("/result/index?voteID=$vote");
$candId = $admin->js("(() => { const r = document.querySelector('table tbody tr[data-key]'); return r ? r.getAttribute('data-key') : null; })()");
if (!$admin->check('開票後有結果資料', !empty($candId))) {
    return;
}
$manual = "/candi/manual?voteID=$vote&action=update&id=$candId";

try {
    // 2. 候選設定加入照片欄位（此場次依問題設定，取該候選人所屬問題）
    $admin->go($manual);
    $questionId = $admin->js("document.querySelector('#formcandidata-questionid') && document.querySelector('#formcandidata-questionid').value");
    $configUrl = "/candi/config?voteID=$vote".($questionId ? "&questionID=$questionId" : '');
    $admin->go($configUrl);
    if ($admin->js("!!document.querySelector('#formcandiconfig-showfieldsort')")) {
        $origCandiFields = $admin->js('document.querySelector("#formcandiconfig-showfieldsort").value');
        $fields = json_decode($origCandiFields, true) ?: [];
        if (!in_array('photo', array_column($fields, 'id'))) {
            $fields[] = ['id' => 'photo', 'children' => []];
        }
        $saveHidden($configUrl, 'formcandiconfig-showfieldsort', json_encode($fields));
    }

    // 3. 上傳候選人照片
    $admin->go($manual);
    if ($admin->check('候選人編輯頁有照片上傳欄', $admin->js("!!document.querySelector('#formcandidata-photo')"))) {
        $admin->setFiles('#formcandidata-photo', [$photo]);
        $admin->click("[...document.querySelectorAll('form button[type=submit]')].find(e => e.innerText.includes('修改'))");
        $admin->waitLoad();
        $admin->check('上傳候選人照片', (bool) array_filter($admin->flashes(), function ($m) { return mb_strpos($m, '修改完成') !== false; }), implode(' | ', $admin->flashes()));
    }

    // 4. 計票結果設定加入照片欄位
    $admin->go($resultConfigUrl);
    $origResultFields = $admin->js('document.querySelector("#formresultsconfig-showfieldsort").value');
    $resultFields = array_filter(explode(',', $origResultFields));
    if (!in_array('photo', $resultFields)) {
        $resultFields[] = 'photo';
    }
    $saveHidden($resultConfigUrl, 'formresultsconfig-showfieldsort', implode(',', $resultFields));
    $admin->go($resultConfigUrl);
    $admin->check('計票結果設定加入照片欄位', in_array('photo', explode(',', $admin->js('document.querySelector("#formresultsconfig-showfieldsort").value'))));

    // 5. 各結果頁照片（該候選人那一列）
    $rowPhoto = "(() => { const r = document.querySelector('table tbody tr[data-key=\"$candId\"]'); if (!r) return {row: false}; const i = r.querySelector('img[src*=\"view-photo\"]'); return {row: true, img: !!i, loaded: !!i && i.complete && i.naturalWidth === 100}; })()";
    foreach ([
        '開票結果（後台）' => "/ballot-work/result?voteID=$vote",
        '投票結果' => "/result/index?voteID=$vote",
    ] as $label => $url) {
        $status = $admin->go($url);
        $admin->sleep(800);
        $r = $admin->js($rowPhoto);
        $admin->check("{$label}：顯示候選人照片", $status === 200 && $r['row'] && $r['loaded'], "HTTP $status ".json_encode($r));
        $admin->shot("results-$label");
        $problems = PageCheck::inspect($admin);
        $admin->check("{$label}：含照片欄位時共用檢查", !$problems, implode(' / ', $problems));
    }

    // 前台投票結果：匿名投票須先以投票密碼登入（AnonPartyTest 明碼為 testN1～testN100）
    $resultVoter = new Browser($cdpUrl, $base, $outDir, true);
    $resultVoter->group = 'results';
    $browsers[] = $resultVoter;
    $resultVoter->go("/site/password?voteID=$vote");
    $resultVoter->js('document.querySelector("#passwd").value = '.json_encode('testN'.random_int(60, 100)));
    $resultVoter->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.includes('登入'))");
    $resultVoter->waitLoad();
    $status = $resultVoter->go("/vote/result?voteID=$vote");
    $resultVoter->sleep(800);
    // 前台表格列沒有 data-key，改檢查頁面上是否有載入測試照片（100x80）
    $r = $resultVoter->js("(() => { const i = [...document.querySelectorAll('img[src*=\"view-photo\"]')].find(i => i.complete && i.naturalWidth === 100); return {row: true, loaded: !!i}; })()");
    $resultVoter->check('投票結果（前台，投票者登入後）：顯示候選人照片', $status === 200 && $r['row'] && $r['loaded'], "HTTP $status ".json_encode($r).' '.$resultVoter->js('location.pathname'));
    $resultVoter->shot('results-投票結果_前台_');
} finally {
    // 6. 還原：刪除照片、還原計票結果設定與候選設定（中途失敗也執行）
    $admin->go($manual);
    $del = $admin->js("document.querySelector('#delete-photo') && document.querySelector('#delete-photo').href");
    if ($del) {
        $admin->go($del, $manual);
    }
    if (isset($origResultFields)) {
        $saveHidden($resultConfigUrl, 'formresultsconfig-showfieldsort', $origResultFields);
    }
    if (isset($origCandiFields)) {
        $saveHidden($configUrl, 'formcandiconfig-showfieldsort', $origCandiFields);
    }
}
