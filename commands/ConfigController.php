<?php
/**
 * 配置管理 Console 命令
 *
 * 提供配置驗證、健康檢查等功能
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
use app\config\ConfigValidator;

class ConfigController extends Controller
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
     * 驗證系統配置
     *
     * 檢查配置完整性與正確性
     *
     * @return int 退出碼（0=成功，1=失敗）
     */
    public function actionValidate()
    {
        $this->stdout("正在驗證系統配置...\n", Console::FG_CYAN);

        try {
            $cm = ConfigManager::getInstance();
            $errors = $cm->validate(['strict' => false]);

            if (empty($errors)) {
                $this->stdout("\n✅ 配置驗證通過\n", Console::FG_GREEN);
                $this->stdout("所有必要的配置項目都已正確設定。\n");
                return ExitCode::OK;
            } else {
                $this->stdout("\n❌ 配置驗證失敗\n", Console::FG_RED);
                $this->stdout("發現以下問題：\n\n", Console::FG_RED);

                foreach ($errors as $i => $error) {
                    $this->stdout(sprintf("  %d. %s\n", $i + 1, $error), Console::FG_RED);
                }

                $this->stdout("\n請檢查 .env 檔案。\n");
                return ExitCode::DATAERR;
            }
        } catch (\Exception $e) {
            $this->stderr("\n💥 驗證過程發生錯誤：\n", Console::FG_RED);
            $this->stderr($e->getMessage() . "\n");

            if ($this->verbose) {
                $this->stderr("\n堆疊追蹤：\n");
                $this->stderr($e->getTraceAsString() . "\n");
            }

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 顯示配置健康報告
     *
     * 提供完整的配置健康狀態分析
     *
     * @return int 退出碼（0=健康，1=警告，2=嚴重）
     */
    public function actionHealth()
    {
        $this->stdout("配置健康檢查\n", Console::FG_CYAN);
        $this->stdout(str_repeat("=", 60) . "\n\n");

        try {
            $cm = ConfigManager::getInstance();
            $report = $cm->getHealthReport();

            // 顯示環境資訊
            $this->stdout("環境資訊：\n", Console::FG_YELLOW);
            $this->stdout(sprintf("  環境：%s\n", $cm->getCurrentEnvironment()));
            $this->stdout(sprintf("  正式環境：%s\n", $cm->isProduction() ? '是' : '否'));
            $this->stdout(sprintf("  測試環境：%s\n", $cm->isTestEnvironment() ? '是' : '否'));
            $this->stdout(sprintf("  開發環境：%s\n", $cm->isDevelopmentEnvironment() ? '是' : '否'));
            $this->stdout("\n");

            // 顯示健康狀態
            $this->stdout("健康狀態：", Console::FG_YELLOW);
            switch ($report['status']) {
                case 'healthy':
                    $this->stdout("✅ 健康\n", Console::FG_GREEN);
                    break;
                case 'warning':
                    $this->stdout("⚠️  警告\n", Console::FG_YELLOW);
                    break;
                case 'critical':
                    $this->stdout("❌ 嚴重\n", Console::FG_RED);
                    break;
            }

            $this->stdout("\n");

            // 顯示錯誤
            if (!empty($report['errors'])) {
                $this->stdout("錯誤項目：\n", Console::FG_RED);
                foreach ($report['errors'] as $error) {
                    $this->stdout(sprintf("  ❌ %s\n", $error), Console::FG_RED);
                }
                $this->stdout("\n");
            }

            // 顯示警告
            if (!empty($report['warnings'])) {
                $this->stdout("警告項目：\n", Console::FG_YELLOW);
                foreach ($report['warnings'] as $warning) {
                    $this->stdout(sprintf("  ⚠️  %s\n", $warning), Console::FG_YELLOW);
                }
                $this->stdout("\n");
            }

            // 顯示時間戳記
            $this->stdout(sprintf("檢查時間：%s\n", $report['timestamp']));

            $this->stdout("\n" . str_repeat("=", 60) . "\n");

            // 根據狀態返回退出碼
            if ($report['status'] === 'healthy') {
                return ExitCode::OK;
            } elseif ($report['status'] === 'warning') {
                return ExitCode::OK;  // 警告不視為錯誤
            } else {
                return ExitCode::DATAERR;
            }
        } catch (\Exception $e) {
            $this->stderr("\n💥 健康檢查過程發生錯誤：\n", Console::FG_RED);
            $this->stderr($e->getMessage() . "\n");

            if ($this->verbose) {
                $this->stderr("\n堆疊追蹤：\n");
                $this->stderr($e->getTraceAsString() . "\n");
            }

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 顯示配置資訊
     *
     * 顯示目前的配置狀態（隱藏敏感資訊）
     *
     * @return int 退出碼
     */
    public function actionInfo()
    {
        $this->stdout("系統配置資訊\n", Console::FG_CYAN);
        $this->stdout(str_repeat("=", 60) . "\n\n");

        try {
            $cm = ConfigManager::getInstance();

            // 環境資訊
            $this->stdout("環境配置：\n", Console::FG_YELLOW);
            $this->stdout(sprintf("  APP_ENV: %s\n", getenv('APP_ENV') ?: '(未設定)'));
            $this->stdout(sprintf("  當前環境: %s\n", $cm->getCurrentEnvironment()));
            $this->stdout(sprintf("  YII_DEBUG: %s\n", YII_DEBUG ? 'true' : 'false'));
            $this->stdout(sprintf("  YII_ENV: %s\n", YII_ENV));
            $this->stdout("\n");

            // 資料庫配置
            $dbConfig = $cm->getDatabaseConfig();
            $this->stdout("資料庫配置：\n", Console::FG_YELLOW);
            $this->stdout(sprintf("  DSN: %s\n", $dbConfig['dsn'] ?? '(未設定)'));
            $this->stdout(sprintf("  使用者: %s\n", $dbConfig['username'] ?? '(未設定)'));
            $this->stdout(sprintf("  密碼: %s\n", isset($dbConfig['password']) && !empty($dbConfig['password']) ? '●●●●●●●●' : '(未設定)'));
            $this->stdout("\n");

            // 金鑰狀態
            $this->stdout("安全金鑰狀態：\n", Console::FG_YELLOW);
            $cookieKey = $cm->get('COOKIE_VALIDATION_KEY', null);

            $this->stdout(sprintf("  COOKIE_VALIDATION_KEY: %s (長度: %d)\n",
                $cookieKey ? '✅ 已設定' : '❌ 未設定',
                $cookieKey ? strlen($cookieKey) : 0
            ));

            // 顯示 MASTER_KEY 狀態
            $masterKey = getenv('MASTER_KEY');
            $this->stdout("\n  主加密金鑰（用於解密 .env 中的敏感資料）：\n", Console::FG_CYAN);
            $this->stdout(sprintf("  MASTER_KEY: %s (長度: %d)\n",
                $masterKey ? '✅ 已設定' : '❌ 未設定',
                $masterKey ? strlen($masterKey) : 0
            ));
            $this->stdout("\n");

            // 路徑配置
            $this->stdout("路徑配置：\n", Console::FG_YELLOW);
            $this->stdout(sprintf("  專案根目錄: %s\n", dirname(__DIR__)));

            // 安全地存取 Config 路徑
            $conf = $cm->getConfigInstance();
            if (isset($conf->path['filePool'])) {
                $this->stdout(sprintf("  檔案池: %s\n", $conf->path['filePool']));
            }
            $this->stdout("\n");

            $this->stdout(str_repeat("=", 60) . "\n");

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("\n💥 讀取配置資訊時發生錯誤：\n", Console::FG_RED);
            $this->stderr($e->getMessage() . "\n");

            if ($this->verbose) {
                $this->stderr("\n堆疊追蹤：\n");
                $this->stderr($e->getTraceAsString() . "\n");
            }

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    public function actionClearCache()
    {
        $this->stdout("正在清除配置快取...\n", Console::FG_CYAN);

        try {
            $cm = ConfigManager::getInstance();

            // 清除 ConfigManager 快取
            $cm->clearCache();
            $this->stdout("✅ 配置快取已清除\n", Console::FG_GREEN);

            // 清除 Yii 快取
            if (Yii::$app->has('cache')) {
                Yii::$app->cache->flush();
                $this->stdout("✅ Yii 快取已清除\n", Console::FG_GREEN);
            }

            $this->stdout("\n配置快取已成功清除。\n");
            $this->stdout("下次請求將重新載入並解密配置。\n");

            return ExitCode::OK;
        } catch (\Exception $e) {
            $this->stderr("\n💥 清除快取時發生錯誤：\n", Console::FG_RED);
            $this->stderr($e->getMessage() . "\n");

            if ($this->verbose) {
                $this->stderr("\n堆疊追蹤：\n");
                $this->stderr($e->getTraceAsString() . "\n");
            }

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
