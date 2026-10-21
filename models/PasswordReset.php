<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * 管理員密碼重設 token（只存雜湊）。
 *
 * @property int $id
 * @property string $cn
 * @property string $token_hash
 * @property string $expires_at
 * @property string|null $used_at
 * @property string $created_at
 */
class PasswordReset extends ActiveRecord
{
    public const TTL_SECONDS = 1800;

    public static function tableName()
    {
        return 'password_reset';
    }

    /**
     * 作廢該帳號尚未使用的 token，並發出新 token。
     *
     * @return string 明文 token（只出現在信件連結）
     */
    public static function issue(string $cn): string
    {
        $now = date('Y-m-d H:i:s');
        self::updateAll(['used_at' => $now], ['and', ['cn' => $cn], ['used_at' => null]]);

        $token = bin2hex(random_bytes(32));
        $row = new self();
        $row->cn = $cn;
        $row->token_hash = hash('sha256', $token);
        $row->expires_at = date('Y-m-d H:i:s', time() + self::TTL_SECONDS);
        $row->created_at = $now;
        $row->save(false);

        return $token;
    }

    public static function findValid(string $token): ?self
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $row = self::findOne([
            'token_hash' => hash('sha256', $token),
            'used_at' => null,
        ]);
        if ($row === null || strtotime($row->expires_at) < time()) {
            return null;
        }

        return $row;
    }

    public function markUsed(): void
    {
        $this->updateAttributes(['used_at' => date('Y-m-d H:i:s')]);
    }
}
