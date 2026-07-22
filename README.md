# Sistem POS Toko Bangunan HARISUIX

Sistem informasi terintegrasi untuk mendukung operasional toko bangunan, mencakup penjualan/POS, persediaan, pembelian, gudang, pengiriman, hutang, piutang, kas, bank, akuntansi, serta pengaturan pengguna dan hak akses.

> Status proyek: **dalam tahap perancangan dan pengembangan fondasi**. Repositori saat ini berisi blueprint alur sistem dan ERD, struktur database MySQL/MariaDB, serta template antarmuka admin statis. Backend aplikasi belum tersedia secara lengkap.

## Tujuan

- Mengintegrasikan transaksi kasir, stok, pembelian, dan keuangan.
- Mempercepat pencarian barang dan pelayanan pelanggan.
- Memantau stok per cabang, gudang, dan lokasi penyimpanan.
- Mengelola transaksi tunai, transfer, QRIS, kartu, dan tempo.
- Mengontrol hutang pemasok serta piutang pelanggan.
- Menyediakan log aktivitas untuk kebutuhan audit.

## Status Pengembangan

| Komponen | Status |
|---|---|
| Blueprint alur sistem | Tersedia |
| ERD | Tersedia dalam dokumen blueprint |
| Struktur database | Tersedia |
| Data awal/master | Tersedia |
| Template admin | Tersedia |
| Backend aplikasi | Belum tersedia |
| Integrasi database | Belum tersedia |
| Pengujian dan deployment | Belum dilakukan |

## Modul Sistem

### Pengguna dan Hak Akses

- Cabang dan pegawai.
- Akun pengguna.
- Peran dan hak akses.
- Hak akses berdasarkan cabang.
- Pengaturan aplikasi.
- Penomoran dokumen otomatis.

### Master Barang

- Kategori dan merek.
- Satuan dasar dan konversi satuan.
- Barcode per satuan barang.
- Harga beli dan harga jual.
- Ukuran, warna, berat, dan spesifikasi.
- Stok minimum dan maksimum.
- Dukungan lot dan tanggal kedaluwarsa.
- Metode persediaan rata-rata dan FIFO.

### Pelanggan dan Pemasok

- Pelanggan umum, tukang, kontraktor, dan pengecer.
- Alamat pelanggan dan alamat pengiriman.
- Batas piutang dan jatuh tempo.
- Data pemasok dan kontak pemasok.

### Persediaan dan Gudang

- Banyak gudang dalam satu cabang.
- Lokasi atau rak penyimpanan bertingkat.
- Saldo stok dan stok tersedia.
- Mutasi dan kartu stok.
- Transfer antargudang.
- Stok opname.
- Penyesuaian stok.

### Pembelian

- Permintaan pembelian.
- Pesanan pembelian.
- Penerimaan barang.
- Faktur dan retur pembelian.
- Hutang pemasok dan pembayaran hutang.

### POS dan Penjualan

- Penawaran penjualan.
- Pesanan pelanggan.
- Transaksi kasir/POS.
- Penjualan tunai dan tempo.
- Pembayaran multi-metode.
- Potongan harga dan pajak.
- Retur penjualan.
- Piutang dan pembayaran piutang.

### Pengiriman

- Alamat pengiriman.
- Jadwal pengiriman.
- Kendaraan dan pengemudi.
- Status pengiriman.
- Biaya pengiriman.

### Kas, Bank, dan Akuntansi

- Kas masuk dan kas keluar.
- Rekening kas dan bank.
- Mutasi kas/bank.
- Daftar akun keuangan.
- Jurnal umum dan detail jurnal.

### Audit

- Lampiran dokumen transaksi.
- Log aktivitas pengguna.
- Data sebelum dan sesudah perubahan.
- Alamat IP dan informasi peramban.

## Peran Bawaan

| Peran | Keterangan |
|---|---|
| Administrator | Akses penuh seluruh modul |
| Pemilik | Pemantauan usaha, laporan, dan persetujuan |
| Kasir | Transaksi penjualan dan pembayaran |
| Petugas Gudang | Persediaan, transfer, dan stok opname |
| Petugas Pembelian | Permintaan, pesanan, dan faktur pembelian |
| Petugas Penjualan | Penawaran, pesanan, dan pelanggan |
| Petugas Keuangan | Kas, bank, hutang, piutang, dan jurnal |

## Metode Pembayaran Bawaan

- Tunai
- Transfer bank
- QRIS
- Kartu debit
- Kartu kredit
- Pembayaran tempo

## Struktur Repositori

