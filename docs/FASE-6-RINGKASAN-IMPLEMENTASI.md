# Fase 6 — Ringkasan Implementasi Penjualan, Piutang, dan Pengiriman

## Status

**IMPLEMENTASI BERJALAN — BELUM LULUS.**

- Branch: `fase-6-penjualan-piutang`
- Pull request: Draft PR #7
- Target: `main`
- Auto-merge: dilarang
- Fase 7: belum dimulai

## Alur dokumen

1. Pengguna membuat penawaran untuk pelanggan atau pelanggan umum.
2. Penawaran dapat dikirim, diterima pelanggan, ditolak, kedaluwarsa, atau diubah menjadi pesanan.
3. Pesanan penjualan menyimpan sumber pesanan, daftar harga, alamat, cara pembayaran, dan rencana pengiriman.
4. Persetujuan pesanan memvalidasi batas kredit pelanggan dan ketersediaan stok.
5. Penjualan tunai langsung mencatat pembayaran, sedangkan penjualan tempo membentuk `piutang_pelanggan`.
6. Persetujuan penjualan mengurangi stok melalui mutasi `PENJUALAN` pada layanan persediaan Fase 4.
7. Pembayaran piutang dapat dialokasikan ke beberapa transaksi pelanggan yang sama.
8. Pengiriman dapat dilakukan sebagian atau penuh dan memperbarui status pesanan/penjualan.
9. Retur penjualan menambah stok melalui mutasi `RETUR_PENJUALAN`; barang rusak dipisahkan dari stok tersedia.
10. Retur dengan opsi `POTONG_PIUTANG` memperbarui saldo piutang secara atomik.

## Pengaman

- Semua transaksi dibatasi oleh cabang aktif.
- Status dokumen dan saldo stok/piutang dikunci dengan `lockForUpdate()`.
- Kuantitas mengikuti `satuan.jumlah_desimal` dan `barang_satuan.nilai_konversi`.
- Penjualan tidak boleh mengurangi stok melebihi stok tersedia.
- Pengiriman dan retur tidak boleh melebihi jumlah transaksi sumber.
- Alokasi pembayaran tidak boleh melebihi sisa piutang.
- Seluruh tindakan penting dicatat ke audit aktivitas.
- Tidak ada perubahan skema paten.

## Permission Fase 6

Permission akan dipisahkan untuk penawaran, pesanan, penjualan, piutang, pembayaran, pengiriman, retur, dan laporan agar setiap endpoint tetap terlindungi di sisi server.
