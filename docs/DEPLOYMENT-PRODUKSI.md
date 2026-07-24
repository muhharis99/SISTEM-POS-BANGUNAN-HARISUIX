# Deployment Produksi — Sistem POS Toko Bangunan

Dokumen ini menjelaskan pemasangan aplikasi pada server Linux dengan Nginx, PHP-FPM 8.4, Composer, serta MySQL/MariaDB. Deployment dilakukan manual menggunakan release directory dan symlink atomik. Repositori tidak melakukan deployment otomatis ke server mana pun.

## 1. Arsitektur direktori

Struktur bawaan skrip:

```text
/var/www/sistem-pos-bangunan/
├── current -> releases/20260724-120000-abc123def456
├── releases/
│   ├── 20260724-120000-abc123def456/
│   └── 20260723-220000-fed654cba321/
├── shared/
│   ├── .env
│   ├── storage/
│   ├── backups/database/
│   ├── DEPLOYED_COMMIT
│   └── DEPLOYED_RELEASE
└── .deploy.lock
```

Nginx selalu menunjuk ke:

```text
/var/www/sistem-pos-bangunan/current/public
```

Data `.env`, lampiran privat, session file, cache file, log, dan backup tidak berada di dalam release. Semua release memakai `shared/storage` yang sama.

## 2. Persyaratan server

- Linux server dengan systemd.
- Nginx.
- PHP 8.4 CLI dan PHP-FPM.
- Extension PHP: `mbstring`, `dom`, `fileinfo`, `pdo_mysql`, `zlib`, dan extension standar Laravel.
- Composer 2.
- MySQL 8 atau MariaDB yang kompatibel dengan SQL paten.
- Program `mysql`, `mysqldump`, `git`, `tar`, dan `flock` tersedia pada `PATH`.
- Sertifikat TLS/HTTPS valid.
- Pengguna deployment menjadi anggota grup `www-data`.

Sebelum produksi, pastikan versi PHP dan Composer:

```bash
php -v
composer --version
mysql --version
mysqldump --version
```

## 3. Menyiapkan direktori bersama

Contoh dengan pengguna deployment bernama `deploy`:

```bash
sudo mkdir -p /var/www/sistem-pos-bangunan/{releases,shared/storage,shared/backups/database}
sudo chown -R deploy:www-data /var/www/sistem-pos-bangunan
sudo chmod -R 2770 /var/www/sistem-pos-bangunan/shared
```

Salin environment produksi:

```bash
sudo -u deploy cp .env.production.example /var/www/sistem-pos-bangunan/shared/.env
sudo chmod 600 /var/www/sistem-pos-bangunan/shared/.env
```

Isi nilai nyata pada `.env` di server. Jangan pernah mengomit `.env` produksi atau kredensial ke Git.

Generate `APP_KEY` sekali pada instalasi pertama:

```bash
APP_ROOT=/var/www/sistem-pos-bangunan
cp "$APP_ROOT/shared/.env" /tmp/pos-env-awal
php artisan key:generate --show
```

Masukkan hasilnya ke `APP_KEY` pada `$APP_ROOT/shared/.env`. Jangan mengganti `APP_KEY` setelah data terenkripsi atau session produksi dipakai kecuali melalui prosedur rotasi kunci yang terencana.

## 4. Environment produksi minimum

