<?php
/**
 * @var string $basePath 當前資料夾路徑
 */
$basePath = dirname(__DIR__);

// 載入環境變數（需在定義 YII_DEBUG / YII_ENV 之前）
require $basePath . '/config/env-loader.php';

// 由環境變數控制，預設為正式環境（YII_DEBUG=false、YII_ENV=prod）
// 開發環境請在 .env 設定 YII_DEBUG=true、YII_ENV=dev
defined('YII_DEBUG') or define('YII_DEBUG', filter_var(getenv('YII_DEBUG'), FILTER_VALIDATE_BOOLEAN));
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'prod');

/**
 * @var string $venderDir 引入套件之路徑
 */
$venderDir = $basePath . '/vendor';

require $venderDir . '/autoload.php'; // 加載套件
require $venderDir . '/yiisoft/yii2/Yii.php'; // 加載Yii

Yii::setAlias('@app', $basePath); // 定義共用項目

(new yii\web\Application(require $basePath . '/config/web.php'))->run();