#!/bin/bash
# =============================================
# 測試資料庫初始化腳本
# =============================================
# 此腳本用於：
# 1. 建立測試資料庫與測試使用者
# 2. 從來源資料庫複製結構（不含數據）
# 3. 驗證測試資料庫
#
# 可用環境變數：
#   DB_HOST、DB_USER（預設 root）、DB_PASS（未設定則提示輸入）
#   SOURCE_DB（預設 voting）、TEST_DB（預設 voting_test）
#   TEST_USER（預設 voting_test）、TEST_DB_PASSWORD（未設定則提示輸入）
#
# 密碼不會出現在命令列參數，也不會輸出到畫面。
# =============================================

set -euo pipefail

echo "======================================"
echo "測試資料庫初始化"
echo "======================================"

# 配置參數
DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS-}"
SOURCE_DB="${SOURCE_DB:-voting}"
TEST_DB="${TEST_DB:-voting_test}"
TEST_USER="${TEST_USER:-voting_test}"
TEST_PASS="${TEST_DB_PASSWORD-}"

if [ -z "$DB_PASS" ]; then
    read -r -s -p "請輸入資料庫 ${DB_USER} 密碼：" DB_PASS
    echo ""
fi
if [ -z "$TEST_PASS" ]; then
    read -r -s -p "請設定測試使用者 ${TEST_USER} 的密碼（與 .env 的 TEST_DB_PASSWORD 相同）：" TEST_PASS
    echo ""
fi
[ -n "$TEST_PASS" ] || { echo "測試使用者密碼不可為空" >&2; exit 1; }

# 以 0600 暫存 option file 傳密碼給 mysql／mysqldump，避免密碼出現在 ps 看得到的命令列
CNF_FILES=()
cleanup() { [ ${#CNF_FILES[@]} -eq 0 ] || rm -f "${CNF_FILES[@]}"; }
trap cleanup EXIT

make_cnf() {
    local pw="$1" cnf
    cnf="$(mktemp)"
    chmod 600 "$cnf"
    pw="${pw//\\/\\\\}"
    pw="${pw//\"/\\\"}"
    printf '[client]\npassword="%s"\n' "$pw" > "$cnf"
    CNF_FILES+=("$cnf")
    printf '%s' "$cnf"
}

# SQL 字串常值跳脫（反斜線與單引號）
sql_quote() {
    local s="${1//\\/\\\\}"
    printf '%s' "${s//\'/\'\'}"
}

ROOT_CNF="$(make_cnf "$DB_PASS")"
TEST_CNF="$(make_cnf "$TEST_PASS")"

echo ""
echo "步驟 1/3: 建立測試資料庫和使用者..."
mysql --defaults-extra-file="$ROOT_CNF" -h"$DB_HOST" -u"$DB_USER" <<EOF
-- 建立測試資料庫
CREATE DATABASE IF NOT EXISTS \`$TEST_DB\`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- 建立測試使用者
CREATE USER IF NOT EXISTS '$TEST_USER'@'localhost' IDENTIFIED BY '$(sql_quote "$TEST_PASS")';

-- 授予權限
GRANT ALL PRIVILEGES ON \`$TEST_DB\`.* TO '$TEST_USER'@'localhost';
FLUSH PRIVILEGES;

SELECT 'Test database and user created!' AS Status;
EOF

echo "✓ 測試資料庫和使用者建立完成"

echo ""
echo "步驟 2/3: 複製資料庫結構（不含數據）..."
mysqldump --defaults-extra-file="$ROOT_CNF" -h"$DB_HOST" -u"$DB_USER" \
  --no-data \
  --routines \
  --triggers \
  "$SOURCE_DB" | \
  mysql --defaults-extra-file="$TEST_CNF" -h"$DB_HOST" -u"$TEST_USER" "$TEST_DB"

echo "✓ 資料庫結構複製完成"

echo ""
echo "步驟 3/3: 驗證測試資料庫..."
TABLES="$(mysql --defaults-extra-file="$TEST_CNF" -h"$DB_HOST" -u"$TEST_USER" -N "$TEST_DB" -e "SHOW TABLES;" | wc -l)"
echo "資料表數量: $TABLES"

echo ""
echo "======================================"
echo "✓ 測試資料庫初始化完成！"
echo "======================================"
echo ""
echo "資料庫資訊："
echo "  - 主機: $DB_HOST"
echo "  - 資料庫名稱: $TEST_DB"
echo "  - 使用者: $TEST_USER"
echo ""
echo "請確認 .env 的 TEST_DB_NAME、TEST_DB_USERNAME、TEST_DB_PASSWORD 與上面一致，"
echo "再載入 fixtures 並執行測試（見 tests/README.md）："
echo "  composer test unit"
echo ""
