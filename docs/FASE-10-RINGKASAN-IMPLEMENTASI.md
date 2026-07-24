# Fase 10 — Ringkasan Implementasi Kesiapan Produksi dan Deployment

## Status

**IMPLEMENTASI TEKNIS SELESAI DAN CHECKPOINT OTOMATIS BERHASIL — BELUM LULUS MENURUT KEPUTUSAN PEMILIK.**

- Branch: `fase-10-kesiapan-produksi-deployment`
- Pull request: Draft PR #13
- Target: `main`
- Auto-merge: dilarang dan tidak digunakan
- Deployment otomatis ke server: tidak dilakukan
- Fase 11: belum dimulai

## Batasan SQL paten

Fase 10 tidak menambahkan atau mengubah tabel, kolom, index, foreign key, migration bisnis, view, maupun permission bisnis.

Target tetap:

- 71 base table;
- 3 view paten;
- 98 permission aktif;
- tanpa tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, dan `password_reset_tokens`.

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

Mode `--ketat` memperlakukan rekomendasi cache sebagai kegagalan sehingga dapat dipakai sebagai gate deployment.

## Endpoint kesehatan

- `/up`: liveness endpoint bawaan Laravel;
- `/kesiapan`: readiness endpoint Fase 10.

Endpoint `/kesiapan` hanya menampilkan status umum komponen dan waktu pemeriksaan. Nama database, username, password, path server, query, serta exception internal tidak ditampilkan. Response memakai no-cache, noindex, rate limit, dan HTTP 503 ketika komponen kritis tidak siap.

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

Workflow Fase 10 membuat backup `.sql.gz` nyata, memverifikasi gzip serta checksum, dan menyimpan backup baseline sebagai artifact berumur terbatas.

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

Workflow memulihkan backup nyata ke database kedua dan membuktikan hasil restore tetap memiliki 71 base table dan 3 view.

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
6. mengaktifkan maintenance mode;
7. membuat backup sebelum migration;
8. menjalankan migration paten;
9. membuat config, route, dan view cache;
10. menjalankan pemeriksaan produksi ketat;
11. mengganti symlink `current` secara atomik;
12. mengaktifkan aplikasi kembali;
13. membersihkan release lama berdasarkan retensi.

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

Rollback aplikasi tidak melakukan rollback database otomatis. Restore database merupakan prosedur terpisah yang memerlukan keputusan dan analisis dampak data.

## Hardening produksi

- proxy tidak lagi dipercaya menggunakan wildcard `*`;
- `TRUSTED_PROXIES` harus berupa daftar eksplisit;
- `.env.production.example` menggunakan debug mati dan HTTPS;
- session cookie secure, HTTP-only, dan terenkripsi;
- session/cache tetap file dan queue tetap sync untuk menjaga SQL paten;
- Nginx melarang akses berkas tersembunyi serta konfigurasi sensitif;
- PHP-FPM memakai pool dan socket khusus;
- readiness response memakai no-cache dan noindex;
- route fase tidak didaftarkan ulang ketika route cache aktif;
- repository tidak menyimpan rahasia dan tidak mengirim deployment otomatis.

## Infrastruktur contoh

- `deploy/nginx/sistem-pos-bangunan.conf`;
- `deploy/php-fpm/sistem-pos-bangunan.conf`;
- `deploy/systemd/sistem-pos-backup.service`;
- `deploy/systemd/sistem-pos-backup.timer`.

Seluruh contoh wajib disesuaikan dengan domain, kapasitas RAM, path PHP, sertifikat, pengguna deployment, serta kebijakan server nyata.

## Dokumentasi operasional

- `README.md`;
- `docs/DEPLOYMENT-PRODUKSI.md`;
- `docs/BACKUP-RESTORE-DATABASE.md`;
- `docs/FASE-10-CHECKLIST-PENGUJIAN-MANUAL.md`;
- `docs/FASE-10-STATUS.md`.

## Hasil pengujian otomatis

Checkpoint teknis telah berhasil menjalankan:

- sintaks Bash;
- dry-run deployment dan rollback;
- sintaks PHP dan Laravel Pint;
- migration SQL paten pada MySQL 8.4;
- verifikasi 71 base table, 3 view, dan 98 permission;
- regression Fase 9;
- integration test Fase 10;
- pengujian readiness tanpa kebocoran data sensitif;
- backup `.sql.gz` nyata dan checksum;
- restore ke database kedua;
- verifikasi skema database hasil restore;
- pemeriksaan produksi mode ketat;
- regression Fase 2 sampai Fase 5;
- full regression suite.

## Gate

Fase 10 tetap belum lulus. Draft PR #13 harus tetap draft dan tidak boleh di-merge sampai checklist manual diterima serta pemilik menyatakan eksplisit `Fase 10 lulus`.
