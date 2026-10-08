<?php
/**
 * Acceptance tests bootstrap file
 * This is loaded after the main _bootstrap.php
 * Do not load autoloader here as it's already loaded by Yii2 module
 */

// 確保 Composer autoloader 和 Yii 已載入
$basePath = dirname(__DIR__, 2);
if (!class_exists('Composer\Autoload\ClassLoader', false)) {
    require $basePath . '/vendor/autoload.php';
}
if (!class_exists('Yii', false)) {
    require $basePath . '/vendor/yiisoft/yii2/Yii.php';
}

// 手動載入 VoteInterfaces 介面（AcceptanceTester 需要此介面）
require_once __DIR__ . '/../interfaces/VoteInterfaces.php';

// Additional acceptance test specific initialization can go here
