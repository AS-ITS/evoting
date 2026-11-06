<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "logins".
 *
 * @property string $voteID 投票識別碼
 * @property string $party 投票者組別
 * @property int $creator 登入對象(投票者)
 * @property string|null $session 登入session
 */
class Logins extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'logins';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['voteID', 'party', 'creator'], 'required'],
            [['creator'], 'integer'],
            [['voteID'], 'string', 'max' => 20],
            [['party'], 'string', 'max' => 12],
            [['session'], 'string', 'max' => 100],
            [['voteID', 'party', 'creator'], 'unique', 'targetAttribute' => ['voteID', 'party', 'creator']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'voteID' => '投票識別碼',
            'party' => '投票者組別',
            'creator' => '登入對象(投票者)',
            'session' => '登入session',
        ];
    }

    /**
     * 檢查投票密碼是否已有其他裝置的有效 session
     *
     * @param int $creator 投票密碼 record id
     * @param string|null $currentSessionId 目前請求的 session id（相同則視為同一裝置）
     * @return bool
     */
    public static function hasActiveSession(int $creator, ?string $currentSessionId = null): bool
    {
        $login = static::findOne(['creator' => $creator]);
        if ($login === null || $login->session === null || $login->session === '') {
            return false;
        }

        if ($currentSessionId !== null && $login->session === $currentSessionId) {
            return false;
        }

        $sessionFile = Yii::getAlias('@runtime/sessions/sess_' . $login->session);
        clearstatcache(true, $sessionFile);
        $lastActivity = is_file($sessionFile) ? @filemtime($sessionFile) : false;
        if ($lastActivity === false) {
            return false;
        }

        // 已登入的投票者每次請求都會更新 anon 逾時並寫回 session 檔。
        // 超過 anon authTimeout 未活動時，原裝置的登入已失效；session 檔可能要等 GC 才刪除，
        // 不可因此持續擋住同一密碼重新登入（例如未完成投票就關閉瀏覽器）。
        return (time() - $lastActivity) < static::anonAuthTimeout();
    }

    /**
     * 匿名投票登入的閒置逾時秒數，與 anon 元件的 authTimeout 一致
     */
    protected static function anonAuthTimeout(): int
    {
        $timeout = Yii::$app->has('anon') ? (int) (Yii::$app->anon->authTimeout ?? 0) : 0;

        return $timeout > 0 ? $timeout : 1800;
    }
}
