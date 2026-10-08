<?php
/**
 * Console 應用程式配置檔（改善版）
 *
 * 使用 ConfigManager 統一管理所有配置
 *
 * @since 2026-01-14
 * @version 2.0
 */

/** @var string $basePath 當前資料夾路徑 */
/** @var string $venderDir 引入套件之路徑 */

use app\config\ConfigManager;
use app\config\Config;

$basePath = dirname(__DIR__);

// ============================================
// 初始化 ConfigManager（統一配置管理）
// ============================================
$cm = ConfigManager::getInstance();

// Console 環境不進行自動驗證，由各命令自行決定
// 這允許 config 命令在配置不完整時仍可執行
// $cm->ensureValidated(['strict' => false]);

// 取得 Config 實例（向後相容）
$conf = $cm->getConfigInstance();

// 取得機敏參數
$sens = $cm->getSecureParams();

// ============================================
// Yii2 Console 應用程式配置
// ============================================
return [
    'id' => $cm->get('APP_ID', 'voting'),
    'name' => $cm->get('APP_NAME', '投票系統'),
    'basePath' => $basePath,
    'language' => 'zh-TW',
    'timeZone' => 'Asia/Taipei',
    'controllerNamespace' => 'app\commands',

    'bootstrap' => [
        'log',
        \app\components\ProductionConsoleGuard::class,
    ],

    // ============================================
    // 路徑別名
    // ============================================
    'aliases' => [
        '@bower'    => "@vendor/bower-asset",
        '@npm'      => "@vendor/npm-asset",
        '@frontend' => "@app/frontend",
        '@sens'     => $conf->path['dore'],
        '@filePool' => $conf->path['filePool'],
        '@tests'    => "@app/tests",
    ],

    // ============================================
    // Controller 映射
    // ============================================
    'controllerMap' => [
        'fixture' => [
            'class' => \yii\faker\FixtureController::class,
            'fixtureDataPath' => '@tests/fixtures/data',
            'namespace' => 'app\tests\fixtures'
        ],
    ],

    // ============================================
    // 應用程式組件
    // ============================================
    'components' => [
        // 資料庫連線（使用 ConfigManager）
        'db' => $cm->getDatabaseConfig(),

        // 資產管理器
        'assetManager' => [
            'linkAssets' => true,
            'appendTimestamp' => true,
        ],

        // URL 管理器
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => true,
        ],

        // Request 組件（Console 環境）
        'request' => [
            'class' => \yii\console\Request::class,
        ],

        // 日誌
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/console.log',
                    'logVars' => [],
                ],
            ],
        ],

        // RBAC 權限管理
        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
            'cache' => 'cache'
        ],

        // 快取
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
    ],

    // ============================================
    // 應用程式參數
    // ============================================
    'params' => require __DIR__ . '/params.php',
];
