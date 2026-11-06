<?php
require_once __DIR__.'/Cdp.php';

/**
 * 以 CDP 操作頁面的輔助方法與檢查結果收集
 */
class Browser
{
    /** @var Cdp */
    public $cdp;
    public $base;
    public $outDir;
    public $results = [];
    /** @var string 目前的測試組別 */
    public $group = '';
    /** @var array<string, array> 收集到的 CSP 違規（以內容去重） */
    public $csp = [];

    /** 頁面腳本以此前綴經 console.debug 回報 CSP 違規 */
    const CSP_PREFIX = '__E2E_CSP__';

    /**
     * @param string $endpoint Chrome 遠端除錯位址
     * @param string $base 受測網站網址
     * @param string $outDir 輸出目錄
     * @param bool $isolated 是否使用獨立（未登入）的瀏覽器環境
     */
    public function __construct($endpoint, $base, $outDir, $isolated = false)
    {
        $this->base = rtrim($base, '/');
        $this->outDir = $outDir;
        @mkdir($outDir.'/shots', 0777, true);
        $this->cdp = Cdp::newPage($endpoint, $isolated);
        $this->cdp->send('Page.enable');
        $this->cdp->send('Runtime.enable');
        $this->cdp->send('Emulation.setDeviceMetricsOverride', ['width' => 1400, 'height' => 900, 'deviceScaleFactor' => 1, 'mobile' => false]);
        // 在頁面腳本執行前註冊 CSP 違規監聽（report-only 也會觸發）
        $this->cdp->send('Page.addScriptToEvaluateOnNewDocument', ['source' => "document.addEventListener('securitypolicyviolation', function (e) {"
            ."console.debug('".self::CSP_PREFIX."' + JSON.stringify({directive: e.effectiveDirective || e.violatedDirective,"
            ." blocked: e.blockedURI, source: e.sourceFile, line: e.lineNumber, page: location.pathname,"
            ." disposition: e.disposition, sample: e.sample}));});"]);
    }

    /** 將已收到的 CSP 違規事件移入 $this->csp（清除事件緩衝前呼叫） */
    private function harvestCsp()
    {
        foreach ($this->cdp->events('Runtime.consoleAPICalled') as $e) {
            $text = $e['args'][0]['value'] ?? '';
            if (!is_string($text) || strpos($text, self::CSP_PREFIX) !== 0) {
                continue;
            }
            $v = json_decode(substr($text, strlen(self::CSP_PREFIX)), true);
            if (!is_array($v)) {
                continue;
            }
            $v['group'] = $this->group;
            $key = implode('|', [$v['directive'] ?? '', $v['blocked'] ?? '', $v['source'] ?? '', $v['line'] ?? '', $v['page'] ?? '']);
            if (!isset($this->csp[$key])) {
                $this->csp[$key] = $v;
            }
        }
    }

    /** 目前為止收集到的 CSP 違規 */
    public function cspViolations()
    {
        $this->js('0'); // 讀入尚在途中的事件
        $this->harvestCsp();
        return array_values($this->csp);
    }

    public function url($path)
    {
        return preg_match('#^https?://#', $path) ? $path : $this->base.$path;
    }

    /**
     * 前往頁面並等待載入完成
     *
     * @return int|null 最終頁面的 HTTP 狀態碼（逾時為 null）
     */
    public function go($path, $referrer = null)
    {
        $this->harvestCsp();
        $this->cdp->clearEvents();
        $params = ['url' => $this->url($path)];
        if ($referrer) {
            $params['referrer'] = $this->url($referrer);
        }
        $this->cdp->send('Page.navigate', $params);
        return $this->afterLoad();
    }

    /** 等待因點擊等動作造成的頁面載入 */
    public function waitLoad()
    {
        return $this->afterLoad();
    }

    private function afterLoad()
    {
        try {
            $this->cdp->waitEvent('Page.loadEventFired', 30);
        } catch (RuntimeException $e) {
            $file = $this->shot('timeout-'.date('His'));
            echo "    ! 等待頁面載入逾時（截圖 {$file}）\n";
            return null;
        }
        usleep(600000);
        return $this->js("performance.getEntriesByType('navigation')[0].responseStatus");
    }

    public function js($expr)
    {
        $r = $this->cdp->send('Runtime.evaluate', ['expression' => $expr, 'returnByValue' => true, 'awaitPromise' => true]);
        if (isset($r['exceptionDetails'])) {
            $desc = $r['exceptionDetails']['exception']['description'] ?? json_encode($r['exceptionDetails'], JSON_UNESCAPED_UNICODE);
            throw new RuntimeException("JS 錯誤：$desc\n  in: ".mb_substr($expr, 0, 200));
        }
        return $r['result']['value'] ?? null;
    }

