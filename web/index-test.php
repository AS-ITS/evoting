<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../config/test-entry-guard.php';

$basePath = dirname(__DIR__);
$venderDir = $basePath . '/vendor';

require $venderDir . '/autoload.php';
require $venderDir . '/yiisoft/yii2/Yii.php';

// 載入環境變數
require $basePath . '/config/env-loader.php';

Yii::setAlias('@app', $basePath);

$config = require __DIR__ . '/../config/test.php';

(new yii\web\Application($config))->run(); 