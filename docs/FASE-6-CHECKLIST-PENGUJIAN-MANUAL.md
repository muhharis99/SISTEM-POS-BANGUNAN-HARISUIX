# Fase 6 — Checklist Pengujian Manual

Status: **belum diterima pemilik**.

## Persiapan

- [ ] Jalankan migration SQL paten pada database kosong.
- [ ] Jalankan setup administrator Fase 2 serta setup Fase 3 sampai Fase 6.
- [ ] Pastikan tetap 71 base table dan 3 view.
- [ ] Pastikan 75 permission aktif dan 18 permission Fase 6 tanpa duplikasi.

## Penawaran dan pesanan

- [ ] Buat penawaran dengan beberapa barang/satuan.
- [ ] Uji draf, kirim, diterima pelanggan, ditolak, kedaluwarsa, dan dibatalkan.
- [ ] Ubah penawaran yang diterima menjadi pesanan satu kali saja.
- [ ] Uji pesanan tunai dan tempo.
- [ ] Pastikan batas kredit dan jatuh tempo pelanggan tervalidasi.
- [ ] Uji harga, diskon, pajak, biaya kirim, biaya lain, dan total bersih.

## Penjualan dan stok

- [ ] Buat penjualan tunai tanpa pesanan.
- [ ] Buat penjualan dari pesanan.
- [ ] Setujui penjualan dan pastikan mutasi `PENJUALAN` terbentuk.
- [ ] Pastikan saldo stok, harga pokok, total harga pokok, dan laba kotor benar.
- [ ] Pastikan penjualan melebihi stok ditolak atomik.
- [ ] Pastikan kuantitas mengikuti jumlah desimal satuan.

## Piutang dan pembayaran

- [ ] Setujui penjualan tempo dan pastikan piutang terbentuk satu kali.
- [ ] Uji pembayaran sebagian, potongan, dan pelunasan.
- [ ] Uji satu pembayaran untuk beberapa piutang pelanggan yang sama.
- [ ] Pastikan pelanggan/cabang berbeda ditolak.
- [ ] Pastikan pembayaran melebihi sisa piutang ditolak atomik.

## Pengiriman

- [ ] Jadwalkan pengiriman dengan armada dan pengemudi.
- [ ] Uji alur draf, dijadwalkan, dalam perjalanan, diterima, gagal, dan dibatalkan.
- [ ] Uji pengiriman sebagian dan penuh.
- [ ] Pastikan jumlah dikirim tidak melebihi pesanan/penjualan.
- [ ] Pastikan status pengiriman pada pesanan dan penjualan diperbarui.

## Retur penjualan

- [ ] Buat retur dari detail penjualan.
- [ ] Uji alur draf, disetujui, diterima, selesai, dan dibatalkan.
- [ ] Pastikan barang layak jual kembali menambah stok tersedia.
- [ ] Pastikan barang rusak/cacat masuk ke gudang RUSAK.
- [ ] Pastikan mutasi `RETUR_PENJUALAN` terbentuk.
- [ ] Pastikan opsi potong piutang memperbarui piutang dan penjualan.
- [ ] Pastikan retur melebihi jumlah penjualan ditolak atomik.

## Keamanan dan regresi

- [ ] Uji endpoint tanpa permission menghasilkan 403.
- [ ] Uji manipulasi ID dokumen cabang lain menghasilkan 404/403.
- [ ] Jalankan regression test Fase 1 sampai Fase 5.
- [ ] Pastikan aset UBold/Nunito tetap lokal dan tidak ada CDN eksternal.
- [ ] Pastikan tidak ada auto-merge.

## Gate akhir

Fase 6 hanya boleh di-merge setelah seluruh CI hijau, checklist ini diterima, dan pemilik menyatakan eksplisit:

```text
Fase 6 lulus
```
