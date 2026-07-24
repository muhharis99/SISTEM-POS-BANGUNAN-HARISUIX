#!/usr/bin/env bash
set -Eeuo pipefail

APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
PHP_BINARY="${PHP_BINARY:-php}"
BACKUP_DIRECTORY="${BACKUP_DIRECTORY:-${APP_ROOT}/storage/app/private/backups/database}"
MAX_BACKUP_AGE_HOURS="${MAX_BACKUP_AGE_HOURS:-24}"
RELEASE_PACKAGE="${RELEASE_PACKAGE:-}"

if [[ -z "${RELEASE_PACKAGE}" ]]; then
    echo "RELEASE_PACKAGE wajib menunjuk paket rilis final yang sudah disalin ke server." >&2
    exit 64
fi

if [[ ! -f "${RELEASE_PACKAGE}" ]]; then
    echo "Paket rilis final tidak ditemukan: ${RELEASE_PACKAGE}" >&2
    exit 66
fi

cd "${APP_ROOT}"

"${PHP_BINARY}" artisan sistem:periksa-produksi --ketat
"${PHP_BINARY}" artisan sistem:smoke-test-pascadeploy
"${PHP_BINARY}" artisan sistem:periksa-go-live \
    --ketat \
    --backup-direktori="${BACKUP_DIRECTORY}" \
    --maks-usia-backup="${MAX_BACKUP_AGE_HOURS}" \
    --paket="${RELEASE_PACKAGE}"

echo "Smoke test dan gate go-live berhasil."
