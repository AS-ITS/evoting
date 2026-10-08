<?php
/**
 * Web 測試環境配置檔（改善版）
 *
 * 此配置用於 Codeception 測試環境
 * 使用 ConfigManager 統一管理所有配置
 *
 * @author Configuration Improvement Team
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
// Yii2 Web 測試應用程式配置
// ============================================
$config = [
    'id' => $cm->get('APP_ID', 'voting') . '-test',
    'name' => $cm->get('APP_NAME', '投票系統') . ' (測試)',
    'language' => 'zh-TW',
    'timeZone' => 'Asia/Taipei',
    'homeUrl' => ['vote/index'],
    'defaultRoute' => 'vote/index',
    'basePath' => $basePath,

    'bootstrap' => [
        'lang', 'log',
        function($app) {
            unset($GLOBALS['config']);
            unset($GLOBALS['__composer_autoload_files']);
        },
    ],

    'params' => require __DIR__ . '/params.php',

    'aliases' => [
        '@basePath' => $basePath,
        '@views'    => "@app/views",
        '@bower'    => "@vendor/bower-asset",
        '@npm'      => "@vendor/npm-asset",
        '@frontend' => "@app/frontend",
        '@sens'     => $conf->path['dore'],
        '@filePool' => $conf->path['filePool'],
        '@tests'    => "@app/tests",
    ],

    'container' => [
        'definitions' => [
            'yii\widgets\LinkPager' => \yii\bootstrap5\LinkPager::class,
            'yii\bootstrap5\Button' => \app\components\bootstrap5\Button::class,
        ],
    ],

    'modules' => [
        'gridview' => [
            'class' => \kartik\grid\Module::class,
        ]
    ],

    'components' => [
        'assetManager' => [
            'linkAssets' => true,
            'appendTimestamp' => true,
        ],

        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
            'cache' => 'cache'
        ],

        'cache' => [
            'class' => \yii\caching\FileCache::class,
            // 測試環境使用獨立的快取目錄，避免影響正式環境的 RBAC 快取
            'cachePath' => '@runtime/cache-test',
        ],

        'cookies' => [
            'class'    => \yii\web\Cookie::class,
            'httpOnly' => true,
            'secure'   => false,  // 測試環境不需要 HTTPS
            'sameSite' => \yii\web\Cookie::SAME_SITE_STRICT,
        ],

        // 資料庫連線：測試環境使用測試資料庫（從 .env 讀取 TEST_DB_* 配置）
        'db' => array_merge($cm->getTestDatabaseConfig(), [
            'commandClass' => \app\tests\_support\TestDbCommand::class,
            'on afterOpen' => function($event) {
                $event->sender->createCommand("SET sql_mode = ''")->execute();
            }
        ]),

        'errorHandler' => [
            'errorAction' => '/site/error',
        ],

        'request' => [
            'class' => \yii\web\Request::class,
            'cookieValidationKey' => $cm->mustGet('cookieValidationKey'),
            'enableCsrfValidation' => false,  // 測試環境關閉 CSRF（驗收測試用 PhpBrowser 不依賴手動帶 token）
            'csrfParam' => $conf->getFlagName('FrontendCSRF', true),
        ],

        'view' => [
            'theme' => [
                'pathMap' => [
                    '@app/useTheme' => sprintf(
                        '%s/views/layouts/%s/', '@app', 'offcanvas'
                    ),
                    '@app/useTemplate' => sprintf(
                        '%s/views/layouts/%s/', '@app', 'basic'
                    ),
                ],
            ],
        ],

        'i18n' => [
            'translations' => [
                'app' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => \app\models\Language::LANG_TW,
                ],
                'vote' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => \app\models\Language::LANG_TW,
                ],
                'switch' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => \app\models\Language::LANG_TW,
                ],
            ],
        ],

        'log' => [
            'traceLevel' => 3,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/test.log',
                    'logVars' => [],
                ],
            ],
        ],

        'user' => [
            'class' => \yii\web\User::class,
            'identityClass' => \app\components\AdminIdentity::class,
            'loginUrl' => ['/auth/login'],
            'enableAutoLogin' => false,
            'authTimeout' => 7200,
            'idParam' => $conf->getFlagName('UserId', true),
            'authKeyParam' => $conf->getFlagName('UserAuthKey', true),
            'returnUrlParam' => $conf->getFlagName('UserReturnUrl', true),
            'authTimeoutParam' => $conf->getFlagName('UserExpire', true),
            'identityCookie' => [
                'name' => $conf->getFlagName('userIdentity'),
                'httpOnly' => true,
                'secure' => false,
            ],
            'on afterLogin' => function ($event) {
                try {
                    (new \app\components\AdminIdentity)->afterLogin($event->identity);
                } catch (\Exception $e) {
                    Yii::error("Error in afterLogin event handler: " . $e->getMessage());
                    if (!Yii::$app->user->isGuest) {
                        Yii::$app->user->logout(true);
                    }
                    Yii::$app->session->addFlash('error', '認證過程發生錯誤，請稍後再試');
                }
            },
        ],

        'anon' => [
            'class' => \yii\web\User::class,
            'identityClass' => \app\components\Anon::class,
            'loginUrl' => ['/site/password'],
            'enableAutoLogin' => false,
            'authTimeout' => 1800,
            'idParam' => $conf->getFlagName('AnonId', true),
            'authKeyParam' => $conf->getFlagName('AnonAuthKey', true),
            'returnUrlParam' => $conf->getFlagName('AnonReturnUrl', true),
            'authTimeoutParam' => $conf->getFlagName('AnonExpire', true),
            'identityCookie' => [
                'name' => $conf->getFlagName('anonIdentity'),
                'httpOnly' => true,
                'secure' => false,
            ],
            'on afterLogin' => function ($event) {
                (new \app\components\Anon)->afterLogin($event->identity);
            },
            'on afterLogout' => function ($event) {
                (new \app\components\Anon)->afterLogout($event->identity);
            }
        ],

        'session' => [
            'class' => \app\components\RecoverableSession::class,
            'name' => $conf->getFlagName('sessionId') . '_test',
            'timeout' => 3600 * 6,
            'savePath' => dirname(__DIR__) . '/runtime/sessions/test',
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => true,
            'rules' => [
                'admin' => 'auth/login',
                '<shortUrl:\w+>' => 'vote/short-url',
            ],
        ],

        'userAgent' => [
            'class' => \app\components\helper\UserAgent::class,
            'returnUrlExc' => [
                '/auth/login',
                '/site/login',
                '/site/error',
                '/site/logout',
            ],
        ],

        'lang' => [
            'class' => \app\models\Language::class,
        ],

        'swUserId' => [
            'class' => \app\models\SwitchUserIdentity::class,
        ],

        'tablePag' => [
            'class' => \app\models\TablePagination::class,
            'pageSizeDef' => 15,
        ],
    ],
];

// 測試環境清理
unset($cm, $conf, $sens, $basePath);

return $config;
