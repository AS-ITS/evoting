<?php
/**
 * 管理端互動：頁面巡檢（pages）只確認畫面能開，這裡實際操作會送出請求或換頁的控制項
 *
 * - 輪次匯入：切換輪次下拉選單後導向所選輪次
 * - 表格每頁筆數：變更後設定保留，重新載入仍是新值
 *
 * @var Browser $admin
 */
$admin->section('interactions');
$vote = 'AnonPartyTest';

// 輪次匯入：fixture 的 AnonPartyTest 有第 1～4 輪
$admin->go("/question/round-import?voteID=$vote&round=1");
$roundSelect = "document.querySelector('select[name=round]')";
$hasRound2 = $admin->js("!!($roundSelect) && [...$roundSelect.options].some(o => o.value === '2')");
$admin->check('輪次匯入頁列出可切換的輪次', $hasRound2);
if ($hasRound2) {
    // 包成不回傳值的函式：jQuery 物件無法以值傳回
    $admin->js("(() => { $($roundSelect).val('2').trigger({type: 'select2:select', params: {data: {id: '2'}}}); })()");
    $switched = $admin->waitFor("new URLSearchParams(location.search).get('round') === '2'");
    $admin->waitLoad();
    $search = $admin->js('location.pathname + location.search');
    $admin->check('切換輪次後導向所選輪次', $switched
        && strpos($search, 'question/round-import') !== false
        && strpos($search, "voteID=$vote") !== false, $search);
    $problems = PageCheck::inspect($admin);
    $admin->check('切換輪次後頁面共用檢查', !$problems, implode(' / ', $problems));
}

// 每頁筆數：選一個和目前不同的值，變更後重新載入應保留；最後還原
$sizeSelect = "[...document.querySelectorAll('select')].find(e => (e.getAttribute('onchange') || '').includes('set-page-size'))";
$setSize = function ($value) use ($admin, $sizeSelect) {
    $admin->js("(() => { const s = $sizeSelect; s.value = ".json_encode($value)."; s.dispatchEvent(new Event('change', {bubbles: true})); })()");
    $admin->sleep(1500);
    $admin->go('/elect/index');
    return $admin->js("(() => { const s = $sizeSelect; return s ? s.value : null; })()");
};
$admin->go('/elect/index');
$original = $admin->js("(() => { const s = $sizeSelect; return s ? s.value : null; })()");
$admin->check('投票管理列表有每頁筆數選單', $original !== null, (string) $original);
if ($original !== null) {
    $other = $admin->js("(() => { const s = $sizeSelect; const o = [...s.options].find(o => o.value !== s.value); return o ? o.value : null; })()");
    $admin->check('每頁筆數有其他選項可切換', $other !== null);
    if ($other !== null) {
        $after = $setSize($other);
        $admin->check("每頁筆數改為 $other 後，重新載入仍保留", $after === $other, "原為 {$original}，重新載入後為 {$after}");
        $problems = PageCheck::inspect($admin);
        $admin->check('變更每頁筆數後頁面共用檢查', !$problems, implode(' / ', $problems));
        $restored = $setSize($original);
        $admin->check("每頁筆數還原為 $original", $restored === $original, (string) $restored);
    }
}
