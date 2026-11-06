<?php

namespace app\tests\unit\config;

use app\config\ConfigValidator;
use app\tests\_support\TestLoginController;

/**
 * 正式環境執行模式檢查與測試登入路由隔離（S4／P10）
 */
class ProductionRuntimeModeTest extends \Codeception\Test\Unit
{
    /** @var string|false */
    private $savedAppEnv;

    protected function _before()
    {
        $this->savedAppEnv = getenv('APP_ENV');
    }

    protected function _after()
    {
        if ($this->savedAppEnv === false) {
            putenv('APP_ENV');
        } else {
            putenv('APP_ENV=' . $this->savedAppEnv);
        }
    }

    public function testProductionModeAcceptsProdWithoutDebug()
    {
        $this->assertSame([], ConfigValidator::validateProductionRuntimeMode('prod', false));
    }

    public function testRejectsNonProdYiiEnv()
    {
        foreach (['dev', 'test'] as $yiiEnv) {
            $errors = ConfigValidator::validateProductionRuntimeMode($yiiEnv, false);
            $this->assertCount(1, $errors, $yiiEnv);
            $this->assertStringContainsString('YII_ENV=prod', $errors[0]);
        }
    }

    public function testRejectsDebugMode()
    {
        $errors = ConfigValidator::validateProductionRuntimeMode('prod', true);
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('YII_DEBUG', $errors[0]);
    }

    public function testValidateAppliesRuntimeModeOnlyInProduction()
    {
        // 單元測試以 YII_ENV=test、YII_DEBUG=true 執行，正式環境應被拒絕
        $config = ['db_password' => 'x', 'cookieValidationKey' => str_repeat('a', 32)];

        putenv('APP_ENV=production');
        $errors = implode("\n", ConfigValidator::validate($config, ['strict' => false]));
        $this->assertStringContainsString('YII_ENV=prod', $errors);
        $this->assertStringContainsString('YII_DEBUG', $errors);

        putenv('APP_ENV=development');
        $errors = implode("\n", ConfigValidator::validate($config, ['strict' => false]));
        $this->assertStringNotContainsString('YII_ENV=prod', $errors);
    }

    public function testTestLoginIsNotPartOfProductionCode()
    {
        $root = dirname(__DIR__, 3);
        $this->assertFileDoesNotExist($root . '/controllers/TestController.php');
        $this->assertStringNotContainsString('TestLoginController', file_get_contents($root . '/config/web.php'));
        $this->assertStringContainsString('TestLoginController', file_get_contents($root . '/config/test.php'));
    }

    public function testTestLoginRequiresTestingAppEnv()
    {
        $this->assertTrue(TestLoginController::isAllowed('testing'));
        $this->assertTrue(TestLoginController::isAllowed('test'));
        foreach ([null, '', 'production', 'prod', 'development', 'dev'] as $appEnv) {
            $this->assertFalse(TestLoginController::isAllowed($appEnv), var_export($appEnv, true));
        }
    }
}
