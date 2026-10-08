<?php
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);

$basePath = dirname(dirname(__DIR__));

/**
 * @var string $venderDir 引入套件之路徑
 */
$venderDir = $basePath . '/vendor';

require $venderDir . '/autoload.php'; // 加載套件
require $venderDir . '/yiisoft/yii2/Yii.php'; // 加載Yii

// 載入環境變數（.env）
require $basePath . '/config/env-loader.php';

// 手動載入 VoteInterfaces 介面（UnitTester 需要此介面）
require_once __DIR__ . '/../interfaces/VoteInterfaces.php';

Yii::setAlias('@app', $basePath); // 定義共用項目

// unit test
// (new yii\console\Application(require $basePath . '/config/test.php'));
// acceptance test
// (new yii\web\Application(require $basePath . '/config/web.php'));