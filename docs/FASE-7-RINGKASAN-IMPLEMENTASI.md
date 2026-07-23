# Fase 7 — Ringkasan Implementasi Kas, Bank, dan Akuntansi

## Status

**IMPLEMENTASI TEKNIS DITERAPKAN — SEDANG DIUJI, BELUM LULUS.**

- Branch: `fase-7-kas-bank-akuntansi`
- Pull request: Draft PR #10
- Target: `main`
- Auto-merge: dilarang
- Fase 8: belum dimulai

## Cakupan skema paten

Fase 7 menggunakan tepat lima tabel yang sudah tersedia dalam SQL paten:

1. `transaksi_kas`
2. `akun_keuangan`
3. `pemetaan_akun`
4. `jurnal_umum`
5. `jurnal_umum_detail`

Tidak ada tabel, kolom, index, foreign key, migration bisnis, atau view yang ditambahkan maupun diubah.

## Implementasi

### Permission dan setup

Fase 7 menambahkan 14 permission. Total target setelah Fase 2 sampai Fase 7 adalah 89 permission aktif. Command `php artisan fase7:siapkan` bersifat idempotent dan menyiapkan:

- matriks permission untuk Administrator, Pemilik, Keuangan, dan Kasir;
- bagan akun bertingkat;
- akun rincian yang berbeda untuk setiap kas/bank aktif;
- pemetaan akun global dan pemetaan kas/bank per cabang.

### Bagan dan pemetaan akun

- akun induk dan akun rincian mengikuti kelompok ASET, KEWAJIBAN, MODAL, PENDAPATAN, dan BEBAN;
- hanya akun rincian aktif yang boleh dipakai pada jurnal;
- kode akun wajib unik;
- relasi induk tidak boleh membentuk siklus;
- pemetaan cabang diprioritaskan dibanding pemetaan global;
- setiap kas/bank memiliki akun rincian tersendiri agar pemindahan antar-rekening dapat dicatat dengan benar.

### Transaksi kas dan bank

- jenis transaksi: `MASUK`, `KELUAR`, dan `PINDAH`;
- transaksi disimpan sebagai `DRAF`;
- persetujuan memakai transaksi database dan penguncian baris;
- persetujuan membentuk jurnal otomatis berstatus `DIPOSTING`;
- transaksi masuk mendebet kas/bank dan mengkredit akun lawan;
- transaksi keluar mendebet beban dan mengkredit kas/bank;
- transaksi pindah mendebet kas/bank tujuan dan mengkredit kas/bank sumber;
- transaksi cabang lain tidak dapat diproses;
- transaksi yang sudah disetujui tidak dapat dibatalkan melalui alur draf.

### Jurnal umum

- jurnal manual minimal memiliki dua baris;
- setiap baris hanya boleh berisi salah satu sisi: debet atau kredit;
- total debet wajib sama dengan total kredit;
- jurnal disimpan sebagai `DRAF` dan baru memengaruhi laporan setelah `DIPOSTING`;
- dokumen sumber tidak boleh membentuk jurnal ganda;
- posting memakai transaksi database dan penguncian baris;
- jurnal yang sudah diposting tidak dapat dibatalkan melalui alur draf.

### Laporan operasional

Halaman Fase 7 menyediakan filter periode dan menampilkan:

- saldo berjalan setiap kas/bank;
- transaksi kas/bank;
- jurnal umum;
- bagan akun;
- pemetaan akun;
- neraca saldo;
- pendapatan dan beban periode;
- laba/rugi sederhana;
- perbandingan aset dengan kewajiban, modal, dan laba/rugi periode.

Saldo kas/bank dihitung dari saldo awal dan transaksi yang sudah disetujui. Laporan akuntansi hanya membaca jurnal berstatus `DIPOSTING`.

## Pengaman

- Form Request untuk seluruh formulir utama Fase 7;
- RBAC diterapkan pada route dan controller;
- seluruh transaksi dibatasi cabang aktif;
- audit aktivitas untuk penambahan, perubahan, persetujuan, posting, dan pembatalan;
- nomor dokumen menggunakan layanan penomoran atomik yang sudah dipakai fase sebelumnya;
- tidak ada saldo berjalan baru yang disimpan di luar skema paten;
- tidak ada perubahan skema database.

## Gate

Fase 7 tetap belum lulus sampai seluruh CI hijau, checklist pengujian manual diterima, dan pemilik menyatakan eksplisit `Fase 7 lulus`.
