# Runbook Go-Live Sistem POS Bangunan

## Tujuan

Dokumen ini menjadi urutan kendali saat merilis `v1.0.0` ke produksi. Setiap langkah harus memiliki PIC, waktu mulai, hasil, bukti, serta keputusan lanjut atau rollback.

## Peran minimal

- **Pemilik keputusan:** menyetujui buka/tutup layanan dan rollback.
- **PIC aplikasi:** menjalankan deployment, smoke test, dan analisis error aplikasi.
- **PIC database:** memastikan backup, checksum, restore test, dan konsistensi data.
- **PIC operasional:** memvalidasi penjualan, stok, pembelian, kas, dan laporan.
- **PIC infrastruktur:** Nginx, PHP-FPM, TLS, disk, CPU, RAM, jaringan, dan systemd.

Satu orang dapat memegang beberapa peran hanya bila disetujui dan jalur eskalasi tetap jelas.

## H-7 sampai H-2

1. bekukan perubahan skema dan fitur di luar rilis;
2. selesaikan UAT pada staging menggunakan data nonproduksi;
3. pastikan tidak ada temuan KRITIS atau TINGGI terbuka;
4. verifikasi 71 base table, 3 view, dan 98 permission;
5. uji backup dan restore pada database terpisah;
6. uji deployment serta rollback release pada staging;
7. tinjau kapasitas disk, database, sertifikat TLS, dan jadwal backup;
8. tetapkan jendela go-live serta batas maksimum downtime;
9. komunikasikan prosedur manual sementara bila layanan harus dihentikan.

## H-1

1. buat paket rilis final:

```bash
php artisan sistem:buat-paket-rilis-final v1.0.0
```

2. verifikasi paket:

```bash
php artisan sistem:verifikasi-paket-rilis-final \
  storage/app/release-final/sistem-pos-bangunan-1-0-0.tar.gz
```

3. salin paket dan checksum ke lokasi distribusi privat;
4. buat backup database terbaru dan salinan off-server;
5. periksa checksum backup;
6. catat commit, checksum paket, checksum backup, dan release target;
7. hentikan perubahan konfigurasi produksi yang tidak berkaitan dengan go-live.

## Pelaksanaan go-live

### 1. Buka ruang kendali

- konfirmasi seluruh PIC hadir atau dapat dihubungi;
- catat waktu mulai;
- konfirmasi jalur komunikasi tunggal;
- konfirmasi backup terbaru tersedia.

### 2. Aktifkan maintenance mode

```bash
php artisan down --retry=60
```

Pastikan transaksi baru tidak masuk dan prosedur manual operasional telah aktif.

### 3. Backup sebelum deploy

```bash
php artisan sistem:backup-database \
  --direktori=/var/www/sistem-pos-bangunan/shared/backups/database
```

Verifikasi `.sql.gz`, `.sha256`, `gzip -t`, dan checksum.

### 4. Jalankan deployment

Gunakan `scripts/deploy-production.sh` sesuai `docs/DEPLOYMENT-PRODUKSI.md`. Deployment harus memakai release directory, shared storage, lock, cache produksi, dan pergantian symlink atomik.

### 5. Buka aplikasi

```bash
php artisan up
```

### 6. Jalankan smoke test dan gate go-live

```bash
RELEASE_PACKAGE=/lokasi/sistem-pos-bangunan-1-0-0.tar.gz \
BACKUP_DIRECTORY=/var/www/sistem-pos-bangunan/shared/backups/database \
bash scripts/post-deploy-smoke.sh
```

Seluruh pemeriksaan wajib berhasil.

### 7. Validasi bisnis cepat

- login dan pemilihan cabang;
- master barang dan pelanggan;
- saldo stok satu barang uji;
- transaksi penjualan uji yang disetujui;
- cetak nota 80 mm;
- cek mutasi stok;
- cek kas/jurnal terkait bila digunakan;
- buka dashboard dan laporan;
- unduh CSV;
- akses lampiran privat sesuai permission;
- pastikan isolasi cabang.

Data uji produksi harus ditandai dan dibatalkan/dikoreksi menggunakan prosedur bisnis yang sah, bukan penghapusan langsung.

## Kriteria lanjut

Go-live dapat diteruskan bila:

- smoke test dan gate go-live berhasil;
- tidak ada error 5xx berulang;
- transaksi inti dapat diselesaikan;
- stok, kas, hutang, dan piutang tidak menunjukkan anomali;
- backup terbaru valid;
- PIC operasional menyetujui pembukaan layanan.

## Kriteria rollback

Rollback aplikasi dipertimbangkan bila:

- login atau pemilihan cabang gagal luas;
- transaksi tidak dapat disimpan atau menghasilkan data tidak konsisten;
- stok atau saldo keuangan berubah tidak wajar;
- error 5xx berulang dan tidak dapat dipulihkan dalam batas waktu keputusan;
- readiness atau database gagal;
- risiko keamanan atau kebocoran data ditemukan.

Rollback aplikasi menggunakan `scripts/rollback-production.sh`. Database tidak di-rollback otomatis. Restore database hanya dilakukan setelah analisis transaksi sejak backup, persetujuan pemilik, dan rencana rekonsiliasi data.

## Setelah layanan dibuka

1. aktifkan timer hypercare;
2. pantau error log, readiness, kapasitas, backup, transaksi, dan antrean operasional;
3. lakukan rekonsiliasi penjualan, stok, kas, hutang, serta piutang pada akhir hari;
4. catat seluruh insiden dan tindakan;
5. lakukan evaluasi 2 jam, 1 hari, 3 hari, 7 hari, dan 14 hari setelah go-live.

## Penutupan go-live

Catat versi, commit, checksum paket, waktu deployment, waktu pembukaan layanan, hasil smoke test, hasil validasi bisnis, insiden, keputusan rollback bila ada, serta nama pihak yang menyetujui.