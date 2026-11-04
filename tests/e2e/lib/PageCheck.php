<?php
/**
 * 每個畫面共用的檢查
 */
class PageCheck
{
    /** Bootstrap 4 專用、在 Bootstrap 5 已移除或改名的 class／屬性 */
    const LEGACY_SELECTORS = [
        '.badge-primary', '.badge-secondary', '.badge-success', '.badge-danger', '.badge-warning', '.badge-info', '.badge-light', '.badge-dark', '.badge-pill',
        '.custom-control', '.custom-select', '.custom-file', '.custom-switch', '.custom-range',
        '.input-group-append', '.input-group-prepend', '.btn-block', '.form-row', '.form-inline', '.no-gutters', '.sr-only', '.jumbotron',
        '.float-left', '.float-right', '.text-left', '.text-right', '.font-weight-bold', '.font-weight-normal', '.font-weight-light', '.font-italic', '.text-monospace',
        '.rounded-left', '.rounded-right', '.border-left', '.border-right', '.border-left-0', '.border-right-0', '.ml-auto', '.mr-auto',
        '.ml-0', '.ml-1', '.ml-2', '.ml-3', '.ml-4', '.ml-5', '.mr-0', '.mr-1', '.mr-2', '.mr-3', '.mr-4', '.mr-5',
        '.pl-0', '.pl-1', '.pl-2', '.pl-3', '.pl-4', '.pl-5', '.pr-0', '.pr-1', '.pr-2', '.pr-3', '.pr-4', '.pr-5',
        '[data-toggle]', '[data-dismiss]', '[data-target]', 'button.close',
    ];

    /**
     * 載入頁面並執行共用檢查
     *
     * @param Browser $b
     * @param string $label 畫面名稱
     * @param string $path 網址路徑
     * @param array $opt expectStatus（預設 200）、allowLogin（允許導向登入頁）
     * @return array 頁面資訊
     */
    public static function run(Browser $b, $label, $path, array $opt = [])
    {
        $expect = $opt['expectStatus'] ?? 200;
        $status = $b->go($path);
        $b->sleep(300);
        $info = $b->js(self::inspectJs());
        $exceptions = $b->jsExceptions();
        $shot = $b->shot($b->group.'-'.$label);

        $problems = [];
        if ($status !== $expect) {
            $problems[] = "HTTP {$status}（預期 {$expect}）";
        }
        if (empty($opt['allowLogin']) && preg_match('#/auth/login#', $info['url']) && strpos($path, '/auth/login') === false) {
            $problems[] = '被導向登入頁';
        }
        if ($info['phpErr']) {
            $problems[] = "頁面含錯誤訊息「{$info['phpErr']}」";
        }
        if ($exceptions) {
            $problems[] = 'JS 例外：'.implode('；', array_slice($exceptions, 0, 2));
        }
        if ($info['legacy']) {
            $problems[] = 'Bootstrap 4 殘留：'.implode(', ', $info['legacy']);
        }
        if ($info['badges']) {
            $problems[] = '看不見的徽章：'.implode(', ', $info['badges']);
        }
        if ($info['imgs']) {
            $problems[] = '破圖：'.implode(', ', $info['imgs']);
        }
        $b->check("$label  $path", !$problems, $problems ? implode(' / ', $problems) : '');
        return $info + ['status' => $status, 'shot' => $shot];
    }

    /**
     * 不重新載入，檢查目前畫面（用於 JS 動態產生內容之後）
     *
     * @return string[] 問題清單，空陣列表示通過
     */
    public static function inspect(Browser $b)
    {
        $info = $b->js(self::inspectJs());
        $problems = [];
        if ($info['legacy']) {
            $problems[] = 'Bootstrap 4 殘留：'.implode(', ', $info['legacy']);
        }
        if ($info['badges']) {
            $problems[] = '看不見的徽章：'.implode(', ', $info['badges']);
        }
        if ($info['imgs']) {
            $problems[] = '破圖：'.implode(', ', $info['imgs']);
        }
        if ($ex = $b->jsExceptions()) {
            $problems[] = 'JS 例外：'.implode('；', array_slice($ex, 0, 2));
        }
        return $problems;
    }

    private static function inspectJs()
    {
        $legacy = json_encode(self::LEGACY_SELECTORS);
        return <<<JS
(() => {
  const vis = el => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none'; };
  const text = document.body ? document.body.innerText : '';
  const m = /(PHP (Fatal error|Warning|Notice|Deprecated)|Stack trace:|ErrorException|Undefined (variable|array key|index|offset)|Call to (undefined|a member function)|TypeError:|SQLSTATE\[)/.exec(text);
  // 第三方元件的內部標記不列入：summernote 編輯器、kartik popover-x（自有外掛與樣式，非 Bootstrap 4 殘留）
  const own = el => !el.closest('.note-editor, .note-modal, .note-popover, .popover-x')
    && el.getAttribute('data-toggle') !== 'popover-x' && el.getAttribute('data-dismiss') !== 'popover-x';
  const legacy = [];
  for (const s of $legacy) {
    const n = [...document.querySelectorAll(s)].filter(own).length;
    if (n) legacy.push(s + '×' + n);
  }
  const badges = [...document.querySelectorAll('.badge')].filter(vis).filter(e => {
    const st = getComputedStyle(e); const bg = st.backgroundColor;
    return (bg === 'rgba(0, 0, 0, 0)' || bg === 'transparent') && st.color === 'rgb(255, 255, 255)';
  }).map(e => e.innerText.trim().slice(0, 20));
  const imgs = [...document.querySelectorAll('img')].filter(i => i.getAttribute('src') && i.complete && i.naturalWidth === 0).map(i => decodeURIComponent(i.getAttribute('src')).slice(0, 90));
  return {phpErr: m && m[0], legacy, badges, imgs, title: document.title, url: location.href};
})()
JS;
    }
}
