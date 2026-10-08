#!/usr/bin/env bash
# =============================================================================
# 院士選舉系統 — Ubuntu 測試環境升級腳本（舊版程式 + 舊 DB → 新版）
# =============================================================================
# 用途：在已有舊版 voting 的 Ubuntu 主機上，更新程式碼並升級資料庫 schema。
# 注意：本腳本會修改正式資料庫，執行前請確認備份完成。
#
# 使用方式：
#   1. 編輯下方「可調整變數」
#   2. chmod +x scripts/ubuntu-upgrade-test.sh
#   3. sudo -u <部署使用者> ./scripts/ubuntu-upgrade-test.sh
#
# 乾跑（只顯示將執行的步驟，不寫入 DB）：
#   DRY_RUN=1 ./scripts/ubuntu-upgrade-test.sh
# =============================================================================

set -euo pipefail

# ---------- 可調整變數 ----------
APP_ROOT="${APP_ROOT:-/var/www/html/voting}"
DB_NAME="${DB_NAME:-voting}"
DB_USER="${DB_USER:-root}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
# 若需密碼：export MYSQL_PWD='...' 或 mysql -p 互動輸入
BACKUP_DIR="${BACKUP_DIR:-/var/backups/voting}"
PHP_USER="${PHP_USER:-www-data}"          # Ubuntu + php-fpm 常見；Apache 可能是 www-data
APP_ENV="${APP_ENV:-testing}"             # 測試機建議 testing；正式請改 production
RUN_CRYPTO_UPGRADE="${RUN_CRYPTO_UPGRADE:-1}"  # 1=執行 v0→v1 密碼 backfill
DRY_RUN="${DRY_RUN:-0}"

# 程式碼來源（擇一；預設不拉 code，假設你已 rsync/git 到新目錄）
GIT_PULL="${GIT_PULL:-0}"                 # 1=在 APP_ROOT 執行 git pull
COMPOSER_NO_DEV="${COMPOSER_NO_DEV:-0}"   # 1= composer install --no-dev

# ---------- 輔助函式 ----------
log()  { printf '[%s] %s\n' "$(date '+%H:%M:%S')" "$*"; }
die()  { log "ERROR: $*"; exit 1; }
run()  {
  log ">> $*"
  if [[ "$DRY_RUN" == "1" ]]; then
    return 0
  fi
  eval "$@"
}

mysql_q() {
  mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" "$@" 2>/dev/null
}

