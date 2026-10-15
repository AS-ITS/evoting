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
CHECK_ONLY=0
REENCRYPT_DB_PASSWORD=0

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
  --check-only        只做環境檢查後結束
  --reencrypt-db-password  強制重加密 .env DB_PASSWORD（沿用 master.key 時預設保留既有密文）
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
    --check-only) CHECK_ONLY=1 ;;
    --reencrypt-db-password) REENCRYPT_DB_PASSWORD=1 ;;
    -h|--help) usage; exit 0 ;;
    *) echo "未知選項: $1" >&2; usage; exit 2 ;;
  esac
  shift
done

log() { echo "==> $*"; }
warn() { echo "WARN: $*" >&2; }
die() { echo "FAIL: $*" >&2; exit 1; }

prompt() {
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


prompt_admin_password() {
  # 迴圈直到符合 admin/create 規則：≥8、含字母與數字
  local var="$1"
  local val confirm
  if [[ "$NON_INTERACTIVE" -eq 1 ]]; then
    val="${!var-}"
    if [[ -z "$val" ]]; then
      die "CREATE_ADMIN 需要 ADMIN_PASSWORD"
    fi
    if [[ ${#val} -lt 8 || ! "$val" =~ [A-Za-z] || ! "$val" =~ [0-9] ]]; then
      die "ADMIN_PASSWORD 須≥8字元且含英文字母與數字"
    fi
    printf -v "$var" '%s' "$val"
    return
  fi
  while true; do
    read -r -s -p "admin 密碼（≥8，含英文字母與數字）: " val || true
    echo
    if [[ -z "$val" ]]; then
      echo "密碼不可空白，請重新輸入。"
      continue
    fi
    if [[ ${#val} -lt 8 ]]; then
      echo "密碼至少 8 字元，請重新輸入。"
      continue
    fi
    if [[ ! "$val" =~ [A-Za-z] || ! "$val" =~ [0-9] ]]; then
      echo "密碼須同時包含英文字母與數字，請重新輸入。"
      continue
    fi
    read -r -s -p "再輸入一次確認: " confirm || true
    echo
    if [[ "$val" != "$confirm" ]]; then
      echo "兩次輸入不一致，請重新輸入。"
      continue
    fi
    printf -v "$var" '%s' "$val"
    return
  done
}

prompt_allowed_hosts() {
  local var="$1"
  local val insecure bad
  if [[ "$NON_INTERACTIVE" -eq 1 ]]; then
    val="${!var-}"
    printf -v "$var" '%s' "$val"
    return
  fi
  while true; do
    if [[ "$APP_ENV" == "production" ]]; then
      read -r -p "ALLOWED_HOSTS（正式環境必填實際 FQDN，逗號分隔；不可 localhost）: " val || true
    else
      read -r -p "ALLOWED_HOSTS [${ALLOWED_HOSTS:-localhost}]: " val || true
      val="${val:-${ALLOWED_HOSTS:-localhost}}"
    fi
    val="$(echo "$val" | tr -d '[:space:]')"
    if [[ -z "$val" ]]; then
      echo "ALLOWED_HOSTS 不可空白。"
      continue
    fi
    if [[ "$APP_ENV" == "production" ]]; then
      bad=0
      IFS=',' read -ra _hosts <<< "$val"
      for h in "${_hosts[@]}"; do
        case "${h,,}" in
          \*|localhost|127.0.0.1|0.0.0.0) echo "不可使用不安全 host: $h"; bad=1 ;;
        esac
      done
      if [[ "$bad" -eq 1 ]]; then
        continue
      fi
    fi
    printf -v "$var" '%s' "$val"
    return
  done
}

ask_yes() {
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

get_env_key() {
  local key="$1" file="${2:-.env}"
  [[ -f "$file" ]] || return 0
  local line
  line="$(grep -E "^[[:space:]]*${key}=" "$file" 2>/dev/null | tail -1 || true)"
  [[ -n "$line" ]] || return 0
  printf '%s' "${line#*=}"
}

version_ge() {
  printf '%s\n%s\n' "$2" "$1" | sort -V | head -1 | grep -qx "$2"
}

php_version_id() {
  php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION.".".PHP_RELEASE_VERSION;'
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
  mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$@"
}

mysql_root_cmd() {
  if [[ -n "${DB_ROOT_PASSWORD-}" ]]; then
    mysql -h "$DB_HOST" -P "$DB_PORT" -u root -p"$DB_ROOT_PASSWORD" "$@"
  else
    mysql -u root "$@"
  fi
}

table_count() {
  mysql_cmd -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_type='BASE TABLE';" 2>/dev/null || echo "0"
}

db_schema_exists() {
  mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -N -e \
    "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='${DB_NAME}';" 2>/dev/null \
    | tr -d '[:space:]' || echo "0"
}

looks_encrypted_env_value() {
  local v="$1"
  [[ ${#v} -ge 24 ]] || return 1
  [[ "$v" =~ ^[A-Za-z0-9+/=]+$ ]] || return 1
  return 0
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
  "$COMPOSER_BIN" reinstall yiisoft/yii2-composer yiisoft/yii2 yiisoft/yii2-bootstrap5 \
    --no-interaction \
    || "$COMPOSER_BIN" update yiisoft/yii2-composer --with-dependencies --no-interaction --prefer-dist \
    || true
  if extensions_php_ok; then
    return 0
  fi
  log "改從 installed.json 重建 extensions.php…"
  [[ -f scripts/rebuild-yii-extensions.php ]] \
    || die "缺少 scripts/rebuild-yii-extensions.php（請一併更新此檔）。或: export COMPOSER_ALLOW_SUPERUSER=1 && composer reinstall yiisoft/yii2-bootstrap5 --no-interaction"
  php scripts/rebuild-yii-extensions.php \
    || die "extensions.php 仍缺 yii2-bootstrap5。請手動: export COMPOSER_ALLOW_SUPERUSER=1 && composer reinstall yiisoft/yii2-bootstrap5 --no-interaction"
  extensions_php_ok \
    || die "extensions.php 仍缺 yii2-bootstrap5。請手動: php scripts/rebuild-yii-extensions.php"
}

preflight_checks() {
  log "環境檢查…"
  local php_ver missing=() ext ver_raw ver web_ok=0

  command -v php >/dev/null || die "需要 php（建議 PHP 8.2+）"
  php_ver="$(php_version_id)"
  if ! version_ge "$php_ver" "8.2.0"; then
    die "PHP ${php_ver} < 8.2.0（composer.json 要求 >=8.2，無法繼續）"
  fi
  log "PHP ${php_ver} OK"

  for ext in pdo_mysql openssl mbstring gd curl json; do
    php -m 2>/dev/null | grep -qi "^${ext}$" || missing+=("$ext")
  done
  if [[ ${#missing[@]} -gt 0 ]]; then
    die "缺少 PHP 擴展: ${missing[*]}（見 SETUP.md）"
  fi
  log "必要 PHP 擴展 OK"

  missing=()
  for ext in ctype dom fileinfo iconv simplexml xml zip zlib; do
    php -m 2>/dev/null | grep -qi "^${ext}$" || missing+=("$ext")
  done
  if [[ ${#missing[@]} -gt 0 ]]; then
    warn "建議 PHP 擴展未偵測到: ${missing[*]}（通常不阻擋安裝）"
  fi

  if command -v mysql >/dev/null 2>&1; then
    ver_raw="$(mysql --version 2>/dev/null || true)"
    log "mysql client: ${ver_raw:-unknown}"
    ver=""
    if [[ -n "${DB_PASSWORD-}" ]]; then
      ver="$(mysql -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "${DB_USERNAME:-voting}" -p"${DB_PASSWORD}" -N -e 'SELECT VERSION();' 2>/dev/null || true)"
    fi
    if [[ -z "$ver" && "$(id -u)" -eq 0 ]]; then
      ver="$(mysql -N -e 'SELECT VERSION();' 2>/dev/null || true)"
    fi
    if [[ -n "$ver" ]]; then
      log "DB server VERSION=${ver}"
      if echo "$ver" | grep -qi mariadb; then
        local mver
        mver="$(echo "$ver" | grep -oE '[0-9]+\.[0-9]+(\.[0-9]+)?' | head -1)"
        if [[ -n "$mver" ]] && ! version_ge "$mver" "10.6.0"; then
          warn "MariaDB ${mver} < 建議 10.6（不阻擋安裝，請自行評估）"
        fi
      else
        local sver
        sver="$(echo "$ver" | grep -oE '^[0-9]+\.[0-9]+(\.[0-9]+)?' | head -1)"
        if [[ -n "$sver" ]] && ! version_ge "$sver" "8.0.0"; then
          warn "MySQL ${sver} < 建議 8.0（不阻擋安裝，請自行評估）"
        fi
      fi
    else
      warn "無法取得 DB server 版本（稍後建庫／連線時再驗證）"
    fi
  else
    warn "找不到 mysql 客戶端；若要建庫／init_db 會失敗"
  fi

  if command -v nginx >/dev/null 2>&1 || systemctl is-active --quiet nginx 2>/dev/null; then
    web_ok=1
    log "偵測到 nginx: $(nginx -v 2>&1 || true)"
  fi
  if command -v httpd >/dev/null 2>&1 || command -v apache2 >/dev/null 2>&1 \
    || systemctl is-active --quiet httpd 2>/dev/null || systemctl is-active --quiet apache2 2>/dev/null; then
    web_ok=1
    log "偵測到 Apache: $(httpd -v 2>&1 | head -1 || apache2 -v 2>&1 | head -1 || true)"
  fi
  if [[ "$web_ok" -eq 0 ]]; then
    warn "未偵測到 nginx／Apache（不阻擋安裝；請稍後設定 Document Root → web/）"
  fi

  if ! systemctl is-active --quiet php-fpm 2>/dev/null \
    && ! systemctl is-active --quiet php82-php-fpm 2>/dev/null \
    && ! systemctl is-active --quiet php8.2-fpm 2>/dev/null \
    && ! pgrep -f 'php-fpm: pool' >/dev/null 2>&1; then
    warn "未偵測到 PHP-FPM 執行中（不阻擋安裝；請確認 PHP_USER）"
  else
    log "PHP-FPM 似乎在執行中"
  fi
}

# ---------- 前置 ----------
COMPOSER_BIN="$(find_composer)" || die "需要 composer（請安裝或將 /usr/local/bin 加入 PATH）"
export PATH="$(dirname "$COMPOSER_BIN"):$PATH"
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
MASTER_KEY_FILE="${MASTER_KEY_FILE:-/etc/voting/master.key}"
MASTER_KEY_EXISTED=0
[[ -f "$MASTER_KEY_FILE" ]] && MASTER_KEY_EXISTED=1

if [[ "$NON_INTERACTIVE" -eq 0 ]]; then
  log "請輸入安裝參數（直接 Enter 使用預設值）"
  prompt APP_ENV "APP_ENV" "$APP_ENV"
  prompt APP_BASE_PATH "APP_BASE_PATH" "$APP_BASE_PATH"
  prompt_allowed_hosts ALLOWED_HOSTS
  prompt DB_HOST "DB_HOST" "$DB_HOST"
  prompt DB_PORT "DB_PORT" "$DB_PORT"
  prompt DB_NAME "DB_NAME" "$DB_NAME"
  prompt DB_USERNAME "DB_USERNAME" "$DB_USERNAME"
  prompt_secret DB_PASSWORD "DB_PASSWORD（應用程式連線密碼）"
  prompt APP_PATH_UPLOADS "APP_PATH_UPLOADS" "$APP_PATH_UPLOADS"
  prompt PHP_USER "PHP_USER（PHP-FPM 執行身分）" "$PHP_USER"
fi

[[ -n "$DB_PASSWORD" ]] || die "DB_PASSWORD 必填"
if [[ "$APP_ENV" == "production" ]]; then
  [[ -n "$ALLOWED_HOSTS" ]] || die "production 必須設定 ALLOWED_HOSTS"
  IFS=',' read -ra _ah <<< "$ALLOWED_HOSTS"
  for h in "${_ah[@]}"; do
    h="$(echo "$h" | tr -d '[:space:]')"
    case "${h,,}" in
      \*|localhost|127.0.0.1|0.0.0.0) die "production ALLOWED_HOSTS 不可含不安全 host: $h" ;;
    esac
  done
fi

preflight_checks
if [[ "$CHECK_ONLY" -eq 1 ]]; then
  log "check-only：環境檢查結束"
  exit 0
fi

DB_PRESENT=0
DB_TABLES=0
if command -v mysql >/dev/null 2>&1; then
  if [[ "$(db_schema_exists)" == "1" ]]; then
    DB_PRESENT=1
    DB_TABLES="$(table_count | tr -d '[:space:]' || echo 0)"
    log "偵測到資料庫 ${DB_NAME} 已存在（表數量=${DB_TABLES}）"
  else
    log "資料庫 ${DB_NAME} 尚不存在（或目前帳號無法讀 information_schema）"
  fi
fi

DEF_WITH_DB=Y
DEF_INIT_DB=Y
DEF_CREATE_ADMIN=Y
if [[ "$DB_PRESENT" -eq 1 ]]; then
  DEF_WITH_DB=N
  if [[ "${DB_TABLES:-0}" -gt 0 ]]; then
    DEF_INIT_DB=N
    log "已有業務表：預設跳過建庫與 init_db（升級請見 SETUP.md）"
  else
    DEF_INIT_DB=Y
    log "空庫：預設跳過建庫、可匯入 init_db"
  fi
fi

if [[ "$NON_INTERACTIVE" -eq 0 ]]; then
  local_ans=0
  ask_yes local_ans "是否建立資料庫與使用者（需 root／高權限）？" "$DEF_WITH_DB"
  WITH_DB="$local_ans"
  if [[ "$WITH_DB" -eq 1 ]]; then
    prompt_secret DB_ROOT_PASSWORD "MySQL/MariaDB root 密碼（本機 socket 可留空）"
  fi

  ask_yes local_ans "是否匯入 docs/init_db.sql（僅空庫）？" "$DEF_INIT_DB"
  INIT_DB="$local_ans"

  ask_yes local_ans "是否建立 admin 帳號？" "$DEF_CREATE_ADMIN"
  CREATE_ADMIN="$local_ans"
  if [[ "$CREATE_ADMIN" -eq 1 ]]; then
    prompt_admin_password ADMIN_PASSWORD
  fi

  if [[ "$APP_ENV" == "production" ]]; then
    ask_yes local_ans "正式環境使用 composer --no-dev？" "Y"
    NO_DEV="$local_ans"
  fi
else
  if [[ "$DB_PRESENT" -eq 1 ]]; then
    if [[ "$WITH_DB" -eq 1 ]]; then
      log "非互動：資料庫已存在，略過 --with-db"
      WITH_DB=0
    fi
    if [[ "$INIT_DB" -eq 1 && "${DB_TABLES:-0}" -gt 0 && "$FORCE_INIT_DB" -eq 0 ]]; then
      log "非互動：資料庫已有表，略過 --init-db（強制請 --force-init-db）"
      INIT_DB=0
    fi
  fi
fi

if [[ "$CREATE_ADMIN" -eq 1 ]]; then
  if [[ -z "$ADMIN_PASSWORD" ]]; then
    die "CREATE_ADMIN 需要 ADMIN_PASSWORD"
  fi
  if [[ ${#ADMIN_PASSWORD} -lt 8 || ! "$ADMIN_PASSWORD" =~ [A-Za-z] || ! "$ADMIN_PASSWORD" =~ [0-9] ]]; then
    die "ADMIN_PASSWORD 須≥8字元且含英文字母與數字"
  fi
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

EXISTING_COOKIE="$(get_env_key COOKIE_VALIDATION_KEY)"
EXISTING_DB_PASSWORD="$(get_env_key DB_PASSWORD)"
if [[ -n "${COOKIE_VALIDATION_KEY-}" ]]; then
  :
elif [[ -n "$EXISTING_COOKIE" ]]; then
  COOKIE_VALIDATION_KEY="$EXISTING_COOKIE"
  log "沿用 .env 既有 COOKIE_VALIDATION_KEY"
else
  COOKIE_VALIDATION_KEY="$(php -r 'echo bin2hex(random_bytes(32));')"
fi
MASTER_KEY="${MASTER_KEY:-$(php -r 'echo base64_encode(random_bytes(64));')}"

set_env_key APP_ENV "$APP_ENV"
set_env_key APP_BASE_PATH "$APP_BASE_PATH"
set_env_key APP_PATH_UPLOADS "$APP_PATH_UPLOADS"
set_env_key DB_HOST "$DB_HOST"
set_env_key DB_PORT "$DB_PORT"
set_env_key DB_NAME "$DB_NAME"
set_env_key DB_USERNAME "$DB_USERNAME"
set_env_key ALLOWED_HOSTS "$ALLOWED_HOSTS"
set_env_key COOKIE_VALIDATION_KEY "$COOKIE_VALIDATION_KEY"

# 主金鑰：只要 key file 存在，執行期一定優先用它（高於 .env MASTER_KEY）
# → 不可再寫一組開發用 MASTER_KEY，否則與 DB 密文錯位
USE_KEY_FILE=0
if [[ -f "$MASTER_KEY_FILE" ]]; then
  USE_KEY_FILE=1
  MASTER_KEY="$(tr -d '\r\n' < "$MASTER_KEY_FILE")"
  if [[ "$APP_ENV" != "production" ]]; then
    warn "偵測到既有 $MASTER_KEY_FILE：即使 APP_ENV=$APP_ENV 也會沿用此檔（略過 .env MASTER_KEY）"
  else
    log "沿用既有主金鑰檔: $MASTER_KEY_FILE"
  fi
elif [[ "$APP_ENV" == "production" ]]; then
  [[ "$(id -u)" -eq 0 ]] || die "正式環境需 root 寫入 $MASTER_KEY_FILE（會自動 mkdir），或先 export VOTING_MASTER_KEY=…"
  mkdir -p "$(dirname "$MASTER_KEY_FILE")"
  printf '%s\n' "$MASTER_KEY" > "$MASTER_KEY_FILE"
  chmod 600 "$MASTER_KEY_FILE"
  chown "${PHP_USER}:${PHP_USER}" "$MASTER_KEY_FILE" 2>/dev/null || true
  USE_KEY_FILE=1
  log "已寫入主金鑰: $MASTER_KEY_FILE（0600, owner=$PHP_USER；目錄不存在會自動建立）"
fi

if [[ "$USE_KEY_FILE" -eq 1 ]]; then
  export VOTING_MASTER_KEY="$MASTER_KEY"
  if grep -qE '^[# ]*MASTER_KEY=' .env 2>/dev/null; then
    sed -i -E 's/^[# ]*MASTER_KEY=.*/# MASTER_KEY=  # use key file or VOTING_MASTER_KEY/' .env
  fi
  # 與執行期同一把鑰匙 → DB_PASSWORD 必須用該鑰匙加密（或沿用同鑰匙密文）
  if [[ "$REENCRYPT_DB_PASSWORD" -eq 0 && "$MASTER_KEY_EXISTED" -eq 1 ]] \
    && looks_encrypted_env_value "$EXISTING_DB_PASSWORD"; then
    log "沿用 .env 既有加密 DB_PASSWORD（master.key 已存在；重加密請 --reencrypt-db-password）"
    set_env_key DB_PASSWORD "$EXISTING_DB_PASSWORD"
  else
    log "以目前主金鑰加密 DB_PASSWORD 寫入 .env"
    [[ -f scripts/encrypt-config-value.php ]] || die "缺少 scripts/encrypt-config-value.php"
    ENC_DB_PASSWORD="$(php scripts/encrypt-config-value.php "$DB_PASSWORD")" \
      || die "加密 DB_PASSWORD 失敗（請確認 VOTING_MASTER_KEY／$MASTER_KEY_FILE）"
    [[ -n "$ENC_DB_PASSWORD" ]] || die "加密結果為空"
    set_env_key DB_PASSWORD "$ENC_DB_PASSWORD"
  fi
else
  EXISTING_MASTER="$(get_env_key MASTER_KEY)"
  if [[ -n "$EXISTING_MASTER" ]]; then
    MASTER_KEY="$EXISTING_MASTER"
    log "沿用 .env 既有 MASTER_KEY"
  fi
  set_env_key MASTER_KEY "$MASTER_KEY"
  set_env_key DB_PASSWORD "$DB_PASSWORD"
  log "已寫入開發用 MASTER_KEY 與明文 DB_PASSWORD 至 .env（無 $MASTER_KEY_FILE）"
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
  # 既有使用者不 ALTER 密碼，避免覆蓋 DBA 設定
  mysql_root_cmd -e "CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" \
    || mysql_root_cmd -e "CREATE USER '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" || true
  mysql_root_cmd -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USERNAME}'@'localhost'; FLUSH PRIVILEGES;"
fi

if [[ "$INIT_DB" -eq 1 ]]; then
  log "匯入 docs/init_db.sql"
  command -v mysql >/dev/null || die "需要 mysql 客戶端"
  cnt="$(table_count | tr -d '[:space:]')"
  if [[ "${cnt:-0}" -gt 0 && "$FORCE_INIT_DB" -eq 0 ]]; then
    warn "資料庫 ${DB_NAME} 已有 ${cnt} 張表，跳過 init_db.sql（升級見 SETUP.md；強制請 --force-init-db）"
  else
    if [[ "$FORCE_INIT_DB" -eq 1 && "${cnt:-0}" -gt 0 ]]; then
      warn "--force-init-db：將對既有庫匯入 init_db.sql"
    fi
    mysql_cmd "$DB_NAME" < docs/init_db.sql
    log "init_db.sql 完成"
  fi
fi

# ---------- admin ----------
if [[ "$CREATE_ADMIN" -eq 1 ]]; then
  log "建立 admin"
  if php yii admin/create --password="$ADMIN_PASSWORD"; then
    :
  else
    warn "admin/create 失敗或帳號已存在 — 略過（若為新裝請檢查密碼規則／資料庫）"
  fi
fi

# ---------- 驗證 ----------
log "php yii config/validate"
# 確保 CLI 能讀到正式主金鑰（與 PHP-FPM 的 key file 路徑一致）
export VOTING_MASTER_KEY="${VOTING_MASTER_KEY:-${MASTER_KEY-}}"
if [[ -z "${VOTING_MASTER_KEY-}" && -f "${MASTER_KEY_FILE:-/etc/voting/master.key}" ]]; then
  export VOTING_MASTER_KEY="$(tr -d '\r\n' < "$MASTER_KEY_FILE")"
fi
set +e
php yii config/validate
_validate_rc=$?
set -e
[[ "$_validate_rc" -eq 0 ]] || die "config/validate 失敗 (exit=${_validate_rc})"

log "安裝完成"
cat <<EOF

下一步：
  1. 確認 Web Server Document Root 指向: ${APP_BASE_PATH}/web
  2. 瀏覽器開啟網站並以 admin 登入
  3. 正式環境主金鑰已寫入 /etc/voting/master.key（或 VOTING_MASTER_KEY）；確認 PHP-FPM 可讀

升級／更新既有系統：勿再執行本腳本的 --init-db；見 SETUP.md「從舊版升級」。
EOF
