<?php
namespace app\models;

use Yii;

class FormAnon extends \yii\base\Model
{
    /** @var string $voteID 投票識別碼 */
    public $voteID;
    /** @var string $password 投票密碼 */
    public $password;
    /** @var null|string $session 投票場次碼 */
    public $session;
    /** @var false|object $_identity */
    private $_identity = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // password is required
            [['voteID', 'password'], 'required', 'message' => Yii::t('app', '密碼不能為空')],
            // votes有設定場次碼才必填
            [['session'], 'required', 'message' => Yii::t('app', '場次碼不能為空'), 
            'when' => function ($model) {
                $votes = new FormVotes;
                $voteInfo = $votes->getVoteInfo($this->voteID);
                return !empty($voteInfo->session);
            }],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
            // session is validated by validateSession()
            ['session', 'validateSession'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'session' => Yii::t('app', '場次碼'),
            'password' => Yii::t('app', '密碼'),
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) { // 確定沒有錯誤
            $FormPasswords = new FormPasswords;
            $passwdData = $FormPasswords->getPasswd($this->voteID, $this->password);
            if (is_null($passwdData) || !is_array($passwdData)) {
                $this->addError($attribute, Yii::t('app', '您輸入的投票密碼不正確，請再試一次').'！');
            } elseif (Logins::hasActiveSession((int) $passwdData['id'], Yii::$app->session->id)) {
                $this->addError(
                    $attribute,
                    Yii::t('app', '此投票密碼已在其他裝置登入中，請先於原裝置登出後再試。')
                );
            } else {
                $this->_identity = new Yii::$app->anon->identityClass;
                $this->_identity->setAuthData($passwdData);
            }
        }
    }

    /**
     * 場次碼驗證
     */
    public function validateSession($attribute, $params)
    {
        
        if (!$this->hasErrors()) { // 確定沒有錯誤
            $votes = new FormVotes;
            $voteInfo = $votes->getVoteInfo($this->voteID);
            if ($voteInfo->session != $this->session) {
                $this->addError($attribute, Yii::t('app', '您輸入的投票場次碼不正確，請再試一次').'！');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            $loggedIn = Yii::$app->anon->login($this->_identity, Yii::$app->anon->authTimeout);
            if ($loggedIn) {
                // afterLogin 已寫入舊 session id；regenerate 後必須同步，否則清除登入會砍錯檔
                Yii::$app->session->regenerateID(true);
                self::syncLoginSessionId();
            }
            return $loggedIn;
        }
        return false;
    }

    /**
     * 將 Logins.session 更新為目前 PHP session id（regenerateID 之後呼叫）
     */
    public static function syncLoginSessionId(): void
    {
        if (Yii::$app->anon->isGuest) {
            return;
        }
        $authData = Yii::$app->anon->identity->getAuthData();
        if (empty($authData['id'])) {
            return;
        }
        $login = Logins::findOne(['creator' => $authData['id']]);
        if ($login === null) {
            return;
        }
        $currentId = (string) Yii::$app->session->id;
        if ($login->session === $currentId) {
            return;
        }
        $login->session = $currentId;
        $login->save(false);
    }

    /**
     * 供操作日誌使用的安全 context（不含明文投票密碼）
     *
     * @param array|null $authData 登入成功後的 authData（可含 id）
     * @return array
     */
    public function buildLoginLogContext(?array $authData = null): array
    {
        $context = [];
        if ($this->voteID !== null && $this->voteID !== '') {
            $context['voteID'] = $this->voteID;
        }
        if ($authData !== null && isset($authData['id'])) {
            $context['passwordId'] = (int) $authData['id'];
        }
        if (!empty($this->errors)) {
            $context['errors'] = $this->errors;
        }
        return $context;
    }
}
