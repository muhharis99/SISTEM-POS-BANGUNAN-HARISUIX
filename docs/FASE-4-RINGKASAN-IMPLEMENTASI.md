# Fase 4 — Ringkasan Implementasi Persediaan dan Mutasi Stok

## Status

**FASE 4 LULUS — implementasi, CI otomatis, dan checklist manual telah diterima pemilik.**

- Branch: `fase-4-persediaan`
- Pull request: PR #5
- Target: `main`
- Auto-merge: dilarang
- Fase 5: belum dimulai

Pemilik telah menyatakan Fase 4 lulus. PR #5 boleh diubah menjadi siap ditinjau dan digabung ke `main` setelah seluruh CI pada commit checkpoint terakhir tetap hijau. Auto-merge tetap tidak digunakan.

## Integritas skema paten

Implementasi menggunakan tepat 10 tabel persediaan yang sudah tersedia pada `struktur_database_toko_bangunan.sql`:

- `saldo_stok`;
- `mutasi_stok`;
- `stok_awal` dan `stok_awal_detail`;
- `transfer_stok` dan `transfer_stok_detail`;
- `stok_opname` dan `stok_opname_detail`;
- `penyesuaian_stok` dan `penyesuaian_stok_detail`.

Laporan saldo tersedia memakai view paten `tampilan_stok_tersedia`. Tidak ada migration bisnis, tabel, kolom, index, foreign key, atau view baru. Satu-satunya tabel internal Laravel di luar 70 tabel bisnis tetap `migrations`.

## Buku besar dan saldo persediaan

Semua proses yang mengubah stok memakai `LayananPersediaan` dan transaksi database. Satu perubahan stok wajib:

1. mengunci atau membuat baris `saldo_stok` untuk kombinasi gudang, lokasi, dan barang;
2. memvalidasi stok keluar agar tidak melebihi saldo yang dapat dipakai;
3. menghitung saldo dan harga pokok rata-rata baru;
4. memperbarui `saldo_stok`;
5. menulis satu baris `mutasi_stok` dengan saldo setelah transaksi.

Nomor dokumen menggunakan tabel paten `nomor_dokumen` dan penguncian baris agar dua transaksi bersamaan tidak memperoleh nomor yang sama.

## Konversi dan jumlah desimal

Jumlah yang dimasukkan dalam satuan jual/beli dikonversi ke satuan dasar memakai `barang_satuan.nilai_konversi`.

Jumlah desimal tidak ditentukan berdasarkan kode satuan. Validasi selalu membaca `satuan.jumlah_desimal`. Hasil konversi disimpan maksimal tiga angka di belakang koma sesuai kapasitas kolom kuantitas pada skema paten.

Barang yang mewajibkan nomor lot atau tanggal kedaluwarsa akan menolak detail transaksi yang tidak melengkapinya.

## Stok awal

Alur status:

```text
DRAF → DISETUJUI
DRAF → DIBATALKAN
```

Dokumen hanya dapat diubah ketika masih DRAF. Persetujuan dilakukan atomik dan menghasilkan mutasi `STOK_AWAL`. Persetujuan ulang ditolak sehingga saldo tidak dapat tergandakan.

## Transfer stok

Alur status:

```text
DRAF → DISETUJUI → DIKIRIM → DITERIMA
DRAF/DISETUJUI → DIBATALKAN
```

Transfer mendukung:

- antar-gudang dalam cabang aktif;
- antar-lokasi pada gudang yang sama;
- konversi satuan barang;
- pemeriksaan stok asal saat pengiriman;
- pengurangan saldo asal hanya ketika DIKIRIM;
- penambahan saldo tujuan hanya ketika DITERIMA;
- mutasi `TRANSFER_KELUAR` dan `TRANSFER_MASUK` yang terpisah.

Lokasi asal dan tujuan pada satu baris tidak boleh sama. Transfer yang sudah dikirim tidak dapat dibatalkan.

## Stok opname

Alur status:

```text
DRAF → PROSES → SELESAI → DISETUJUI
DRAF/PROSES/SELESAI → DIBATALKAN
```

Saat opname dimulai, jumlah sistem dan harga pokok disalin ke detail sebagai snapshot. Pengguna mengisi jumlah fisik, kemudian sistem menghitung selisih dan nilai selisih.

Saat disetujui, sistem membentuk `penyesuaian_stok` otomatis berstatus DISETUJUI. Selisih positif menambah stok dan selisih negatif mengurangi stok. Mutasi memakai jenis `STOK_OPNAME`.

## Penyesuaian stok

Penyesuaian manual memiliki alasan wajib dan detail TAMBAH atau KURANG. Dokumen DRAF dapat diubah atau dibatalkan. Persetujuan mengubah saldo serta membentuk mutasi `PENYESUAIAN_MASUK` atau `PENYESUAIAN_KELUAR` secara atomik.

Penyesuaian yang dibentuk otomatis dari stok opname tidak dapat diedit manual.

## Stok rusak dan stok tersedia

Saat barang ditempatkan pada gudang khusus berjenis `RUSAK`, `jumlah_rusak` mengikuti `jumlah_stok`. View paten menghitung:

```text
jumlah_tersedia = jumlah_stok - jumlah_dipesan - jumlah_rusak
```

Dengan demikian, stok pada gudang rusak tidak tampil sebagai stok yang tersedia untuk operasi normal.

## Akses dan audit

Fase 4 menambahkan 12 permission sehingga jumlah katalog aktif setelah Fase 2–4 menjadi 41. Akses endpoint diperiksa oleh middleware dan controller, bukan hanya melalui tampilan sidebar.

Setiap pembuatan, perubahan status, persetujuan, pengiriman, penerimaan, pembatalan, dan proses opname dicatat ke `log_aktivitas`.

## Antarmuka

Halaman Fase 4 memakai layout UBold dan Nunito lokal:

- Saldo dan Mutasi Persediaan;
- Kartu Stok;
- Stok Awal;
- Transfer Stok;
- Stok Opname;
- Penyesuaian Stok.

Tambah dan edit dokumen dilakukan melalui modal pada halaman daftar yang sama. Tidak ada CDN eksternal.

## Setup development

Sebelum menjalankan setup, buat backup database development:

```bash
mysqldump -u root -p --single-transaction sistem_informasi_toko_bangunan \
  > backup-sebelum-fase-4.sql
```

Kemudian jalankan:

```bash
php artisan fase4:siapkan
```

Command tersebut idempotent dan hanya menyiapkan permission serta matriks role tanpa mengubah skema.

## Pengujian otomatis wajib

Workflow Fase 4 harus:

1. menjalankan pemeriksaan sintaks PHP dan Pint;
2. membuat backup sebelum migration;
3. menjalankan migration SQL paten pada MySQL 8.4;
4. menyiapkan Fase 2 dan Fase 3;
5. membuat backup baseline sebelum setup/testing Fase 4;
6. mengunggah kedua backup sebagai artifact;
7. menjalankan `fase4:siapkan`;
8. memverifikasi tepat 71 base table dan 3 view;
9. memverifikasi tidak ada tabel infrastruktur Laravel tambahan;
10. memverifikasi 41 permission aktif;
11. menjalankan integration test Fase 4;
12. menjalankan regression test Fase 3 dan Fase 2;
13. menjalankan full regression suite Fase 1–Fase 4.

## Batas fase

Sebelum pernyataan eksplisit `Fase 4 lulus`:

- PR #5 harus tetap draft dan belum merged;
- auto-merge tidak boleh diaktifkan;
- tag/checkpoint Fase 4 tidak boleh dibuat;
- Fase 5 tidak boleh dimulai.
