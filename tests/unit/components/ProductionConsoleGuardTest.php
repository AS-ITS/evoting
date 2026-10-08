<?php

namespace app\tests\unit\components;

use app\components\ProductionConsoleGuard;
use app\models\Logs;

class ProductionConsoleGuardTest extends \Codeception\Test\Unit
{
    public function testEvaluateFixtureLoadIsGuarded()
    {
        $evaluation = ProductionConsoleGuard::evaluateCommand('fixture', 'load');

        $this->assertNotNull($evaluation);
        $this->assertSame('fixture/load', $evaluation['route']);
        $this->assertSame('ALLOW_PRODUCTION_FIXTURE', $evaluation['overrideEnv']);
    }

    public function testEvaluateMigrateUpIsGuarded()
    {
        $evaluation = ProductionConsoleGuard::evaluateCommand('migrate', 'up');

        $this->assertNotNull($evaluation);
        $this->assertSame('migrate/up', $evaluation['route']);
        $this->assertSame('ALLOW_PRODUCTION_MIGRATE', $evaluation['overrideEnv']);
    }

    public function testEvaluateUpgradeCryptoIsGuarded()
    {
        $evaluation = ProductionConsoleGuard::evaluateCommand('migrate-passwords', 'upgrade-crypto');

        $this->assertNotNull($evaluation);
        $this->assertSame('migrate-passwords/upgrade-crypto', $evaluation['route']);
        $this->assertSame('ALLOW_PRODUCTION_MIGRATE', $evaluation['overrideEnv']);
    }

    public function testEvaluateIgnoresSafeCommands()
    {
        $this->assertNull(ProductionConsoleGuard::evaluateCommand('encrypt', 'test'));
        $this->assertNull(ProductionConsoleGuard::evaluateCommand('fixture', 'index'));
    }

    public function testAddConsoleCreatesAuditLog()
    {
        $before = (int) Logs::find()->where(['type' => Logs::SYSTEM_CONSOLE_BLOCKED])->count();

        $saved = Logs::addConsole(Logs::SYSTEM_CONSOLE_BLOCKED, [
            'route' => 'fixture/load',
            'outcome' => 'blocked',
            'overrideEnv' => 'ALLOW_PRODUCTION_FIXTURE',
        ]);

        $this->assertTrue($saved);
        $after = (int) Logs::find()->where(['type' => Logs::SYSTEM_CONSOLE_BLOCKED])->count();
        $this->assertSame($before + 1, $after);
    }

    public function testAllowedWhenOverrideFlagSet()
    {
        $override = 'ALLOW_PRODUCTION_FIXTURE';
        $previous = getenv($override);
        putenv("{$override}=1");

        try {
            $evaluation = ProductionConsoleGuard::evaluateCommand('fixture', 'load');
            $this->assertNotNull($evaluation);
            $this->assertTrue($evaluation['allowed']);
        } finally {
            if ($previous === false) {
                putenv($override);
            } else {
                putenv("{$override}={$previous}");
            }
        }
    }
}
