# Changelog

Semua perubahan penting Sistem POS Toko Bangunan HARISUIX dicatat pada berkas ini.

Format mengikuti prinsip Keep a Changelog dan penomoran versi Semantic Versioning.

## [Unreleased]

### Dalam pengembangan

- Fase 11: UAT, release candidate, pusat bantuan, panduan pengguna, dan serah-terima operasional.

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
- Pusat bantuan berbasis hak akses dan command manifest release candidate.

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
