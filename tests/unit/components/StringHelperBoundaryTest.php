<?php

namespace app\tests\unit\components;

use app\components\helper\StringHelper;

/**
 * StringHelper 邊界值分析與等價劃分測試
 *
 * 補強項目：
 * - EP：getByteSize 負數/無單位/零值
 * - EP：chkIpRange 格式錯誤/空值
 * - BVA：getVerboseSize 邊界（0, 1023, 1024）
 */
class StringHelperBoundaryTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // ==================== EP：getByteSize 邊界 ====================

    /**
     * EP：零值大小
     */
    public function testGetByteSizeZero()
    {
        $result = StringHelper::getByteSize('0KB');
        $this->assertEquals(0, $result, '0KB should be 0 bytes');
    }

    /**
     * EP：無單位的數字
     */
    public function testGetByteSizeNoUnit()
    {
        $result = StringHelper::getByteSize('1024');
        // 無單位可能返回原數字或 0，記錄實際行為
        $this->assertIsNumeric($result, 'Number without unit should return numeric');
    }

    /**
     * EP：空字串
     */
    public function testGetByteSizeEmpty()
    {
        $result = StringHelper::getByteSize('');
        $this->assertIsNumeric($result, 'Empty string should return numeric');
    }

    // ==================== BVA：getVerboseSize 邊界 ====================

    /**
     * BVA：0 bytes
     */
    public function testGetVerboseSizeZero()
    {
        $result = StringHelper::getVerboseSize(0);
        $this->assertIsString($result, '0 bytes should return string');
    }

    /**
     * BVA：1023 bytes（KB 邊界 - 1）
     */
    public function testGetVerboseSize1023()
    {
        $result = StringHelper::getVerboseSize(1023);
        $this->assertIsString($result, '1023 bytes should return string');
        // 1023 bytes 仍然在 bytes 範圍
        $this->assertStringContainsString('B', $result);
    }

    /**
     * BVA：1024 bytes（恰好 1 KB）
     */
    public function testGetVerboseSize1024()
    {
        $result = StringHelper::getVerboseSize(1024);
        $this->assertIsString($result, '1024 bytes should return string');
        $this->assertStringContainsString('K', $result);
    }

    /**
     * BVA：1025 bytes（KB 邊界 + 1）
     */
    public function testGetVerboseSize1025()
    {
        $result = StringHelper::getVerboseSize(1025);
        $this->assertIsString($result, '1025 bytes should return string');
        $this->assertStringContainsString('K', $result);
    }

    // ==================== EP：chkIpRange 邊界 ====================

    /**
     * EP：空字串 IP 範圍
     */
    public function testChkIpRangeEmptyRange()
    {
        $result = StringHelper::chkIpRange('', '192.168.1.1');
        $this->assertIsBool($result, 'Empty range should return bool');
    }

    /**
     * EP：空字串 IP
     */
    public function testChkIpRangeEmptyIp()
    {
        $result = StringHelper::chkIpRange('192.168.1.1', '');
        $this->assertIsBool($result, 'Empty IP should return bool');
    }

    /**
     * EP：IP 範圍邊界值（起始 = 結束）
     */
    public function testChkIpRangeSingleRange()
    {
        $result = StringHelper::chkIpRange('192.168.1.5~5', '192.168.1.5');
        $this->assertTrue($result, 'IP in single-value range should match');
    }

    /**
     * EP：IP 範圍最小值
     */
    public function testChkIpRangeAtStart()
    {
        $result = StringHelper::chkIpRange('192.168.1.1~10', '192.168.1.1');
        $this->assertTrue($result, 'IP at range start should match');
    }

    /**
     * EP：IP 範圍最大值
     */
    public function testChkIpRangeAtEnd()
    {
        $result = StringHelper::chkIpRange('192.168.1.1~10', '192.168.1.10');
        $this->assertTrue($result, 'IP at range end should match');
    }

    /**
     * EP：IP 超出範圍
     */
    public function testChkIpRangeOutOfRange()
    {
        $result = StringHelper::chkIpRange('192.168.1.1~10', '192.168.1.11');
        $this->assertFalse($result, 'IP outside range should not match');
    }

    // ==================== #20 EP：getByteSize 負數/未知單位 ====================

    /**
     * EP：負數 KB → regex 不再匹配負號，回傳 0
     */
    public function testGetByteSizeNegativeKB()
    {
        $result = StringHelper::getByteSize('-5KB');
        // regex 已移除 [+-]?，負數不匹配 → 回傳 0
        $this->assertEquals(0, $result,
            '-5KB should return 0 (negative numbers no longer matched)');
    }

    /**
     * EP：TB 單位（程式碼有支援 T/TB case）
     */
    public function testGetByteSizeTB()
    {
        $result = StringHelper::getByteSize('2TB');
        $this->assertEquals(2 * pow(1024, 4), $result,
            '2TB should return 2 * 1024^4');
    }

    /**
     * EP：PB 單位（已新增支援）
     */
    public function testGetByteSizePB()
    {
        $result = StringHelper::getByteSize('5PB');
        $this->assertEquals(5 * pow(1024, 5), $result,
            '5PB should return 5 * 1024^5');
    }

    /**
     * EP：完全無法辨識的單位字串
     */
    public function testGetByteSizeUnknownUnitXYZ()
    {
        $result = StringHelper::getByteSize('100XYZ');
        // regex 無法匹配 XYZ 中任何有效部分 → unit='' → 回傳 100 as bytes
        $this->assertIsNumeric($result,
            'Unknown unit XYZ should still return numeric');
    }

    // ==================== #19 EP：chkIpRange IPv6/格式錯誤 ====================

    /**
     * EP：IPv6 loopback (::1) — 不匹配 IPv4 regex → 回傳 false
     */
    public function testChkIpRangeIpv6Loopback()
    {
        $result = StringHelper::chkIpRange('192.168.1.0~255', '::1');
        $this->assertFalse($result,
            'IPv6 address should return false (not matching IPv4 regex)');
    }

    /**
     * EP：超出有效範圍的 IPv4 (999.999.999.999) — regex 接受但值不匹配
     */
    public function testChkIpRangeInvalidOctet999()
    {
        $result = StringHelper::chkIpRange('192.168.1.1', '999.999.999.999');
        $this->assertFalse($result,
            '999.999.999.999 should not match 192.168.1.1 (values differ)');
    }

    /**
     * EP：反轉範圍 (10~1) — 程式碼有處理（交換 min/max）
     */
    public function testChkIpRangeReversedRange()
    {
        $result = StringHelper::chkIpRange('192.168.1.10~1', '192.168.1.5');
        $this->assertTrue($result,
            'Reversed range 10~1 should be auto-corrected and match IP .5');
    }

    /**
     * EP：純文字 IP 規則 → preg_replace 清除後為空字串
     */
    public function testChkIpRangeAlphaOnly()
    {
        $result = StringHelper::chkIpRange('abc', '192.168.1.1');
        $this->assertFalse($result,
            'Alpha-only range should fail regex check and return false');
    }

    // ==================== EP：genRandStr 邊界 ====================

    /**
     * BVA：genRandStr 長度 1（最小有意義值）
     */
    public function testGenRandStrLength1()
    {
        $result = StringHelper::genRandStr(1);
        $this->assertEquals(1, strlen($result), 'genRandStr(1) should return 1-char string');
    }

    /**
     * BVA：genRandStr 長度 0
     */
    public function testGenRandStrLength0()
    {
        $result = StringHelper::genRandStr(0);
        $this->assertEquals(0, strlen($result), 'genRandStr(0) should return empty string');
    }

}
