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

        return file_exists($sessionFile);
    }
}
