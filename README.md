# Sistem POS Toko Bangunan HARISUIX

Sistem informasi POS dan operasional toko bangunan berbasis Laravel untuk mengintegrasikan penjualan, pembelian, persediaan, pengiriman, hutang, piutang, kas, bank, akuntansi, laporan, lampiran, dan audit aktivitas dalam satu aplikasi multi-cabang.

## Status proyek

- **Fase 1 sampai Fase 9:** lulus dan sudah digabung ke `main`.
- **Fase 10:** kesiapan produksi, deployment, backup, restore, dan rollback sedang melalui gate akhir pada Draft PR terpisah.
- Auto-merge dilarang dan tidak digunakan.
- Deployment ke server produksi tidak dijalankan otomatis oleh repository.

## Teknologi

- Laravel 13
- PHP 8.4
- MySQL 8 atau MariaDB yang kompatibel
- Bootstrap 5
- Template admin UBold lokal
- Font Nunito lokal
- Laravel Query Builder dan Eloquent sesuai kebutuhan modul
- PHPUnit/Laravel Test
- GitHub Actions
- Nginx dan PHP-FPM untuk contoh produksi

Aplikasi tidak memakai Tailwind, Livewire, atau Inertia.

## Modul yang tersedia

### Fondasi dan keamanan

- autentikasi dan manajemen sesi;
- pengguna, pegawai, peran, dan hak akses;
- pembatasan data berdasarkan cabang aktif;
- pengaturan aplikasi dan penomoran dokumen atomik;
- soft delete dan audit field;
- validasi hak akses pada backend.

### Master data

- barang, kategori, merek, satuan, dan konversi satuan;
- barcode dan daftar harga;
- pelanggan dan jenis pelanggan;
- pemasok;
- gudang dan lokasi gudang;
- kas/bank, metode pembayaran, kategori biaya, pajak, dan armada.

### Persediaan

- saldo stok dan stok tersedia;
- kartu serta mutasi stok;
- stok awal;
- transfer stok;
- stok opname;
- penyesuaian stok;
- dukungan nomor lot dan tanggal kedaluwarsa.

### Pembelian dan hutang

- permintaan pembelian;
- pesanan pembelian;
- penerimaan barang;
- faktur pembelian tunai/tempo;
- hutang pemasok dan alokasi pembayaran;
- retur pembelian;
- integrasi stok dan jurnal.

### Penjualan dan piutang

- penawaran penjualan;
- pesanan pelanggan;
- transaksi penjualan tunai/tempo;
- pemeriksaan stok dan batas kredit;
- piutang pelanggan dan alokasi pembayaran;
- pengiriman;
- retur penjualan;
- harga pokok dan laba kotor;
- nota penjualan 80 mm.

### Kas, bank, dan akuntansi

- kas masuk, kas keluar, dan pemindahan kas/bank;
- daftar akun keuangan;
- pemetaan akun;
- jurnal umum seimbang;
- posting dan pembatalan jurnal.

### Lampiran dan audit

- lampiran transaksi disimpan privat;
- unduh melalui route terotorisasi;
- soft delete lampiran tanpa langsung menghapus berkas fisik;
- log aktivitas pengguna;
- data sebelum/sesudah dengan penyamaran nilai sensitif;
- ekspor audit CSV.

### Dashboard dan laporan

- KPI penjualan, pembelian, laba kotor, kas/bank, hutang, piutang, dan stok menipis;
- tren penjualan dan barang terlaris;
- laporan penjualan, pembelian, persediaan, hutang, piutang, serta kas/bank;
- filter periode dan pencarian;
- ekspor CSV streaming;
- isolasi cabang pada dashboard, laporan, ekspor, dan nota.

## Jaminan skema paten

Struktur bisnis berasal dari `struktur_database_toko_bangunan.sql` dan tidak boleh diubah tanpa keputusan pemilik.

Target skema:

- 71 base table, termasuk tabel `migrations` Laravel;
- 3 view:
  - `tampilan_stok_tersedia`;
  - `tampilan_hutang_pemasok`;
  - `tampilan_piutang_pelanggan`;
- 98 permission aktif setelah Fase 9;
- tidak menggunakan tabel infrastruktur Laravel berikut:
  - `sessions`;
  - `cache`;
  - `jobs`;
  - `job_batches`;
  - `failed_jobs`;
  - `password_reset_tokens`.

Karena itu konfigurasi bawaan menggunakan session dan cache berbasis file serta queue `sync`.

## Instalasi pengembangan

### Persyaratan

- PHP 8.4;
- Composer 2;
- MySQL 8/MariaDB;
- extension `mbstring`, `dom`, `fileinfo`, dan `pdo_mysql`.

