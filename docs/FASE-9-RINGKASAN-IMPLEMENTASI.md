# Fase 9 — Ringkasan Implementasi Dashboard, Laporan, Ekspor, dan Cetak

## Status

**IMPLEMENTASI TEKNIS DITERAPKAN — SEDANG DIVERIFIKASI, BELUM LULUS.**

- Branch: `fase-9-dashboard-laporan-cetak`
- Pull request: Draft PR #12
- Target: `main`
- Auto-merge: dilarang
- Fase 10: belum dimulai

## Cakupan skema paten

Fase 9 tidak menambahkan tabel atau view. Implementasi membaca tabel transaksi yang telah tersedia dan tiga view paten:

1. `tampilan_stok_tersedia`
2. `tampilan_hutang_pemasok`
3. `tampilan_piutang_pelanggan`

Tidak ada tabel, kolom, index, foreign key, migration bisnis, atau view yang ditambahkan maupun diubah.

## Permission dan setup

Fase 9 hanya menambahkan 3 permission baru sehingga target total setelah Fase 2 sampai Fase 9 adalah 98 permission aktif:

- `DASHBOARD_BISNIS_LIHAT`
- `LAPORAN_OPERASIONAL_UNDUH`
- `NOTA_PENJUALAN_CETAK`

Izin melihat laporan menggunakan permission yang telah tersedia pada fase sebelumnya:

- penjualan: `LAPORAN_PENJUALAN_LIHAT`;
- pembelian: `LAPORAN_PEMBELIAN_LIHAT`;
- persediaan: `LAPORAN_STOK_LIHAT`;
- hutang: `HUTANG_PEMASOK_LIHAT`;
- piutang: `LAPORAN_PIUTANG_LIHAT`;
- kas dan bank: `LAPORAN_KAS_BANK_LIHAT`.

Command `php artisan fase9:siapkan` bersifat idempotent dan tidak mengubah skema.

## Dashboard bisnis

- filter tanggal awal dan tanggal akhir maksimal 366 hari;
- nilai penjualan dan jumlah transaksi;
- nilai pembelian dan jumlah faktur;
- laba kotor berdasarkan detail penjualan;
- saldo kas dan bank sampai tanggal akhir;
- sisa hutang dan sisa piutang aktif;
- jumlah barang dengan stok tersedia kurang dari atau sama dengan stok minimum;
- tren penjualan harian;
- sepuluh barang terlaris berdasarkan jumlah dasar;
- semua data dibatasi pada cabang aktif.

## Pusat laporan operasional

Jenis laporan yang tersedia:

1. penjualan;
2. pembelian;
3. persediaan;
4. hutang pemasok;
5. piutang pelanggan;
6. kas dan bank.

Setiap laporan menerapkan filter periode, pencarian, permission khusus, dan isolasi cabang. Tampilan web dibatasi 250 baris agar respons tetap ringan.

## Ekspor CSV

- ekspor menggunakan streaming dan tidak memuat seluruh data ke memori;
- berkas memakai UTF-8 BOM dan pemisah titik koma;
- filter ekspor sama dengan filter halaman laporan;
- permission `LAPORAN_OPERASIONAL_UNDUH` wajib dimiliki;
- aktivitas ekspor dicatat pada `log_aktivitas` sebagai `LAPORAN / UNDUH`.

## Cetak nota penjualan

- nota hanya tersedia untuk penjualan aktif pada cabang aktif;
- transaksi cabang lain menghasilkan 404;
- detail barang, jumlah, harga, potongan, pajak, biaya, total, pembayaran, kembalian, dan piutang ditampilkan;
- desain cetak mandiri berukuran 80 mm tanpa CDN;
- aktivitas cetak dicatat sebagai `PENJUALAN / CETAK`.

## Arsitektur

- query dashboard dan laporan dipusatkan pada `LayananLaporanOperasional`;
- filter menggunakan Form Request;
- route Fase 9 dipisahkan dalam `routes/fase9.php`;
- UI memakai layout dan komponen UBold yang sudah tersedia;
- tidak ada aset eksternal baru;
- total penjualan dihitung dari header agar transaksi multi-item tidak terhitung berulang.

## Gate

Fase 9 tetap belum lulus sampai seluruh CI hijau, checklist pengujian manual diterima, dan pemilik menyatakan eksplisit `Fase 9 lulus`.
