<?php
/**
 * 環境變數載入器
 *
 * 此腳本會載入 .env 文件中的環境變數到 $_ENV 和 putenv()
 * 使用方式：在 web/index.php 或 yii 腳本開頭 include 此文件
 */

// .env 文件路徑
$envFile = dirname(__DIR__) . '/.env';

// 如果 .env 文件存在，載入環境變數
if (!file_exists($envFile)) {
    // .env 檔案不存在 - 記錄錯誤但不中斷執行
    if (defined('YII_DEBUG') && YII_DEBUG) {
        error_log("Warning: .env file not found at: $envFile");
    }
} else if (!is_readable($envFile)) {
    // .env 檔案無法讀取
    if (defined('YII_DEBUG') && YII_DEBUG) {
        error_log("Warning: .env file is not readable: $envFile");
    }
} else {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // 跳過註解行
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // 解析環境變數 (KEY=VALUE 格式)
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // 移除值前後的引號
            $value = trim($value, '"\'');

            // 設定環境變數（不覆蓋已存在的環境變數）
            if (!getenv($name)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
