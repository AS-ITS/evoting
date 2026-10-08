<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property string $cn 帳號
 * @property string $name 姓名
 * @property string $roles 角色
 * @property string $password 加密密碼
 * @property string|null $totp_secret TOTP secret（Base32）
 * @property string $totp_enabled 是否啟用 TOTP
 */
class Users extends \yii\db\ActiveRecord
{
    public $password_plain; // 明文密碼（用於表單輸入）

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cn', 'name'], 'required'],
            [['cn', 'name', 'roles'], 'string', 'max' => 20],
            [['password'], 'string', 'max' => 255],
            [['totp_secret'], 'string', 'max' => 255],
            [['totp_enabled'], 'in', 'range' => ['0', '1']],
            [['password_plain'], 'string', 'min' => 8, 'max' => 255, 'message' => '密碼長度至少需要 8 個字元'],
            // 密碼必須包含英文字母和數字
            [['password_plain'], 'match', 'pattern' => '/^(?=.*[A-Za-z])(?=.*\d).+$/', 'message' => '密碼必須包含英文字母和數字'],
            // 密碼不可與帳號相同
            [['password_plain'], 'validatePasswordNotSameAsUsername'],
            [['cn'], 'unique'],
        ];
    }

    /**
     * 驗證密碼不可與帳號相同或類似
     */
    public function validatePasswordNotSameAsUsername($attribute, $params)
    {
        if (empty($this->$attribute)) {
            return; // 如果密碼為空，跳過驗證（更新時留空表示不修改）
        }

        $password = strtolower($this->$attribute);
        $username = strtolower($this->cn);

        // 檢查密碼是否與帳號完全相同
        if ($password === $username) {
            $this->addError($attribute, '密碼不可與帳號相同');
            return;
        }

        // 檢查密碼是否包含帳號（帳號長度 >= 3 時才檢查）
        if (strlen($username) >= 3 && strpos($password, $username) !== false) {
            $this->addError($attribute, '密碼不可包含帳號');
            return;
        }

        // 檢查帳號是否包含密碼（密碼長度 >= 3 時才檢查）
        if (strlen($password) >= 3 && strpos($username, $password) !== false) {
            $this->addError($attribute, '密碼與帳號過於相似');
            return;
        }

        // 計算 Levenshtein 距離（編輯距離），檢查相似度
        $distance = levenshtein($password, $username);
        $maxLength = max(strlen($password), strlen($username));
        $similarity = 1 - ($distance / $maxLength);

        // 如果相似度超過 70%，視為過於相似
        if ($similarity > 0.7) {
            $this->addError($attribute, '密碼與帳號過於相似');
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'cn' => '帳號',
            'name' => '姓名',
            'roles' => '角色',
            'password' => '密碼',
            'password_plain' => '密碼',
        ];
    }

    /**
     * 產生密碼雜湊（單向，不可逆）
     * @param string $plainPassword 明文密碼
     * @return string 雜湊後的密碼
     */
    public function hashPassword($plainPassword)
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    /**
     * 驗證密碼
     *
     * 優先使用 password_verify() 驗證雜湊密碼；
     * 若不符則嘗試舊制 AES 可逆加密比對（漸進式遷移），
     * 成功後自動轉為雜湊並寫回資料庫。
     *
     * @param string $plainPassword 明文密碼
     * @return bool 密碼是否正確
     */
    public function validatePassword($plainPassword)
    {
        if (empty($this->password)) {
            return false;
        }

        // 新制：password_hash 雜湊
        if (password_verify($plainPassword, $this->password)) {
            // 演算法或 cost 更新時自動 rehash
            if (password_needs_rehash($this->password, PASSWORD_DEFAULT) && !$this->isNewRecord) {
                $this->updateAttributes(['password' => $this->hashPassword($plainPassword)]);
            }
            return true;
        }

        // 舊制：AES 可逆加密（漸進式遷移；正式環境可設 DISABLE_LEGACY_ADMIN_PASSWORD=true 關閉）
        if (self::isLegacyAdminPasswordDisabled()) {
            return false;
        }

        try {
            $decrypted = (new Passwd())->decrypt($this->password);
        } catch (\Throwable $e) {
            return false;
        }
        if ($decrypted !== false && $decrypted === $plainPassword) {
            if (!$this->isNewRecord) {
                $this->updateAttributes(['password' => $this->hashPassword($plainPassword)]);
            }
            return true;
        }

        return false;
    }

    /**
     * 是否停用舊制 AES 管理員密碼驗證（DISABLE_LEGACY_ADMIN_PASSWORD=true）
     */
    public static function isLegacyAdminPasswordDisabled(): bool
    {
        return filter_var(getenv('DISABLE_LEGACY_ADMIN_PASSWORD') ?: 'false', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * 密碼欄位是否為 bcrypt/argon2 雜湊格式
     */
    public static function looksLikePasswordHash(string $stored): bool
    {
        return (bool) preg_match('/^\$(2[aby]|argon2)/', $stored);
    }

    /**
     * 統計仍使用舊制 AES 的管理員帳號數
     */
    public static function countLegacyPasswordUsers(): int
    {
        $count = 0;
        foreach (self::find()->select(['username', 'password'])->asArray()->each(100) as $row) {
            $pwd = $row['password'] ?? '';
            if ($pwd !== '' && !self::looksLikePasswordHash($pwd)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 儲存前處理
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // 如果有輸入明文密碼，則雜湊後儲存
            if (!empty($this->password_plain)) {
                $this->password = $this->hashPassword($this->password_plain);
            }
            return true;
        }
        return false;
    }

    public function submitForm($postData)
    {
        $this->load($postData);
        $roles = isset($postData[$this->formName()]['roles']) ? $postData[$this->formName()]['roles'] : [];
        $this->roles = is_array($roles) ? implode(',', $roles) : null;
        return $this->save();
    }
}
