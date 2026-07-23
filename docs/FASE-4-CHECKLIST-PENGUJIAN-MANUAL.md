# Fase 4 — Checklist Pengujian Manual

## Status

Checklist dijalankan pada database development atau hasil restore backup, bukan langsung pada database produksi.

Fase 4 masih **belum lulus** sampai pemilik menyatakan eksplisit `Fase 4 lulus`.

## A. Persiapan, backup, dan skema

- [ ] Checkout branch `fase-4-persediaan`.
- [ ] Pastikan checkpoint/tag Fase 3 tersedia di sisi pemilik.
- [ ] Jalankan `composer install` tanpa error.
- [ ] Konfigurasi `.env` memakai database development.
- [ ] Jalankan `php scripts/salin-aset-template.php`.
- [ ] Buat backup database sebelum migration atau setup Fase 4.
- [ ] Pastikan file backup dapat dibaca dan ukurannya masuk akal.
- [ ] Jalankan `php artisan migrate`.
- [ ] Jalankan `php artisan skema:verifikasi --rinci`.
- [ ] Pastikan tepat 71 base table: 70 tabel bisnis dan `migrations`.
- [ ] Pastikan tepat 3 view.
- [ ] Pastikan tidak ada tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, atau `password_reset_tokens`.
- [ ] Jalankan `php artisan fase4:siapkan` dua kali dan pastikan tidak ada duplicate-key error.
- [ ] Pastikan jumlah permission aktif menjadi 41.

## B. Hak akses dan isolasi cabang

- [ ] Administrator, Pemilik, dan Gudang dapat mengakses seluruh halaman persediaan.
- [ ] Kasir hanya dapat melihat saldo persediaan dan tidak dapat mengelola dokumen stok.
- [ ] Pembelian, Penjualan, dan Keuangan dapat melihat laporan sesuai matriks awal.
- [ ] Menu tanpa izin tidak tampil pada sidebar.
- [ ] Mengetik URL langsung tetap menghasilkan 403 ketika permission tidak dimiliki.
- [ ] Mengganti cabang mengubah saldo, dokumen, gudang, dan mutasi yang ditampilkan.
- [ ] ID dokumen cabang lain tidak dapat dibaca, diubah, disetujui, dikirim, diterima, atau dibatalkan.

## C. Nomor dokumen

- [ ] Stok awal menghasilkan nomor `SA/YYYYMM/NNNNN`.
- [ ] Transfer menghasilkan nomor `TS/YYYYMM/NNNNN`.
- [ ] Stok opname menghasilkan nomor `SO/YYYYMM/NNNNN`.
- [ ] Penyesuaian menghasilkan nomor `PS/YYYYMM/NNNNN`.
- [ ] Nomor bertambah berurutan per cabang, jenis, tahun, dan bulan.
- [ ] Dua request bersamaan tidak menghasilkan nomor duplikat.

## D. Konversi satuan dan desimal

- [ ] Jumlah pada satuan alternatif dikalikan `nilai_konversi` sebelum saldo diperbarui.
- [ ] Satuan dengan `jumlah_desimal = 0` menolak `1,5`.
- [ ] Satuan dengan `jumlah_desimal = 2` menerima `1,25` dan menolak `1,255`.
- [ ] Tidak ada aturan hardcode berdasarkan kode PCS, KG, METER, atau kode lainnya.
- [ ] Hasil konversi tersimpan maksimal tiga angka desimal sesuai kapasitas skema paten.
- [ ] Barang wajib lot menolak detail tanpa nomor lot.
- [ ] Barang wajib kedaluwarsa menolak detail tanpa tanggal kedaluwarsa.

## E. Stok awal

- [ ] Tambah stok awal DRAF berhasil.
- [ ] Detail membutuhkan gudang dan lokasi aktif pada cabang yang sama.
- [ ] Detail duplikat barang-satuan, lokasi, dan lot ditolak.
- [ ] Edit DRAF berhasil tanpa menyisakan detail aktif ganda.
- [ ] Stok awal yang DISETUJUI tidak dapat diedit atau disetujui ulang.
- [ ] Persetujuan membentuk `saldo_stok` dan satu mutasi `STOK_AWAL` per detail.
- [ ] Harga pokok rata-rata dan saldo setelah mutasi benar.
- [ ] Pembatalan hanya dapat dilakukan ketika DRAF.
- [ ] Seluruh proses rollback apabila satu detail gagal.

## F. Saldo, mutasi, dan kartu stok

- [ ] Saldo tampil per cabang, gudang, lokasi, barang, dan satuan dasar.
- [ ] Jumlah stok, dipesan, rusak, dan tersedia sesuai view paten.
- [ ] Filter gudang, pencarian barang, dan pagination berjalan.
- [ ] Kartu stok menampilkan tanggal, jenis, nomor dokumen, masuk, keluar, harga pokok, dan saldo setelah.
- [ ] Urutan kartu stok konsisten berdasarkan tanggal dan ID mutasi.
- [ ] Saldo tidak dapat diedit langsung dari UI.
- [ ] Semua perubahan saldo memiliki baris `mutasi_stok` yang sesuai.

## G. Transfer stok

