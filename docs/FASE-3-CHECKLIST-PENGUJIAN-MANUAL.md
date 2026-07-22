# Fase 3 — Checklist Pengujian Manual

## Status

Checklist ini harus diselesaikan pada database development atau hasil restore backup, bukan langsung pada database produksi.

Fase 3 masih **belum lulus** sampai pemilik menyatakan eksplisit `Fase 3 lulus`.

## A. Persiapan dan backup

- [ ] Checkout branch `fase-3-master-data`.
- [ ] Pastikan tag/checkpoint Fase 2 tersedia di sisi pemilik.
- [ ] Jalankan `composer install` tanpa error.
- [ ] Konfigurasi `.env` memakai database development.
- [ ] Jalankan `php artisan key:generate` jika belum ada key.
- [ ] Jalankan `php scripts/salin-aset-template.php`.
- [ ] Buat backup database sebelum migration atau setup Fase 3.
- [ ] Pastikan file backup dapat dibaca dan ukurannya masuk akal.
- [ ] Jalankan `php artisan migrate`.
- [ ] Jalankan `php artisan skema:verifikasi --rinci`.
- [ ] Pastikan jumlah base table 71: 70 tabel bisnis dan `migrations`.
- [ ] Pastikan jumlah view 3.
- [ ] Pastikan tidak ada tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, atau `password_reset_tokens`.
- [ ] Jalankan `php artisan fase3:siapkan`.
- [ ] Jalankan command tersebut untuk kedua kalinya dan pastikan tidak muncul duplicate-key error.

## B. Data awal

- [ ] Jenis pelanggan tersedia: Umum, Tukang, Kontraktor/Proyek, dan Toko/Reseller.
- [ ] Pelanggan `UMUM / PELANGGAN TUNAI` tersedia dan aktif.
- [ ] Pelanggan tunai default memiliki kredit, jatuh tempo, dan potongan nol.
- [ ] Identitas dan status pelanggan tunai default tidak dapat dirusak melalui form maupun request langsung.
- [ ] Metode pembayaran Tunai, Transfer Bank, dan Tempo tersedia.
- [ ] Tarif `NON_PAJAK` tersedia dengan nilai tepat 0%.
- [ ] Tarif `NON_PAJAK` tidak dapat diubah menjadi tarif nonnol atau dinonaktifkan.
- [ ] Setiap cabang aktif memiliki gudang RUSAK dan RETUR.
- [ ] Setiap gudang khusus memiliki lokasi AREA-RUSAK atau AREA-RETUR.
- [ ] Menjalankan setup ulang tidak menggandakan data awal.

## C. Kategori barang

- [ ] Tambah kategori utama berhasil.
- [ ] Tambah subkategori berhasil.
- [ ] Kode kategori duplikat ditolak.
- [ ] Kategori tidak dapat memilih dirinya sendiri sebagai induk.
- [ ] Kategori tidak dapat memilih salah satu turunannya sebagai induk.
- [ ] Edit nama, urutan, dan keterangan berhasil.
- [ ] Aktivasi dan nonaktivasi berhasil.
- [ ] Pencarian dan pagination berjalan.

## D. Merek dan satuan

- [ ] Tambah dan edit merek berhasil.
- [ ] Kode merek duplikat ditolak.
- [ ] Tambah satuan dengan `jumlah_desimal = 0` berhasil.
- [ ] Tambah satuan dengan `jumlah_desimal = 1`, `2`, dan `3` berhasil.
- [ ] Nilai `jumlah_desimal` di atas 3 ditolak karena kapasitas kuantitas skema paten tiga digit.
- [ ] Kode dan nama satuan duplikat ditolak.
- [ ] Mengubah jumlah desimal menyesuaikan validasi kuantitas pada request berikutnya.
- [ ] Tidak ada aturan yang memeriksa kode satuan tertentu seperti PCS, KG, METER, atau sejenisnya.

## E. Master barang dan jasa

- [ ] Tambah barang dengan kategori, merek, dan satuan dasar berhasil.
- [ ] Tambah jasa berhasil dan stok min/maks tersimpan nol.
- [ ] Kode barang duplikat ditolak.
- [ ] Satuan dasar otomatis memiliki konversi 1.
- [ ] Tambah beberapa satuan alternatif berhasil.
- [ ] Satuan alternatif yang sama dalam satu barang ditolak.
- [ ] Konversi nol atau negatif ditolak.
- [ ] Hanya satu satuan pembelian utama yang dapat dipilih.
- [ ] Hanya satu satuan penjualan utama yang dapat dipilih.
- [ ] Barcode duplikat dalam form yang sama ditolak.
- [ ] Barcode yang sudah pernah dipakai, termasuk pada baris soft-deleted, ditolak tanpa SQL error mentah.
- [ ] Harga beli dan harga jual acuan menerima maksimal dua desimal.
- [ ] Satuan 0 desimal menolak stok `1,5`.
- [ ] Satuan 2 desimal menerima stok `1,25` tetapi menolak `1,255`.
- [ ] Stok minimum lebih besar dari stok maksimum ditolak ketika stok maksimum lebih dari nol.
- [ ] Barang nonaktif yang diedit tetap nonaktif.
- [ ] Tombol aktivasi/nonaktivasi mengubah status secara eksplisit.
- [ ] Pencarian berdasarkan kode, nama, merek, dan barcode berhasil.
- [ ] Audit tambah, edit, dan perubahan status tersedia.

