<?php

namespace app\tests\unit\config;

use app\config\ConfigValidator;
use Codeception\Test\Unit;

class ConfigValidatorSaDbToolsTest extends Unit
{
    private array $originalEnv = [];

    protected function _before()
    {
        $this->originalEnv = [
            'APP_ENV' => getenv('APP_ENV'),
            'SA_DB_TOOLS_ENABLED' => getenv('SA_DB_TOOLS_ENABLED'),
            'SA_DB_TOOLS_WRITABLE' => getenv('SA_DB_TOOLS_WRITABLE'),
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

    public function testProductionRejectsSaDbToolsEnabled()
    {
        putenv('APP_ENV=production');
        putenv('SA_DB_TOOLS_ENABLED=true');
        putenv('SA_DB_TOOLS_WRITABLE=false');

        $errors = ConfigValidator::validate([], ['strict' => false, 'checkEncryption' => false]);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('SA_DB_TOOLS_ENABLED', implode("\n", $errors));
    }
}
