<?php

namespace app\tests\unit\config;

class TestEntryPolicyTest extends \Codeception\Test\Unit
{
    /** @var \Closure(?string): bool */
    private $isAllowed;

    protected function _before(): void
    {
        $this->isAllowed = require dirname(__DIR__, 3) . '/config/test-entry-policy.php';
    }

    public function testAllowsExplicitTestEnvironments(): void
    {
        $this->assertTrue(($this->isAllowed)('testing'));
        $this->assertTrue(($this->isAllowed)('test'));
        $this->assertTrue(($this->isAllowed)(' TESTING '));
    }

    /**
     * @dataProvider deniedEnvironmentProvider
     */
    public function testDeniesEveryOtherEnvironment(?string $environment): void
    {
        $this->assertFalse(($this->isAllowed)($environment));
    }

    public function deniedEnvironmentProvider(): array
    {
        return [
            'missing' => [null],
            'empty' => [''],
            'production' => ['production'],
            'production alias' => ['prod'],
            'development' => ['development'],
            'development alias' => ['dev'],
            'unknown' => ['staging'],
        ];
    }
}
