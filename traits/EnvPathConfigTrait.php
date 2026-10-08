<?php
namespace app\traits;

use Yii;
use app\components\helper\ArrayHelper;
use app\interfaces\ConfigInterface;

/**
 * 此 trait 包含與環境參數和路徑設定相關的函數。
 */
trait EnvPathConfigTrait
{
    /**
     * 使用網址中的網域辨別系統名稱及分區（通用版本）
     * 匹配格式：{app}.{env}.domain.com 或 domain.com/{app}
     * @var string AREA_REGEX_URL_DOMAIN
     * @deprecated 建議使用 APP_ENV 環境變數設定環境
     **/
    const AREA_REGEX_URL_DOMAIN = '/(?:([a-zA-Z0-9\-]+)[.])*([a-zA-Z0-9\-]+)\.[a-zA-Z]{2,}/m';
    /**
     * 使用運行腳本的路徑辨別系統名稱及分區
     * @var string
     * @deprecated 建議使用 APP_ENV 環境變數設定環境，使用 APP_BASE_PATH 設定路徑
     **/
    const AREA_REGEX_SCRIPT_PATH = '/\/([a-zA-Z0-9_\-\/]+)\/([a-zA-Z0-9\-]+)(?:\/(?:yii|web|public))?$/m';
    /**
     * 使用程式根的路徑辨別系統名稱及分區
     * @var string
     * @deprecated 建議使用 APP_BASE_PATH 環境變數設定路徑
     **/
    const AREA_REGEX_PATH = '/\/([a-zA-Z0-9_\-\/]+)\/([a-zA-Z0-9\-]+)$/m';
    /**
     * 使用路徑辨別 WS 分區:識別開頭符合 ws+數字+/
     * @var string
     * @deprecated 不再使用 WS 分區概念
     **/
    const AREA_REGEX_PATH_WS = '/^ws(?:\d+)?\/(.+)/m';
    
    /** @var string $_envRmt 當前環境參數 (production/testing/development) */
    protected $_envRmt = '';
    /** @var string $_appSign 專案資料夾名稱 */
    protected $_appSign = '';
    /** @var string $_basePath 專案根路徑 */
    protected $_basePath = '';
    /** @var string $_gitVer 當前版控號碼 (資料夾須符合格式:{專案名稱}_V_{年}-{月}-{日}，例如:ComVer_V_2022-08-07) */
    protected $_gitVer = '';

    /** @var string[] $_pathBaseDomain 共用網域，使用路徑區分開發環境 */
    protected $_pathBaseDomain = [
        'localhost',
        '127.0.0.1',
        'vhost.test',
        'fisher8966-PC',
        // 可在此添加您的開發域名
    ];

    /**
     * @var string[][] $_ipEnvInfo 各環境主機IP資訊對照表
     *
     * 此對照表用於根據伺服器IP自動判斷環境
     * 若不需要IP對照功能，可保持為空陣列
     *
     * 格式範例：
     * '192.168.1.100' => [
     *     'ApName' => 'production-server',
     *     'Area' => 'prod',
     *     'EnvRmt' => ConfigInterface::ENV_PRODUCTION,
     * ],
     */
    protected $_ipEnvInfo = [
        // 範例：可根據您的環境添加IP對照
        // '192.168.1.100' => [
        //     'ApName' => 'production-server',
        //     'Area' => 'production',
        //     'EnvRmt' => ConfigInterface::ENV_PRODUCTION,
        // ],
        // '192.168.1.200' => [
        //     'ApName' => 'testing-server',
        //     'Area' => 'testing',
        //     'EnvRmt' => ConfigInterface::ENV_TESTING,
        // ],
    ];