```text
SISTEM-POS-BANGUNAN-HARISUIX/
├── blueprint_alur_sistem_erd_toko_bangunan_wiryo_pojok.pdf
├── struktur_database_toko_bangunan.sql
├── template_admin/
└── README.md
```

| Berkas/Folder | Fungsi |
|---|---|
| `blueprint_alur_sistem_erd_toko_bangunan_wiryo_pojok.pdf` | Alur proses dan ERD sistem |
| `struktur_database_toko_bangunan.sql` | Struktur database, relasi, data awal, index, dan view |
| `template_admin/` | Referensi antarmuka admin berbasis HTML, CSS, JavaScript, dan Bootstrap 5 |

## Database

Database menggunakan nama:

```sql
sistem_informasi_toko_bangunan
```

Karakteristik database:

- MySQL atau MariaDB.
- Engine InnoDB.
- Character set utf8mb4.
- Relasi foreign key.
- Index untuk pencarian dan relasi.
- Soft delete dengan `deleted_at` dan `deleted_by`.
- Audit dengan `created_at`, `created_by`, `updated_at`, dan `updated_by`.
- View ringkasan stok, hutang, dan piutang.

Kelompok utama struktur database:

1. Cabang, pegawai, pengguna, dan hak akses.
2. Master barang, pelanggan, pemasok, gudang, dan kas.
3. Daftar harga.
4. Persediaan dan mutasi stok.
5. Pembelian dan hutang pemasok.
6. Penjualan, piutang, dan pengiriman.
7. Kas, bank, dan akuntansi.
8. Lampiran dan log aktivitas.

## Teknologi

Komponen yang sudah tersedia:

- MySQL/MariaDB
- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- Template admin UBold
- DataTables dan komponen pendukung antarmuka

Framework backend belum ditentukan atau belum disertakan dalam repositori.

## Instalasi Fondasi Proyek

### Clone repositori

```bash
git clone https://github.com/muhharis99/SISTEM-POS-BANGUNAN-HARISUIX.git
cd SISTEM-POS-BANGUNAN-HARISUIX
```

### Import database

```bash
mysql -u root -p < struktur_database_toko_bangunan.sql
```

Database juga dapat diimpor melalui DBeaver, phpMyAdmin, HeidiSQL, atau aplikasi pengelola database lainnya.

### Membuka template admin

Buka:

```text
template_admin/index.html
```

Template tersebut masih statis dan belum terhubung dengan database.

## Alur Utama

### Penjualan

```text
Pelanggan
→ Penawaran
→ Pesanan Penjualan
→ Transaksi POS
→ Pembayaran Tunai/Piutang
→ Pengurangan Stok
→ Pengiriman
→ Jurnal dan Laporan
```

### Pembelian

```text
Permintaan Pembelian
→ Pesanan Pembelian
→ Penerimaan Barang
→ Penambahan Stok
→ Faktur Pembelian
→ Pembayaran/Hutang
→ Jurnal dan Laporan
```

### Persediaan

```text
Saldo Awal
→ Penerimaan/Penjualan/Retur/Transfer
→ Mutasi dan Kartu Stok
→ Stok Opname
→ Penyesuaian
→ Saldo Stok Tersedia
```

## Rencana Pengembangan

- [ ] Menentukan framework backend.
- [ ] Membuat autentikasi dan manajemen sesi.
- [ ] Mengimplementasikan hak akses.
- [ ] Mengintegrasikan template admin.
- [ ] Membuat modul master barang dan barcode.
- [ ] Membuat modul pelanggan dan pemasok.
- [ ] Membuat modul pembelian dan gudang.
- [ ] Membuat halaman kasir/POS.
- [ ] Membuat modul pengiriman.
- [ ] Membuat modul hutang dan piutang.
- [ ] Membuat modul kas, bank, dan jurnal.
- [ ] Membuat laporan dan pencetakan nota.
- [ ] Menambahkan pengujian dan deployment.

## Catatan Implementasi

- Kata sandi wajib disimpan dalam bentuk hash yang aman.
- Transaksi stok dan keuangan harus menggunakan transaksi database.
- Setiap perubahan stok harus menghasilkan catatan mutasi.
- Hak akses wajib divalidasi pada backend.
- Nomor dokumen harus dibuat secara atomik agar tidak ganda.
- Transaksi tempo harus memeriksa batas kredit pelanggan.
- Backup database harus dilakukan secara berkala.

## Pengembang

**Muhammad Haris Chaidir**  
Web Developer & UI/UX Designer — HARISUIX

- GitHub: [muhharis99](https://github.com/muhharis99)
- Website: [harisuix.com](https://harisuix.com)

---

Dibuat untuk mendukung digitalisasi dan integrasi operasional toko bangunan.
