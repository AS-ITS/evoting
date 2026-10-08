<?php
namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Json;

class FormBallotsCreator extends Model
{
    /**
     * 欄位及過濾項目
     */
    static public $filterItem = [
        'cn'  => 'filterCn',
        'name' => 'filterName',
    ];

    /**
     * 紀錄查詢結果的 session 欄位名稱
     */
    static public $sessionKey = 'Search.personUser';

    /**
     * 取得搜尋的 Array
     * https://stackoverflow.com/questions/28428492/using-yii2-with-array-of-data-and-a-gridview-with-sorting-and-filter
     */
    public static function filteredResultData($filterItem, $methods = null, $data = null)
    {
        if(is_null($methods))
            $methods = 'get';
        if(is_null($data))
        {
            foreach($filterItem as $fKey => $fValue)
            {
                $temp = Yii::$app->request->$methods($fValue);
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
     * https://stackoverflow.com/questions/28428492/using-yii2-with-array-of-data-and-a-gridview-with-sorting-and-filter
     */
    public static function getFilteredArray( $data, $filterItem, $searchModel = null)
    {
        if(is_null($searchModel))
            $searchModel = self::getSearchModel($filterItem);
        $filter = function ($item) use ($filterItem,$searchModel)
        {
            $status = true;
            $pass = [];
            foreach($filterItem as $fKey => $fValue)
            {
                $temp = $searchModel[$fKey];//搜尋項目
                if(strlen($temp) > 0)//存在
                {
                    if(is_array($item[$fKey]) && !in_array( $temp, $item[$fKey]))
                        $status = false;
                    else if(is_string($item[$fKey]) && strpos(strtolower($item[$fKey]), strtolower($temp)) === false)
                        $status = false;
                    else if(isset($pass[$fValue])) // 多重數值
                    {   $status = true;}
                    else // 單值
                    {   $status = true && $status;}
                }
                if(isset($pass[$fValue])) // 多重數值
                    $status = $status || $pass[$fValue];
                $pass[$fValue] = $status;
            }
            return $status;
        };
        return array_filter( $data, $filter);
    }

    /**
     * 保存查詢條件
     */
    public function saveInquire($postData)
    {
        $searchData = [];
        foreach(self::$filterItem as $wsField => $postField)
        {
            if(isset($postData[$postField]) && trim($postData[$postField]) != '')
                $searchData[$wsField] = $postData[$postField];
        }
        Yii::$app->session->set(self::$sessionKey, $searchData);
    }

    /**
     * 取得搜尋模組
     */
    public function getSearchModel($personUser, $methods = 'post')
    {
        return self::filteredResultData(self::$filterItem, $methods, $personUser);
    }

    /**
     * 取得資料
     */
    public function getDataProvider($personUser)
    {
        $filterItem = self::$filterItem;

        // 排除自己本身的過濾器
        unset(
            $filterItem['instCode'],
            $filterItem['tCode']
        );
        
        $dataProvider = (new DataProvider)->getBasicArrayProvider(
            self::getFilteredArray(
                $this->search($personUser), $filterItem, $this->getSearchModel($personUser)
            )
        );
        $dataProvider->sort = [
            'attributes'   => array_keys(self::$filterItem),
            'defaultOrder' => $this->getSort()
        ];
        return $dataProvider;
    }

    /**
     * 取得排序
     */
    public function getSort()
    {
        return [
            'cn' => SORT_ASC,
        ];
    }

    /**
     * 搜尋人員
     */
    public function search($query)
    {
        $result = Users::find()
            ->select(['cn', 'name'])
            ->filterWhere(['cn' => $query])
            ->asArray()
            ->all();
        return $result;
    }
}
