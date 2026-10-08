<?php

namespace app\tests\unit\components;

use app\components\helper\StringHelper;
use app\tests\fixtures\ConfigFixture;

/**
 * StringHelper 元件測試
 * 測試字串輔助類別的各項功能
 */
class StringHelperTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([
            'config' => ConfigFixture::class,
        ]);
    }

    // ==================== matchWithAsterisk() 測試 ====================

    /**
     * 測試：matchWithAsterisk() 前後都有星號
     */
    public function testMatchWithAsteriskBothSides()
    {
        $this->assertTrue(StringHelper::matchWithAsterisk('*world*', 'hello world'));
        $this->assertTrue(StringHelper::matchWithAsterisk('*ello*', 'hello'));
    }

    /**
     * 測試：matchWithAsterisk() 只有前面星號
     */
    public function testMatchWithAsteriskFront()
    {
        $this->assertTrue(StringHelper::matchWithAsterisk('*world', 'hello world'));
        $this->assertFalse(StringHelper::matchWithAsterisk('*world', 'world hello'));
    }

    /**
     * 測試：matchWithAsterisk() 只有後面星號
     */
    public function testMatchWithAsteriskBack()
    {
        $this->assertTrue(StringHelper::matchWithAsterisk('hello*', 'hello world'));
        $this->assertFalse(StringHelper::matchWithAsterisk('world*', 'hello world'));
    }

    /**
     * 測試：matchWithAsterisk() 多個星號
     */
    public function testMatchWithAsteriskMultiple()
    {
        $this->assertTrue(StringHelper::matchWithAsterisk('*ello*w*', 'hello world'));
        $this->assertTrue(StringHelper::matchWithAsterisk('*w*o*r*l*d*', 'hello world'));
    }

    /**
     * 測試：matchWithAsterisk() 無星號完全匹配
     */
    public function testMatchWithAsteriskExact()
    {
        $this->assertTrue(StringHelper::matchWithAsterisk('hello', 'hello'));
        $this->assertFalse(StringHelper::matchWithAsterisk('hello', 'hello world'));
    }

    /**
     * 測試：matchWithAsterisk() 大小寫不敏感
     */
    public function testMatchWithAsteriskCaseInsensitive()
    {
        $this->assertTrue(StringHelper::matchWithAsterisk('*WORLD*', 'hello world'));
        $this->assertTrue(StringHelper::matchWithAsterisk('*world*', 'HELLO WORLD'));
    }

    // ==================== getVerboseSize() 測試 ====================

    /**
     * 測試：getVerboseSize() 字節
     */
    public function testGetVerboseSizeBytes()
    {
        $this->assertEquals('500 B', StringHelper::getVerboseSize(500));
        $this->assertEquals('0 B', StringHelper::getVerboseSize(0));
    }

    /**
     * 測試：getVerboseSize() KB
     */
    public function testGetVerboseSizeKB()
    {
        $this->assertEquals('1 KB', StringHelper::getVerboseSize(1024));
        $this->assertEquals('5 KB', StringHelper::getVerboseSize(5 * 1024));
    }

    /**
     * 測試：getVerboseSize() MB
     */
    public function testGetVerboseSizeMB()
    {
        $this->assertEquals('1 MB', StringHelper::getVerboseSize(1024 * 1024));
        $this->assertEquals('10 MB', StringHelper::getVerboseSize(10 * 1024 * 1024));
    }

    /**
     * 測試：getVerboseSize() GB
     */
    public function testGetVerboseSizeGB()
    {
        $this->assertEquals('1 GB', StringHelper::getVerboseSize(1024 * 1024 * 1024));
    }

    /**
     * 測試：getVerboseSize() 自訂精確度
     */
    public function testGetVerboseSizeCustomPrecision()
    {
        $bytes = 1536; // 1.5 KB
        $this->assertEquals('1.5 KB', StringHelper::getVerboseSize($bytes, 1));
        $this->assertEquals('1.5 KB', StringHelper::getVerboseSize($bytes, 2)); // round() 不保留尾隨零
    }

    // ==================== getByteSize() 測試 ====================

    /**
     * 測試：getByteSize() KB
     */
    public function testGetByteSizeKB()
    {
        $this->assertEquals(5 * 1024, StringHelper::getByteSize('5K'));
        $this->assertEquals(5 * 1024, StringHelper::getByteSize('5KB'));
        $this->assertEquals(5 * 1024, StringHelper::getByteSize('5 KB'));
    }

    /**
     * 測試：getByteSize() MB
     */
    public function testGetByteSizeMB()
    {
        $this->assertEquals(10 * 1024 * 1024, StringHelper::getByteSize('10M'));
        $this->assertEquals(10 * 1024 * 1024, StringHelper::getByteSize('10MB'));
    }

    /**
     * 測試：getByteSize() GB
     */
    public function testGetByteSizeGB()
    {
        $this->assertEquals(1024 * 1024 * 1024, StringHelper::getByteSize('1G'));
        $this->assertEquals(1024 * 1024 * 1024, StringHelper::getByteSize('1GB'));
    }

    /**
     * 測試：getByteSize() Bytes
     */
    public function testGetByteSizeBytes()
    {
        $this->assertEquals(500, StringHelper::getByteSize('500B'));
        $this->assertEquals(500, StringHelper::getByteSize('500'));
        $this->assertEquals(500, StringHelper::getByteSize('500 Bytes'));
    }

    /**
     * 測試：getByteSize() 無效輸入
     */
    public function testGetByteSizeInvalid()
    {
        $this->assertEquals(0, StringHelper::getByteSize('invalid'));
        $this->assertEquals(0, StringHelper::getByteSize(''));
    }

    /**
     * 測試：getByteSize() 小數
     */
    public function testGetByteSizeDecimal()
    {
        $this->assertEquals(1.5 * 1024, StringHelper::getByteSize('1.5K'));
    }

    // ==================== str_contains() 測試 ====================

    /**
     * 測試：str_contains() 包含子字串
     */
    public function testStrContainsFound()
    {
        $this->assertTrue(StringHelper::str_contains('hello world', 'world'));
        $this->assertTrue(StringHelper::str_contains('hello world', 'hello'));
        $this->assertTrue(StringHelper::str_contains('hello world', 'o w'));
    }

    /**
     * 測試：str_contains() 不包含子字串
     */
    public function testStrContainsNotFound()
    {
        $this->assertFalse(StringHelper::str_contains('hello world', 'xyz'));
        $this->assertFalse(StringHelper::str_contains('hello world', 'World')); // 大小寫敏感
    }

    /**
     * 測試：str_contains() 空字串
     */
    public function testStrContainsEmpty()
    {
        $this->assertFalse(StringHelper::str_contains('hello world', ''));
    }

    /**
     * 測試：str_contains() 中文字串
     */
    public function testStrContainsChinese()
    {
        $this->assertTrue(StringHelper::str_contains('你好世界', '世界'));
        $this->assertFalse(StringHelper::str_contains('你好世界', '地球'));
    }

    // ==================== combinedSimilarityScore() 測試 ====================

    /**
     * 測試：combinedSimilarityScore() 相同字串
     */
    public function testCombinedSimilarityScoreIdentical()
    {
        $score = StringHelper::combinedSimilarityScore('hello', 'hello');
        $this->assertEquals(100, $score);
    }

    /**
     * 測試：combinedSimilarityScore() 相似字串
     */
    public function testCombinedSimilarityScoreSimilar()
    {
        $score = StringHelper::combinedSimilarityScore('hello', 'hallo');
        $this->assertGreaterThan(70, $score);
        $this->assertLessThan(100, $score);
    }

    /**
     * 測試：combinedSimilarityScore() 完全不同字串
     */
    public function testCombinedSimilarityScoreDifferent()
    {
        $score = StringHelper::combinedSimilarityScore('abc', 'xyz');
        $this->assertLessThan(50, $score);
    }

    // ==================== chkIpRange() 測試 ====================

    /**
     * 測試：chkIpRange() 單一 IP
     */
    public function testChkIpRangeSingleIp()
    {
        $this->assertTrue(StringHelper::chkIpRange('192.168.1.1', '192.168.1.1'));
        $this->assertFalse(StringHelper::chkIpRange('192.168.1.1', '192.168.1.2'));
    }

    /**
     * 測試：chkIpRange() IP 範圍
     */
    public function testChkIpRangeRange()
    {
        $this->assertTrue(StringHelper::chkIpRange('192.168.1.1~10', '192.168.1.5'));
        $this->assertTrue(StringHelper::chkIpRange('192.168.1.1~10', '192.168.1.1'));
        $this->assertTrue(StringHelper::chkIpRange('192.168.1.1~10', '192.168.1.10'));
        $this->assertFalse(StringHelper::chkIpRange('192.168.1.1~10', '192.168.1.11'));
    }

    /**
     * 測試：chkIpRange() 多個 IP 規則
     */
    public function testChkIpRangeMultiple()
    {
        $this->assertTrue(StringHelper::chkIpRange('192.168.1.1,192.168.1.5', '192.168.1.1'));
        $this->assertTrue(StringHelper::chkIpRange('192.168.1.1,192.168.1.5', '192.168.1.5'));
        $this->assertFalse(StringHelper::chkIpRange('192.168.1.1,192.168.1.5', '192.168.1.3'));
    }

    /**
     * 測試：chkIpRange() 部分 IP 匹配
     */
    public function testChkIpRangePartial()
    {
        $this->assertTrue(StringHelper::chkIpRange('192.168', '192.168.1.1'));
        $this->assertTrue(StringHelper::chkIpRange('192.168.1', '192.168.1.100'));
        $this->assertFalse(StringHelper::chkIpRange('192.168', '192.169.1.1'));
    }

    // ==================== genRandStr() 測試 ====================

    /**
     * 測試：genRandStr() 預設長度
     */
    public function testGenRandStrDefaultLength()
    {
        $result = StringHelper::genRandStr();
        $this->assertEquals(10, strlen($result));
    }

    /**
     * 測試：genRandStr() 自訂長度
     */
    public function testGenRandStrCustomLength()
    {
        $result = StringHelper::genRandStr(20);
        $this->assertEquals(20, strlen($result));
    }

    /**
     * 測試：genRandStr() 只有數字
     */
    public function testGenRandStrOnlyNumbers()
    {
        $result = StringHelper::genRandStr(10, false, false, true);
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $result);
    }

    /**
     * 測試：genRandStr() 只有小寫字母
     */
    public function testGenRandStrOnlyLower()
    {
        $result = StringHelper::genRandStr(10, true, false, false);
        $this->assertMatchesRegularExpression('/^[a-z]+$/', $result);
    }

    /**
     * 測試：genRandStr() 只有大寫字母
     */
    public function testGenRandStrOnlyUpper()
    {
        $result = StringHelper::genRandStr(10, false, true, false);
        $this->assertMatchesRegularExpression('/^[A-Z]+$/', $result);
    }

    /**
     * 測試：genRandStr() 無字元類型拋出例外
     */
    public function testGenRandStrNoCharTypes()
    {
        $this->expectException(\yii\base\InvalidArgumentException::class);
        StringHelper::genRandStr(10, false, false, false);
    }

    /**
     * 測試：genRandStr() 唯一性
     */
    public function testGenRandStrUniqueness()
    {
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $results[] = StringHelper::genRandStr(20);
        }
        $uniqueResults = array_unique($results);
        $this->assertCount(100, $uniqueResults, 'All generated strings should be unique');
    }

    // ==================== getMediaTypeName() 測試 ====================

    /**
     * 測試：getMediaTypeName() 文字類型
     */
    public function testGetMediaTypeNameText()
    {
        $this->assertEquals('純文字', StringHelper::getMediaTypeName('text/plain'));
        $this->assertEquals('網頁', StringHelper::getMediaTypeName('text/html'));
    }

    /**
     * 測試：getMediaTypeName() 應用程式類型
     */
    public function testGetMediaTypeNameApplication()
    {
        $this->assertEquals('PDF檔案', StringHelper::getMediaTypeName('application/pdf'));
        $this->assertEquals('Microsoft Word檔案', StringHelper::getMediaTypeName('application/msword'));
    }

    /**
     * 測試：getMediaTypeName() 圖片類型
     */
    public function testGetMediaTypeNameImage()
    {
        $this->assertEquals('GIF圖片', StringHelper::getMediaTypeName('image/gif'));
        $this->assertEquals('PNG圖片', StringHelper::getMediaTypeName('image/png'));
        $this->assertEquals('JPEG圖片', StringHelper::getMediaTypeName('image/jpeg'));
    }

    /**
     * 測試：getMediaTypeName() 音訊類型
     */
    public function testGetMediaTypeNameAudio()
    {
        $this->assertEquals('MP3音訊', StringHelper::getMediaTypeName('audio/mpeg'));
        $this->assertEquals('AAC音訊', StringHelper::getMediaTypeName('audio/aac'));
    }

    /**
     * 測試：getMediaTypeName() 影片類型
     */
    public function testGetMediaTypeNameVideo()
    {
        $this->assertEquals('MPEG影片', StringHelper::getMediaTypeName('video/mpeg'));
        $this->assertEquals('MPEG-4影片', StringHelper::getMediaTypeName('video/mp4'));
    }

    /**
     * 測試：getMediaTypeName() 未知類型
     */
    public function testGetMediaTypeNameUnknown()
    {
        $result = StringHelper::getMediaTypeName('unknown/type');
        $this->assertStringContainsString('未知類型', $result);
    }
}
