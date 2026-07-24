# Sistem POS Toko Bangunan HARISUIX

Sistem informasi POS dan operasional toko bangunan berbasis Laravel untuk mengintegrasikan penjualan, pembelian, persediaan, pengiriman, hutang, piutang, kas, bank, akuntansi, laporan, lampiran, audit, deployment, dan dukungan operasional dalam satu aplikasi multi-cabang.

## Status proyek

- **Fase 1 sampai Fase 11:** lulus dan sudah digabung ke `main`.
- **Fase 12:** implementasi teknis Final Release, Go-Live, Observability, dan Hypercare selesai pada Draft PR terpisah; belum lulus menurut keputusan pemilik.
- Target versi final: `v1.0.0`.
- Skema tetap 71 base table dan 3 view paten.
- Total permission aktif tetap 98.
- Auto-merge dilarang dan tidak digunakan.
- Deployment staging/produksi tidak dijalankan otomatis oleh repository.
- Tag `v1.0.0` dan GitHub Release final belum dibuat.

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
- systemd untuk contoh backup dan pemeriksaan hypercare

Aplikasi tidak memakai Tailwind, Livewire, atau Inertia.

## Modul yang tersedia

### Fondasi dan keamanan

- autentikasi dan manajemen sesi;
- pengguna, pegawai, peran, serta hak akses;
- pemilihan dan pembatasan data berdasarkan cabang aktif;
- pengaturan aplikasi serta penomoran dokumen atomik;
- soft delete, audit field, dan log aktivitas;
- validasi hak akses pada backend.

### Master data

- barang, kategori, merek, satuan, dan konversi satuan;
- barcode serta daftar harga;
- pelanggan dan jenis pelanggan;
- pemasok;
- gudang dan lokasi gudang;
- kas/bank, metode pembayaran, kategori biaya, pajak, serta armada.

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
- integrasi stok dan jurnal operasional yang tersedia.

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
- posting dan pembatalan jurnal;
- neraca saldo, laba/rugi sederhana, dan posisi keuangan sederhana.

### Lampiran dan audit

- lampiran transaksi disimpan privat;
- unduh melalui route terotorisasi;
- soft delete lampiran tanpa langsung menghapus berkas fisik;
- log aktivitas pengguna;
- data sebelum/sesudah dengan penyamaran nilai sensitif;
- ekspor audit CSV.

### Dashboard dan laporan

- KPI penjualan, pembelian, laba kotor, kas/bank, hutang, piutang, serta stok menipis;
- tren penjualan dan barang terlaris;
- laporan penjualan, pembelian, persediaan, hutang, piutang, serta kas/bank;
- filter periode dan pencarian;
- ekspor CSV streaming UTF-8;
- isolasi cabang pada dashboard, laporan, ekspor, dan nota.

### Pusat bantuan dan serah-terima

- Pusat Bantuan berbasis hak akses;
- panduan pengguna per modul;
- pencarian panduan sisi klien;
- manifest release candidate;
- matriks UAT;
- panduan pelatihan, dukungan, eskalasi, dan serah-terima operasional.

### Final release dan operasi produksi

- kontrak rilis final `v1.0.0`;
- paket final dari commit Git aktif;
- manifest dan inventaris berkas;
- checksum SHA-256 per komponen, berkas kritis, dan setiap berkas sumber;
- verifikasi paket serta penolakan paket rusak;
- readiness dan pemeriksaan produksi;
- backup/restore database;
- deployment release atomik dan rollback aplikasi;
- smoke test pascadeploy;
- gate go-live berbasis paket dan backup;
- runbook observability, insiden, hypercare, dan pemeliharaan.

## Jaminan skema paten

Struktur bisnis berasal dari `struktur_database_toko_bangunan.sql` dan tidak boleh diubah tanpa keputusan pemilik.

Target skema:

- 71 base table, termasuk tabel `migrations` Laravel;
- 3 view:
  - `tampilan_stok_tersedia`;
  - `tampilan_hutang_pemasok`;
  - `tampilan_piutang_pelanggan`;
- 98 permission aktif;
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
- extension `mbstring`, `dom`, `fileinfo`, `pdo_mysql`, dan `zlib`.

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

Siapkan permission dan data pendukung:

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

