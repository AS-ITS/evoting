<?php

namespace app\tests\unit\components;

use app\components\Branding;
use Codeception\Test\Unit;
use Yii;

class BrandingTest extends Unit
{
    protected function _before()
    {
        Branding::reset();
    }

    public function testCopyrightFallbackUsesAppName()
    {
        $text = Branding::copyright();
        $this->assertStringContainsString((string) Yii::$app->name, $text);
        $this->assertStringContainsString((string) date('Y'), $text);
        $this->assertStringNotContainsString('Academia Sinica', $text);
    }

    public function testFaviconDefaultsToFaviconIco()
    {
        $this->assertSame('favicon.ico', Branding::faviconPath());
        $this->assertStringContainsString('favicon.ico', Branding::faviconUrl());
    }

    public function testLogoEmptyMeansNoUrl()
    {
        // Without logoPath configured, logoUrl should be null
        $this->assertSame('', Branding::logoPath());
        $this->assertNull(Branding::logoUrl());
    }

    public function testSiteTitleFallback()
    {
        $title = Branding::siteTitle();
        $this->assertNotSame('', $title);
        $this->assertStringNotContainsString('中央研究院', $title);
    }
}
