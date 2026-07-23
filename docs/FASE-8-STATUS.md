# Fase 8 — Status

## Checkpoint

- Fase 1 sampai Fase 7: lulus dan sudah digabung ke `main`.
- Fase 8: **LULUS berdasarkan keputusan eksplisit pemilik pada 23 Juli 2026**.
- Branch: `fase-8-lampiran-audit`.
- Pull request: PR #11.
- Checklist manual: diterima pemilik.
- Auto-merge: dilarang dan tidak digunakan.
- Fase 9 belum dimulai.

## Cakupan SQL paten

Fase 8 menggunakan tepat dua tabel yang sudah tersedia pada bagian 8 `struktur_database_toko_bangunan.sql`:

1. `lampiran_dokumen`
2. `log_aktivitas`

Tidak ada tabel, kolom, index, foreign key, migration bisnis, maupun view yang ditambahkan atau diubah.

## Implementasi yang selesai

- 6 permission Fase 8 dan matriks peran; total 95 permission aktif;
- registry 18 jenis dokumen operasional;
- penyimpanan lampiran pada storage privat dengan nama UUID;
- validasi jenis dokumen, ID dokumen, cabang aktif, soft delete, dan permission modul;
- unggah, daftar, filter, unduh terotorisasi, dan soft delete lampiran;
- batas ukuran 10 MB dan daftar format berkas yang diizinkan;
- perlindungan path traversal dan akses lintas cabang;
- pencatatan audit unggah, unduh, hapus, dan ekspor;
- filter audit berdasarkan periode, pengguna, modul, aktivitas, tabel, referensi, IP, serta pencarian bebas;
- detail data sebelum/sesudah dengan penyamaran kata sandi, token, secret, authorization, cookie, dan session;
- ekspor audit CSV secara streaming dan terotorisasi;
- UI UBold, Form Request, RBAC, isolasi cabang, dan penguncian baris;
- workflow CI serta integration test Fase 8;
- regression test Fase 1 sampai Fase 7.

## Hasil pengujian otomatis

- Sintaks PHP berhasil.
- Laravel Pint berhasil.
- Backup sebelum migration dan sebelum testing berhasil dibuat.
- Migration SQL paten pada MySQL 8.4 berhasil.
- Verifikasi skema paten berhasil: tetap 71 base table dan 3 view.
- Tidak ada tabel infrastruktur Laravel yang dilarang.
- Total 95 permission aktif dan 6 permission Fase 8 terverifikasi.
- Route lampiran serta audit lanjutan terverifikasi.
- Integration test Fase 8 berhasil.
- Regression test Fase 1 sampai Fase 7 berhasil.
- Full regression suite berhasil.
- UBold/Nunito lokal dan visual test berhasil.
- Audit larangan auto-merge berhasil.
- Seluruh 13 workflow hijau pada checkpoint sebelum kelulusan `12d322544b6685f15eede2bd3dc3121ba8e829ca`.

## Gate merge

- Pemilik menyatakan eksplisit Fase 8 lulus dan meminta PR digabungkan.
- Checklist manual diterima pemilik berdasarkan keputusan kelulusan.
- Seluruh CI pada head kelulusan terbaru wajib hijau sebelum merge.
- Expected head SHA wajib dikunci ketika merge.
- Auto-merge tidak digunakan.
- Fase 9 tidak boleh dimulai tanpa instruksi terpisah.