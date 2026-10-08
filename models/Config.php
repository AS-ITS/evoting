<?php

namespace app\models;

use Yii;
use yii\helpers\Json;
use app\components\helper\ArrayHelper;

/**
 * This is the model class for table "config".
 *
 * @property string $id
 * @property string $indexUrl 首頁導向網址
 * @property string $homeLayout 首頁樣板
 * @property string $homeTitle 首頁標題
 * @property string $homeTitleE 首頁標題(英)
 * @property string|null $logoPath Logo 相對 @web 路徑
 * @property string|null $faviconPath Favicon 相對 @web 路徑
 * @property string|null $copyright Footer 版權文字
 * @property int $countColumnNum 計票單行數
 * @property int $partyLimit 分組上限
 * @property int $canDeletePassword 是否可以刪除密碼
 * @property int $anonPasswordErrorTimes 匿名登入嘗試錯誤次數
 * @property int $anonLoginLockPeriod 匿名登入鎖定時間
 * @property int $anonLoginWaiting 匿名登入等待時間
 * @property string $updated_at
 */
class Config extends \yii\db\ActiveRecord
{
    /** 首頁樣板-公版 */
    const LAYOUT_MAIN = 'main';
    /** 首頁樣板-會議版 */
    const LAYOUT_MEETING = 'meeting';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'config';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'countColumnNum', 'partyLimit', 'canDeletePassword'], 'required'],
            [['countColumnNum', 'partyLimit', 'canDeletePassword', 'anonPasswordErrorTimes', 'anonLoginLockPeriod', 'anonLoginWaiting'], 'integer'],
            [['updated_at'], 'safe'],
            [['id'], 'string', 'max' => 20],
            [['homeLayout'], 'string', 'max' => 10],
            [['indexUrl'], 'string', 'max' => 100],
            [['homeTitle'], 'string', 'max' => 120],
            [['homeTitleE', 'copyright'], 'string', 'max' => 255],
            [['logoPath', 'faviconPath'], 'string', 'max' => 200],
            [['logoPath', 'faviconPath'], 'match', 'pattern' => '/^[\w.\-\/]*$/', 'message' => '路徑僅允許英數、底線、連字號、點與斜線'],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '系統代號',
            'indexUrl' => '首頁導向網址',
            'homeLayout' => '首頁樣板',
            'homeTitle' => '首頁標題',
            'homeTitleE' => '首頁標題(英)',
            'logoPath' => 'Logo 路徑',
            'faviconPath' => 'Favicon 路徑',
            'copyright' => '版權文字',
            'countColumnNum' => '計票單行數',
            'partyLimit' => '分組上限',
            'canDeletePassword' => '是否可以刪除密碼',
            'anonPasswordErrorTimes' => '匿名登入嘗試錯誤次數',
            'anonLoginLockPeriod' => '匿名登入鎖定時間(秒)',
            'anonLoginWaiting' => '匿名登入等待時間(秒)',
            'updated_at' => '更新時間',
        ];
    }

    /**
     * 編輯網站設定
     *
     * @param string $id 系統代號
     * @param array $post 表單資料
     *
     * @return bool
     */
    public function updateConfig($id, $post)
    {
        $config = self::findOne($id);
        if ($config === null) {
            $config = new self();
            $config->id = $id;
        }
        $config->load($post);
        $oldAttributes = $config->oldAttributes;

        if(!$config->save()) // 資料驗證
        {
            foreach($config->errors as $message)
            {
                Yii::$app->session->addFlash('error', $message[0]);
            }
            Logs::add(Logs::MANAGE_CONFIG_EDIT_FAIL, Json::encode(['id' => $config->id]+$config->errors, 336));
            return false;
        }
        //比較差異
        $attributesDiff = ArrayHelper::getAttributesMigration($config->attributes, $oldAttributes);
        // LOG紀錄: 修改儲存差異
        Logs::add(Logs::MANAGE_CONFIG_EDIT, Json::encode(['id' => $config->id]+$attributesDiff, 336));

        return true;
    }
}
