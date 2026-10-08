<?php

namespace app\tests\unit\config;

use app\config\ConfigValidator;
use yii\base\InvalidConfigException;

/**
 * ConfigValidator 正式環境加密檢查（Phase 2.2）
 */
class ConfigValidatorProductionTest extends \Codeception\Test\Unit
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
    }

    private function setEnv(string $name, ?string $value): void
    {
        if (!array_key_exists($name, $this->savedEnv)) {
            $envValue = getenv($name);
            $this->savedEnv[$name] = $envValue === false ? false : $envValue;
        }
        if ($value === null || $value === '') {
            putenv($name);
        } else {
            putenv("{$name}={$value}");
        }
    }

    public function testProductionRejectsPlaintextDbPasswordInEnv()
    {
        $this->setEnv('APP_ENV', 'production');
        $this->setEnv('DB_PASSWORD', 'plaintext-db-secret');

        $errors = ConfigValidator::validate(
            ['db_password' => 'plaintext-db-secret', 'cookieValidationKey' => str_repeat('a', 32)],
            ['strict' => false]
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('DB_PASSWORD', implode("\n", $errors));
    }

    public function testProductionAcceptsEncryptedDbPasswordInEnv()
    {
        $encrypted = base64_encode(random_bytes(32));
        $this->setEnv('APP_ENV', 'production');
        $this->setEnv('DB_PASSWORD', $encrypted);
        $this->setEnv('ALLOWED_HOSTS', 'voting.example.com');

        $errors = ConfigValidator::validate(
            ['db_password' => 'decrypted-for-app', 'cookieValidationKey' => str_repeat('a', 32)],
            ['strict' => false]
        );

        $plaintextErrors = array_filter($errors, static function ($msg) {
            return strpos($msg, 'DB_PASSWORD') !== false && strpos($msg, 'Base64') !== false;
        });
        $this->assertEmpty($plaintextErrors, implode("\n", $errors));
    }

    public function testNonProductionAllowsPlaintextDbPassword()
    {
        $this->setEnv('APP_ENV', 'development');
        $this->setEnv('DB_PASSWORD', 'plaintext-db-secret');

        $errors = ConfigValidator::validate(
            ['db_password' => 'plaintext-db-secret', 'cookieValidationKey' => str_repeat('a', 32)],
            ['strict' => false]
        );

        $plaintextErrors = array_filter($errors, static function ($msg) {
            return strpos($msg, 'DB_PASSWORD') !== false && strpos($msg, 'Base64') !== false;
        });
        $this->assertEmpty($plaintextErrors);
    }

    public function testStrictProductionThrowsOnPlaintextDbPassword()
    {
        $this->setEnv('APP_ENV', 'production');
        $this->setEnv('DB_PASSWORD', 'plaintext-db-secret');

        $this->expectException(InvalidConfigException::class);
        ConfigValidator::validate(
            ['db_password' => 'x', 'cookieValidationKey' => str_repeat('a', 32)],
            ['strict' => true]
        );
    }

    public function testProductionRejectsMissingAllowedHosts()
    {
        $encrypted = base64_encode(random_bytes(32));
        $this->setEnv('APP_ENV', 'production');
        $this->setEnv('DB_PASSWORD', $encrypted);
        $this->setEnv('ALLOWED_HOSTS', null);

        $errors = ConfigValidator::validate(
            ['db_password' => 'decrypted', 'cookieValidationKey' => str_repeat('a', 32)],
            ['strict' => false]
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('ALLOWED_HOSTS', implode("\n", $errors));
    }

    public function testProductionRejectsInsecureAllowedHosts()
    {
        $encrypted = base64_encode(random_bytes(32));
        $this->setEnv('APP_ENV', 'production');
        $this->setEnv('DB_PASSWORD', $encrypted);
        $this->setEnv('ALLOWED_HOSTS', 'localhost,voting.example.com');

        $errors = ConfigValidator::validate(
            ['db_password' => 'decrypted', 'cookieValidationKey' => str_repeat('a', 32)],
            ['strict' => false]
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('localhost', implode("\n", $errors));
    }

    public function testProductionAcceptsValidAllowedHosts()
    {
        $encrypted = base64_encode(random_bytes(32));
        $this->setEnv('APP_ENV', 'production');
        $this->setEnv('DB_PASSWORD', $encrypted);
        $this->setEnv('ALLOWED_HOSTS', 'voting.example.com');

        $errors = ConfigValidator::validate(
            ['db_password' => 'decrypted', 'cookieValidationKey' => str_repeat('a', 32)],
            ['strict' => false]
        );

        $hostErrors = array_filter($errors, static function ($msg) {
            return strpos($msg, 'ALLOWED_HOSTS') !== false;
        });
        $this->assertEmpty($hostErrors, implode("\n", $errors));
    }
}
