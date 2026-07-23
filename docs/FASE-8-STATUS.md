# Fase 8 — Status

## Checkpoint

- Fase 1 sampai Fase 7: lulus dan sudah digabung ke `main`.
- Fase 8: **dimulai — implementasi Lampiran Dokumen dan Audit Lanjutan sedang dikerjakan**.
- Branch: `fase-8-lampiran-audit`.
- Pull request: Draft PR Fase 8.
- Auto-merge: dilarang dan tidak digunakan.
- Fase 9 belum dimulai.

## Cakupan SQL paten

Fase 8 menggunakan tepat dua tabel yang sudah tersedia pada bagian 8 `struktur_database_toko_bangunan.sql`:

1. `lampiran_dokumen`
2. `log_aktivitas`

Tidak boleh menambah atau mengubah tabel, kolom, index, foreign key, migration bisnis, maupun view.

## Sasaran implementasi

- penyimpanan lampiran pada storage privat;
- validasi jenis dokumen, ID dokumen, cabang aktif, dan permission modul;
- unggah, daftar, unduh, dan soft delete lampiran;
- pembatasan ukuran dan jenis berkas;
- nama berkas acak untuk mencegah benturan dan manipulasi path;
- pencatatan audit unggah, unduh, hapus, cetak, dan ekspor;
- filter audit berdasarkan periode, pengguna, modul, aktivitas, tabel, referensi, dan IP;
- detail data sebelum/sesudah dengan penyamaran data sensitif;
- ekspor audit CSV terotorisasi;
- RBAC, isolasi cabang, Form Request, dan regression test Fase 1 sampai Fase 7.

## Gate

- Fase 8 tetap belum lulus selama implementasi, CI, dan checklist manual belum diterima pemilik.
- PR Fase 8 harus tetap draft dan tidak boleh digabung ke `main` sebelum pemilik menyatakan eksplisit `Fase 8 lulus`.
- Auto-merge dilarang.
- Fase 9 tidak boleh dimulai tanpa instruksi terpisah.