## F. Jenis pelanggan, pelanggan, dan alamat

- [ ] Tambah jenis pelanggan selain data default berhasil.
- [ ] Tambah pelanggan berhasil.
- [ ] Kode pelanggan duplikat ditolak.
- [ ] Data identitas, kontak, NPWP, kredit, jatuh tempo, dan potongan tersimpan benar.
- [ ] Tambah beberapa alamat pelanggan berhasil.
- [ ] Hanya satu alamat tambahan dapat ditandai sebagai alamat utama.
- [ ] Koordinat lintang di luar -90 sampai 90 ditolak.
- [ ] Koordinat bujur di luar -180 sampai 180 ditolak.
- [ ] Edit alamat tidak meninggalkan alamat lama sebagai data aktif ganda.
- [ ] Pelanggan biasa dapat dinonaktifkan dan diaktifkan kembali.
- [ ] Pelanggan tunai default tidak dapat dinonaktifkan.
- [ ] Pencarian kode, nama, WhatsApp, dan kontak berhasil.

## G. Pemasok

- [ ] Tambah pemasok berhasil.
- [ ] Kode pemasok duplikat ditolak.
- [ ] Kontak, alamat, NPWP, rekening, batas hutang, dan jatuh tempo tersimpan benar.
- [ ] Edit dan perubahan status berhasil.
- [ ] Pencarian nama, kode, kontak, dan WhatsApp berhasil.
- [ ] Audit perubahan pemasok tersedia.

## H. Gudang dan lokasi

- [ ] Data gudang hanya menampilkan cabang aktif saat ini.
- [ ] Tambah gudang biasa berhasil.
- [ ] Kode gudang boleh sama di cabang lain tetapi tidak boleh duplikat dalam cabang yang sama.
- [ ] Tambah zona, rak, baris, tingkat, dan area umum berhasil.
- [ ] Kode lokasi tidak boleh duplikat dalam gudang yang sama.
- [ ] Lokasi tidak dapat menjadi induk dirinya sendiri.
- [ ] Lokasi tidak dapat memilih turunannya sebagai induk.
- [ ] Gudang RUSAK dan RETUR tidak dapat dinonaktifkan.
- [ ] Kode dan jenis gudang khusus tidak dapat diubah.
- [ ] Lokasi AREA-RUSAK dan AREA-RETUR tidak dapat dinonaktifkan.
- [ ] Kode, tipe, dan posisi induk lokasi khusus tidak dapat diubah.
- [ ] Pengguna cabang A tidak dapat memanipulasi ID untuk mengubah gudang cabang B.
- [ ] Audit gudang dan lokasi tersedia.

## I. Kas, bank, metode pembayaran, dan kategori biaya

- [ ] Tambah kas berhasil tanpa data rekening bank.
- [ ] Tambah bank mewajibkan nama bank, nomor rekening, dan pemilik rekening.
- [ ] Kode kas/bank unik per cabang.
- [ ] Saldo awal negatif dapat dipakai hanya bila kebijakan bisnis mengizinkan dan tersimpan benar.
- [ ] Tambah metode pembayaran berhasil.
- [ ] Biaya persen di atas 100 ditolak.
- [ ] Metode TUNAI tidak dapat dipindahkan ke kelompok lain atau dinonaktifkan.
- [ ] Tambah kategori biaya utama dan subkategori berhasil.
- [ ] Siklus induk kategori biaya ditolak.
- [ ] Semua data mendukung pencarian, edit, status, dan audit.

## J. Armada

- [ ] Tambah armada pada cabang aktif berhasil.
- [ ] Nomor polisi duplikat ditolak.
- [ ] Tahun kendaraan tidak wajar ditolak.
- [ ] Kapasitas kilogram dan meter kubik tersimpan benar.
- [ ] Status kendaraan Tersedia, Digunakan, Perawatan, dan Nonaktif dapat dipilih.
- [ ] Armada cabang lain tidak tampil dan tidak dapat diubah melalui manipulasi ID.
- [ ] Edit, status aktif, pencarian, dan audit berhasil.

## K. Tarif pajak

- [ ] Pajak default NON_PAJAK tetap 0%.
- [ ] Tambah tarif pajak lain berhasil.
- [ ] Persen pajak negatif atau di atas 100 ditolak.
- [ ] Jenis Masukan, Keluaran, dan Keduanya dapat dipilih.
- [ ] Edit dan perubahan status tarif selain NON_PAJAK berhasil.
- [ ] Audit perubahan pajak tersedia.

