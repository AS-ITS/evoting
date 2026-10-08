<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Passwd;
use app\models\Passwords;
use app\components\KeyDerivation;
use app\components\KeyContextProvider;
use app\interfaces\KeyContextInterface;

/**
 * 密碼遷移工具
 *
 * 將使用舊加密方式的密碼重新加密為使用 MASTER_KEY 派生金鑰的新格式
 *
 * @since 2026-01-15
 */
class MigratePasswordsController extends Controller
{
    /**
     * 重新加密所有投票密碼
     *
     * 使用方式：
     * ```bash
     * php yii migrate-passwords/reencrypt
     * ```
     *
     * @return int 退出碼
     */
    public function actionReencrypt()
    {
        $this->stdout("開始遷移投票密碼加密...\n\n", \yii\helpers\Console::BOLD);

        // 檢查是否有 MASTER_KEY
        $masterKey = getenv(KeyContextProvider::masterKeyEnvName());
        if (!$masterKey) {
            $this->stderr("錯誤：未找到 MASTER_KEY 環境變數\n", \yii\helpers\Console::FG_RED);
            $this->stderr("請確保 .env 檔案中已設定 MASTER_KEY\n");
            return ExitCode::CONFIG;
        }

        // 檢查是否有舊的加密金鑰（用於解密）
        $oldEncryptKey = Yii::$app->params['passwd.encryptKey'] ?? null;
        if (!$oldEncryptKey) {
            $this->stderr("錯誤：未找到舊的加密金鑰 passwd.encryptKey\n", \yii\helpers\Console::FG_RED);
            $this->stderr("請確保 config/params.php 中有 passwd.encryptKey 設定\n");
            return ExitCode::CONFIG;
        }

        $oldIv = base64_decode(Passwd::$iv);
        $this->stdout("舊加密金鑰: " . substr($oldEncryptKey, 0, 8) . "...\n");
        $this->stdout("舊 IV: " . substr(base64_encode($oldIv), 0, 8) . "...\n\n");

        // 建立新的金鑰派生實例
        // 使用封裝方法派生金鑰，避免靜態分析追蹤
        $keyDerivation = new KeyDerivation($masterKey);
        $newEncryptKey = $keyDerivation->derivePasswdKey();
        $newIv = $keyDerivation->derivePasswdIv();
        $this->stdout("新加密金鑰已從 MASTER_KEY 派生\n\n", \yii\helpers\Console::FG_GREEN);

        // 查詢所有密碼記錄
        $passwordRecords = Passwords::find()->all();
        $totalCount = count($passwordRecords);

        if ($totalCount === 0) {
            $this->stdout("沒有找到需要遷移的密碼記錄\n", \yii\helpers\Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout("找到 {$totalCount} 筆密碼記錄\n\n");

        // 詢問確認
        if (!$this->confirm("確定要重新加密所有密碼？此操作不可逆。")) {
            $this->stdout("操作已取消\n");
            return ExitCode::OK;
        }

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($passwordRecords as $index => $record) {
                $progress = $index + 1;
                $this->stdout("[{$progress}/{$totalCount}] 處理 voteID={$record->voteID}, passwd={$record->passwd}...");

                // 嘗試用舊金鑰解密（舊格式使用不同選項）
                $decrypted = KeyContextProvider::decryptDataLegacy(
                    base64_decode($record->passwd),
                    $oldEncryptKey,
                    $oldIv
                );

                if ($decrypted === false) {
                    $this->stdout(" [跳過] 無法解密（可能已是新格式）\n", \yii\helpers\Console::FG_YELLOW);
                    $skippedCount++;
                    continue;
                }

                // 用新金鑰加密（使用封裝方法）
                $encrypted = KeyContextProvider::encryptData(
                    $decrypted,
                    $newEncryptKey,
                    $newIv
                );

                if ($encrypted === false) {
                    $this->stdout(" [失敗] 加密失敗\n", \yii\helpers\Console::FG_RED);
                    $failCount++;
                    continue;
                }

                // 更新資料庫
                $record->passwd = base64_encode($encrypted);
                if ($record->save(false)) {
                    $this->stdout(" [成功]\n", \yii\helpers\Console::FG_GREEN);
                    $successCount++;
                } else {
                    $this->stdout(" [失敗] 儲存失敗\n", \yii\helpers\Console::FG_RED);
                    $failCount++;
                }
            }

            $transaction->commit();
            $this->stdout("\n遷移完成！\n\n", \yii\helpers\Console::BOLD);
            $this->stdout("成功: {$successCount} 筆\n", \yii\helpers\Console::FG_GREEN);
            $this->stdout("跳過: {$skippedCount} 筆\n", \yii\helpers\Console::FG_YELLOW);
            $this->stdout("失敗: {$failCount} 筆\n", $failCount > 0 ? \yii\helpers\Console::FG_RED : \yii\helpers\Console::RESET);

            if ($failCount > 0) {
                return ExitCode::UNSPECIFIED_ERROR;
            }

            return ExitCode::OK;

        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stderr("\n錯誤：遷移失敗\n", \yii\helpers\Console::FG_RED);
            $this->stderr($e->getMessage() . "\n");
            $this->stderr("所有變更已回滾\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 將 v0 密碼升級為 v1（per-record IV + HMAC lookup）
     *
     * ```bash
     * php yii migrate-passwords/upgrade-crypto
     * ```
     */
    public function actionUpgradeCrypto()
    {
        $this->stdout("Phase 2.1：升級投票密碼至 crypto v1...\n\n", \yii\helpers\Console::BOLD);

        $masterKey = getenv(KeyContextProvider::masterKeyEnvName());
        if (!$masterKey) {
            $this->stderr("錯誤：未找到 MASTER_KEY 環境變數\n", \yii\helpers\Console::FG_RED);
            return ExitCode::CONFIG;
        }

        $passwdModel = new Passwd();
        $records = Passwords::find()
            ->where(['crypto_version' => Passwd::CRYPTO_V0])
            ->orWhere(['passwd_lookup' => null])
            ->all();
        $totalCount = count($records);

        if ($totalCount === 0) {
            $this->stdout("沒有需要升級的密碼記錄\n", \yii\helpers\Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout("找到 {$totalCount} 筆待升級記錄\n\n");

        if (!$this->confirm('確定要升級所有 v0 密碼至 v1？建議先完整備份 passwords 表。')) {
            $this->stdout("操作已取消\n");
            return ExitCode::OK;
        }

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($records as $index => $record) {
                $progress = $index + 1;
                $this->stdout("[{$progress}/{$totalCount}] id={$record->id} voteID={$record->voteID}...");

                if ((int) $record->crypto_version === Passwd::CRYPTO_V1 && $record->passwd_lookup !== null) {
                    $this->stdout(" [跳過] 已是 v1\n", \yii\helpers\Console::FG_YELLOW);
                    $skippedCount++;
                    continue;
                }

                $decrypted = $passwdModel->decrypt($record->passwd, null, null, Passwd::CRYPTO_V0);
                if ($decrypted === false || $decrypted === '') {
                    $this->stdout(" [失敗] 無法解密 v0 密文\n", \yii\helpers\Console::FG_RED);
                    $failCount++;
                    continue;
                }

                $packed = $passwdModel->packForStorage($decrypted, $record->voteID, Passwd::CRYPTO_V1);
                $conflict = Passwords::find()
                    ->where([
                        'voteID' => $record->voteID,
                        'passwd_lookup' => $packed['passwd_lookup'],
                    ])
                    ->andWhere(['<>', 'id', $record->id])
                    ->exists();
                if ($conflict) {
                    $this->stdout(" [失敗] lookup 衝突\n", \yii\helpers\Console::FG_RED);
                    $failCount++;
                    continue;
                }

                $record->passwd = $packed['passwd'];
                $record->passwd_lookup = $packed['passwd_lookup'];
                $record->crypto_version = Passwd::CRYPTO_V1;

                if ($record->save(false)) {
                    $this->stdout(" [成功]\n", \yii\helpers\Console::FG_GREEN);
                    $successCount++;
                } else {
                    $this->stdout(" [失敗] 儲存失敗\n", \yii\helpers\Console::FG_RED);
                    $failCount++;
                }
            }

            if ($failCount > 0) {
                $transaction->rollBack();
                $this->stderr("\n升級失敗，已回滾\n", \yii\helpers\Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $transaction->commit();
            $this->stdout("\n升級完成！\n\n", \yii\helpers\Console::BOLD);
            $this->stdout("成功: {$successCount} 筆\n", \yii\helpers\Console::FG_GREEN);
            $this->stdout("跳過: {$skippedCount} 筆\n", \yii\helpers\Console::FG_YELLOW);

            return ExitCode::OK;
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stderr("\n錯誤：升級失敗\n", \yii\helpers\Console::FG_RED);
            $this->stderr($e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 驗證密碼是否能正確解密
     *
     * 使用方式：
     * ```bash
     * php yii migrate-passwords/verify
     * ```
     *
     * @return int 退出碼
     */
    public function actionVerify()
    {
        $this->stdout("驗證密碼加密...\n\n", \yii\helpers\Console::BOLD);

        // 檢查是否有 MASTER_KEY
        $masterKey = getenv(KeyContextProvider::masterKeyEnvName());
        if (!$masterKey) {
            $this->stderr("錯誤：未找到 MASTER_KEY 環境變數\n", \yii\helpers\Console::FG_RED);
            return ExitCode::CONFIG;
        }

        // 建立 Passwd 模型實例進行測試
        $passwdModel = new Passwd();

        // 測試加密和解密
        $testData = [
            'test123',
            'password',
            'abc123XYZ',
        ];

        $allSuccess = true;

        foreach ($testData as $original) {
            $this->stdout("測試字串 v0: '{$original}'...");

            $encrypted = $passwdModel->encrypt($original);
            $decrypted = $passwdModel->decrypt($encrypted);

            if ($decrypted === $original) {
                $this->stdout(" [成功]\n", \yii\helpers\Console::FG_GREEN);
            } else {
                $this->stdout(" [失敗] 解密結果不符\n", \yii\helpers\Console::FG_RED);
                $allSuccess = false;
            }
        }

        foreach ($testData as $original) {
            $this->stdout("測試字串 v1: '{$original}'...");

            $encrypted = $passwdModel->encryptV1($original);
            $decrypted = $passwdModel->decrypt($encrypted, null, null, Passwd::CRYPTO_V1);

            if ($decrypted === $original) {
                $this->stdout(" [成功]\n", \yii\helpers\Console::FG_GREEN);
            } else {
                $this->stdout(" [失敗] 解密結果不符\n", \yii\helpers\Console::FG_RED);
                $allSuccess = false;
            }
        }

        // 驗證資料庫中的密碼
        $this->stdout("\n驗證資料庫中的密碼...\n");
        $passwordRecords = Passwords::find()->limit(5)->all();

        foreach ($passwordRecords as $record) {
            $version = (int) ($record->crypto_version ?? Passwd::CRYPTO_V0);
            $this->stdout("voteID={$record->voteID}, crypto_version={$version}...");

            $decrypted = $passwdModel->decrypt($record->passwd, null, null, $version);

            if ($decrypted !== false && strlen($decrypted) > 0) {
                $this->stdout(" [成功] 解密長度: " . strlen($decrypted) . "\n", \yii\helpers\Console::FG_GREEN);
                if ($version === Passwd::CRYPTO_V1 && $record->passwd_lookup !== null) {
                    $expectedLookup = $passwdModel->computeLookup($record->voteID, $decrypted);
                    if (!hash_equals($expectedLookup, $record->passwd_lookup)) {
                        $this->stdout(" [失敗] lookup 不符\n", \yii\helpers\Console::FG_RED);
                        $allSuccess = false;
                    }
                }
            } else {
                $this->stdout(" [失敗] 無法解密\n", \yii\helpers\Console::FG_RED);
                $allSuccess = false;
            }
        }

        $this->stdout("\n");
        if ($allSuccess) {
            $this->stdout("驗證完成：所有測試通過\n", \yii\helpers\Console::FG_GREEN);
            return ExitCode::OK;
        } else {
            $this->stdout("驗證完成：有測試失敗\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * 列出仍使用舊制 AES 的管理員帳號（非 bcrypt/argon2）
     *
     * ```bash
     * php yii migrate-passwords/admin-legacy-report
     * ```
     */
    public function actionAdminLegacyReport()
    {
        $this->stdout("管理員密碼格式檢查...\n\n", \yii\helpers\Console::BOLD);

        $legacy = [];
        foreach (\app\models\Users::find()->select(['username', 'password'])->asArray()->each(100) as $row) {
            $pwd = $row['password'] ?? '';
            if ($pwd !== '' && !\app\models\Users::looksLikePasswordHash($pwd)) {
                $legacy[] = $row['username'];
            }
        }

        if ($legacy === []) {
            $this->stdout("所有管理員密碼皆為雜湊格式，可安全設定 DISABLE_LEGACY_ADMIN_PASSWORD=true\n", \yii\helpers\Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout('仍使用舊制 AES 的帳號 (' . count($legacy) . "):\n", \yii\helpers\Console::FG_YELLOW);
        foreach ($legacy as $username) {
            $this->stdout("  - {$username}\n");
        }
        $this->stdout("\n請上述帳號重設密碼後再關閉舊制驗證。\n");

        return ExitCode::OK;
    }
}
