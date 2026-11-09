#!/usr/bin/env bash
# 執行 unit + functional 測試（acceptance 需另開測試伺服器，見 README）
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

# PHP 8.5 的 CLI 會忽略 register_argc_argv，ini_get 回傳 0，Codeception 5.1 因此誤判；明確開啟
CODECEPT=(php -d register_argc_argv=On vendor/bin/codecept)

FIXTURES="Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac,Group,GroupMember,Ballots,BallotsSelected,ResultsConfig"

echo "==> Load fixtures before unit"
echo "yes" | php yii fixture/load "$FIXTURES" --appconfig=config/console-test.php

echo "==> Unit tests"
"${CODECEPT[@]}" run unit --no-colors || exit 1

echo "==> Reload fixtures after unit (unit tests mutate voting_test)"
echo "yes" | php yii fixture/load "$FIXTURES" --appconfig=config/console-test.php

echo "==> Functional tests"
"${CODECEPT[@]}" run functional --no-colors || exit 1

if [[ "${RUN_ACCEPTANCE:-0}" == "1" ]]; then
    echo "==> Loading acceptance fixtures"
    echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac,ResultsConfig" \
        --appconfig=config/console-test.php

    echo "==> Acceptance tests (requires: php -S localhost:8080 -t web web/router-test.php)"
    "${CODECEPT[@]}" run acceptance --no-colors
fi

echo "==> All requested suites passed"
