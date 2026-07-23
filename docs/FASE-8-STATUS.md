# Fase 8 — Status

## Checkpoint

- Fase 1 sampai Fase 7: lulus dan sudah digabung ke `main`.
- Fase 8: **implementasi teknis selesai dan seluruh CI otomatis hijau; belum lulus menurut keputusan pemilik**.
- Branch: `fase-8-lampiran-audit`.
- Pull request: Draft PR #11.
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
- Seluruh 13 workflow hijau pada commit teknis `63c3c0f0c61bb73ead60ae35d1af236571ba3230`.

## Gate

- Draft PR #11 tetap draft dan belum di-merge.
- Checklist manual belum dinyatakan diterima oleh pemilik.
- Fase 8 hanya boleh dinyatakan lulus setelah pemilik menyatakan eksplisit `Fase 8 lulus`.
- Auto-merge dilarang dan tidak digunakan.
- Fase 9 tidak boleh dimulai tanpa instruksi terpisah.
