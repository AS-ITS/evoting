<?php
namespace app\interfaces;

/**
 * 這是處理環境參數的介面(Interface)
 */
interface ConfigInterface
{
    /********** 環境參數 **********/

    /**
     * 環境參數:正式環境
     * @var string
     **/
    const ENV_PRODUCTION = 'production';
    /**
     * 環境參數:測試環境
     * @var string
     **/
    const ENV_TESTING = 'testing';
    /**
     * 環境參數:開發環境
     * @var string
     **/
    const ENV_DEVELOPMENT = 'development';


    /********** 機敏參數加密 **********/

    /********** 網址路徑格式(Domain) **********/

    /**
     * 網址路徑格式:正式環境
     * 可透過環境變數 APP_DOMAIN_PRODUCTION 自訂
     * @var string
     **/
    const DOMAIN_URL_PROD = 'https://{ap}.example.com';
    /**
     * 網址路徑格式:測試環境
     * 可透過環境變數 APP_DOMAIN_TESTING 自訂
     * @var string
     **/
    const DOMAIN_URL_TEST = 'https://{ap}.test.example.com';
    /**
     * 網址路徑格式:開發環境
     * 可透過環境變數 APP_DOMAIN_DEVELOPMENT 自訂
     * @var string
     **/
    const DOMAIN_URL_DEV = 'http://localhost/{ap}';

    /********** 路徑 **********/

    /**
     * 路徑:程式目錄（預設值，可透過環境變數 APP_PATH_PROGRAM 自訂）
     * @var string
     **/
    const PATH_PROGRAM = '/var/www/html';
    /**
     * 路徑:資料庫檔案目錄（預設值，可透過環境變數 APP_PATH_DB 自訂）
     * @var string
     **/
    const PATH_DB = '/var/www/db';
    /**
     * 路徑:檔案上傳目錄（預設值，可透過環境變數 APP_PATH_UPLOADS 自訂）
     * @var string
     **/
    const PATH_FILE_POOL = '/var/www/uploads';
    /**
     * 路徑:機敏參數目錄（預設值，可透過環境變數 APP_PATH_CONFIG 自訂）
     * @var string
     **/
    const PATH_CONFIG = '/var/www/config';

    /********** DB 主機 **********/
    // 注意：資料庫主機設定應透過環境變數配置
    // 以下常數僅作為預設值參考

    /**
     * MYSQL DB主機:正式環境（建議透過環境變數 DB_HOST 設定）
     * @var string
     **/
    const DB_MYSQL_HOST_PROD = 'localhost';
    /**
     * MYSQL DB主機:測試環境（建議透過環境變數 DB_HOST 設定）
     * @var string
     * */
    const DB_MYSQL_HOST_TEST = 'localhost';
    /**
     * MYSQL DB主機:開發環境（建議透過環境變數 DB_HOST 設定）
     * @var string
     **/
    const DB_MYSQL_HOST_DEV  = 'localhost';

    /**
     * MSSQL DB主機:正式環境（選用，若不使用MSSQL可忽略）
     * @var string
     **/
    const DB_MSSQL_HOST_PROD = 'localhost';
    /**
     * MSSQL DB主機:測試環境（選用，若不使用MSSQL可忽略）
     * @var string
     * */
    const DB_MSSQL_HOST_TEST = 'localhost';
    /**
     * MSSQL DB主機:開發環境（選用，若不使用MSSQL可忽略）
     * @var string
     **/
    const DB_MSSQL_HOST_DEV  = 'localhost';

    /********** 外部 API 服務 **********/
    // 注意：以下為原 FISA API 設定，已改為通用配置
    // 若不使用外部 API 服務，可忽略這些設定

    /**
     * 外部 API:正式環境（可透過環境變數 EXTERNAL_API_URL 設定）
     * @var string
     **/
    const FISA_API_PROD = '';
    /**
     * 外部 API:測試環境（可透過環境變數 EXTERNAL_API_URL 設定）
     * @var string
     **/
    const FISA_API_TEST = '';
    /**
     * 外部 API:開發環境（可透過環境變數 EXTERNAL_API_URL 設定）
     * @var string
     **/
    const FISA_API_DEV = '';
    /**
     * 外部 API:開發區備用（選用）
     * @var string
     **/
    const FISA_API_DEV_WS = '';
    /**
     * 外部 API:開發區使用正式資料源（選用）
     * @var string
     **/
    const FISA_API_DEV_PROD = '';

    /********** PROXY服務 **********/
    // 注意：若環境需要使用 Proxy，請透過環境變數 HTTP_PROXY 設定

    /**
     * 連出外網的proxy - 正式環境（選用，可透過環境變數 HTTP_PROXY 設定）
     * @var string
     **/
    const PROXY_IP_PROD = '';
    /**
     * 連出外網的proxy - 測試環境（選用，可透過環境變數 HTTP_PROXY 設定）
     * @var string
     **/
    const PROXY_IP_TEST = '';
    /**
     * 連出外網的proxy - 開發環境（選用，可透過環境變數 HTTP_PROXY 設定）
     * @var string
     **/
    const PROXY_IP_DEV  = '';

    /********** 寄信服務 **********/
    // 注意：郵件服務設定建議透過環境變數配置
    // 環境變數：SMTP_HOST, SMTP_PORT, SMTP_ENCRYPTION, SMTP_USERNAME, SMTP_PASSWORD

    /**
     * 寄信服務:主機 - 正式環境
     * @var string
     **/
    const SMTP_HOST_PROD = 'localhost';
    /**
     * 寄信服務:端口 - 正式環境
     * @var string
     **/
    const SMTP_PORT_PROD = '25';
    /**
     * 寄信服務:加密 - 正式環境 (tls/ssl/留空)
     * @var string
     **/
    const SMTP_ENC_PROD  = '';

    /**
     * 寄信服務:主機 - 測試環境
     * @var string
     **/
    const SMTP_HOST_TEST = 'localhost';
    /**
     * 寄信服務:端口 - 測試環境
     * @var string
     **/
    const SMTP_PORT_TEST = '25';
    /**
     * 寄信服務:加密 - 測試環境 (tls/ssl/留空)
     * @var string
     **/
    const SMTP_ENC_TEST  = '';

    /**
     * 寄信服務:主機 - 開發環境
     * @var string
     * */
    const SMTP_HOST_DEV  = 'localhost';
    /**
     * 寄信服務:端口 - 開發環境
     * @var string
     **/
    const SMTP_PORT_DEV  = '25';
    /**
     * 寄信服務:加密 - 開發環境 (tls/ssl/留空)
     * @var string
     **/
    const SMTP_ENC_DEV   = '';

    /********** IP 限制 **********/
    // 注意：若需要 IP 白名單限制，請透過環境變數配置
    // 環境變數：ALLOWED_IP_RANGES (逗號分隔)

    /**
     * IP區段: 允許的IP範圍（選用，可透過環境變數 ALLOWED_IP_RANGES 設定）
     * 範例格式：'192.168.1' 或 '10.0.0.0/8'
     * @var string
     **/
    const IP_RANGE_VPN = '';
}