# Fase 12 — Checklist Pengujian Manual

Status: **diterima pemilik pada 24 Juli 2026; Fase 12 dinyatakan lulus**.

> Tanda `[x]` pada pemeriksaan teknis menunjukkan bukti CI. Penerimaan pemilik menutup gate fase, tetapi tidak dipresentasikan sebagai bukti bahwa agen menjalankan deployment staging/produksi, simulasi insiden manusia, penetapan PIC, atau hypercare nyata.

## Integritas proyek

- [x] Fase 1 sampai Fase 11 berada pada `main` sebelum branch Fase 12 dibuat.
- [x] Branch aktif `fase-12-final-release-go-live-hypercare`.
- [x] `VERSION` berisi `v1.0.0`.
- [x] Tetap 71 base table, 3 view, dan 98 permission aktif.
- [x] Tidak ada migration bisnis atau perubahan SQL paten.
- [x] Tidak ada tabel infrastruktur Laravel yang dilarang.
- [x] Audit larangan auto-merge berhasil.

## Paket rilis final

- [x] `php artisan sistem:buat-paket-rilis-final v1.0.0` berhasil pada CI.
- [x] Paket `.tar.gz` dan sidecar `.sha256` terbentuk.
- [x] Permission paket dan checksum ditetapkan privat `0600`.
- [x] `php artisan sistem:verifikasi-paket-rilis-final` berhasil.
- [x] Versi, commit, 71 tabel, 3 view, dan 98 permission terverifikasi.
- [x] Inventaris berkas sama dengan isi arsip sumber.
- [x] Checksum komponen, berkas kritis, dan setiap berkas sumber cocok.
- [x] Paket menolak `.env`, backup, log, private key, vendor, node_modules, symlink, dan data runtime.
- [x] Salinan paket yang diubah ditolak oleh verifikator.
- [ ] Perubahan checksum komponen di dalam salinan paket belum diklaim diuji secara manual pada staging.
- [x] Versi prerelease ditolak oleh pembuat paket final.
- [x] Release notes final disertakan.
- [x] Paket final diunggah sebagai artifact CI dengan retensi terbatas.

## Gate go-live

- [x] Konfigurasi produksi aman dengan HTTPS dan debug mati disimulasikan pada CI.
- [x] Backup database nyata dibuat pada CI.
- [x] `.sql.gz` dan `.sha256` backup diverifikasi.
- [x] `php artisan sistem:periksa-go-live --ketat` berhasil dengan paket dan backup valid.
- [ ] Penolakan backup kedaluwarsa belum diklaim diuji pada staging nyata.
- [x] Checksum backup salah menyebabkan kegagalan pada integration test.
- [x] Paket yang rusak menyebabkan kegagalan.
- [ ] Maintenance mode belum diklaim diuji pada staging nyata.
- [ ] Kondisi ruang disk rendah belum diklaim disimulasikan pada staging nyata.

## Smoke test pascadeploy

- [x] `php artisan sistem:smoke-test-pascadeploy` berhasil pada CI.
- [x] Versi `v1.0.0` berhasil diverifikasi.
- [x] Route `masuk`, dashboard, panduan, dan readiness tersedia.
- [x] Database, skema, permission, dan storage sehat.
- [x] `/up` mengembalikan HTTP 200.
- [x] `/kesiapan` mengembalikan HTTP 200.
- [ ] Pemutusan database staging belum diklaim dijalankan.
- [ ] Maintenance mode pada staging belum diklaim dijalankan.
- [x] `scripts/post-deploy-smoke.sh` berhasil pada CI menggunakan paket dan backup valid.

## Go-live staging dan produksi

- [ ] Deployment release atomik pada staging belum diklaim dijalankan oleh agen.
- [ ] Rollback staging ke release sebelumnya belum diklaim dijalankan oleh agen.
- [ ] Database produksi tidak diubah atau di-rollback otomatis oleh repository.
- [ ] Uji operasional manusia pada penjualan, pembelian, retur, stok, hutang, piutang, kas, dan jurnal tidak diklaim telah dijalankan oleh agen.
- [ ] Uji nota 80 mm, CSV UTF-8, lampiran privat, dan audit pada staging nyata tidak diklaim telah dijalankan oleh agen.
- [ ] Status temuan operasional staging nyata berada di luar bukti CI.

## Observability, insiden, dan hypercare

- [x] Runbook observability, respons insiden, hypercare, dan pemeliharaan tersedia.
- [x] Sintaks `scripts/hypercare-check.sh` berhasil.
- [x] Pemeriksaan hypercare menghasilkan keluaran JSON privat pada CI.
- [ ] Penetapan PIC aplikasi, database, infrastruktur, dan operasional belum diklaim dilakukan oleh agen.
- [ ] Alarm server nyata, simulasi insiden manusia, komunikasi insiden, dan keputusan rollback belum diklaim diuji.
- [ ] systemd service/timer pada server staging/produksi belum dipasang oleh repository.
- [ ] Masa hypercare nyata dan evaluasi 2 jam, 1 hari, 3 hari, 7 hari, serta 14 hari belum diklaim dijalankan.

## Regresi dan CI

- [x] Syntax check Bash/PHP dan Laravel Pint berhasil.
- [x] Migration SQL paten pada MySQL 8.4 berhasil.
- [x] Integration test Fase 12 berhasil.
- [x] Paket final nyata dibuat dan diverifikasi pada CI.
- [x] Smoke test dan gate go-live berhasil pada CI.
- [x] Regresi Fase 11 dan Fase 10 berhasil.
- [x] Regresi fase sebelumnya berhasil.
- [x] Full regression suite berhasil.
- [x] UBold dan Nunito tetap lokal.
- [x] Seluruh 17 workflow pada head final `797cd0950e1288fe852d1c99d1638bbce738d603` berhasil.

## Gate akhir

- [x] Implementasi teknis selesai.
- [x] Seluruh checkpoint otomatis berhasil.
- [x] Checklist dan hasil teknis diterima pemilik pada 24 Juli 2026.
- [x] Pemilik menyatakan eksplisit `Fase 12 lulus`.
- [ ] PR #15 menunggu workflow pada head dokumentasi kelulusan, ready-for-review, dan merge manual dengan expected head SHA terbaru.
- [ ] Tag `v1.0.0` dan GitHub Release final dibuat setelah merge berhasil.

Pernyataan pemilik yang menutup gate:

```text
Fase 12 lulus dan silahkan lanjut ke Fase selanjutnya
```
