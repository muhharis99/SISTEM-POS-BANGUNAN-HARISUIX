# Changelog

Semua perubahan penting Sistem POS Toko Bangunan HARISUIX dicatat pada berkas ini.

Format mengikuti prinsip Keep a Changelog dan penomoran versi Semantic Versioning.

## [Unreleased]

Belum ada perubahan setelah target rilis final `v1.0.0`.

## [1.0.0] - 2026-07-24

### Ditambahkan

- Paket rilis final dari commit Git aktif, manifest, inventaris berkas, release notes, dan checksum SHA-256.
- Command pembuat serta verifikator paket final.
- Gate go-live yang memeriksa konfigurasi produksi, versi, skema, permission, maintenance mode, disk, backup, dan paket.
- Smoke test pascadeploy untuk route, database, skema, storage, `/up`, dan `/kesiapan`.
- Skrip pascadeploy dan pemeriksaan hypercare dengan lock serta output JSON privat.
- Contoh systemd service/timer pemeriksaan hypercare setiap 15 menit.
- Runbook go-live, observability, respons insiden, hypercare, pemeliharaan, dan release notes final.

### Diperkuat

- Paket final menolak `.env`, backup database, log runtime, private key, vendor, node_modules, storage runtime, dan data transaksi.
- Checksum paket luar, checksum komponen, checksum berkas kritis, inventaris, dan isi arsip diverifikasi.
- Backup terbaru diverifikasi berdasarkan usia, checksum, dan keterbacaan gzip sebelum go-live.
- Tag dan GitHub Release final dipisahkan dari implementasi teknis dan hanya dibuat setelah Fase 12 lulus.

## [1.0.0-rc1] - 2026-07-24

### Ditambahkan

- Fondasi Laravel 13, PHP 8.4, MySQL, locale Indonesia, serta template admin UBold lokal.
- Autentikasi, pemilihan cabang, RBAC, pengguna, peran, dan audit akses.
- Master data barang, pelanggan, pemasok, gudang, harga, pajak, armada, kas, bank, dan referensi operasional.
- Persediaan, mutasi, stok awal, transfer, stok opname, dan penyesuaian.
- Pembelian, penerimaan, faktur, retur, hutang pemasok, dan pembayaran hutang.
- Penawaran, pesanan, POS, penjualan tunai/tempo, pembayaran, piutang, pengiriman, dan retur penjualan.
- Kas, bank, daftar akun, pemetaan akun, transaksi kas, jurnal umum, dan laporan keuangan sederhana.
- Lampiran privat dan audit aktivitas terstruktur.
- Dashboard bisnis, laporan operasional, ekspor CSV, dan nota penjualan 80 mm.
- Pemeriksaan kesiapan produksi, readiness endpoint, backup/restore, deployment atomik, rollback, dan contoh konfigurasi server.
- Pusat bantuan berbasis hak akses, panduan pengguna, matriks UAT, serta command manifest release candidate.

### Keamanan

- Password disimpan menggunakan hashing Laravel.
- Hak akses divalidasi pada backend dan dibatasi berdasarkan cabang aktif.
- Lampiran disimpan privat dan diakses melalui controller terotorisasi.
- Proxy tepercaya harus ditetapkan secara eksplisit.
- Endpoint readiness tidak membocorkan kredensial, path server, query, atau exception internal.
- Backup menggunakan kredensial sementara berizin `0600`, gzip, dan checksum SHA-256.

### Batasan kandidat rilis

- Kandidat rilis belum merupakan tag atau GitHub Release final.
- Deployment produksi nyata tidak dilakukan oleh repository.
- Konfigurasi Nginx, PHP-FPM, systemd, domain, sertifikat, dan kapasitas server harus disesuaikan pada staging/produksi.
- Skema tetap mengikuti SQL paten: 71 base table dan 3 view.
- Total permission aktif tetap 98.
