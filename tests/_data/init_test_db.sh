#!/bin/bash
# =============================================
# 測試資料庫初始化腳本
# =============================================
# 此腳本用於：
# 1. 建立測試資料庫
# 2. 從開發資料庫複製結構（不含數據）
# 3. 執行遷移以確保結構最新
# =============================================

set -e  # 遇到錯誤立即退出

echo "======================================"
echo "測試資料庫初始化"
echo "======================================"

# 配置參數
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS}"
SOURCE_DB="${SOURCE_DB:-voting}"
TEST_DB="voting_test"
TEST_USER="voting_test"
TEST_PASS="test_password_123"

# 檢查是否提供了資料庫密碼
if [ -z "$DB_PASS" ]; then
    echo "請輸入資料庫 root 密碼："
    read -s DB_PASS
fi

echo ""
echo "步驟 1/4: 建立測試資料庫和使用者..."
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" <<EOF
-- 建立測試資料庫
CREATE DATABASE IF NOT EXISTS \`$TEST_DB\`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- 建立測試使用者
CREATE USER IF NOT EXISTS '$TEST_USER'@'localhost' IDENTIFIED BY '$TEST_PASS';

-- 授予權限
GRANT ALL PRIVILEGES ON \`$TEST_DB\`.* TO '$TEST_USER'@'localhost';
FLUSH PRIVILEGES;

SELECT 'Test database and user created!' AS Status;
EOF

echo "✓ 測試資料庫和使用者建立完成"

echo ""
echo "步驟 2/4: 複製資料庫結構（不含數據）..."
mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" \
  --no-data \
  --routines \
  --triggers \
  "$SOURCE_DB" | \
  mysql -h"$DB_HOST" -u"$TEST_USER" -p"$TEST_PASS" "$TEST_DB"

echo "✓ 資料庫結構複製完成"

echo ""
echo "步驟 3/4: 執行 Yii 遷移..."
cd "$(dirname "$0")/../.."
php yii migrate --interactive=0 --db=db

echo "✓ 遷移執行完成"

echo ""
echo "步驟 4/4: 驗證測試資料庫..."
mysql -h"$DB_HOST" -u"$TEST_USER" -p"$TEST_PASS" "$TEST_DB" -e "SHOW TABLES;" | wc -l

echo ""
echo "======================================"
echo "✓ 測試資料庫初始化完成！"
echo "======================================"
echo ""
echo "資料庫資訊："
echo "  - 主機: $DB_HOST"
echo "  - 資料庫名稱: $TEST_DB"
echo "  - 使用者: $TEST_USER"
echo "  - 密碼: $TEST_PASS"
echo ""
echo "現在可以執行測試："
echo "  php vendor/bin/codecept run unit"
echo ""
