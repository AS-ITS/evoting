#!/usr/bin/env bash
# 全新安裝精靈（僅新裝；既有業務庫請改走 SETUP「從舊版升級」／ubuntu-upgrade-test.sh）
#
# 用法：
#   cd /var/www/html/voting   # 已 git clone
#   sudo bash scripts/install.sh
#
# 非互動（CI／重裝）：
#   export DB_PASSWORD=… ADMIN_PASSWORD=… ALLOWED_HOSTS=voting.example.com
#   bash scripts/install.sh --non-interactive --with-db --init-db --create-admin
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

NON_INTERACTIVE=0
WITH_DB=0
INIT_DB=0
CREATE_ADMIN=0
FORCE_INIT_DB=0
SKIP_COMPOSER=0
NO_DEV=0

usage() {
  cat <<'EOF'
全新安裝精靈（僅新裝；升級請見 SETUP.md「從舊版升級」）

用法：
  cd /var/www/html/voting
  sudo bash scripts/install.sh

非互動：
  export DB_PASSWORD=… ADMIN_PASSWORD=… ALLOWED_HOSTS=voting.example.com
  bash scripts/install.sh --non-interactive --with-db --init-db --create-admin

選項：
  --non-interactive   不詢問；缺必填變數則失敗
  --with-db           建立 DATABASE／USER（需 DB_ROOT_PASSWORD 或 unix_socket root）
  --init-db           匯入 docs/init_db.sql（庫需為空，或搭配 --force-init-db）
  --force-init-db     強制匯入（危險）
  --create-admin      執行 php yii admin/create
  --no-dev            composer install --no-dev --optimize-autoloader
  --skip-composer     略過 composer install
  -h, --help          說明
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --non-interactive) NON_INTERACTIVE=1 ;;
    --with-db) WITH_DB=1 ;;
    --init-db) INIT_DB=1 ;;
    --force-init-db) FORCE_INIT_DB=1; INIT_DB=1 ;;
    --create-admin) CREATE_ADMIN=1 ;;
    --no-dev) NO_DEV=1 ;;
    --skip-composer) SKIP_COMPOSER=1 ;;
    -h|--help) usage; exit 0 ;;
    *) echo "未知選項: $1" >&2; usage; exit 2 ;;
  esac
  shift
done

log() { echo "==> $*"; }
die() { echo "FAIL: $*" >&2; exit 1; }

prompt() {
  # prompt VAR "說明" "預設"
  local var="$1" msg="$2" def="${3-}"
  local val
  if [[ "$NON_INTERACTIVE" -eq 1 ]]; then
    val="${!var-}"
    if [[ -z "$val" && -n "$def" ]]; then
      val="$def"
    fi
    printf -v "$var" '%s' "$val"
    return
  fi
  if [[ -n "$def" ]]; then
    read -r -p "$msg [$def]: " val || true
    val="${val:-$def}"
  else
    read -r -p "$msg: " val || true
  fi
  printf -v "$var" '%s' "$val"
}

prompt_secret() {
  local var="$1" msg="$2"
  local val
  if [[ "$NON_INTERACTIVE" -eq 1 ]]; then
    val="${!var-}"
    printf -v "$var" '%s' "$val"
    return
  fi
  read -r -s -p "$msg: " val || true
  echo
  printf -v "$var" '%s' "$val"
}

ask_yes() {
  # ask_yes VAR "說明" default_Y_or_N
  local var="$1" msg="$2" def="${3:-Y}"
  local val
  if [[ "$NON_INTERACTIVE" -eq 1 ]]; then
    return 0
  fi
  read -r -p "$msg [${def}/$([[ $def == Y ]] && echo n || echo y)]: " val || true
  val="${val:-$def}"
  case "$val" in
    Y|y|yes|YES) printf -v "$var" '%s' "1" ;;
    *) printf -v "$var" '%s' "0" ;;
  esac
}

set_env_key() {
  local key="$1" value="$2" file="${3:-.env}"
  local tmp
  tmp="$(mktemp)"
  if grep -qE "^[# ]*${key}=" "$file" 2>/dev/null; then
    # 取消註解並設定；保留其他行
    awk -v k="$key" -v v="$value" '
      BEGIN { done=0 }
      {
        if ($0 ~ "^[[:space:]]*#?[[:space:]]*" k "=") {
          if (!done) { print k "=" v; done=1 }
          next
        }
        print
      }
      END { if (!done) print k "=" v }
    ' "$file" > "$tmp"
  else
    cat "$file" > "$tmp"
    printf '%s=%s\n' "$key" "$value" >> "$tmp"
  fi
  mv "$tmp" "$file"
}

