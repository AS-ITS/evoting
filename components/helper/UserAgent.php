<?php

namespace app\components\helper;

use Yii;
use yii\helpers\VarDumper;
use yii\web\HttpException;
use app\components\helper\ArrayHelper;
use yii\helpers\StringHelper;

class UserAgent
{    
    /**
     * 設定返回網址時，使用與當前系統不同的網域(可能存在別名導致可共用)
     * @var string
     */
    const ERROR_SET_RETURN_URL_HOST = 1010;

    /**
     * 設定返回網址時，使用與當前系統不同的位置
     * @var string
     */
    const ERROR_SET_RETURN_URL_PATH = 1011;

    /** @var bool $returnUrlExist 使用者登入完成後，若導回的 action 不存在，則返回首頁不顯示 404 */
    public $returnUrlExist = true;
    /** @var array $returnUrlExc 使用者登入完成後，不要將使用者導回的這些 action */
    public $returnUrlExc = [];

    /** @var string $_type 配置登入的 User flag */
    protected $_type = false;

    /**
     * 使用user agent取得瀏覽器名稱
     *
     * @return string
     * @see https://developer.mozilla.org/zh-TW/docs/Web/HTTP/Browser_detection_using_the_user_agent#%E7%80%8F%E8%A6%BD%E5%99%A8%E5%90%8D%E7%A8%B1
     */
    public static function getBrowserName($userAgent)
    {
        $agent = strtolower($userAgent);

        if (strpos($agent, "opera/") || strpos($agent, "opr/")) {
            $browserName = "Opera";
        } 
        elseIf (strpos($agent, "seamonkey/")) {
            $browserName = "Seamonkey";
        } 
        elseIf (strpos($agent, "chrome/") and strpos(strtolower($agent), "chromium/") == false) {
            $browserName = "Chrome";
        } 
        elseIf (strpos($agent, "msie")) {
            $browserName = "Internet Explorer";
        } 
        elseIf (strpos($agent, "firefox/") and strpos(strtolower($agent), "seamonkey/") == false) {
            $browserName = "Firefox";
        } 
        elseIf (strpos($agent, "safari/") and strpos(strtolower($agent), "chromium/") == false and strpos(strtolower($agent), "chrome/") == false) {
            $browserName = "Safari";
        } 
        else {
            $browserName = 'Unknown';
        };

        return $browserName;
    }

    /**
     * ===============================================================================
     * ============================= 網址有關的 Function =============================
     * ===============================================================================
     */

    /**
     * 在用戶登入前記錄返回 URL。
     *
     * 此方法會記錄用戶在登入前訪問的最後一個有效網址，並將其設置為登入後的返回網址。
     * 它會根據用戶的訪問路徑或引用頁面來確定返回網址。
     *
     * @param string $type 用戶類型，預設為 'user'。
     * @param bool $write 是否寫入返回網址到用戶 session，預設為 true。
     * @return array 包含 'returnUrl' 和 'useMethod' 的陣列，具體包括：
     *   - returnUrl: 要設定的 URL。根據用戶的訪問狀況，這可能是用戶最後訪問的有效 URL。
     *   - useMethod: 使用的方法來確定返回 URL。可能的值包括：
     *     - 'homeUrl': 如果沒有有效的前導頁面或路徑信息，則預設導回 homeUrl。
     *     - 'pathInfo': 如果用戶的當前路徑有效且不在排除列表中，則預設導回 pathInfo。
     *     - 'referrer': 如果用戶有有效的引用頁面且不在排除列表中，則預設導回 referrer。
     */
    public function recordToReturnUrlBeforeLogin($type='user', $write=true)
    {
        // 確定用戶類型是否存在，並為 $_type 變數賦值
        $_type = (Yii::$app->has($type) && Yii::$app->{$type} instanceof \yii\web\User)? $type: 'user';

         // 初始化變數
        $useMethod = 'homeUrl';
        $userReturnUrl = Yii::$app->homeUrl;
        $request = Yii::$app->request;

        // 處理並清理路徑信息
        $pathInfo = trim($request->pathInfo, "/ \t\n\r\0\x0B");
        $returnUrlExc = array_map('trim', $this->returnUrlExc, array_fill(0, count($this->returnUrlExc), "/ \t\n\r\0\x0B"));
        $referrer = $request->referrer;
        $loginUrl = trim(Yii::$app->{$_type}->loginUrl[0], "/ \t\n\r\0\x0B");// 將 /site/login 或 site/login/ 轉為 site/login

        // 處理用戶導向前的網址，判斷當前網址的 controller、action [不為] site/login 或 /site/login
        if(Yii::$app->has($_type) && trim($pathInfo) != '' && $loginUrl != $pathInfo)
        {
            // 檢查路徑是否不在排除列表中
            if(!in_array( $pathInfo, $returnUrlExc) && (!$this->returnUrlExist || $this->existAction($pathInfo)))
            {
                $userReturnUrl = ArrayHelper::merge(["/$pathInfo"], $request->queryParams);
                $useMethod = 'pathInfo';
            }
        }
        // 處理使用者存取的上一個網址
        else if(!is_null($referrer))
        {
            // 處理腳本網址，包括有無尾隨斜線的情況，script 網址的斜線可有可無
            $scriptUrl = [$request->scriptUrl.'/',$request->scriptUrl];
            if(!empty($request->baseUrl))
            {
                // 如果存在基本網址，則添加相應的變體以應對路徑基礎的網址，因應開發區網址是 path base
                $scriptUrl[] = $request->baseUrl.'/';
                $scriptUrl[] = $request->baseUrl;
            }
            // 從引用頁面 URL 中提取動作部分
            $action = str_replace( $scriptUrl, '', parse_url($referrer, PHP_URL_PATH));
            // 檢查該動作是否不在排除列表中，並且是否存在於允許的動作列表中
            if(!in_array( $action, $returnUrlExc) && (!$this->returnUrlExist || $this->existAction($action)))
            {
                // 解析引用頁面的查詢參數
                parse_str(parse_url($referrer, PHP_URL_QUERY), $query);
                // 組合動作和查詢參數以構建最終的返回 URL
                $userReturnUrl = ArrayHelper::merge(["/$action"], $query);
                $useMethod = 'referrer';
            }
        }

        // 記錄最終決定的返回網址和使用的方法
        Yii::debug(sprintf(
            "returnUrl: %s, useMethod: %s",
            VarDumper::dumpAsString($userReturnUrl), VarDumper::dumpAsString($useMethod)
        ), __METHOD__);

        // 如果需要，將返回網址寫入用戶 session
        if(Yii::$app->has($_type) && $write)
        {
            Yii::$app->{$_type}->setReturnUrl($userReturnUrl);
        }

        // 返回結果
        return [
            'returnUrl' => $userReturnUrl,
            'useMethod' => $useMethod
        ];
    }

