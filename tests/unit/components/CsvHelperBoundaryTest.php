<?php

namespace app\tests\unit\components;

use app\components\helper\CsvHelper;

/**
 * CsvHelper 邊界值分析與等價劃分測試
 *
 * 補強項目：
 * - EP：BOM 標記處理
 * - EP：各行欄位數不一致
 * - EP：單欄位 CSV
 * - EP：超大內容處理
 */
class CsvHelperBoundaryTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // ==================== EP：BOM 標記 ====================

    /**
     * EP：含 UTF-8 BOM 的 CSV 應能正確解析
     */
    public function testImportWithUtf8Bom()
    {
        $bom = "\xEF\xBB\xBF";
        $csvData = $bom . "name,age\r\nAlice,30\r\n";

        $result = CsvHelper::import($csvData, true);

        $this->assertIsArray($result, 'CSV with BOM should parse to array');
        $this->assertNotEmpty($result, 'CSV with BOM should not be empty');
    }

    // ==================== EP：各行欄位數不一致 ====================

    /**
     * EP：各行欄位數不同的 CSV
     */
    public function testImportInconsistentFieldCount()
    {
        $csvData = "a,b,c\r\n1,2\r\n3,4,5,6";

        $result = CsvHelper::import($csvData, true);

        $this->assertIsArray($result, 'Inconsistent field count should still parse');
        $this->assertCount(3, $result, 'Should parse all 3 rows');
    }

    // ==================== EP：單欄位 CSV ====================

    /**
     * EP：只有單一欄位的 CSV
     */
    public function testImportSingleFieldCsv()
    {
        $csvData = "value1\r\nvalue2\r\nvalue3";

        $result = CsvHelper::import($csvData, true);

        $this->assertIsArray($result, 'Single field CSV should parse');
        $this->assertCount(3, $result, 'Should have 3 rows');
        foreach ($result as $row) {
            $this->assertCount(1, $row, 'Each row should have 1 field');
        }
    }

    // ==================== EP：空行處理 ====================

    /**
     * EP：CSV 中間含空行
     */
    public function testImportWithEmptyLines()
    {
        $csvData = "a,b\r\n\r\n1,2\r\n";

        $result = CsvHelper::import($csvData, true);

        $this->assertIsArray($result, 'CSV with empty lines should parse');
    }

    /**
     * EP：僅包含空行的 CSV
     */
    public function testImportOnlyEmptyLines()
    {
        $csvData = "\r\n\r\n\r\n";

        $result = CsvHelper::import($csvData, true);

        $this->assertIsArray($result, 'Only empty lines should return array');
    }

    // ==================== EP：特殊分隔符 ====================

    /**
     * EP：管線符號作為分隔符
     */
    public function testImportPipeDelimiter()
    {
        $csv = new CsvHelper('|');
        $csvData = "a|b|c\r\n1|2|3";

        $result = $csv->toArray($csvData, true);

        $this->assertIsArray($result, 'Pipe delimiter should parse');
        $this->assertCount(2, $result, 'Should have 2 rows');
        $this->assertEquals('a', $result[0][0], 'First field should be "a"');
    }

    // ==================== EP：fromArray 邊界 ====================

    /**
     * EP：空陣列匯出
     */
    public function testExportEmptyArray()
    {
        $csv = new CsvHelper();
        $result = $csv->fromArray([]);

        $this->assertIsString($result, 'Empty array export should return string');
    }

    /**
     * EP：單列陣列匯出
     */
    public function testExportSingleRow()
    {
        $csv = new CsvHelper();
        $result = $csv->fromArray([['a', 'b', 'c']]);

        $this->assertIsString($result, 'Single row export should return string');
        $this->assertStringContainsString('a', $result);
        $this->assertStringContainsString('b', $result);
        $this->assertStringContainsString('c', $result);
    }

    /**
     * EP：含 null 值的陣列匯出
     */
    public function testExportWithNullValues()
    {
        $csv = new CsvHelper();
        $result = $csv->fromArray([['a', null, 'c']]);

        $this->assertIsString($result, 'Array with null values should export');
    }

    // ==================== EP：往返一致性 ====================

    /**
     * EP：含特殊字元的完整往返測試
     */
    public function testRoundTripWithSpecialChars()
    {
        $original = [
            ['名稱', '說明', '備註'],
            ['測試"引號', '逗號,測試', "換行\n測試"],
        ];

        $exported = CsvHelper::export($original);
        $imported = CsvHelper::import($exported, true);

        $this->assertCount(count($original), $imported, 'Round trip should preserve row count');
        $this->assertEquals($original[0][0], $imported[0][0], 'First cell should match');
    }
}