detect_php_user() {
  local u
  u="$(ps aux 2>/dev/null | grep -E 'php-fpm: pool' | grep -v grep | awk '{print $1}' | head -1 || true)"
  if [[ -z "$u" ]]; then
    u="$(ps aux 2>/dev/null | grep -E 'php-fpm' | grep -v grep | grep -v master | awk '{print $1}' | head -1 || true)"
  fi
  if [[ -z "$u" ]]; then
    for cand in www-data apache nginx; do
      if id "$cand" &>/dev/null; then u="$cand"; break; fi
    done
  fi
  echo "${u:-www-data}"
}

mysql_cmd() {
  # 使用應用帳號
  mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$@"
}

mysql_root_cmd() {
  if [[ -n "${DB_ROOT_PASSWORD-}" ]]; then
    mysql -h "$DB_HOST" -P "$DB_PORT" -u root -p"$DB_ROOT_PASSWORD" "$@"
  else
    # 常見：本機 unix_socket
    mysql -u root "$@"
  fi
}

table_count() {
  mysql_cmd -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_type='BASE TABLE';" 2>/dev/null || echo "0"
}

find_composer() {
  if command -v composer >/dev/null 2>&1; then
    command -v composer
    return
  fi
  for cand in /usr/local/bin/composer /usr/bin/composer; do
    if [[ -x "$cand" ]]; then
      echo "$cand"
      return
    fi
  done
  return 1
}

extensions_php_ok() {
  [[ -f vendor/yiisoft/extensions.php ]] \
    && grep -q 'yiisoft/yii2-bootstrap5' vendor/yiisoft/extensions.php
}

ensure_extensions_php() {
  if extensions_php_ok; then
    return 0
  fi
  log "extensions.php 不完整，嘗試修復 yiisoft/yii2-composer 註冊…"
  "$COMPOSER_BIN" config allow-plugins.yiisoft/yii2-composer true
  # 重裝以觸發 Installer::addPackage（插件若晚於套件解壓啟動會留下空 extensions.php）
  "$COMPOSER_BIN" reinstall yiisoft/yii2-composer yiisoft/yii2 yiisoft/yii2-bootstrap5 \
    --no-interaction \
    || "$COMPOSER_BIN" update yiisoft/yii2-composer --with-dependencies --no-interaction --prefer-dist \
    || true
  if extensions_php_ok; then
    return 0
  fi
  log "改從 installed.json 重建 extensions.php…"
  [[ -f scripts/rebuild-yii-extensions.php ]]     || die "缺少 scripts/rebuild-yii-extensions.php（請一併更新此檔）。或: export COMPOSER_ALLOW_SUPERUSER=1 && composer reinstall yiisoft/yii2-bootstrap5 --no-interaction"
  php scripts/rebuild-yii-extensions.php     || die "extensions.php 仍缺 yii2-bootstrap5。請手動: export COMPOSER_ALLOW_SUPERUSER=1 && composer reinstall yiisoft/yii2-bootstrap5 --no-interaction"
  extensions_php_ok \
    || die "extensions.php 仍缺 yii2-bootstrap5。請手動: php scripts/rebuild-yii-extensions.php"
}

# ---------- 前置檢查 ----------
command -v php >/dev/null || die "需要 php"
COMPOSER_BIN="$(find_composer)" || die "需要 composer（請安裝或將 /usr/local/bin 加入 PATH）"
export PATH="$(dirname "$COMPOSER_BIN"):$PATH"
# root 下非互動 composer 會禁用插件 → extensions.php 空檔；必須允許
if [[ "$(id -u)" -eq 0 ]]; then
  export COMPOSER_ALLOW_SUPERUSER=1
fi
[[ -f composer.json && -f composer.lock ]] || die "請在專案根目錄執行（找不到 composer.json／lock）"
[[ -f docs/init_db.sql ]] || die "缺少 docs/init_db.sql"

log "專案根目錄: $ROOT"
log "本腳本僅適用全新安裝。升級請見 SETUP.md「從舊版升級」。"

# ---------- 收集參數 ----------
APP_ENV="${APP_ENV:-production}"
APP_BASE_PATH="${APP_BASE_PATH:-$ROOT}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-voting}"
DB_USERNAME="${DB_USERNAME:-voting}"
DB_PASSWORD="${DB_PASSWORD-}"
ALLOWED_HOSTS="${ALLOWED_HOSTS-}"
APP_PATH_UPLOADS="${APP_PATH_UPLOADS:-/var/www/uploads/voting}"
PHP_USER="${PHP_USER:-$(detect_php_user)}"
ADMIN_PASSWORD="${ADMIN_PASSWORD-}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD-}"

