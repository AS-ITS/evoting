-- 管理員自助重設密碼
-- 執行前請備份 users。請只執行一次（欄位已存在時請略過本檔）。
-- 未填 users.email 的帳號無法寄出重設信；既有登入與 TOTP 不受影響。

ALTER TABLE `users`
    ADD COLUMN `email` VARCHAR(255) NULL DEFAULT NULL COMMENT '重設密碼信箱' AFTER `name`,
    ADD COLUMN `auth_epoch` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '密碼變更序號，用於作廢既有 session' AFTER `totp_enabled`;

CREATE TABLE IF NOT EXISTS `password_reset` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cn` varchar(20) NOT NULL COMMENT '帳號',
  `token_hash` char(64) NOT NULL COMMENT 'SHA-256(token)',
  `expires_at` datetime NOT NULL COMMENT '到期時間',
  `used_at` datetime DEFAULT NULL COMMENT '使用時間',
  `created_at` datetime NOT NULL COMMENT '建立時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `cn` (`cn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
