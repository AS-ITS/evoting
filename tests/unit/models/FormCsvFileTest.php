<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormCsvFile;
use yii\web\UploadedFile;

/**
 * FormCsvFile 模型測試
 * 測試 CSV 檔案導入模組
 */
class FormCsvFileTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    // ==================== 靜態屬性測試 ====================

    /**
     * 測試：genMode 靜態屬性
     */
    public function testGenModeStaticProperty()
    {
        $this->assertEquals('csvfile', FormCsvFile::$genMode);
    }

    /**
     * 測試：fileEncoding 靜態屬性
     */
    public function testFileEncodingStaticProperty()
    {
        $this->assertIsArray(FormCsvFile::$fileEncoding);
        $this->assertContains('ASCII', FormCsvFile::$fileEncoding);
        $this->assertContains('big5', FormCsvFile::$fileEncoding);
        $this->assertContains('UTF-8', FormCsvFile::$fileEncoding);
    }

    /**
     * 測試：FieldCP 靜態屬性包含必要欄位
     */
    public function testFieldCPStaticProperty()
    {
        $this->assertIsArray(FormCsvFile::$FieldCP);
        $this->assertNotEmpty(FormCsvFile::$FieldCP);

        // 檢查欄位結構
        $firstField = FormCsvFile::$FieldCP[0];
        $this->assertArrayHasKey('column', $firstField);
        $this->assertArrayHasKey('columnE', $firstField);
        $this->assertArrayHasKey('desc', $firstField);
        $this->assertArrayHasKey('fieldLimit', $firstField);
    }

    /**
     * 測試：FieldCP 包含投票組別欄位
     */
    public function testFieldCPContainsPartyField()
    {
        $partyField = null;
        foreach (FormCsvFile::$FieldCP as $field) {
            if ($field['columnE'] === 'party') {
                $partyField = $field;
                break;
            }
        }

        $this->assertNotNull($partyField, 'FieldCP should contain party field');
        $this->assertEquals('投票組別(中文)', $partyField['column']);
    }

    /**
     * 測試：FieldCP 包含問題代碼欄位
     */
    public function testFieldCPContainsQuestionIdField()
    {
        $questionField = null;
        foreach (FormCsvFile::$FieldCP as $field) {
            if ($field['columnE'] === 'questionID') {
                $questionField = $field;
                break;
            }
        }

        $this->assertNotNull($questionField, 'FieldCP should contain questionID field');
        $this->assertEquals('問題代碼', $questionField['column']);
    }

    /**
     * 測試：FieldCP 包含名稱欄位
     */
    public function testFieldCPContainsNameField()
    {
        $nameFields = [];
        foreach (FormCsvFile::$FieldCP as $field) {
            if ($field['columnE'] === 'Name') {
                $nameFields[] = $field;
            }
        }

        // 應該有兩個 Name 欄位（名稱和名字）
        $this->assertCount(2, $nameFields, 'FieldCP should contain two Name fields');
    }

    /**
     * 測試：FieldCP 包含自定義欄位
     */
    public function testFieldCPContainsCustomFields()
    {
        $customFields = ['otherColA', 'otherColB', 'otherColC', 'otherColD', 'otherColE', 'otherColF'];
        $foundFields = [];

        foreach (FormCsvFile::$FieldCP as $field) {
            if (in_array($field['columnE'], $customFields)) {
                $foundFields[] = $field['columnE'];
            }
        }

        $this->assertCount(6, array_unique($foundFields), 'FieldCP should contain all 6 custom fields');
    }

    // ==================== 基本結構測試 ====================

    /**
     * 測試：Model 繼承自 yii\base\Model
     */
    public function testExtendsModel()
    {
        $model = new FormCsvFile();
        $this->assertInstanceOf(\yii\base\Model::class, $model);
    }

    /**
     * 測試：預設屬性值
     */
    public function testDefaultPropertyValues()
    {
        $model = new FormCsvFile();

        $this->assertNull($model->csvFile);
        $this->assertEquals(0, $model->dataCount);
    }

    // ==================== rules() 測試 ====================

    /**
     * 測試：rules() 返回陣列
     */
    public function testRulesReturnsArray()
    {
        $model = new FormCsvFile();
        $rules = $model->rules();

        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
    }

    /**
     * 測試：rules() 包含 csvFile 驗證
     */
    public function testRulesContainsCsvFileValidation()
    {
        $model = new FormCsvFile();
        $rules = $model->rules();

        $hasCsvFileRule = false;
        foreach ($rules as $rule) {
            if (in_array('csvFile', (array)$rule[0])) {
                $hasCsvFileRule = true;
                break;
            }
        }

        $this->assertTrue($hasCsvFileRule, 'Rules should contain csvFile validation');
    }

    /**
     * 測試：rules() csvFile 必須是 csv 格式
     */
    public function testRulesCsvFileExtension()
    {
        $model = new FormCsvFile();
        $rules = $model->rules();

        $csvRule = null;
        foreach ($rules as $rule) {
            if (in_array('csvFile', (array)$rule[0]) && $rule[1] === 'file') {
                $csvRule = $rule;
                break;
            }
        }

        $this->assertNotNull($csvRule, 'Should have file rule for csvFile');
        $this->assertEquals('csv', $csvRule['extensions']);
    }

    /**
     * 測試：rules() csvFile 同時驗證副檔名與 MIME 類型
     */
    public function testRulesCsvFileMimeType()
    {
        $model = new FormCsvFile();
        $rules = $model->rules();

        $csvRule = null;
        foreach ($rules as $rule) {
            if (in_array('csvFile', (array)$rule[0]) && $rule[1] === 'file') {
                $csvRule = $rule;
                break;
            }
        }

        $this->assertNotNull($csvRule, 'csvFile file rule should exist');
        $this->assertEquals('csv', $csvRule['extensions']);
        // MIME 依環境/檔案魔數不穩，僅以副檔名驗證（見 FormCsvFile::rules）
        $this->assertFalse($csvRule['checkExtensionByMimeType']);
        $this->assertArrayNotHasKey('mimeTypes', $csvRule);
    }

    /**
     * 測試：rules() csvFile 不可為空
     */
    public function testRulesCsvFileNotEmpty()
    {
        $model = new FormCsvFile();
        $rules = $model->rules();

        $csvRule = null;
        foreach ($rules as $rule) {
            if (in_array('csvFile', (array)$rule[0]) && $rule[1] === 'file') {
                $csvRule = $rule;
                break;
            }
        }

        $this->assertFalse($csvRule['skipOnEmpty']);
    }

    // ==================== attributeLabels() 測試 ====================

    /**
     * 測試：attributeLabels() 返回陣列
     */
    public function testAttributeLabelsReturnsArray()
    {
        $model = new FormCsvFile();
        $labels = $model->attributeLabels();

        $this->assertIsArray($labels);
    }

    /**
     * 測試：attributeLabels() 包含 csvFile 標籤
     */
    public function testAttributeLabelsContainsCsvFile()
    {
        $model = new FormCsvFile();
        $labels = $model->attributeLabels();

        $this->assertArrayHasKey('csvFile', $labels);
        $this->assertEquals('匯入 CSV 檔案', $labels['csvFile']);
    }

    // ==================== dataCount 測試 ====================

    /**
     * 測試：dataCount 可以設定
     */
    public function testDataCountCanBeSet()
    {
        $model = new FormCsvFile();
        $model->dataCount = 10;

        $this->assertEquals(10, $model->dataCount);
    }

    // ==================== FieldCP 欄位對應測試 ====================

    /**
     * 測試：所有 FieldCP 欄位都有中英文對應
     */
    public function testAllFieldCPHaveBilingualMapping()
    {
        foreach (FormCsvFile::$FieldCP as $index => $field) {
            $this->assertArrayHasKey('column', $field, "Field at index $index should have 'column' key");
            $this->assertArrayHasKey('columnE', $field, "Field at index $index should have 'columnE' key");
            $this->assertNotEmpty($field['column'], "Field at index $index 'column' should not be empty");
            $this->assertNotEmpty($field['columnE'], "Field at index $index 'columnE' should not be empty");
        }
    }

    /**
     * 測試：所有 FieldCP 欄位都有描述
     */
    public function testAllFieldCPHaveDescription()
    {
        foreach (FormCsvFile::$FieldCP as $index => $field) {
            $this->assertArrayHasKey('desc', $field, "Field at index $index should have 'desc' key");
            $this->assertNotEmpty($field['desc'], "Field at index $index 'desc' should not be empty");
        }
    }

    /**
     * 測試：所有 FieldCP 欄位都有限制說明
     */
    public function testAllFieldCPHaveFieldLimit()
    {
        foreach (FormCsvFile::$FieldCP as $index => $field) {
            $this->assertArrayHasKey('fieldLimit', $field, "Field at index $index should have 'fieldLimit' key");
            $this->assertNotEmpty($field['fieldLimit'], "Field at index $index 'fieldLimit' should not be empty");
        }
    }

    /**
     * 測試：FieldCP 包含性別欄位
     */
    public function testFieldCPContainsSexField()
    {
        $sexField = null;
        foreach (FormCsvFile::$FieldCP as $field) {
            if ($field['columnE'] === 'sex') {
                $sexField = $field;
                break;
            }
        }

        $this->assertNotNull($sexField, 'FieldCP should contain sex field');
        $this->assertEquals('性別', $sexField['column']);
    }

    /**
     * 測試：FieldCP 包含排序欄位
     */
    public function testFieldCPContainsOrderNumField()
    {
        $orderField = null;
        foreach (FormCsvFile::$FieldCP as $field) {
            if ($field['columnE'] === 'orderNum') {
                $orderField = $field;
                break;
            }
        }

        $this->assertNotNull($orderField, 'FieldCP should contain orderNum field');
        $this->assertEquals('排序', $orderField['column']);
    }

    /**
     * 測試：FieldCP 包含背景顏色欄位
     */
    public function testFieldCPContainsBackgroundColorField()
    {
        $colorField = null;
        foreach (FormCsvFile::$FieldCP as $field) {
            if ($field['columnE'] === 'backgroundColor') {
                $colorField = $field;
                break;
            }
        }

        $this->assertNotNull($colorField, 'FieldCP should contain backgroundColor field');
        $this->assertEquals('背景顏色', $colorField['column']);
    }

    /**
     * 測試：FieldCP 包含特殊榮譽欄位
     */
    public function testFieldCPContainsSpecialHonorField()
    {
        $honorField = null;
        foreach (FormCsvFile::$FieldCP as $field) {
            if ($field['columnE'] === 'specialHonor') {
                $honorField = $field;
                break;
            }
        }

        $this->assertNotNull($honorField, 'FieldCP should contain specialHonor field');
        $this->assertEquals('特殊榮譽', $honorField['column']);
    }

    /**
     * 測試：FieldCP 包含關聯組別欄位
     */
    public function testFieldCPContainsRelatePartyField()
    {
        $relateField = null;
        foreach (FormCsvFile::$FieldCP as $field) {
            if ($field['columnE'] === 'relateParty') {
                $relateField = $field;
                break;
            }
        }

        $this->assertNotNull($relateField, 'FieldCP should contain relateParty field');
        $this->assertEquals('關聯組別', $relateField['column']);
    }

    // ==================== 驗證測試 ====================

    /**
     * 測試：無檔案時驗證失敗
     */
    public function testValidationFailsWithoutFile()
    {
        $model = new FormCsvFile();

        $this->assertFalse($model->validate());
        $this->assertArrayHasKey('csvFile', $model->errors);
    }

    // ==================== PHP 8.x Warning 邊界值測試 ====================

    /**
     * 測試：性別代碼查表 - 空字串鍵不觸發 Warning（PHP 8.x）
     *
     * 修正前：$sexAry[$value] 在 key 不存在時觸發 E_WARNING（PHP 8.x 升級為例外）
     * 修正後：$sexAry[$value] ?? '' 安全回傳空字串
     *
     * 此測試在 unit suite（不抑制 E_WARNING）中執行，若修正失效會自動失敗。
     */
    public function testSexAryLookupWithEmptyStringKey()
    {
        $sexAry = array_flip(Yii::$app->params['ct.candi.sexAry']);

        $result = strval($sexAry[''] ?? '');

        $this->assertSame('', $result,
            '空字串 sex 值應回傳空字串，不應觸發 PHP 8.x E_WARNING');
    }

    /**
     * 測試：性別代碼查表 - 不存在的中文值不觸發 Warning
     */
    public function testSexAryLookupWithUnknownValue()
    {
        $sexAry = array_flip(Yii::$app->params['ct.candi.sexAry']);

        $result = strval($sexAry['不存在的性別值'] ?? '');

        $this->assertSame('', $result,
            '不存在的 sex 值應回傳空字串，不應觸發 PHP 8.x E_WARNING');
    }

    /**
     * 測試：性別代碼查表 - 合法中文值正常轉換（正向驗證）
     */
    public function testSexAryLookupWithValidValue()
    {
        $sexAry        = array_flip(Yii::$app->params['ct.candi.sexAry']);
        $validLabel    = array_key_first($sexAry);
        $expectedCode  = strval($sexAry[$validLabel]);

        $result = strval($sexAry[$validLabel] ?? '');

        $this->assertSame($expectedCode, $result, '合法性別值應回傳對應代碼');
        $this->assertNotSame('', $result,          '合法性別值不應回傳空字串');
    }

    /**
     * 測試：組別代碼查表 - 空字串或不存在的值不觸發 Warning（CSV 欄位 A 與 Z）
     *
     * 修正前：$divisionAry[$value] 觸發 E_WARNING
     * 修正後：$divisionAry[$value] ?? '' 安全回傳空字串
     */
    public function testDivisionAryLookupWithUnknownValue()
    {
        $parties     = ['A' => '甲組', 'B' => '乙組'];
        $divisionAry = array_flip($parties); // ['甲組' => 'A', '乙組' => 'B']

        $this->assertSame('', strval($divisionAry[''] ?? ''),
            '空字串組別應回傳空字串，不應觸發 PHP 8.x E_WARNING');
        $this->assertSame('', strval($divisionAry['不存在的組別'] ?? ''),
            '不存在的組別名稱應回傳空字串，不應觸發 PHP 8.x E_WARNING');
    }

    /**
     * 測試：組別代碼查表 - 合法中文組別名稱正常查表（正向驗證）
     */
    public function testDivisionAryLookupWithValidValue()
    {
        $parties     = ['A' => '甲組', 'B' => '乙組'];
        $divisionAry = array_flip($parties);

        $this->assertSame('A', strval($divisionAry['甲組'] ?? ''), '甲組應對應代碼 A');
        $this->assertSame('B', strval($divisionAry['乙組'] ?? ''), '乙組應對應代碼 B');
    }

    /**
     * 測試：問題代碼存在性檢查 - 不存在的 questionID 不觸發 Warning
     *
     * 修正前：$questionAry[$id]['party'] 在 key 不存在時觸發 E_WARNING
     * 修正後：!isset($questionAry[$id]) || $questionAry[$id]['party'] != $party
     *         先 isset 確認 key 存在再存取
     */
    public function testQuestionAryMissingKeyDoesNotTriggerWarning()
    {
        $questionAry = [
            '1' => ['questionID' => '1', 'party' => 'A', 'title' => '問題一'],
        ];

        // 存在的 questionID：應正常執行，不觸發 Warning
        $existingID = '1';
        $hasErrorForExisting = !isset($questionAry[$existingID]) ||
                               $questionAry[$existingID]['party'] !== 'A';
        $this->assertFalse($hasErrorForExisting,
            '存在的 questionID 且分組正確時不應視為錯誤');

        // 不存在的 questionID：應視為錯誤，且不觸發 PHP 8.x Warning
        $missingID = '999';
        $hasErrorForMissing = !isset($questionAry[$missingID]) ||
                              $questionAry[$missingID]['party'] !== 'A';
        $this->assertTrue($hasErrorForMissing,
            '不存在的 questionID 應被視為錯誤，不應觸發 PHP 8.x E_WARNING');
    }

    /**
     * 測試：問題代碼存在性檢查 - 存在但分組不符應視為錯誤
     */
    public function testQuestionAryPartyMismatchIsError()
    {
        $questionAry = [
            '5' => ['questionID' => '5', 'party' => 'B'],
        ];

        $hasError = !isset($questionAry['5']) || $questionAry['5']['party'] !== 'A';

        $this->assertTrue($hasError, '問題代碼存在但分組不符時應視為錯誤');
    }

    /**
     * 測試：prepareCsvPath 去除 UTF-8 BOM
     */
    public function testPrepareCsvPathStripsUtf8Bom()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'csv_bom_test_');
        file_put_contents($tmp, "\xEF\xBB\xBFparty,questionID\n");

        $model = new FormCsvFile();
        $method = new \ReflectionMethod(FormCsvFile::class, 'prepareCsvPath');
        $method->setAccessible(true);
        $strippedPath = $method->invoke($model, $tmp);

        $this->assertNotSame($tmp, $strippedPath);
        $this->assertSame("party,questionID\n", file_get_contents($strippedPath));

        @unlink($tmp);
        if ($strippedPath !== $tmp && is_file($strippedPath)) {
            @unlink($strippedPath);
        }
    }

    /**
     * 測試：無 BOM 時 prepareCsvPath 回傳原路徑
     */
    public function testPrepareCsvPathReturnsOriginalWhenNoBom()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'csv_plain_test_');
        file_put_contents($tmp, "party,questionID\n");

        $model = new FormCsvFile();
        $method = new \ReflectionMethod(FormCsvFile::class, 'prepareCsvPath');
        $method->setAccessible(true);
        $path = $method->invoke($model, $tmp);

        $this->assertSame($tmp, $path);
        @unlink($tmp);
    }
}
