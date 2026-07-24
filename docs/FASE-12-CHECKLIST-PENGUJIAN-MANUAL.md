# Fase 12 — Checklist Pengujian Manual

Status: **checkpoint otomatis berhasil; checklist go-live belum diterima pemilik**.

> Tanda `[x]` hanya menunjukkan pemeriksaan yang benar-benar dibuktikan oleh CI. Pengujian staging, deployment produksi, simulasi insiden oleh manusia, penetapan PIC, dan hypercare nyata tetap bertanda `[ ]`.

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
- [ ] Ubah checksum komponen di dalam salinan paket dan pastikan verifikasi gagal pada staging/manual.
- [x] Versi prerelease ditolak oleh pembuat paket final.
- [x] Release notes final disertakan.
- [x] Paket final diunggah sebagai artifact CI dengan retensi terbatas.

## Gate go-live

- [x] Konfigurasi produksi aman dengan HTTPS dan debug mati disimulasikan pada CI.
- [x] Backup database nyata dibuat pada CI.
- [x] `.sql.gz` dan `.sha256` backup diverifikasi.
- [x] `php artisan sistem:periksa-go-live --ketat` berhasil dengan paket dan backup valid.
- [ ] Pastikan backup yang melampaui batas usia ditolak pada staging.
- [x] Checksum backup salah menyebabkan kegagalan pada integration test.
- [x] Paket yang rusak menyebabkan kegagalan.
- [ ] Pastikan maintenance mode menyebabkan kegagalan pada staging.
- [ ] Simulasikan ruang disk rendah dan pastikan mode ketat gagal.

## Smoke test pascadeploy

- [x] `php artisan sistem:smoke-test-pascadeploy` berhasil pada CI.
- [x] Versi `v1.0.0` berhasil diverifikasi.
- [x] Route `masuk`, dashboard, panduan, dan readiness tersedia.
- [x] Database, skema, permission, dan storage sehat.
- [x] `/up` mengembalikan HTTP 200.
- [x] `/kesiapan` mengembalikan HTTP 200.
- [ ] Putuskan database staging dan pastikan smoke test gagal.
- [ ] Aktifkan maintenance mode pada staging dan pastikan smoke test gagal.
- [x] `scripts/post-deploy-smoke.sh` berhasil pada CI menggunakan paket dan backup valid.

## Go-live staging

- [ ] Ikuti `docs/GO-LIVE-RUNBOOK.md` pada staging.
- [ ] Uji deployment release atomik pada staging.
- [ ] Uji rollback ke release sebelumnya.
- [ ] Pastikan database tidak di-rollback otomatis.
- [ ] Uji login dan isolasi cabang.
- [ ] Uji transaksi penjualan, pembelian, retur, stok, hutang, piutang, kas, dan jurnal.
- [ ] Uji nota 80 mm, CSV UTF-8, lampiran privat, dan audit.
- [ ] Pastikan tidak ada temuan KRITIS atau TINGGI terbuka.

## Observability dan insiden

- [ ] Tinjau `docs/OBSERVABILITY-DAN-RESPONS-INSIDEN.md` bersama tim operasional.
- [ ] Tetapkan pemilik keputusan dan PIC aplikasi, database, infrastruktur, serta operasional.
- [ ] Uji alarm readiness, error 5xx, disk, database, dan backup pada staging.
- [ ] Simulasikan insiden tinggi pada staging.
- [ ] Uji komunikasi insiden dan batas keputusan rollback.
- [ ] Pastikan bukti insiden tidak memuat rahasia.

## Hypercare

- [ ] Tinjau `docs/HYPERCARE-DAN-PEMELIHARAAN.md` bersama pemilik dan PIC.
- [x] Sintaks `scripts/hypercare-check.sh` berhasil.
- [ ] Uji lock dengan dua pemeriksaan yang berjalan bersamaan pada staging.
- [ ] Pasang service dan timer pada staging.
- [ ] Pastikan timer berjalan setiap 15 menit.
- [x] Pemeriksaan hypercare menghasilkan keluaran JSON privat pada CI.
- [ ] Verifikasi retensi keluaran 14 hari pada server staging.
- [ ] Pastikan backup tidak ditemukan menghasilkan kegagalan yang terlihat.
- [ ] Tetapkan jadwal evaluasi 2 jam, 1 hari, 3 hari, 7 hari, dan 14 hari.
- [ ] Tetapkan kriteria keluar dari hypercare.

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
- [x] Seluruh 17 workflow pada head teknis `669a5c9d4b82d368874175d4868fbfa09dea9c7f` berhasil.

## Gate akhir

- [x] Implementasi teknis selesai.
- [x] Seluruh checkpoint otomatis pada head teknis berhasil.
- [ ] Checklist go-live/staging diterima pemilik.
- [ ] Pemilik menyatakan eksplisit `Fase 12 lulus`.
- [ ] PR #15 diproses menuju ready-for-review dan merge manual dengan expected head SHA terbaru.
- [ ] Tag `v1.0.0` dan GitHub Release final dibuat setelah merge serta keputusan pemilik.

Fase 12 hanya boleh di-merge setelah checklist go-live diterima dan pemilik menyatakan eksplisit:

```text
Fase 12 lulus
```

Pengujian otomatis tidak dipresentasikan sebagai bukti bahwa deployment staging/produksi, simulasi insiden manusia, atau hypercare nyata telah dilakukan.