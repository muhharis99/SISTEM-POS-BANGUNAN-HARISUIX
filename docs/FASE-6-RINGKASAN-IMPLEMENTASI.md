# Fase 6 — Ringkasan Implementasi Penjualan, Piutang, Pengiriman, dan Retur

## Status

**IMPLEMENTASI TEKNIS DITERAPKAN — SEDANG DIUJI, BELUM LULUS.**

- Branch: `fase-6-implementasi-penjualan-piutang`
- Pull request: Draft PR #8
- Target: `main`
- Auto-merge: dilarang
- Fase 7: belum dimulai

## Alur dokumen

1. Pengguna membuat penawaran untuk pelanggan atau pelanggan umum.
2. Penawaran dapat dikirim, diterima pelanggan, ditolak, ditandai kedaluwarsa, dibatalkan, atau dikonversi satu kali menjadi pesanan.
3. Pesanan menyimpan sumber pesanan, daftar harga, alamat, cara pembayaran, dan rencana pengiriman.
4. Persetujuan pesanan tempo memvalidasi pelanggan dan batas kredit.
5. Penjualan dapat dibuat langsung atau mengacu pada pesanan.
6. Persetujuan penjualan mengurangi stok melalui mutasi `PENJUALAN` dan menyimpan harga pokok serta laba kotor.
7. Penjualan tempo membentuk satu `piutang_pelanggan`; penjualan tunai menyimpan nilai pembayaran dan uang kembali.
8. Pembayaran piutang dapat dialokasikan ke beberapa piutang milik pelanggan yang sama secara atomik.
9. Pengiriman mendukung alur draf, dijadwalkan, dalam perjalanan, diterima, gagal, dan dibatalkan.
10. Retur penjualan mengembalikan stok melalui mutasi `RETUR_PENJUALAN`; barang tidak layak jual dialihkan ke gudang RUSAK.
11. Retur dengan cara `POTONG_PIUTANG` mengurangi saldo piutang dan memperbarui status penjualan.

## Pengaman

- Seluruh transaksi dibatasi cabang aktif.
- Dokumen, saldo stok, dan saldo piutang dikunci dengan `lockForUpdate()`.
- Kuantitas mengikuti `satuan.jumlah_desimal` dan `barang_satuan.nilai_konversi`.
- Penjualan tidak boleh mengurangi stok melebihi stok tersedia.
- Pengiriman tidak boleh melampaui jumlah penjualan atau pesanan sumber.
- Retur tidak boleh melampaui jumlah barang yang dijual.
- Alokasi pembayaran dan potongan tidak boleh melampaui sisa piutang.
- Seluruh tindakan penting dicatat pada audit aktivitas.
- Tidak ada perubahan skema paten.

## Permission Fase 6

Fase 6 menambahkan 18 permission. Total target setelah Fase 2–Fase 6 adalah 75 permission aktif.