## L. Daftar harga

- [ ] Tambah daftar harga untuk cabang aktif berhasil.
- [ ] Daftar harga dapat ditujukan ke satu jenis pelanggan.
- [ ] Daftar harga tanpa jenis pelanggan dapat dibuat sebagai daftar umum.
- [ ] Tanggal selesai sebelum tanggal mulai ditolak.
- [ ] Periode aktif yang bertabrakan pada cabang dan jenis pelanggan yang sama ditolak.
- [ ] Periode yang sama pada cabang berbeda tidak dianggap bertabrakan.
- [ ] Daftar harga nonaktif dapat dipersiapkan dengan periode yang sama.
- [ ] Mengaktifkan daftar harga nonaktif tetap menjalankan pemeriksaan benturan.
- [ ] Detail membutuhkan barang-satuan aktif.
- [ ] Kombinasi barang-satuan dan jumlah minimum duplikat ditolak.
- [ ] Jumlah minimum mengikuti `satuan.jumlah_desimal`.
- [ ] Harga jual maksimal dua desimal.
- [ ] Potongan 0–100% dengan maksimal empat desimal.
- [ ] Edit detail menonaktifkan detail lama tanpa membuat duplicate-key error.
- [ ] Status, pencarian, pagination, dan audit berhasil.

## M. Role dan akses URL langsung

- [ ] Jumlah permission aktif setelah Fase 2 dan Fase 3 adalah 29.
- [ ] Administrator dan Pemilik dapat mengakses seluruh master Fase 3.
- [ ] Kasir dapat melihat barang, pelanggan, dan daftar harga sesuai matriks awal.
- [ ] Kasir tidak dapat menambah atau mengubah barang melalui request langsung.
- [ ] Gudang memperoleh akses barang dan gudang sesuai matriks.
- [ ] Pembelian memperoleh akses barang dan pemasok sesuai matriks.
- [ ] Penjualan memperoleh akses barang, pelanggan, dan daftar harga sesuai matriks.
- [ ] Keuangan memperoleh akses master keuangan dan pajak sesuai matriks.
- [ ] Menu yang tidak diizinkan tidak tampil pada sidebar.
- [ ] Mengetik URL secara langsung tetap menghasilkan 403 untuk permission yang tidak dimiliki.
- [ ] Mengganti cabang memengaruhi data gudang, kas/bank, armada, daftar harga, dan dashboard.

## N. Tampilan dan asset

- [ ] Dashboard menampilkan jumlah barang, pelanggan, gudang, dan daftar harga cabang aktif.
- [ ] Semua halaman memakai UBold lokal.
- [ ] Nunito lokal termuat.
- [ ] Network browser tidak memuat CDN eksternal.
- [ ] Tidak ada request `/css2*` yang gagal.
- [ ] Modal tambah dan edit dapat dipakai pada desktop.
- [ ] Modal, tabel, dan sidebar dapat dipakai pada viewport mobile.
- [ ] Tabel lebar dapat di-scroll tanpa merusak layout.
- [ ] Console browser tidak menampilkan error JavaScript yang memblokir fungsi tambah/hapus baris dinamis.

## O. Regresi, backup, dan restore

- [ ] `vendor/bin/pint --test` berhasil.
- [ ] `php artisan test` berhasil.
- [ ] Integration test Fase 2 berhasil.
- [ ] Integration test Fase 3 berhasil.
- [ ] `php artisan skema:verifikasi --rinci` tetap berhasil setelah seluruh CRUD.
- [ ] Tidak ada tabel, kolom, index, foreign key, atau view paten yang berubah.
- [ ] Tidak ada tabel Laravel tambahan selain `migrations`.
- [ ] Backup sebelum Fase 3 dapat di-restore ke database development lain.
- [ ] Setelah restore, login, multi-cabang, permission Fase 2, master Fase 3, dan audit tetap berfungsi.
- [ ] Draft PR #4 tetap open, draft, dan belum merged selama pengujian.
- [ ] Tidak ada workflow komentar/review yang mengaktifkan auto-merge.

## P. Keputusan kelulusan

Fase 3 hanya boleh dinyatakan lulus apabila:

- [ ] fitur kritis master barang, pelanggan, pemasok, gudang, keuangan, pajak, dan daftar harga berhasil;
- [ ] aturan desimal berasal dari `satuan.jumlah_desimal`;
- [ ] isolasi cabang dan RBAC URL langsung berhasil;
- [ ] periode daftar harga tidak bertabrakan;
- [ ] data default terlindungi;
- [ ] backup dan restore berhasil;
- [ ] seluruh CI serta regression test hijau;
- [ ] pemilik menyatakan eksplisit `Fase 3 lulus`.

Sebelum itu, PR #4 tidak boleh digabung dan Fase 4 tidak boleh dimulai.
