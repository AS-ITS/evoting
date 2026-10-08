<?php
/**
 * Main tests bootstrap file
 * Loaded before suite-specific bootstrap files
 */

define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);

// 設置錯誤處理
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

$basePath = dirname(__DIR__);
$venderDir = $basePath . '/vendor';

// 設置模擬的 web 環境變數（讓 Config 類別能夠初始化）
$_SERVER['SCRIPT_FILENAME'] = $basePath . '/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/web/index.php';
$_SERVER['PHP_SELF'] = '/web/index.php';

// 載入環境變數（.env）
require $basePath . '/config/env-loader.php';

// 載入 Composer autoloader 和 Yii（Yii2 模組會建立應用程式）
require $venderDir . '/autoload.php';
require_once $venderDir . '/yiisoft/yii2/Yii.php';

Yii::setAlias('@app', $basePath);
Yii::setAlias('@tests', __DIR__);

// unit test
//(new yii\console\Application(require $basePath . '/config/test.php'));
// acceptance test
// (new yii\web\Application(require $basePath . '/config/web.php'));