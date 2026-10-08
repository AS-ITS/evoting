#!/usr/bin/env bash
# 開源 / 移轉釋出前檢查腳本（§15.5）
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

FAIL=0

warn() { echo "WARN: $*"; }
ok() { echo "OK  $*"; }
fail() { echo "FAIL: $*"; FAIL=1; }

echo "==> Open source prep: ${ROOT}"

# 1. 清空 runtime（保留目錄結構）
for sub in cache debug logs sessions; do
    if [[ -d "runtime/${sub}" ]]; then
        if find "runtime/${sub}" -mindepth 1 -delete; then
            ok "cleared runtime/${sub}"
        else
            fail "cannot fully clear runtime/${sub} (check ownership/permissions)"
        fi
    fi
done

# config/test.php 的 session savePath 必須預先存在；清理後重建空目錄。
mkdir -p runtime/cache runtime/cache-test runtime/debug runtime/logs runtime/sessions/test

# 2. 禁止提交的敏感檔
for f in .env web/.env master.key /etc/voting/master.key; do
    if [[ -f "$f" ]] && git rev-parse --is-inside-work-tree &>/dev/null; then
        if git ls-files --error-unmatch "$f" &>/dev/null; then
            fail "tracked sensitive file: $f"
        fi
    fi
done

# 3. 危險檔案不應存在
for f in web/phpinfo.php web/index-Backup.php; do
    if [[ -f "$f" ]]; then
        fail "dangerous file exists: $f"
    else
        ok "absent: $f"
    fi
done

# 4. composer.lock 應存在
if [[ -f composer.lock ]]; then
    ok "composer.lock present"
else
    fail "composer.lock missing — run composer update --lock"
fi


# 4b. Webix 不得回到應用程式碼／資產樹
if [[ -f assets/WebixAsset.php ]]; then
    fail "assets/WebixAsset.php must not exist (Webix removed)"
else
    ok "WebixAsset.php absent"
fi
leftover_webix=$(find assets frontend web/assets -type d -name 'webix_*' 2>/dev/null || true)
if [[ -n "$leftover_webix" ]]; then
    fail "Webix published leftover still on disk:"
    echo "$leftover_webix"
else
    ok "no webix_* directories under assets/frontend/web"
fi
if git rev-parse --is-inside-work-tree &>/dev/null; then
    tracked=$(git ls-files | grep -i webix || true)
    if [[ -n "$tracked" ]]; then
        fail "Webix still git-tracked:"
        echo "$tracked"
    else
        ok "Webix not tracked"
    fi
fi
if [[ ! -f LICENSE ]]; then
    fail "LICENSE missing"
else
    ok "LICENSE present"
fi

# 5. Secret scan（可選）
if command -v gitleaks >/dev/null 2>&1; then
    if gitleaks detect --source . --no-banner --redact 2>/dev/null; then
        ok "gitleaks: no leaks"
    else
        fail "gitleaks reported findings"
    fi
else
    warn "gitleaks not installed — skip secret scan"
fi

# 6. 部署 smoke（可選，需測試伺服器）
if [[ "${RUN_DEPLOY_SMOKE:-}" == "1" ]]; then
    bash tests/deploy-smoke.sh || FAIL=1
fi

if [[ "$FAIL" -ne 0 ]]; then
    echo "==> Open source prep FAILED"
    exit 1
fi

echo "==> Open source prep passed"
