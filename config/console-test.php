<?php
/**
 * Console 測試環境配置檔（改善版）
 *
 * 此配置用於 Console 命令測試環境（如 fixture 載入）
 * 使用 ConfigManager 統一管理所有配置
 *
 * @since 2026-01-14
 * @version 2.0
 */

/** @var string $basePath 當前資料夾路徑 */

use app\config\ConfigManager;
use app\config\Config;

$basePath = dirname(__DIR__);

// ============================================
// 初始化 ConfigManager（統一配置管理）
// ============================================
$cm = ConfigManager::getInstance();

// 測試環境使用寬鬆驗證
$cm->ensureValidated(['strict' => false]);

// 取得 Config 實例
$conf = $cm->getConfigInstance();

// 取得機敏參數
$sens = $cm->getSecureParams();

// ============================================
// Yii2 Console 測試應用程式配置
// ============================================
return [
    'id' => $cm->get('APP_ID', 'voting') . '-console-test',
    'name' => $cm->get('APP_NAME', '投票系統') . ' (Console測試)',
    'basePath' => $basePath,
    'language' => 'zh-TW',
    'timeZone' => 'Asia/Taipei',
    'controllerNamespace' => 'app\commands',

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
        // 資料庫連線：測試環境使用測試資料庫（從 .env 讀取 TEST_DB_* 配置）
        'db' => array_merge($cm->getTestDatabaseConfig(), [
            'commandClass' => \app\tests\_support\TestDbCommand::class,
        ]),

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
            'traceLevel' => 3,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/console-test.log',
                    'logVars' => [],
                ],
            ],
        ],

        // RBAC 權限管理
        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
            'cache' => 'cache'
        ],

        // 快取：測試環境使用獨立的快取目錄，避免影響正式環境的 RBAC 快取
        'cache' => [
            'class' => \yii\caching\FileCache::class,
            'cachePath' => '@runtime/cache-test',
        ],
    ],

    // ============================================
    // 應用程式參數
    // ============================================
    'params' => require __DIR__ . '/params.php',
];
