# Fase 9 — Status

## Checkpoint

- Fase 1 sampai Fase 8: lulus dan sudah digabung ke `main`.
- Fase 9: **dimulai — Dashboard Bisnis, Laporan Operasional, Ekspor, dan Cetak sedang dikerjakan**.
- Branch: `fase-9-dashboard-laporan-cetak`.
- Pull request: Draft PR Fase 9.
- Auto-merge: dilarang dan tidak digunakan.
- Fase 10 belum dimulai.

## Cakupan SQL paten

Fase 9 tidak menambahkan tabel baru. Implementasi hanya membaca tabel transaksi yang telah tersedia dan tiga view paten:

1. `tampilan_stok_tersedia`
2. `tampilan_hutang_pemasok`
3. `tampilan_piutang_pelanggan`

Tidak boleh menambah atau mengubah tabel, kolom, index, foreign key, migration bisnis, maupun view.

## Sasaran implementasi

- dashboard bisnis berbasis periode dan cabang aktif;
- KPI penjualan, pembelian, laba kotor, stok, hutang, piutang, dan kas;
- tren penjualan harian dan barang terlaris;
- laporan penjualan, pembelian, persediaan, hutang, piutang, dan kas;
- ekspor CSV streaming dan terotorisasi;
- cetak nota penjualan terotorisasi;
- audit aktivitas lihat, unduh, dan cetak;
- RBAC, isolasi cabang, Form Request, UI UBold, dan regression test Fase 1 sampai Fase 8.

## Gate

- Fase 9 tetap belum lulus selama implementasi, CI, dan checklist manual belum diterima pemilik.
- PR Fase 9 harus tetap draft dan tidak boleh digabung ke `main` sebelum pemilik menyatakan eksplisit `Fase 9 lulus`.
- Auto-merge dilarang.
- Fase 10 tidak boleh dimulai tanpa instruksi terpisah.
