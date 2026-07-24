#!/usr/bin/env bash
set -Eeuo pipefail

APP_ROOT="${APP_ROOT:-/var/www/sistem-pos-bangunan}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
RELEASE_KEEP="${RELEASE_KEEP:-5}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"
DEPLOY_DRY_RUN="${DEPLOY_DRY_RUN:-0}"
SOURCE_DIR="${SOURCE_DIR:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}"

log() {
    printf '[deploy] %s\n' "$*"
}

gagal() {
    printf '[deploy] GAGAL: %s\n' "$*" >&2
    exit 1
}

perlu_program() {
    command -v "$1" >/dev/null 2>&1 || gagal "Program '$1' tidak ditemukan pada PATH."
}

angka_positif() {
    [[ "$1" =~ ^[1-9][0-9]*$ ]] || gagal "$2 harus berupa angka positif."
}

perlu_program git
perlu_program tar
perlu_program flock
perlu_program "$PHP_BIN"
perlu_program "$COMPOSER_BIN"

angka_positif "$RELEASE_KEEP" "RELEASE_KEEP"
angka_positif "$BACKUP_RETENTION_DAYS" "BACKUP_RETENTION_DAYS"

SOURCE_DIR="$(cd "$SOURCE_DIR" && pwd)"
cd "$SOURCE_DIR"

git rev-parse --is-inside-work-tree >/dev/null 2>&1 || gagal "SOURCE_DIR bukan repository Git."
[[ -z "$(git status --porcelain)" ]] || gagal "Repository sumber memiliki perubahan yang belum dikomit."

COMMIT_SHA="$(git rev-parse HEAD)"
SHORT_SHA="$(git rev-parse --short=12 HEAD)"
RELEASE_ID="$(date +%Y%m%d-%H%M%S)-${SHORT_SHA}"
RELEASES_DIR="$APP_ROOT/releases"
SHARED_DIR="$APP_ROOT/shared"
CURRENT_LINK="$APP_ROOT/current"
RELEASE_DIR="$RELEASES_DIR/$RELEASE_ID"
ENV_FILE="$SHARED_DIR/.env"
STORAGE_DIR="$SHARED_DIR/storage"
BACKUP_DIR="$SHARED_DIR/backups/database"
LOCK_FILE="$APP_ROOT/.deploy.lock"

[[ -f "$ENV_FILE" ]] || gagal "Environment produksi tidak ditemukan di $ENV_FILE."

if [[ "$DEPLOY_DRY_RUN" == "1" ]]; then
    log "Simulasi deployment berhasil."
    log "Sumber       : $SOURCE_DIR"
    log "Commit       : $COMMIT_SHA"
    log "Release      : $RELEASE_DIR"
    log "Current link : $CURRENT_LINK"
    log "Backup       : $BACKUP_DIR"
    exit 0
fi

mkdir -p "$RELEASES_DIR" "$SHARED_DIR" "$BACKUP_DIR"
exec 9>"$LOCK_FILE"
flock -n 9 || gagal "Deployment lain sedang berjalan."

CURRENT_SEBELUM=""
if [[ -L "$CURRENT_LINK" ]]; then
    CURRENT_SEBELUM="$(readlink -f "$CURRENT_LINK")"
fi

aplikasi_naik() {
    local target=""

    if [[ -L "$CURRENT_LINK" && -f "$CURRENT_LINK/artisan" ]]; then
        target="$CURRENT_LINK"
    elif [[ -n "$CURRENT_SEBELUM" && -f "$CURRENT_SEBELUM/artisan" ]]; then
        target="$CURRENT_SEBELUM"
    fi

    if [[ -n "$target" ]]; then
        "$PHP_BIN" "$target/artisan" up >/dev/null 2>&1 || true
    fi
}

bersihkan_release_gagal() {
    aplikasi_naik

    if [[ -d "$RELEASE_DIR" && "$(readlink -f "$CURRENT_LINK" 2>/dev/null || true)" != "$RELEASE_DIR" ]]; then
        rm -rf "$RELEASE_DIR"
    fi
}

trap bersihkan_release_gagal ERR INT TERM

log "Membuat release $RELEASE_ID dari commit $COMMIT_SHA."
mkdir -p "$RELEASE_DIR"
git archive "$COMMIT_SHA" | tar -x -C "$RELEASE_DIR"

mkdir -p \
    "$STORAGE_DIR/app/private" \
    "$STORAGE_DIR/framework/cache/data" \
    "$STORAGE_DIR/framework/sessions" \
    "$STORAGE_DIR/framework/views" \
    "$STORAGE_DIR/logs"

rm -rf "$RELEASE_DIR/storage"
ln -s "$STORAGE_DIR" "$RELEASE_DIR/storage"
ln -s "$ENV_FILE" "$RELEASE_DIR/.env"
mkdir -p "$RELEASE_DIR/bootstrap/cache"
chmod -R ug+rwX "$STORAGE_DIR" "$RELEASE_DIR/bootstrap/cache"

log "Memasang dependency produksi."
(
    cd "$RELEASE_DIR"
    "$COMPOSER_BIN" install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-progress
    "$PHP_BIN" scripts/salin-aset-template.php
)

if [[ -n "$CURRENT_SEBELUM" && -f "$CURRENT_SEBELUM/artisan" ]]; then
    log "Mengaktifkan maintenance mode pada release aktif."
    "$PHP_BIN" "$CURRENT_SEBELUM/artisan" down --retry=60
fi

log "Membuat backup database sebelum migration."
"$PHP_BIN" "$RELEASE_DIR/artisan" sistem:backup-database \
    --direktori="$BACKUP_DIR" \
    --retensi-hari="$BACKUP_RETENTION_DAYS"

log "Menjalankan migration paten dan cache produksi."
"$PHP_BIN" "$RELEASE_DIR/artisan" migrate --force
"$PHP_BIN" "$RELEASE_DIR/artisan" optimize:clear
"$PHP_BIN" "$RELEASE_DIR/artisan" optimize
"$PHP_BIN" "$RELEASE_DIR/artisan" sistem:periksa-produksi --ketat

log "Mengganti symlink current secara atomik."
NEXT_LINK="$APP_ROOT/.current-next"
rm -f "$NEXT_LINK"
ln -s "$RELEASE_DIR" "$NEXT_LINK"
mv -Tf "$NEXT_LINK" "$CURRENT_LINK"

printf '%s\n' "$COMMIT_SHA" > "$SHARED_DIR/DEPLOYED_COMMIT"
printf '%s\n' "$RELEASE_ID" > "$SHARED_DIR/DEPLOYED_RELEASE"

"$PHP_BIN" "$CURRENT_LINK/artisan" up
trap - ERR INT TERM

log "Membersihkan release lama; mempertahankan $RELEASE_KEEP release terbaru."
mapfile -t RELEASE_LAMA < <(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' \
    | sort -nr \
    | awk -v keep="$RELEASE_KEEP" 'NR > keep {sub(/^[^ ]+ /, ""); print}')

for release in "${RELEASE_LAMA[@]:-}"; do
    [[ -n "$release" ]] || continue
    [[ "$(readlink -f "$CURRENT_LINK")" == "$(readlink -f "$release")" ]] && continue
    rm -rf "$release"
done

log "Deployment berhasil. Release aktif: $RELEASE_ID ($COMMIT_SHA)."