has_column() {
  local table="$1" column="$2"
  local cnt
  cnt=$(mysql_q -N -e \
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA='${DB_NAME}' AND TABLE_NAME='${table}' AND COLUMN_NAME='${column}'")
  [[ "${cnt:-0}" -gt 0 ]]
}

apply_sql_if_needed() {
  local label="$1" check_table="$2" check_column="$3" sql_file="$4"
  if has_column "$check_table" "$check_column"; then
    log "SKIP $label（${check_table}.${check_column} 已存在）"
    return 0
  fi
  log "APPLY $label ← $sql_file"
  if [[ "$DRY_RUN" == "1" ]]; then
    return 0
  fi
  mysql_q "$DB_NAME" < "$sql_file"
}

# ---------- 前置檢查 ----------
[[ -d "$APP_ROOT" ]] || die "APP_ROOT 不存在: $APP_ROOT"
[[ -f "$APP_ROOT/yii" ]] || die "找不到 yii，請確認 APP_ROOT"
[[ -f "$APP_ROOT/.env" ]] || die "找不到 .env，請先從 .env.example 複製並設定"

cd "$APP_ROOT"
MIG_DIR="$APP_ROOT/docs/migrations"

log "=== 院士選舉系統升級開始 ==="
log "APP_ROOT=$APP_ROOT DB=$DB_NAME APP_ENV=$APP_ENV DRY_RUN=$DRY_RUN"

# ---------- 0. 進行中投票檢查 ----------
log "--- [0] 檢查進行中投票 ---"
active_cnt=$(mysql_q -N -e "SELECT COUNT(*) FROM \`${DB_NAME}\`.votes WHERE active='1'" || echo "?")
log "active=1 的場次數: ${active_cnt}"
if [[ "$active_cnt" != "0" && "$active_cnt" != "?" ]]; then
  die "仍有進行中投票，請先暫停或改在維護窗口執行"
fi

# ---------- 1. 備份 ----------
log "--- [1] 備份 ---"
run "mkdir -p '$BACKUP_DIR'"
TS=$(date +%Y%m%d_%H%M%S)
BACKUP_SQL="$BACKUP_DIR/${DB_NAME}_${TS}.sql.gz"
if [[ "$DRY_RUN" != "1" ]]; then
  log ">> mysqldump → $BACKUP_SQL"
  mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" \
    --single-transaction --routines --triggers "$DB_NAME" \
    | gzip > "$BACKUP_SQL"
  log "DB 備份完成: $BACKUP_SQL"
else
  log ">> (dry-run) mysqldump $DB_NAME"
fi

if [[ -f "$APP_ROOT/.env" ]]; then
  run "cp -a '$APP_ROOT/.env' '$BACKUP_DIR/env_${TS}.bak'"
fi
if [[ -f /etc/voting/master.key ]]; then
  run "cp -a /etc/voting/master.key '$BACKUP_DIR/master.key_${TS}.bak'"
fi

# ---------- 2. 更新程式碼 ----------
log "--- [2] 更新程式碼 ---"
if [[ "$GIT_PULL" == "1" ]]; then
  run "git -C '$APP_ROOT' pull --ff-only"
else
  log "SKIP git pull（GIT_PULL=0；請確認已手動部署新版程式）"
fi

if [[ "$COMPOSER_NO_DEV" == "1" ]]; then
  run "composer install --no-dev --working-dir='$APP_ROOT'"
else
  run "composer install --working-dir='$APP_ROOT'"
fi

# ---------- 3. Schema migration（增量 SQL） ----------
log "--- [3] 資料庫 schema migration ---"
apply_sql_if_needed \
  "passwords crypto v1" "passwords" "passwd_lookup" \
  "$MIG_DIR/20260709_passwords_crypto_v1.sql"

# shortUrl：以欄位長度判斷（6→8）
short_len=$(mysql_q -N -e \
  "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA='${DB_NAME}' AND TABLE_NAME='votes' AND COLUMN_NAME='shortUrl'" || echo "0")
if [[ "${short_len:-0}" -lt 8 ]]; then
  log "APPLY votes shortUrl 8 ← $MIG_DIR/20260713_votes_shorturl_8.sql"
  if [[ "$DRY_RUN" != "1" ]]; then
    mysql_q "$DB_NAME" < "$MIG_DIR/20260713_votes_shorturl_8.sql"
  fi
else
  log "SKIP votes shortUrl（已是 ${short_len} 字元）"
fi

apply_sql_if_needed \
  "users TOTP" "users" "totp_secret" \
  "$MIG_DIR/20260713_users_totp.sql"

# ---------- 4. .env 新變數提醒 ----------
log "--- [4] 環境變數檢查 ---"
for var in APP_ENV SENSITIVE_REAUTH SENSITIVE_PLAINTEXT_TTL ALLOWED_HOSTS; do
  if ! grep -q "^${var}=" "$APP_ROOT/.env" 2>/dev/null; then
    log "WARN: .env 缺少 ${var}，請參考 .env.example 補上"
  fi
done
if [[ ! -f /etc/voting/master.key ]] && ! grep -qE '^(VOTING_MASTER_KEY|MASTER_KEY)=' "$APP_ROOT/.env" 2>/dev/null; then
  log "WARN: 未偵測到主金鑰（/etc/voting/master.key 或 .env），匿名密碼加解密可能失敗"
fi

# ---------- 5. Yii 維護指令 ----------
log "--- [5] Yii 健檢與密碼 crypto backfill ---"
export APP_ENV
run "php '$APP_ROOT/yii' config/validate"
run "php '$APP_ROOT/yii' encrypt/test"

if has_column "passwords" "crypto_version"; then
  run "php '$APP_ROOT/yii' migrate-passwords/verify"
  if [[ "$RUN_CRYPTO_UPGRADE" == "1" ]]; then
    if [[ "$APP_ENV" == "production" ]]; then
      export ALLOW_PRODUCTION_MIGRATE=1
      log "正式環境：已設 ALLOW_PRODUCTION_MIGRATE=1"
    fi
    if [[ "$DRY_RUN" != "1" ]]; then
      # 非互動：以 yes 確認（測試機用；正式環境建議手動執行並人工確認）
      yes | php "$APP_ROOT/yii" migrate-passwords/upgrade-crypto || true
    else
      log ">> (dry-run) php yii migrate-passwords/upgrade-crypto"
    fi
    run "php '$APP_ROOT/yii' migrate-passwords/verify"
  else
    log "SKIP upgrade-crypto（RUN_CRYPTO_UPGRADE=0）"
  fi
else
  log "SKIP migrate-passwords（passwords 尚無 crypto_version 欄位）"
fi

run "php '$APP_ROOT/yii' cache/flush-all"
run "php '$APP_ROOT/yii' config/clear-cache"

# ---------- 6. 目錄權限（Ubuntu / php-fpm） ----------
log "--- [6] 目錄權限 ---"
run "mkdir -p '$APP_ROOT/runtime/storage' '$APP_ROOT/runtime/URI'"
run "chmod -R 775 '$APP_ROOT/runtime' '$APP_ROOT/web/assets'"
if [[ "$DRY_RUN" != "1" ]] && id "$PHP_USER" &>/dev/null; then
  chown -R "$PHP_USER:$PHP_USER" "$APP_ROOT/runtime" "$APP_ROOT/web/assets" || true
fi

# ---------- 7. 冒煙測試提示 ----------
log "--- [7] 完成 ---"
log "請手動驗證："
log "  • 後台登入 https://<網域>/admin"
log "  • php yii config/health"
log "  • 匿名投票：密碼登入 → 投票 → 計票"
log "  • 密碼匯出／選票統計匯出（二次驗證）"
log "備份位置: $BACKUP_DIR"
log "=== 升級腳本結束 ==="
