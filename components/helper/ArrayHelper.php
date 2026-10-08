<?php

namespace app\components\helper;

use Yii;
use Closure;
use Exception;
use ArrayAccess;
use yii\helpers\VarDumper;
use yii\helpers\BaseArrayHelper;

class ArrayHelper extends BaseArrayHelper
{
    /**
     * 移除陣列中不要的鍵值
     *
     * @param  array $array
     * @param  array|string $keys
     * @return array
     */
    public static function forget(&$array, $keys)
    {
        $keys = (array) $keys;
       
        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * 只留下陣列中所需要的鍵值
     *
     * @param  array $array
     * @param  array|string $keys
     * @return array
     */
    public static function only($array, $keys)
    {
        $keys = (array) $keys;
        return array_intersect_key($array, array_flip($keys));
    }
    
    /**
     * 取得兩個相同鍵的array，鍵值的改變
     *
     * @param  array $attributes
     * @param  array $oldAttributes
     * @return array
     */
    public static function getAttributesMigration($attributes, $oldAttributes)
    {
        // 先確認是否有差異
        $diff = array_diff_assoc($attributes, $oldAttributes);
        header('Content-Type: text/html; charset=utf-8');
        // Missing HSTS Header
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        if (empty($diff)) {
            return [];
        }

        $migrations = [];
        
        foreach ($diff as $attribute => $value) {
            if ($value != ($oldAttributes[$attribute] ?? null)) {
                $migrations[$attribute]['before'] = $oldAttributes[$attribute] ?? null;
                $migrations[$attribute]['after'] = $value;
            }
        }

        return $migrations;
    }
    
    /**
     * 加入空白
     *
     * @param  array $array
     * @return array
     */
    public static function addSpace($array)
    {
        foreach($array as $k => $a)
        {
            $temp[$k] = $a.'　';
        }
        return isset($temp) ? $temp : $array;
    }

    /**
     * 在指定的鍵之後插入新的元素到陣列中。
     *
     * @param array $array 原始陣列。
     * @param string|int $key 要在其後插入新元素的鍵。
     * @param mixed $newElement 要插入的新元素。
     * @return array 返回修改後的陣列。如果在陣列中找不到指定的鍵，則返回原始陣列。
     */
    public static function insertAfterKey($array, $key, $newElement) 
    {
        // 獲取陣列的所有鍵
        $keys = array_keys($array);
        // 尋找指定鍵在鍵陣列中的索引
        $index = array_search($key, $keys);

        // 如果找不到鍵，則直接返回原陣列
        if ($index === false) {
            return $array;
        }

        // 在指定索引之後插入新的元素
        array_splice($array, $index + 1, 0, $newElement);

        // 返回修改後的陣列
        return $array;
    }
    
    /**
     * 是否為json格式
     *
     * @param  string $string
     * @return bool
     */
    public static function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }
        try {
            $data = json_decode($string, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }
            return is_array($data);
        } catch (\Exception $e) {
            Yii::error("Error decoding JSON in isJson: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * 檢索具有給定鍵或屬性名稱的數組元素或對象屬性的值
     *
     * @param array|object $array 要從中提取值的數組或對象
     * @param string|\Closure|array $key 數組元素的鍵名，對象的鍵或屬性名數組，或處理返回值的匿名函數。
     * 匿名函數格式應該是：`function($array, $defaultValue)`。
     * @param mixed $default 如果指定的數組鍵不存在，則返回默認值。從對象獲取值時不使用。
     * @param bool $caseSensitive 關鍵比較是否應該區分大小寫，預設：`true`
     *
     * @return mixed 如果找到元素的值，否則為默認值。
     *
     * @see BaseArrayHelper::getValue()
     */
    public static function getValueV2($array, $key, $default = null, $caseSensitive = true)
    {
        if ($key instanceof Closure) {
            return $key($array, $default);
        }

        if (is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = static::getValue($array, $keyPart);
            }
            $key = $lastKey;
        }

        if (is_object($array)) {
            if(property_exists($array, $key)) {
                return $array->$key;
            }
            // 參考PHP官方例子： https://www.php.net/manual/en/function.get-class-vars.php
            $classVars = get_class_vars(get_class($array));
            $classKey = null;
            foreach (array_keys($classVars) as $k) {
                if (strcasecmp($key, $k) === 0) {
                    $classKey = $k;
                    break;
                }
            }
            if(!is_null($classKey) && property_exists($array, $classKey)) {
                return $array->$classKey;
            }
        }

        if (static::keyExists($key, $array)) {
            return $array[$key];
        }

        if ($key && ($pos = strrpos($key, '.')) !== false) {
            $array = static::getValue($array, substr($key, 0, $pos), $default);
            $key = substr($key, $pos + 1);
        }

        if (static::keyExists($key, $array, $caseSensitive)) {
            if(!$caseSensitive) {
                foreach (array_keys($array) as $k) {
                    if (strcasecmp($key, $k) === 0) {
                        $key = $k;
                        break;
                    }
                }
            }
            return $array[$key];
        }
        if (is_object($array)) {
            // 如果屬性不存在，或者 __get() 未實現，這預計會失敗 無法可靠地預先檢查屬性是否可訪問
            try {
                return $array->$key;
            } catch (Exception $e) {
                if ($array instanceof ArrayAccess) {
                    return $default;
                }
                throw $e;
            }
        }

        return $default;
    }

    /**
     * 取得一個鍵和值的資料
     *
     * @param array $array 原始資料
     * @param string $keep 需要返回的欄位
     *  - key: 鍵
     *  - value: 值
     *
     * @return mixed 返回第一個的鍵或值
     *  - ($keep='key') 第一個的鍵
     *  - ($keep='value') 第一個的值
     */
    public static function arrayFirst($array, $keep=null)
    {
        $result = [];
        foreach($array as $key => $value)
        {
            $result['key'] = $key;
            $result['value'] = $value;
            break;
        }
        if(is_null($keep))
            return $result;
        return self::getValue($result, $keep);
    }

    /**
     * 取得一個第一個鍵和值的簡單數組
     *
     * @param array $array 原始資料
     *
     * @return array
     */
    public static function arrayKShift(&$array)
    {
        list($k) = array_keys($array);
        $r = array($k=>$array[$k]);
        unset($array[$k]);
        return $r;
    }

    /**
     * 從 array 中刪除特定項目，而非刪除 Key
     *
     * @param array $array 原始數組
     * @param string $value 要刪除的特定項目
     *
     * @return array 刪除特定項目的 array
     */
    public static function removeByValue($array, $value)
    {
        if (($key = array_search($value, $array)) !== false) {
            unset($array[$key]);
        }

        return $array;
    }

    /**
     * 從 array 中刪除特定項目，而非刪除 Key
     *
     * @param array $array 原始數組
     * @param array $value 要刪除的特定項目(多個)
     *
     * @return array 刪除特定項目的 array
     */
    public static function removeByValues($array, $value)
    {
        foreach($value as $v)
        {
            $array = self::removeByValue($array, $v);
        }
        return $array;
    }

    /**
     * [uncertainty] 加入空白選項
     *
     * @param array $array 原始數組
     * @param string $valText 加入數組的值
     * @param string $keyText 加入數組的鍵
     *
     * @return array 返回加入的數組
     */
    public static function getIndexColumn($array, $name, $keepKeys = true)
    {
        $result = BaseArrayHelper::getColumn($array, $name, $keepKeys);

        foreach ($result as $key => $val)
        {
            if(!is_int($key))
            {
                unset($result[$key]);
            }
        }

        return $result;
    }

    /**
     * [uncertainty] 在數組開頭加入元素(預設:加入空白選項)
     *
     * @param array $array 原始數組
     * @param string $addAry 加入的數組
     *
     * @return array 返回加入的數組
     */
    public static function addToAryBegin($array, $addAry=null)
    {
        if(is_null($addAry))
        {
            $addAry = [''=>''];
        }
        return BaseArrayHelper::merge($addAry, $array);
    }

    /**
     * 翻譯字符或替換子字串
     *
     * @param array $array 組成字典的源資料
     * @param string|\Closure $from 字典中的鍵值
     * @param string|\Closure $to 字典中的數值
     * @param string|\Closure|null $group 可使用此字串做進一步的分組
     *
     * @return array 根據提供的鍵值及數值返回其數組
     *
     * @see app\components\helper\ArrayHelper::strtr()
     */
    public static function mapByStrtr($array, $from, $to, $group = null)
    {
        // 鍵值
        $fromClosure = $from;
        if (!$from instanceof \Closure) {
            $fromClosure = function ($element, $defaultValue) use ($from)
            {
                return self::strtr($from, $element);
            };
            Yii::debug('fromClosure[def]', __METHOD__);
        }

        // 數值
        $toClosure = $to;
        if (!$to instanceof \Closure)
        {
            $toClosure = function ($element, $defaultValue) use ($to)
            {
                return self::strtr($to, $element);
            };
            Yii::debug('toClosure[def]', __METHOD__);
        }
        try {
            Yii::debug(sprintf(
                "group: %s",
                VarDumper::dumpAsString($group)
            ), __METHOD__);
        } catch (\Exception $e) {
            Yii::error("Error in mapByStrtr debug: " . $e->getMessage(), __METHOD__);
        }

        // 無須群組則直接回傳
        if(is_null($group))
        {
            return static::map($array, $fromClosure, $toClosure);
        }
        // 群組處理
        $groupClosure = $group;
        if (!$group instanceof \Closure)
        {
            $groupClosure = function ($element, $defaultValue) use ($group) {
                return self::strtr($group, $element);
            };
            try {
                Yii::debug('fromClosure[def]', __METHOD__);
            } catch (\Exception $e) {
                Yii::error("Error in mapByStrtr groupClosure debug: " . $e->getMessage(), __METHOD__);
            }
        }
        return static::map($array, $fromClosure, $toClosure, $groupClosure);
    }

    /**
     * 將既有的鍵加入值中，並返回。
     *
     * @param array $array 源資料
     * @param string $formatVal 值的格式
     * @param string $formatKey 鍵的格式
     *
     * @return array 加入鍵的值
     */
    public static function getAddKeyToValue($array, $formatVal='[{key}] {value}', $formatKey='{key}')
    {
        $result = [];
        foreach($array as $k => $v)
        {
            $key = strtr($formatKey, [
                '{key}'   => $k,
                '{value}' => $v,
            ]);
            $value = strtr($formatVal, [
                '{key}'   => $k,
                '{value}' => $v,
            ]);
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * 將 key 及 value 轉換為 array 的 List
     *
     * @param array $array 帶有 key 的 array
     * @param string $keyName 鍵值名稱
     * @param string $valueName 數值名稱
     *
     * @return array key 與 value 組成的 List
     */
    public static function mapToList($array, $keyName = 'key', $valueName = 'value')
    {
        $result = [];
        foreach($array as $key => $value)
        {
            $result[] = [
                $keyName => $key,
                $valueName => $value,
            ];
        }
        return $result;
    }

    /**
     * 翻譯字符或替換子字串
     *
     * @param string $string 文字模板。
     * @param array $array 替換文字模板中的參數值。
     *
     * @return string 經參數值與文字模板組合好的文字
     *
     * @see app\components\helper\ArrayHelper::dataToStrtrAty()
     */
    public static function strtr($string, $array = [])
    {
        return strtr($string, self::dataToStrtrAty($array));
    }

    /**
     * 將 array 的 key 格式修改為 {key} 與 Yii2 語言包類似
     *
     * @param array $array 原始陣列
     *
     * @return array 轉換後陣列
     */
    public static function dataToStrtrAty($array = [])
    {
        $result = [];
        foreach($array as $k => $v)
        {
            $result['{'.$k.'}'] = $v;
        }
        return $result;
    }

    /**
     * 查詢正則表達式
     *
     * @param string $pattern 比對規則
     * @param string $str 要比對的字串
     * @param bool $first 返回第一筆，當沒有筆數時，返回 `null`
     *
     * @return array|null 匹配的項目，當要求返回第一筆卻沒有資料時，返回 `null`
     */
    public static function matchPregAll($pattern, $str, $first=false)
    {
        preg_match_all($pattern, $str, $matches, PREG_SET_ORDER, 0);
        if($first)
        {
            return static::getValue($matches, 0);
        }
        return $matches;
    }

    /**
     * 從參數中根據環境參數取得
     *
     * @param array|string $var 紀錄不同環境參數的數組，若給予非數組將於 Yii::$app->params 中尋找
     * @param string $envrmt 環境參數在 Yii::$app->params 中的鍵值
     * @param null|string $default 當找不到時，預設返回的預設值
     *
     * @return mixed 回傳找到對應環境參數的值或找不倒返回預設值
     */
    public static function getEnvParam($var, $envrmt = 'envrmt', $default = null)
    {
        // 處理 key
        if(is_array($var))
            $array = $var;
        else if(!isset(\Yii::$app->params[$var]))
            throw new \yii\base\InvalidArgumentException("缺少參數 Yii::\$app->params['{$var}']！");
        else
            $array = \Yii::$app->params[$var];
        // 處理 env
        if(!isset(\Yii::$app->params[$envrmt]))
            throw new \yii\base\InvalidArgumentException("缺少參數 Yii::\$app->params['{$envrmt}']！");
        else if(!is_string(\Yii::$app->params[$envrmt]))
            throw new \yii\base\InvalidArgumentException("Yii::\$app->params['{$envrmt}'] 必須是字串！");
        else
            $env = \Yii::$app->params[$envrmt];
        // 最後統整
        if(BaseArrayHelper::keyExists($env, $array, false))
        {
            return static::getValue($env, $array, $default, false);
        }
        return $default;
    }

    /**
     * 建立搜尋所需的 Dict Array
     *
     * 使用方法：
     *
     * ```php
     * $fieldAry = ['asdFgh','qweRty'=>'zxcVbn','zxcVbn'];
     * self::createSearchDict($fieldAry);
     * // [
     * //     'asdFgh'=>'filterAsdFgh',
     * //     'qweRty'=>'filterZxcVbn',
     * //     'zxcVbn'=>'filterZxcVbn'
     * // ]
     * ```
     *
     * @param array $fieldAry 輸入需要搜尋的欄位
     *
     * @return array 轉換成特定字典格式
     */
    public static function createSearchDict($fieldAry)
    {
        foreach($fieldAry as $fK => $fV)
        {
            $key = is_numeric($fK)?$fV:$fK;
            $result[$key] = \yii\helpers\Inflector::variablize('filter_'.$fV);
        }
        return $result;
    }

    /**
     * 取得搜尋的 Array
     *
     * @param array $filterItem 特定字典格式，參考 CusProcessAry::createSearchDict
     * @param null|array $methods 取得搜尋參數取得方法(get/post)，由該 function 取得搜尋值
     * @param null|array $data 外部提供搜尋值
     *
     * @return array 欄位對應搜尋值的 Array
     *
     * @see https://stackoverflow.com/questions/28428492/using-yii2-with-array-of-data-and-a-gridview-with-sorting-and-filter
     */
    public static function getSearchModel($filterItem, $methods = null, $data = null)
    {
        if(is_null($methods))
            $methods = 'get';
        if(is_null($data))
        {
            foreach($filterItem as $fKey => $fValue)
            {
                $temp = \Yii::$app->request->$methods($fValue);
                $result[$fKey] = is_null($temp)?'':$temp;
            }
        } else {
            foreach($filterItem as $fKey => $fValue)
            {
                $result[$fKey] = is_null($data[$fKey])?'':$data[$fKey];
            }
        }
        return $result;
    }

    /**
     * 取得搜尋條件後過濾 $data 的資料，回傳過濾結果
     *
     * @param array $data 要過濾的資料
     * @param array $filterItem 特定字典格式，參考 CusProcessAry::createSearchDict
     * @param null|array $searchModel 欄位對應搜尋值的 Array，參考 CusProcessAry::getSearchModel
     * @return array 過濾後的資料
     *
     * @see app\components\helper\ArrayHelper::getSearchModel()
     * @see https://stackoverflow.com/questions/28428492/using-yii2-with-array-of-data-and-a-gridview-with-sorting-and-filter
     */
    public static function getFilteredArray( $data, $filterItem, $searchModel = null)
    {
        if(is_null($searchModel))
            $searchModel = self::getSearchModel($filterItem);

        // 將一個欄位搜尋多個欄位使用 array 的方式，方便過濾後的結果去使用 OR 方式判斷
        $filterGroupAry = [];
        foreach($filterItem as $field => $filterKey) {
            if(isset($filterGroupAry[$filterKey])) {
                if(is_array($filterGroupAry[$filterKey])) {
                    $filterGroupAry[$filterKey][] = $field;
                } else {
                    $tmp = $filterGroupAry[$filterKey];
                    $filterGroupAry[$filterKey] = [$tmp,$field];
                }
            } else {
                $filterGroupAry[$filterKey] = $field;
            }
        }
        $filterGroupAry = array_values($filterGroupAry);

        $filter = function ($item) use ($filterItem,$searchModel,$filterGroupAry)
        {
            // 針對欄位的值逐一過濾
            $filterAry = [];
            foreach($filterItem as $fKey => $fValue)
            {
                $status = true;
                $temp = $searchModel[$fKey];//搜尋項目
                switch(true)
                {
                    case strlen($temp) == 0: //未輸入
                        break;
                    case is_null($item[$fKey]):
                    case is_array($item[$fKey]) && !in_array( $temp, $item[$fKey]):
                    case (is_numeric($item[$fKey]) || is_string($item[$fKey])) && strlen("$item[$fKey]") < 1:
                    case is_numeric($item[$fKey]) && stripos("$item[$fKey]", $temp) === false:
                    case is_string($item[$fKey]) && stripos($item[$fKey], $temp) === false:
                        $status = false;
                        break;
                    default:
                        break;
                }
                $filterAry[$fKey] = $status;
            }
            // 針對所有欄位過濾的結果去判斷是否顯示
            $result = true;
            foreach($filterGroupAry as $fields)
            {
                if(is_array($fields)) // 多欄位 OR 的方式判斷
                {
                    $r = false;
                    foreach($fields as $field)
                    {
                        // 一個符合就過
                        if ($filterAry[$field] === true) {
                            $r = true;
                            break;
                        }
                    }
                    $result = $r;
                    continue;
                }
                if($filterAry[$fields] === false) // 單一欄位過濾，一個不能就移除
                    $result = false;
                if($result === false) // 只要結果為 false 就結束
                    break;
            }
            return $result;
        };
        return array_filter( $data, $filter);
    }

    /**
     * [uncertainty] 將Array轉成選擇選項
     */
    public static function selectCustomizeByAry($array, $key, $value, $text = '[%s] %s')
    {
        $result = [];
        foreach($array as $k => $v)
        {
            if(is_string($value))
            {
                $result[$v[$key]] = $v[$value];
            }
            else
            {
                $params = [$text];
                foreach($value as $vv)
                {
                    $params[] = $v[$vv];
                }
                try {
                    $result[$v[$key]] = call_user_func_array('sprintf',$params);
                } catch (\Exception $e) {
                    Yii::error("Error in selectCustomizeByAry sprintf: " . $e->getMessage(), __METHOD__);
                    // 回傳一個預設文字或使用其他方式組合
                    $result[$v[$key]] = implode(' ', $params);
                }
            }
        }
        asort($result);
        return $result;
    }

    /**
     * 由 ActiveDataProvider 生成表格特定欄位資料。
     *
     * 這個方法主要用於處理從 ActiveDataProvider 獲取的數據，並根據指定的欄位名稱或鍵名
     * 提取出對應的資料列。進一步地，它支援過濾重複的資料項目。
     *
     * @param \yii\data\ActiveDataProvider $dataProvider 這是提供給表格使用的數據提供器物件。
     * @param integer|string|array|\Closure $name 指定從數據集中提取哪些欄位的標識。可以是單一的欄位名稱、欄位名稱陣列、或者是一個返回欄位名稱的匿名函數。
     * @param bool $unique 如果設置為 true，則從結果中過濾掉重複的資料項目。預設值為 false。
     *
     * @return array 返回處理後的數據集陣列。如果 unique 參數被設定為 true，則返回的數據集將不包含重複項目。
     */
    public static function getDataProviderColumn($dataProvider, $name, $unique = false)
    {
        $models = $dataProvider->getModels(); // 取得當前頁面的資料模型陣列
        $columnData = static::getColumn($models, $name); // 根據欄位名稱提取資料列

        if (!$unique)
        {
            return $columnData;
        }

        return array_unique($columnData); // 返回去重後的資料列
    }
}
