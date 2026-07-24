# Release Notes — Sistem POS Bangunan v1.0.0

## Status rilis

`v1.0.0` merupakan target rilis final pertama Sistem POS Bangunan HARISUIX. Berkas ini mendokumentasikan isi kandidat rilis final; tag dan GitHub Release belum dibuat sebelum Fase 12 dinyatakan lulus.

## Cakupan utama

- fondasi Laravel 13, PHP 8.4, MySQL, UBold lokal, dan locale Indonesia;
- autentikasi, cabang aktif, RBAC, audit, dan soft delete;
- master barang, pelanggan, pemasok, gudang, harga, kas/bank, pajak, dan armada;
- stok awal, mutasi, transfer, opname, penyesuaian, serta isolasi gudang/cabang;
- pembelian, penerimaan, retur, hutang, dan pembayaran pemasok;
- penawaran, pesanan, POS, penjualan tunai/kredit, pengiriman, retur, piutang, dan alokasi pembayaran;
- kas, bank, akun, pemetaan akun, jurnal, neraca saldo, laba/rugi sederhana, dan posisi keuangan sederhana;
- lampiran privat, unduhan terotorisasi, audit lanjutan, dan penyamaran data sensitif;
- dashboard bisnis, laporan operasional, CSV UTF-8, dan nota 80 mm;
- backup, restore, deployment release atomik, rollback aplikasi, readiness endpoint, dan hardening produksi;
- Pusat Bantuan, panduan pengguna, matriks UAT, manifest release candidate, dan serah-terima operasional;
- paket rilis final, checksum, smoke test pascadeploy, gate go-live, observability, dan hypercare.

## Integritas dan skema

- 71 base table;
- 3 view paten;
- 98 permission aktif;
- tanpa tabel infrastruktur Laravel yang dilarang;
- tanpa perubahan SQL paten pada Fase 12;
- paket final dibuat dari commit Git aktif dan diverifikasi dengan SHA-256.

## Perintah operasional baru

```bash
php artisan sistem:buat-paket-rilis-final v1.0.0
php artisan sistem:verifikasi-paket-rilis-final /lokasi/sistem-pos-bangunan-1-0-0.tar.gz
php artisan sistem:periksa-go-live --ketat --paket=/lokasi/paket.tar.gz
php artisan sistem:smoke-test-pascadeploy
```

## Catatan keamanan

Paket final tidak boleh memuat `.env`, kredensial, backup database, log runtime, private key, vendor, node_modules, atau data transaksi. Kredensial produksi tetap disediakan di server melalui mekanisme rahasia yang terpisah dari repository dan paket.

## Batasan rilis

- deployment ke server produksi tidak dilakukan otomatis oleh repository;
- tag dan GitHub Release hanya dibuat setelah Fase 12 lulus;
- konfigurasi Nginx, PHP-FPM, systemd, domain, sertifikat, kapasitas server, dan jalur backup harus disesuaikan pada lingkungan nyata;
- laporan keuangan yang tersedia bersifat operasional dan sederhana, bukan pengganti proses akuntansi formal atau audit eksternal;
- UAT manusia, pelatihan, dan persetujuan go-live tetap menjadi tanggung jawab pemilik dan tim operasional.

## Prosedur sebelum go-live

1. verifikasi paket final dan checksum;
2. buat backup database terbaru dan salinan off-server;
3. jalankan deployment pada staging;
4. jalankan full UAT serta perbaiki temuan kritis/tinggi;
5. jalankan pemeriksaan produksi mode ketat;
6. jalankan smoke test pascadeploy;
7. tetapkan PIC, jalur eskalasi, jadwal go-live, serta batas keputusan rollback;
8. aktifkan pemantauan hypercare setelah produksi dibuka.