### Langkah instalasi

```bash
git clone https://github.com/muhharis99/SISTEM-POS-BANGUNAN-HARISUIX.git
cd SISTEM-POS-BANGUNAN-HARISUIX
composer install
cp .env.example .env
php artisan key:generate
```

Atur koneksi database pada `.env`, lalu jalankan:

```bash
php artisan migrate
php scripts/salin-aset-template.php
```

Siapkan administrator awal:

```bash
php artisan fase2:siapkan \
  --nama-pengguna=administrator \
  --kata-sandi='GantiDenganKataSandiKuat' \
  --nama-tampilan='Administrator'
```

Siapkan permission dan data pendukung fase berikutnya:

```bash
php artisan fase3:siapkan
php artisan fase4:siapkan
php artisan fase5:siapkan
php artisan fase6:siapkan
php artisan fase7:siapkan
php artisan fase8:siapkan
php artisan fase9:siapkan
```

Jalankan aplikasi:

```bash
php artisan serve
```

## Pengujian

Pemeriksaan format dan test umum:

```bash
vendor/bin/pint --test
php artisan test
```

Verifikasi skema paten:

```bash
php artisan skema:verifikasi --rinci
```

Integration test setiap fase dijalankan melalui workflow MySQL khusus dengan environment flag masing-masing. Workflow fase terbaru juga menjalankan regresi fase sebelumnya dan full test suite.

## Kesiapan produksi — Fase 10

### Pemeriksaan produksi

```bash
php artisan sistem:periksa-produksi
php artisan sistem:periksa-produksi --ketat
php artisan sistem:periksa-produksi --json
```

Endpoint:

```text
/up
/kesiapan
```

`/kesiapan` hanya memberikan status umum dan tidak menampilkan nama database, kredensial, path server, atau exception internal.

### Backup database

```bash
php artisan sistem:backup-database \
  --direktori=/var/backups/sistem-pos-bangunan/database \
  --retensi-hari=14
```

Backup dibuat sebagai `.sql.gz` dengan checksum SHA-256. Kredensial MySQL disimpan pada berkas sementara berizin `0600`, bukan pada argumen proses.

### Restore database

```bash
php artisan down --retry=60
php artisan sistem:restore-database /lokasi/backup.sql.gz --konfirmasi=RESTORE
```

Restore membuat backup keselamatan secara default dan memverifikasi skema paten setelah proses selesai.

### Deployment dan rollback

```bash
APP_ROOT=/var/www/sistem-pos-bangunan \
SOURCE_DIR="$PWD" \
bash scripts/deploy-production.sh
```

```bash
APP_ROOT=/var/www/sistem-pos-bangunan \
bash scripts/rollback-production.sh <id-release>
```

Deployment memakai direktori release dan symlink `current` agar pergantian versi atomik. Rollback aplikasi tidak melakukan rollback database otomatis.

Dokumentasi lengkap:

- `docs/DEPLOYMENT-PRODUKSI.md`;
- `docs/BACKUP-RESTORE-DATABASE.md`;
- `docs/FASE-10-CHECKLIST-PENGUJIAN-MANUAL.md`.

Contoh konfigurasi server:

- `deploy/nginx/sistem-pos-bangunan.conf`;
- `deploy/php-fpm/sistem-pos-bangunan.conf`;
- `deploy/systemd/sistem-pos-backup.service`;
- `deploy/systemd/sistem-pos-backup.timer`.

## Struktur penting repository

```text
app/
├── Console/Commands/
├── Http/Controllers/
├── Http/Middleware/
├── Http/Requests/
├── Models/
└── Services/

deploy/
├── nginx/
├── php-fpm/
└── systemd/

docs/
routes/
scripts/
tests/Feature/
template_admin/
struktur_database_toko_bangunan.sql
blueprint_alur_sistem_erd_toko_bangunan_wiryo_pojok.pdf
```

## Prinsip implementasi

- seluruh transaksi stok dan keuangan menggunakan transaksi database;
- operasi kritis memakai locking untuk mencegah race condition;
- nomor dokumen dibuat atomik;
- setiap perubahan stok menghasilkan mutasi;
- transaksi cabang lain tidak boleh dapat diakses melalui manipulasi ID;
- data sensitif pada audit disamarkan;
- lampiran tidak disimpan pada direktori publik;
- kredensial produksi tidak boleh masuk repository;
- backup wajib diverifikasi dan disalin ke lokasi di luar server utama;
- auto-merge tidak digunakan.

## Pengembang

**Muhammad Haris Chaidir**  
Web Developer & UI/UX Designer — HARISUIX

Dibuat untuk mendukung digitalisasi dan integrasi operasional toko bangunan.
