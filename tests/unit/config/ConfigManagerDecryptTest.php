<?php

namespace app\tests\unit\config;

use app\config\ConfigManager;
use yii\base\InvalidConfigException;

/**
 * ConfigManager 讀取加密欄位：純英數明文不可被誤判為密文
 */
class ConfigManagerDecryptTest extends \Codeception\Test\Unit
{
    /** @var array<string, string|false> */
    private $savedEnv = [];

    protected function _before()
    {
        $this->setEnv('VOTING_MASTER_KEY', str_repeat('0123456789abcdef', 4));
    }

    protected function _after()
    {
        foreach ($this->savedEnv as $name => $value) {
            putenv($value === false ? $name : "{$name}={$value}");
        }
        ConfigManager::reset();
    }

    private function setEnv(string $name, string $value): void
    {
        if (!array_key_exists($name, $this->savedEnv)) {
            $this->savedEnv[$name] = getenv($name);
        }
        putenv("{$name}={$value}");
    }

    private function readDbPassword(string $appEnv, string $value)
    {
        $this->setEnv('APP_ENV', $appEnv);
        $this->setEnv('DB_PASSWORD', $value);
        ConfigManager::reset();
        return ConfigManager::getInstance()->get('DB_PASSWORD');
    }

    public function testDevelopmentKeepsAlphanumericPlaintext()
    {
        $this->assertSame('Voting2026', $this->readDbPassword('development', 'Voting2026'));
        // 長度 4 的倍數、可嚴格 Base64 解碼但不是 16 bytes 倍數
        $this->assertSame('abcdABCD1234', $this->readDbPassword('development', 'abcdABCD1234'));
    }

    public function testDevelopmentKeepsBlockSizedPlaintextThatFailsToDecrypt()
    {
        // 解碼後為 48 bytes（16 的倍數），但不是用目前主金鑰加密的值
        $value = str_repeat('A1b2', 16);
        $this->assertSame($value, $this->readDbPassword('development', $value));
    }

    public function testDevelopmentKeepsNonBase64Plaintext()
    {
        $this->assertSame('dev-secret!', $this->readDbPassword('testing', 'dev-secret!'));
    }

    public function testEncryptedValueIsDecrypted()
    {
        $this->setEnv('APP_ENV', 'development');
        ConfigManager::reset();
        $encrypted = ConfigManager::getInstance()->encrypt('Voting2026');

        $this->assertNotSame('Voting2026', $encrypted);
        $this->assertSame('Voting2026', $this->readDbPassword('development', $encrypted));
        $this->assertSame('Voting2026', $this->readDbPassword('production', $encrypted));
    }

    public function testProductionRejectsValueThatFailsToDecrypt()
    {
        $this->expectException(InvalidConfigException::class);
        $this->readDbPassword('production', 'Voting2026');
    }
}
