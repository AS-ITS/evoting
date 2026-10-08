<?php

namespace app\components;

use Yii;
use app\models\Config;

/**
 * 網站品牌設定（logo / favicon / copyright / 首頁標題）
 *
 * 優先讀取 config 表；欄位為空時 fallback 至 APP_NAME / 預設資產路徑。
 */
class Branding
{
    /** @var Config|null */
    private static $config;

    /**
     * @return Config|null
     */
    public static function config()
    {
        if (self::$config === null) {
            try {
                self::$config = Config::findOne(Yii::$app->id);
            } catch (\Throwable $e) {
                self::$config = null;
            }
        }

        return self::$config;
    }

    /**
     * 測試／單元測試可重置快取
     */
    public static function reset(): void
    {
        self::$config = null;
    }

    /**
     * Logo 相對 @web 路徑；空字串表示不顯示
     */
    public static function logoPath(): string
    {
        $cfg = self::config();
        return trim((string) ($cfg->logoPath ?? ''));
    }

    /**
     * Logo 完整 URL；無 logo 時回傳 null
     */
    public static function logoUrl(): ?string
    {
        $path = self::logoPath();
        if ($path === '') {
            return null;
        }

        return Yii::getAlias('@web/' . ltrim($path, '/'));
    }

    /**
     * Favicon 相對 @web 路徑
     */
    public static function faviconPath(): string
    {
        $cfg = self::config();
        $path = trim((string) ($cfg->faviconPath ?? ''));

        return $path !== '' ? $path : 'favicon.ico';
    }

    /**
     * Favicon 完整 URL
     */
    public static function faviconUrl(): string
    {
        return Yii::getAlias('@web/' . ltrim(self::faviconPath(), '/'));
    }

    /**
     * Footer 版權文字
     */
    public static function copyright(): string
    {
        $cfg = self::config();
        $custom = trim((string) ($cfg->copyright ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return sprintf(
            '%s © %s',
            Yii::t('app', Yii::$app->name),
            date('Y')
        );
    }

    /**
     * 投票流程頁等使用的標題（依目前語系，來源為首頁標題）
     */
    public static function siteTitle(): string
    {
        $cfg = self::config();
        if ($cfg !== null) {
            $homeZh = trim((string) ($cfg->homeTitle ?? ''));
            $homeEn = trim((string) ($cfg->homeTitleE ?? ''));
            if ($homeZh !== '' || $homeEn !== '') {
                return self::pickLocale($homeEn, $homeZh);
            }
        }

        return Yii::t('app', Yii::$app->name);
    }

    /**
     * @param string $en
     * @param string $zh
     * @return string
     */
    private static function pickLocale(string $en, string $zh): string
    {
        $lang = strtolower(Yii::$app->language);
        if ($lang === 'zh-tw') {
            return $zh !== '' ? $zh : $en;
        }

        return $en !== '' ? $en : $zh;
    }
}
