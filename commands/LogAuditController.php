<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Logs;

/**
 * 操作日誌稽核工具（唯讀；建議在 DB 離線副本執行）
 */
class LogAuditController extends Controller
{
    /**
     * 掃描匿名登入 log 是否曾寫入明文 password 欄位
     *
     * ```bash
     * php yii log-audit/scan-login-password-logs --limit=5000
     * ```
     *
     * @param int $limit 掃描筆數上限
     * @return int
     */
    public function actionScanLoginPasswordLogs($limit = 5000)
    {
        $this->stdout("掃描 LOGIN_PASSWORD / LOGIN_PASSWORD_FAIL 是否含明文 password 欄位...\n\n");

        $query = Logs::find()
            ->where(['type' => [Logs::LOGIN_PASSWORD, Logs::LOGIN_PASSWORD_FAIL]])
            ->orderBy(['id' => SORT_DESC])
            ->limit((int) $limit);

        $total = 0;
        $suspect = 0;

        foreach ($query->each(200) as $log) {
            $total++;
            if ($this->contextContainsPlaintextPassword($log->context)) {
                $suspect++;
                $this->stdout(
                    "  [可疑] id={$log->id} type={$log->type} voteID={$log->voteID} created_at={$log->created_at}\n",
                    \yii\helpers\Console::FG_YELLOW
                );
            }
        }

        $this->stdout("\n掃描完成：共 {$total} 筆，可疑 {$suspect} 筆。\n");
        if ($suspect > 0) {
            $this->stdout("請依 SETUP.md 與 SECURITY.md 進行受控處置（隔離、輪替金鑰、通知維運）。\n", \yii\helpers\Console::FG_YELLOW);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("未發現含 \"password\" 鍵的 context。\n", \yii\helpers\Console::FG_GREEN);
        return ExitCode::OK;
    }

    private function contextContainsPlaintextPassword(string $context): bool
    {
        if ($context === '') {
            return false;
        }

        $decoded = json_decode($context, true);
        if (!is_array($decoded)) {
            return stripos($context, '"password"') !== false;
        }

        return array_key_exists('password', $decoded)
            && is_string($decoded['password'])
            && $decoded['password'] !== '';
    }
}