    /** 頁面載入後發生的未捕捉 JS 例外 */
    public function jsExceptions()
    {
        return array_map(function ($e) {
            $d = $e['exceptionDetails'];
            return ($d['exception']['description'] ?? $d['text']).(isset($d['url']) ? ' @ '.basename(parse_url($d['url'], PHP_URL_PATH)).':'.$d['lineNumber'] : '');
        }, $this->cdp->events('Runtime.exceptionThrown'));
    }

    public function shot($name)
    {
        $r = $this->cdp->send('Page.captureScreenshot', ['format' => 'png'], 60);
        $file = $this->outDir.'/shots/'.preg_replace('/[^\w\-]+/u', '_', $name).'.png';
        file_put_contents($file, base64_decode($r['data']));
        return $file;
    }

    /** 以真實滑鼠事件點擊元素（$selectorJs 為回傳元素的 JS 運算式） */
    public function click($selectorJs)
    {
        $pos = $this->js("(() => { const el = $selectorJs; if (!el) return null; el.scrollIntoView({block:'center'}); const r = el.getBoundingClientRect(); return {x: r.left + r.width/2, y: r.top + r.height/2}; })()");
        if (!$pos) {
            throw new RuntimeException("找不到要點擊的元素：$selectorJs");
        }
        $this->mouse($pos['x'], $pos['y']);
    }

    public function mouse($x, $y)
    {
        $this->harvestCsp();
        $this->cdp->clearEvents();
        foreach (['mousePressed', 'mouseReleased'] as $type) {
            $this->cdp->send('Input.dispatchMouseEvent', ['type' => $type, 'x' => $x, 'y' => $y, 'button' => 'left', 'clickCount' => 1]);
        }
    }

    public function key($key, $code, $vk)
    {
        foreach (['keyDown', 'keyUp'] as $type) {
            $this->cdp->send('Input.dispatchKeyEvent', ['type' => $type, 'key' => $key, 'code' => $code, 'windowsVirtualKeyCode' => $vk]);
        }
    }

    public function setFiles($cssSelector, array $files)
    {
        $doc = $this->cdp->send('DOM.getDocument');
        $node = $this->cdp->send('DOM.querySelector', ['nodeId' => $doc['root']['nodeId'], 'selector' => $cssSelector]);
        if (empty($node['nodeId'])) {
            throw new RuntimeException("找不到檔案欄位：$cssSelector");
        }
        $this->cdp->send('DOM.setFileInputFiles', ['nodeId' => $node['nodeId'], 'files' => array_values($files)]);
    }

    /** 頁面上（對話框以外）可見的提示訊息 */
    public function flashes()
    {
        return $this->js("[...document.querySelectorAll('.alert')].filter(e => e.offsetParent !== null && !e.closest('.modal')).map(e => e.innerText.replace(/\\s+/g,' ').trim())");
    }

    /** 等待 JS 條件成立 */
    public function waitFor($condJs, $timeoutMs = 8000)
    {
        $end = microtime(true) + $timeoutMs / 1000;
        while (microtime(true) < $end) {
            if ($this->js($condJs)) {
                return true;
            }
            usleep(150000);
        }
        return false;
    }

    /** 開啟對話框並等淡入動畫結束 */
    public function openModal($triggerJs, $modalSelector)
    {
        $this->click($triggerJs);
        return $this->waitFor("(() => { const m = document.querySelector('$modalSelector'); return !!m && m.classList.contains('show') && getComputedStyle(m).opacity === '1'; })()")
            && (usleep(300000) || true);
    }

    public function modalShown($modalSelector)
    {
        return (bool) $this->js("(() => { const m = document.querySelector('$modalSelector'); return !!m && m.classList.contains('show'); })()");
    }

    public function sleep($ms)
    {
        usleep($ms * 1000);
    }

    public function check($name, $ok, $detail = '')
    {
        $ok = (bool) $ok;
        $this->results[] = ['group' => $this->group, 'name' => $name, 'ok' => $ok, 'detail' => $detail];
        printf("  %s %s%s\n", $ok ? '✔' : '✖', $name, ($detail !== '' && !$ok) || ($detail !== '' && strlen($detail) < 120) ? "  [$detail]" : '');
        return $ok;
    }

    public function section($title)
    {
        $this->group = $title;
        echo "\n== $title\n";
    }

    /** 以測試專用路由登入（僅測試環境可用，使用 fixture 帳號） */
    public function login($cn)
    {
        return $this->go('/index-test.php/test/login?user='.rawurlencode($cn));
    }
}
