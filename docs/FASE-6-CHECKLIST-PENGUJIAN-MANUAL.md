# Fase 6 — Checklist Pengujian Manual

Status: **DITERIMA PEMILIK — FASE 6 LULUS pada 23 Juli 2026 berdasarkan keputusan eksplisit pemilik proyek.**

Checklist berikut dicatat diterima sebagai checkpoint manual pemilik. Pencatatan ini tidak mengubah skema paten dan tidak menyatakan bahwa pengujian browser dilakukan oleh agen.

## Persiapan

- [x] Jalankan migration SQL paten pada database kosong.
- [x] Jalankan setup administrator Fase 2 serta setup Fase 3 sampai Fase 6.
- [x] Pastikan tetap 71 base table dan 3 view.
- [x] Pastikan 75 permission aktif dan 18 permission Fase 6 tanpa duplikasi.

## Penawaran dan pesanan

- [x] Buat penawaran dengan beberapa barang/satuan.
- [x] Uji draf, kirim, diterima pelanggan, ditolak, kedaluwarsa, dan dibatalkan.
- [x] Ubah penawaran yang diterima menjadi pesanan satu kali saja.
- [x] Uji pesanan tunai dan tempo.
- [x] Pastikan batas kredit dan jatuh tempo pelanggan tervalidasi.
- [x] Uji harga, diskon, pajak, biaya kirim, biaya lain, dan total bersih.

## Penjualan dan stok

- [x] Buat penjualan tunai tanpa pesanan.
- [x] Buat penjualan dari pesanan.
- [x] Setujui penjualan dan pastikan mutasi `PENJUALAN` terbentuk.
- [x] Pastikan saldo stok, harga pokok, total harga pokok, dan laba kotor benar.
- [x] Pastikan penjualan melebihi stok ditolak atomik.
- [x] Pastikan kuantitas mengikuti jumlah desimal satuan.

## Piutang dan pembayaran

- [x] Setujui penjualan tempo dan pastikan piutang terbentuk satu kali.
- [x] Uji pembayaran sebagian, potongan, dan pelunasan.
- [x] Uji satu pembayaran untuk beberapa piutang pelanggan yang sama.
- [x] Pastikan pelanggan/cabang berbeda ditolak.
- [x] Pastikan pembayaran melebihi sisa piutang ditolak atomik.

## Pengiriman

- [x] Jadwalkan pengiriman dengan armada dan pengemudi.
- [x] Uji alur draf, dijadwalkan, dalam perjalanan, diterima, gagal, dan dibatalkan.
- [x] Uji pengiriman sebagian dan penuh.
- [x] Pastikan jumlah dikirim tidak melebihi pesanan/penjualan.
- [x] Pastikan status pengiriman pada pesanan dan penjualan diperbarui.

## Retur penjualan

- [x] Buat retur dari detail penjualan.
- [x] Uji alur draf, disetujui, diterima, selesai, dan dibatalkan.
- [x] Pastikan barang layak jual kembali menambah stok tersedia.
- [x] Pastikan barang rusak/cacat masuk ke gudang RUSAK.
- [x] Pastikan mutasi `RETUR_PENJUALAN` terbentuk.
- [x] Pastikan opsi potong piutang memperbarui piutang dan penjualan.
- [x] Pastikan retur melebihi jumlah penjualan ditolak atomik.

## Keamanan dan regresi

- [x] Uji endpoint tanpa permission menghasilkan 403.
- [x] Uji manipulasi ID dokumen cabang lain menghasilkan 404/403.
- [x] Jalankan regression test Fase 1 sampai Fase 5.
- [x] Pastikan aset UBold/Nunito tetap lokal dan tidak ada CDN eksternal.
- [x] Pastikan tidak ada auto-merge.

## Gate akhir

Pemilik proyek telah menyatakan secara eksplisit:

```text
Fase 6 lulus
```

Dengan keputusan tersebut, checklist manual diterima dan PR Fase 6 boleh diproses menuju ready-for-review serta merge setelah pemeriksaan CI pada commit checkpoint terbaru tetap hijau. Auto-merge tetap dilarang.