if [[ "$NON_INTERACTIVE" -eq 0 ]]; then
  log "請輸入安裝參數（直接 Enter 使用預設值）"
  prompt APP_ENV "APP_ENV" "$APP_ENV"
  prompt APP_BASE_PATH "APP_BASE_PATH" "$APP_BASE_PATH"
  prompt ALLOWED_HOSTS "ALLOWED_HOSTS（正式環境必填 FQDN，逗號分隔）" "${ALLOWED_HOSTS:-localhost}"
  prompt DB_HOST "DB_HOST" "$DB_HOST"
  prompt DB_PORT "DB_PORT" "$DB_PORT"
  prompt DB_NAME "DB_NAME" "$DB_NAME"
  prompt DB_USERNAME "DB_USERNAME" "$DB_USERNAME"
  prompt_secret DB_PASSWORD "DB_PASSWORD（應用程式連線密碼）"
  prompt APP_PATH_UPLOADS "APP_PATH_UPLOADS" "$APP_PATH_UPLOADS"
  prompt PHP_USER "PHP_USER（PHP-FPM 執行身分）" "$PHP_USER"

  local_ans=0
  ask_yes local_ans "是否建立資料庫與使用者（需 root／高權限）？" "Y"
  WITH_DB="$local_ans"
  if [[ "$WITH_DB" -eq 1 ]]; then
    prompt_secret DB_ROOT_PASSWORD "MySQL/MariaDB root 密碼（本機 socket 可留空）"
  fi

  ask_yes local_ans "是否匯入 docs/init_db.sql（僅空庫）？" "Y"
  INIT_DB="$local_ans"

  ask_yes local_ans "是否建立 admin 帳號？" "Y"
  CREATE_ADMIN="$local_ans"
  if [[ "$CREATE_ADMIN" -eq 1 ]]; then
    prompt_secret ADMIN_PASSWORD "admin 密碼（≥8，含字母與數字）"
  fi

  if [[ "$APP_ENV" == "production" ]]; then
    ask_yes local_ans "正式環境使用 composer --no-dev？" "Y"
    NO_DEV="$local_ans"
  fi
fi

[[ -n "$DB_PASSWORD" ]] || die "DB_PASSWORD 必填"
if [[ "$APP_ENV" == "production" && -z "$ALLOWED_HOSTS" ]]; then
  die "production 必須設定 ALLOWED_HOSTS"
fi
if [[ "$CREATE_ADMIN" -eq 1 && -z "$ADMIN_PASSWORD" ]]; then
  die "CREATE_ADMIN 需要 ADMIN_PASSWORD"
fi

# ---------- Composer ----------
if [[ "$SKIP_COMPOSER" -eq 0 ]]; then
  log "composer install ($COMPOSER_BIN)"
  if [[ -L vendor ]]; then
    die "vendor 是 symlink（共用舊 vendor）。請 rm vendor 後重跑，勿使用舊框架共用目錄"
  fi
  "$COMPOSER_BIN" config allow-plugins.yiisoft/yii2-composer true
  if [[ "$NO_DEV" -eq 1 ]]; then
    "$COMPOSER_BIN" install --no-interaction --prefer-dist --no-dev --optimize-autoloader
  else
    "$COMPOSER_BIN" install --no-interaction --prefer-dist
  fi
fi

ensure_extensions_php

# ---------- 目錄 ----------
log "建立可寫目錄"
bash scripts/ensure-writable-dirs.sh
mkdir -p "$APP_PATH_UPLOADS"

if [[ "$(id -u)" -eq 0 ]]; then
  log "設定擁有者 PHP_USER=$PHP_USER"
  chown -R "${PHP_USER}:${PHP_USER}" runtime web/assets "$APP_PATH_UPLOADS" || true
  chmod -R 775 runtime web/assets || true
else
  log "非 root：請稍後執行: sudo chown -R ${PHP_USER}:${PHP_USER} runtime web/assets ${APP_PATH_UPLOADS}"
fi

# ---------- .env ----------
log "設定 .env"
if [[ ! -f .env ]]; then
  cp .env.example .env
fi

COOKIE_VALIDATION_KEY="${COOKIE_VALIDATION_KEY:-$(php -r 'echo bin2hex(random_bytes(32));')}"
MASTER_KEY="${MASTER_KEY:-$(php -r 'echo base64_encode(random_bytes(64));')}"
MASTER_KEY_FILE="${MASTER_KEY_FILE:-/etc/voting/master.key}"

