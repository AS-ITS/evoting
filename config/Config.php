<?php
namespace app\config;

use yii\helpers\StringHelper;
use app\traits\HeaderConfigTrait;
use app\traits\EnvPathConfigTrait;
use app\interfaces\ConfigInterface;

/**
 * 根據不同環境提供對應的環境參數及設定
 *
 * 應用系統名稱只能有 a-z,A-Z,0-9,- 其餘暫無支持。
 *
 * @property string $appSign 專案名稱
 * @property string $basePath 專案根路徑
 * @property-read string $envRmt 環境參數
 * @property-read string $dbMysqlHost MySql資料庫主機
 * @property-read string $dbMssqlHost MsSql資料庫主機
 * @property-read string $gitVersion 取得專案版本控制的版本
 * @property-read string $consoleOrWeb 當前運行模式屬於 console 或 web
 * @property-read string[] $proxyIp PROXY IP
 * @property-read string[] $smtpAttrs SMTP 參數
 * @property-read string[]|false $path 更多路徑
 */
class Config extends \yii\base\BaseObject implements ConfigInterface
{
    use EnvPathConfigTrait;   // 環境參數和路徑設定
    use HeaderConfigTrait;    // 環境參數和路徑設定

    /**
     * 建立 DB 專用匿名函數
     *
     * @param array $dbConfig DB 相關資訊
     * @param array $param DB 附加設定選項
     *
     * @return array
     */
    public static function getDbArray($dbConfig, $param = [])
    {
        return \app\components\helper\ArrayHelper::merge([
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host='.$dbConfig['db_host'].';dbname='.$dbConfig['db_name'],
            'username' => $dbConfig['db_username'],
            'password' => $dbConfig['db_password'],
            'charset' => 'utf8',
            // Schema cache options (for production environment)
            // 'enableSchemaCache' => true,
            // 'schemaCacheDuration' => 3600,
            // 'schemaCache' => 'cache',
        ], $param);
    }

    /**
     * 回傳帶有AP名稱、環境參數及名稱的字串
     *
     * @param string $name 名稱
     * @param bool $frameUsage 是否為框架使用
     * 若為 true 則回傳名稱以雙底線開頭，若為 false 則是單底線
     *
     * @return string
     */
    public function getFlagName($name, $frameUsage=false)
    {
        $this->checkConf();
        return ($frameUsage?'__':'_')
            .strtolower($this->_appSign)
            .StringHelper::mb_ucwords($this->_envRmt)
            .StringHelper::mb_ucwords($name);
    }

    /**
     * 設定檢查
     *
     * @return true
     */
    protected function checkConf()
    {
        if(trim($this->_envRmt)=='' || trim($this->_appSign)=='')
        {
            throw new \Exception('請確定環境參數(envRmt)、專案資料夾名稱(appSign)正確配置');
        }
        return true;
    }

    /**
     * 取得程式運行環境(console/web)
     *
     * @return string
     */
    public function getConsoleOrWeb()
    {
        $request = new \yii\web\Request;
        if($request->getIsConsoleRequest())
        {
            return 'console';
        }
        else
        {
            return 'web';
        }
    }

    /**
     * 取得 PROXY IP
     *
     * @return string
     */
    public function getProxyIp()
    {
        switch($this->_envRmt)
        {
            case ConfigInterface::ENV_PRODUCTION:
                $value = ConfigInterface::PROXY_IP_PROD;
                break;
            case ConfigInterface::ENV_TESTING:
                $value = ConfigInterface::PROXY_IP_TEST;
                break;
            case ConfigInterface::ENV_DEVELOPMENT:
                $value = ConfigInterface::PROXY_IP_DEV;
                break;
            default:
                header('content-Type: text/plain; charset=utf-8');
                throw new \Exception('請確認有設定環境參數。');
                break;
        }
        return $value;
    }

    /**
     * 刪除資產連結、Yii2記錄
     *
     * @param boolean $assets 是否刪除所有資產連結
     * @param boolean $runtime 是否刪除所有執行中記錄
     *
     * @return \Closure 回傳用於啟動時處理
     *
     * @link Bootstrapping Components https://www.yiiframework.com/doc/guide/2.0/en/structure-application-components#bootstrapping-components
     */
    public function getDeleteRuntimeData($assets=false,$runtime=true)
    {
        return function($app)
        {
            header('content-Type: text/plain; charset=utf-8');
            // 清理 runtime 資料夾
            foreach (glob(\Yii::getAlias('@app/runtime/*'), GLOB_BRACE) as $filename)
            {
                if(is_dir($filename))
                {
                    \yii\helpers\FileHelper::removeDirectory($filename);
                    echo "$filename 已刪除\n";
                }
                else
                {
                    echo "$filename 略過\n";
                }
            }
            // 清理 web/assets 下所有的連結
            foreach (glob(\Yii::getAlias('@app/web/assets/*')) as $filename)
            {
                if(is_link($filename))
                {
                    \yii\helpers\FileHelper::unlink($filename);
                    echo "$filename 已刪除\n";
                }
                else
                {
                    echo "$filename 略過\n";
                }
            }
            exit();
        };
    }
}
