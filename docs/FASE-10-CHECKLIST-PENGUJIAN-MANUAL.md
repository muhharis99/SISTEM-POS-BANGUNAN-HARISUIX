# Fase 10 — Checklist Pengujian Manual

Status: **belum diterima pemilik**.

## Persiapan

- [ ] Pastikan Fase 1 sampai Fase 9 sudah berada pada `main`.
- [ ] Pastikan branch aktif `fase-10-kesiapan-produksi-deployment`.
- [ ] Pastikan tidak ada kredensial produksi nyata dalam repository.
- [ ] Pastikan tetap 71 base table, 3 view, dan 98 permission aktif.
- [ ] Pastikan tidak ada migration bisnis atau perubahan SQL paten.
- [ ] Pastikan server menyediakan PHP 8.4, Composer, MySQL client, `git`, `tar`, dan `flock`.

## Environment dan hardening

- [ ] Salin `.env.production.example` menjadi `.env` di direktori shared server.
- [ ] Pastikan `APP_ENV=production`.
- [ ] Pastikan `APP_DEBUG=false`.
- [ ] Pastikan `APP_URL` memakai HTTPS.
- [ ] Pastikan `APP_KEY` valid dan tidak diganti sembarangan.
- [ ] Pastikan `SESSION_DRIVER=file`.
- [ ] Pastikan `CACHE_STORE=file`.
- [ ] Pastikan `QUEUE_CONNECTION=sync`.
- [ ] Pastikan cookie session secure, HTTP-only, dan encrypted.
- [ ] Pastikan `TRUSTED_PROXIES` kosong atau berisi proxy yang benar-benar dipercaya.
- [ ] Pastikan tidak ada wildcard proxy `*`.
- [ ] Pastikan `.env` berizin `0600` dan tidak dapat diakses dari web.

## Pemeriksaan produksi

- [ ] Jalankan `php artisan sistem:periksa-produksi`.
- [ ] Jalankan `php artisan optimize`.
- [ ] Jalankan `php artisan sistem:periksa-produksi --ketat`.
- [ ] Pastikan seluruh pemeriksaan berstatus BERHASIL.
- [ ] Uji `--json` dan pastikan format valid.
- [ ] Matikan koneksi database uji sementara dan pastikan command gagal.
- [ ] Ubah `APP_DEBUG=true` pada environment uji dan pastikan pemeriksaan konfigurasi gagal.
- [ ] Ubah `APP_URL` menjadi HTTP pada environment uji dan pastikan pemeriksaan gagal.
- [ ] Pastikan storage dan bootstrap cache tidak writable pada environment uji lalu pastikan pemeriksaan gagal.

## Endpoint kesehatan

- [ ] Akses `/up` dan pastikan HTTP 200.
- [ ] Akses `/kesiapan` dan pastikan HTTP 200 saat infrastruktur sehat.
- [ ] Pastikan response `/kesiapan` memiliki `Cache-Control: no-store`.
- [ ] Pastikan response tidak memuat nama database.
- [ ] Pastikan response tidak memuat username atau password database.
- [ ] Pastikan response tidak memuat path server.
- [ ] Pastikan response tidak memuat exception internal.
- [ ] Putuskan database uji dan pastikan `/kesiapan` mengembalikan HTTP 503.
- [ ] Pastikan rate limit endpoint bekerja.

## Backup database

- [ ] Jalankan mode simulasi backup.
- [ ] Jalankan backup nyata ke direktori privat.
- [ ] Pastikan berkas `.sql.gz` terbentuk.
- [ ] Pastikan berkas `.sha256` terbentuk.
- [ ] Jalankan `gzip -t` dan pastikan berhasil.
- [ ] Jalankan `sha256sum -c` dan pastikan cocok.
- [ ] Pastikan permission backup `0600`.
- [ ] Pastikan kata sandi database tidak terlihat pada daftar proses.
- [ ] Uji retensi dengan berkas backup lama.
- [ ] Uji direktori tidak writable dan pastikan command gagal aman.
- [ ] Uji ketika `mysqldump` tidak tersedia dan pastikan pesan jelas.
- [ ] Pastikan backup disalin ke media/lokasi di luar server utama.

## Restore database

- [ ] Jalankan mode simulasi restore dengan backup valid.
- [ ] Uji konfirmasi selain `RESTORE` dan pastikan ditolak.
- [ ] Uji restore saat aplikasi aktif dan pastikan ditolak.
- [ ] Aktifkan maintenance mode.
- [ ] Restore backup ke database uji terpisah.
- [ ] Pastikan backup keselamatan dibuat sebelum restore nyata.
- [ ] Pastikan checksum salah menyebabkan restore ditolak.
- [ ] Pastikan `.sql` biasa dapat dipulihkan.
- [ ] Pastikan `.sql.gz` dapat dipulihkan.
- [ ] Pastikan hasil restore memiliki 71 base table dan 3 view.
- [ ] Jalankan `skema:verifikasi --rinci` pada database hasil restore.
- [ ] Periksa stok, penjualan, hutang, piutang, kas, jurnal, dan lampiran pada database uji.
- [ ] Pastikan aplikasi kembali aktif setelah prosedur selesai.

