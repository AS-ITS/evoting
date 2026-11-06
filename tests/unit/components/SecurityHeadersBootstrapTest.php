<?php

namespace app\tests\unit\components;

use app\components\SecurityHeadersBootstrap;
use yii\web\Response;

/**
 * SecurityHeadersBootstrap 單元測試（Phase 2.4）
 */
class SecurityHeadersBootstrapTest extends \Codeception\Test\Unit
{
    public function testBeforeSendAddsSecurityHeaders()
    {
        (new SecurityHeadersBootstrap())->bootstrap(\Yii::$app);

        $response = \Yii::$app->response;
        $response->format = Response::FORMAT_HTML;
        $response->trigger(Response::EVENT_BEFORE_SEND);

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('0', $response->headers->get('X-XSS-Protection'));
    }

    public function testReportOnlyCspByDefault()
    {
        putenv('CSP_MODE');
        (new SecurityHeadersBootstrap())->bootstrap(\Yii::$app);

        $response = \Yii::$app->response;
        $response->format = Response::FORMAT_HTML;
        $response->trigger(Response::EVENT_BEFORE_SEND);

        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function testCspOffSkipsHeader()
    {
        putenv('CSP_MODE=off');
        (new SecurityHeadersBootstrap())->bootstrap(\Yii::$app);

        $response = \Yii::$app->response;
        $response->format = Response::FORMAT_HTML;
        $response->trigger(Response::EVENT_BEFORE_SEND);

        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($response->headers->get('Content-Security-Policy'));
        putenv('CSP_MODE');
    }

    /**
     * 以全新 Response 觸發，避免沿用其他測試留下的標頭
     */
    private function hstsFor(array $env, string $format = Response::FORMAT_HTML): ?string
    {
        $names = ['APP_ENV', 'HSTS_MAX_AGE', 'HSTS_INCLUDE_SUBDOMAINS'];
        $saved = [];
        foreach ($names as $name) {
            $saved[$name] = getenv($name);
            putenv(array_key_exists($name, $env) ? "{$name}={$env[$name]}" : $name);
        }
        try {
            (new SecurityHeadersBootstrap())->bootstrap(\Yii::$app);
            $response = new Response(['format' => $format]);
            $response->trigger(Response::EVENT_BEFORE_SEND);
            return $response->headers->get('Strict-Transport-Security');
        } finally {
            foreach ($saved as $name => $value) {
                putenv($value === false ? $name : "{$name}={$value}");
            }
        }
    }

    public function testProductionSendsHstsWithoutPreloadOrSubdomainsByDefault()
    {
        $this->assertSame('max-age=31536000', $this->hstsFor(['APP_ENV' => 'production']));
    }

    public function testHstsIncludeSubdomainsIsOptIn()
    {
        $value = $this->hstsFor(['APP_ENV' => 'production', 'HSTS_INCLUDE_SUBDOMAINS' => 'true']);
        $this->assertSame('max-age=31536000; includeSubDomains', $value);
        $this->assertStringNotContainsString('preload', $value);
    }

    public function testHstsMaxAgeConfigurableAndZeroDisables()
    {
        $this->assertSame('max-age=600', $this->hstsFor(['APP_ENV' => 'production', 'HSTS_MAX_AGE' => '600']));
        $this->assertNull($this->hstsFor(['APP_ENV' => 'production', 'HSTS_MAX_AGE' => '0']));
    }

    public function testNonProductionSendsNoHsts()
    {
        foreach (['development', 'testing'] as $appEnv) {
            $this->assertNull($this->hstsFor(['APP_ENV' => $appEnv]), $appEnv);
        }
    }

    public function testHstsAppliesToRawResponses()
    {
        $this->assertSame('max-age=31536000', $this->hstsFor(['APP_ENV' => 'production'], Response::FORMAT_RAW));
    }

    public function testAttributeDiffHelperNoLongerSendsHeaders()
    {
        $source = file_get_contents(dirname(__DIR__, 3) . '/components/helper/ArrayHelper.php');
        $this->assertStringNotContainsString('Strict-Transport-Security', $source);
    }

    public function testRawFormatSkipsSecurityHeaders()
    {
        (new SecurityHeadersBootstrap())->bootstrap(\Yii::$app);

        $response = \Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->trigger(Response::EVENT_BEFORE_SEND);

        $this->assertNull($response->headers->get('X-Content-Type-Options'));
    }
}
