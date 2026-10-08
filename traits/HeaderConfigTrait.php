<?php
namespace app\traits;

use app\components\helper\ArrayHelper;

/**
 * 此 trait 包含與 HTTP 頭部欄位操作相關的函數。
 */
trait HeaderConfigTrait
{
    /** @var array $_headers 使用者請求之頭部 */
    protected $_headers = [];

    /**
     * 初始化對象。
     * 在使用給定配置初始化對像後，在構造函數的末尾調用此方法。
     */
    public function init()
    {
        $this->getAttrDetByAuto();

        // 根據 RFC2616 規範，HTTP 頭部是不區分大小寫的
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        foreach ($headers as $name => $value)
        {
            $this->_headers[$name] = $value;
        }
    }

    /**
     * 取得所有頭部欄位
     *
     * @return array 所有頭部欄位
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * 取得特定頭部欄位
     *
     * @param int|string $name 欄位名稱
     * @param mixed $default 當找不到該欄位名稱時，回傳該值
     *
     * @return mixed 欄位內容或`$default`
     */
    public function getHeader($name, $default=null)
    {
        return ArrayHelper::getValue($this->_headers, $name, $default);
    }

    /**
     * 檢查是否存在頭部欄位
     *
     * @param mixed $name 欄位名稱
     *
     * @return bool 該欄位是否存在
     */
    public function hasHeader($name, $caseSensitive=false)
    {
        return ArrayHelper::keyExists($name, $this->_headers, $caseSensitive);
    }
}
