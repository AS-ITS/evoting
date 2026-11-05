<?php
/**
 * 頁首投票資訊徽章（輪次、投票狀態、捲動後的投票名稱）
 *
 * @var Browser $admin
 */
$admin->section('header');
$admin->go('/elect/edit-vote?voteID=NoAuthVoteTest');

$opaque = "(el) => { const c = getComputedStyle(el).backgroundColor; return c !== 'rgba(0, 0, 0, 0)' && c !== 'transparent'; }";
foreach (['輪次', '投票狀態'] as $label) {
    $ok = $admin->js("(() => { const el = [...document.querySelectorAll('.badge')].find(e => e.innerText.includes('$label')); if (!el) return false; const r = el.getBoundingClientRect(); return r.width > 0 && ($opaque)(el) && getComputedStyle(el).color !== getComputedStyle(el).backgroundColor; })()");
    $admin->check("「{$label}」徽章可見", $ok);
}
$admin->shot('header-badges');

$admin->js('OverlayScrollbars(document.body).scroll({y: 700})');
$admin->sleep(800);
$fix = $admin->js("(() => { const el = document.querySelector('.fixme-vote'); return el ? {cls: el.className, bg: getComputedStyle(el).backgroundColor} : null; })()");
$admin->check('捲動後投票名稱徽章為 bg-primary 且有底色', $fix && strpos($fix['cls'], 'bg-primary') !== false && $fix['bg'] !== 'rgba(0, 0, 0, 0)', json_encode($fix));
$admin->shot('header-scrolled');
