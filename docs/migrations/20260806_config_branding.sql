-- 網站品牌設定欄位（logo / favicon / copyright）
-- 執行前請備份 config 表
-- 系統標題已廢止，改用 homeTitle／homeTitleE（見 20260924_drop_unused.sql）

ALTER TABLE `config`
    ADD COLUMN `logoPath` VARCHAR(200) NULL DEFAULT NULL COMMENT 'Logo 相對 @web 路徑，空=不顯示' AFTER `homeTitleE`,
    ADD COLUMN `faviconPath` VARCHAR(200) NULL DEFAULT NULL COMMENT 'Favicon 相對 @web 路徑' AFTER `logoPath`,
    ADD COLUMN `copyright` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Footer 版權文字，空則 APP_NAME © year' AFTER `faviconPath`;
