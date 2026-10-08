<?php
/**
 * Functional tests bootstrap file.
 * Ensure Yii is loaded without causing "class already declared" errors.
 * Mock Env for Console Request compatibility.
 */

$basePath = dirname(dirname(__DIR__));
$vendorDir = $basePath . '/vendor';

// Mock argv for Console Request used in EnvPathConfigTrait::determinePartitionByPath
// This allows new \yii\console\Request to succeed in calling getScriptFile()
if (!isset($_SERVER['argv'])) {
    $_SERVER['argv'] = [];
}
if (empty($_SERVER['argv'])) {
    $_SERVER['argv'][0] = __FILE__;
}

// Redefine web server vars just in case
$_SERVER['SCRIPT_FILENAME'] = $basePath . '/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/web/index.php';

if (!class_exists('Yii')) {
    if (file_exists($vendorDir . '/autoload.php')) {
        require_once $vendorDir . '/autoload.php';
    }
    if (file_exists($vendorDir . '/yiisoft/yii2/Yii.php')) {
        require_once $vendorDir . '/yiisoft/yii2/Yii.php';
    }
}

Yii::setAlias('@app', $basePath);
Yii::setAlias('@tests', $basePath . '/tests');
