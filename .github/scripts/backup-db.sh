#!/bin/bash
# Make a database backup before the workflow changes a site.
#
# Runs on the server, in the WordPress folder (public_html):
#   ssh host "cd <app path> && bash -s -- <label>" < backup-db.sh
#
# The backup goes to db-backups/ next to public_html, so the web cannot reach
# it. The last ten stay. When the backup fails, the script stops with an
# error, and the workflow changes nothing.

set -euo pipefail

label="${1:-backup}"
backups="$(cd .. && pwd)/db-backups"

mkdir -p "$backups"
chmod 700 "$backups"

file="$backups/$label-$(date -u +%Y%m%d-%H%M%S).sql.gz"

if ! wp db export - | gzip > "$file" || ! gzip -t "$file"; then
  rm -f "$file"
  echo "::error::The database backup failed, so nothing was changed."
  exit 1
fi

echo "Backup: $file ($(du -h "$file" | cut -f1))"

# Keep the last ten.
ls -1t "$backups"/*.sql.gz | tail -n +11 | xargs -r rm -f