- [ ] Transfer antar-gudang berhasil.
- [ ] Transfer antar-lokasi dalam gudang yang sama berhasil.
- [ ] Lokasi asal dan tujuan yang sama pada satu detail ditolak.
- [ ] DRAF dapat diedit dan dibatalkan.
- [ ] Alur DRAF → DISETUJUI → DIKIRIM → DITERIMA berjalan.
- [ ] Pengiriman mengurangi asal tetapi belum menambah tujuan.
- [ ] Penerimaan menambah tujuan dengan harga pokok dari asal.
- [ ] Mutasi `TRANSFER_KELUAR` dan `TRANSFER_MASUK` terbentuk terpisah.
- [ ] Jumlah melebihi stok tersedia ditolak dan seluruh transaksi rollback.
- [ ] Dokumen yang sudah DIKIRIM tidak dapat dibatalkan atau dikirim ulang.
- [ ] Dokumen yang sudah DITERIMA tidak dapat diterima ulang.
- [ ] Penguncian saldo mencegah dua pengiriman memakai stok yang sama.

## H. Stok rusak

- [ ] Stok yang masuk ke gudang `RUSAK` memperbarui `jumlah_rusak`.
- [ ] `jumlah_tersedia` untuk stok rusak menjadi nol.
- [ ] Gudang/lokasi khusus rusak tetap tidak dapat dinonaktifkan melalui Fase 3.
- [ ] Stok rusak tidak tercampur dengan stok lokasi normal pada laporan.

## I. Stok opname

- [ ] Tambah stok opname DRAF berhasil.
- [ ] Aksi Mulai mengubah status menjadi PROSES dan membekukan jumlah sistem serta harga pokok pada detail.
- [ ] Jumlah fisik dapat diperbarui ketika PROSES.
- [ ] Selisih dan nilai selisih dihitung benar.
- [ ] Aksi Selesai hanya tersedia dari status PROSES.
- [ ] Persetujuan hanya tersedia dari status SELESAI.
- [ ] Persetujuan membentuk penyesuaian otomatis berstatus DISETUJUI.
- [ ] Selisih positif menambah stok dan selisih negatif mengurangi stok.
- [ ] Mutasi hasil opname memakai jenis `STOK_OPNAME`.
- [ ] Opname tanpa selisih tidak membuat detail penyesuaian yang tidak perlu.
- [ ] Persetujuan ulang ditolak dan tidak menggandakan mutasi.
- [ ] Pembatalan tidak mengubah saldo.

## J. Penyesuaian stok

- [ ] Tambah penyesuaian DRAF membutuhkan alasan.
- [ ] Jenis TAMBAH dan KURANG tersimpan benar.
- [ ] Edit dan batal hanya tersedia ketika DRAF.
- [ ] Persetujuan TAMBAH membentuk mutasi `PENYESUAIAN_MASUK`.
- [ ] Persetujuan KURANG membentuk mutasi `PENYESUAIAN_KELUAR`.
- [ ] Pengurangan melebihi stok ditolak dan rollback atomik.
- [ ] Penyesuaian otomatis dari opname tidak dapat diedit manual.
- [ ] Total nilai dokumen sesuai detail.

## K. Audit dan keamanan transaksi

- [ ] Tambah, edit, setujui, kirim, terima, mulai, selesai, dan batal tercatat pada audit aktivitas.
- [ ] Audit berisi pengguna, cabang, modul, tabel, referensi, IP, dan peramban.
- [ ] Semua proses multi-tabel memakai transaksi database.
- [ ] Kegagalan pada detail tengah tidak meninggalkan saldo, mutasi, atau status parsial.
- [ ] Saldo dikunci ketika diperbarui.
- [ ] Nomor dokumen dikunci ketika dinaikkan.

## L. Tampilan dan asset

- [ ] Semua halaman menggunakan UBold lokal dan Nunito lokal.
- [ ] Network browser tidak memuat CDN eksternal.
- [ ] Sidebar Persediaan menampilkan menu sesuai permission.
- [ ] Modal tambah/edit dapat dipakai pada desktop.
- [ ] Modal, tabel, filter, dan sidebar dapat dipakai pada viewport mobile.
- [ ] Tabel lebar dapat di-scroll tanpa merusak layout.
- [ ] Penambahan/penghapusan detail dinamis tidak menimbulkan error JavaScript.
- [ ] Pesan validasi menunjukkan baris detail yang bermasalah.

## M. Regresi, backup, dan restore

- [ ] `vendor/bin/pint --test` berhasil.
- [ ] `php artisan test` berhasil.
- [ ] Integration test Fase 2 berhasil.
- [ ] Integration test Fase 3 berhasil.
- [ ] Integration test Fase 4 berhasil.
- [ ] `php artisan skema:verifikasi --rinci` berhasil setelah seluruh pengujian CRUD.
- [ ] Tidak ada tabel, kolom, index, foreign key, atau view paten yang berubah.
- [ ] Backup sebelum Fase 4 dapat di-restore pada database development lain.
- [ ] Setelah restore, login, multi-cabang, Fase 3, saldo, mutasi, dan audit tetap berfungsi.
- [ ] Draft PR #5 tetap open, draft, dan belum merged selama pengujian.
- [ ] Tidak ada auto-merge yang aktif.

## N. Keputusan kelulusan

Fase 4 hanya boleh dinyatakan lulus apabila:

- [ ] seluruh proses yang mengubah stok bersifat atomik;
- [ ] saldo dan mutasi selalu konsisten;
- [ ] konversi/desimal mengikuti data satuan;
- [ ] transfer, opname, penyesuaian, dan stok rusak lulus pengujian;
- [ ] isolasi cabang dan RBAC URL langsung lulus;
- [ ] backup dan restore lulus;
- [ ] seluruh CI dan regresi hijau;
- [ ] pemilik menyatakan eksplisit `Fase 4 lulus`.
