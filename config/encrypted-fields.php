<?php
/**
 * 需要加密的欄位清單
 *
 * 此檔案定義了系統中所有需要加密處理的敏感欄位
 * 必須以加密形式存儲
 *
 * @since 2026-01-13
 */

return [
    // 資料庫相關
    'db_password',           // 主資料庫密碼

    // 應用程式安全相關
    'cookieValidationKey',   // Cookie 驗證金鑰

    // SMTP 郵件相關
    'smtp_password',         // SMTP 郵件密碼（如有使用）

    // 外部服務相關
    'api_key',               // 外部 API 金鑰（如有使用）
    'proxy_password',        // Proxy 密碼（如有使用）
];
