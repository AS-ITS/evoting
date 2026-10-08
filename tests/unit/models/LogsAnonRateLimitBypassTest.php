<?php

namespace app\tests\unit\models;

use app\models\Logs;
use Codeception\Test\Unit;
use ReflectionMethod;
use ReflectionProperty;
use Yii;

class LogsAnonRateLimitBypassTest extends Unit
{
    private array $originalEnv = [];

    protected function _before()
    {
        $this->originalEnv = [
            'APP_ENV' => getenv('APP_ENV'),
            'ANON_LOGIN_RATE_LIMIT_BYPASS_CIDRS' => getenv('ANON_LOGIN_RATE_LIMIT_BYPASS_CIDRS'),
        ];
    }

    protected function _after()
    {
        foreach ($this->originalEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv("$key=$value");
            }
        }
    }

    private function shouldApply(string $userIp): bool
    {
        $_SERVER['REMOTE_ADDR'] = $userIp;
        $prop = new ReflectionProperty(Yii::$app->request, '_ip');
        $prop->setAccessible(true);
        $prop->setValue(Yii::$app->request, null);

        $method = new ReflectionMethod(Logs::class, 'shouldApplyAnonLoginRateLimit');
        $method->setAccessible(true);

        return $method->invoke(null);
    }

    public function testNonProductionDefaultBypassOnlyLocalhost()
    {
        putenv('APP_ENV=test');
        putenv('ANON_LOGIN_RATE_LIMIT_BYPASS_CIDRS');

        $this->assertTrue($this->shouldApply('203.0.113.1'), '203.0.x should not bypass by default');
        $this->assertFalse($this->shouldApply('127.0.0.1'), '127.0 should bypass in non-production');
    }

    public function testExplicitBypassCidr()
    {
        putenv('APP_ENV=test');
        // 比對 client IP 前兩段（見 Logs::shouldApplyAnonLoginRateLimit）
        putenv('ANON_LOGIN_RATE_LIMIT_BYPASS_CIDRS=203.0');

        $this->assertFalse($this->shouldApply('203.0.113.1'));
        $this->assertTrue($this->shouldApply('10.0.0.1'));
    }
}
