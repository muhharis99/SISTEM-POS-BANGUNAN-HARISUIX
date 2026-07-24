# Panduan Pengguna Sistem POS Toko Bangunan HARISUIX

## 1. Tujuan

Panduan ini menjelaskan penggunaan aplikasi berdasarkan alur operasional dan peran pengguna. Menu yang terlihat di aplikasi mengikuti hak akses serta cabang aktif masing-masing akun.

## 2. Prinsip penggunaan

- Gunakan akun pribadi dan jangan membagikan kata sandi.
- Pastikan cabang aktif sudah benar sebelum membuat transaksi.
- Cari data sebelum menambahkan data baru.
- Periksa dokumen secara lengkap sebelum menekan tombol setujui atau posting.
- Jangan mengubah database secara manual.
- Catat nomor dokumen dan pesan kesalahan ketika meminta dukungan.

## 3. Masuk dan memilih cabang

1. Buka alamat aplikasi melalui HTTPS.
2. Masukkan nama pengguna dan kata sandi.
3. Pilih cabang tempat Anda bekerja.
4. Periksa nama cabang aktif pada tampilan aplikasi.
5. Gunakan menu sesuai tugas Anda.
6. Keluar dari sistem setelah pekerjaan selesai, terutama pada komputer bersama.

## 4. Dashboard

Dashboard menampilkan ringkasan sesuai cabang aktif dan hak akses. Informasi dapat mencakup:

- nilai dan jumlah penjualan;
- nilai dan jumlah pembelian;
- laba kotor;
- saldo kas dan bank;
- sisa hutang serta piutang;
- jumlah stok menipis;
- tren penjualan;
- barang terlaris.

Periksa periode yang digunakan sebelum membandingkan angka dengan laporan.

## 5. Master data

### 5.1 Barang

- Gunakan kode barang yang unik dan konsisten.
- Isi nama, kategori, merek, satuan dasar, dan status aktif.
- Periksa kebutuhan lot, kedaluwarsa, berat, ukuran, serta spesifikasi.
- Tentukan stok minimum dan maksimum.
- Tambahkan barcode pada satuan yang benar.
- Periksa konversi satuan sebelum barang digunakan pada transaksi.

### 5.2 Pelanggan

- Cari pelanggan terlebih dahulu agar tidak duplikat.
- Isi jenis pelanggan, identitas, kontak, dan alamat.
- Untuk pelanggan tempo, periksa batas kredit serta jatuh tempo.
- Gunakan alamat pengiriman yang sesuai dengan pesanan.

### 5.3 Pemasok

- Isi identitas, kontak, alamat, dan status pemasok.
- Pastikan pemasok aktif sebelum dipilih pada pembelian.
- Hindari membuat pemasok baru hanya karena perbedaan penulisan nama.

### 5.4 Gudang dan lokasi

- Gudang harus terhubung ke cabang yang benar.
- Gunakan lokasi atau rak untuk mempermudah pencarian fisik.
- Jangan menggunakan lokasi cabang lain pada transaksi.

### 5.5 Daftar harga

- Tentukan jenis pelanggan dan periode harga.
- Periksa satuan barang yang digunakan.
- Pastikan harga aktif tidak bertabrakan secara tidak sengaja.

## 6. Persediaan dan gudang

### 6.1 Saldo dan mutasi

Gunakan halaman persediaan untuk melihat stok, dipesan, rusak, tersedia, serta riwayat mutasi. Pastikan filter gudang, lokasi, barang, dan periode sudah benar.

### 6.2 Stok awal

Stok awal digunakan saat inisialisasi atau koreksi resmi. Isi jumlah, harga pokok, gudang, lokasi, dan alasan. Hindari mengulang stok awal untuk barang yang sama tanpa pemeriksaan.

### 6.3 Transfer stok

1. Pilih gudang atau lokasi sumber.
2. Pilih tujuan yang berbeda.
3. Tambahkan barang dan jumlah.
4. Periksa stok tersedia.
5. Simpan dan setujui sesuai kewenangan.
6. Pastikan mutasi keluar dan masuk tercatat.

### 6.4 Stok opname

1. Tentukan gudang dan waktu opname.
2. Lakukan hitung fisik.
3. Masukkan jumlah fisik.
4. Periksa selisih dengan saldo sistem.
5. Simpan bukti serta alasan selisih.
6. Setujui hasil hanya setelah verifikasi.

### 6.5 Penyesuaian stok

Penyesuaian wajib memiliki alasan yang jelas, misalnya rusak, hilang, koreksi administrasi, atau hasil opname. Setiap penyesuaian harus menghasilkan mutasi dan jejak audit.

## 7. Pembelian

### 7.1 Permintaan dan pesanan

- Pilih pemasok dan cabang yang benar.
- Tambahkan barang, satuan, jumlah, dan estimasi harga.
- Periksa tanggal kebutuhan serta catatan.
- Pesanan pembelian harus mengikuti persetujuan internal.

### 7.2 Penerimaan barang

- Cocokkan barang fisik dengan pesanan dan surat jalan.
- Periksa jumlah, satuan, kondisi, lot, dan kedaluwarsa.
- Pilih gudang serta lokasi tujuan.
- Catat kekurangan atau kerusakan.
- Jangan menyetujui penerimaan yang belum diverifikasi.

### 7.3 Faktur pembelian