Nilai berikut wajib diperiksa:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.example.com
SESSION_DRIVER=file
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
DB_CONNECTION=mysql
```

`SESSION_DRIVER`, `CACHE_STORE`, dan `QUEUE_CONNECTION` tidak boleh menggunakan database karena SQL paten tidak menyediakan tabel infrastruktur Laravel.

`TRUSTED_PROXIES` harus kosong jika tidak ada reverse proxy HTTP di depan Nginx. Jika ada load balancer atau proxy tepercaya, isi alamat IP/CIDR secara eksplisit dan pisahkan dengan koma. Jangan memakai `*`.

## 5. Memasang konfigurasi PHP-FPM

Salin contoh pool:

```bash
sudo cp deploy/php-fpm/sistem-pos-bangunan.conf /etc/php/8.4/fpm/pool.d/sistem-pos-bangunan.conf
sudo php-fpm8.4 -t
sudo systemctl reload php8.4-fpm
```

Sesuaikan kapasitas `pm.max_children`, memory limit, ukuran upload, dan timeout berdasarkan RAM serta pola transaksi server.

Pastikan socket tersedia:

```bash
sudo ls -l /run/php/php8.4-fpm-sistem-pos.sock
```

## 6. Memasang konfigurasi Nginx

Salin contoh virtual host dan ganti domain serta lokasi sertifikat:

```bash
sudo cp deploy/nginx/sistem-pos-bangunan.conf /etc/nginx/sites-available/sistem-pos-bangunan.conf
sudo ln -s /etc/nginx/sites-available/sistem-pos-bangunan.conf /etc/nginx/sites-enabled/sistem-pos-bangunan.conf
sudo nginx -t
sudo systemctl reload nginx
```

Jangan aktifkan blok HTTPS sebelum sertifikat tersedia. Setelah aktif, pastikan HTTP selalu dialihkan ke HTTPS.

## 7. Menyiapkan repository sumber deployment

Simpan checkout repository pada lokasi yang berbeda dari `APP_ROOT`, misalnya:

```text
/opt/deploy/sistem-pos-bangunan-source
```

Checkout wajib bersih dan berada pada commit yang sudah lulus serta digabung ke `main`:

```bash
cd /opt/deploy/sistem-pos-bangunan-source
git fetch origin
git checkout main
git pull --ff-only origin main
git status --short
```

`git status --short` harus kosong. Skrip deployment akan menolak repository yang memiliki perubahan lokal.

Aktifkan executable bit pada server jika diperlukan:

```bash
chmod +x scripts/deploy-production.sh scripts/rollback-production.sh
```

## 8. Simulasi deployment

Simulasi memeriksa repository, program, `.env`, target release, dan parameter tanpa membuat release:

```bash
APP_ROOT=/var/www/sistem-pos-bangunan \
SOURCE_DIR=/opt/deploy/sistem-pos-bangunan-source \
DEPLOY_DRY_RUN=1 \
bash scripts/deploy-production.sh
```

## 9. Menjalankan deployment

```bash
cd /opt/deploy/sistem-pos-bangunan-source

