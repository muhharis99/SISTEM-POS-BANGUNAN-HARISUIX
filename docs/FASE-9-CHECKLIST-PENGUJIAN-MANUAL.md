# Fase 9 — Checklist Pengujian Manual

Status: **DITERIMA PEMILIK pada 24 Juli 2026**.

> Catatan: checkpoint ini mencatat keputusan eksplisit pemilik bahwa Fase 9 lulus. Checklist ditandai selesai berdasarkan penerimaan pemilik dan hasil CI otomatis; catatan ini tidak menyatakan bahwa agen menjalankan sendiri seluruh pengujian melalui browser/perangkat cetak fisik.

## Persiapan

- [x] Jalankan migration SQL paten pada database kosong.
- [x] Jalankan setup administrator Fase 2 serta setup Fase 3 sampai Fase 9.
- [x] Pastikan tetap 71 base table dan 3 view.
- [x] Pastikan total 98 permission aktif dan hanya 3 permission baru Fase 9.
- [x] Pastikan tidak ada tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, atau `password_reset_tokens`.
- [x] Pastikan tidak ada migration bisnis atau perubahan pada SQL paten.

## Permission dan menu

- [x] Pastikan KPI bisnis hanya tampil bagi pengguna dengan `DASHBOARD_BISNIS_LIHAT`.
- [x] Pastikan menu Laporan Operasional hanya tampil jika pengguna memiliki sedikitnya satu permission laporan.
- [x] Pastikan jenis laporan yang tidak diizinkan tidak tampil pada pilihan.
- [x] Uji akses langsung ke jenis laporan tanpa permission dan pastikan 403.
- [x] Pastikan tombol ekspor hanya tampil bagi `LAPORAN_OPERASIONAL_UNDUH`.
- [x] Pastikan tombol cetak nota hanya tampil bagi `NOTA_PENJUALAN_CETAK`.

## Dashboard bisnis

- [x] Uji filter tanggal awal dan akhir.
- [x] Uji tanggal akhir sebelum tanggal awal dan pastikan ditolak.
- [x] Uji rentang lebih dari 366 hari dan pastikan ditolak.
- [x] Cocokkan total penjualan dengan transaksi aktif pada cabang aktif.
- [x] Pastikan transaksi multi-item hanya menghitung header satu kali.
- [x] Cocokkan total pembelian dan jumlah faktur.
- [x] Cocokkan laba kotor dengan jumlah `penjualan_detail.laba_kotor`.
- [x] Cocokkan saldo kas/bank dengan saldo awal ditambah kas masuk dikurangi kas keluar.
- [x] Cocokkan sisa hutang dan piutang dengan view paten.
- [x] Pastikan stok menipis menggunakan `jumlah_tersedia <= stok_minimum`.
- [x] Periksa tren penjualan harian dan sepuluh barang terlaris.
- [x] Ganti cabang aktif dan pastikan seluruh KPI ikut berubah.

## Laporan penjualan

- [x] Uji filter periode dan pencarian nomor penjualan/pelanggan.
- [x] Cocokkan total bersih, pembayaran, piutang, dan laba kotor.
- [x] Pastikan transaksi draf dan dibatalkan tidak muncul.
- [x] Pastikan transaksi cabang lain tidak muncul.
- [x] Pastikan transaksi dengan banyak detail hanya tampil satu baris.

## Laporan pembelian

- [x] Uji filter periode dan pencarian nomor faktur/pemasok.
- [x] Cocokkan total bersih, pembayaran, dan sisa hutang.
- [x] Pastikan faktur draf dan dibatalkan tidak muncul.
- [x] Pastikan faktur cabang lain tidak muncul.

## Laporan persediaan

- [x] Cocokkan stok, dipesan, rusak, dan tersedia dengan `tampilan_stok_tersedia`.
- [x] Uji pencarian kode barang, nama barang, gudang, dan lokasi.
- [x] Pastikan penanda MENIPIS dan AMAN sesuai stok minimum.
- [x] Pastikan lokasi cabang lain tidak muncul.

## Laporan hutang dan piutang

- [x] Cocokkan laporan hutang dengan `tampilan_hutang_pemasok`.
- [x] Cocokkan laporan piutang dengan `tampilan_piutang_pelanggan`.
- [x] Uji pencarian pihak dan nomor dokumen.
- [x] Periksa sisa, status, jatuh tempo, dan hari keterlambatan.
- [x] Pastikan data cabang lain tidak muncul.

## Laporan kas dan bank

- [x] Uji filter periode, nomor transaksi, keterangan, dan nama kas/bank.
- [x] Pastikan kas masuk, kas keluar, dan pindah kas tampil benar.
- [x] Pastikan sumber serta tujuan pemindahan kas tampil.
- [x] Pastikan transaksi cabang lain tidak muncul.

## Ekspor CSV

- [x] Ekspor setiap jenis laporan yang diizinkan.
- [x] Pastikan nama berkas memuat jenis dan periode.
- [x] Pastikan CSV mengikuti filter halaman.
- [x] Pastikan UTF-8 dan teks Indonesia terbaca benar.
- [x] Pastikan transaksi cabang lain tidak terdapat dalam CSV.
- [x] Uji data besar dan pastikan streaming tidak menghabiskan memori.
- [x] Pastikan aktivitas ekspor tercatat sebagai `LAPORAN / UNDUH`.

## Cetak nota penjualan

- [x] Cetak nota transaksi tunai.
- [x] Cetak nota transaksi tempo dengan sisa piutang.
- [x] Cocokkan barang, jumlah, satuan, harga, potongan, pajak, biaya, total, pembayaran, dan kembalian.
- [x] Pastikan ukuran cetak 80 mm dan tidak memakai CDN.
- [x] Uji transaksi draf/dibatalkan dan pastikan 404.
- [x] Uji ID transaksi cabang lain dan pastikan 404.
- [x] Pastikan aktivitas cetak tercatat sebagai `PENJUALAN / CETAK`.

## Keamanan dan regresi

- [x] Uji manipulasi `jenis_laporan`, tanggal, pencarian, dan ID penjualan.
- [x] Pastikan seluruh query dibatasi cabang aktif.
- [x] Jalankan regression test Fase 1 sampai Fase 8.
- [x] Pastikan aset UBold dan Nunito tetap lokal.
- [x] Pastikan tidak ada auto-merge.

## Gate akhir

- [x] Seluruh CI hijau.
- [x] Checklist diterima pemilik.
- [x] Pemilik menyatakan eksplisit `Fase 9 lulus` pada 24 Juli 2026.
- [x] Fase 9 boleh diproses menuju ready-for-review dan merge manual dengan expected head SHA terkunci.
