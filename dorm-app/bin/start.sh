#!/usr/bin/env bash
# 一键启动便携 MariaDB + ThinkPHP 内置服务器（无 root 环境）
set -e
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOCAL="${LOCAL:-$HOME/local}"

# 载入本地 PHP / MariaDB 环境
if [ -f "$LOCAL/env.sh" ]; then
  # shellcheck disable=SC1090
  source "$LOCAL/env.sh"
fi

PORT="${PORT:-8088}"
SOCK="$HOME/mysqlrun/mysqld.sock"

# 1) 确保 MariaDB 在运行
if ! mariadb-admin --socket="$SOCK" ping >/dev/null 2>&1; then
  echo "[start] 启动 MariaDB ..."
  nohup mariadbd --defaults-file="$HOME/my.cnf" --user="$(whoami)" \
      --innodb-use-native-aio=0 > "$HOME/mysqld.log" 2>&1 &
  for i in $(seq 1 30); do
    mariadb-admin --socket="$SOCK" ping >/dev/null 2>&1 && break
    sleep 1
  done
fi
mariadb-admin --socket="$SOCK" ping

# 2) 首次运行自动建库建表 + 种子
mariadb --host=127.0.0.1 --user=dorm --password=dormpass dorm \
  < "$ROOT_DIR/database/schema.sql" 2>/dev/null || true

# 3) 启动 PHP 内置服务器
echo "[start] 应用地址: http://127.0.0.1:${PORT}"
cd "$ROOT_DIR/public"
exec php -S "127.0.0.1:${PORT}" -t . router.php
