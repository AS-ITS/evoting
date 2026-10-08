<?php

namespace app\tests\unit\components;

use app\components\LogSanitizer;
use Codeception\Test\Unit;

class LogSanitizerTest extends Unit
{
    public function testRedactsPasswordFieldsInArray()
    {
        $sanitized = LogSanitizer::redactArray([
            'cn' => 'admin',
            'password' => '$2y$10$hash',
            'nested' => ['passwd' => 'secret'],
        ]);

        $this->assertSame('admin', $sanitized['cn']);
        $this->assertSame('[REDACTED]', $sanitized['password']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['passwd']);
    }

    public function testSanitizeContextJsonString()
    {
        $json = LogSanitizer::sanitizeContext([
            'password_plain' => 'TestSecret123',
            'roles' => 'sa',
        ]);

        $this->assertStringNotContainsString('TestSecret123', $json);
        $this->assertStringContainsString('[REDACTED]', $json);
        $this->assertStringContainsString('sa', $json);
    }

    public function testRedactsEnvKeysWithUnderscores()
    {
        $sanitized = LogSanitizer::redactArray([
            'VOTING_MASTER_KEY' => 'base64-secret-value',
            'COOKIE_VALIDATION_KEY' => 'hex-secret-value',
        ]);

        $this->assertSame('[REDACTED]', $sanitized['VOTING_MASTER_KEY']);
        $this->assertSame('[REDACTED]', $sanitized['COOKIE_VALIDATION_KEY']);
    }

    public function testSanitizeStringEnvLines()
    {
        $text = LogSanitizer::sanitizeString('MASTER_KEY=abc123 COOKIE_VALIDATION_KEY=deadbeef');

        $this->assertStringNotContainsString('abc123', $text);
        $this->assertStringNotContainsString('deadbeef', $text);
        $this->assertStringContainsString('MASTER_KEY=[REDACTED]', $text);
        $this->assertStringContainsString('COOKIE_VALIDATION_KEY=[REDACTED]', $text);
    }
}
