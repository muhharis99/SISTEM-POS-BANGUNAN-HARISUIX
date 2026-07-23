# Fase 6 — Status

## Checkpoint

- Fase 1: lulus.
- Fase 2: lulus dan merged ke `main`.
- Fase 3: lulus dan merged ke `main`.
- Fase 4: lulus dan PR #5 merged ke `main`.
- Fase 5: lulus dan PR #6 merged ke `main`.
- PR #7 hanya memasukkan dokumentasi awal Fase 6 ke `main`; belum memuat implementasi aplikasi.
- Fase 6: **sedang dikerjakan** pada branch `fase-6-implementasi-penjualan-piutang` melalui Draft PR lanjutan.
- Fase 7: belum dimulai.

## Ruang lingkup

Fase 6 memakai tepat 13 tabel pada bagian 6 SQL paten:

1. `penawaran_penjualan`
2. `penawaran_penjualan_detail`
3. `pesanan_penjualan`
4. `pesanan_penjualan_detail`
5. `penjualan`
6. `penjualan_detail`
7. `piutang_pelanggan`
8. `pembayaran_piutang`
9. `pembayaran_piutang_detail`
10. `pengiriman`
11. `pengiriman_detail`
12. `retur_penjualan`
13. `retur_penjualan_detail`

## Target implementasi

- penawaran penjualan dan perubahan menjadi pesanan;
- pesanan penjualan tunai/tempo dan persetujuan;
- penjualan tunai/tempo serta integrasi mutasi stok Fase 4;
- pembentukan piutang pelanggan;
- pembayaran serta alokasi piutang;
- pengiriman sebagian/penuh dengan armada dan pengemudi;
- retur penjualan, pengembalian stok, dan potong piutang;
- laporan penjualan, laba kotor, piutang, jatuh tempo, pembayaran, pengiriman, dan retur;
- RBAC, audit, isolasi cabang, transaksi database, dan penguncian baris;
- regression test Fase 1 sampai Fase 5.

## Integritas skema paten

- Tidak menambah atau mengubah migration bisnis, tabel, kolom, index, foreign key, maupun view.
- Target tetap 71 base table: 70 tabel bisnis dan `migrations`.
- Target tetap 3 view.
- Fase 6 berhenti sebelum bagian 7: Kas, Bank, dan Akuntansi.

## Gate

- Draft PR implementasi tidak boleh diubah menjadi ready atau di-merge sebelum seluruh CI hijau, checklist manual diterima, dan pemilik menyatakan eksplisit `Fase 6 lulus`.
- Auto-merge dilarang.
- Fase 7 tidak boleh dimulai tanpa instruksi terpisah.
