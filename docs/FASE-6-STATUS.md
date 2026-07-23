# Fase 6 — Status

## Checkpoint

- Fase 1: lulus.
- Fase 2: lulus dan merged ke `main`.
- Fase 3: lulus dan merged ke `main`.
- Fase 4: lulus dan PR #5 merged ke `main`.
- Fase 5: lulus dan PR #6 merged ke `main`.
- PR #7 hanya memuat dokumentasi awal Fase 6 dan sudah merged ke `main`.
- Fase 6: **implementasi teknis sedang dituntaskan** pada branch `fase-6-implementasi-penjualan-piutang` melalui Draft PR #8.
- Fase 7: belum dimulai.

## Implementasi yang dimasukkan

- permission dan matriks peran penjualan, piutang, pengiriman, serta retur;
- penawaran penjualan dan konversi satu kali menjadi pesanan;
- pesanan penjualan tunai/tempo dan persetujuan;
- penjualan tunai/tempo dengan mutasi stok `PENJUALAN`;
- pembentukan piutang pelanggan dan validasi batas kredit;
- pembayaran piutang dengan alokasi ke beberapa transaksi;
- pengiriman sebagian/penuh dengan armada dan pengemudi;
- retur penjualan dengan mutasi `RETUR_PENJUALAN`;
- pemisahan retur layak jual dan barang rusak;
- laporan operasional penjualan, laba kotor, piutang, pengiriman, dan retur;
- isolasi cabang, audit aktivitas, transaksi database, serta penguncian baris;
- antarmuka UBold/Nunito lokal;
- workflow CI Fase 6 dan regression Fase 1–Fase 5.

## Integritas skema paten

- Menggunakan tepat 13 tabel pada bagian 6 SQL paten.
- Tidak menambah migration bisnis, tabel, kolom, index, foreign key, atau view.
- Target tetap 71 base table: 70 tabel bisnis dan `migrations`.
- Target tetap 3 view.
- Fase 6 berhenti sebelum bagian 7: Kas, Bank, dan Akuntansi.

## Gate

- Draft PR #8 tidak boleh diubah menjadi ready atau di-merge sebelum seluruh CI hijau, checklist manual diterima, dan pemilik menyatakan eksplisit `Fase 6 lulus`.
- Auto-merge dilarang.
- Fase 7 tidak boleh dimulai tanpa instruksi terpisah.
