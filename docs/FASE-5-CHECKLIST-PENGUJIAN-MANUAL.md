# Fase 5 — Checklist Pengujian Manual

Status: **belum diterima pemilik**.

## Persiapan

- [ ] Jalankan migration SQL paten pada database kosong.
- [ ] Jalankan `php artisan fase2:siapkan` untuk administrator Fase 5.
- [ ] Jalankan `php artisan fase3:siapkan`.
- [ ] Jalankan `php artisan fase4:siapkan`.
- [ ] Jalankan `php artisan fase5:siapkan`.
- [ ] Pastikan 71 base table, 3 view, dan 57 permission aktif.

## Permintaan dan pesanan

- [ ] Buat permintaan dengan beberapa satuan barang.
- [ ] Uji alur draf → diajukan → disetujui.
- [ ] Uji penolakan dan pembatalan.
- [ ] Buat pesanan dari detail permintaan.
- [ ] Pastikan jumlah dipesan dan status permintaan diperbarui.
- [ ] Uji diskon, pajak, biaya kirim, biaya lain, dan total bersih.

## Penerimaan dan stok

- [ ] Terima barang sebagian dan penuh.
- [ ] Pastikan lokasi harus berada dalam gudang/cabang aktif.
- [ ] Pastikan nomor lot/kedaluwarsa wajib mengikuti master barang.
- [ ] Pastikan mutasi `PEMBELIAN`, saldo stok, harga beli terakhir, dan harga rata-rata terbentuk.
- [ ] Pastikan penerimaan melebihi pesanan ditolak atomik.

## Faktur dan hutang

- [ ] Setujui faktur tunai dan pastikan status langsung lunas.
- [ ] Setujui faktur tempo dan pastikan hutang terbentuk satu kali.
- [ ] Pastikan jumlah difakturkan tidak melebihi pesanan.
- [ ] Periksa laporan jatuh tempo dan sisa hutang.

## Pembayaran

- [ ] Buat satu pembayaran untuk beberapa faktur pemasok yang sama.
- [ ] Uji pembayaran sebagian, potongan, dan pelunasan.
- [ ] Pastikan pemasok/cabang berbeda ditolak.
- [ ] Pastikan pembayaran melebihi sisa hutang ditolak atomik.

## Retur

- [ ] Buat retur dengan kondisi barang yang berbeda.
- [ ] Uji alur draf → disetujui → dikirim → selesai.
- [ ] Pastikan mutasi `RETUR_PEMBELIAN` mengurangi stok.
- [ ] Pastikan retur melebihi stok ditolak atomik.
- [ ] Pastikan opsi potong hutang memperbarui hutang dan faktur.

## Keamanan dan regresi

- [ ] Uji semua endpoint tanpa permission menghasilkan 403.
- [ ] Uji manipulasi ID dokumen cabang lain menghasilkan 404/403.
- [ ] Uji Fase 1 sampai Fase 4 tetap lulus.
- [ ] Pastikan tidak ada CDN eksternal dan aset UBold/Nunito tetap lokal.
- [ ] Pastikan tidak ada auto-merge.

## Gate akhir

Fase 5 hanya boleh di-merge setelah semua checklist diterima, seluruh CI hijau, dan pemilik menyatakan eksplisit:

```text
Fase 5 lulus
```