## Deployment dry-run

- [ ] Jalankan `bash -n scripts/deploy-production.sh`.
- [ ] Jalankan `DEPLOY_DRY_RUN=1`.
- [ ] Pastikan repository kotor ditolak.
- [ ] Pastikan `.env` shared yang hilang menyebabkan penolakan.
- [ ] Pastikan program wajib yang hilang menyebabkan penolakan.
- [ ] Pastikan dua deployment bersamaan ditolak oleh `flock`.

## Deployment release nyata pada staging

- [ ] Siapkan struktur `releases`, `shared`, dan `current`.
- [ ] Pastikan `.env` dan storage berada pada `shared`.
- [ ] Jalankan deployment staging.
- [ ] Pastikan release dibuat dari commit yang benar.
- [ ] Pastikan `current` menunjuk release baru.
- [ ] Pastikan storage tetap sama antarrelease.
- [ ] Pastikan backup dibuat sebelum migration.
- [ ] Pastikan migration dan pemeriksaan ketat berhasil.
- [ ] Pastikan aplikasi maintenance selama pergantian.
- [ ] Pastikan aplikasi aktif kembali setelah sukses.
- [ ] Pastikan release lama dibersihkan sesuai `RELEASE_KEEP`.
- [ ] Simulasikan kegagalan sebelum symlink dan pastikan release lama aktif kembali.
- [ ] Pastikan deployment tidak pernah dijalankan otomatis oleh repository.

## Rollback aplikasi

- [ ] Jalankan `bash -n scripts/rollback-production.sh`.
- [ ] Jalankan mode dry-run.
- [ ] Uji release target tidak ditemukan dan pastikan ditolak.
- [ ] Uji target di luar direktori releases dan pastikan ditolak.
- [ ] Jalankan rollback staging ke release sebelumnya.
- [ ] Pastikan backup keselamatan dibuat.
- [ ] Pastikan symlink `current` berpindah atomik.
- [ ] Pastikan aplikasi aktif kembali.
- [ ] Simulasikan kegagalan rollback dan pastikan current dikembalikan.
- [ ] Pastikan database tidak di-rollback otomatis.

## Nginx dan PHP-FPM

- [ ] Sesuaikan domain dan sertifikat pada konfigurasi Nginx.
- [ ] Jalankan `nginx -t`.
- [ ] Sesuaikan path PHP 8.4 dan socket.
- [ ] Jalankan `php-fpm8.4 -t`.
- [ ] Pastikan root Nginx menunjuk `current/public`.
- [ ] Pastikan `.env`, Git, composer, artisan, dan log tidak dapat diakses dari web.
- [ ] Pastikan HTTP dialihkan ke HTTPS.
- [ ] Pastikan batas upload sesuai kebutuhan lampiran.
- [ ] Pastikan header keamanan tampil.
- [ ] Uji static asset UBold dan Nunito lokal.
- [ ] Uji login, session, upload, download, cetak nota, dan laporan.

## Systemd backup timer

- [ ] Salin service dan timer ke `/etc/systemd/system`.
- [ ] Jalankan `systemctl daemon-reload`.
- [ ] Aktifkan timer.
- [ ] Pastikan jadwal terlihat pada `systemctl list-timers`.
- [ ] Jalankan service secara manual.
- [ ] Periksa journal dan exit code.
- [ ] Pastikan backup tersimpan pada direktori privat.
- [ ] Pastikan user `www-data` dapat menulis backup dan storage.

## Regresi aplikasi

- [ ] Jalankan test Fase 10.
- [ ] Jalankan regression Fase 9.
- [ ] Jalankan regression Fase 2 sampai Fase 8.
- [ ] Jalankan full regression suite.
- [ ] Pastikan dashboard dan laporan tetap benar.
- [ ] Pastikan lampiran privat tetap dapat diakses sesuai permission.
- [ ] Pastikan seluruh modul transaksi tidak berubah perilakunya.
- [ ] Pastikan total permission tetap 98.
- [ ] Pastikan tidak ada tabel baru.
- [ ] Pastikan auto-merge tidak digunakan.

## Gate akhir

Fase 10 hanya boleh di-merge setelah seluruh CI hijau, checklist ini diterima, dan pemilik menyatakan eksplisit:

```text
Fase 10 lulus
```
