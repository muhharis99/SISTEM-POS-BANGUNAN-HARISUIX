# Fase 12 — Checklist Pengujian Manual

Status: **belum diterima pemilik**.

## Integritas proyek

- [ ] Pastikan Fase 1 sampai Fase 11 berada pada `main`.
- [ ] Pastikan branch aktif `fase-12-final-release-go-live-hypercare`.
- [ ] Pastikan `VERSION` berisi `v1.0.0`.
- [ ] Pastikan tetap 71 base table, 3 view, dan 98 permission aktif.
- [ ] Pastikan tidak ada migration bisnis atau perubahan SQL paten.
- [ ] Pastikan tidak ada tabel infrastruktur Laravel yang dilarang.
- [ ] Pastikan auto-merge tidak digunakan.

## Paket rilis final

- [ ] Jalankan `php artisan sistem:buat-paket-rilis-final v1.0.0`.
- [ ] Pastikan paket `.tar.gz` dan sidecar `.sha256` terbentuk.
- [ ] Pastikan permission paket dan checksum privat.
- [ ] Jalankan `php artisan sistem:verifikasi-paket-rilis-final`.
- [ ] Pastikan versi, commit, 71 tabel, 3 view, dan 98 permission benar.
- [ ] Pastikan inventaris berkas sama dengan isi arsip sumber.
- [ ] Pastikan checksum komponen dan berkas kritis cocok.
- [ ] Pastikan paket tidak memuat `.env`, backup, log, private key, vendor, node_modules, atau data runtime.
- [ ] Ubah satu byte paket dan pastikan verifikasi checksum gagal.
- [ ] Ubah checksum komponen pada salinan paket dan pastikan verifikasi gagal.
- [ ] Uji versi prerelease dan pastikan pembuat paket final menolak.
- [ ] Pastikan release notes final disertakan.

## Gate go-live

- [ ] Siapkan konfigurasi produksi aman dengan HTTPS dan debug mati.
- [ ] Buat backup database terbaru.
- [ ] Pastikan `.sql.gz` dan `.sha256` valid.
- [ ] Jalankan `php artisan sistem:periksa-go-live --ketat` dengan paket final.
- [ ] Pastikan backup lama ditolak sesuai batas usia.
- [ ] Pastikan checksum backup salah menyebabkan kegagalan.
- [ ] Pastikan paket yang rusak menyebabkan kegagalan.
- [ ] Pastikan maintenance mode menyebabkan kegagalan.
- [ ] Pastikan ruang disk rendah menghasilkan peringatan dan mode ketat gagal.

## Smoke test pascadeploy

- [ ] Jalankan `php artisan sistem:smoke-test-pascadeploy`.
- [ ] Pastikan versi `v1.0.0` berhasil diverifikasi.
- [ ] Pastikan route login, dashboard, panduan, dan readiness tersedia.
- [ ] Pastikan database, skema, permission, dan storage sehat.
- [ ] Pastikan `/up` mengembalikan HTTP 200.
- [ ] Pastikan `/kesiapan` mengembalikan HTTP 200.
- [ ] Putuskan database staging dan pastikan smoke test gagal.
- [ ] Aktifkan maintenance mode dan pastikan smoke test gagal.
- [ ] Jalankan `scripts/post-deploy-smoke.sh` dengan paket dan backup valid.

## Go-live staging

- [ ] Ikuti `docs/GO-LIVE-RUNBOOK.md` pada staging.
- [ ] Uji deployment release atomik.
- [ ] Uji rollback ke release sebelumnya.
- [ ] Pastikan database tidak di-rollback otomatis.
- [ ] Uji login dan isolasi cabang.
- [ ] Uji transaksi penjualan, pembelian, retur, stok, hutang, piutang, kas, dan jurnal.
- [ ] Uji nota 80 mm, CSV UTF-8, lampiran privat, dan audit.
- [ ] Pastikan tidak ada temuan KRITIS atau TINGGI terbuka.

## Observability dan insiden

- [ ] Tinjau `docs/OBSERVABILITY-DAN-RESPONS-INSIDEN.md`.
- [ ] Tetapkan pemilik keputusan dan PIC aplikasi, database, infrastruktur, serta operasional.
- [ ] Uji alarm readiness, error 5xx, disk, database, dan backup.
- [ ] Simulasikan insiden tinggi pada staging.
- [ ] Uji komunikasi insiden dan batas keputusan rollback.
- [ ] Pastikan bukti insiden tidak memuat rahasia.

## Hypercare

- [ ] Tinjau `docs/HYPERCARE-DAN-PEMELIHARAAN.md`.
- [ ] Jalankan `bash -n scripts/hypercare-check.sh`.
- [ ] Uji lock agar dua pemeriksaan tidak berjalan bersamaan.
- [ ] Pasang service dan timer pada staging.
- [ ] Pastikan timer berjalan setiap 15 menit.
- [ ] Pastikan output JSON privat dan retensi 14 hari bekerja.
- [ ] Pastikan backup tidak ditemukan menyebabkan kegagalan yang terlihat.
- [ ] Tetapkan jadwal evaluasi 2 jam, 1 hari, 3 hari, 7 hari, dan 14 hari.
- [ ] Tetapkan kriteria keluar dari hypercare.

## Regresi dan CI

- [ ] Jalankan syntax check dan Laravel Pint.
- [ ] Jalankan migration SQL paten pada MySQL 8.4.
- [ ] Jalankan integration test Fase 12.
- [ ] Buat serta verifikasi paket final nyata pada CI.
- [ ] Jalankan smoke test dan gate go-live pada CI.
- [ ] Jalankan regresi Fase 11 dan Fase 10.
- [ ] Jalankan regresi fase sebelumnya dan full suite.
- [ ] Pastikan UBold dan Nunito tetap lokal.
- [ ] Pastikan audit larangan auto-merge berhasil.

## Gate akhir

Fase 12 hanya boleh di-merge setelah seluruh CI hijau, checklist go-live diterima, dan pemilik menyatakan eksplisit:

```text
Fase 12 lulus
```

Tag `v1.0.0` dan GitHub Release final hanya dibuat setelah merge Fase 12 dan keputusan pemilik pada gate akhir.