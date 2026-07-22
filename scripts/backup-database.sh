#!/usr/bin/env bash
set -euo pipefail

AKAR_PROYEK="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BERKAS_ENV="${AKAR_PROYEK}/.env"
FOLDER_BACKUP="${AKAR_PROYEK}/backups/database"

if [[ ! -f "${BERKAS_ENV}" ]]; then
    echo "Berkas .env tidak ditemukan: ${BERKAS_ENV}" >&2
    exit 1
fi

ambil_env() {
    local kunci="$1"
    local nilai
    nilai="$(grep -E "^${kunci}=" "${BERKAS_ENV}" | tail -n 1 | cut -d '=' -f 2- || true)"
    nilai="${nilai%\"}"
    nilai="${nilai#\"}"
    printf '%s' "${nilai}"
}

DB_HOST="$(ambil_env DB_HOST)"
DB_PORT="$(ambil_env DB_PORT)"
DB_DATABASE="$(ambil_env DB_DATABASE)"
DB_USERNAME="$(ambil_env DB_USERNAME)"
DB_PASSWORD="$(ambil_env DB_PASSWORD)"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

if [[ -z "${DB_DATABASE}" || -z "${DB_USERNAME}" ]]; then
    echo "DB_DATABASE dan DB_USERNAME wajib diisi di .env." >&2
    exit 1
fi

if ! command -v mysqldump >/dev/null 2>&1; then
    echo "mysqldump belum terpasang atau tidak ada di PATH." >&2
    exit 1
fi

mkdir -p "${FOLDER_BACKUP}"
WAKTU="$(date '+%Y%m%d_%H%M%S')"
BERKAS_HASIL="${FOLDER_BACKUP}/${DB_DATABASE}_${WAKTU}.sql.gz"

export MYSQL_PWD="${DB_PASSWORD}"

mysqldump \
    --host="${DB_HOST}" \
    --port="${DB_PORT}" \
    --user="${DB_USERNAME}" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --hex-blob \
    --default-character-set=utf8mb4 \
    "${DB_DATABASE}" | gzip -9 > "${BERKAS_HASIL}"

unset MYSQL_PWD
sha256sum "${BERKAS_HASIL}" > "${BERKAS_HASIL}.sha256"

echo "Backup selesai: ${BERKAS_HASIL}"
echo "Checksum     : ${BERKAS_HASIL}.sha256"
