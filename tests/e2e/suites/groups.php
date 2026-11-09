<?php
/**
 * 群組成員：新增 → 重複新增被擋 → 修改權限 → 刪除
 *
 * 以 fixture 群組 1 與尚未加入的帳號 test_va2 操作，結束時刪除；
 * 開始前若上次中斷留下 test_va2 也會先刪除，可重複執行。
 *
 * @var Browser $admin
 */
$admin->section('groups');
$groupId = 1;
$cn = 'test_va2';
// 列表顯示成員姓名，以修改／刪除連結的 cn 參數找出該列
$row = "[...document.querySelectorAll('#group-members tbody tr')].find(tr => [...tr.querySelectorAll('a[href]')].some(a => new URL(a.href, location.href).searchParams.get('cn') === '$cn'))";
$hasFlash = function (Browser $b, $text) {
    return (bool) array_filter($b->flashes(), function ($m) use ($text) { return mb_strpos($m, $text) !== false; });
};
// kartik 對話框取代 data-confirm，按「確定」
$confirmOk = "[...document.querySelectorAll('.modal.show button')].find(e => /^(確定|OK|Ok)$/.test(e.innerText.trim()) && e.offsetParent !== null)";
$deleteMember = function () use ($admin, $groupId, $row, $confirmOk) {
    $admin->go("/group-member/index?groupId=$groupId");
    if (!$admin->js("!!($row)")) {
        return null;
    }
    $admin->click("($row).querySelector('a[data-method=post]')");
    if (!$admin->waitFor("!!($confirmOk)", 5000)) {
        return false;
    }
    $admin->click($confirmOk);
    $admin->waitLoad();
    return true;
};

// 清掉上次中斷留下的資料
$deleteMember();

// 新增：查詢帳號 → 點名字開啟對話框 → 設定權限 → 新增
$admin->go("/group-member/clear-search?groupId=$groupId");
$admin->go("/group-member/create?groupId=$groupId");
$admin->js("document.querySelector('#labelCn').value = '$cn'");
$admin->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.includes('查詢'))");
$admin->waitLoad();
$trigger = "document.querySelector('[data-bs-target=\"#createMember\"][data-cn=\"$cn\"]')";
if ($admin->check("查詢帳號 $cn 有結果", $admin->js("!!$trigger"))) {
    $admin->check('開啟新增對話框', $admin->openModal($trigger, '#createMember'));
    $admin->check('對話框帶入帳號', $admin->js("document.querySelector('#formgroupmember-cn').value") === $cn);
    $admin->js("(() => { document.querySelector('#createMember #formgroupmember-iswrite').value = 'Y'; document.querySelector('#createMember #formgroupmember-isowner').value = 'N'; })()");
    $admin->click("[...document.querySelectorAll('#createMember button[type=submit]')].find(e => e.innerText.includes('新增'))");
    $admin->waitLoad();
    $admin->check('新增成員成功', $hasFlash($admin, '群組成員新增完成'), implode(' | ', $admin->flashes()));

    // 重複新增同一帳號應被擋下
    $admin->check('重新開啟新增對話框', $admin->openModal($trigger, '#createMember'));
    $admin->click("[...document.querySelectorAll('#createMember button[type=submit]')].find(e => e.innerText.includes('新增'))");
    $admin->waitLoad();
    $admin->check('重複新增顯示「該成員已存在」', $hasFlash($admin, '該成員已存在'), implode(' | ', $admin->flashes()));
}
$admin->go("/group-member/clear-search?groupId=$groupId");

// 成員列表看得到新成員
PageCheck::run($admin, '群組成員列表（含新成員）', "/group-member/index?groupId=$groupId");
$admin->check("列表出現 $cn", $admin->js("!!($row)"));

// 修改權限：允許編輯群組
$admin->go("/group-member/update?groupId=$groupId&cn=$cn");
$admin->check('修改頁帶入目前權限', $admin->js("document.querySelector('#formgroupmember-iswrite').value") === 'Y');
$admin->js("document.querySelector('#formgroupmember-isowner').value = 'Y'");
$admin->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.includes('修改'))");
$admin->waitLoad();
$admin->check('修改成員成功', $hasFlash($admin, '群組成員修改成功'), implode(' | ', $admin->flashes()));
$admin->go("/group-member/update?groupId=$groupId&cn=$cn");
$admin->check('修改後權限已儲存', $admin->js("document.querySelector('#formgroupmember-isowner').value") === 'Y');

// 刪除（確認對話框按「確定」）
$deleted = $deleteMember();
$admin->check('刪除確認對話框出現並送出', $deleted === true);
$admin->check('刪除成員成功', $hasFlash($admin, '群組成員刪除成功'), implode(' | ', $admin->flashes()));
$admin->check("列表不再出現 $cn", !$admin->js("!!($row)"));
$admin->shot('groups-after-delete');
