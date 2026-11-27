<?php
/**
 * 列印模式（views/partials/print-mode.php，計票單、投票結果等頁共用）
 *
 * - 未進入過列印模式時，ESC 不動作
 * - 「列印模式」按鈕：切換版面並開簽名欄位視窗；選擇後出現提示框
 * - 提示框開著按 ESC：提示框關閉並回到顯示模式（不可殘留在畫面上）
 * - 之後 ESC 在列印／顯示之間切換，不再開簽名欄位視窗，保留已選的簽名欄位
 * - 簽名欄位視窗開著按 ESC：視窗關閉並回到顯示模式
 * - 下拉選單開著按 ESC：只關閉選單，不切換
 *
 * @var Browser $admin
 */
$admin->section('print-mode');

$admin->go('/count/index?voteID=AnonPartyTest');
$admin->sleep(500);

$state = function () use ($admin) {
    return $admin->js("(() => {
        const shown = el => !!el && getComputedStyle(el).display !== 'none';
        const box = document.querySelector('main').children[0];
        return {
            print: box.classList.contains('container-fluid') && !box.classList.contains('container'),
            header: shown(document.querySelector('header')),
            button: shown(document.querySelector('.render-type')),
            signModal: !!document.querySelector('#print-sign-modal.show'),
            info: [...document.querySelectorAll('.bootstrap-dialog.show')].some(d => d.textContent.includes('重新整理退出列印模式')),
            modals: document.querySelectorAll('.modal.show').length,
            signText: (document.querySelector('#sign-area h3') || {}).textContent || ''
        };
    })()");
};
$esc = function () use ($admin) {
    $admin->key('Escape', 'Escape', 27);
    $admin->sleep(600);
};
$isView = fn($s) => !$s['print'] && $s['header'] && $s['button'];
$isPrint = fn($s) => $s['print'] && !$s['header'] && !$s['button'];
$fmt = fn($s) => json_encode($s, JSON_UNESCAPED_UNICODE);

$s = $state();
$admin->check('計票單一開始是顯示模式', $isView($s), $fmt($s));

$esc();
$s = $state();
$admin->check('未進入過列印模式時 ESC 不動作', $isView($s), $fmt($s));

$admin->click("document.querySelector('.render-type')");
$admin->sleep(800);
$s = $state();
$admin->check('「列印模式」按鈕：切換為列印版面並開啟簽名欄位視窗', $isPrint($s) && $s['signModal'], $fmt($s));

$admin->click("document.querySelector('#print-sign-modal [data-sign-layout=\"chair\"]')");
$admin->sleep(900);
$s = $state();
$admin->check('選擇「僅主席」後出現提示框、簽名欄位已填入', $isPrint($s) && $s['info'] && !$s['signModal'] && strpos($s['signText'], '主') !== false, $fmt($s));

$esc();
$s = $state();
$admin->check('提示框開著按 ESC：提示框關閉並回到顯示模式', $isView($s) && !$s['info'] && $s['modals'] === 0, $fmt($s));

$esc();
$s = $state();
$admin->check('再按 ESC：回到列印模式，不再開簽名欄位視窗', $isPrint($s) && !$s['signModal'] && $s['modals'] === 0, $fmt($s));
$admin->check('切回列印模式時保留已選的簽名欄位', strpos($s['signText'], '主') !== false, $s['signText']);

$esc();
$s = $state();
$admin->check('再按 ESC：回到顯示模式', $isView($s), $fmt($s));

// 下拉選單開著：ESC 只關閉選單
$admin->click("document.querySelector('.hide-print [data-bs-toggle=\"dropdown\"]')");
$admin->sleep(400);
$menuOpen = $admin->js("!!document.querySelector('.dropdown-menu.show')");
$esc();
$s = $state();
$admin->check('匯出選單開著按 ESC：只關閉選單，不切換版面', $menuOpen && $isView($s) && !$admin->js("!!document.querySelector('.dropdown-menu.show')"), $fmt($s));

// 列印模式中再按 Ctrl+Alt+P：維持列印版面（原本會再 toggle 一次而切回顯示）
$esc();
$admin->js("document.dispatchEvent(new KeyboardEvent('keydown', {key: 'p', ctrlKey: true, altKey: true, bubbles: true}))");
$admin->sleep(800);
$s = $state();
$admin->check('列印模式中按 Ctrl+Alt+P：維持列印版面並開啟簽名欄位視窗', $isPrint($s) && $s['signModal'], $fmt($s));

$esc();
$s = $state();
$admin->check('簽名欄位視窗開著按 ESC：視窗關閉並回到顯示模式', $isView($s) && $s['modals'] === 0, $fmt($s));

$exceptions = $admin->jsExceptions();
$admin->check('列印模式操作沒有 JS 例外', !$exceptions, implode(' / ', $exceptions));

// 共用同一段程式、沒有「列印模式」按鈕的頁面：以 Ctrl+Alt+P 進入
$ctrlAltP = "document.dispatchEvent(new KeyboardEvent('keydown', {key: 'p', ctrlKey: true, altKey: true, bubbles: true}))";
// 投票結果用表決投票：匿名投票未登入會導向投票密碼頁
foreach (['投票結果' => '/vote/result?voteID=NoAuthVoteTest', '開票狀態' => '/ballot-work/status?voteID=AnonPartyTest'] as $name => $url) {
    $admin->go($url);
    $admin->sleep(500);
    $admin->js($ctrlAltP);
    $admin->sleep(800);
    $s = $state();
    $entered = $s['print'] && !$s['header'] && $s['signModal'];
    $admin->click("document.querySelector('#print-sign-modal [data-sign-layout=\"none\"]')");
    $admin->sleep(900);
    $esc();
    $s1 = $state();
    $esc();
    $s2 = $state();
    $esc();
    $s3 = $state();
    $admin->check("$name：Ctrl+Alt+P 進入列印模式，ESC 關閉提示框並在顯示／列印間切換",
        $entered && !$s1['print'] && $s1['header'] && $s1['modals'] === 0
        && $s2['print'] && !$s2['header'] && $s2['modals'] === 0
        && !$s3['print'] && $s3['header'],
        $fmt($s1).' → '.$fmt($s2).' → '.$fmt($s3));
}
