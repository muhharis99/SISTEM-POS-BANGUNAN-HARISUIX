# Fase 10 — Ringkasan Implementasi Kesiapan Produksi dan Deployment

## Status

**IMPLEMENTASI TEKNIS DITERAPKAN — SEDANG DIVERIFIKASI, BELUM LULUS.**

- Branch: `fase-10-kesiapan-produksi-deployment`
- Pull request: Draft PR #13
- Target: `main`
- Auto-merge: dilarang
- Deployment otomatis ke server: tidak dilakukan
- Fase 11: belum dimulai

## Batasan SQL paten

Fase 10 tidak menambahkan atau mengubah:

- tabel;
- kolom;
- index;
- foreign key;
- migration bisnis;
- view;
- permission bisnis.

Target tetap 71 base table, 3 view, dan 98 permission aktif.

## Pemeriksaan kesiapan produksi

Service `PemeriksaKesiapanProduksi` memeriksa:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- `APP_KEY` tersedia;
- `APP_URL` menggunakan HTTPS;
- session dan cache memakai file;
- queue memakai sync;
- koneksi MySQL;
- 71 base table dan 3 view paten;
- tidak adanya tabel infrastruktur Laravel yang dilarang;
- direktori storage dan bootstrap cache dapat ditulis;
- config serta route cache sebagai rekomendasi produksi.

Command:

```bash
php artisan sistem:periksa-produksi
php artisan sistem:periksa-produksi --ketat
php artisan sistem:periksa-produksi --json
```

Mode `--ketat` memperlakukan rekomendasi cache sebagai kegagalan sehingga cocok dipakai sebagai gate deployment.

## Endpoint kesehatan

- `/up`: liveness endpoint bawaan Laravel;
- `/kesiapan`: readiness endpoint Fase 10.

Endpoint `/kesiapan` hanya menampilkan status umum komponen dan waktu pemeriksaan. Nama database, username, password, path server, query, dan exception internal tidak ditampilkan.

## Backup database

Command:

```bash
php artisan sistem:backup-database
```

Fitur:

- `mysqldump` dengan transaksi konsisten;
- streaming dan gzip;
- checksum SHA-256;
- permission berkas `0600`;
- berkas kredensial sementara `0600`;
- kata sandi tidak diletakkan pada argumen proses;
- retensi otomatis;
- mode simulasi dan JSON.

## Restore database

Command:

```bash
php artisan sistem:restore-database /lokasi/backup.sql.gz --konfirmasi=RESTORE
```

Pengamanan:

- konfirmasi literal `RESTORE` wajib;
- aplikasi wajib maintenance mode kecuali override eksplisit;
- backup keselamatan dibuat sebelum restore secara default;
- checksum diperiksa bila sidecar tersedia;
- `.sql` dan `.sql.gz` diproses streaming;
- skema paten diverifikasi setelah restore;
- mode simulasi tersedia.

## Deployment release atomik

`scripts/deploy-production.sh` menggunakan struktur:

```text
APP_ROOT/
├── current -> releases/<release-id>
├── releases/
└── shared/
```

Tahap deployment:

1. memeriksa program, parameter, `.env`, dan working tree Git;
2. mengambil lock dengan `flock`;
3. membuat release dari commit saat ini menggunakan `git archive`;
4. menautkan `.env` dan storage bersama;
5. memasang dependency produksi dan aset lokal;
6. maintenance mode;
7. backup sebelum migration;
8. migration paten;
9. config/route/view cache;
10. pemeriksaan produksi ketat;
11. penggantian symlink atomik;
12. aplikasi aktif kembali;
13. retensi release lama.

Skrip memiliki mode `DEPLOY_DRY_RUN=1` dan tidak melakukan deployment otomatis.

## Rollback aplikasi

`scripts/rollback-production.sh`:

- menerima ID release target;
- mendukung dry-run;
- memakai lock yang sama dengan deployment;
- membuat backup database keselamatan secara default;
- memeriksa release target;
- memindahkan symlink atomik;
- mengembalikan release lama bila rollback gagal.

Rollback aplikasi tidak melakukan rollback database otomatis.

## Hardening produksi

- proxy tidak lagi dipercaya menggunakan wildcard `*`;
- `TRUSTED_PROXIES` harus berupa daftar eksplisit;
- `.env.production.example` menggunakan debug mati dan HTTPS;
- session cookie secure, HTTP-only, dan terenkripsi;
- Nginx melarang akses berkas tersembunyi serta konfigurasi sensitif;
- PHP-FPM memakai pool dan socket khusus;
- readiness response memakai no-cache dan noindex;
- route fase tidak didaftarkan ulang ketika route cache aktif.

## Infrastruktur contoh

- `deploy/nginx/sistem-pos-bangunan.conf`;
- `deploy/php-fpm/sistem-pos-bangunan.conf`;
- `deploy/systemd/sistem-pos-backup.service`;
- `deploy/systemd/sistem-pos-backup.timer`.

Seluruh contoh wajib disesuaikan dengan domain, kapasitas RAM, path PHP, sertifikat, user deployment, dan kebijakan server nyata.

## Dokumentasi operasional

- `docs/DEPLOYMENT-PRODUKSI.md`;
- `docs/BACKUP-RESTORE-DATABASE.md`;
- `docs/FASE-10-CHECKLIST-PENGUJIAN-MANUAL.md`;
- `docs/FASE-10-STATUS.md`.

## Pengujian otomatis

Workflow Fase 10 dirancang untuk:

- memeriksa sintaks Bash;
- dry-run deployment dan rollback;
- memeriksa sintaks PHP dan Laravel Pint;
- menjalankan migration SQL paten pada MySQL 8.4;
- memastikan 71 base table, 3 view, dan 98 permission;
- menjalankan regression Fase 9;
- menguji endpoint readiness;
- membuat backup `.sql.gz` nyata dan checksum;
- restore ke database kedua;
- memverifikasi skema database hasil restore;
- menjalankan pemeriksaan produksi ketat;
- menjalankan regression Fase 2 sampai Fase 5 dan full suite.

## Gate

Fase 10 tetap belum lulus sampai seluruh CI hijau, checklist manual diterima, dan pemilik menyatakan eksplisit `Fase 10 lulus`.
