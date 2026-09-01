#!/usr/bin/env bash
# Start MariaDB/MySQL with the workspace-persistent datadir (sandbox/dev).
# Production: use your platform's managed MySQL instead.
set -e
DATADIR="${MYSQL_DATA_DIR:-/home/user/mysql-data}"
if [ ! -d "$DATADIR/mysql" ]; then
  mariadb-install-db --datadir="$DATADIR" --user="$(whoami)" --auth-root-authentication-method=normal --skip-test-db
fi
exec /usr/sbin/mariadbd --no-defaults --datadir="$DATADIR" --socket="$DATADIR/mysql.sock" \
  --port="${MYSQL_PORT:-3306}" --bind-address=127.0.0.1 --user="$(whoami)" \
  --pid-file="$DATADIR/mysqld.pid" --log-error="$DATADIR/mysql.err"
