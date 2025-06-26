#!/usr/bin/env bash
set -euo pipefail

if [ $# -ne 1 ]; then
  echo "Usage: $0 path/to/backup.sql.gz" >&2
  exit 1
fi
BACKUP_FILE="$1"
[ -f "$BACKUP_FILE" ] || { echo "Not found: $BACKUP_FILE" >&2; exit 1; }

PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/.env"

export $(grep -v '^#' "$ENV_FILE" | grep '^DB_' | xargs)

read -p "⚠️  This will DROP and re-create $DB_DATABASE. Continue? (y/N) " yn
case "$yn" in [Yy]*) ;; *) echo "Aborted."; exit 1 ;; esac

MYSQL_PWD="$DB_PASSWORD" \
  mysql \
    -h "$DB_HOST" -P "${DB_PORT:-3306}" \
    -u "$DB_USERNAME" \
    -e "DROP DATABASE IF EXISTS \`$DB_DATABASE\`; CREATE DATABASE \`$DB_DATABASE\`;"

gunzip < "$BACKUP_FILE" | \
  MYSQL_PWD="$DB_PASSWORD" \
  mysql \
    -h "$DB_HOST" -P "${DB_PORT:-3306}" \
    -u "$DB_USERNAME" \
    "$DB_DATABASE"

echo "✔ Restored $DB_DATABASE from $BACKUP_FILE"
