#!/usr/bin/env bash
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
RUNTIME="$ROOT/.runtime"
[ -f "$RUNTIME/run/mariadb.pid" ] && kill "$(cat "$RUNTIME/run/mariadb.pid")" 2>/dev/null && echo "MariaDB 已停止"
pkill -f "php -S 0.0.0.0" 2>/dev/null && echo "PHP 服务已停止" || true
