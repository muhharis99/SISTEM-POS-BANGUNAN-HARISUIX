# Fase 6 — Status

## Checkpoint

- Fase 1: lulus.
- Fase 2: lulus dan merged ke `main`.
- Fase 3: lulus dan merged ke `main`.
- Fase 4: lulus dan PR #5 merged ke `main`.
- Fase 5: lulus dan PR #6 merged ke `main`.
- PR #7 hanya memuat dokumentasi awal Fase 6 dan sudah merged ke `main`.
- Fase 6: **implementasi teknis selesai dan seluruh CI otomatis hijau; belum lulus menurut keputusan pemilik**.
- Branch: `fase-6-implementasi-penjualan-piutang`.
- Pull request: Draft PR #8.
- Fase 7: belum dimulai.

## Implementasi yang selesai

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

## Hasil pengujian otomatis

- Sintaks PHP dan Laravel Pint berhasil.
- Backup sebelum migration dan sebelum setup/testing berhasil dibuat.
- Migration SQL paten pada MySQL 8.4 berhasil.
- Verifikasi skema paten berhasil: tetap 71 base table dan 3 view.
- Tidak ada tabel infrastruktur Laravel yang dilarang.
- Total 75 permission aktif dan 18 permission Fase 6 terverifikasi.
- Integration test Fase 6 berhasil.
- Regression test Fase 1 sampai Fase 5 berhasil.
- Full regression suite berhasil.
- UBold/Nunito lokal dan visual test berhasil.
- Audit larangan auto-merge berhasil.
- Seluruh workflow hijau pada commit teknis `8de670389796f0573b229ec1eee43c3d53670f33`.

## Integritas skema paten

- Menggunakan tepat 13 tabel pada bagian 6 SQL paten.
- Tidak menambah migration bisnis, tabel, kolom, index, foreign key, atau view.
- Tetap 71 base table: 70 tabel bisnis dan `migrations`.
- Tetap 3 view.
- Fase 6 berhenti sebelum bagian 7: Kas, Bank, dan Akuntansi.

## Gate

- Draft PR #8 tetap draft dan belum di-merge.
- Checklist manual belum dinyatakan diterima oleh pemilik.
- Fase 6 hanya boleh dinyatakan lulus setelah pemilik menyatakan eksplisit `Fase 6 lulus`.
- Auto-merge dilarang dan tidak digunakan.
- Fase 7 tidak boleh dimulai tanpa instruksi terpisah.
