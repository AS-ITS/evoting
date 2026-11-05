<?php
/**
 * 個別畫面的版面細節
 *
 * @var Browser $admin
 * @var Browser $guest
 */
$guest->section('layout');

// 投票密碼頁：密碼欄與眼睛按鈕相接、圓角在右側
$guest->go('/site/password?voteID=AnonNoPartyTest');
$r = $guest->js("(() => { const e = document.querySelector('#eye'), p = document.querySelector('#passwd'); if (!e || !p) return null; return {gap: Math.round(e.getBoundingClientRect().left - p.getBoundingClientRect().right), eye: e.className}; })()");
$guest->check('投票密碼頁：密碼欄與眼睛按鈕相接', $r && abs($r['gap']) <= 2 && strpos($r['eye'], 'rounded-end') !== false, json_encode($r));
$guest->click("document.querySelector('#eye')");
$guest->sleep(300);
$guest->check('投票密碼頁：點眼睛可顯示密碼', $guest->js("document.querySelector('#passwd').type === 'text'"));
$guest->shot('layout-password');

// 候選設定頁：藍色小標題可見
$admin->group = 'layout';
$admin->go('/candi/config?voteID=AnonNoPartyTest');
$r = $admin->js("(() => { const bs = [...document.querySelectorAll('.badge.bg-primary')]; return {count: bs.length, visible: bs.filter(e => getComputedStyle(e).backgroundColor !== 'rgba(0, 0, 0, 0)').length}; })()");
$admin->check('候選設定頁：藍色小標題可見', $r['count'] > 0 && $r['count'] === $r['visible'], json_encode($r));

// 問題新增：「自訂規則」核取方塊可勾選
$admin->go('/question/create?voteID=AnonPartyTest');
$before = $admin->js("document.querySelector('#vote-rule-check') && document.querySelector('#vote-rule-check').checked");
$admin->click("document.querySelector('label[for=vote-rule-check]')");
$admin->sleep(300);
$admin->check('新增問題：「自訂規則」核取方塊可勾選', $before === false && $admin->js("document.querySelector('#vote-rule-check').checked"));
$admin->shot('layout-question-rule');
