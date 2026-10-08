<?php
namespace app\models;

use Yii;
use yii\base\BaseObject;

/**
 * 中英翻譯處理
 *
 * @link 參考 https://github.com/yiisoft/yii2/blob/161526cd41beee62d0910333c3bd96034cf73186/framework/messages/config.php#L15
 */
class Language extends BaseObject
{
    /**
     * 語言:英文
     * @var string 格式前兩碼參考 ISO-639，後兩碼參考 ISO-3166。
     * @link YiiDoc https://www.yiiframework.com/doc/guide/2.0/en/tutorial-i18n#locale
     **/
    const LANG_US = 'en-US';

    /**
     * 語言:中文
     * @var string 格式前兩碼參考 ISO-639，後兩碼參考 ISO-3166。
     * @link YiiDoc https://www.yiiframework.com/doc/guide/2.0/en/tutorial-i18n#locale
     **/
    const LANG_TW  = 'zh-TW';

    /**
     * 語言保存在session的key
     * @var string
     **/
    const SESSION_NAME = 'language';

    /**
     * @var string $getFlag GET語言參數，預設：hl (完整英文為：host language)
     * @link google的規範 https://sites.google.com/site/tomihasa/google-language-codes
     **/
    public $getFlag = 'hl';

    /**
     * @var string $defLang 預設語言
     **/
    public $defLang = self::LANG_TW;

    /**
     * @var string[][] $language 語言及對應翻譯
     **/
    public $language = [
        self::LANG_US => ['en', self::LANG_US, 'eng', 'english', 'us'],
        self::LANG_TW => ['tw', self::LANG_TW, 'tc', 'chinese', 'taiwan'],
    ];

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the given configuration.
     *
     * @return void
     */
    public function init()
    {
        Yii::$app->language = $this->change(Yii::$app->request->get($this->getFlag));
    }

    /**
     * 中英文切換
     *
     * @return string 修改後的語言，不修改則回傳目前語言
     */
    public function toggle()
    {
        $language = array_keys($this->language);
        return $this->change($language[(!array_search(Yii::$app->language,$language))]);
    }

    /**
     * 用戶設定語言
     *
     * @param null|string $lang 用戶傳入的語言
     *
     * @return string 修改後的語言，不修改則回傳目前語言
     */
    public function change($lang)
    {
        $language = null;
        if(!is_null($lang))
        {
            $lang = strtolower($lang);
            foreach($this->language as $yiiLang => $abbrevLangAry)
            {
                $abbrevLangAry = array_map('strtolower', $abbrevLangAry);
                if(in_array($lang, $abbrevLangAry))
                {
                    $language = $yiiLang;
                    break;
                }
            }
        }
        if(is_null($language))
        {
            $language = Yii::$app->session->get(static::SESSION_NAME, $this->defLang);
        }
        else
        {
            Yii::$app->session->set(static::SESSION_NAME, $language);
        }
        return $language;
    }
}