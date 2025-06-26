#!/usr/bin/env bash
set -euo pipefail

if [ $# -ne 1 ]; then
  echo "Usage: $0 path/to/backup.sql.gz" >&2
  exit 1
fi
BACKUP_FILE="$1"

# project root is two levels up from this script
PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/.env"

# load DB_* vars
export $(grep -v '^#' "$ENV_FILE" | grep '^DB_' | xargs)

# log file
LOG_FILE="$PROJECT_ROOT/storage/logs/db_restore.log"
mkdir -p "$(dirname "$LOG_FILE")"

# simple logger
log() { echo "[$(date +'%Y-%m-%d %H:%M:%S')] [$$] [$(whoami)] $*" >> "$LOG_FILE"; }

# on any error, log and exit
trap 'log "ERROR during restore of $DB_DATABASE from $BACKUP_FILE"; exit 1' ERR

log "STARTING restore of $BACKUP_FILE into $DB_DATABASE"

[ -f "$BACKUP_FILE" ] || { log "Backup file not found: $BACKUP_FILE"; echo "Not found: $BACKUP_FILE" >&2; exit 1; }

read -p "⚠️  This will DROP and re-create $DB_DATABASE. Continue? (y/N) " yn
case "$yn" in
  [Yy]*) ;;
  *) log "ABORTED restore of $DB_DATABASE"; echo "Aborted."; exit 1 ;;
esac

# drop & recreate database
MYSQL_PWD="$DB_PASSWORD" \
  mysql \
    -h "$DB_HOST" -P "${DB_PORT:-3306}" \
    -u "$DB_USERNAME" \
    -e "DROP DATABASE IF EXISTS \`$DB_DATABASE\`; CREATE DATABASE \`$DB_DATABASE\`;"

# import
gunzip < "$BACKUP_FILE" | \
  MYSQL_PWD="$DB_PASSWORD" \
  mysql \
    -h "$DB_HOST" -P "${DB_PORT:-3306}" \
    -u "$DB_USERNAME" \
    "$DB_DATABASE"

log "SUCCESS restored $DB_DATABASE from $BACKUP_FILE"
echo "✔ Restored $DB_DATABASE from $BACKUP_FILE"
