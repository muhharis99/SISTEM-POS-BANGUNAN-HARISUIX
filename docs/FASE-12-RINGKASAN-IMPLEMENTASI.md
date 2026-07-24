# Fase 12 — Ringkasan Implementasi Final Release dan Hypercare

## Status

**IMPLEMENTASI TEKNIS SEDANG DIKERJAKAN — BELUM LULUS.**

- Branch: `fase-12-final-release-go-live-hypercare`
- Pull request: Draft PR #15
- Target: `main`
- Target versi final: `v1.0.0`
- Auto-merge: dilarang dan tidak digunakan
- Deployment otomatis: tidak dilakukan
- Tag/GitHub Release final: belum dibuat

## Batasan SQL paten

Fase 12 tidak menambahkan atau mengubah tabel, kolom, index, foreign key, migration bisnis, view, maupun permission bisnis.

Target tetap:

- 71 base table;
- 3 view paten;
- 98 permission aktif;
- tanpa tabel infrastruktur Laravel yang dilarang.

## Paket rilis final

`PembuatPaketRilisFinal` membuat paket `.tar.gz` privat dari commit Git aktif. Paket berisi:

- arsip sumber dari `git archive`;
- manifest rilis final;
- inventaris seluruh berkas terlacak dengan ukuran dan checksum SHA-256;
- release notes final;
- daftar checksum seluruh komponen.

Paket luar memiliki sidecar checksum SHA-256. Verifikator memeriksa checksum luar, keamanan path arsip, checksum komponen, format manifest, versi, skema, permission, berkas kritis, inventaris, serta daftar isi arsip sumber.

Berkas `.env`, backup database, log runtime, private key, vendor, node_modules, storage runtime, dan data transaksi tidak boleh masuk paket.

Command:

```bash
php artisan sistem:buat-paket-rilis-final v1.0.0
php artisan sistem:verifikasi-paket-rilis-final /lokasi/paket.tar.gz
```

## Gate go-live

`PemeriksaGoLive` memeriksa:

- hasil kesiapan produksi;
- versi final;
- 98 permission aktif;
- maintenance mode;
- ruang disk;
- backup database terbaru;
- usia, checksum, dan keterbacaan gzip backup;
- validitas paket rilis final.

Command mendukung output JSON dan mode ketat yang memperlakukan peringatan sebagai kegagalan.

```bash
php artisan sistem:periksa-go-live --ketat --paket=/lokasi/paket.tar.gz
```

## Smoke test pascadeploy

Smoke test memeriksa versi, maintenance mode, route utama, database, 71 tabel, 3 view, 98 permission, storage, `/up`, dan `/kesiapan`.

```bash
php artisan sistem:smoke-test-pascadeploy
```

`scripts/post-deploy-smoke.sh` menjalankan pemeriksaan produksi ketat, smoke test, dan gate go-live menggunakan paket serta backup yang diberikan.

## Hypercare

`scripts/hypercare-check.sh`:

- memakai `flock` agar pemeriksaan tidak tumpang tindih;
- menjalankan smoke test dan pemeriksaan go-live dalam JSON;
- menyimpan output privat;
- membersihkan hasil yang lebih lama dari 14 hari.

Contoh systemd service dan timer disediakan untuk pemeriksaan setiap 15 menit selama masa hypercare.

## Dokumentasi

- `docs/RELEASE-NOTES-v1.0.0.md`;
- `docs/GO-LIVE-RUNBOOK.md`;
- `docs/OBSERVABILITY-DAN-RESPONS-INSIDEN.md`;
- `docs/HYPERCARE-DAN-PEMELIHARAAN.md`;
- `docs/FASE-12-CHECKLIST-PENGUJIAN-MANUAL.md`;
- `docs/FASE-12-STATUS.md`.

## Gate

Fase 12 tetap belum lulus. Draft PR #15 tidak boleh di-merge dan tag/GitHub Release final tidak boleh dibuat sampai implementasi teknis selesai, seluruh CI hijau, checklist go-live diterima, dan pemilik menyatakan eksplisit `Fase 12 lulus`.