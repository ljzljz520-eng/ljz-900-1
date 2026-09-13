#!/usr/bin/env bash
# 开发环境一键启动（MariaDB + PHP 内置服务器）
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
RUN="$ROOT/.runtime"
PHP="$RUN/bin/php"
MDB="$RUN/mariadb/usr"
export LD_LIBRARY_PATH="$MDB/lib/aarch64-linux-gnu"

# 启动 MariaDB（若未运行）
if [ ! -S "$RUN/run/mysql.sock" ]; then
  mkdir -p "$RUN/run"
  nohup "$MDB/sbin/mariadbd" --no-defaults --basedir="$MDB" \
    --datadir="$RUN/data" --socket="$RUN/run/mysql.sock" \
    --pid-file="$RUN/run/mariadb.pid" --port=3306 --bind-address=127.0.0.1 \
    --skip-name-resolve --log-error="$RUN/run/error.log" >/dev/null 2>&1 &
  sleep 3
fi

# 启动 PHP 开发服务器
exec "$PHP" -d upload_max_filesize=20M -d post_max_size=60M \
  -S 0.0.0.0:8000 -t "$ROOT/public" "$ROOT/public/router.php"
