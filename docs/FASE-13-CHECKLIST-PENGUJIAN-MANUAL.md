# Fase 13 — Checklist Pengujian Manual

Status: **belum diterima pemilik**.

## Integritas proyek

- [ ] Pastikan Fase 1 sampai Fase 12 sudah berada pada `main`.
- [ ] Pastikan branch aktif `fase-13-operasional-pascapeluncuran`.
- [ ] Pastikan versi tetap `v1.0.0` selama belum ada maintenance release.
- [ ] Pastikan tetap 71 base table, 3 view, dan 98 permission aktif.
- [ ] Pastikan tidak ada migration bisnis atau perubahan SQL paten.
- [ ] Pastikan auto-merge tidak digunakan.

## Issue form

- [ ] Buka menu pembuatan issue dan pastikan blank issue dinonaktifkan.
- [ ] Pastikan form Laporan Bug atau Insiden dapat dibuka.
- [ ] Pastikan prioritas, modul, versi, lingkungan, reproduksi, hasil aktual, dan hasil harapan wajib diisi.
- [ ] Pastikan pengguna diperingatkan untuk tidak memasukkan data sensitif.
- [ ] Pastikan form Permintaan Perubahan dapat dibuka.
- [ ] Pastikan kriteria penerimaan dan potensi dampak dapat dicatat.
- [ ] Pastikan konfigurasi issue form valid dan tidak menghasilkan error YAML.

## Operasional dan SLA

- [ ] Tinjau `docs/OPERASIONAL-PASCAPELUNCURAN.md`.
- [ ] Tinjau `docs/SLA-DAN-DUKUNGAN.md`.
- [ ] Tetapkan jam layanan nyata.
- [ ] Tetapkan kanal eskalasi P0 yang tidak disimpan di repository publik.
- [ ] Tetapkan PIC pemilik keputusan, aplikasi, database, infrastruktur, dan operasional.
- [ ] Pastikan target respons P0–P3 sesuai kemampuan tim.
- [ ] Simulasikan triase satu bug dan satu permintaan perubahan menggunakan data uji.
- [ ] Pastikan issue tidak ditutup tanpa akar masalah, bukti verifikasi, dan risiko tersisa.

## Hotfix dan maintenance release

- [ ] Simulasikan keputusan apakah sebuah masalah membutuhkan dokumentasi, konfigurasi, hotfix, maintenance release, atau fase fitur baru.
- [ ] Pastikan hotfix P0/P1 tetap menggunakan branch, Draft PR, test, backup, rollback, dan expected head SHA.
- [ ] Pastikan maintenance release memperbarui changelog dan release notes.
- [ ] Pastikan paket rilis, checksum, smoke test, go-live gate, dan regresi tetap digunakan.
- [ ] Pastikan tag/release/deployment hanya dilakukan setelah keputusan pemilik.

## Regresi dan CI

- [ ] Validasi sintaks YAML issue form.
- [ ] Jalankan syntax check PHP dan Laravel Pint.
- [ ] Jalankan migration SQL paten pada MySQL 8.4.
- [ ] Verifikasi 71 base table, 3 view, dan 98 permission.
- [ ] Jalankan regresi Fase 12.
- [ ] Jalankan full regression suite.
- [ ] Pastikan UBold dan Nunito tetap lokal.
- [ ] Pastikan audit larangan auto-merge berhasil.

## Gate akhir

Fase 13 hanya boleh di-merge setelah seluruh CI hijau, checklist operasional diterima, dan pemilik menyatakan eksplisit:

```text
Fase 13 lulus
```
