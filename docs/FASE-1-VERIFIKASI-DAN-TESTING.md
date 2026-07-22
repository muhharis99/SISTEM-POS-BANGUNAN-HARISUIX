# Fase 1 — Verifikasi dan Testing Manual

Status fase tidak berubah menjadi lulus hanya karena seluruh file sudah ditulis. Pengujian harus dijalankan pada mesin development dan hasilnya dikonfirmasi oleh pemilik proyek.

## A. Prasyarat

Pastikan tersedia:

- PHP 8.4;
- Composer;
- MySQL 8 atau MariaDB yang kompatibel;
- ekstensi PHP `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, dan `fileinfo`;
- Git;
- `mysqldump` untuk backup;
- database development kosong.

Periksa versi:

```bash
php -v
composer --version
mysql --version
git --version
```

## B. Setup branch Fase 1

```bash
git fetch origin
git switch fase-1-fondasi
composer install
cp .env.example .env
php artisan key:generate
```

Pada Windows PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Isi koneksi database di `.env`.

## C. Salin asset UBold

```bash
php scripts/salin-aset-template.php
```

Pastikan tidak ada proses `npm update`, `npm install`, atau pengambilan asset dari CDN. Asset disalin persis dari `template_admin/assets`.

## D. Keputusan wajib sebelum migration

Laravel memakai tabel internal bernama `migrations` untuk mencatat migration yang sudah dijalankan. Tabel tersebut bukan salah satu dari 70 tabel bisnis pada SQL final.

Sebelum menjalankan `php artisan migrate`, pemilik proyek harus memilih salah satu:

### Opsi A — Mengizinkan satu tabel internal `migrations`

- 70 tabel bisnis dan 3 view tetap persis mengikuti SQL final;
- Laravel menambahkan hanya tabel internal `migrations`;
- tabel `cache`, `sessions`, `jobs`, `failed_jobs`, dan tabel autentikasi tambahan tidak dibuat;
- perintah migration dan rollback standar Laravel tetap tersedia.

### Opsi B — Melarang seluruh tabel tambahan tanpa pengecualian

- jangan jalankan `php artisan migrate`;
- baseline SQL harus diaplikasikan melalui runner khusus tanpa migration repository;
- pencatatan versi skema dilakukan melalui Git/tag dan mekanisme custom;
- konsekuensinya workflow migration standar Laravel tidak dapat digunakan secara penuh.

Tidak ada eksekusi migration sampai opsi tersebut dikonfirmasi.

## E. Menjalankan baseline setelah Opsi A disetujui

Pastikan database benar-benar kosong, lalu:

```bash
php artisan migrate
php artisan skema:verifikasi --rinci
```

Hasil yang diharapkan:

```text
Tabel bisnis: 70
View: 3
Skema bisnis sesuai dengan SQL final.
```

Perintah verifikasi memeriksa:

- nama dan jumlah tabel bisnis;
- nama dan jumlah view;
- nama dan urutan kolom;
- tipe data;
- nullable;
- default value;
- `AUTO_INCREMENT`;
- primary key dan index yang dideklarasikan;
- nama, kolom, tujuan, dan aturan hapus foreign key.

## F. Automated smoke test

```bash
php artisan test
```

Yang diuji pada Fase 1:

- `/` mengarah ke `/dashboard`;
- dashboard dapat dirender;
- identitas Laravel 13 dan UBold tampil;
- asset menggunakan path lokal;
- halaman tidak memuat CDN umum.

## G. Menjalankan aplikasi

```bash
php artisan serve
```

Buka:

```text
http://127.0.0.1:8000/dashboard
```

## H. Checklist UI UBold

Periksa secara manual:

- [ ] Dashboard tampil tanpa halaman putih atau error 500.
- [ ] CSS UBold termuat.
- [ ] Topbar tampil.
- [ ] Sidebar tampil.
- [ ] Sidebar dapat dibuka dan diperkecil.
- [ ] Menu collapse dapat dibuka dan ditutup.
- [ ] Ikon Lucide tampil.
- [ ] Dropdown pengguna dapat dibuka.
- [ ] Offcanvas pengaturan tema dapat dibuka.
- [ ] Mode terang/gelap berfungsi sesuai kemampuan template.
- [ ] Tombol layar penuh berfungsi.
- [ ] Tampilan tetap rapi di desktop dan layar kecil.
- [ ] Browser console tidak menampilkan error JavaScript.
- [ ] Network browser tidak memuat asset dari `template_admin_1`.
- [ ] Network browser tidak mengambil Bootstrap, Chart.js, atau ikon dari CDN.

## I. Checklist database

- [ ] Database development sudah dibackup atau benar-benar kosong.
- [ ] Character set database `utf8mb4`.
- [ ] Collation database `utf8mb4_unicode_ci`.
- [ ] Seluruh 70 tabel bisnis tersedia.
- [ ] Ketiga view tersedia.
- [ ] Seeder bawaan dari SQL tersedia.
- [ ] Tidak ada tabel `cache`.
- [ ] Tidak ada tabel `sessions`.
- [ ] Tidak ada tabel `jobs`.
- [ ] Tidak ada tabel `failed_jobs`.
- [ ] Tidak ada tabel Spatie Permission.
- [ ] Tidak ada perubahan nama kolom atau tabel.
- [ ] `php artisan skema:verifikasi --rinci` berhasil tanpa perbedaan.

## J. Uji rollback hanya pada database disposable

Uji rollback tidak boleh dilakukan pada database yang berisi data penting.

```bash
php artisan migrate:rollback
```

Setelah rollback:

- 3 view baseline hilang;
- 70 tabel bisnis baseline hilang;
- tabel internal `migrations` dapat tetap ada sebagai repository Laravel;
- tidak ada kegagalan foreign key saat drop.

Lalu uji ulang:

```bash
php artisan migrate
php artisan skema:verifikasi --rinci
```

## K. Backup setelah pengujian

Linux Mint:

```bash
bash scripts/backup-database.sh
```

Windows/Laragon:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/backup-database.ps1
```

Verifikasi checksum dan lakukan satu uji restore pada database terpisah.

## L. Smoke test regresi untuk fase berikutnya

Sebelum Fase 2 dimulai, ulangi minimal:

```bash
php artisan test
php artisan skema:verifikasi
```

Serta periksa dashboard dan asset UBold secara manual. Smoke test tersebut menjadi baseline regresi untuk seluruh fase berikutnya.

## M. Syarat kelulusan

Fase 1 baru dinyatakan lulus setelah:

1. pilihan tabel internal `migrations` dikonfirmasi;
2. dependency berhasil dipasang;
3. asset berhasil disalin;
4. migration atau runner baseline berhasil;
5. verifikasi skema berhasil;
6. automated test berhasil;
7. checklist UI dan database selesai;
8. backup dan uji restore selesai;
9. pemilik proyek menyatakan secara eksplisit `Fase 1 lulus`.

Sebelum semua syarat dipenuhi, statusnya adalah **siap diuji / belum lulus**.
