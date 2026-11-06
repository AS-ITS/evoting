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
            $headers = $response->headers;

            // HSTS 適用所有回應（含檔案下載）
            static::applyStrictTransportSecurity($headers);

            if ($response->format === Response::FORMAT_RAW) {
                return;
            }

            $headers->set('X-Content-Type-Options', 'nosniff');
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
            $headers->set('X-XSS-Protection', '0');

            static::applyContentSecurityPolicy($headers);
        });
    }

    /**
     * 正式環境送出 HSTS。
     *
     * 瀏覽器會忽略經 HTTP 收到的 HSTS，因此不需判斷是否為 HTTPS 連線（反向代理終結 TLS 時亦適用）。
     * 不加 preload（難以撤回）；includeSubDomains 需確認同網域所有子網域皆為 HTTPS 後再以
     * HSTS_INCLUDE_SUBDOMAINS=true 開啟。HSTS_MAX_AGE=0 可停用。
     */
    private static function applyStrictTransportSecurity($headers): void
    {
        if (!\app\config\ConfigValidator::isProductionEnvironment()) {
            return;
        }

        $maxAge = getenv('HSTS_MAX_AGE');
        $maxAge = ($maxAge === false || trim($maxAge) === '') ? 31536000 : (int) $maxAge;
        if ($maxAge <= 0) {
            return;
        }

        $value = 'max-age=' . $maxAge;
        if (filter_var(getenv('HSTS_INCLUDE_SUBDOMAINS') ?: 'false', FILTER_VALIDATE_BOOLEAN)) {
            $value .= '; includeSubDomains';
        }

        $headers->set('Strict-Transport-Security', $value);
    }

    private static function applyContentSecurityPolicy($headers): void
    {
        $mode = strtolower(trim(getenv('CSP_MODE') ?: 'report-only'));
        if ($mode === 'off' || $mode === 'false' || $mode === '0') {
            return;
        }

        $policy = static::contentSecurityPolicy(getenv('CSP_POLICY') ?: 'compat');

        if ($mode === 'enforce') {
            $headers->set('Content-Security-Policy', $policy);
            return;
        }

        $headers->set('Content-Security-Policy-Report-Only', $policy);
    }

    /**
     * CSP 政策內容
     *
     * - compat（預設）：AdminLTE / Bootstrap / Yii 需 inline script、style 與 eval，過渡用的寬鬆政策
     * - strict：不允許任何 unsafe-*，為最終目標。搭配 report-only 用於測試環境找出違規
     *   （見 tests/e2e/README.md 的 CSP 違規收集）；修正完成前不可搭配 enforce
     */
    public static function contentSecurityPolicy(string $name): string
    {
        $strict = strtolower(trim($name)) === 'strict';

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            // report-sample：違規報告附上程式碼前 40 字，便於定位來源（不放寬任何限制）
            $strict ? "script-src 'self' 'report-sample'" : "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            $strict ? "style-src 'self' 'report-sample'" : "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
        ]);
    }
}
