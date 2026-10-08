-- =============================================
-- 測試資料庫初始化腳本
-- =============================================
-- 此腳本用於建立獨立的測試資料庫
-- 執行方式: mysql -u root -p < tests/_data/setup_test_database.sql
-- =============================================

-- 建立測試資料庫（如果不存在）
CREATE DATABASE IF NOT EXISTS `voting_test`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- 建立測試資料庫使用者（如果不存在）
-- 注意：請根據實際需求修改密碼
CREATE USER IF NOT EXISTS 'voting_test'@'localhost' IDENTIFIED BY 'test_password_123';

-- 授予測試使用者所有權限
GRANT ALL PRIVILEGES ON `voting_test`.* TO 'voting_test'@'localhost';

-- 刷新權限
FLUSH PRIVILEGES;

-- 顯示結果
SELECT 'Test database created successfully!' AS Status;
SHOW DATABASES LIKE 'voting_test';
