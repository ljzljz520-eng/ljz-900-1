#!/usr/bin/env bash
# 启动 MariaDB（用户态）+ PHP 内置服务器
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
RUNTIME="$ROOT/.runtime"
export LD_LIBRARY_PATH="$RUNTIME/mariadb/usr/lib/aarch64-linux-gnu:$RUNTIME/mariadb/lib/aarch64-linux-gnu"
MH="$RUNTIME/mariadb/usr"
PORT="${PORT:-8000}"

# 启动 MariaDB
if [ ! -d "$RUNTIME/mysql-data/mysql" ]; then
    echo "初始化 MariaDB 数据目录..."
    mkdir -p "$RUNTIME/mysql-data" "$RUNTIME/run"
    "$MH/bin/mariadb-install-db" --no-defaults --basedir="$MH" \
        --datadir="$RUNTIME/mysql-data" --auth-root-authentication-method=normal >/dev/null
fi
if ! "$MH/bin/mariadb-admin" --socket="$RUNTIME/run/mysql.sock" -u root ping >/dev/null 2>&1; then
    echo "启动 MariaDB (127.0.0.1:3307)..."
    nohup "$MH/sbin/mariadbd" --no-defaults --basedir="$MH" \
        --datadir="$RUNTIME/mysql-data" --socket="$RUNTIME/run/mysql.sock" \
        --port=3307 --bind-address=127.0.0.1 \
        --pid-file="$RUNTIME/run/mariadb.pid" --skip-name-resolve \
        > "$RUNTIME/run/mariadb.log" 2>&1 &
    for i in $(seq 1 30); do
        "$MH/bin/mariadb-admin" --socket="$RUNTIME/run/mysql.sock" -u root ping >/dev/null 2>&1 && break
        sleep 1
    done
fi

# 初始化数据表和账号（幂等）
"$RUNTIME/php" "$ROOT/bin/setup.php"

# 启动 PHP 内置服务器
echo "启动 Web 服务: http://0.0.0.0:${PORT}"
cd "$ROOT"
exec "$RUNTIME/php" -S "0.0.0.0:${PORT}" -t public public/router.php
