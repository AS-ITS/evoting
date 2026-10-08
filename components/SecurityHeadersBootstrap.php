<?php

namespace app\components;

use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\web\Response;

/**
 * 回應安全標頭（Phase 2.4）
 */
class SecurityHeadersBootstrap implements BootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    public function bootstrap($app): void
    {
        if (!$app instanceof \yii\web\Application) {
            return;
        }

        Event::on(Response::class, Response::EVENT_BEFORE_SEND, static function ($event): void {
            /** @var Response $response */
            $response = $event->sender;
            if ($response->format === Response::FORMAT_RAW) {
                return;
            }

            $headers = $response->headers;
            $headers->set('X-Content-Type-Options', 'nosniff');
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
            $headers->set('X-XSS-Protection', '0');

            static::applyContentSecurityPolicy($headers);
        });
    }

    private static function applyContentSecurityPolicy($headers): void
    {
        $mode = strtolower(trim(getenv('CSP_MODE') ?: 'report-only'));
        if ($mode === 'off' || $mode === 'false' || $mode === '0') {
            return;
        }

        // AdminLTE / Bootstrap 需 inline script/style；先以 report-only 或寬鬆 policy 過渡
        $policy = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
        ]);

        if ($mode === 'enforce') {
            $headers->set('Content-Security-Policy', $policy);
            return;
        }

        $headers->set('Content-Security-Policy-Report-Only', $policy);
    }
}
