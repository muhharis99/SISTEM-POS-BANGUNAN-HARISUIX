#!/usr/bin/env bash
set -Eeuo pipefail

APP_ROOT="${APP_ROOT:-/var/www/sistem-pos-bangunan}"
PHP_BIN="${PHP_BIN:-php}"
ROLLBACK_BACKUP="${ROLLBACK_BACKUP:-1}"
ROLLBACK_DRY_RUN="${ROLLBACK_DRY_RUN:-0}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
TARGET_RELEASE="${1:-}"

log() {
    printf '[rollback] %s\n' "$*"
}

gagal() {
    printf '[rollback] GAGAL: %s\n' "$*" >&2
    exit 1
}

[[ -n "$TARGET_RELEASE" ]] || gagal "Gunakan: scripts/rollback-production.sh <id-release>."
command -v "$PHP_BIN" >/dev/null 2>&1 || gagal "Program PHP tidak ditemukan."
command -v flock >/dev/null 2>&1 || gagal "Program flock tidak ditemukan."

RELEASES_DIR="$APP_ROOT/releases"
CURRENT_LINK="$APP_ROOT/current"
SHARED_DIR="$APP_ROOT/shared"
BACKUP_DIR="$SHARED_DIR/backups/database"
LOCK_FILE="$APP_ROOT/.deploy.lock"

if [[ "$TARGET_RELEASE" = /* ]]; then
    TARGET_DIR="$(readlink -f "$TARGET_RELEASE")"
else
    TARGET_DIR="$(readlink -f "$RELEASES_DIR/$TARGET_RELEASE" 2>/dev/null || true)"
fi

[[ -n "$TARGET_DIR" && -d "$TARGET_DIR" ]] || gagal "Release target tidak ditemukan."
[[ "$TARGET_DIR" == "$(readlink -f "$RELEASES_DIR")"/* ]] || gagal "Release target harus berada di dalam $RELEASES_DIR."
[[ -f "$TARGET_DIR/artisan" ]] || gagal "Release target tidak memiliki berkas artisan."
[[ -L "$CURRENT_LINK" ]] || gagal "Symlink current belum tersedia."

CURRENT_SEBELUM="$(readlink -f "$CURRENT_LINK")"
[[ "$CURRENT_SEBELUM" != "$TARGET_DIR" ]] || gagal "Release target sudah menjadi release aktif."

if [[ "$ROLLBACK_DRY_RUN" == "1" ]]; then
    log "Simulasi rollback berhasil."
    log "Release aktif : $CURRENT_SEBELUM"
    log "Release target: $TARGET_DIR"
    log "Backup DB     : $ROLLBACK_BACKUP"
    exit 0
fi

exec 9>"$LOCK_FILE"
flock -n 9 || gagal "Deployment atau rollback lain sedang berjalan."

kembalikan_current() {
    local next="$APP_ROOT/.current-rollback-error"
    rm -f "$next"
    ln -s "$CURRENT_SEBELUM" "$next"
    mv -Tf "$next" "$CURRENT_LINK"
    "$PHP_BIN" "$CURRENT_LINK/artisan" up >/dev/null 2>&1 || true
}

trap kembalikan_current ERR INT TERM

log "Mengaktifkan maintenance mode."
"$PHP_BIN" "$CURRENT_LINK/artisan" down --retry=60

if [[ "$ROLLBACK_BACKUP" == "1" ]]; then
    log "Membuat backup keselamatan sebelum rollback aplikasi."
    "$PHP_BIN" "$CURRENT_LINK/artisan" sistem:backup-database \
        --direktori="$BACKUP_DIR" \
        --retensi-hari="$BACKUP_RETENTION_DAYS"
fi

log "Memeriksa release target."
"$PHP_BIN" "$TARGET_DIR/artisan" optimize:clear
"$PHP_BIN" "$TARGET_DIR/artisan" optimize
"$PHP_BIN" "$TARGET_DIR/artisan" sistem:periksa-produksi --ketat

NEXT_LINK="$APP_ROOT/.current-rollback"
rm -f "$NEXT_LINK"
ln -s "$TARGET_DIR" "$NEXT_LINK"
mv -Tf "$NEXT_LINK" "$CURRENT_LINK"

printf '%s\n' "$(basename "$TARGET_DIR")" > "$SHARED_DIR/DEPLOYED_RELEASE"
if [[ -d "$TARGET_DIR/.git" ]]; then
    git -C "$TARGET_DIR" rev-parse HEAD > "$SHARED_DIR/DEPLOYED_COMMIT"
else
    printf '%s\n' "tidak-diketahui" > "$SHARED_DIR/DEPLOYED_COMMIT"
fi

"$PHP_BIN" "$CURRENT_LINK/artisan" up
trap - ERR INT TERM

log "Rollback aplikasi berhasil ke $(basename "$TARGET_DIR")."
log "Database tidak di-rollback otomatis. Gunakan sistem:restore-database hanya berdasarkan prosedur dan persetujuan yang benar."
