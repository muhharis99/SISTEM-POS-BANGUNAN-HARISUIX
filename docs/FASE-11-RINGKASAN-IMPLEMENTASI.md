# Fase 11 — Ringkasan Implementasi UAT dan Serah-Terima

## Status

**IMPLEMENTASI TEKNIS SELESAI DAN SELURUH CI OTOMATIS HIJAU — BELUM LULUS MENURUT KEPUTUSAN PEMILIK.**

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

Halaman menggunakan layout UBold, aset lokal, pencarian sisi klien, dan tautan menuju modul yang tersedia. Route telah diverifikasi tetap tersedia setelah Laravel route cache dibuat.

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

Manifest tidak memuat kredensial, isi `.env`, path absolut, atau data transaksi. Nama berkas dibatasi dan hasil disimpan privat pada `storage/app/release-candidate` dengan permission privat.

Verifikator membandingkan format, kondisi skema, jumlah permission, tabel terlarang, dan checksum berkas kritis. Perubahan checksum menyebabkan verifikasi gagal.

## Dokumentasi kandidat rilis

- `CHANGELOG.md`;
- `docs/RELEASE-NOTES-v1.0.0-rc1.md`;
- `docs/PANDUAN-PENGGUNA.md`;
- `docs/UAT-RELEASE-CANDIDATE.md`;
- `docs/SERAH-TERIMA-OPERASIONAL.md`;
- `docs/FASE-11-CHECKLIST-PENGUJIAN-MANUAL.md`;
- `docs/FASE-11-STATUS.md`.

Dokumentasi tersebut mencakup panduan operasional per modul, skenario UAT lintas peran, kriteria temuan, pelatihan, dukungan, eskalasi, persiapan go-live, dan pembagian tanggung jawab serah-terima.

## Hasil pengujian otomatis

Seluruh 16 workflow pada commit teknis `98af6398d94733c2fe0c77e992bf70ead9b13cc6` berhasil menjalankan:

- sintaks PHP dan Laravel Pint;
- migration SQL paten pada MySQL 8.4;
- verifikasi 71 base table, 3 view, dan 98 permission;
- verifikasi tidak adanya tabel infrastruktur Laravel yang dilarang;
- regression Fase 9 dan Fase 10;
- autentikasi serta cabang aktif pada Pusat Bantuan;
- filter panduan berdasarkan permission;
- route cache Pusat Bantuan;
- pembuatan manifest kandidat rilis;
- verifikasi manifest, commit, kondisi skema, dan checksum;
- pemeriksaan bahwa manifest tidak memuat rahasia;
- unggah manifest sebagai artifact terbatas;
- regression fase sebelumnya dan full suite;
- verifikasi UBold/Nunito lokal dan visual font;
- audit larangan auto-merge.

## Gate

Fase 11 tetap belum lulus. Draft PR #14 harus tetap draft dan tidak boleh di-merge sampai checklist UAT diterima serta pemilik menyatakan eksplisit `Fase 11 lulus`.

Pengujian otomatis tidak dianggap sebagai bukti bahwa UAT manusia, pelatihan, serah-terima, atau deployment staging/produksi nyata telah dilakukan.

Tag atau GitHub Release final memerlukan keputusan terpisah setelah merge Fase 11.