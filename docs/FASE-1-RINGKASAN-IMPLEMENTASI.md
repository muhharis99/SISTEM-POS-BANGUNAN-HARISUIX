# Fase 1 — Ringkasan Implementasi Fondasi

## Status

**Siap diuji, belum lulus.**

Fase 2 tidak boleh dimulai sebelum pemilik proyek menyatakan secara eksplisit `Fase 1 lulus`.

## Branch pengerjaan

```text
fase-1-fondasi
```

Branch belum digabung ke `main` dan tag `fase-1-selesai` belum dibuat.

## Fondasi Laravel

- Laravel 13 melalui `laravel/framework:^13.0`.
- PHP `^8.4`.
- MySQL sebagai koneksi utama.
- Zona waktu `Asia/Jakarta`.
- Locale `id` dan faker `id_ID`.
- Entry point web dan Artisan.
- Route kesehatan `/up`.
- Route dashboard `/dashboard`.
- Session berbasis file.
- Cache berbasis file.
- Queue sinkron.

Driver file/sync dipilih agar Laravel tidak membuat tabel `sessions`, `cache`, `jobs`, `job_batches`, atau `failed_jobs` di luar SQL final.

## Baseline database

Migration:

```text
database/migrations/2026_07_22_000000_import_struktur_database_final.php
```

Migration membaca langsung:

```text
struktur_database_toko_bangunan.sql
```

Hal yang tidak dilakukan migration:

- tidak menulis ulang nama tabel;
- tidak menulis ulang nama kolom;
- tidak mengubah tipe data;
- tidak mengubah nullable;
- tidak mengubah default;
- tidak mengubah index;
- tidak mengubah foreign key;
- tidak menambah tabel bisnis;
- tidak menambah view di luar SQL final.

Perintah yang dihapus dari SQL hanya perintah lingkungan yang tidak tepat dijalankan pada koneksi aktif Laravel:

- `SET NAMES`;
- `SET time_zone`;
- `CREATE DATABASE`;
- `USE database`.

Seluruh `CREATE TABLE`, seed `INSERT`, dan `CREATE VIEW` tetap dieksekusi dari SQL final.

## Verifikasi skema

Perintah:

```bash
php artisan skema:verifikasi --rinci
```

Pemeriksaan mencakup:

- daftar tabel bisnis;
- daftar view;
- urutan kolom;
- tipe data;
- nullable;
- default;
- auto increment;
- primary key;
- index;
- foreign key dan aturan hapus.

Tabel internal `migrations` dikecualikan dari hitungan tabel bisnis sambil menunggu keputusan pemilik proyek.

## Integrasi UBold

Struktur Blade:

```text
resources/views/
├── layouts/
│   └── admin.blade.php
├── partials/
│   ├── head.blade.php
│   ├── topbar.blade.php
│   ├── sidebar.blade.php
│   ├── footer.blade.php
│   ├── scripts.blade.php
│   └── theme-customizer.blade.php
└── dashboard/
    └── index.blade.php
```

Asset tetap berasal dari:

```text
template_admin/assets/
```

Asset runtime dibuat dengan:

```bash
php scripts/salin-aset-template.php
```

Output:

```text
public/assets/admin/
```

Tidak ada Tailwind, Livewire, Inertia, template pengganti, atau CDN frontend yang ditambahkan.

## Backup

Tersedia:

```text
scripts/backup-database.sh
scripts/backup-database.ps1
```

Backup disimpan lokal di `backups/database/` dan tidak masuk Git. Checksum SHA-256 dibuat untuk setiap backup.

## Automated test

File:

```text
tests/Feature/FondasiAplikasiTest.php
```

Cakupan:

- redirect halaman utama;
- render dashboard;
- identitas fondasi;
- penggunaan asset UBold lokal;
- pencegahan CDN umum.

## Dokumentasi Fase 1

```text
docs/FASE-1-INVENTARIS-ASSET-UBOLD.md
docs/FASE-1-SOP-BACKUP-DAN-VERSION-CONTROL.md
docs/FASE-1-VERIFIKASI-DAN-TESTING.md
docs/FASE-1-RINGKASAN-IMPLEMENTASI.md
```

## Hal yang belum dilakukan

- dependency Composer belum diuji pada mesin pengguna;
- migration belum dijalankan pada MySQL pengguna;
- pemeriksa skema belum dijalankan terhadap database nyata;
- tampilan belum diperiksa melalui browser pengguna;
- backup belum diuji restore;
- branch belum digabung;
- tag fase belum dibuat;
- Fase 1 belum dinyatakan lulus.

## Keputusan yang masih diperlukan

Pemilik proyek perlu menentukan apakah satu tabel internal Laravel bernama `migrations` diizinkan.

### Rekomendasi teknis

Izinkan satu tabel internal `migrations` dengan ketentuan:

- bukan tabel bisnis;
- tidak digunakan oleh modul POS;
- 70 tabel bisnis dan 3 view tetap paten;
- tidak ada tabel framework lain yang dibuat;
- pemeriksa skema tetap memastikan tidak ada tabel bisnis tambahan.

Keputusan ini diperlukan sebelum migration dijalankan.
