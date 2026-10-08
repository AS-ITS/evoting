-- 管理員 TOTP（MFA）選用欄位
-- 執行前請備份 users 表；未啟用 totp_enabled 時不影響既有登入流程

ALTER TABLE `users`
    ADD COLUMN `totp_secret` VARCHAR(255) NULL DEFAULT NULL COMMENT 'TOTP Base32 secret' AFTER `password`,
    ADD COLUMN `totp_enabled` CHAR(1) NOT NULL DEFAULT '0' COMMENT '是否啟用 TOTP：0否 1是' AFTER `totp_secret`;
