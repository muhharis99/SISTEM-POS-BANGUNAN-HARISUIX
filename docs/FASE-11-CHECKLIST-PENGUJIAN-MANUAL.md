# Fase 11 — Checklist Pengujian Manual

Status: **belum diterima pemilik**.

## Persiapan

- [ ] Pastikan Fase 1 sampai Fase 10 sudah berada pada `main`.
- [ ] Pastikan branch aktif `fase-11-uat-release-candidate-serah-terima`.
- [ ] Pastikan database staging terpisah dari produksi.
- [ ] Pastikan tetap 71 base table, 3 view, dan 98 permission aktif.
- [ ] Pastikan tidak ada migration bisnis atau perubahan SQL paten.
- [ ] Siapkan akun Administrator, Pemilik, Kasir, Gudang, Pembelian, Penjualan, dan Keuangan.
- [ ] Siapkan data uji dua cabang tanpa data sensitif nyata.

## Pusat bantuan

- [ ] Pengguna belum login diarahkan ke halaman masuk.
- [ ] Pengguna login tanpa cabang aktif diminta memilih cabang.
- [ ] Pusat Bantuan tampil pada sidebar Sistem.
- [ ] Halaman menggunakan layout UBold lokal.
- [ ] Panduan dasar dan dukungan tampil untuk semua pengguna.
- [ ] Kasir melihat panduan penjualan.
- [ ] Kasir tanpa izin administrasi tidak melihat panduan pengguna/peran.
- [ ] Administrator melihat seluruh panduan yang relevan.
- [ ] Tombol Buka Modul menuju route yang benar.
- [ ] Pencarian menyaring judul, ringkasan, dan langkah tanpa permintaan eksternal.
- [ ] Aset dan ikon tetap lokal.

## Panduan pengguna

- [ ] Tinjau `docs/PANDUAN-PENGGUNA.md` bersama penanggung jawab tiap fungsi.
- [ ] Pastikan istilah sesuai proses toko.
- [ ] Pastikan alur master, persediaan, pembelian, penjualan, pengiriman, keuangan, laporan, lampiran, dan audit lengkap.
- [ ] Pastikan prosedur pelaporan masalah tidak meminta kata sandi atau rahasia.
- [ ] Pastikan panduan penutupan harian dapat dijalankan.

## Manifest release candidate

- [ ] Jalankan `php artisan sistem:buat-manifest-rilis v1.0.0-rc1`.
- [ ] Pastikan berkas tersimpan pada `storage/app/release-candidate`.
- [ ] Pastikan permission berkas privat.
- [ ] Pastikan versi dan commit benar.
- [ ] Pastikan base table 71, view 3, permission 98, dan tabel terlarang 0.
- [ ] Pastikan checksum seluruh berkas kritis tersedia.
- [ ] Pastikan manifest tidak memuat `APP_KEY`, password, username database, atau data transaksi.
- [ ] Jalankan `php artisan sistem:verifikasi-manifest-rilis` dan pastikan berhasil.
- [ ] Ubah satu checksum pada salinan manifest dan pastikan verifikasi gagal.
- [ ] Ubah satu berkas kritis setelah manifest dibuat dan pastikan verifikasi gagal.
- [ ] Uji nama berkas tidak aman dan pastikan ditolak.
- [ ] Uji versi bukan semver dan pastikan ditolak.

## Changelog dan release notes

- [ ] Tinjau `CHANGELOG.md`.
- [ ] Tinjau `docs/RELEASE-NOTES-v1.0.0-rc1.md`.
- [ ] Pastikan seluruh modul Fase 1 sampai Fase 10 tercakup.
- [ ] Pastikan batasan kandidat rilis dijelaskan.
- [ ] Pastikan tidak ada klaim deployment produksi nyata.
- [ ] Pastikan tag/GitHub Release final belum dibuat.

## UAT lintas peran

- [ ] Jalankan seluruh skenario pada `docs/UAT-RELEASE-CANDIDATE.md`.
- [ ] Simpan pelaksana, tanggal, data uji, hasil, bukti, dan temuan.
- [ ] Uji isolasi cabang pada URL, laporan, ekspor, dan lampiran.
- [ ] Uji akses langsung tanpa permission.
- [ ] Uji transaksi banyak item.
- [ ] Uji stok, retur, pembatalan, hutang, piutang, dan pembayaran parsial.
- [ ] Uji jurnal seimbang dan tidak seimbang.
- [ ] Uji nota 80 mm dan CSV UTF-8.
- [ ] Uji backup, restore, deployment, dan rollback pada staging.
- [ ] Pastikan tidak ada temuan KRITIS atau TINGGI terbuka.

## Serah-terima operasional

- [ ] Tinjau `docs/SERAH-TERIMA-OPERASIONAL.md`.
- [ ] Tetapkan pemilik, penanggung jawab operasional, keuangan, gudang, dan IT.
- [ ] Pastikan seluruh artefak serah-terima tersedia.
- [ ] Jadwalkan pelatihan per peran.
- [ ] Tetapkan jalur dukungan dan eskalasi.
- [ ] Tetapkan lokasi bukti UAT dan backup off-server.
- [ ] Tetapkan jadwal go-live dan rollback.
- [ ] Pastikan kredensial produksi tidak diserahkan melalui repository atau dokumen biasa.

## Regresi dan CI

- [ ] Jalankan syntax check dan Laravel Pint.
- [ ] Jalankan migration SQL paten pada MySQL 8.4.
- [ ] Jalankan integration test Fase 11.
- [ ] Jalankan regression Fase 10 dan Fase 9.
- [ ] Jalankan regression fase sebelumnya.
- [ ] Jalankan full regression suite.
- [ ] Pastikan aset UBold dan Nunito tetap lokal.
- [ ] Pastikan audit larangan auto-merge berhasil.
- [ ] Pastikan tidak ada tabel, view, atau permission baru.

## Gate akhir

Fase 11 hanya boleh di-merge setelah seluruh CI hijau, checklist UAT diterima, dan pemilik menyatakan eksplisit:

```text
Fase 11 lulus
```

Tag atau GitHub Release final tetap membutuhkan keputusan terpisah setelah merge Fase 11.