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

    public function testRawFormatSkipsSecurityHeaders()
    {
        (new SecurityHeadersBootstrap())->bootstrap(\Yii::$app);

        $response = \Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->trigger(Response::EVENT_BEFORE_SEND);

        $this->assertNull($response->headers->get('X-Content-Type-Options'));
    }
}
