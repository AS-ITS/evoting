<?php

namespace app\commands;

use app\models\Users;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * 初始管理員帳號建立
 */
class AdminController extends Controller
{
    /** @var string|null */
    public $password;

    /** @var bool 從 stdin 讀取密碼（第一行），密碼不會出現在 ps 看得到的命令列 */
    public $passwordStdin = false;

    /**
     * {@inheritdoc}
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['password', 'passwordStdin']);
    }

    /**
     * 建立初始系統管理員 (sa)
     *
     * php yii admin/create
     * printf '%s\n' "$PASSWORD" | php yii admin/create --password-stdin
     * php yii admin/create --password='YourSecurePass1'   # 密碼會留在命令列，僅限手動使用
     */
    public function actionCreate()
    {
        if (Users::find()->where(['cn' => 'admin'])->exists()) {
            $this->stderr("admin 帳號已存在。\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($this->passwordStdin) {
            $this->password = rtrim((string) fgets(STDIN), "\r\n");
            if ($this->password === '') {
                $this->stderr("--password-stdin 未讀到密碼。\n", Console::FG_RED);
                return ExitCode::USAGE;
            }
        }

        $password = $this->password ?: $this->prompt('請設定 admin 密碼:', [
            'required' => true,
            'pattern' => '/^(?=.*[A-Za-z])(?=.*\d).{8,}$/',
            'error' => '密碼至少 8 字元，且須包含英文字母與數字。',
        ]);

        $user = new Users();
        $user->cn = 'admin';
        $user->name = '系統管理員';
        $user->roles = 'sa';
        $user->password_plain = $password;

        if (!$user->save()) {
            $this->stderr('建立失敗: ' . json_encode($user->errors, JSON_UNESCAPED_UNICODE) . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("已建立 admin 帳號（sa）。請妥善保存密碼。\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
