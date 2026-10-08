<?php

namespace app\tests\unit\components;

use app\components\MasterKeyLoader;
use yii\base\InvalidConfigException;

/**
 * MasterKeyLoader 單元測試（P3-2）
 */
class MasterKeyLoaderTest extends \Codeception\Test\Unit
{
    /** @var array<string, string|false> */
    private $savedEnv = [];

    protected function _after()
    {
        foreach ($this->savedEnv as $name => $value) {
            if ($value === false) {
                putenv($name);
            } else {
                putenv("{$name}={$value}");
            }
        }
        MasterKeyLoader::reset();
    }

    private function saveEnv(string $name): void
    {
        if (!array_key_exists($name, $this->savedEnv)) {
            $value = getenv($name);
            $this->savedEnv[$name] = $value === false ? false : $value;
        }
    }

    private function setEnv(string $name, ?string $value): void
    {
        $this->saveEnv($name);
        if ($value === null || $value === '') {
            putenv($name);
        } else {
            putenv("{$name}={$value}");
        }
        MasterKeyLoader::reset();
    }

    public function testLoadKeyFailsWhenNoSourceConfigured()
    {
        $this->setEnv(MasterKeyLoader::ENV_VAR_NAME, null);
        $this->setEnv(MasterKeyLoader::ENV_VAR_FALLBACK, null);
        $this->setEnv('VOTING_KEY_FILE', null);

        $loader = MasterKeyLoader::getInstance();
        $loader->allowEnvFallback = false;

        $this->expectException(InvalidConfigException::class);
        $loader->loadKey();
    }

    public function testLoadKeyFromSystemEnv()
    {
        $key = base64_encode(random_bytes(64));
        $this->setEnv(MasterKeyLoader::ENV_VAR_NAME, $key);
        $this->setEnv(MasterKeyLoader::ENV_VAR_FALLBACK, null);

        $loader = MasterKeyLoader::getInstance();
        $this->assertSame($key, $loader->loadKey());
        $this->assertSame('system_env:' . MasterKeyLoader::ENV_VAR_NAME, $loader->getKeySource());
    }

    public function testValidateKeyRejectsInvalidBase64()
    {
        $this->assertFalse(MasterKeyLoader::getInstance()->validateKey('!!!not-base64!!!'));
    }

    public function testValidateKeyAcceptsStrongKey()
    {
        $key = base64_encode(random_bytes(64));
        $this->assertTrue(MasterKeyLoader::getInstance()->validateKey($key));
    }

    public function testStatusReportStructure()
    {
        $report = MasterKeyLoader::getInstance()->getStatusReport();
        $this->assertArrayHasKey('system_env', $report);
        $this->assertArrayHasKey('key_file', $report);
        $this->assertArrayHasKey('env_fallback', $report);
    }

    public function testProductionRejectsEnvFallbackMasterKey()
    {
        $key = base64_encode(random_bytes(64));
        $this->setEnv('APP_ENV', 'production');
        $this->setEnv(MasterKeyLoader::ENV_VAR_NAME, null);
        $this->setEnv('VOTING_KEY_FILE', null);
        $this->setEnv(MasterKeyLoader::ENV_VAR_FALLBACK, $key);

        $loader = MasterKeyLoader::getInstance();
        $loader->allowEnvFallback = true;

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('正式環境禁止使用 .env 中的 MASTER_KEY');
        $loader->loadKey();
    }
}
