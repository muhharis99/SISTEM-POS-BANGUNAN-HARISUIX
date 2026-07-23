# Fase 7 — Status

## Checkpoint

- Fase 1 sampai Fase 6: lulus dan sudah digabung ke `main`.
- Fase 7: **dimulai — implementasi Kas, Bank, dan Akuntansi sedang dikerjakan**.
- Branch: `fase-7-kas-bank-akuntansi`.
- Pull request: Draft PR #10.
- Auto-merge: dilarang dan tidak digunakan.
- Fase 8 belum dimulai.

## Cakupan SQL paten

Fase 7 hanya menggunakan tabel yang sudah tersedia pada bagian 7 `struktur_database_toko_bangunan.sql`:

1. `transaksi_kas`
2. `akun_keuangan`
3. `pemetaan_akun`
4. `jurnal_umum`
5. `jurnal_umum_detail`

Tidak boleh menambah atau mengubah tabel, kolom, index, foreign key, migration bisnis, maupun view.

## Sasaran implementasi

- bagan akun bertingkat;
- pemetaan akun per cabang;
- transaksi kas masuk, kas keluar, dan pindah kas/bank;
- persetujuan transaksi kas secara atomik;
- jurnal umum manual berimbang;
- posting jurnal dan penguncian dokumen;
- buku kas/bank dan saldo per periode;
- buku besar;
- neraca saldo;
- laporan laba rugi;
- laporan posisi keuangan/neraca;
- audit aktivitas, RBAC, dan isolasi cabang;
- regression test Fase 1 sampai Fase 6.

## Gate

- Fase 7 tetap berstatus belum lulus selama implementasi, CI, dan checklist manual belum diterima pemilik.
- PR #10 harus tetap draft dan tidak boleh digabung ke `main` sebelum pemilik menyatakan eksplisit `Fase 7 lulus`.
- Auto-merge dilarang.
- Fase 8 tidak boleh dimulai tanpa instruksi terpisah.