```bash
vendor/bin/pint --test
php artisan test
php artisan skema:verifikasi --rinci
```

Integration test setiap fase dijalankan melalui workflow MySQL khusus dengan environment flag masing-masing. Workflow fase terbaru juga menjalankan regresi fase sebelumnya dan full test suite.

## Pusat Bantuan dan manifest release candidate

Pusat Bantuan tersedia di route:

```text
/panduan
```

Manifest kandidat rilis:

```bash
php artisan sistem:buat-manifest-rilis v1.0.0-rc1
php artisan sistem:verifikasi-manifest-rilis \
  storage/app/release-candidate/manifest-rilis.json
```

## Kesiapan produksi

### Pemeriksaan produksi

```bash
php artisan sistem:periksa-produksi
php artisan sistem:periksa-produksi --ketat
php artisan sistem:periksa-produksi --json
```

Endpoint kesehatan:

```text
/up
/kesiapan
```

`/kesiapan` hanya memberikan status umum dan tidak menampilkan nama database, kredensial, path server, query, atau exception internal.

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
php artisan sistem:restore-database \
  /lokasi/backup.sql.gz \
  --konfirmasi=RESTORE
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

## Paket rilis final `v1.0.0`

Buat paket:

```bash
php artisan sistem:buat-paket-rilis-final v1.0.0
```

Verifikasi paket:

```bash
php artisan sistem:verifikasi-paket-rilis-final \
  storage/app/release-final/sistem-pos-bangunan-1-0-0.tar.gz
```

Paket final tidak boleh memuat `.env`, kredensial, backup, log runtime, private key, vendor, node_modules, symlink, atau data transaksi.

## Smoke test dan gate go-live

```bash
php artisan sistem:smoke-test-pascadeploy
```

```bash
php artisan sistem:periksa-go-live \
  --ketat \
  --backup-direktori=/var/backups/sistem-pos-bangunan/database \
  --paket=/lokasi/sistem-pos-bangunan-1-0-0.tar.gz
```

Skrip gabungan pascadeploy:

```bash
RELEASE_PACKAGE=/lokasi/sistem-pos-bangunan-1-0-0.tar.gz \
BACKUP_DIRECTORY=/var/backups/sistem-pos-bangunan/database \
bash scripts/post-deploy-smoke.sh
```

## Hypercare

Pemeriksaan manual:

```bash
APP_ROOT=/var/www/sistem-pos-bangunan/current \
BACKUP_DIRECTORY=/var/www/sistem-pos-bangunan/shared/backups/database \
bash scripts/hypercare-check.sh
```

Contoh systemd:

- `deploy/systemd/sistem-pos-hypercare.service`;
- `deploy/systemd/sistem-pos-hypercare.timer`.

Konfigurasi contoh wajib disesuaikan dengan server nyata.

## Dokumentasi operasional

- `docs/DEPLOYMENT-PRODUKSI.md`;
- `docs/BACKUP-RESTORE-DATABASE.md`;
- `docs/PANDUAN-PENGGUNA.md`;
- `docs/UAT-RELEASE-CANDIDATE.md`;
- `docs/SERAH-TERIMA-OPERASIONAL.md`;
- `docs/RELEASE-NOTES-v1.0.0.md`;
- `docs/GO-LIVE-RUNBOOK.md`;
- `docs/OBSERVABILITY-DAN-RESPONS-INSIDEN.md`;
- `docs/HYPERCARE-DAN-PEMELIHARAAN.md`;
- `docs/FASE-12-CHECKLIST-PENGUJIAN-MANUAL.md`.

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
- transaksi cabang lain tidak boleh diakses melalui manipulasi ID;
- data sensitif pada audit disamarkan;
- lampiran tidak disimpan pada direktori publik;
- kredensial produksi tidak boleh masuk repository atau paket rilis;
- backup wajib diverifikasi dan disalin ke lokasi di luar server utama;
- database tidak di-rollback otomatis bersama aplikasi;
- auto-merge tidak digunakan.

## Pengembang

**Muhammad Haris Chaidir**  
Web Developer & UI/UX Designer — HARISUIX

Dibuat untuk mendukung digitalisasi dan integrasi operasional toko bangunan.