<?php

namespace app\tests\unit\components;

use app\components\SaDbTools;
use Codeception\Test\Unit;

class SaDbToolsTest extends Unit
{
    private array $originalEnv = [];

    protected function _before()
    {
        $this->originalEnv = [
            'SA_DB_TOOLS_ENABLED' => getenv('SA_DB_TOOLS_ENABLED'),
            'SA_DB_TOOLS_WRITABLE' => getenv('SA_DB_TOOLS_WRITABLE'),
            'APP_ENV' => getenv('APP_ENV'),
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

    public function testDisabledByDefault()
    {
        putenv('SA_DB_TOOLS_ENABLED');
        putenv('SA_DB_TOOLS_WRITABLE');
        $this->assertFalse(SaDbTools::isEnabled());
        $this->assertFalse(SaDbTools::isWritable());
    }

    public function testReadOnlyWhenEnabled()
    {
        putenv('SA_DB_TOOLS_ENABLED=true');
        putenv('SA_DB_TOOLS_WRITABLE=false');
        putenv('APP_ENV=dev');
        $this->assertTrue(SaDbTools::isEnabled());
        $this->assertFalse(SaDbTools::isWritable());
    }

    public function testWritableBlockedInProduction()
    {
        putenv('SA_DB_TOOLS_ENABLED=true');
        putenv('SA_DB_TOOLS_WRITABLE=true');
        putenv('APP_ENV=production');
        $this->assertTrue(SaDbTools::isEnabled());
        $this->assertFalse(SaDbTools::isWritable());
    }

    public function testWritableAllowedInDevWhenExplicit()
    {
        putenv('SA_DB_TOOLS_ENABLED=true');
        putenv('SA_DB_TOOLS_WRITABLE=true');
        putenv('APP_ENV=dev');
        $this->assertTrue(SaDbTools::isEnabled());
        $this->assertTrue(SaDbTools::isWritable());
    }
}
