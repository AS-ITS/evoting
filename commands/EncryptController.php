<?php
/**
 * 加密工具 Console 命令
 *
 * 用於加密敏感配置值並更新到 .env 檔案
 *
 * @since 2026-01-14
 * @version 1.0
 */

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use app\config\ConfigManager;

class EncryptController extends Controller
{
    /**
     * @var bool 是否顯示詳細輸出
     */
    public $verbose = false;

    /**
     * {@inheritdoc}
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['verbose']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'v' => 'verbose',
        ]);
    }

    /**
     * 加密資料庫密碼
     *
     * 將明文資料庫密碼加密並更新到 .env 檔案
     *
     * @param string|null $password 資料庫密碼（若不提供則提示輸入）
     * @return int 退出碼
     */
    public function actionDbPassword($password = null)
    {
        $this->stdout("加密資料庫密碼\n", Console::FG_CYAN);
        $this->stdout(str_repeat("=", 60) . "\n\n");

        try {
            // 如果沒有提供密碼，提示輸入
            if ($password === null) {
                $password = $this->prompt('請輸入資料庫密碼:', [
                    'required' => true,
                ]);
            }

            $cm = ConfigManager::getInstance();
            $encrypted = $cm->encrypt($password);

            $this->stdout("原始密碼: ");
            $this->stdout(str_repeat('●', strlen($password)) . "\n", Console::FG_YELLOW);
            $this->stdout("加密結果: {$encrypted}\n\n", Console::FG_GREEN);

            $this->stdout("請將以下內容更新到 .env 檔案：\n", Console::FG_CYAN);
            $this->stdout("DB_PASSWORD={$encrypted}\n\n", Console::BOLD);

            // 詢問是否自動更新
            if ($this->confirm('是否自動更新到 .env 檔案？')) {
                $this->updateEnvFile('DB_PASSWORD', $encrypted);
                $this->stdout("✅ .env 檔案已更新\n", Console::FG_GREEN);
            }

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("\n💥 加密失敗：\n", Console::FG_RED);
            $this->stderr($e->getMessage() . "\n");

            if ($this->verbose) {
                $this->stderr("\n堆疊追蹤：\n");
                $this->stderr($e->getTraceAsString() . "\n");
            }

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 加密 SMTP 密碼
     *
     * 將明文 SMTP 密碼加密並更新到 .env 檔案
     *
     * @param string|null $password SMTP 密碼（若不提供則提示輸入）
     * @return int 退出碼
     */
    public function actionSmtpPassword($password = null)
    {
        $this->stdout("加密 SMTP 密碼\n", Console::FG_CYAN);
        $this->stdout(str_repeat("=", 60) . "\n\n");

        try {
            if ($password === null) {
                $password = $this->prompt('請輸入 SMTP 密碼:', [
                    'required' => true,
                ]);
            }

            $cm = ConfigManager::getInstance();
            $encrypted = $cm->encrypt($password);

            $this->stdout("原始密碼: ");
            $this->stdout(str_repeat('●', strlen($password)) . "\n", Console::FG_YELLOW);
            $this->stdout("加密結果: {$encrypted}\n\n", Console::FG_GREEN);

            $this->stdout("請將以下內容更新到 .env 檔案：\n", Console::FG_CYAN);
            $this->stdout("SMTP_PASSWORD={$encrypted}\n\n", Console::BOLD);

            if ($this->confirm('是否自動更新到 .env 檔案？')) {
                $this->updateEnvFile('SMTP_PASSWORD', $encrypted);
                $this->stdout("✅ .env 檔案已更新\n", Console::FG_GREEN);
            }

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("\n💥 加密失敗：\n", Console::FG_RED);
            $this->stderr($e->getMessage() . "\n");

            if ($this->verbose) {
                $this->stderr("\n堆疊追蹤：\n");
                $this->stderr($e->getTraceAsString() . "\n");
            }

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 加密任意文字
     *
     * 將提供的明文加密（通用工具）
     *
     * @param string|null $text 要加密的文字
     * @return int 退出碼
     */
    public function actionText($text = null)
    {
        $this->stdout("加密文字工具\n", Console::FG_CYAN);
        $this->stdout(str_repeat("=", 60) . "\n\n");

        try {
            if ($text === null) {
                $text = $this->prompt('請輸入要加密的文字:', [
                    'required' => true,
                ]);
            }

            $cm = ConfigManager::getInstance();
            $encrypted = $cm->encrypt($text);

            $this->stdout("原始文字: ");
            $this->stdout(str_repeat('●', strlen($text)) . "\n", Console::FG_YELLOW);
            $this->stdout("加密結果: {$encrypted}\n\n", Console::FG_GREEN);

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("\n💥 加密失敗：\n", Console::FG_RED);
            $this->stderr($e->getMessage() . "\n");

            if ($this->verbose) {
                $this->stderr("\n堆疊追蹤：\n");
                $this->stderr($e->getTraceAsString() . "\n");
            }

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 測試解密
     *
     * 測試加密的值是否能正確解密
     *
     * @param string $key 配置鍵名（如 DB_PASSWORD）
     * @return int 退出碼
     */
    public function actionTest($key)
    {
        $this->stdout("測試解密\n", Console::FG_CYAN);
        $this->stdout(str_repeat("=", 60) . "\n\n");

        try {
            $cm = ConfigManager::getInstance();
            $value = $cm->get($key);

            if ($value === null || $value === '') {
                $this->stdout("⚠️  配置項目 {$key} 未設定或為空\n", Console::FG_YELLOW);
                return ExitCode::OK;
            }

            $this->stdout("配置鍵名: {$key}\n");
            $this->stdout("解密結果: ");
            $this->stdout(str_repeat('●', strlen($value)) . " (長度: " . strlen($value) . ")\n", Console::FG_GREEN);
            $this->stdout("\n✅ 解密成功\n", Console::FG_GREEN);

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("\n💥 解密失敗：\n", Console::FG_RED);
            $this->stderr($e->getMessage() . "\n");

            if ($this->verbose) {
                $this->stderr("\n堆疊追蹤：\n");
                $this->stderr($e->getTraceAsString() . "\n");
            }

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 更新 .env 檔案中的指定鍵值
     *
     * @param string $key 配置鍵名
     * @param string $value 配置值
     * @return bool 是否成功
     */
    private function updateEnvFile(string $key, string $value): bool
    {
        $envFile = dirname(__DIR__) . '/.env';

        if (!file_exists($envFile)) {
            $this->stderr("錯誤：.env 檔案不存在\n", Console::FG_RED);
            return false;
        }

        $content = file_get_contents($envFile);
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

        if (preg_match($pattern, $content)) {
            // 更新現有鍵值
            $newContent = preg_replace($pattern, "{$key}={$value}", $content);
        } else {
            // 新增鍵值
            $newContent = $content . "\n{$key}={$value}\n";
        }

        return file_put_contents($envFile, $newContent) !== false;
    }
}