    /**
     * 檢查指定的模組、控制器或動作是否存在。(不檢查存取權限，意味著有可能無權存取)
     *
     * 此方法通過分析提供的路徑（如模組/控制器/動作）來檢查指定的模組、控制器或動作是否存在於應用中。
     * 它可以處理從最簡單的單一控制器或動作到包含多層模組的複雜路徑。
     *
     * @param string $id 表示要檢查的模組、控制器或動作的 ID。可以是一個從根模組開始的路徑。
     * @param yii\base\Module|null $module 起始檢查的模組。如果為 null，則從整個應用開始檢查。
     *
     * @return bool 返回 true 如果指定的模組、控制器或動作存在，否則返回 false。
     */
    public function existAction($id, $module=null)
    {
        if (is_string($id))
        {
            // 過濾字串前後斜線及空白，並分割成數組
            $_id = explode('/', trim($id, "/ \t\n\r\0\x0B"));
        }
        if (is_null($module))
        {
            // 如果未指定模組，則從根模組開始檢查
            $module = Yii::$app;
        }

        // 取得第一部分的 ID 並刪除其參數，準備進行檢查
        $m = array_shift($_id);

        // 檢查是否為一個模組
        if ($module->hasModule($m))
        {
            // 取得模組並進行進一步檢查
            $mModule = $module->getModule($m);
            if (count($_id) == 0)
            {
                // 如果沒有更多的路徑部分，表示只檢查模組
                return $module->hasModule($m);
            }

            // 如果有更多路徑部分，則繼續檢查下一層模組
            return $this->existAction(join('/', $_id), $mModule);
        }
        // 處理控制器和動作的情況

        // 檢查控制器是否存在
        $mController = $module->createController($m);
        if (count($_id) == 0)
        {
            // 如果沒有更多的路徑部分，表示只檢查控制器
            return $mController !== false;
        }

        // 檢查動作是否存在
        $mAction = array_shift($_id);
        return $mController !== false && $mController[0]->createAction($mAction) !== null;
    }

    /**
     * 設置返回 URL 並進行安全性檢查。
     *
     * 此方法會設置一個返回 URL，並檢查該 URL 的網域和路徑是否符合安全標準。
     * 如果返回網址的網域或路徑不符合當前系統設定，則會拋出 HttpException 異常。
     *
     * @param string $returnURL 欲設置的返回 URL。
     * @return bool 返回設置的狀態
     *
     * @throws HttpException 如果返回網址的網域或路徑不符合安全標準時拋出。
     */
    public function setReturnURL($returnURL)
    {
        // 獲取當前請求信息
        $request = Yii::$app->request;

        // 檢查返回 URL 的網域是否與當前請求的網域相同
        if( parse_url($returnURL, PHP_URL_HOST) != $request->headers->get('host') )
        {
            throw new HttpException( 400, '[安全性] 錯誤：返回網址的網域非本系統因而受到阻擋！', static::ERROR_SET_RETURN_URL_HOST);
        }

        // 檢查返回 URL 的路徑是否以基本網址開頭
        if( !StringHelper::startsWith( parse_url($returnURL, PHP_URL_PATH), $request->baseUrl ) )
        {
            throw new HttpException( 400, '[安全性] 錯誤：返回網址路徑與基本網址不一致！', static::ERROR_SET_RETURN_URL_PATH);
        }

        // 處理返回動作的路徑
        $action = trim(str_replace($request->scriptUrl, '', parse_url($returnURL, PHP_URL_PATH)), '/');

        // 檢查動作是否在排除名單之外，並設置返回 URL
        if( trim($action) != '' && !in_array( $action, $this->returnUrlExc))
        {
            parse_str(parse_url($returnURL, PHP_URL_QUERY), $query);
            $userReturnUrl = ArrayHelper::merge([$action], $query);
            Yii::debug(sprintf(
                "type: %s, setReturnUrl: %s",
                VarDumper::dumpAsString($this->_type),
                VarDumper::dumpAsString($userReturnUrl)
            ), __METHOD__);
            Yii::$app->{$this->_type}->setReturnUrl($userReturnUrl);
            return true;
        }
        return false;
    }
}

