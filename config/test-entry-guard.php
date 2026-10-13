<?php
/**
 * 阻擋正式環境透過 HTTP 存取測試入口（index-test.php / router-test.php）
 */
require __DIR__ . '/env-loader.php';

$isAllowed = require __DIR__ . '/test-entry-policy.php';
$env = getenv('APP_ENV');

if (!$isAllowed($env === false ? null : $env)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Not Found';
    exit;
}
