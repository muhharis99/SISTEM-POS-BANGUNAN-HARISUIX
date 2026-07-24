# Fase 10 — Checklist Pengujian Manual

Status: **diterima pemilik pada 24 Juli 2026**.

## Catatan penerimaan

Pemilik telah menyatakan eksplisit `Fase 10 lulus`. Penerimaan ini memenuhi gate proyek untuk merge. Pengujian otomatis membuktikan dry-run deployment/rollback, backup nyata, checksum, restore ke database kedua, verifikasi skema, pemeriksaan produksi ketat, dan regresi aplikasi.

Pengujian yang membutuhkan server staging atau produksi nyata tetap menjadi runbook operasional saat infrastruktur tersedia dan tidak diklaim telah dijalankan oleh repository.

## Persiapan server

- [ ] Pastikan PHP 8.4, Composer, MySQL client, `git`, `tar`, dan `flock` tersedia.
- [ ] Pastikan tidak ada kredensial produksi nyata dalam repository.
- [ ] Pastikan tetap 71 base table, 3 view, dan 98 permission aktif.
- [ ] Pastikan tidak ada migration bisnis atau perubahan SQL paten.

## Environment dan hardening

- [ ] Salin `.env.production.example` menjadi `.env` pada direktori shared server.
- [ ] Atur `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL` HTTPS.
- [ ] Gunakan `SESSION_DRIVER=file`, `CACHE_STORE=file`, dan `QUEUE_CONNECTION=sync`.
- [ ] Pastikan cookie session secure, HTTP-only, dan encrypted.
- [ ] Isi `TRUSTED_PROXIES` hanya dengan proxy yang benar-benar dipercaya.
- [ ] Pastikan `.env` berizin `0600` dan tidak dapat diakses dari web.

## Pemeriksaan produksi

- [ ] Jalankan `php artisan optimize`.
- [ ] Jalankan `php artisan sistem:periksa-produksi --ketat`.
- [ ] Pastikan seluruh pemeriksaan kritis berstatus BERHASIL.
- [ ] Akses `/up` dan `/kesiapan` melalui HTTPS.
- [ ] Pastikan `/kesiapan` tidak memuat database, kredensial, path, atau exception internal.

## Backup dan restore

- [ ] Jalankan backup nyata ke direktori privat.
- [ ] Verifikasi `.sql.gz`, `.sha256`, `gzip -t`, dan `sha256sum -c`.
- [ ] Salin backup ke media atau lokasi di luar server utama.
- [ ] Uji restore pada database staging terpisah.
- [ ] Pastikan hasil restore memiliki 71 base table dan 3 view.
- [ ] Jalankan `php artisan skema:verifikasi --rinci` pada hasil restore.
- [ ] Pastikan restore produksi hanya dilakukan dengan keputusan dan maintenance mode.

## Deployment staging

- [ ] Sesuaikan konfigurasi Nginx, PHP-FPM, domain, sertifikat, dan socket.
- [ ] Jalankan `nginx -t` dan `php-fpm8.4 -t`.
- [ ] Jalankan deployment staging menggunakan struktur `releases`, `shared`, dan `current`.
- [ ] Pastikan backup dibuat sebelum migration.
- [ ] Pastikan symlink `current` berpindah atomik.
- [ ] Uji login, transaksi, lampiran, laporan, ekspor, dan cetak nota.
- [ ] Uji rollback ke release sebelumnya.
- [ ] Pastikan rollback aplikasi tidak melakukan rollback database otomatis.

## Backup terjadwal

- [ ] Pasang service dan timer systemd.
- [ ] Jalankan `systemctl daemon-reload` dan aktifkan timer.
- [ ] Jalankan service secara manual dan periksa journal.
- [ ] Pastikan retensi serta salinan off-server bekerja.

## Bukti otomatis yang sudah berhasil

- [x] Sintaks Bash deployment dan rollback.
- [x] Dry-run deployment dan rollback.
- [x] Sintaks PHP dan Laravel Pint.
- [x] Migration SQL paten pada MySQL 8.4.
- [x] Verifikasi 71 base table, 3 view, dan 98 permission.
- [x] Integration test Fase 10.
- [x] Endpoint readiness tanpa kebocoran data sensitif.
- [x] Backup `.sql.gz` nyata dan checksum SHA-256.
- [x] Restore ke database kedua.
- [x] Verifikasi skema hasil restore.
- [x] Pemeriksaan produksi mode ketat.
- [x] Regresi fase sebelumnya dan full suite.
- [x] Audit larangan auto-merge.

## Gate akhir

- [x] Seluruh CI pada checkpoint teknis hijau.
- [x] Checklist diterima pemilik.
- [x] Pemilik menyatakan eksplisit `Fase 10 lulus`.
- [ ] PR #13 digabung manual dengan expected head SHA terkunci.