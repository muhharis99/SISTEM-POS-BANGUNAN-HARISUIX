# Fase 13 — Status

## Checkpoint awal

- Fase 1 sampai Fase 12: lulus dan sudah digabung ke `main`.
- Fase 12 merge commit: `f9ff81a4b0e0e02fe05b61f03db95db10c8e5a6b`.
- Fase 13: **dimulai — Operasional Pascapeluncuran, Dukungan, SLA, dan Perbaikan Berkelanjutan**.
- Branch: `fase-13-operasional-pascapeluncuran`.
- Pull request: Draft PR Fase 13.
- Versi aplikasi tetap `v1.0.0` sampai ada maintenance release yang diterima pemilik.
- Auto-merge: dilarang dan tidak digunakan.
- Deployment otomatis ke server: tidak dilakukan.

## Batasan

- Tidak menambah atau mengubah tabel, kolom, index, foreign key, migration bisnis, maupun view.
- Tetap 71 base table dan 3 view paten.
- Tidak menambah permission bisnis; total tetap 98 permission aktif.
- Tidak menambahkan tabel infrastruktur Laravel yang dilarang.
- Tidak menyimpan kredensial, data transaksi, backup, atau bukti insiden sensitif pada repository.
- Tidak membuat maintenance release, tag, atau deployment tanpa gate pemilik.

## Sasaran

- tata kelola laporan bug dan insiden produksi;
- formulir permintaan perubahan yang terstruktur;
- klasifikasi prioritas dan SLA respons/penanganan;
- alur triase, reproduksi, perbaikan, verifikasi, dan penutupan;
- kebijakan maintenance release dan hotfix;
- daftar bukti operasional yang aman;
- checklist evaluasi pascapeluncuran;
- CI Fase 13 dan regresi Fase 1 sampai Fase 12.

## Gate

Fase 13 tetap belum lulus sampai implementasi teknis selesai, seluruh CI hijau, checklist operasional diterima, dan pemilik menyatakan eksplisit `Fase 13 lulus`. PR harus tetap draft dan tidak boleh di-merge sebelum gate tersebut terpenuhi.
