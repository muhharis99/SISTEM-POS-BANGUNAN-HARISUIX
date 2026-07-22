# Fase 1 — Ringkasan Implementasi Fondasi

## Status

**Siap diuji manual, belum lulus.**

Fase 2 tidak boleh dimulai sebelum pemilik proyek menyatakan secara eksplisit `Fase 1 lulus`.

## Branch dan pull request

```text
Branch: fase-1-fondasi
Draft PR: #1
Target: main
```

Branch belum digabung ke `main` dan tag `fase-1-selesai` belum dibuat.

## Fondasi Laravel

Constraint utama pada `composer.json`:

- PHP `^8.4`;
- `laravel/framework:^13.8`;
- `laravel/tinker:^3.0`.

Dependency sudah dikunci melalui `composer.lock` hasil resolusi CI pada PHP 8.4. Versi framework yang terkunci saat Fase 1 adalah Laravel `v13.21.1`.

Konfigurasi aplikasi:

- MySQL sebagai koneksi utama;
- zona waktu `Asia/Jakarta`;
- locale `id` dan faker `id_ID`;
- entry point web dan Artisan;
- route kesehatan `/up`;
- route dashboard `/dashboard`;
- session berbasis file;
- cache berbasis file;
- queue sinkron.

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

Versi vendor yang berhasil diidentifikasi langsung dari asset telah dicatat dalam:

```text
docs/FASE-1-INVENTARIS-ASSET-UBOLD.md
```

Temuan import font relatif yang belum dipetakan telah dicatat tanpa mengubah asset:

```text
docs/FASE-1-CATATAN-ASSET-FONT-UBOLD.md
```

## Backup

Tersedia:

```text
scripts/backup-database.sh
scripts/backup-database.ps1
```

Backup disimpan lokal di `backups/database/` dan tidak masuk Git. Checksum SHA-256 dibuat untuk setiap backup.

## Automated test

File utama:

```text
tests/Feature/FondasiAplikasiTest.php
tests/Unit/StrukturDatabaseFinalTest.php
```

Cakupan:

- redirect halaman utama;
- render dashboard;
- identitas fondasi;
- penggunaan asset UBold lokal;
- pencegahan CDN umum;
- penguncian jumlah 70 tabel bisnis dalam SQL;
- penguncian tiga view yang disepakati;
- pencegahan tabel framework dan Spatie di dalam SQL final.

## Hasil GitHub Actions

Workflow `Fase 1 Smoke Test` berhasil pada PHP 8.4 dengan langkah:

- validasi `composer.json`;
- instalasi dependency dari `composer.lock`;
- penyalinan asset UBold;
- pemeriksaan sintaks PHP;
- pemeriksaan format Pint;
- unit test dan feature test;
- impor SQL final langsung ke layanan MySQL 8 disposable;
- verifikasi tabel, kolom, tipe, nullable, default, index, dan foreign key;
- penegasan jumlah tepat 70 tabel bisnis dan 3 view.

Status otomatis saat ringkasan ini diperbarui: **berhasil**.

## Dokumentasi Fase 1

```text
docs/FASE-1-INVENTARIS-ASSET-UBOLD.md
docs/FASE-1-CATATAN-ASSET-FONT-UBOLD.md
docs/FASE-1-SOP-BACKUP-DAN-VERSION-CONTROL.md
docs/FASE-1-VERIFIKASI-DAN-TESTING.md
docs/FASE-1-RINGKASAN-IMPLEMENTASI.md
```

## Hal yang belum dilakukan

- migration Laravel belum dijalankan pada MySQL development milik pengguna;
- pemeriksa skema belum dijalankan terhadap database development milik pengguna;
- tampilan belum diperiksa melalui browser pengguna;
- request font relatif `css2` dan variannya belum diverifikasi melalui Network browser;
- backup belum dijalankan dan diuji restore pada lingkungan pengguna;
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
