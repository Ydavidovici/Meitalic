#!/usr/bin/env bash
set -euo pipefail

# project root is two levels up from this script
PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/.env"

# load DB_* vars
export $(grep -v '^#' "$ENV_FILE" | grep '^DB_' | xargs)

# log file
LOG_FILE="$PROJECT_ROOT/storage/logs/db_backup.log"
mkdir -p "$(dirname "$LOG_FILE")"

# simple logger
log() { echo "[$(date +'%Y-%m-%d %H:%M:%S')] [$$] [$(whoami)] $*" >> "$LOG_FILE"; }

# on any error, log and exit
trap 'log "ERROR during backup of $DB_DATABASE"; exit 1' ERR

log "STARTING backup of $DB_DATABASE"

# ensure target dir exists
BACKUP_DIR="$PROJECT_ROOT/storage/app/database"
mkdir -p "$BACKUP_DIR"

# timestamped filename
TS="$(date +'%Y%m%d_%H%M%S')"
FILE="$BACKUP_DIR/${DB_DATABASE}_${TS}.sql.gz"

# dump + gzip
MYSQL_PWD="$DB_PASSWORD" \
  mysqldump \
    -h "$DB_HOST" -P "${DB_PORT:-3306}" \
    -u "$DB_USERNAME" \
    --single-transaction --quick --lock-tables=false \
    "$DB_DATABASE" \
  | gzip > "$FILE"

# prune anything older than 30 days
find "$BACKUP_DIR" -type f -name '*.sql.gz' -mtime +30 -delete

log "SUCCESS backup complete: $FILE"
echo "✔ Backup complete: $FILE"