    /**
     * 取得環境參數
     * 優先順序：1. 已設定的 $_envRmt  2. 環境變數 APP_ENV  3. 自動偵測
     *
     * @return string
     */
    public function getEnvRmt()
    {
        // 若尚未設定，嘗試從環境變數讀取
        if (empty($this->_envRmt) && !empty(getenv('APP_ENV'))) {
            $envFromVar = getenv('APP_ENV');
            $this->setEnvRmt($envFromVar);
        }

        switch($this->_envRmt)
        {
            case ConfigInterface::ENV_PRODUCTION:
            case ConfigInterface::ENV_TESTING:
            case ConfigInterface::ENV_DEVELOPMENT:
                $value = $this->_envRmt;
                break;
            default:
                header('content-Type: text/plain; charset=utf-8');
                throw new \Exception('請確認有設定環境參數（APP_ENV）。支援的值：production, testing, development');
                break;
        }
        return $value;
    }

    /**
     * 設定環境參數
     *
     * @param string $value 環境參數
     *
     * @return void
     */
    protected function setEnvRmt($value)
    {
        // 標準化環境值（支援多種寫法）
        $normalizedValue = strtolower(trim($value));
        $envMap = [
            'production' => ConfigInterface::ENV_PRODUCTION,
            'prod' => ConfigInterface::ENV_PRODUCTION,
            'product' => ConfigInterface::ENV_PRODUCTION,
            'testing' => ConfigInterface::ENV_TESTING,
            'test' => ConfigInterface::ENV_TESTING,
            'development' => ConfigInterface::ENV_DEVELOPMENT,
            'dev' => ConfigInterface::ENV_DEVELOPMENT,
            'alpha' => ConfigInterface::ENV_DEVELOPMENT,
            'ws' => ConfigInterface::ENV_DEVELOPMENT,
        ];

        if (array_key_exists($normalizedValue, $envMap)) {
            $this->_envRmt = $envMap[$normalizedValue];
        } else {
            switch($value)
            {
                case ConfigInterface::ENV_PRODUCTION:
                case ConfigInterface::ENV_TESTING:
                case ConfigInterface::ENV_DEVELOPMENT:
                    $this->_envRmt = strval($value);
                    break;
                default:
                    header('content-Type: text/plain; charset=utf-8');
                    throw new \Exception('當前環境參數僅支援：production, testing, development（或其縮寫 prod, test, dev）');
                    break;
            }
        }
    }

    /**
     * 設定專案資料夾名稱
     *
     * @param string $value 專案名稱
     *
     * @return void
     */
    protected function setAppSign($value)
    {
        $this->_appSign = $value;
    }

    /**
     * 設定專案根路徑
     *
     * @param string $value 根路徑
     *
     * @return void
     */
    protected function setBasePath($value)
    {
        $this->_basePath = $value;
    }

    /**
     * 取得環境參數
     *
     * @return string
     */
    public function getAppSign()
    {
        $this->checkConf();
        return $this->_appSign;
    }

    /**
     * 取得網址路徑格式
     *
     * ```php
     * \app\components\helper\ArrayHelper::strtr($conf->getDomainUrl(), [
     *     'ap' => 'comver',
     * ]);
     * ```
     *
     * @return string
     */
    public function getDomainUrl()
    {
        switch($this->_envRmt)
        {
            case ConfigInterface::ENV_PRODUCTION:
                $value = ConfigInterface::DOMAIN_URL_PROD;
                break;
            case ConfigInterface::ENV_TESTING:
                $value = ConfigInterface::DOMAIN_URL_TEST;
                break;
            case ConfigInterface::ENV_DEVELOPMENT:
                $value = ConfigInterface::DOMAIN_URL_DEV;
                break;
            default:
                header('content-Type: text/plain; charset=utf-8');
                throw new \Exception('請確認有設定環境參數。');
                break;
        }
        return $value;
    }

