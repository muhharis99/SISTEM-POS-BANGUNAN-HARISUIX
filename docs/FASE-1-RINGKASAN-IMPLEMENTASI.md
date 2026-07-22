# Fase 1 — Ringkasan Implementasi Fondasi

## Status

**Implementasi otomatis selesai, pengujian manual belum selesai, Fase 1 belum lulus.**

Fase 2 tidak boleh mulai sebelum pemilik proyek menyatakan secara eksplisit:

```text
Fase 1 lulus
```

## Branch, pull request, dan tag

```text
Branch kerja : fase-1-fondasi
PR awal      : #1
Target       : main
Tag fase     : belum dibuat
```

PR #1 tercatat sudah ter-merge ke `main` pada 22 Juli 2026 sebelum pemilik menyatakan Fase 1 lulus. Merge tersebut tidak mengubah aturan kelulusan: Fase 1 tetap belum lulus dan tag `fase-1-selesai` tetap tidak boleh dibuat.

Branch `fase-1-fondasi` tetap digunakan untuk dokumentasi dan validasi lanjutan. Tidak dilakukan revert otomatis terhadap `main` karena tindakan tersebut harus menunggu keputusan pemilik proyek.

## Fondasi Laravel

Constraint utama:

- PHP `^8.4`;
- `laravel/framework:^13.8`;
- `laravel/tinker:^3.0`.

Dependency dikunci melalui `composer.lock`. Versi Laravel yang terkunci saat Fase 1 adalah `v13.21.1`.

Konfigurasi utama:

- MySQL sebagai koneksi utama;
- zona waktu `Asia/Jakarta`;
- locale `id` dan faker `id_ID`;
- route kesehatan `/up`;
- route dashboard `/dashboard`;
- session berbasis file;
- cache berbasis file;
- queue sinkron.

Driver file dan sinkron mencegah Laravel membuat tabel `sessions`, `cache`, `jobs`, `job_batches`, atau `failed_jobs` di luar SQL final.

## Keputusan tabel internal Laravel

Pemilik proyek telah menyetujui **Opsi A**:

```text
Izinkan hanya tabel internal Laravel migrations.
```

Ketentuannya:

- `migrations` bukan tabel bisnis;
- tidak menyimpan transaksi atau data toko;
- tidak berelasi dengan tabel bisnis;
- 70 tabel bisnis dan 3 view tetap paten;
- tabel framework lain tetap dilarang kecuali disetujui pada fase berikutnya;
- pemeriksa skema menghitung objek bisnis secara terpisah dari `migrations`.

Dengan keputusan ini, migration standar Laravel boleh dijalankan pada database development milik pengguna.

## Baseline database

Migration baseline:

```text
database/migrations/2026_07_22_000000_import_struktur_database_final.php
```

Migration membaca langsung:

```text
struktur_database_toko_bangunan.sql
```

Migration tidak menulis ulang:

- nama tabel dan kolom;
- tipe data dan nullable;
- default value;
- primary key dan index;
- foreign key;
- seed data;
- database view.

Perintah lingkungan seperti `CREATE DATABASE`, `USE`, `SET NAMES`, dan `SET time_zone` dipisahkan agar SQL dapat dijalankan pada koneksi Laravel yang aktif. Seluruh definisi objek bisnis tetap berasal dari SQL final.

## Verifikasi skema

Perintah:

```bash
php artisan skema:verifikasi --rinci
```

Pemeriksaan mencakup:

- tepat 70 tabel bisnis;
- tepat 3 view;
- nama dan urutan kolom;
- tipe data;
- nullable;
- default value;
- auto increment;
- primary key dan index;
- foreign key dan aturan hapus.

Verifikasi terhadap MySQL 8 disposable di GitHub Actions berhasil.

## Integrasi UBold

Struktur Blade:

```text
resources/views/
├── layouts/admin.blade.php
├── partials/
│   ├── head.blade.php
│   ├── topbar.blade.php
│   ├── sidebar.blade.php
│   ├── footer.blade.php
│   ├── scripts.blade.php
│   └── theme-customizer.blade.php
└── dashboard/index.blade.php
```

Asset sumber:

```text
template_admin/assets/
```

Asset runtime:

```text
public/assets/admin/
```

Asset disalin dengan:

```bash
php scripts/salin-aset-template.php
```

Tidak ditambahkan Tailwind, Livewire, Inertia, template lain, atau CDN frontend.

## Hasil investigasi font `css2`

Investigasi Chrome headless dan NetLog sudah selesai.

Hasil:

- import `css2`, `css2-1`, sampai `css2-8` adalah URL relatif;
- browser menyelesaikannya menjadi `/css2`, `/css2-1`, sampai `/css2-8` pada origin aplikasi;
- seluruh request memberikan HTTP `404`;
- file `css2*` tidak tersedia pada repository atau asset runtime;
- tidak ada request URL aktual ke `fonts.googleapis.com`;
- tidak ada request URL aktual ke `fonts.gstatic.com`;
- font utama tema dideklarasikan sebagai `Nunito`, lalu fallback `sans-serif`;
- font dan CSS belum diubah.

Rincian terdapat pada:

```text
docs/FASE-1-CATATAN-ASSET-FONT-UBOLD.md
```

Checklist Asset UBold belum dapat dinyatakan lulus sampai pemilik memilih tindakan untuk font tersebut.

## Backup

Script tersedia:

```text
scripts/backup-database.sh
scripts/backup-database.ps1
```

Backup disimpan di `backups/database/`, tidak masuk Git, dan memiliki checksum SHA-256.

## Automated test

Workflow berhasil menjalankan:

- validasi Composer;
- instalasi dependency terkunci;
- penyalinan asset UBold;
- pemeriksaan sintaks PHP;
- pemeriksaan format Pint;
- unit test dan feature test;
- impor SQL final ke MySQL 8;
- pemeriksaan skema;
- penegasan 70 tabel bisnis dan 3 view.

## Yang masih menunggu pengujian pengguna

- `composer install` pada Linux Mint pengguna;
- konfigurasi `.env` development;
- `php artisan migrate` dengan Opsi A;
- `php artisan skema:verifikasi --rinci` pada database development;
- `php artisan test` pada mesin pengguna;
- pemeriksaan dashboard melalui browser;
- pemeriksaan tampilan desktop dan mobile;
- keputusan perbaikan font `Nunito/css2`;
- backup dan uji restore;
- keputusan penanganan merge PR #1 yang terjadi sebelum kelulusan;
- pernyataan eksplisit `Fase 1 lulus`.

## Larangan kelanjutan

Sebelum pernyataan `Fase 1 lulus`:

- jangan membuat tag `fase-1-selesai`;
- jangan memulai implementasi Fase 2;
- jangan mengubah font tanpa persetujuan pemilik;
- jangan menganggap merge PR #1 sebagai bukti kelulusan.