APP_ROOT=/var/www/sistem-pos-bangunan \
SOURCE_DIR="$PWD" \
RELEASE_KEEP=5 \
BACKUP_RETENTION_DAYS=14 \
bash scripts/deploy-production.sh
```

Urutan deployment:

1. mengunci proses menggunakan `.deploy.lock`;
2. memastikan repository bersih;
3. membuat release dari commit Git saat ini;
4. menautkan `.env` dan `shared/storage`;
5. memasang dependency dengan `--no-dev`;
6. menyalin aset UBold lokal;
7. mengaktifkan maintenance mode pada release lama;
8. membuat backup database sebelum migration;
9. menjalankan migration dengan `--force`;
10. membuat config/route/view cache;
11. menjalankan `sistem:periksa-produksi --ketat`;
12. mengganti symlink `current` secara atomik;
13. menonaktifkan maintenance mode;
14. menghapus release lama berdasarkan retensi.

Jika proses gagal sebelum pergantian symlink, release lama diaktifkan kembali dan release baru yang gagal dihapus.

## 10. Pemeriksaan setelah deployment

Jalankan:

```bash
cd /var/www/sistem-pos-bangunan/current
php artisan sistem:periksa-produksi --ketat
php artisan skema:verifikasi --rinci
php artisan about
```

Periksa endpoint:

```bash
curl --fail --silent https://pos.example.com/up
curl --fail --silent https://pos.example.com/kesiapan
```

`/up` adalah liveness Laravel. `/kesiapan` memeriksa database, skema paten, dan direktori tulis. Endpoint tidak menampilkan nama database, username, password, path server, atau exception internal.

Periksa log:

```bash
sudo tail -n 100 /var/log/nginx/sistem-pos-bangunan.error.log
tail -n 100 /var/www/sistem-pos-bangunan/shared/storage/logs/laravel.log
```

## 11. Rollback aplikasi

Lihat daftar release:

```bash
ls -1dt /var/www/sistem-pos-bangunan/releases/*
```

Simulasi:

```bash
APP_ROOT=/var/www/sistem-pos-bangunan \
ROLLBACK_DRY_RUN=1 \
bash scripts/rollback-production.sh 20260723-220000-fed654cba321
```

Rollback nyata:

```bash
APP_ROOT=/var/www/sistem-pos-bangunan \
ROLLBACK_BACKUP=1 \
bash scripts/rollback-production.sh 20260723-220000-fed654cba321
```

Rollback aplikasi:

- membuat backup database keselamatan secara default;
- mengaktifkan maintenance mode;
- memeriksa release target;
- memindahkan symlink `current` secara atomik;
- mengaktifkan aplikasi kembali.

**Rollback aplikasi tidak melakukan rollback database otomatis.** Restore database hanya dilakukan bila ada keputusan operasional yang jelas, backup yang terverifikasi, dan analisis dampak data transaksi.

## 12. Backup terjadwal

Salin unit systemd:

```bash
sudo cp deploy/systemd/sistem-pos-backup.service /etc/systemd/system/
sudo cp deploy/systemd/sistem-pos-backup.timer /etc/systemd/system/
sudo mkdir -p /var/backups/sistem-pos-bangunan/database
sudo chown -R www-data:www-data /var/backups/sistem-pos-bangunan
sudo chmod 700 /var/backups/sistem-pos-bangunan/database
sudo systemctl daemon-reload
sudo systemctl enable --now sistem-pos-backup.timer
```

Periksa jadwal dan hasil:

```bash
systemctl list-timers sistem-pos-backup.timer
sudo systemctl start sistem-pos-backup.service
sudo journalctl -u sistem-pos-backup.service -n 100 --no-pager
```

## 13. Izin berkas

Rekomendasi:

```bash
sudo chown -R deploy:www-data /var/www/sistem-pos-bangunan
sudo find /var/www/sistem-pos-bangunan/releases -type d -exec chmod 2750 {} \;
sudo find /var/www/sistem-pos-bangunan/releases -type f -exec chmod 0640 {} \;
sudo chmod -R 2770 /var/www/sistem-pos-bangunan/shared/storage
sudo chmod 600 /var/www/sistem-pos-bangunan/shared/.env
```

Pengguna PHP-FPM harus dapat menulis `shared/storage` dan `bootstrap/cache` pada release aktif, tetapi tidak memerlukan hak tulis ke source code lainnya.

## 14. Troubleshooting

### Pemeriksaan produksi gagal pada config/route cache

```bash
php artisan optimize:clear
php artisan optimize
php artisan sistem:periksa-produksi --ketat
```

### Permission storage gagal

```bash
sudo chown -R deploy:www-data /var/www/sistem-pos-bangunan/shared/storage
sudo chmod -R 2770 /var/www/sistem-pos-bangunan/shared/storage
```

### `mysqldump` atau `mysql` tidak ditemukan

Pasang paket client MySQL/MariaDB yang sesuai dan pastikan program tersedia pada `PATH` pengguna deployment serta `www-data`.

### Endpoint `/kesiapan` mengembalikan 503

Jalankan command rinci dari server:

```bash
php artisan sistem:periksa-produksi
```

Endpoint publik sengaja tidak menampilkan rincian kegagalan.

### Deployment tertahan oleh lock

Pastikan tidak ada proses deployment/rollback aktif. Hapus `.deploy.lock` hanya setelah memastikan proses lama benar-benar berhenti; keberadaan file lock sendiri tidak bermasalah karena penguncian dilakukan melalui `flock`.

## 15. Gate operasional

Deployment produksi hanya dilakukan setelah:

- branch fase telah lulus;
- PR telah digabung manual ke `main`;
- seluruh CI hijau;
- backup terbaru berhasil dan checksum valid;
- PIC deployment, waktu maintenance, dan rencana rollback ditetapkan;
- kredensial produksi tersimpan di server, bukan di Git.