    /**
     * 由 basePath 取得更多路徑
     *
     * 優先使用環境變數配置：
     * - APP_ID: 應用程式識別名稱
     * - APP_BASE_PATH: 應用程式根目錄
     * - APP_PATH_PROGRAM: 程式目錄根路徑
     * - APP_PATH_CONFIG: 機敏參數目錄
     * - APP_PATH_DB: 資料庫檔案目錄
     * - APP_PATH_UPLOADS: 檔案上傳目錄
     *
     * @param null|string $basePath 程式根路徑
     *
     * @return string[]|false
     */
    public function getPath($basePath=null)
    {
        if(is_null($basePath))
        {
            $basePath = $this->_basePath;
        }

        // 優先使用環境變數
        $appSign = getenv('APP_ID') ?: $this->_appSign;
        if (empty($appSign)) {
            // 從 basePath 提取專案名稱（最後一個目錄名）
            $appSign = basename($basePath);
        }

        // 支援環境變數覆蓋預設路徑
        $pathProgram = getenv('APP_PATH_PROGRAM') ?: ConfigInterface::PATH_PROGRAM;
        $pathDb = getenv('APP_PATH_DB') ?: ConfigInterface::PATH_DB;
        $pathFilePool = getenv('APP_PATH_UPLOADS') ?: ConfigInterface::PATH_FILE_POOL;

        // 判斷路徑結構
        // 如果環境變數已設定完整路徑（包含 appSign），則直接使用
        $programPath = $pathProgram;
        $dbPath = $pathDb;
        $filePoolPath = $pathFilePool;

        // 檢查路徑是否已包含 appSign（避免重複附加）
        if (strpos($programPath, $appSign) === false) {
            $programPath = rtrim($pathProgram, '/') . "/{$appSign}";
        }
        if (strpos($dbPath, $appSign) === false) {
            $dbPath = rtrim($pathDb, '/') . "/{$appSign}";
        }
        if (strpos($filePoolPath, $appSign) === false) {
            $filePoolPath = rtrim($pathFilePool, '/') . "/{$appSign}";
        }

        // 取得機敏參數路徑（向後相容 dore）
        $pathConfig = getenv('APP_PATH_CONFIG') ?: ConfigInterface::PATH_CONFIG;
        $configPath = $pathConfig;
        if (strpos($configPath, $appSign) === false) {
            $configPath = rtrim($pathConfig, '/') . "/{$appSign}";
        }

        return [
            'apSign' => $appSign,
            'apBase' => $appSign,
            'program' => $programPath,
            'db' => $dbPath,
            'filePool' => $filePoolPath,
            'dore' => $configPath,  // 向後相容：機敏參數路徑
            'config' => $configPath, // 新名稱
        ];
    }

    /**
     * 由網域自動檢測所屬環境
     * 支援格式：app.prod.domain.com, app.test.domain.com, app.dev.domain.com
     *
     * @param string $serverName 網域名稱
     * @param bool $force 是否強制使用自動識別的數值(將無視原先給予的資料)
     *
     * @return object $this
     */
    protected function determinePartitionByDomain($serverName, $force)
    {
        $matches = $this->regexMatch(self::AREA_REGEX_URL_DOMAIN, $serverName);
        if(count($matches) > 0)
        {
            // domain base
            $res = array_shift($matches);// 1:ApName, 2:env/domain識別部分
            if(trim($this->_appSign)=='' || $force)
            {
                // 如果有子域名，取第一部分作為 app name
                if (!empty($res[1])) {
                    $this->_appSign = $res[1];
                }
            }

            // 根據域名關鍵字判斷環境
            $envKeywords = [
                'prod' => ConfigInterface::ENV_PRODUCTION,
                'production' => ConfigInterface::ENV_PRODUCTION,
                'test' => ConfigInterface::ENV_TESTING,
                'testing' => ConfigInterface::ENV_TESTING,
                'stage' => ConfigInterface::ENV_TESTING,
                'staging' => ConfigInterface::ENV_TESTING,
                'dev' => ConfigInterface::ENV_DEVELOPMENT,
                'development' => ConfigInterface::ENV_DEVELOPMENT,
                'local' => ConfigInterface::ENV_DEVELOPMENT,
                'localhost' => ConfigInterface::ENV_DEVELOPMENT,
            ];

            if(trim($this->_envRmt)=='' || $force)
            {
                // 檢查域名中是否包含環境關鍵字
                foreach ($envKeywords as $keyword => $env) {
                    if (stripos($serverName, $keyword) !== false) {
                        $this->_envRmt = $env;
                        return $this;
                    }
                }
                // 預設為開發環境
                $this->_envRmt = ConfigInterface::ENV_DEVELOPMENT;
            }
        }
        else
        {
            header('content-Type: text/plain; charset=utf-8');
            throw new \Exception('請確認[網域]格式是否正確！');
        }
        return $this;
    }

