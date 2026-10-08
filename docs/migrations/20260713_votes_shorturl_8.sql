-- L4: 短網址代碼由 6 字元擴充至 8 字元（提升熵值）
ALTER TABLE `votes` MODIFY COLUMN `shortUrl` char(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '短網址代碼';
