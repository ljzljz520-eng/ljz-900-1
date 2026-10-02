#!/usr/bin/env bash
SOCK="$HOME/mysqlrun/mysqld.sock"
pkill -f "php -S 127.0.0.1" 2>/dev/null && echo "[stop] PHP server stopped" || echo "[stop] PHP server not running"
if mariadb-admin --socket="$SOCK" ping >/dev/null 2>&1; then
  mariadb-admin --socket="$SOCK" shutdown && echo "[stop] MariaDB stopped"
fi