    /**
     * 由路徑自動檢測所屬分區
     *
     * 優先使用環境變數 APP_ENV 和 APP_ID，若未設定則嘗試從路徑推測
     *
     * @param bool $force 是否強制使用自動識別的數值(將無視原先給予的資料)
     *
     * @return object $this
     * @deprecated 建議直接使用 APP_ENV 和 APP_ID 環境變數設定
     */
    protected function determinePartitionByPath($force)
    {
        // 優先使用環境變數
        $envFromVar = getenv('APP_ENV');
        $appIdFromVar = getenv('APP_ID');

        if ($envFromVar !== false && $envFromVar !== '') {
            if (trim($this->_envRmt) == '' || $force) {
                $this->setEnvRmt($envFromVar);
            }
        }

        if ($appIdFromVar !== false && $appIdFromVar !== '') {
            if (trim($this->_appSign) == '' || $force) {
                $this->_appSign = $appIdFromVar;
            }
            return $this;
        }

        // 若環境變數未設定，嘗試從路徑推測
        $scriptFile = '';
        try {
            $request = new \yii\console\Request;
            $scriptFile = $request->getScriptFile();
        } catch (\yii\base\InvalidConfigException $e) {
            // 使用 basePath 作為後備
            if (!empty($this->_basePath)) {
                $scriptFile = $this->_basePath . '/web/index.php';
            }
        }

        // 從路徑提取專案名稱（取最後一個有意義的目錄名）
        if (!empty($scriptFile)) {
            $pathParts = explode('/', trim($scriptFile, '/'));
            // 移除 web、public、yii 等常見入口目錄
            $excludeDirs = ['web', 'public', 'yii', 'index.php', 'html', 'www', 'htdocs'];
            $appSign = '';
            for ($i = count($pathParts) - 1; $i >= 0; $i--) {
                if (!in_array(strtolower($pathParts[$i]), $excludeDirs) && !empty($pathParts[$i])) {
                    $appSign = $pathParts[$i];
                    break;
                }
            }

            if (!empty($appSign) && (trim($this->_appSign) == '' || $force)) {
                $this->_appSign = $appSign;
            }
        }

        // 若環境仍未設定，使用預設開發環境
        if (trim($this->_envRmt) == '' || $force) {
            $this->_envRmt = ConfigInterface::ENV_DEVELOPMENT;
        }

        return $this;
    }

    /**
     * 取得自動檢測到的屬性
     *
     * 優先使用環境變數 APP_ENV 和 APP_ID，若未設定則嘗試自動推測
     *
     * @param bool $force 是否強制使用自動識別的數值(將無視原先給予的資料)
     *
     * @return object $this
     */
    public function getAttrDetByAuto($force=false) // Get attribute detected by auto
    {
        // 1. 優先使用環境變數（推薦方式）
        $envFromVar = getenv('APP_ENV');
        $appIdFromVar = getenv('APP_ID');

        if (($envFromVar !== false && $envFromVar !== '') ||
            ($appIdFromVar !== false && $appIdFromVar !== '')) {
            // 使用環境變數設定
            $this->determinePartitionByPath($force);
            return $this;
        }

        // 2. 使用[IP對照表]判斷AP名稱、所在分區（向後相容）
        $serverIp = getHostByName(getHostName());
        if(ArrayHelper::keyExists($serverIp, $this->_ipEnvInfo))
        {
            // 根據路徑設定其專案名稱
            $this->determinePartitionByPath($force);

            // 根據IP對照表中的資訊回填分區
            $ipEnvInfo = ArrayHelper::getValue($this->_ipEnvInfo, $serverIp);
            if(ArrayHelper::keyExists('EnvRmt', $ipEnvInfo))
            {
                if(!$force)
                    $this->_envRmt = ArrayHelper::getValue($ipEnvInfo,'EnvRmt');
            }

            return $this;
        }

        // 3. 使用[網址/路徑]判斷（向後相容）
        if($this->getConsoleOrWeb() == 'console')
        {
            $serverName = php_uname('n');
        }
        else
        {
            $request = new \yii\web\Request;
            $serverName = $request->serverName;
        }

        // 嘗試各種方式判斷環境
        if(in_array($serverName, $this->_pathBaseDomain))
        {
            // path base
            $this->determinePartitionByPath($force);
        }
        else if(preg_match(self::AREA_REGEX_URL_DOMAIN, $serverName))
        {
            // domain base
            $this->determinePartitionByDomain($serverName, $force);
        }
        else
        {
            // 無法自動判斷，使用預設值
            $this->determinePartitionByPath($force);
        }
        return $this;
    }

