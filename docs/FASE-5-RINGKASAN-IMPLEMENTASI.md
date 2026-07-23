# Fase 5 — Ringkasan Implementasi Pembelian dan Hutang Pemasok

## Status

**LULUS MENURUT KEPUTUSAN EKSPLISIT PEMILIK.**

- Branch: `fase-5-pembelian-hutang`
- Pull request: PR #6
- Target: `main`
- Auto-merge: tidak digunakan
- Fase 6: belum dimulai

## Alur dokumen

1. Pengguna membuat permintaan pembelian sebagai draf.
2. Permintaan diajukan dan disetujui/ditolak oleh pengguna berizin.
3. Pesanan pembelian dapat mengacu ke detail permintaan dan menyimpan harga, diskon, serta pajak.
4. Penerimaan barang memvalidasi gudang/lokasi, satuan, lot, kedaluwarsa, dan jumlah terhadap pesanan.
5. Persetujuan penerimaan membentuk mutasi stok `PEMBELIAN` melalui `LayananPersediaan` Fase 4.
6. Faktur tunai menjadi lunas saat disetujui; faktur tempo membentuk satu `hutang_pemasok`.
7. Pembayaran hutang dapat dialokasikan ke beberapa faktur pemasok yang sama dan diproses atomik.
8. Retur pembelian mengurangi stok melalui mutasi `RETUR_PEMBELIAN`; opsi `POTONG_HUTANG` langsung memperbarui saldo hutang.

## Pengaman

- Seluruh transaksi dibatasi oleh cabang aktif.
- Status dokumen dikunci dengan `lockForUpdate()` sebelum diproses.
- Jumlah mengikuti `satuan.jumlah_desimal` dan konversi `barang_satuan.nilai_konversi`.
- Jumlah penerimaan dan faktur tidak boleh melampaui pesanan terkait.
- Alokasi pembayaran dan potongan tidak boleh melampaui sisa hutang.
- Retur tidak boleh mengurangi stok melebihi stok tersedia.
- Semua aksi penting dicatat ke audit aktivitas.
- Tidak ada perubahan skema paten.

## Permission Fase 5

Fase 5 menambahkan 16 permission. Total setelah Fase 2–5 adalah 57 permission aktif.

## Hasil akhir

- 71 base table tetap terjaga.
- 3 view tetap terjaga.
- Integration test Fase 5 berhasil.
- Regression test Fase 1–Fase 4 berhasil.
- UBold dan Nunito tetap menggunakan aset lokal.
- Pemilik menyatakan eksplisit `Fase 5 lulus` pada 23 Juli 2026.
- PR #6 hanya boleh di-merge setelah checkpoint CI terakhir tetap hijau dan head SHA dikunci.