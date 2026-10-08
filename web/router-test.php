<?php
/**
 * PHP 內建伺服器路由腳本（測試環境專用）
 *
 * 此腳本用於 Codeception acceptance 測試，將所有請求路由到測試入口點 (index-test.php)。
 *
 * 使用方式：
 * ```bash
 * # 啟動測試伺服器
 * php -S localhost:8080 -t web web/router-test.php
 *
 * # 載入測試資料 (另開終端機)
 * echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig" --appconfig=config/console-test.php
 *
 * # 執行 acceptance 測試
 * php vendor/bin/codecept run acceptance
 * ```
 *
 * 功能：
 * - 將所有 PHP 請求導向 index-test.php（使用測試配置和測試資料庫）
 * - 靜態檔案（CSS、JS、圖片等）直接回傳
 * - 處理 /index.php/... 格式的 URL 路由，自動轉換為 /index-test.php/...
 *
 * 注意事項：
 * - 測試伺服器連接到 voting_test 資料庫（由 .env 中的 TEST_DB_* 定義）
 * - 測試失敗的 HTML 輸出會儲存在 tests/_output/ 目錄
 *
 * @since 2026-01-16
 */

require __DIR__ . '/../config/test-entry-guard.php';

// 取得請求的 URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 如果請求的是真實存在的靜態檔案，讓內建伺服器直接處理
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    $extension = pathinfo($uri, PATHINFO_EXTENSION);
    // 對於 PHP 檔案以外的靜態資源，讓伺服器直接處理
    if ($extension !== 'php') {
        return false;
    }
}

// 處理 /index.php/... 或 /index-test.php/... 格式的 URL
// 將其轉換為 Yii2 可以理解的格式
if (preg_match('#^/index(-test)?\.php(/.*)?$#', $uri, $matches)) {
    $pathInfo = $matches[2] ?? '';
    // 重寫 REQUEST_URI 為 /index-test.php/... 格式
    $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    $_SERVER['REQUEST_URI'] = '/index-test.php' . $pathInfo . ($query ? '?' . $query : '');
}

// 設定腳本名稱和路徑
$_SERVER['SCRIPT_NAME'] = '/index-test.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index-test.php';

require __DIR__ . '/index-test.php';
