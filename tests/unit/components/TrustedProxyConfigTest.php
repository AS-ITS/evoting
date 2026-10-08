<?php

namespace app\tests\unit\components;

use app\components\TrustedProxyConfig;
use Codeception\Test\Unit;

class TrustedProxyConfigTest extends Unit
{
    private ?string $originalCidrs = null;

    protected function _before()
    {
        $this->originalCidrs = getenv('TRUSTED_PROXY_CIDRS') ?: null;
    }

    protected function _after()
    {
        if ($this->originalCidrs === null) {
            putenv('TRUSTED_PROXY_CIDRS');
        } else {
            putenv('TRUSTED_PROXY_CIDRS=' . $this->originalCidrs);
        }
    }

    public function testAlwaysIncludesLocalhost()
    {
        putenv('TRUSTED_PROXY_CIDRS');
        $hosts = TrustedProxyConfig::getTrustedHosts();
        $this->assertArrayHasKey('127.0.0.1/32', $hosts);
    }

    public function testMergesEnvCidrs()
    {
        putenv('TRUSTED_PROXY_CIDRS=10.0.0.0/8,172.16.0.0/12');
        $hosts = TrustedProxyConfig::getTrustedHosts();
        $this->assertArrayHasKey('10.0.0.0/8', $hosts);
        $this->assertArrayHasKey('172.16.0.0/12', $hosts);
    }
}
