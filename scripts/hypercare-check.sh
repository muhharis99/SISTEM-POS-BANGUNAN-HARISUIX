#!/usr/bin/env bash
set -Eeuo pipefail

APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
PHP_BINARY="${PHP_BINARY:-php}"
BACKUP_DIRECTORY="${BACKUP_DIRECTORY:-${APP_ROOT}/storage/app/private/backups/database}"
MAX_BACKUP_AGE_HOURS="${MAX_BACKUP_AGE_HOURS:-24}"
HYPERCARE_LOG_DIR="${HYPERCARE_LOG_DIR:-${APP_ROOT}/storage/logs/hypercare}"
LOCK_FILE="${LOCK_FILE:-${APP_ROOT}/storage/framework/hypercare-check.lock}"

mkdir -p "${HYPERCARE_LOG_DIR}" "$(dirname "${LOCK_FILE}")"
chmod 700 "${HYPERCARE_LOG_DIR}" 2>/dev/null || true

exec 9>"${LOCK_FILE}"
if ! flock -n 9; then
    echo "Pemeriksaan hypercare lain masih berjalan." >&2
    exit 75
fi

cd "${APP_ROOT}"
WAKTU="$(date -u +%Y%m%dT%H%M%SZ)"
SMOKE_FILE="${HYPERCARE_LOG_DIR}/smoke-${WAKTU}.json"
GO_LIVE_FILE="${HYPERCARE_LOG_DIR}/go-live-${WAKTU}.json"

"${PHP_BINARY}" artisan sistem:smoke-test-pascadeploy --json | tee "${SMOKE_FILE}"
"${PHP_BINARY}" artisan sistem:periksa-go-live \
    --json \
    --backup-direktori="${BACKUP_DIRECTORY}" \
    --maks-usia-backup="${MAX_BACKUP_AGE_HOURS}" | tee "${GO_LIVE_FILE}"

chmod 600 "${SMOKE_FILE}" "${GO_LIVE_FILE}" 2>/dev/null || true
find "${HYPERCARE_LOG_DIR}" -type f -name '*.json' -mtime +14 -delete

echo "Pemeriksaan hypercare berhasil."
