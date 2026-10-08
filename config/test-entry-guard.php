<?php
/**
 * 阻擋正式環境透過 HTTP 存取測試入口（index-test.php / router-test.php）
 */
require __DIR__ . '/env-loader.php';

$env = strtolower(getenv('APP_ENV') ?: '');
if (in_array($env, ['production', 'prod', 'product'], true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Not Found';
    exit;
}