set_env_key APP_ENV "$APP_ENV"
set_env_key APP_BASE_PATH "$APP_BASE_PATH"
set_env_key APP_PATH_UPLOADS "$APP_PATH_UPLOADS"
set_env_key DB_HOST "$DB_HOST"
set_env_key DB_PORT "$DB_PORT"
set_env_key DB_NAME "$DB_NAME"
set_env_key DB_USERNAME "$DB_USERNAME"
set_env_key DB_PASSWORD "$DB_PASSWORD"
set_env_key ALLOWED_HOSTS "$ALLOWED_HOSTS"
set_env_key COOKIE_VALIDATION_KEY "$COOKIE_VALIDATION_KEY"

if [[ "$APP_ENV" == "production" ]]; then
  if [[ ! -f "$MASTER_KEY_FILE" ]]; then
    [[ "$(id -u)" -eq 0 ]] || die "正式環境需 root 寫入 $MASTER_KEY_FILE，或先 export VOTING_MASTER_KEY=…"
    mkdir -p "$(dirname "$MASTER_KEY_FILE")"
    printf '%s\n' "$MASTER_KEY" > "$MASTER_KEY_FILE"
    chmod 600 "$MASTER_KEY_FILE"
    chown "${PHP_USER}:${PHP_USER}" "$MASTER_KEY_FILE" 2>/dev/null || true
    log "已寫入主金鑰: $MASTER_KEY_FILE（0600, owner=$PHP_USER）"
  else
    log "沿用既有主金鑰檔: $MASTER_KEY_FILE"
    MASTER_KEY="$(tr -d '\r\n' < "$MASTER_KEY_FILE")"
  fi
  if grep -qE '^[# ]*MASTER_KEY=' .env 2>/dev/null; then
    sed -i -E 's/^[# ]*MASTER_KEY=.*/# MASTER_KEY=  # production: use key file or VOTING_MASTER_KEY/' .env
  fi
  export VOTING_MASTER_KEY="$MASTER_KEY"
else
  set_env_key MASTER_KEY "$MASTER_KEY"
  log "已寫入開發用 MASTER_KEY 至 .env"
fi

chmod 640 .env || true
if [[ "$(id -u)" -eq 0 ]]; then
  PHP_GROUP="$(id -gn "$PHP_USER" 2>/dev/null || echo "$PHP_USER")"
  chown "$(id -un 2>/dev/null || echo root):${PHP_GROUP}" .env 2>/dev/null \
    || chown "${PHP_USER}:${PHP_GROUP}" .env 2>/dev/null || true
fi

# ---------- 資料庫 ----------
if [[ "$WITH_DB" -eq 1 ]]; then
  log "建立資料庫與使用者"
  command -v mysql >/dev/null || die "需要 mysql 客戶端"
  mysql_root_cmd -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  # 使用者：以 localhost 為主（可再擴充）
  mysql_root_cmd -e "CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" \
    || mysql_root_cmd -e "CREATE USER '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" || true
  mysql_root_cmd -e "ALTER USER '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" 2>/dev/null || true
  mysql_root_cmd -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USERNAME}'@'localhost'; FLUSH PRIVILEGES;"
fi

if [[ "$INIT_DB" -eq 1 ]]; then
  log "匯入 docs/init_db.sql"
  command -v mysql >/dev/null || die "需要 mysql 客戶端"
  cnt="$(table_count | tr -d '[:space:]')"
  if [[ "${cnt:-0}" -gt 0 && "$FORCE_INIT_DB" -eq 0 ]]; then
    die "資料庫 ${DB_NAME} 已有 ${cnt} 張表。拒絕匯入 init_db.sql（避免清空資料）。升級請見 SETUP.md；若確定新裝可加 --force-init-db"
  fi
  if [[ "$FORCE_INIT_DB" -eq 1 && "${cnt:-0}" -gt 0 ]]; then
    log "警告: --force-init-db，將對既有庫匯入 init_db.sql"
  fi
  mysql_cmd "$DB_NAME" < docs/init_db.sql
  log "init_db.sql 完成"
fi

# ---------- admin ----------
if [[ "$CREATE_ADMIN" -eq 1 ]]; then
  log "建立 admin"
  php yii admin/create --password="$ADMIN_PASSWORD"
fi

# ---------- 驗證 ----------
log "php yii config/validate"
php yii config/validate || die "config/validate 失敗"

log "安裝完成"
cat <<EOF

下一步：
  1. 確認 Web Server Document Root 指向: ${APP_BASE_PATH}/web
  2. 瀏覽器開啟網站並以 admin 登入
  3. 正式環境主金鑰已寫入 /etc/voting/master.key（或 VOTING_MASTER_KEY）；確認 PHP-FPM 可讀

升級／更新既有系統：勿再執行本腳本的 --init-db；見 SETUP.md「從舊版升級」。
EOF
