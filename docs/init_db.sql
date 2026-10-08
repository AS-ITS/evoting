-- =============================================
-- Academia Sinica Academician Election System
-- 院士選舉系統 - 資料庫初始化 SQL
-- =============================================
-- 適用版本：MariaDB 10.6+ / MySQL 8.0+
-- 更新日期：2026-09-24
--
-- 用途：全新安裝（空資料庫）時匯入結構與預設 RBAC／管理員帳號。
-- ⚠️ 既有正式庫請勿直接覆寫本檔；請改執行 docs/migrations/*.sql
--     或 scripts/ubuntu-upgrade-test.sh（見 SETUP.md §從舊版升級）。
--
-- 本檔已併入之下列 schema 變更：
--   • passwords.passwd_lookup、crypto_version（20260709_passwords_crypto_v1.sql）
--   • votes.shortUrl char(8)（20260713_votes_shorturl_8.sql）
--   • users.totp_secret、totp_enabled（20260713_users_totp.sql）
--   • config branding 欄位（20260806_config_branding.sql；不含已廢止的 siteTitle）
--   • 已移除未用表：voters、votePerms、urlRedirection、migration（20260924）
--
-- 匯入後必須執行：php yii admin/create
-- config.id 必須與 .env 的 APP_ID 一致（預設 voting）
-- =============================================


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `auth_assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_assignment` (
  `item_name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_name`,`user_id`),
  KEY `idx-auth_assignment-user_id` (`user_id`),
  CONSTRAINT `auth_assignment_ibfk_1` FOREIGN KEY (`item_name`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_item` (
  `name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` smallint(6) NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `rule_name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `data` blob DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`name`),
  KEY `rule_name` (`rule_name`),
  KEY `idx-auth_item-type` (`type`),
  CONSTRAINT `auth_item_ibfk_1` FOREIGN KEY (`rule_name`) REFERENCES `auth_rule` (`name`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_item_child`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_item_child` (
  `parent` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `child` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`parent`,`child`),
  KEY `child` (`child`),
  CONSTRAINT `auth_item_child_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `auth_item_child_ibfk_2` FOREIGN KEY (`child`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_rule` (
  `name` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `data` blob DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ballots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ballots` (
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票識別碼',
  `round` int(11) DEFAULT 1 COMMENT '輪次',
  `ballotID` int(11) NOT NULL AUTO_INCREMENT COMMENT '選票編號',
  `party` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票者組別',
  `isAdminAdd` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '是否為管理員新增',
  `ip` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票者IP',
  `creator` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票者識別碼',
  `modifier` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '修改選票者',
  `insTime` datetime NOT NULL COMMENT '建立時間',
  `updTime` datetime NOT NULL COMMENT '修改時間',
  PRIMARY KEY (`ballotID`,`voteID`) USING BTREE,
  UNIQUE KEY `voteID` (`voteID`,`round`,`party`,`creator`) USING BTREE,
  CONSTRAINT `ballots_ibfk_1` FOREIGN KEY (`voteID`) REFERENCES `votes` (`voteID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='投票者資訊(人)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ballotsSelected`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ballotsSelected` (
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票識別碼',
  `ballotID` int(11) NOT NULL COMMENT '對應選票之投票者資訊',
  `selCandiID` int(11) NOT NULL COMMENT '選擇的候選人',
  `party` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '組別',
  `questionID` int(11) NOT NULL COMMENT '問題識別碼',
  `jobLctn` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '0' COMMENT '工作地點',
  `isValiable` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '是否為有效票',
  PRIMARY KEY (`voteID`,`ballotID`,`selCandiID`) USING BTREE,
  KEY `voteID` (`voteID`,`party`,`isValiable`) USING BTREE,
  KEY `selCandiID` (`selCandiID`),
  CONSTRAINT `ballotsSelected_ibfk_1` FOREIGN KEY (`voteID`) REFERENCES `votes` (`voteID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ballotsSelected_ibfk_2` FOREIGN KEY (`selCandiID`) REFERENCES `candiData` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='投票者資訊(票)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candiConfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candiConfig` (
  `candiConfig` int(11) NOT NULL AUTO_INCREMENT COMMENT '流水號',
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票識別碼',
  `questionID` int(11) DEFAULT NULL COMMENT '問題',
  `num` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '編號顯示方式',
  `Name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '姓名' COMMENT '名稱顯示',
  `NameE` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'Name' COMMENT '名稱(英)顯示',
  `NameUnit` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '名稱的單位',
  `NameUnitE` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '名稱的單位(英)',
  `columnNum` int(11) NOT NULL DEFAULT 1 COMMENT '投票時候選人顯示列數',
  `useBeforeHeader` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '0' COMMENT '使用欄位表頭',
  `width` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '1' COMMENT '寬度',
  `fontSize` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '19px' COMMENT '文字大小',
  `fontSizeE` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '文字(英)大小',
  `cellHeight` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'td高度',
  `cellHeightE` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'td(英)高度',
  `headerColor` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '表格header顏色',
  `showFieldSort` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '要顯示的欄位，且排序',
  `sort` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '是否排序',
  `sortDefault` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '預設排序的欄位',
  `sortColumns` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '排序的欄位及順序',
  `alignLeft` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '文字向左對齊欄位',
  `otherColNameA` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位A名稱',
  `otherColNameAE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位A名稱(英)',
  `otherColNameB` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位B名稱',
  `otherColNameBE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位B名稱(英)',
  `otherColNameC` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位C名稱',
  `otherColNameCE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位C名稱(英)',
  `otherColNameD` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位D名稱',
  `otherColNameDE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位D名稱(英)',
  `otherColNameE` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位E名稱',
  `otherColNameEE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位E名稱(英)',
  `otherColNameF` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位F名稱',
  `otherColNameFE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義欄位F名稱(英)',
  `beforeHeaderA` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '欄位表頭A名稱',
  `beforeHeaderAE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '欄位表頭A名稱(英)',
  `beforeHeaderB` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '欄位表頭B名稱',
  `beforeHeaderBE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '欄位表頭B名稱(英)',
  `beforeHeaderC` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '欄位表頭B名稱',
  `beforeHeaderCE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '欄位表頭C名稱(英)',
  PRIMARY KEY (`candiConfig`),
  UNIQUE KEY `voteID_2` (`voteID`,`questionID`),
  KEY `voteID` (`voteID`),
  CONSTRAINT `candiConfig_ibfk_1` FOREIGN KEY (`voteID`) REFERENCES `votes` (`voteID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='候選配置';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `candiData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `candiData` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '序號',
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票識別碼',
  `party` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '分組',
  `questionID` int(11) NOT NULL COMMENT '問題識別碼',
  `isReachThreshold` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '是否達門檻',
  `jobLctn` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '工作地點',
  `instCode` char(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '單位代碼',
  `tCode` char(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '職稱代碼',
  `instName` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '單位名稱',
  `instNameE` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '單位名稱(英)',
  `title` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '職稱名稱',
  `titleE` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '職稱名稱(英)',
  `Name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '名字/名稱',
  `NameE` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '名字(英)/名稱(英)',
  `sex` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '性別',
  `sysId` int(11) DEFAULT NULL COMMENT '當條件過濾，避免重複',
  `orderNum` int(11) NOT NULL COMMENT '自訂排序',
  `otherColA` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位1',
  `otherColAE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位1(英)',
  `otherColB` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位2',
  `otherColBE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位2(英)',
  `otherColC` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位3',
  `otherColCE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位3(英)',
  `otherColD` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位4',
  `otherColDE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位4(英)',
  `otherColE` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位5',
  `otherColEE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位5(英)',
  `otherColF` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位6',
  `otherColFE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '自定義欄位6(英)',
  `genMode` char(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '生成方式',
  `other` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '(保留)',
  `photo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '照片',
  `backgroundColor` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '背景顏色',
  `relateParty` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '關聯組別，給共同問題使用的，用於取得分組的人數',
  `specialHonor` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '特殊榮譽，影響計票展示通過規則3',
  PRIMARY KEY (`id`),
  UNIQUE KEY `voteID` (`voteID`,`id`),
  KEY `questionID` (`questionID`),
  CONSTRAINT `candiData_ibfk_1` FOREIGN KEY (`questionID`) REFERENCES `questions` (`questionID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `candiData_ibfk_2` FOREIGN KEY (`voteID`) REFERENCES `votes` (`voteID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='候選清單';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config` (
  `id` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `indexUrl` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '首頁網址',
  `countColumnNum` int(11) NOT NULL COMMENT '計票單行數',
  `partyLimit` int(11) NOT NULL COMMENT '分組上限',
  `canDeletePassword` int(11) NOT NULL COMMENT '是否可以刪除密碼',
  `anonPasswordErrorTimes` int(11) NOT NULL DEFAULT 3 COMMENT '匿名登入嘗試錯誤次數',
  `anonLoginLockPeriod` int(11) NOT NULL DEFAULT 300 COMMENT '匿名登入鎖定時間',
  `anonLoginWaiting` int(11) NOT NULL DEFAULT 5 COMMENT '匿名登入等待時間',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `homeLayout` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '首頁樣板',
  `homeTitle` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '首頁標題',
  `homeTitleE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '首頁標題(英)',
  `logoPath` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Logo 相對 @web 路徑，空=不顯示',
  `faviconPath` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Favicon 相對 @web 路徑',
  `copyright` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Footer 版權文字',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group` (
  `groupId` int(11) NOT NULL AUTO_INCREMENT COMMENT '群組識別碼',
  `groupName` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '群組名稱',
  `isRoutine` enum('Y','N') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '是否為例行投票',
  PRIMARY KEY (`groupId`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='組別(投票類別)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groupMember`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupMember` (
  `groupId` int(11) NOT NULL COMMENT '群組識別碼',
  `cn` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '群組成員(cn)',
  `isWrite` enum('Y','N') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'N' COMMENT '可否修改群組投票',
  `isOwner` enum('Y','N') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'N' COMMENT '可否修群組成員',
  PRIMARY KEY (`groupId`,`cn`),
  CONSTRAINT `groupMember_ibfk_1` FOREIGN KEY (`groupId`) REFERENCES `group` (`groupId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='組別成員(投票之業務權限)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logins` (
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票識別碼',
  `party` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票者組別',
  `creator` int(11) NOT NULL COMMENT '登入對象(投票者)',
  `session` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '登入session',
  PRIMARY KEY (`voteID`,`party`,`creator`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='投票者登入紀錄';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '投票識別碼',
  `type` char(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'log種類',
  `user` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '使用者',
  `ip` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '使用者IP',
  `browser` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '瀏覽器',
  `context` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '內文',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parties` (
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票識別碼',
  `party` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '組別',
  `name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '分組名稱',
  `nameE` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '分組英文名稱',
  `numBallots` int(11) NOT NULL DEFAULT 0 COMMENT '最多可投票數',
  `leastNumBallots` int(11) NOT NULL COMMENT '最少應投票數',
  `maxElect` int(11) NOT NULL DEFAULT 0 COMMENT '當選人數',
  `numOfKeep` int(11) NOT NULL COMMENT '候補人數',
  `numFemaleKeep` int(11) DEFAULT NULL COMMENT '女性保留人數',
  `numCounting` int(11) DEFAULT NULL COMMENT '清點人數',
  PRIMARY KEY (`voteID`,`party`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='投票限制(票)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `passwords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `passwords` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '密碼編號',
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票識別碼',
  `party` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票組別',
  `sn` int(11) NOT NULL COMMENT '投票分組的密碼序號',
  `passwd` varchar(90) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票密碼',
  `passwd_lookup` char(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'HMAC-SHA256 登入索引（crypto_version=1）',
  `crypto_version` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '0=固定IV舊制, 1=per-record IV',
  `status` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '啟用與否',
  `dtrack` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '1' COMMENT '雙軌投票',
  `voted` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票與否',
  `mark` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '標記',
  PRIMARY KEY (`id`),
  UNIQUE KEY `voteID` (`voteID`,`passwd`) USING BTREE,
  UNIQUE KEY `uq_passwords_vote_lookup` (`voteID`,`passwd_lookup`),
  CONSTRAINT `passwords_ibfk_1` FOREIGN KEY (`voteID`) REFERENCES `votes` (`voteID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='投票密碼(匿名投票適用)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `questionID` int(11) NOT NULL AUTO_INCREMENT COMMENT '問題識別碼',
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票識別碼',
  `round` int(11) DEFAULT 1 COMMENT '輪次',
  `party` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '組別',
  `title` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '標題',
  `titleE` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '英文標題',
  `confirmTitle` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '投票頁面的確認關卡問題顯示名稱',
  `confirmTitleE` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '投票頁面的確認關卡問題顯示名稱(英)',
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '描述',
  `descriptionE` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '英文描述',
  `ruleText` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義投票規則文字',
  `ruleTextE` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義投票規則文字(英)',
  `numBallots` int(11) NOT NULL DEFAULT 0 COMMENT '最多可投票數',
  `leastNumBallots` int(11) NOT NULL COMMENT '最少投票數',
  `maxElect` int(11) NOT NULL DEFAULT 0 COMMENT '當選人數',
  `numOfKeep` int(11) NOT NULL COMMENT '候補人數',
  `numFemaleKeep` int(11) DEFAULT NULL COMMENT '女性保留人數',
  `population` int(11) DEFAULT 0 COMMENT '投票母數，用於通過規則2',
  PRIMARY KEY (`questionID`),
  KEY `voteID` (`voteID`,`round`,`party`) USING BTREE,
  CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`voteID`) REFERENCES `votes` (`voteID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `results` (
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票識別碼',
  `candID` int(11) NOT NULL COMMENT '候選人編號',
  `party` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '分組',
  `questionID` int(11) NOT NULL COMMENT '問題識別碼',
  `jobLctn` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '工作地點',
  `ballotCounts` int(11) NOT NULL COMMENT '總得票數',
  `elected` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '當選與否',
  `rank` int(11) NOT NULL COMMENT '得票排序',
  `comment` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '備註',
  PRIMARY KEY (`voteID`,`candID`) USING BTREE,
  KEY `candID` (`candID`,`party`,`questionID`) USING BTREE,
  KEY `questionID` (`questionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='投票結果';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `resultsConfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resultsConfig` (
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票識別碼',
  `isShow` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '是否顯示於投票結果',
  `isLogin` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '是否需要登入查看投票結果',
  `isParty` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '是否分組查看投票',
  `sort` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '排序方式(姓氏筆畫、得票高低)',
  `showFieldSort` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '欄位順序',
  `showElectedStatus` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '顯示結果投票狀態',
  PRIMARY KEY (`voteID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='投票結果(配置)';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `round`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `round` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `round` int(11) NOT NULL COMMENT '輪次',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '輪次名稱',
  `nameE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '英文名稱',
  `showName` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '1' COMMENT '是否顯示輪次名稱',
  `enablePasswords` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '該輪次各分組啟用密碼數量',
  `insert_datetime` datetime NOT NULL DEFAULT current_timestamp() COMMENT '新增時間',
  `update_datetime` datetime DEFAULT NULL ON UPDATE current_timestamp() COMMENT '修改時間',
  PRIMARY KEY (`id`),
  UNIQUE KEY `voteID` (`voteID`,`round`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `cn` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '帳號',
  `name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '姓名',
  `roles` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '角色',
  `password` varchar(255) DEFAULT NULL COMMENT '加密密碼',
  `totp_secret` varchar(255) DEFAULT NULL COMMENT 'TOTP Base32 secret',
  `totp_enabled` char(1) NOT NULL DEFAULT '0' COMMENT '是否啟用 TOTP：0否 1是',
  PRIMARY KEY (`cn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `votes` (
  `voteID` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票識別碼',
  `Name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票名稱',
  `NameE` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票名稱(英)',
  `creator` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票建立者',
  `contact` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '聯絡人',
  `contactE` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '聯絡人(英)',
  `candComment` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '候選人名單備註',
  `candCommentE` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '候選人名單備註(英)',
  `openStart` datetime NOT NULL COMMENT '投票開放時間(起)',
  `openEnd` datetime NOT NULL COMMENT '投票開放時間(迄)',
  `verifyStart` datetime NOT NULL COMMENT '驗證時間(起)',
  `verifyEnd` datetime NOT NULL COMMENT '驗證時間(迄)',
  `type` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '登入驗證類型',
  `partyOrNot` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '是否分組',
  `addiCondition` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '當選保留條件',
  `hosted` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '主辦單位',
  `hostedE` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '主辦單位(英)',
  `tel` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '聯絡電話',
  `email` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '聯絡信箱',
  `isByParty` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '是否依組別看選票',
  `isBindVote` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '是否共用其他投票之密碼',
  `bindWhichVote` varchar(128) NOT NULL DEFAULT '',
  `notice` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票要點',
  `noticeE` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票要點(英)',
  `information` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票須知',
  `informationE` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票須知(英)',
  `otherInfoTitle` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義資訊標題',
  `otherInfoTitleE` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義資訊英文標題',
  `otherInfo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義資訊',
  `otherInfoE` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '自定義英文資訊',
  `active` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票是否進行',
  `isFinish` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '是否完成投票',
  `finishPage` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '1' COMMENT '投票完成轉跳頁面',
  `pattern` char(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '投票樣版',
  `loginLayout` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '投票密碼登入樣板',
  `themeColor` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '主題色',
  `sort` int(11) NOT NULL COMMENT '管理頁面的排序',
  `session` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT '場次碼',
  `shortUrl` char(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '短網址代碼',
  `groupId` int(11) DEFAULT NULL COMMENT '群組id',
  `authBeforeDetail` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '0' COMMENT '查看投票資訊是否要先驗證',
  `skipDetail` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '0' COMMENT '是否略過投票資訊頁面',
  `skipCheck` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '0' COMMENT '是否略過圈選結果頁',
  `isShow` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '1' COMMENT '是否於首頁顯示',
  `round` int(11) DEFAULT 1 COMMENT '輪次',
  `candiConfig` char(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT '候選人配置',
  PRIMARY KEY (`voteID`) USING BTREE,
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='投票基本資料';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- =============================================
-- 初始預設資料
-- =============================================

-- 預設系統配置 (ID 必須與 APP_ID 一致，預設為 'voting')
INSERT INTO `config` (`id`, `indexUrl`, `countColumnNum`, `partyLimit`, `canDeletePassword`, `anonPasswordErrorTimes`, `anonLoginLockPeriod`, `anonLoginWaiting`, `homeLayout`, `homeTitle`, `homeTitleE`, `logoPath`, `faviconPath`, `copyright`) 
VALUES ('voting', '', 3, 4, 1, 4, 300, 0, 'meeting', '選舉系統', 'Voting System', NULL, 'favicon.ico', NULL);

-- 初始管理員：匯入 schema 後執行 php yii admin/create（互動設定密碼，不在 SQL 寫死）

-- =============================================
-- RBAC 角色權限設定
-- =============================================

-- RBAC 規則
INSERT INTO `auth_rule` (`name`,`data`,`created_at`,`updated_at`) VALUES ('ballotWorkManage','O:24:"app\\rules\\ballotWorkRule":3:{s:4:"name";s:16:"ballotWorkManage";s:9:"createdAt";i:1769136605;s:9:"updatedAt";i:1769136605;}',1769136605,1769136605);
INSERT INTO `auth_rule` (`name`,`data`,`created_at`,`updated_at`) VALUES ('manageByMember','O:32:"app\\rules\\groupManagByMemberRule":3:{s:4:"name";s:14:"manageByMember";s:9:"createdAt";i:1769136605;s:9:"updatedAt";i:1769136605;}',1769136605,1769136605);
INSERT INTO `auth_rule` (`name`,`data`,`created_at`,`updated_at`) VALUES ('manageOwnVote','O:31:"app\\rules\\groupManagOwnVoteRule":3:{s:4:"name";s:13:"manageOwnVote";s:9:"createdAt";i:1769136605;s:9:"updatedAt";i:1769136605;}',1769136605,1769136605);
INSERT INTO `auth_rule` (`name`,`data`,`created_at`,`updated_at`) VALUES ('viewOwnGroup','O:26:"app\\rules\\groupViewOwnRule":3:{s:4:"name";s:12:"viewOwnGroup";s:9:"createdAt";i:1769136605;s:9:"updatedAt";i:1769136605;}',1769136605,1769136605);
INSERT INTO `auth_rule` (`name`,`data`,`created_at`,`updated_at`) VALUES ('voteManag','O:23:"app\\rules\\voteManagRule":3:{s:4:"name";s:9:"voteManag";s:9:"createdAt";i:1769136605;s:9:"updatedAt";i:1769136605;}',1769136605,1769136605);

-- RBAC 權限項目（Permission）與角色（Role）
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('ballotWork',2,'開票作業','ballotWorkManage',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('ballotWorkCount',2,'計票顯示','ballotWorkManage',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('ballotWorkIndex',2,'開票作業首頁',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('ballotWorkResult',2,'投票結果','ballotWorkManage',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('ballotWorkSetting',2,'開票設定','ballotWorkManage',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('ballotWorkStatus',2,'投票情形','ballotWorkManage',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupCreate',2,'建立新群組',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupCreateMember',2,'新增群組成員資料',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupDeleteMember',2,'刪除群組成員資料',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupEditBase',2,'編輯群組基本資料',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupEditMember',2,'編輯群組成員資料',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupManag',2,'管理群組',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupManageByMember',2,'允許群組成員管理','manageByMember',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupManagOwnVote',2,'只能管理屬於自己群組的投票','manageOwnVote',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupView',2,'檢視群組',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupViewBase',2,'檢視群組基本資料',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupViewMember',2,'檢視群組成員資料',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupViewOwn',2,'僅能檢視自己的群組','viewOwnGroup',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('groupVoteManage',2,'管理屬於自己群組的投票',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('voteBallot',2,'投票選票管理全功能','voteManag',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('voteCandi',2,'投票候選人管理全功能','voteManag',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('voteCount',2,'投票計票單、開票全功能','voteManag',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('voteCreate',2,'建立投票',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('voteGenManag',2,'投票總管理',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('voteInfo',2,'投票基本設定編輯','voteManag',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('voteManag',2,'投票管理頁面',NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('votePasswd',2,'投票密碼管理全功能','voteManag',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('voteQuestion',2,'投票問題全功能','voteManag',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('voteReset',2,'投票重啟全功能','voteManag',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('voteResult',2,'投票結果全功能','voteManag',NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('ga',1,NULL,NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('gm',1,NULL,NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('sa',1,NULL,NULL,NULL,1769136605,1769136605);
INSERT INTO `auth_item` (`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) VALUES ('va',1,NULL,NULL,NULL,1769136605,1769136605);

-- RBAC 角色繼承關係
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','ballotWorkCount');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','ballotWorkIndex');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','ballotWorkResult');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','ballotWorkSetting');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','ballotWorkStatus');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','groupCreate');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','groupCreateMember');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','groupDeleteMember');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','groupEditBase');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','groupEditMember');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','groupManag');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','groupManagOwnVote');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','groupView');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','groupViewBase');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('ga','groupViewMember');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('gm','ballotWorkCount');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('gm','ballotWorkIndex');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('gm','ballotWorkResult');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('gm','ballotWorkSetting');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('gm','ballotWorkStatus');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('gm','groupManageByMember');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('gm','groupManagOwnVote');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('gm','groupView');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('gm','groupViewOwn');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManag','groupManageByMember');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManag','groupViewOwn');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManageByMember','groupCreateMember');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManageByMember','groupDeleteMember');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManageByMember','groupEditBase');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManageByMember','groupEditMember');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManagOwnVote','voteBallot');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManagOwnVote','voteCandi');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManagOwnVote','voteCount');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManagOwnVote','voteInfo');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManagOwnVote','votePasswd');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManagOwnVote','voteQuestion');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManagOwnVote','voteReset');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupManagOwnVote','voteResult');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupViewOwn','groupView');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupViewOwn','groupViewBase');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('groupViewOwn','groupViewMember');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('sa','ga');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('sa','gm');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('sa','va');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('sa','voteGenManag');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','ballotWorkCount');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','ballotWorkIndex');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','ballotWorkResult');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','ballotWorkSetting');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','ballotWorkStatus');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','groupCreate');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','groupManageByMember');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','groupManagOwnVote');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','groupView');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','groupViewOwn');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','voteBallot');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','voteCandi');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','voteCount');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','voteCreate');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','voteInfo');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','voteManag');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','votePasswd');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','voteQuestion');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','voteReset');
INSERT INTO `auth_item_child` (`parent`,`child`) VALUES ('va','voteResult');
