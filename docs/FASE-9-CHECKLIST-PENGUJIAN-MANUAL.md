# Fase 9 — Checklist Pengujian Manual

Status: **belum diterima pemilik**.

## Persiapan

- [ ] Jalankan migration SQL paten pada database kosong.
- [ ] Jalankan setup administrator Fase 2 serta setup Fase 3 sampai Fase 9.
- [ ] Pastikan tetap 71 base table dan 3 view.
- [ ] Pastikan total 98 permission aktif dan hanya 3 permission baru Fase 9.
- [ ] Pastikan tidak ada tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, atau `password_reset_tokens`.
- [ ] Pastikan tidak ada migration bisnis atau perubahan pada SQL paten.

## Permission dan menu

- [ ] Pastikan KPI bisnis hanya tampil bagi pengguna dengan `DASHBOARD_BISNIS_LIHAT`.
- [ ] Pastikan menu Laporan Operasional hanya tampil jika pengguna memiliki sedikitnya satu permission laporan.
- [ ] Pastikan jenis laporan yang tidak diizinkan tidak tampil pada pilihan.
- [ ] Uji akses langsung ke jenis laporan tanpa permission dan pastikan 403.
- [ ] Pastikan tombol ekspor hanya tampil bagi `LAPORAN_OPERASIONAL_UNDUH`.
- [ ] Pastikan tombol cetak nota hanya tampil bagi `NOTA_PENJUALAN_CETAK`.

## Dashboard bisnis

- [ ] Uji filter tanggal awal dan akhir.
- [ ] Uji tanggal akhir sebelum tanggal awal dan pastikan ditolak.
- [ ] Uji rentang lebih dari 366 hari dan pastikan ditolak.
- [ ] Cocokkan total penjualan dengan transaksi aktif pada cabang aktif.
- [ ] Pastikan transaksi multi-item hanya menghitung header satu kali.
- [ ] Cocokkan total pembelian dan jumlah faktur.
- [ ] Cocokkan laba kotor dengan jumlah `penjualan_detail.laba_kotor`.
- [ ] Cocokkan saldo kas/bank dengan saldo awal ditambah kas masuk dikurangi kas keluar.
- [ ] Cocokkan sisa hutang dan piutang dengan view paten.
- [ ] Pastikan stok menipis menggunakan `jumlah_tersedia <= stok_minimum`.
- [ ] Periksa tren penjualan harian dan sepuluh barang terlaris.
- [ ] Ganti cabang aktif dan pastikan seluruh KPI ikut berubah.

## Laporan penjualan

- [ ] Uji filter periode dan pencarian nomor penjualan/pelanggan.
- [ ] Cocokkan total bersih, pembayaran, piutang, dan laba kotor.
- [ ] Pastikan transaksi draf dan dibatalkan tidak muncul.
- [ ] Pastikan transaksi cabang lain tidak muncul.
- [ ] Pastikan transaksi dengan banyak detail hanya tampil satu baris.

## Laporan pembelian

- [ ] Uji filter periode dan pencarian nomor faktur/pemasok.
- [ ] Cocokkan total bersih, pembayaran, dan sisa hutang.
- [ ] Pastikan faktur draf dan dibatalkan tidak muncul.
- [ ] Pastikan faktur cabang lain tidak muncul.

## Laporan persediaan

- [ ] Cocokkan stok, dipesan, rusak, dan tersedia dengan `tampilan_stok_tersedia`.
- [ ] Uji pencarian kode barang, nama barang, gudang, dan lokasi.
- [ ] Pastikan penanda MENIPIS dan AMAN sesuai stok minimum.
- [ ] Pastikan lokasi cabang lain tidak muncul.

## Laporan hutang dan piutang

- [ ] Cocokkan laporan hutang dengan `tampilan_hutang_pemasok`.
- [ ] Cocokkan laporan piutang dengan `tampilan_piutang_pelanggan`.
- [ ] Uji pencarian pihak dan nomor dokumen.
- [ ] Periksa sisa, status, jatuh tempo, dan hari keterlambatan.
- [ ] Pastikan data cabang lain tidak muncul.

## Laporan kas dan bank

- [ ] Uji filter periode, nomor transaksi, keterangan, dan nama kas/bank.
- [ ] Pastikan kas masuk, kas keluar, dan pindah kas tampil benar.
- [ ] Pastikan sumber serta tujuan pemindahan kas tampil.
- [ ] Pastikan transaksi cabang lain tidak muncul.

## Ekspor CSV

- [ ] Ekspor setiap jenis laporan yang diizinkan.
- [ ] Pastikan nama berkas memuat jenis dan periode.
- [ ] Pastikan CSV mengikuti filter halaman.
- [ ] Pastikan UTF-8 dan teks Indonesia terbaca benar.
- [ ] Pastikan transaksi cabang lain tidak terdapat dalam CSV.
- [ ] Uji data besar dan pastikan streaming tidak menghabiskan memori.
- [ ] Pastikan aktivitas ekspor tercatat sebagai `LAPORAN / UNDUH`.

## Cetak nota penjualan

- [ ] Cetak nota transaksi tunai.
- [ ] Cetak nota transaksi tempo dengan sisa piutang.
- [ ] Cocokkan barang, jumlah, satuan, harga, potongan, pajak, biaya, total, pembayaran, dan kembalian.
- [ ] Pastikan ukuran cetak 80 mm dan tidak memakai CDN.
- [ ] Uji transaksi draf/dibatalkan dan pastikan 404.
- [ ] Uji ID transaksi cabang lain dan pastikan 404.
- [ ] Pastikan aktivitas cetak tercatat sebagai `PENJUALAN / CETAK`.

## Keamanan dan regresi

- [ ] Uji manipulasi `jenis_laporan`, tanggal, pencarian, dan ID penjualan.
- [ ] Pastikan seluruh query dibatasi cabang aktif.
- [ ] Jalankan regression test Fase 1 sampai Fase 8.
- [ ] Pastikan aset UBold dan Nunito tetap lokal.
- [ ] Pastikan tidak ada auto-merge.

## Gate akhir

Fase 9 hanya boleh di-merge setelah seluruh CI hijau, checklist ini diterima, dan pemilik menyatakan eksplisit:

```text
Fase 9 lulus
```
