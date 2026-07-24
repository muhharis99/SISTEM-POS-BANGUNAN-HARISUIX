# Fase 11 — Ringkasan Implementasi UAT dan Serah-Terima

## Status

**IMPLEMENTASI TEKNIS SEDANG DIKERJAKAN — BELUM LULUS.**

- Branch: `fase-11-uat-release-candidate-serah-terima`
- Pull request: Draft PR #14
- Target: `main`
- Auto-merge: dilarang dan tidak digunakan
- Tag/GitHub Release final: belum dibuat
- Deployment otomatis: tidak dilakukan
- Fase 12: belum dimulai

## Batasan SQL paten

Fase 11 tidak menambahkan atau mengubah tabel, kolom, index, foreign key, migration bisnis, view, maupun permission bisnis.

Target tetap:

- 71 base table;
- 3 view paten;
- 98 permission aktif;
- tanpa tabel infrastruktur Laravel yang dilarang.

## Pusat bantuan

Route `/panduan` tersedia untuk pengguna yang sudah login dan memiliki cabang aktif. Katalog panduan menyesuaikan hak akses pengguna sehingga modul yang tidak diizinkan tidak ditampilkan.

Topik yang tersedia meliputi:

- mulai menggunakan sistem;
- master data;
- persediaan dan gudang;
- pembelian dan hutang;
- POS, penjualan, piutang, dan pengiriman;
- kas, bank, dan akuntansi;
- dashboard, laporan, ekspor, dan nota;
- lampiran serta audit;
- pengguna, peran, dan hak akses;
- dukungan serta pelaporan masalah.

Halaman menggunakan layout UBold, aset lokal, pencarian sisi klien, dan tautan menuju modul yang tersedia.

## Manifest release candidate

Command:

```bash
php artisan sistem:buat-manifest-rilis v1.0.0-rc1
php artisan sistem:verifikasi-manifest-rilis storage/app/release-candidate/manifest-rilis.json
```

Manifest memuat:

- format manifest;
- versi dan waktu pembuatan;
- nama aplikasi, PHP, Laravel, serta commit;
- jumlah base table, view, permission aktif, dan tabel terlarang;
- checksum SHA-256 berkas kritis;
- pernyataan batasan keamanan.

Manifest tidak memuat kredensial, isi `.env`, path absolut, atau data transaksi. Nama berkas dibatasi dan hasil disimpan privat pada `storage/app/release-candidate`.

Verifikator membandingkan:

- format manifest;
- kondisi skema saat ini;
- jumlah permission;
- jumlah tabel terlarang;
- checksum berkas kritis.

## Dokumentasi kandidat rilis

- `CHANGELOG.md`;
- `docs/RELEASE-NOTES-v1.0.0-rc1.md`;
- `docs/PANDUAN-PENGGUNA.md`;
- `docs/UAT-RELEASE-CANDIDATE.md`;
- `docs/SERAH-TERIMA-OPERASIONAL.md`;
- `docs/FASE-11-CHECKLIST-PENGUJIAN-MANUAL.md`;
- `docs/FASE-11-STATUS.md`.

## Pengujian otomatis

Workflow Fase 11 dirancang untuk:

- memeriksa sintaks PHP dan Laravel Pint;
- menjalankan migration SQL paten pada MySQL 8.4;
- memastikan 71 base table, 3 view, dan 98 permission;
- menjalankan regression Fase 9 serta Fase 10;
- menguji autentikasi dan cabang aktif pada Pusat Bantuan;
- menguji filter panduan berdasarkan permission;
- membuat manifest kandidat rilis;
- memverifikasi manifest dan checksum;
- memastikan manifest tidak memuat rahasia;
- mengunggah manifest sebagai artifact terbatas;
- menjalankan regression fase sebelumnya dan full suite.

## Gate

Fase 11 tetap belum lulus. Draft PR #14 harus tetap draft dan tidak boleh di-merge sampai implementasi selesai, seluruh CI hijau, checklist UAT diterima, dan pemilik menyatakan eksplisit `Fase 11 lulus`.

Tag atau GitHub Release final memerlukan keputusan terpisah setelah merge Fase 11.