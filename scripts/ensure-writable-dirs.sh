#!/usr/bin/env bash
# 建立 Web／Console 執行所需的可寫目錄（session、cache、assets 等）。
# 供 install.sh 與 composer post-install 呼叫；不處理 chown（需 root／PHP_USER）。
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

mkdir -p \
  runtime/sessions \
  runtime/sessions/test \
  runtime/cache \
  runtime/cache-test \
  runtime/logs \
  runtime/storage \
  runtime/URI \
  runtime/HTML \
  runtime/CSS \
  runtime/debug \
  web/assets

chmod 755 yii 2>/dev/null || true

echo "OK: writable dirs under ${ROOT}/runtime and web/assets"
