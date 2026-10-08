<?php

namespace app\models;

use Yii;
use app\components\TotpService;
use app\components\SensitiveReauth;

/**
 * 管理員 TOTP 綁定表單
 */
class FormTotpSetup extends \yii\base\Model
{
    public const STEP_GENERATE = 'generate';
    public const STEP_CONFIRM = 'confirm';
    public const STEP_DISABLE = 'disable';

    public $password;
    public $totpCode;
    public $step = self::STEP_GENERATE;

    public function rules()
    {
        return [
            [['step'], 'required'],
            [['step'], 'in', 'range' => [self::STEP_GENERATE, self::STEP_CONFIRM, self::STEP_DISABLE]],
            [['password'], 'required', 'on' => [self::STEP_GENERATE, self::STEP_DISABLE]],
            [['totpCode'], 'required', 'on' => [self::STEP_CONFIRM]],
            [['totpCode'], 'match', 'pattern' => '/^\d{6}$/', 'on' => [self::STEP_CONFIRM]],
            [['password', 'totpCode'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'password' => Yii::t('app', '管理員密碼'),
            'totpCode' => Yii::t('app', '驗證器代碼（6 碼）'),
        ];
    }

    public static function supportsTotpColumns(): bool
    {
        $probe = new Users();
        return $probe->hasAttribute('totp_secret') && $probe->hasAttribute('totp_enabled');
    }

    public function process(Users $user): bool
    {
        if (!static::supportsTotpColumns()) {
            $this->addError('password', '資料庫尚未執行 TOTP migration，請聯絡系統管理員。');
            return false;
        }

        if ($this->step === self::STEP_GENERATE) {
            return $this->processGenerate($user);
        }

        if ($this->step === self::STEP_CONFIRM) {
            return $this->processConfirm($user);
        }

        return $this->processDisable($user);
    }

    private function processGenerate(Users $user): bool
    {
        if (!$user->validatePassword($this->password)) {
            $this->addError('password', Yii::t('app', '密碼不正確。'));
            return false;
        }

        $secret = TotpService::generateSecret();
        Yii::$app->session->set('totp.pending_secret', $secret);
        Yii::$app->session->set('totp.pending_cn', $user->cn);

        return true;
    }

    private function processConfirm(Users $user): bool
    {
        $pendingSecret = Yii::$app->session->get('totp.pending_secret');
        $pendingCn = Yii::$app->session->get('totp.pending_cn');

        if ($pendingSecret === null || $pendingCn !== $user->cn) {
            $this->addError('totpCode', Yii::t('app', '綁定流程已逾時，請重新開始。'));
            return false;
        }

        $tempUser = clone $user;
        $tempUser->totp_secret = $pendingSecret;
        $tempUser->totp_enabled = '1';

        if (!TotpService::verifyForUser($tempUser, $this->totpCode)) {
            $this->addError('totpCode', Yii::t('app', '驗證器代碼不正確，請確認裝置時間是否正確。'));
            return false;
        }

        $user->totp_secret = $pendingSecret;
        $user->totp_enabled = '1';
        if (!$user->save(false, ['totp_secret', 'totp_enabled'])) {
            $this->addError('totpCode', Yii::t('app', '儲存失敗，請稍後再試。'));
            return false;
        }

        Yii::$app->session->remove('totp.pending_secret');
        Yii::$app->session->remove('totp.pending_cn');

        return true;
    }

    private function processDisable(Users $user): bool
    {
        if (!SensitiveReauth::verify($this->password, $this->totpCode)) {
            $this->addError('password', SensitiveReauth::failureMessage($user));
            return false;
        }

        $user->totp_secret = null;
        $user->totp_enabled = '0';
        if (!$user->save(false, ['totp_secret', 'totp_enabled'])) {
            $this->addError('password', Yii::t('app', '停用失敗，請稍後再試。'));
            return false;
        }

        Yii::$app->session->remove('totp.pending_secret');
        Yii::$app->session->remove('totp.pending_cn');

        return true;
    }
}