- Cocokkan nomor faktur pemasok.
- Periksa harga, potongan, pajak, biaya, pembayaran, dan jatuh tempo.
- Pastikan penerimaan sumber sudah benar.
- Transaksi tempo menghasilkan hutang pemasok.

### 7.4 Retur pembelian

Retur harus mengacu pada transaksi sumber. Pilih barang dan jumlah yang benar, jelaskan alasan, lalu periksa dampak stok serta hutang.

### 7.5 Pembayaran hutang

- Pilih pemasok dan dokumen hutang.
- Periksa saldo tersisa.
- Tentukan kas atau bank serta tanggal pembayaran.
- Alokasikan nilai pembayaran secara tepat.
- Simpan bukti pembayaran sebagai lampiran bila diperlukan.

## 8. Penjualan dan POS

### 8.1 Penawaran dan pesanan

- Pilih pelanggan.
- Tambahkan barang, satuan, jumlah, harga, dan potongan.
- Tentukan alamat serta jadwal pengiriman bila dibutuhkan.
- Periksa masa berlaku penawaran.
- Ubah menjadi pesanan hanya setelah disetujui pelanggan.

### 8.2 Transaksi POS atau penjualan

1. Pastikan cabang aktif benar.
2. Pilih pelanggan atau pelanggan umum.
3. Pindai barcode atau pilih barang.
4. Periksa satuan, jumlah, harga, potongan, dan pajak.
5. Tambahkan biaya pengiriman bila ada.
6. Pilih metode pembayaran.
7. Untuk tempo, periksa batas kredit dan jatuh tempo.
8. Periksa total, pembayaran, kembalian, dan piutang.
9. Setujui transaksi satu kali.
10. Cetak nota setelah transaksi aktif.

### 8.3 Pembayaran piutang

- Pilih pelanggan dan dokumen piutang.
- Periksa sisa piutang.
- Tentukan kas atau bank.
- Alokasikan pembayaran ke dokumen yang benar.
- Pastikan status piutang berubah sesuai nilai pembayaran.

### 8.4 Pengiriman

- Periksa alamat pengiriman.
- Tentukan jadwal, kendaraan, dan pengemudi.
- Cocokkan barang dengan pesanan.
- Perbarui status sesuai proses nyata.
- Simpan bukti serah terima bila tersedia.

### 8.5 Retur penjualan

Retur harus mengacu pada penjualan sumber. Pilih item, jumlah, alasan, kondisi barang, dan tindakan stok. Periksa pengaruh retur terhadap pembayaran atau piutang.

## 9. Kas, bank, dan akuntansi

### 9.1 Transaksi kas

- Pilih jenis masuk, keluar, atau pindah.
- Tentukan kas/bank sumber dan tujuan.
- Untuk pindah dana, sumber dan tujuan harus berbeda.
- Pilih kategori dan isi keterangan.
- Periksa nilai sebelum persetujuan.

### 9.2 Daftar akun dan pemetaan

- Gunakan akun detail untuk pencatatan jurnal.
- Pastikan kode akun unik.
- Pemetaan akun harus sesuai fungsi transaksi.
- Hindari mengganti pemetaan tanpa analisis dampak.

### 9.3 Jurnal umum

- Isi tanggal, nomor referensi, dan keterangan.
- Tambahkan minimal dua baris.
- Satu baris hanya boleh memiliki debit atau kredit.
- Total debit dan kredit harus sama.
- Posting hanya setelah pemeriksaan.

## 10. Laporan dan ekspor

1. Pilih jenis laporan.
2. Atur periode dan pencarian.
3. Pastikan cabang aktif benar.
4. Cocokkan ringkasan dengan transaksi sumber bila diperlukan.
5. Gunakan ekspor CSV hanya untuk kebutuhan resmi.
6. Simpan berkas ekspor pada lokasi aman.
7. Jangan mengirim data bisnis melalui kanal yang tidak disetujui.

## 11. Lampiran dan audit

### Lampiran

- Unggah berkas yang relevan dan aman.
- Pastikan dokumen sumber benar.
- Gunakan tipe berkas serta ukuran yang diizinkan.
- Jangan mengunggah executable atau berkas tidak dikenal.

### Audit

Gunakan filter tanggal, pengguna, modul, aktivitas, tabel, referensi, dan alamat IP. Audit dipakai untuk penelusuran, bukan untuk mengubah transaksi.

## 12. Pusat bantuan

Menu **Pusat Bantuan** menampilkan panduan yang disesuaikan dengan hak akses pengguna. Gunakan pencarian untuk menemukan topik seperti stok, pembelian, penjualan, jurnal, atau audit.

## 13. Pelaporan masalah

Siapkan informasi berikut:

- waktu kejadian;
- cabang aktif;
- nama modul;
- nomor dokumen;
- langkah sebelum masalah muncul;
- pesan kesalahan;
- tangkapan layar tanpa kata sandi;
- jumlah pengguna yang terdampak;
- apakah masalah dapat diulang.

Jangan mengirim kata sandi, isi `.env`, kredensial database, atau backup melalui kanal dukungan biasa.

## 14. Penutupan kerja harian

- Pastikan transaksi yang seharusnya selesai tidak tertinggal sebagai draf.
- Rekonsiliasi kas dan bank.
- Periksa transaksi yang gagal atau dibatalkan.
- Tinjau stok kritis dan pengiriman tertunda.
- Pastikan backup terjadwal berhasil.
- Keluar dari sistem.
