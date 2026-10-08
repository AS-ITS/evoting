<?php
/**
 * Web 應用程式配置檔
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

// ============================================
// 初始化 ConfigManager（統一配置管理）
// ============================================
$cm = ConfigManager::getInstance();

// 驗證配置（正式環境嚴格，開發環境寬鬆）
$cm->ensureValidated(['strict' => $cm->isProduction()]);

// 取得 Config 實例（向後相容）
$conf = $cm->getConfigInstance();

// 取得機敏參數
$sens = $cm->getSecureParams();

// ============================================
// Yii2 應用程式配置
// ============================================
/** @var array $config Yii2 Config */
$config = [
    'id' => $cm->get('APP_ID', 'voting'),
    'name' => $cm->get('APP_NAME', '投票系統'),
    'language' => $cm->get('APP_LANGUAGE', 'zh-TW'),
    'timeZone' => 'Asia/Taipei',
    'homeUrl' => ['vote/index'],
    'defaultRoute' => 'vote/index',
    'basePath' => $basePath,

    // ============================================
    // Bootstrap 階段載入組件
    // ============================================
    'bootstrap' => [
        'lang', 'log',
        \app\components\SecurityHeadersBootstrap::class,
        function($app) {
            // 基於安全性刪除全域變數
            unset($GLOBALS['config']);
            unset($GLOBALS['__composer_autoload_files']);
        },
    ],

    // ============================================
    // 請求前置處理
    // ============================================
    'on beforeRequest' => function ($event) {
        // Referrer 檢查（可選）
        // 如需啟用，請將 $regex 改為允許的域名並取消註解以下行：
        // $referrer = parse_url(Yii::$app->request->referrer, PHP_URL_HOST);
        // $regex = '/\b(www\.)?[a-z0-9\-]+\.example\.com\b/i';
        // if (!preg_match($regex, $referrer) && !empty($referrer)) {
        //     \app\components\Controller::NotAllowedAccess();
        // }
    },

    // ============================================
    // Host Control Filter（防止 Host Header 攻擊）
    // ============================================
    'as hostControl' => [
        'class' => \yii\filters\HostControl::class,
        'allowedHosts' => array_filter(array_merge(
            // 從環境變數讀取允許的域名（逗號分隔）
            !empty($cm->get('ALLOWED_HOSTS')) ?
                explode(',', $cm->get('ALLOWED_HOSTS')) : []
        )),
    ],

    // ============================================
    // 應用程式參數
    // ============================================
    'params' => require __DIR__ . '/params.php',

    // ============================================
    // 路徑別名
    // ============================================
    'aliases' => [
        '@basePath' => $basePath,
        '@views'    => "@app/views",
        '@bower'    => "@vendor/bower-asset",
        '@npm'      => "@vendor/npm-asset",
        '@frontend' => "@app/frontend",
        '@sens'     => $conf->path['dore'],
        '@filePool' => $conf->path['filePool'],
    ],

    // ============================================
    // 依賴注入容器
    // ============================================
    'container' => [
        'definitions' => [
            'yii\widgets\LinkPager' => \yii\bootstrap5\LinkPager::class,
            'yii\bootstrap5\Button' => \app\components\bootstrap5\Button::class,
        ],
    ],

    // ============================================
    // 模組
    // ============================================
    'modules' => [
        'gridview' => [
            'class' => \kartik\grid\Module::class,
        ]
    ],

    // ============================================
    // 應用程式組件
    // ============================================
    'components' => [
        // --------------------------------------------
        // 資產管理器
        // --------------------------------------------
        'assetManager' => [
            'linkAssets' => true,
            'appendTimestamp' => true,
        ],

        // --------------------------------------------
        // RBAC 權限管理
        // --------------------------------------------
        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
            'cache' => 'cache'
        ],

        // --------------------------------------------
        // 快取
        // --------------------------------------------
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],

        // --------------------------------------------
        // Cookie 配置
        // --------------------------------------------
        'cookies' => [
            'class'    => \yii\web\Cookie::class,
            'httpOnly' => true,
            'secure'   => $cm->isProduction(),
            'sameSite' => \yii\web\Cookie::SAME_SITE_STRICT,
        ],

        // --------------------------------------------
        // 資料庫連線（使用 ConfigManager）
        // --------------------------------------------
        'db' => $cm->getDatabaseConfig(),

        // --------------------------------------------
        // 錯誤處理
        // --------------------------------------------
        'errorHandler' => [
            'errorAction' => '/site/error',
        ],

        // --------------------------------------------
        // HTTP 請求組件
        // --------------------------------------------
        'request' => [
            'class' => \yii\web\Request::class,
            'cookieValidationKey' => $cm->mustGet('cookieValidationKey'),
            'enableCsrfValidation' => true,
            'csrfParam' => $conf->getFlagName('FrontendCSRF', true),
            'csrfCookie' => [
                'httpOnly' => true,
                'secure' => $cm->isProduction(),
                'sameSite' => \yii\web\Cookie::SAME_SITE_STRICT,
            ],
            // 反向代理設定（TRUSTED_PROXY_CIDRS 擴充，見 TrustedProxyConfig）
            'trustedHosts' => \app\components\TrustedProxyConfig::getTrustedHosts(),
            'secureHeaders' => [
                'X-Real-IP',
                'HTTP_X_REAL_IP',
                'HTTP_X_FORWARDED_FOR',
            ],
            'ipHeaders' => [
                'X-Real-IP',
                'HTTP_X_REAL_IP',
                'HTTP_X_FORWARDED_FOR',
            ]
        ],

        // --------------------------------------------
        // 視圖組件
        // --------------------------------------------
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

        // --------------------------------------------
        // 國際化（i18n）
        // --------------------------------------------
        'i18n' => [
            'translations' => [
                'app' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => \app\models\Language::LANG_TW,
                ],
                'singular' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => \app\models\Language::LANG_TW,
                ],
                'plural' => [
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

        // --------------------------------------------
        // 日誌
        // --------------------------------------------
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/app.log',
                    'logVars' => [],
                ],
            ],
        ],

        // --------------------------------------------
        // 管理員認證（user component）
        // --------------------------------------------
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
                'secure' => $cm->isProduction(),
                'sameSite' => \yii\web\Cookie::SAME_SITE_STRICT,
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

        // --------------------------------------------
        // 匿名認證（anon component）
        // --------------------------------------------
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
                'secure' => $cm->isProduction(),
                'sameSite' => \yii\web\Cookie::SAME_SITE_STRICT,
            ],
            'on afterLogin' => function ($event) {
                (new \app\components\Anon)->afterLogin($event->identity);
            },
            'on afterLogout' => function ($event) {
                (new \app\components\Anon)->afterLogout($event->identity);
            }
        ],

        // --------------------------------------------
        // Session 管理
        // --------------------------------------------
        'session' => [
            'class' => \app\components\RecoverableSession::class,
            'name' => $conf->getFlagName('sessionId'),
            'timeout' => 3600 * 6,
            'savePath' => dirname(__DIR__) . '/runtime/sessions',
            'cookieParams' => [
                'httpOnly' => true,
                'secure' => $cm->isProduction(),
                'path' => '/',
                'sameSite' => \yii\web\Cookie::SAME_SITE_STRICT,
            ]
        ],

        // --------------------------------------------
        // URL 管理器
        // --------------------------------------------
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => true,
            'rules' => [
                'admin' => 'auth/login',
                '<shortUrl:\w+>' => 'vote/short-url',
            ],
        ],

        // --------------------------------------------
        // 郵件
        // --------------------------------------------
        'mailer' => $cm->getMailerConfig(),

        // --------------------------------------------
        // 自定義組件
        // --------------------------------------------
        'userAgent' => [
            'class' => \app\components\helper\UserAgent::class,
            'returnUrlExc' => [
                '/auth/login',
                '/site/login',
                '/site/error',
                '/site/logout',
                '/site/switch-role',
                '/site/change-language',
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

// ============================================
// 開發環境專用配置（Gii、Debug）
// ============================================
// 允許使用 Gii/Debug 工具的來源 IP
// 可透過環境變數 DEV_ALLOWED_IPS 設定（逗號分隔），預設僅限本機
$allowedIPs = array_filter(array_map('trim', explode(',', getenv('DEV_ALLOWED_IPS') ?: '')));
$allowedIPs = array_merge(['127.0.0.1', '::1'], $allowedIPs);

if (YII_DEBUG && $cm->isDevelopmentEnvironment()) {
    // Gii 代碼生成器
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        'allowedIPs' => $allowedIPs
    ];

    // Debug 工具列
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        'allowedIPs' => $allowedIPs,
        'panels' => [
            'user' => [
                'class' => 'yii\debug\panels\UserPanel',
                'displayName' => 'User 管理員',
                'userComponent' => 'user',
            ],
            'anon' => [
                'class' => 'yii\debug\panels\UserPanel',
                'displayName' => 'Anon 匿名投票',
                'userComponent' => 'anon',
            ],
        ],
    ];
    // ============================================
    // Debug 強制開啟/關閉（透過 HTTP Header，僅限開發環境）
    // ============================================
    if ($conf->hasHeader('x-specific-debug')) {
        if ($conf->getHeader('x-specific-debug') == 'false') {
            unset($config['bootstrap']['debug'], $config['modules']['debug']);
        }
        if ($conf->getHeader('x-specific-debug') == 'true') {
            $config['bootstrap']['debug'] = 'debug';
            $config['modules']['debug'] = [
                'class' => \yii\debug\Module::class,
                'allowedIPs' => $allowedIPs,
            ];
        }
    }
}

// ============================================
// 安全性清理：刪除臨時變數
// ============================================
unset(
    $cm, $conf, $sens,
    $basePath, $venderDir, $allowedIPs
);

return $config;
