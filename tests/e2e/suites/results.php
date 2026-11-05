<?php
/**
 * 開票結果頁的候選人照片
 *
 * results 資料表沒有照片欄位，照片須由候選人資料（candiData）取得。
 * 以有選票的 AnonPartyTest：候選設定加入照片欄位 → 上傳照片 → 計票結果設定加入照片欄位
 * → 檢查後台開票結果、投票結果的照片都有載入，最後還原。
 *
 * @var Browser $admin
 * @var callable $file
 */
$admin->section('results');
$vote = 'AnonPartyTest';

$png = (function ($w, $h) {
    $chunk = function ($t, $d) { return pack('N', strlen($d)).$t.$d.pack('N', crc32($t.$d)); };
    $raw = str_repeat("\x00".str_repeat("\xc8\x5a\x2a", $w), $h);
    return "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $w, $h, 8, 2, 0, 0, 0)).$chunk('IDAT', gzcompress($raw)).$chunk('IEND', '');
})(100, 80);
$photo = $file('e2e_result_photo.png', $png);

/** 讀取／設定隱藏欄位後按「修改」 */
$saveHidden = function ($url, $inputId, $value) use ($admin) {
    $admin->go($url);
    $admin->js('document.querySelector("#'.$inputId.'").value = '.json_encode($value));
    $admin->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.trim() === '修改')");
    return $admin->waitLoad();
};

// 1. 候選設定加入照片欄位（此場次依問題設定，取第一位候選人所屬問題）
$candId = 1;
$manual = "/candi/manual?voteID=$vote&action=update&id=$candId";
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

// 2. 上傳候選人照片
$admin->go($manual);
if ($admin->check('候選人編輯頁有照片上傳欄', $admin->js("!!document.querySelector('#formcandidata-photo')"))) {
    $admin->setFiles('#formcandidata-photo', [$photo]);
    $admin->click("[...document.querySelectorAll('form button[type=submit]')].find(e => e.innerText.includes('修改'))");
    $admin->waitLoad();
    $admin->check('上傳候選人照片', (bool) array_filter($admin->flashes(), function ($m) { return mb_strpos($m, '修改完成') !== false; }), implode(' | ', $admin->flashes()));
}

// 3. 計票結果設定加入照片欄位
$resultConfigUrl = "/count/result?voteID=$vote";
$admin->go($resultConfigUrl);
$origResultFields = $admin->js('document.querySelector("#formresultsconfig-showfieldsort").value');
$resultFields = array_filter(explode(',', $origResultFields));
if (!in_array('photo', $resultFields)) {
    $resultFields[] = 'photo';
}
$saveHidden($resultConfigUrl, 'formresultsconfig-showfieldsort', implode(',', $resultFields));
$admin->go($resultConfigUrl);
$admin->check('計票結果設定加入照片欄位', in_array('photo', explode(',', $admin->js('document.querySelector("#formresultsconfig-showfieldsort").value'))));

// 開票（產生開票結果）
// 尚未開票時才有「開票」按鈕（先前流程可能已開過票）
$countBtn = "[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.trim() === '開票')";
if ($admin->js("!!($countBtn)")) {
    $admin->click($countBtn);
    $admin->waitLoad();
}
$admin->check('開票', $admin->go("/result/index?voteID=$vote") === 200 && !$admin->js("document.body.innerText.includes('沒有資料')"), implode(' | ', $admin->flashes()));

// 4. 各結果頁照片
$photoImgs = "(() => { const imgs = [...document.querySelectorAll('img')].filter(i => i.src.includes('view-photo')); return {total: imgs.length, loaded: imgs.filter(i => i.complete && i.naturalWidth === 100).length, broken: imgs.filter(i => i.complete && i.naturalWidth === 0).length}; })()";
foreach ([
    '開票結果（後台）' => "/ballot-work/result?voteID=$vote",
    '投票結果' => "/result/index?voteID=$vote",
    // 前台 /vote/result 對匿名投票須以投票密碼登入，fixture 密碼為加密值無法登入，需人工確認
] as $label => $url) {
    $status = $admin->go($url);
    $admin->sleep(800);
    $r = $admin->js($photoImgs);
    $admin->check("{$label}：顯示候選人照片", $status === 200 && $r['loaded'] >= 1 && $r['broken'] === 0, "HTTP $status ".json_encode($r));
    $admin->shot("results-$label");
}

// 5. 還原：刪除照片、還原計票結果設定與候選設定
$admin->go($manual);
$del = $admin->js("document.querySelector('#delete-photo') && document.querySelector('#delete-photo').href");
if ($del) {
    $admin->go($del, $manual);
}
$saveHidden($resultConfigUrl, 'formresultsconfig-showfieldsort', $origResultFields);
if (isset($origCandiFields)) {
    $saveHidden($configUrl, 'formcandiconfig-showfieldsort', $origCandiFields);
}
