# Fase 11 — Status

## Checkpoint

- Fase 1 sampai Fase 10: lulus dan sudah digabung ke `main`.
- Fase 11: **dimulai — UAT, Release Candidate, Panduan Pengguna, dan Serah-Terima Operasional**.
- Branch: `fase-11-uat-release-candidate-serah-terima`.
- Pull request: Draft PR Fase 11.
- Auto-merge: dilarang dan tidak digunakan.
- Fase 12 belum dimulai.

## Batasan

- Tidak menambah atau mengubah tabel, kolom, index, foreign key, migration bisnis, maupun view.
- Tetap 71 base table dan 3 view paten.
- Tidak menambah permission bisnis; total tetap 98 permission aktif.
- Tidak membuat tag atau GitHub Release final sebelum Fase 11 dinyatakan lulus.
- Tidak melakukan deployment otomatis ke server mana pun.

## Sasaran

- pusat bantuan dalam aplikasi yang dapat diakses pengguna terautentikasi;
- panduan penggunaan berdasarkan modul dan hak akses pengguna;
- command pembuat manifest release candidate tanpa data sensitif;
- changelog dan release notes kandidat rilis;
- matriks UAT lintas peran dan proses bisnis;
- panduan pelatihan, serah-terima, dukungan, dan eskalasi;
- checklist penerimaan release candidate;
- CI Fase 11 serta regression Fase 1 sampai Fase 10.

## Gate

Fase 11 tetap belum lulus sampai implementasi teknis selesai, seluruh CI hijau, checklist UAT diterima, dan pemilik menyatakan eksplisit `Fase 11 lulus`.