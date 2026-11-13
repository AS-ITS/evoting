<?php
/**
 * 瀏覽器 E2E 測試入口
 *
 * 用法：php tests/e2e/run.php [--base=URL] [--cdp=URL] [--chrome=PATH] [--suite=a,b]
 *
 * --base    受測網站（預設 E2E_BASE_URL 或 http://localhost:8080），須為測試入口並已載入 fixture
 * --cdp     既有 Chrome 遠端除錯位址（預設 E2E_CDP_URL 或 http://127.0.0.1:9222）；無法連線時自動啟動 Chrome
 * --chrome  Chrome／Edge 執行檔（預設 E2E_CHROME 或自動偵測）
 * --suite   只跑指定組別：pages, interactions, header, modals, groups, attachments, photos, results, result-fields, voting, anon, layout
 *
 * 結束代碼：全部通過為 0，否則為 1。截圖與 results.json 輸出至 tests/_output/e2e/
 */
error_reporting(E_ALL);
require __DIR__.'/lib/Browser.php';
require __DIR__.'/lib/PageCheck.php';

$opt = getopt('', ['base:', 'cdp:', 'chrome:', 'suite:']);
$base = $opt['base'] ?? getenv('E2E_BASE_URL') ?: 'http://localhost:8080';
$cdpUrl = rtrim($opt['cdp'] ?? getenv('E2E_CDP_URL') ?: 'http://127.0.0.1:9222', '/');
$allSuites = ['pages', 'interactions', 'header', 'modals', 'groups', 'attachments', 'photos', 'results', 'result-fields', 'voting', 'anon', 'layout'];
$suites = isset($opt['suite']) ? array_intersect(explode(',', $opt['suite']), $allSuites) : $allSuites;
$outDir = dirname(__DIR__).'/_output/e2e';
@mkdir($outDir.'/files', 0777, true);
array_map('unlink', glob($outDir.'/shots/*.png') ?: []);

// ---- 受測網站 ----
$probe = @file_get_contents($base.'/auth/login', false, stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]));
if ($probe === false) {
    fwrite(STDERR, "無法連線受測網站 $base\n請先啟動測試伺服器（見 tests/e2e/README.md）\n");
    exit(2);
}

// ---- Chrome ----
$chromeProc = null;
$profileDir = null;
if (!Cdp::http($cdpUrl.'/json/version')) {
    $chrome = $opt['chrome'] ?? getenv('E2E_CHROME') ?: null;
    if (!$chrome) {
        foreach ([
            'C:\Program Files\Google\Chrome\Application\chrome.exe',
            'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
            'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
            '/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser',
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        ] as $p) {
            if (is_file($p)) {
                $chrome = $p;
                break;
            }
        }
    }
    if (!$chrome) {
        fwrite(STDERR, "找不到 Chrome，請以 --chrome 或 E2E_CHROME 指定\n");
        exit(2);
    }
    $port = parse_url($cdpUrl, PHP_URL_PORT) ?: 9222;
    $profileDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'evoting-e2e-'.getmypid();
    $chromeProc = proc_open([$chrome, '--headless=new', "--remote-debugging-port=$port", "--user-data-dir=$profileDir",
        '--no-first-run', '--no-default-browser-check', '--disable-gpu', '--window-size=1400,900', 'about:blank'],
        [['pipe', 'r'], ['file', $outDir.'/chrome.log', 'w'], ['file', $outDir.'/chrome.log', 'a']], $pipes);
    for ($i = 0; $i < 50 && !Cdp::http($cdpUrl.'/json/version', 'GET', 1); $i++) {
        usleep(200000);
    }
}

$file = function ($name, $content) use ($outDir) {
    $p = $outDir.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$name;
    file_put_contents($p, $content);
    return realpath($p);
};

$exitCode = 1;
try {
    echo "受測網站：$base\n";
    $admin = new Browser($cdpUrl, $base, $outDir);
    $guest = new Browser($cdpUrl, $base, $outDir, true);
    /** @var Browser[] 所有瀏覽器（suite 另建的也加入此陣列以收集結果） */
    $browsers = [$admin, $guest];
    $admin->login('test_admin');

    foreach ($suites as $suite) {
        try {
            require __DIR__."/suites/$suite.php";
        } catch (Throwable $e) {
            $admin->check("[$suite] 執行中斷", false, $e->getMessage());
            $admin->shot("$suite-aborted");
        }
    }

    $results = array_merge(...array_map(function ($b) { return $b->results; }, $browsers));
    $failed = array_values(array_filter($results, function ($r) { return !$r['ok']; }));
    printf("\n== 結果：%d 項，通過 %d，失敗 %d\n", count($results), count($results) - count($failed), count($failed));
    foreach ($failed as $r) {
        echo "  ✖ [{$r['group']}] {$r['name']}\n      {$r['detail']}\n";
    }
    file_put_contents($outDir.'/results.json', json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // CSP 違規彙整（僅紀錄，不影響結果）；伺服器以 CSP_POLICY=strict 啟動時才能看出 inline／eval 的依賴
    $csp = array_merge(...array_map(function ($b) {
        try {
            return $b->cspViolations();
        } catch (Throwable $e) {
            return [];
        }
    }, $browsers));
    file_put_contents($outDir.'/csp-violations.json', json_encode($csp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    $byDirective = [];
    foreach ($csp as $v) {
        $d = $v['directive'] ?? '?';
        $byDirective[$d]['count'] = ($byDirective[$d]['count'] ?? 0) + 1;
        $byDirective[$d]['pages'][$v['page'] ?? '?'] = true;
        $src = ($v['blocked'] ?? '') === 'inline' || ($v['blocked'] ?? '') === 'eval'
            ? $v['blocked'].' @ '.basename(parse_url($v['source'] ?? '', PHP_URL_PATH) ?: '(page)')
            : ($v['blocked'] ?? '');
        $byDirective[$d]['sources'][$src] = ($byDirective[$d]['sources'][$src] ?? 0) + 1;
    }
    printf("\n== CSP 違規（僅紀錄）：%d 筆不重複\n", count($csp));
    ksort($byDirective);
    foreach ($byDirective as $d => $info) {
        arsort($info['sources']);
        printf("  %s：%d 筆，%d 個頁面\n", $d, $info['count'], count($info['pages']));
        foreach (array_slice($info['sources'], 0, 5, true) as $src => $n) {
            echo "      $n × $src\n";
        }
    }
    echo "明細：$outDir/csp-violations.json\n";
    echo "截圖：$outDir/shots\n";
    $exitCode = $failed ? 1 : 0;
} finally {
    if ($chromeProc) {
        proc_terminate($chromeProc);
        proc_close($chromeProc);
    }
}
exit($exitCode);
