# Fase 10 — Status

## Checkpoint

- Fase 1 sampai Fase 9: lulus dan sudah digabung ke `main`.
- Fase 10: **dimulai — Kesiapan Produksi dan Deployment sedang dikerjakan**.
- Branch: `fase-10-kesiapan-produksi-deployment`.
- Pull request: Draft PR Fase 10.
- Auto-merge: dilarang dan tidak digunakan.
- Fase 11 belum dimulai.

## Cakupan

Fase 10 menyiapkan aplikasi agar dapat dipasang dan dioperasikan secara aman pada server produksi:

- pemeriksaan konfigurasi dan infrastruktur produksi;
- readiness endpoint tanpa membocorkan konfigurasi sensitif;
- backup dan restore database yang aman;
- deployment berbasis release dan symlink atomik;
- rollback release aplikasi;
- contoh konfigurasi Nginx, PHP-FPM, dan systemd timer backup;
- runbook deployment, backup, restore, rollback, dan troubleshooting;
- CI dry-run serta regression test Fase 1 sampai Fase 9.

## Batasan

- Tidak menambah atau mengubah tabel, kolom, index, foreign key, migration bisnis, maupun view.
- Tidak menambah permission bisnis; total tetap 98 permission aktif.
- Tidak menyimpan kredensial produksi di repositori.
- Tidak menjalankan deployment otomatis ke server mana pun.
- Tidak menggunakan auto-merge.

## Gate

Fase 10 tetap belum lulus sampai implementasi selesai, seluruh CI hijau, checklist manual diterima, dan pemilik menyatakan eksplisit `Fase 10 lulus`.
