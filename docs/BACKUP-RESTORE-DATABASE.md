# Backup dan Restore Database Produksi

Fase 10 menyediakan command Artisan untuk membuat backup MySQL terkompresi dan melakukan restore secara terkendali. Kredensial database tidak diletakkan pada argumen proses; command membuat berkas konfigurasi client sementara berizin `0600` dan menghapusnya setelah proses selesai.

## 1. Backup manual

```bash
cd /var/www/sistem-pos-bangunan/current
php artisan sistem:backup-database
```

Lokasi default:

```text
storage/app/private/backups/database
```

Pada deployment release, gunakan direktori bersama di luar release:

```bash
php artisan sistem:backup-database \
  --direktori=/var/www/sistem-pos-bangunan/shared/backups/database \
  --retensi-hari=14
```

Hasil backup terdiri dari:

```text
backup-sistem_informasi_toko_bangunan-YYYYMMDD-HHMMSS.sql.gz
backup-sistem_informasi_toko_bangunan-YYYYMMDD-HHMMSS.sql.gz.sha256
```

Berkas backup dan checksum dibuat dengan izin `0600` bila sistem operasi mendukung perubahan permission.

## 2. Simulasi backup

Simulasi memeriksa konfigurasi, program `mysqldump`, direktori, nama database, dan rencana nama berkas tanpa membaca isi database:

```bash
php artisan sistem:backup-database \
  --direktori=/var/backups/sistem-pos-bangunan/database \
  --retensi-hari=14 \
  --simulasi
```

Format JSON untuk monitoring:

```bash
php artisan sistem:backup-database --simulasi --json
```

## 3. Karakteristik backup

Command menggunakan opsi:

- transaksi konsisten dengan `--single-transaction`;
- pembacaan streaming dengan `--quick`;
- routines, triggers, dan events;
- binary data dengan `--hex-blob`;
- UTF-8 (`utf8mb4`);
- kompresi gzip streaming;
- checksum SHA-256;
- penghapusan backup lama berdasarkan `--retensi-hari`.

Backup tidak mengunci tabel InnoDB secara penuh, tetapi transaksi besar yang berjalan bersamaan tetap dapat memengaruhi lama proses dan ukuran undo log.

## 4. Verifikasi backup

Periksa gzip:

```bash
gzip -t backup-*.sql.gz
```

Periksa checksum dari direktori backup:

```bash
sha256sum -c backup-sistem_informasi_toko_bangunan-YYYYMMDD-HHMMSS.sql.gz.sha256
```

Periksa ukuran:

```bash
ls -lh backup-*.sql.gz
```

Backup dianggap valid hanya bila:

- command selesai dengan exit code `0`;
- berkas `.sql.gz` tidak kosong;
- `gzip -t` berhasil;
- checksum SHA-256 cocok;
- restore uji berkala berhasil pada database terpisah.

## 5. Salinan di luar server

Backup lokal saja tidak cukup. Terapkan aturan minimal 3-2-1:

- 3 salinan data;
- 2 media/lokasi berbeda;
- 1 salinan berada di luar server utama.

Salinan eksternal harus menggunakan media terenkripsi dan akses terbatas. Jangan menyimpan backup database produksi pada repository Git atau folder web publik.

## 6. Persiapan restore

Restore adalah operasi destruktif. Sebelum restore:

1. pastikan target database benar;
2. identifikasi waktu backup dan dampak kehilangan transaksi setelah waktu tersebut;
3. minta persetujuan PIC/pemilik;
4. aktifkan maintenance mode;
5. pastikan kapasitas disk cukup;
6. pastikan checksum cocok;
7. buat backup keselamatan database saat ini;
8. siapkan rencana verifikasi setelah restore.

Aktifkan maintenance mode:

```bash
php artisan down --retry=60
```

## 7. Simulasi restore

```bash
php artisan sistem:restore-database \
  /var/backups/sistem-pos-bangunan/database/backup-contoh.sql.gz \
  --konfirmasi=RESTORE \
  --simulasi
```

Simulasi tidak mengubah database, tetapi memeriksa:

- berkas dapat dibaca;
- ekstensi `.sql` atau `.sql.gz` valid;
- checksum cocok jika sidecar `.sha256` tersedia;
- program `mysql` tersedia;
- konfigurasi database target tersedia.

## 8. Restore nyata

```bash
php artisan sistem:restore-database \
  /var/backups/sistem-pos-bangunan/database/backup-sistem_informasi_toko_bangunan-YYYYMMDD-HHMMSS.sql.gz \
  --konfirmasi=RESTORE
```

Secara default command akan:

1. menolak restore bila aplikasi tidak berada dalam maintenance mode;
2. membuat backup keselamatan database saat ini;
3. memverifikasi checksum backup sumber;
4. menyalurkan isi `.sql`/`.sql.gz` ke client `mysql` secara streaming;
5. menjalankan `skema:verifikasi --rinci` setelah restore.

Opsi berisiko berikut hanya digunakan dalam kondisi terkontrol:

```text
--tanpa-backup-keselamatan
--izinkan-aplikasi-aktif
```

Penggunaan kedua opsi tersebut pada produksi normal tidak direkomendasikan.

## 9. Restore ke database uji

Restore uji harus dilakukan berkala tanpa menyentuh database produksi:

```bash
mysql -u root -p -e "CREATE DATABASE sistem_informasi_toko_bangunan_uji_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

DB_DATABASE=sistem_informasi_toko_bangunan_uji_restore \
php artisan sistem:restore-database \
  /var/backups/sistem-pos-bangunan/database/backup-terbaru.sql.gz \
  --konfirmasi=RESTORE \
  --tanpa-backup-keselamatan \
  --izinkan-aplikasi-aktif
```

Verifikasi:

```bash
DB_DATABASE=sistem_informasi_toko_bangunan_uji_restore php artisan skema:verifikasi --rinci
```

Setelah validasi selesai, hapus database uji secara sadar:

```bash
mysql -u root -p -e "DROP DATABASE sistem_informasi_toko_bangunan_uji_restore;"
```

## 10. Setelah restore produksi

Jalankan:

```bash
php artisan optimize:clear
php artisan optimize
php artisan skema:verifikasi --rinci
php artisan sistem:periksa-produksi --ketat
php artisan up
```

Lakukan pemeriksaan bisnis:

- login administrator;
- cabang aktif;
- saldo stok dan mutasi terakhir;
- penjualan terakhir;
- hutang dan piutang;
- saldo kas/bank;
- jurnal terakhir;
- lampiran privat;
- dashboard dan laporan.

## 11. Kegagalan restore

Jika restore gagal:

- jangan langsung menjalankan restore kedua tanpa membaca pesan error;
- biarkan aplikasi dalam maintenance mode;
- simpan log command dan waktu kejadian;
- identifikasi backup keselamatan yang dibuat sebelum restore;
- periksa ruang disk, permission, versi client/server, dan checksum;
- pulihkan dari backup keselamatan hanya setelah target serta dampaknya dipastikan.

Command akan menampilkan lokasi backup keselamatan ketika proses restore gagal setelah backup tersebut berhasil dibuat.

## 12. Retensi yang disarankan

Contoh kebijakan awal:

- harian: 14 hari;
- mingguan: 8 minggu;
- bulanan: 12 bulan;
- backup sebelum deployment: minimal sampai deployment berikutnya dinyatakan stabil;
- backup sebelum restore: jangan dihapus sampai insiden ditutup.

Retensi akhir harus menyesuaikan kebijakan perusahaan, kapasitas media, kebutuhan audit, dan regulasi yang berlaku.
