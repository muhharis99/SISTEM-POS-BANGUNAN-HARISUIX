# Fase 5 — Checklist Pengujian Manual

Status: **diterima pemilik melalui keputusan eksplisit `Fase 5 lulus` pada 23 Juli 2026**.

## Persiapan

- [x] Jalankan migration SQL paten pada database kosong.
- [x] Jalankan `php artisan fase2:siapkan` untuk administrator Fase 5.
- [x] Jalankan `php artisan fase3:siapkan`.
- [x] Jalankan `php artisan fase4:siapkan`.
- [x] Jalankan `php artisan fase5:siapkan`.
- [x] Pastikan 71 base table, 3 view, dan 57 permission aktif.

## Permintaan dan pesanan

- [x] Buat permintaan dengan beberapa satuan barang.
- [x] Uji alur draf → diajukan → disetujui.
- [x] Uji penolakan dan pembatalan.
- [x] Buat pesanan dari detail permintaan.
- [x] Pastikan jumlah dipesan dan status permintaan diperbarui.
- [x] Uji diskon, pajak, biaya kirim, biaya lain, dan total bersih.

## Penerimaan dan stok

- [x] Terima barang sebagian dan penuh.
- [x] Pastikan lokasi harus berada dalam gudang/cabang aktif.
- [x] Pastikan nomor lot/kedaluwarsa wajib mengikuti master barang.
- [x] Pastikan mutasi `PEMBELIAN`, saldo stok, harga beli terakhir, dan harga rata-rata terbentuk.
- [x] Pastikan penerimaan melebihi pesanan ditolak atomik.

## Faktur dan hutang

- [x] Setujui faktur tunai dan pastikan status langsung lunas.
- [x] Setujui faktur tempo dan pastikan hutang terbentuk satu kali.
- [x] Pastikan jumlah difakturkan tidak melebihi pesanan.
- [x] Periksa laporan jatuh tempo dan sisa hutang.

## Pembayaran

- [x] Buat satu pembayaran untuk beberapa faktur pemasok yang sama.
- [x] Uji pembayaran sebagian, potongan, dan pelunasan.
- [x] Pastikan pemasok/cabang berbeda ditolak.
- [x] Pastikan pembayaran melebihi sisa hutang ditolak atomik.

## Retur

- [x] Buat retur dengan kondisi barang yang berbeda.
- [x] Uji alur draf → disetujui → dikirim → selesai.
- [x] Pastikan mutasi `RETUR_PEMBELIAN` mengurangi stok.
- [x] Pastikan retur melebihi stok ditolak atomik.
- [x] Pastikan opsi potong hutang memperbarui hutang dan faktur.

## Keamanan dan regresi

- [x] Uji semua endpoint tanpa permission menghasilkan 403.
- [x] Uji manipulasi ID dokumen cabang lain menghasilkan 404/403.
- [x] Uji Fase 1 sampai Fase 4 tetap lulus.
- [x] Pastikan tidak ada CDN eksternal dan aset UBold/Nunito tetap lokal.
- [x] Pastikan tidak ada auto-merge.

## Gate akhir

Seluruh CI otomatis telah hijau dan pemilik menyatakan eksplisit:

```text
Fase 5 lulus
```

PR #6 boleh diproses menjadi ready dan di-merge secara eksplisit setelah checkpoint CI terakhir pada commit dokumentasi kelulusan tetap hijau. Merge harus menggunakan SHA head terkunci dan tidak boleh memakai auto-merge.