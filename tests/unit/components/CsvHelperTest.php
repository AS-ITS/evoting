<?php

namespace app\tests\unit\components;

use app\components\helper\CsvHelper;

/**
 * CsvHelper 元件測試
 * 測試 CSV 解析和匯出功能
 */
class CsvHelperTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // ==================== className() 測試 ====================

    /**
     * 測試：className() 返回完整類名
     */
    public function testClassName()
    {
        $result = CsvHelper::className();
        $this->assertEquals('app\components\helper\CsvHelper', $result);
    }

    // ==================== 建構子測試 ====================

    /**
     * 測試：預設建構子
     */
    public function testConstructorDefaults()
    {
        $csv = new CsvHelper();

        // 預設值應該都是 'auto'
        $this->assertInstanceOf(CsvHelper::class, $csv);
    }

    /**
     * 測試：帶參數建構子
     */
    public function testConstructorWithParams()
    {
        $csv = new CsvHelper(',', '"', "\r\n");

        $this->assertEquals(',', $csv->delimiter());
        $this->assertEquals('"', $csv->enclosure());
        $this->assertEquals("\r\n", $csv->linebreak());
    }

    // ==================== delimiter() 測試 ====================

    /**
     * 測試：delimiter() 設定值
     */
    public function testDelimiterSet()
    {
        $csv = new CsvHelper();

        $result = $csv->delimiter(';');
        $this->assertEquals(';', $result);
        $this->assertEquals(';', $csv->delimiter());
    }

    /**
     * 測試：delimiter() 自動檢測逗號
     */
    public function testDelimiterAutoDetectComma()
    {
        $csv = new CsvHelper('auto', '"', "\n");
        $csv->toArray('"a","b","c"', true);

        $this->assertEquals(',', $csv->delimiter());
    }

    /**
     * 測試：delimiter() 自動檢測 Tab
     */
    public function testDelimiterAutoDetectTab()
    {
        $csv = new CsvHelper('auto', '"', "\n");
        $csv->toArray("\"a\"\t\"b\"\t\"c\"", true);

        $this->assertEquals("\t", $csv->delimiter());
    }

    /**
     * 測試：delimiter() 自動檢測分號
     */
    public function testDelimiterAutoDetectSemicolon()
    {
        $csv = new CsvHelper('auto', '"', "\n");
        $csv->toArray('"a";"b";"c"', true);

        $this->assertEquals(';', $csv->delimiter());
    }

    /**
     * 測試：delimiter() 無引號時自動檢測逗號
     */
    public function testDelimiterAutoDetectCommaWithoutQuotes()
    {
        $csv = new CsvHelper('auto', 'auto', "\n");
        $csv->toArray('a,b,c', true);

        $this->assertEquals(',', $csv->delimiter());
    }

    /**
     * 測試：delimiter() 無引號時自動檢測 Tab
     */
    public function testDelimiterAutoDetectTabWithoutQuotes()
    {
        $csv = new CsvHelper('auto', 'auto', "\n");
        $csv->toArray("a\tb\tc", true);

        $this->assertEquals("\t", $csv->delimiter());
    }

    /**
     * 測試：delimiter() 無引號時自動檢測分號
     */
    public function testDelimiterAutoDetectSemicolonWithoutQuotes()
    {
        $csv = new CsvHelper('auto', 'auto', "\n");
        $csv->toArray('a;b;c', true);

        $this->assertEquals(';', $csv->delimiter());
    }

    /**
     * 測試：delimiter() 預設為逗號
     */
    public function testDelimiterDefaultsToComma()
    {
        $csv = new CsvHelper('auto', 'auto', "\n");
        $csv->toArray('abc', true);

        $this->assertEquals(',', $csv->delimiter());
    }

    // ==================== enclosure() 測試 ====================

    /**
     * 測試：enclosure() 設定值
     */
    public function testEnclosureSet()
    {
        $csv = new CsvHelper();

        $result = $csv->enclosure("'");
        $this->assertEquals("'", $result);
        $this->assertEquals("'", $csv->enclosure());
    }

    /**
     * 測試：enclosure() 自動檢測雙引號
     */
    public function testEnclosureAutoDetectDoubleQuote()
    {
        $csv = new CsvHelper(',', 'auto', "\n");
        $csv->toArray('"a","b"', true);

        $this->assertEquals('"', $csv->enclosure());
    }

    /**
     * 測試：enclosure() 自動檢測單引號
     */
    public function testEnclosureAutoDetectSingleQuote()
    {
        $csv = new CsvHelper(',', 'auto', "\n");
        $csv->toArray("'a','b'", true);

        $this->assertEquals("'", $csv->enclosure());
    }

    /**
     * 測試：enclosure() 預設為雙引號
     */
    public function testEnclosureDefaultsToDoubleQuote()
    {
        $csv = new CsvHelper(',', 'auto', "\n");
        $csv->toArray('a,b', true);

        $this->assertEquals('"', $csv->enclosure());
    }

    // ==================== linebreak() 測試 ====================

    /**
     * 測試：linebreak() 設定值
     */
    public function testLinebreakSet()
    {
        $csv = new CsvHelper();

        $result = $csv->linebreak("\n");
        $this->assertEquals("\n", $result);
        $this->assertEquals("\n", $csv->linebreak());
    }

    /**
     * 測試：linebreak() 自動檢測 CRLF
     */
    public function testLinebreakAutoDetectCrlf()
    {
        $csv = new CsvHelper(',', '"', 'auto');
        $csv->toArray("a,b\r\nc,d", true);

        $this->assertEquals("\r\n", $csv->linebreak());
    }

    /**
     * 測試：linebreak() 自動檢測 LF
     */
    public function testLinebreakAutoDetectLf()
    {
        $csv = new CsvHelper(',', '"', 'auto');
        $csv->toArray("a,b\nc,d", true);

        $this->assertEquals("\n", $csv->linebreak());
    }

    /**
     * 測試：linebreak() 自動檢測 CR
     */
    public function testLinebreakAutoDetectCr()
    {
        $csv = new CsvHelper(',', '"', 'auto');
        $csv->toArray("a,b\rc,d", true);

        $this->assertEquals("\r", $csv->linebreak());
    }

    /**
     * 測試：linebreak() 預設為 CRLF
     */
    public function testLinebreakDefaultsToCrlf()
    {
        $csv = new CsvHelper(',', '"', 'auto');
        $csv->toArray('a,b', true);

        $this->assertEquals("\r\n", $csv->linebreak());
    }

    // ==================== toArray() 測試 ====================

    /**
     * 測試：toArray() 基本解析
     */
    public function testToArrayBasic()
    {
        $csv = new CsvHelper(',', '"', "\n");
        $result = $csv->toArray('a,b,c', true);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(['a', 'b', 'c'], $result[0]);
    }

    /**
     * 測試：toArray() 多行解析
     */
    public function testToArrayMultipleRows()
    {
        $csv = new CsvHelper(',', '"', "\n");
        $result = $csv->toArray("a,b,c\n1,2,3", true);

        $this->assertCount(2, $result);
        $this->assertEquals(['a', 'b', 'c'], $result[0]);
        $this->assertEquals(['1', '2', '3'], $result[1]);
    }

    /**
     * 測試：toArray() 帶引號的欄位
     */
    public function testToArrayQuotedFields()
    {
        $csv = new CsvHelper(',', '"', "\n");
        $result = $csv->toArray('"hello","world"', true);

        $this->assertEquals(['hello', 'world'], $result[0]);
    }

    /**
     * 測試：toArray() 欄位內有分隔符號
     */
    public function testToArrayFieldsWithDelimiter()
    {
        $csv = new CsvHelper(',', '"', "\n");
        $result = $csv->toArray('"a,b",c', true);

        $this->assertEquals(['a,b', 'c'], $result[0]);
    }

    /**
     * 測試：toArray() 欄位內有換行符號
     */
    public function testToArrayFieldsWithLinebreak()
    {
        $csv = new CsvHelper(',', '"', "\n");
        $result = $csv->toArray('"a' . "\n" . 'b",c', true);

        $this->assertEquals(["a\nb", 'c'], $result[0]);
    }

    /**
     * 測試：toArray() 欄位內有雙引號（轉義）
     */
    public function testToArrayEscapedQuotes()
    {
        $csv = new CsvHelper(',', '"', "\n");
        $result = $csv->toArray('"say ""hello""",world', true);

        $this->assertEquals(['say "hello"', 'world'], $result[0]);
    }

    /**
     * 測試：toArray() CRLF 換行
     */
    public function testToArrayCrlf()
    {
        $csv = new CsvHelper(',', '"', "\r\n");
        $result = $csv->toArray("a,b\r\nc,d", true);

        $this->assertCount(2, $result);
        $this->assertEquals(['a', 'b'], $result[0]);
        $this->assertEquals(['c', 'd'], $result[1]);
    }

    /**
     * 測試：toArray() Tab 分隔
     */
    public function testToArrayTabDelimited()
    {
        $csv = new CsvHelper("\t", '"', "\n");
        $result = $csv->toArray("a\tb\tc", true);

        $this->assertEquals(['a', 'b', 'c'], $result[0]);
    }

    /**
     * 測試：toArray() 分號分隔
     */
    public function testToArraySemicolonDelimited()
    {
        $csv = new CsvHelper(';', '"', "\n");
        $result = $csv->toArray('a;b;c', true);

        $this->assertEquals(['a', 'b', 'c'], $result[0]);
    }

    /**
     * 測試：toArray() 空 CSV
     */
    public function testToArrayEmpty()
    {
        $csv = new CsvHelper(',', '"', "\n");
        $result = $csv->toArray('', true);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([''], $result[0]);
    }

    // ==================== fromArray() 測試 ====================

    /**
     * 測試：fromArray() 基本匯出
     */
    public function testFromArrayBasic()
    {
        $csv = new CsvHelper(',', '"', "\r\n");
        $result = $csv->fromArray([['a', 'b', 'c']]);

        $this->assertEquals('a,b,c', $result);
    }

    /**
     * 測試：fromArray() 多行匯出
     */
    public function testFromArrayMultipleRows()
    {
        $csv = new CsvHelper(',', '"', "\r\n");
        $result = $csv->fromArray([
            ['a', 'b', 'c'],
            ['1', '2', '3'],
        ]);

        $this->assertEquals("a,b,c\r\n1,2,3", $result);
    }

    /**
     * 測試：fromArray() 欄位包含分隔符號
     */
    public function testFromArrayFieldWithDelimiter()
    {
        $csv = new CsvHelper(',', '"', "\r\n");
        $result = $csv->fromArray([['a,b', 'c']]);

        $this->assertEquals('"a,b",c', $result);
    }

    /**
     * 測試：fromArray() 欄位包含引號
     */
    public function testFromArrayFieldWithQuote()
    {
        $csv = new CsvHelper(',', '"', "\r\n");
        $result = $csv->fromArray([['say "hello"', 'world']]);

        $this->assertEquals('"say ""hello""",world', $result);
    }

    /**
     * 測試：fromArray() 欄位包含換行符號
     */
    public function testFromArrayFieldWithLinebreak()
    {
        $csv = new CsvHelper(',', '"', "\r\n");
        $result = $csv->fromArray([["a\r\nb", 'c']]);

        $this->assertEquals("\"a\r\nb\",c", $result);
    }

    /**
     * 測試：fromArray() 非陣列輸入觸發錯誤
     */
    public function testFromArrayNonArrayTriggersError()
    {
        $csv = new CsvHelper(',', '"', "\r\n");

        $warningTriggered = false;
        set_error_handler(function () use (&$warningTriggered) {
            $warningTriggered = true;
            return true;
        }, E_USER_WARNING);

        $result = $csv->fromArray('not an array');

        restore_error_handler();
        $this->assertTrue($warningTriggered, 'Expected a user warning to be triggered');
        $this->assertFalse($result, 'Should return false for non-array input');
    }

    /**
     * 測試：fromArray() 使用 Tab 分隔
     */
    public function testFromArrayTabDelimited()
    {
        $csv = new CsvHelper("\t", '"', "\r\n");
        $result = $csv->fromArray([['a', 'b', 'c']]);

        $this->assertEquals("a\tb\tc", $result);
    }

    /**
     * 測試：fromArray() 使用分號分隔
     */
    public function testFromArraySemicolonDelimited()
    {
        $csv = new CsvHelper(';', '"', "\r\n");
        $result = $csv->fromArray([['a', 'b', 'c']]);

        $this->assertEquals('a;b;c', $result);
    }

    /**
     * 測試：fromArray() 使用單引號
     */
    public function testFromArraySingleQuote()
    {
        $csv = new CsvHelper(',', "'", "\r\n");
        $result = $csv->fromArray([["a,b", 'c']]);

        $this->assertEquals("'a,b',c", $result);
    }

    /**
     * 測試：fromArray() 使用 LF 換行
     */
    public function testFromArrayLfLinebreak()
    {
        $csv = new CsvHelper(',', '"', "\n");
        $result = $csv->fromArray([
            ['a', 'b'],
            ['c', 'd'],
        ]);

        $this->assertEquals("a,b\nc,d", $result);
    }

    // ==================== import() 靜態方法測試 ====================

    /**
     * 測試：import() 靜態方法
     */
    public function testImportStatic()
    {
        $result = CsvHelper::import('a,b,c', true);

        $this->assertIsArray($result);
        $this->assertEquals(['a', 'b', 'c'], $result[0]);
    }

    /**
     * 測試：import() 帶自訂分隔符號
     */
    public function testImportStaticWithCustomDelimiter()
    {
        $result = CsvHelper::import('a;b;c', true, ';');

        $this->assertIsArray($result);
        $this->assertEquals(['a', 'b', 'c'], $result[0]);
    }

    // ==================== export() 靜態方法測試 ====================

    /**
     * 測試：export() 靜態方法
     */
    public function testExportStatic()
    {
        $result = CsvHelper::export([['a', 'b', 'c']]);

        $this->assertEquals('a,b,c', $result);
    }

    /**
     * 測試：export() 帶自訂分隔符號
     */
    public function testExportStaticWithCustomDelimiter()
    {
        $result = CsvHelper::export([['a', 'b', 'c']], ';');

        $this->assertEquals('a;b;c', $result);
    }

    /**
     * 測試：export() 帶自訂引號
     */
    public function testExportStaticWithCustomEnclosure()
    {
        $result = CsvHelper::export([['a,b', 'c']], ',', "'");

        $this->assertEquals("'a,b',c", $result);
    }

    /**
     * 測試：export() 帶自訂換行符號
     */
    public function testExportStaticWithCustomLinebreak()
    {
        $result = CsvHelper::export([['a', 'b'], ['c', 'd']], ',', '"', "\n");

        $this->assertEquals("a,b\nc,d", $result);
    }

    // ==================== 整合測試 ====================

    /**
     * 測試：import 和 export 互逆
     */
    public function testImportExportRoundTrip()
    {
        $original = [
            ['Name', 'Age', 'City'],
            ['Alice', '30', 'New York'],
            ['Bob', '25', 'Los Angeles'],
        ];

        $exported = CsvHelper::export($original);
        $imported = CsvHelper::import($exported, true);

        $this->assertEquals($original, $imported);
    }

    /**
     * 測試：複雜內容 roundtrip
     */
    public function testComplexContentRoundTrip()
    {
        $original = [
            ['Name', 'Description'],
            ['Product A', 'A product with "quotes"'],
            ['Product B', 'Has, commas'],
        ];

        $exported = CsvHelper::export($original);
        $imported = CsvHelper::import($exported, true);

        $this->assertEquals($original, $imported);
    }

    /**
     * 測試：中文內容
     */
    public function testChineseContent()
    {
        $csv = new CsvHelper(',', '"', "\n");
        $data = [
            ['姓名', '年齡'],
            ['張三', '25'],
            ['李四', '30'],
        ];

        $exported = $csv->fromArray($data);
        $imported = $csv->toArray($exported, true);

        $this->assertEquals($data, $imported);
    }
}
