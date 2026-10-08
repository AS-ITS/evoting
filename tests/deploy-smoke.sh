#!/usr/bin/env bash
# 部署 smoke：確認 Document Root=web/ 時基本路由可達
# 用法：先啟動 php -S localhost:8080 -t web web/router-test.php，再執行本腳本
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"
FAIL=0

check_http() {
    local label="$1"
    local path="$2"
    local expect="${3:-200}"
    local code
    code="$(curl -s -o /dev/null -w '%{http_code}' "${BASE_URL}${path}")"
    if [[ "$code" == "$expect" ]]; then
        echo "OK  ${label} (${path}) -> ${code}"
    else
        echo "FAIL ${label} (${path}) -> ${code} (expected ${expect})"
        FAIL=1
    fi
}

echo "==> Deploy smoke against ${BASE_URL}"
check_http "admin alias" "/admin"
check_http "vote detail" "/vote/vote-detail?voteID=AnonPartyTest"
check_http "site index" "/"

if [[ "${CHECK_TEST_ENTRY_BLOCKED:-}" == "1" ]]; then
    echo "==> Checking test entry is blocked (APP_ENV=production)"
    code="$(curl -s -o /dev/null -w '%{http_code}' "${BASE_URL}/index-test.php")"
    if [[ "$code" == "404" ]]; then
        echo "OK  test entry blocked (/index-test.php) -> 404"
    else
        echo "FAIL test entry should return 404 on production, got ${code}"
        FAIL=1
    fi
fi

if [[ "$FAIL" -ne 0 ]]; then
    echo "==> Deploy smoke FAILED"
    exit 1
fi

echo "==> Deploy smoke passed"