    /**
     * 根據正則表達式回傳處理結果
     *
     * @param string $re 正規表示式語法
     * @param string $str 要處理的字串
     *
     * @return string[][] 根據表示式排序的多維數組中所有匹配的數組。
     * @link website https://regex101.com/
     */
    protected function regexMatch($re, $str)
    {
        $matches = [];
        preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
        return $matches;
    }

    /**
     * 簡化的環境檢測方法（推薦使用）
     *
     * 此方法提供更清晰簡單的環境檢測邏輯，建議新專案使用此方法
     * 優先順序：
     *   1. 環境變數 APP_ENV（最高優先權）
     *   2. 域名判定（由環境變數 TEST_DOMAIN_SUFFIX / PROD_DOMAIN_SUFFIX 設定）
     *   3. 預設為開發環境（最安全）
     *
     * @return string 環境識別字串 (product/test/alpha)
     * @since 2026-01-13
     */
    public function getCurrentEnvironment(): string
    {
        // 1. 明確的環境變數（最高優先權）
        $envFromVar = getenv('APP_ENV');
        if ($envFromVar !== false && $envFromVar !== '') {
            // 直接返回標準化的環境值（不再使用舊常數）
            $normalized = strtolower(trim($envFromVar));
            $envMap = [
                'production' => 'production',
                'prod' => 'production',
                'product' => 'production',
                'testing' => 'testing',
                'test' => 'testing',
                'development' => 'development',
                'dev' => 'development',
                'alpha' => 'development',
            ];

            if (isset($envMap[$normalized])) {
                return $envMap[$normalized];
            }
        }

        // 2. 根據域名判定（僅限 web 請求）
        // 透過環境變數設定域名後綴，例如：
        //   TEST_DOMAIN_SUFFIX=-t.example.com
        //   PROD_DOMAIN_SUFFIX=.example.com
        if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
            $host = strtolower($_SERVER['HTTP_HOST']);

            $testSuffix = strtolower(trim(getenv('TEST_DOMAIN_SUFFIX') ?: ''));
            $prodSuffix = strtolower(trim(getenv('PROD_DOMAIN_SUFFIX') ?: ''));

            // 測試環境域名
            if ($testSuffix !== '' && strpos($host, $testSuffix) !== false) {
                return 'testing';
            }

            // 正式環境域名
            if ($prodSuffix !== '' && strpos($host, $prodSuffix) !== false) {
                return 'production';
            }
        }

        // 3. 預設為開發環境（最安全的 fallback）
        // 這確保在無法判定時不會誤判為正式環境
        return 'development';
    }

    /**
     * 判斷是否為正式環境
     *
     * @return bool
     * @since 2026-01-13
     */
    public function isProductionEnvironment(): bool
    {
        return $this->getCurrentEnvironment() === ConfigInterface::ENV_PRODUCTION;
    }

    /**
     * 判斷是否為測試環境
     *
     * @return bool
     * @since 2026-01-13
     */
    public function isTestEnvironment(): bool
    {
        return $this->getCurrentEnvironment() === ConfigInterface::ENV_TESTING;
    }

    /**
     * 判斷是否為開發環境
     *
     * @return bool
     * @since 2026-01-13
     */
    public function isDevelopmentEnvironment(): bool
    {
        return $this->getCurrentEnvironment() === ConfigInterface::ENV_DEVELOPMENT;
    }
}
