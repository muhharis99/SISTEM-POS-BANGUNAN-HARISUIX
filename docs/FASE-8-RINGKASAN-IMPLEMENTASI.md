# Fase 8 — Ringkasan Implementasi Lampiran Dokumen dan Audit Lanjutan

## Status

**FASE 8 LULUS — keputusan eksplisit pemilik diterima pada 23 Juli 2026.**

- Branch: `fase-8-lampiran-audit`
- Pull request: PR #11
- Target: `main`
- Checklist manual: diterima pemilik
- Auto-merge: dilarang dan tidak digunakan
- Fase 9: belum dimulai

## Cakupan skema paten

Fase 8 menggunakan tepat dua tabel yang sudah tersedia dalam SQL paten:

1. `lampiran_dokumen`
2. `log_aktivitas`

Tidak ada tabel, kolom, index, foreign key, migration bisnis, atau view yang ditambahkan maupun diubah.

## Implementasi

### Permission dan setup

Fase 8 menambahkan 6 permission. Total setelah Fase 2 sampai Fase 8 adalah 95 permission aktif:

- `LAMPIRAN_LIHAT`
- `LAMPIRAN_UNGGAH`
- `LAMPIRAN_UNDUH`
- `LAMPIRAN_HAPUS`
- `AUDIT_LIHAT_DATA`
- `AUDIT_UNDUH`

Command `php artisan fase8:siapkan` bersifat idempotent dan menyiapkan matriks akses Administrator, Pemilik, Keuangan, Kasir, Gudang, Pembelian, dan Penjualan.

### Lampiran dokumen

- registry 18 jenis dokumen operasional;
- referensi dokumen diverifikasi terhadap tabel, primary key, cabang aktif, soft delete, dan permission modul;
- berkas disimpan pada disk `local` yang privat;
- nama fisik memakai UUID dan tidak memakai nama dari browser;
- ukuran maksimum 10 MB;
- format yang didukung: PDF, JPG/JPEG, PNG, WEBP, CSV, XLS/XLSX, DOC/DOCX;
- daftar lampiran hanya memuat dokumen yang dapat diakses pada cabang aktif;
- unduhan selalu melalui controller terotorisasi;
- lokasi berkas wajib berada di bawah direktori `lampiran/` dan path traversal ditolak;
- penghapusan memakai soft delete sehingga rekam metadata dan audit tetap tersedia;
- unggah, unduh, dan hapus dicatat pada `log_aktivitas`.

### Audit lanjutan

- filter periode, pengguna, modul, jenis aktivitas, tabel, ID referensi, alamat IP, dan pencarian bebas;
- nama pengguna dan cabang ditampilkan pada daftar;
- halaman detail data sebelum/sesudah dibatasi permission khusus;
- field sensitif seperti kata sandi, token, secret, authorization, cookie, dan session otomatis diganti `[DISEMBUNYIKAN]`;
- struktur audit dibatasi kedalaman dan panjang string untuk mencegah log berlebihan;
- ekspor CSV menggunakan streaming dan permission `AUDIT_UNDUH`;
- aktivitas ekspor audit dicatat kembali sebagai aktivitas `UNDUH`.

### Antarmuka dan arsitektur

- UI menggunakan layout serta komponen UBold yang sudah ada;
- formulir unggah menggunakan Form Request;
- route Fase 8 dipisahkan dalam `routes/fase8.php` dan didaftarkan melalui `AppServiceProvider`;
- backend dan route menerapkan RBAC serta isolasi cabang;
- tidak tersedia URL publik langsung menuju storage lampiran.

## Pengujian otomatis

Workflow Fase 8 memverifikasi:

- sintaks PHP dan Laravel Pint;
- migration SQL paten pada MySQL 8.4;
- tetap 71 base table dan 3 view;
- total 95 permission aktif dan 6 permission Fase 8;
- tidak ada tabel infrastruktur Laravel yang dilarang;
- route lampiran dan audit lanjutan terdaftar;
- unggah, storage privat, unduh, dan soft delete;
- penolakan format tidak diizinkan, path traversal, dan akses lintas cabang;
- penyamaran data sensitif;
- filter, detail, dan ekspor audit;
- regression test Fase 1 sampai Fase 7.

## Gate merge

- [x] Implementasi teknis selesai.
- [x] Checklist manual diterima pemilik.
- [x] Pemilik menyatakan `Fase 8 lulus`.
- [x] Pemilik meminta PR #11 digabungkan.
- [ ] Seluruh workflow pada head kelulusan terbaru hijau.
- [ ] Expected head SHA dikunci ketika merge.
- [x] Auto-merge tidak digunakan.

Fase 9 belum dimulai dan tidak boleh dimulai tanpa instruksi terpisah.