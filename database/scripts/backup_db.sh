#!/usr/bin/env bash
set -euo pipefail

# project root is two levels up from this script
PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/.env"

# load DB_* vars
export $(grep -v '^#' "$ENV_FILE" | grep '^DB_' | xargs)

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

# prune anything older than 7 days
find "$BACKUP_DIR" -type f -name '*.sql.gz' -mtime +30 -delete

echo "✔ Backup complete: $FILE"
