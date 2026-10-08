<?php

namespace app\components;

use yii\web\ForbiddenHttpException;

/**
 * SA 資料庫工具（DataTableModifier）功能開關
 *
 * 開源預設關閉；僅在明確設定 SA_DB_TOOLS_ENABLED=true 時啟用唯讀模式。
 * 寫入（CRUD）需額外設定 SA_DB_TOOLS_WRITABLE=true，且正式環境一律禁止。
 */
class SaDbTools
{
    /**
     * 是否啟用 SA 資料庫工具（唯讀查詢 + SELECT SQL）
     */
    public static function isEnabled(): bool
    {
        return filter_var(getenv('SA_DB_TOOLS_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * 是否允許 CRUD 寫入（僅非正式環境且明確啟用）
     */
    public static function isWritable(): bool
    {
        if (!static::isEnabled()) {
            return false;
        }

        $env = strtolower(getenv('APP_ENV') ?: '');
        if (in_array($env, ['production', 'prod', 'product'], true)) {
            return false;
        }

        return filter_var(getenv('SA_DB_TOOLS_WRITABLE') ?: 'false', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @throws ForbiddenHttpException
     */
    public static function assertEnabled(): void
    {
        if (!static::isEnabled()) {
            throw new ForbiddenHttpException('SA 資料庫工具未啟用。請設定 SA_DB_TOOLS_ENABLED=true（僅建議用於開發環境）。');
        }
    }

    /**
     * @throws ForbiddenHttpException
     */
    public static function assertWritable(): void
    {
        static::assertEnabled();
        if (!static::isWritable()) {
            throw new ForbiddenHttpException('SA 資料庫寫入功能未啟用（目前僅允許唯讀查詢）。');
        }
    }
}
