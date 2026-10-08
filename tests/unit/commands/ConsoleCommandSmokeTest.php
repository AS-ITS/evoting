<?php

namespace app\tests\unit\commands;

/**
 * Console 命令 smoke 測試（P3-2）
 *
 * 透過子程序執行 yii 命令，使用 console-test 配置。
 */
class ConsoleCommandSmokeTest extends \Codeception\Test\Unit
{
    private function runYii(string $route, array $args = []): array
    {
        $root = dirname(__DIR__, 3);
        $cmd = 'cd ' . escapeshellarg($root)
            . ' && php yii ' . escapeshellarg($route);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg($arg);
        }
        $cmd .= ' --appconfig=config/console-test.php 2>&1';

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        return [$exitCode, implode("\n", $output)];
    }

    public function testEncryptTextSmoke()
    {
        [$exitCode, $output] = $this->runYii('encrypt/text', ['smoke-test-value']);
        $this->assertSame(0, $exitCode, "encrypt/text failed: {$output}");
        $this->assertStringContainsString('加密結果', $output);
    }

    public function testEncryptTestSmoke()
    {
        [$exitCode, $output] = $this->runYii('encrypt/test', ['DB_PASSWORD']);
        $this->assertSame(0, $exitCode, "encrypt/test failed: {$output}");
    }

    public function testMigratePasswordsVerifySmoke()
    {
        [$exitCode, $output] = $this->runYii('migrate-passwords/verify');
        $this->assertSame(0, $exitCode, "migrate-passwords/verify failed: {$output}");
        $this->assertStringContainsString('驗證', $output);
    }
}
