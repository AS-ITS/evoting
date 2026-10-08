<?php

namespace app\tests\unit\components;

use Yii;
use app\components\helper\ArrayHelper;
use app\tests\fixtures\ConfigFixture;

/**
 * ArrayHelper 元件測試
 * 測試陣列輔助類別的各項功能
 */
class ArrayHelperTest extends \Codeception\Test\Unit
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

    // ==================== forget() 測試 ====================

    /**
     * 測試：forget() 移除單一鍵值
     */
    public function testForgetSingleKey()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $result = ArrayHelper::forget($array, 'b');

        $this->assertArrayNotHasKey('b', $result);
        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('c', $result);
    }

    /**
     * 測試：forget() 移除多個鍵值
     */
    public function testForgetMultipleKeys()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $result = ArrayHelper::forget($array, ['b', 'd']);

        $this->assertArrayNotHasKey('b', $result);
        $this->assertArrayNotHasKey('d', $result);
        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('c', $result);
    }

    /**
     * 測試：forget() 移除不存在的鍵值
     */
    public function testForgetNonExistentKey()
    {
        $array = ['a' => 1, 'b' => 2];
        $result = ArrayHelper::forget($array, 'z');

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('b', $result);
    }

    /**
     * 測試：forget() 空鍵值陣列
     */
    public function testForgetEmptyKeys()
    {
        $array = ['a' => 1, 'b' => 2];
        $result = ArrayHelper::forget($array, []);

        $this->assertNull($result);
    }

    // ==================== only() 測試 ====================

    /**
     * 測試：only() 只保留指定鍵值
     */
    public function testOnlySingleKey()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $result = ArrayHelper::only($array, 'b');

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('b', $result);
        $this->assertEquals(2, $result['b']);
    }

    /**
     * 測試：only() 保留多個鍵值
     */
    public function testOnlyMultipleKeys()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $result = ArrayHelper::only($array, ['a', 'c']);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('c', $result);
        $this->assertArrayNotHasKey('b', $result);
        $this->assertArrayNotHasKey('d', $result);
    }

    /**
     * 測試：only() 保留不存在的鍵值
     */
    public function testOnlyNonExistentKeys()
    {
        $array = ['a' => 1, 'b' => 2];
        $result = ArrayHelper::only($array, ['z']);

        $this->assertEmpty($result);
    }

    // ==================== getAttributesMigration() 測試 ====================

    /**
     * 測試：getAttributesMigration() 偵測變更
     */
    public function testGetAttributesMigrationWithChanges()
    {
        $newAttributes = ['name' => 'John', 'age' => 30, 'city' => 'New York'];
        $oldAttributes = ['name' => 'John', 'age' => 25, 'city' => 'Boston'];

        $result = ArrayHelper::getAttributesMigration($newAttributes, $oldAttributes);

        $this->assertArrayHasKey('age', $result);
        $this->assertEquals(25, $result['age']['before']);
        $this->assertEquals(30, $result['age']['after']);
        $this->assertArrayHasKey('city', $result);
        $this->assertEquals('Boston', $result['city']['before']);
        $this->assertEquals('New York', $result['city']['after']);
    }

    /**
     * 測試：getAttributesMigration() 無變更
     */
    public function testGetAttributesMigrationNoChanges()
    {
        $attributes = ['name' => 'John', 'age' => 25];

        $result = ArrayHelper::getAttributesMigration($attributes, $attributes);

        $this->assertEmpty($result);
    }

    // ==================== addSpace() 測試 ====================

    /**
     * 測試：addSpace() 加入全形空白
     */
    public function testAddSpace()
    {
        $array = ['a' => '測試', 'b' => 'Test'];
        $result = ArrayHelper::addSpace($array);

        $this->assertEquals('測試　', $result['a']);
        $this->assertEquals('Test　', $result['b']);
    }

    /**
     * 測試：addSpace() 空陣列
     */
    public function testAddSpaceEmptyArray()
    {
        $array = [];
        $result = ArrayHelper::addSpace($array);

        $this->assertEmpty($result);
    }

    // ==================== insertAfterKey() 測試 ====================

    /**
     * 測試：insertAfterKey() 在指定鍵後插入
     */
    public function testInsertAfterKey()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $result = ArrayHelper::insertAfterKey($array, 'b', 'new_value');

        $values = array_values($result);
        $this->assertEquals([1, 2, 'new_value', 3], $values);
    }

    /**
     * 測試：insertAfterKey() 鍵不存在時返回原陣列
     */
    public function testInsertAfterKeyNonExistent()
    {
        $array = ['a' => 1, 'b' => 2];
        $result = ArrayHelper::insertAfterKey($array, 'z', 'new_value');

        $this->assertEquals($array, $result);
    }

    // ==================== isJson() 測試 ====================

    /**
     * 測試：isJson() 有效 JSON
     */
    public function testIsJsonValid()
    {
        $this->assertTrue(ArrayHelper::isJson('{"name":"John","age":30}'));
        $this->assertTrue(ArrayHelper::isJson('[1,2,3]'));
        $this->assertTrue(ArrayHelper::isJson('[]'));
        $this->assertTrue(ArrayHelper::isJson('{}'));
    }

    /**
     * 測試：isJson() 無效 JSON
     */
    public function testIsJsonInvalid()
    {
        $this->assertFalse(ArrayHelper::isJson('not json'));
        $this->assertFalse(ArrayHelper::isJson('{invalid}'));
        $this->assertFalse(ArrayHelper::isJson(''));
        $this->assertFalse(ArrayHelper::isJson(null));
        $this->assertFalse(ArrayHelper::isJson(123));
    }

    // ==================== arrayFirst() 測試 ====================

    /**
     * 測試：arrayFirst() 取得第一個元素
     */
    public function testArrayFirst()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $result = ArrayHelper::arrayFirst($array);
        $this->assertEquals(['key' => 'a', 'value' => 1], $result);

        $key = ArrayHelper::arrayFirst($array, 'key');
        $this->assertEquals('a', $key);

        $value = ArrayHelper::arrayFirst($array, 'value');
        $this->assertEquals(1, $value);
    }

    /**
     * 測試：arrayFirst() 空陣列
     */
    public function testArrayFirstEmpty()
    {
        $array = [];
        $result = ArrayHelper::arrayFirst($array);

        $this->assertEquals([], $result);
    }

    // ==================== arrayKShift() 測試 ====================

    /**
     * 測試：arrayKShift() 移出第一個元素
     */
    public function testArrayKShift()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $result = ArrayHelper::arrayKShift($array);

        $this->assertEquals(['a' => 1], $result);
        $this->assertArrayNotHasKey('a', $array);
        $this->assertCount(2, $array);
    }

    // ==================== removeByValue() 測試 ====================

    /**
     * 測試：removeByValue() 移除指定值
     */
    public function testRemoveByValue()
    {
        $array = ['a', 'b', 'c', 'd'];
        $result = ArrayHelper::removeByValue($array, 'c');

        $this->assertNotContains('c', $result);
        $this->assertContains('a', $result);
        $this->assertContains('b', $result);
        $this->assertContains('d', $result);
    }

    /**
     * 測試：removeByValue() 移除不存在的值
     */
    public function testRemoveByValueNonExistent()
    {
        $array = ['a', 'b', 'c'];
        $result = ArrayHelper::removeByValue($array, 'z');

        $this->assertCount(3, $result);
    }

    // ==================== removeByValues() 測試 ====================

    /**
     * 測試：removeByValues() 移除多個值
     */
    public function testRemoveByValues()
    {
        $array = ['a', 'b', 'c', 'd', 'e'];
        $result = ArrayHelper::removeByValues($array, ['b', 'd']);

        $this->assertNotContains('b', $result);
        $this->assertNotContains('d', $result);
        $this->assertContains('a', $result);
        $this->assertContains('c', $result);
        $this->assertContains('e', $result);
    }

    // ==================== addToAryBegin() 測試 ====================

    /**
     * 測試：addToAryBegin() 預設加入空白選項
     */
    public function testAddToAryBeginDefault()
    {
        $array = ['a' => '選項A', 'b' => '選項B'];
        $result = ArrayHelper::addToAryBegin($array);

        $keys = array_keys($result);
        $this->assertEquals('', $keys[0]);
        $this->assertCount(3, $result);
    }

    /**
     * 測試：addToAryBegin() 自訂加入的陣列
     */
    public function testAddToAryBeginCustom()
    {
        $array = ['a' => '選項A', 'b' => '選項B'];
        $result = ArrayHelper::addToAryBegin($array, ['all' => '全部']);

        $keys = array_keys($result);
        $this->assertEquals('all', $keys[0]);
        $this->assertEquals('全部', $result['all']);
    }

    // ==================== strtr() 測試 ====================

    /**
     * 測試：strtr() 字串替換
     */
    public function testStrtr()
    {
        $template = '歡迎 {name}，您的年齡是 {age}';
        $data = ['name' => 'John', 'age' => 30];

        $result = ArrayHelper::strtr($template, $data);

        $this->assertEquals('歡迎 John，您的年齡是 30', $result);
    }

    // ==================== dataToStrtrAty() 測試 ====================

    /**
     * 測試：dataToStrtrAty() 轉換格式
     */
    public function testDataToStrtrAty()
    {
        $data = ['name' => 'John', 'age' => 30];
        $result = ArrayHelper::dataToStrtrAty($data);

        $this->assertArrayHasKey('{name}', $result);
        $this->assertArrayHasKey('{age}', $result);
        $this->assertEquals('John', $result['{name}']);
        $this->assertEquals(30, $result['{age}']);
    }

    // ==================== mapToList() 測試 ====================

    /**
     * 測試：mapToList() 轉換為列表
     */
    public function testMapToList()
    {
        $array = ['a' => '選項A', 'b' => '選項B'];
        $result = ArrayHelper::mapToList($array);

        $this->assertCount(2, $result);
        $this->assertEquals(['key' => 'a', 'value' => '選項A'], $result[0]);
        $this->assertEquals(['key' => 'b', 'value' => '選項B'], $result[1]);
    }

    /**
     * 測試：mapToList() 自訂鍵值名稱
     */
    public function testMapToListCustomNames()
    {
        $array = ['a' => '選項A'];
        $result = ArrayHelper::mapToList($array, 'id', 'label');

        $this->assertEquals(['id' => 'a', 'label' => '選項A'], $result[0]);
    }

    // ==================== getAddKeyToValue() 測試 ====================

    /**
     * 測試：getAddKeyToValue() 將鍵加入值
     */
    public function testGetAddKeyToValue()
    {
        $array = ['A' => '選項A', 'B' => '選項B'];
        $result = ArrayHelper::getAddKeyToValue($array);

        $this->assertEquals('[A] 選項A', $result['A']);
        $this->assertEquals('[B] 選項B', $result['B']);
    }

    /**
     * 測試：getAddKeyToValue() 自訂格式
     */
    public function testGetAddKeyToValueCustomFormat()
    {
        $array = ['A' => '選項A'];
        $result = ArrayHelper::getAddKeyToValue($array, '{key}: {value}');

        $this->assertEquals('A: 選項A', $result['A']);
    }

    // ==================== matchPregAll() 測試 ====================

    /**
     * 測試：matchPregAll() 正則比對
     */
    public function testMatchPregAll()
    {
        $pattern = '/\d+/';
        $str = 'abc123def456';

        $result = ArrayHelper::matchPregAll($pattern, $str);

        $this->assertCount(2, $result);
        $this->assertEquals('123', $result[0][0]);
        $this->assertEquals('456', $result[1][0]);
    }

    /**
     * 測試：matchPregAll() 取得第一筆
     */
    public function testMatchPregAllFirst()
    {
        $pattern = '/\d+/';
        $str = 'abc123def456';

        $result = ArrayHelper::matchPregAll($pattern, $str, true);

        $this->assertEquals('123', $result[0]);
    }

    /**
     * 測試：matchPregAll() 無匹配時返回 null
     */
    public function testMatchPregAllNoMatch()
    {
        $pattern = '/\d+/';
        $str = 'abcdef';

        $result = ArrayHelper::matchPregAll($pattern, $str, true);

        $this->assertNull($result);
    }

    // ==================== createSearchDict() 測試 ====================

    /**
     * 測試：createSearchDict() 建立搜尋字典
     */
    public function testCreateSearchDict()
    {
        $fieldAry = ['name', 'email'];
        $result = ArrayHelper::createSearchDict($fieldAry);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('filterName', $result['name']);
        $this->assertEquals('filterEmail', $result['email']);
    }

    /**
     * 測試：createSearchDict() 帶別名
     */
    public function testCreateSearchDictWithAlias()
    {
        $fieldAry = ['userName' => 'name'];
        $result = ArrayHelper::createSearchDict($fieldAry);

        $this->assertArrayHasKey('userName', $result);
        $this->assertEquals('filterName', $result['userName']);
    }

    // ==================== getValueV2() 測試 ====================

    /**
     * 測試：getValueV2() 取得陣列值
     */
    public function testGetValueV2Array()
    {
        $array = ['name' => 'John', 'age' => 30];

        $result = ArrayHelper::getValueV2($array, 'name');
        $this->assertEquals('John', $result);

        $result = ArrayHelper::getValueV2($array, 'nonexistent', 'default');
        $this->assertEquals('default', $result);
    }

    /**
     * 測試：getValueV2() 使用閉包
     */
    public function testGetValueV2Closure()
    {
        $array = ['a' => 1, 'b' => 2];

        $result = ArrayHelper::getValueV2($array, function($arr, $default) {
            return $arr['a'] + $arr['b'];
        });

        $this->assertEquals(3, $result);
    }

    /**
     * 測試：getValueV2() 取得物件屬性
     */
    public function testGetValueV2Object()
    {
        $obj = new \stdClass();
        $obj->name = 'John';
        $obj->age = 30;

        $result = ArrayHelper::getValueV2($obj, 'name');
        $this->assertEquals('John', $result);
    }
}
