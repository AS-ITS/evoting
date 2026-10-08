#!/usr/bin/env bash
# 執行 unit + functional 測試（acceptance 需另開測試伺服器，見 README）
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

FIXTURES="Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac,Group,GroupMember,Ballots,BallotsSelected,ResultsConfig"

echo "==> Load fixtures before unit"
echo "yes" | php yii fixture/load "$FIXTURES" --appconfig=config/console-test.php

echo "==> Unit tests"
php vendor/bin/codecept run unit --no-colors || exit 1

echo "==> Reload fixtures after unit (unit tests mutate voting_test)"
echo "yes" | php yii fixture/load "$FIXTURES" --appconfig=config/console-test.php

echo "==> Functional tests"
php vendor/bin/codecept run functional --no-colors || exit 1

if [[ "${RUN_ACCEPTANCE:-0}" == "1" ]]; then
    echo "==> Loading acceptance fixtures"
    echo "yes" | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac,ResultsConfig" \
        --appconfig=config/console-test.php

    echo "==> Acceptance tests (requires: php -S localhost:8080 -t web web/router-test.php)"
    php vendor/bin/codecept run acceptance --no-colors
fi

echo "==> All requested suites passed"
