-- 廢止系統標題欄位（改用 homeTitle／homeTitleE）
-- 移除記名／舊轉址／Yii migrate 死表
-- 執行前請備份；MySQL 無 DROP COLUMN IF EXISTS 時，欄位已不存在會失敗，略過即可

ALTER TABLE `config`
    DROP COLUMN `siteTitle`,
    DROP COLUMN `siteTitleE`;

DROP TABLE IF EXISTS `voters`;
DROP TABLE IF EXISTS `votePerms`;
DROP TABLE IF EXISTS `urlRedirection`;
DROP TABLE IF EXISTS `migration`;
