<?php

namespace app\models;

/**
 * 投票密碼管理端顯示（列表遮罩；匯出才解密）
 */
class PasswordDisplay
{
    /**
     * 管理 UI 遮罩標籤（不含明文）
     *
     * @param array<string, mixed> $row
     */
    public static function maskLabel(array $row): string
    {
        if (isset($row['sn']) && $row['sn'] !== '' && $row['sn'] !== null) {
            return '密碼#' . $row['sn'];
        }

        return '密碼ID ' . ($row['id'] ?? '—');
    }

    /**
     * 匯出用：還原明文（舊資料可能未加密）
     */
    public static function decryptForExport(string $stored, int $cryptoVersion = Passwd::CRYPTO_V0): string
    {
        if (strlen($stored) < 20) {
            return $stored;
        }

        return (new Passwd())->decrypt($stored, null, null, $cryptoVersion);
    }

    /**
     * 列表欄位：未解鎖遮罩，已解鎖回傳明文（由 view 負責 Html::encode）
     *
     * @param array<string, mixed> $row
     */
    public static function formatGridValue(array $row, bool $plaintextUnlocked): string
    {
        if (!$plaintextUnlocked) {
            return static::maskLabel($row);
        }

        $stored = (string) ($row['passwd'] ?? '');
        $version = (int) ($row['crypto_version'] ?? Passwd::CRYPTO_V0);

        return static::decryptForExport($stored, $version);
    }
}
