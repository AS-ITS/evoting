<?php

namespace app\components;

/**
 * 反向代理 trustedHosts 設定（M1）
 *
 * 透過 TRUSTED_PROXY_CIDRS（逗號分隔 CIDR，如 10.0.0.0/8,172.16.0.0/12）擴充 Yii Request 信任清單。
 * 127.0.0.1/32 永遠保留（本機 nginx）。
 */
class TrustedProxyConfig
{
    private const PROXY_HEADERS = [
        'X-Real-IP',
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
    ];

    /**
     * @return array<string, string[]>
     */
    public static function getTrustedHosts(): array
    {
        $hosts = [
            '127.0.0.1/32' => self::PROXY_HEADERS,
        ];

        $raw = getenv('TRUSTED_PROXY_CIDRS') ?: '';
        foreach (array_filter(array_map('trim', explode(',', $raw))) as $cidr) {
            if ($cidr !== '' && !isset($hosts[$cidr])) {
                $hosts[$cidr] = self::PROXY_HEADERS;
            }
        }

        return $hosts;
    }
}
