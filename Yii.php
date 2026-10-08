<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

use yii\BaseYii;
use yii\di\Container;

use yii\web\IdentityInterface;
use yii\web\User as BaseUser;
use yii\web\Application as BaseApplication;

use app\models\Language;
use app\models\TablePagination;
use app\models\SwitchUserIdentity;

/**
 * Yii bootstrap file.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Gets the application start timestamp.
 */
defined('YII_BEGIN_TIME') or define('YII_BEGIN_TIME', microtime(true));
/**
 * This constant defines the framework installation directory.
 */
defined('YII2_PATH') or define('YII2_PATH', __DIR__ . '/vendor/yiisoft/yii2');
/**
 * This constant defines whether the application should be in debug mode or not. Defaults to false.
 */
defined('YII_DEBUG') or define('YII_DEBUG', false);
/**
 * This constant defines in which environment the application is running. Defaults to 'prod', meaning production environment.
 * You may define this constant in the bootstrap script. The value could be 'prod' (production), 'dev' (development), 'test', 'staging', etc.
 */
defined('YII_ENV') or define('YII_ENV', 'prod');
/**
 * Whether the the application is running in production environment.
 */
defined('YII_ENV_PROD') or define('YII_ENV_PROD', YII_ENV === 'prod');
/**
 * Whether the the application is running in development environment.
 */
defined('YII_ENV_DEV') or define('YII_ENV_DEV', YII_ENV === 'dev');
/**
 * Whether the the application is running in testing environment.
 */
defined('YII_ENV_TEST') or define('YII_ENV_TEST', YII_ENV === 'test');

/**
 * This constant defines whether error handling should be enabled. Defaults to true.
 */
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', true);

/**
 * [cheat] Yii 是一個服務於通用框架功能的輔助類。
 *
 * It extends from [[\yii\BaseYii]] which provides the actual implementation.
 * 它從提供實際實現的 [[\yii\BaseYii]] 擴展而來。
 * By writing your own Yii class, you can customize some functionalities of [[\yii\BaseYii]].
 * 通過編寫自己的 Yii 類，您可以自定義 [[\yii\BaseYii]] 的一些功能。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Yii extends BaseYii
{
    /**
     * @var Application the application instance
     */
    public static $app;

    /**
     * @var Container the dependency injection (DI) container used by [[createObject()]].
     * You may use [[Container::set()]] to set up the needed dependencies of classes and
     * their initial property values.
     * @see createObject()
     * @see Container
     */
    public static $container;
}

/**
 * [cheat] Yii 是一個服務於通用框架功能的輔助類。
 * 
 * @property-read User $user [cheat]The user component. This property is read-only.
 * @property-read User $anon [cheat]The user component. This property is read-only.
 * @property-read TablePagination $tablePag 每頁筆數更換小工具
 * @property-read Language $lang 中英翻譯處理
 * @property-read SwitchUserIdentity $swUserId 切換使用者身份
 */
class Application extends BaseApplication
{
    // ...
}

/**
 * [cheat] Yii 是一個服務於通用框架功能的輔助類。
 * 
 * @property IdentityInterface|\app\models\UserIdentity|null $identity [cheat]The identity object associated with the currently logged-in
 */
class User extends BaseUser
{
    // ...
}

spl_autoload_register(['Yii', 'Application', 'autoload'], true, true);

Yii::$classMap = require __DIR__ . '/vendor/yiisoft/yii2/classes.php';
Yii::$container = new Container;
