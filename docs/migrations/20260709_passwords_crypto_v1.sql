-- Phase 2.1：投票密碼 per-record IV + HMAC 登入索引
-- 執行前請完整備份 passwords 表
-- 回滾：見本檔末尾

ALTER TABLE `passwords`
    ADD COLUMN `passwd_lookup` CHAR(64) NULL DEFAULT NULL COMMENT 'HMAC-SHA256 登入索引（crypto_version=1）' AFTER `passwd`,
    ADD COLUMN `crypto_version` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=固定IV舊制, 1=per-record IV' AFTER `passwd_lookup`;

ALTER TABLE `passwords`
    ADD UNIQUE KEY `uq_passwords_vote_lookup` (`voteID`, `passwd_lookup`);

-- 回滾（僅在尚未寫入 v1 資料時使用）：
-- ALTER TABLE `passwords` DROP INDEX `uq_passwords_vote_lookup`;
-- ALTER TABLE `passwords` DROP COLUMN `crypto_version`, DROP COLUMN `passwd_lookup`;
