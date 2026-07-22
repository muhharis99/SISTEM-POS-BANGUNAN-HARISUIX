# Fase 2 — Ringkasan Implementasi Autentikasi, Organisasi, Role, dan Permission

## Status

**FASE 2 LULUS. Pemilik menyatakan eksplisit `Fase 2 lulus` pada 22 Juli 2026 setelah implementasi, CI, dan checklist manual dinyatakan diterima.**

- Branch: `fase-2-autentikasi-organisasi-rbac`
- Pull request: PR #3
- Target: `main`
- Status kelulusan: lulus
- Implementasi Fase 3: belum dimulai

## Prinsip utama

Fase 2 menggunakan tabel dan kolom yang sudah tersedia pada SQL paten. Tidak ada perubahan terhadap 70 tabel bisnis, 3 view, tipe kolom, index, atau foreign key.

Keputusan teknis yang diterapkan:

1. Login menggunakan kolom `pengguna.nama_pengguna`.
2. Kata sandi disimpan pada `pengguna.kata_sandi` menggunakan hash Laravel.
3. Autentikasi menggunakan session; tidak ada persistent remember-token karena tabel `pengguna` tidak memiliki kolom `remember_token`.
4. Session, cache, queue, dan password-reset tidak menggunakan database.
5. Satu pengguna dapat memiliki lebih dari satu role dan cabang melalui `pengguna_peran`.
6. Hak akses diberikan melalui `peran_hak_akses`; tidak ada permission langsung ke pengguna karena tabel tersebut tidak tersedia.
7. Cabang aktif disimpan dalam session dan selalu divalidasi terhadap assignment pengguna.
8. Role `ADMINISTRATOR` bersifat global dan dapat memilih seluruh cabang aktif.
9. Semua aksi keamanan dan pengelolaan akses dicatat pada `log_aktivitas`.
10. Soft-delete pada relasi diaktifkan kembali melalui update, bukan membuat baris duplikat yang melanggar unique key.

## Autentikasi

Fitur yang tersedia:

- halaman masuk berbasis UBold;
- login dengan `nama_pengguna` dan kata sandi;
- regenerasi session setelah login;
- logout, invalidasi session, dan regenerasi token CSRF;
- penolakan akun nonaktif atau soft-deleted;
- pencatatan `terakhir_masuk`;
- reset `percobaan_masuk` setelah login berhasil;
- lockout 15 menit setelah 5 percobaan kata sandi salah;
- pesan login dibuat umum agar tidak membocorkan keberadaan akun;
- guest yang membuka halaman terlindungi diarahkan ke `/masuk`.

## Organisasi dan cabang aktif

Middleware cabang melakukan hal berikut:

- mengambil daftar cabang yang benar-benar dapat diakses pengguna;
- menolak pengguna yang belum memiliki cabang aktif;
- memilih cabang pertama secara otomatis jika session belum memiliki cabang;
- memvalidasi ulang cabang session pada setiap request;
- menyediakan cabang aktif dan daftar cabang ke layout UBold;
- mencatat perubahan cabang pada audit.

Administrator memperoleh daftar seluruh cabang aktif. Role lain hanya memperoleh cabang yang masih aktif dan ditugaskan melalui `pengguna_peran`.

## Role dan permission

Role awal tetap mengikuti SQL paten:

- `ADMINISTRATOR`
- `PEMILIK`
- `KASIR`
- `GUDANG`
- `PEMBELIAN`
- `PENJUALAN`
- `KEUANGAN`

Katalog 13 hak akses Fase 2:

| Kode | Fungsi |
|---|---|
| `DASHBOARD_LIHAT` | Melihat dashboard |
| `CABANG_PILIH` | Memilih cabang aktif |
| `PENGGUNA_LIHAT` | Melihat pengguna |
| `PENGGUNA_BUAT` | Menambah pengguna |
| `PENGGUNA_UBAH` | Mengubah pengguna |
| `PENGGUNA_UBAH_STATUS` | Mengaktifkan atau menonaktifkan pengguna |
| `PENGGUNA_RESET_KATA_SANDI` | Mereset kata sandi pengguna |
| `PERAN_LIHAT` | Melihat role dan permission |
| `PERAN_BUAT` | Menambah role |
| `PERAN_UBAH` | Mengubah role dan permission |
| `PERAN_UBAH_STATUS` | Mengaktifkan atau menonaktifkan role |
| `AUDIT_LIHAT` | Melihat log aktivitas |
| `PROFIL_UBAH_KATA_SANDI` | Mengubah kata sandi sendiri |

Matriks awal:

- `ADMINISTRATOR`: seluruh permission Fase 2;
- `PEMILIK`: dashboard, cabang, profil, lihat pengguna, lihat role, dan audit;
- role operasional lain: dashboard, cabang, dan ubah kata sandi sendiri.

Matriks dapat diubah melalui halaman manajemen role oleh pengguna yang memiliki permission terkait.

## Perlindungan server-side

Menu sidebar hanya ditampilkan jika pengguna memiliki permission, tetapi keamanan tidak bergantung pada penyembunyian menu. Semua route sensitif dilindungi middleware `hak.akses` sehingga akses URL langsung tanpa permission menghasilkan HTTP 403.

Proteksi tambahan:

- akun yang sedang dipakai tidak dapat dinonaktifkan sendiri;
- role sistem tidak dapat dihapus secara fisik;
- role Administrator dijaga agar akses penuh tidak terputus;
- assignment role-cabang divalidasi terhadap data aktif;
- reset kata sandi tidak pernah mencatat nilai kata sandi ke audit;
- audit menyimpan pengguna, cabang, waktu, modul, aktivitas, referensi, IP, dan user-agent.

## Antarmuka UBold

Halaman yang tersedia:

- login;
- dashboard Fase 2;
- manajemen pengguna;
- tambah/edit pengguna menggunakan modal pada view yang sama;
- aktivasi/nonaktivasi pengguna;
- reset kata sandi pengguna;
- manajemen role dan permission;
- profil dan ubah kata sandi sendiri;
- log audit;
- pemilih cabang aktif pada topbar;
- sidebar dinamis berdasarkan permission.

Seluruh asset tetap lokal dan tidak memakai CDN eksternal.

## Instalasi pada database development kosong

```bash
composer install
cp .env.example .env
php artisan key:generate
php scripts/salin-aset-template.php
```

Atur koneksi MySQL pada `.env`, kemudian jalankan:

```bash
php artisan migrate
php artisan skema:verifikasi --rinci
```

Siapkan katalog permission dan administrator awal:

```bash
php artisan fase2:siapkan \
  --nama-pengguna=administrator \
  --kata-sandi='GANTI_DENGAN_KATA_SANDI_KUAT' \
  --nama-tampilan='Administrator'
```

Kata sandi minimal 8 karakter serta harus mengandung huruf besar, huruf kecil, dan angka. Gunakan kata sandi unik dan jangan menyimpan kata sandi produksi di repository.

Command `fase2:siapkan` aman dijalankan ulang karena memakai update-or-create/update-or-insert pada data katalog dan assignment awal.

## Pengujian otomatis

Workflow MySQL 8.4 memeriksa:

- Composer dependency;
- sintaks seluruh file PHP;
- format Pint;
- `php artisan migrate --force` menggunakan migration SQL paten;
- tepat 71 base table: 70 tabel bisnis + tabel internal `migrations`;
- tepat 3 view;
- tidak ada tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, atau `password_reset_tokens`;
- tepat 13 permission aktif Fase 2;
- verifikasi skema penuh terhadap SQL final;
- login Administrator;
- penolakan pengguna nonaktif;
- lockout setelah lima kegagalan;
- penolakan URL langsung untuk Kasir;
- akses manajemen bagi Administrator;
- regression test Fase 1;
- asset UBold dan Nunito lokal;
- tidak ada workflow komentar/ChatOps yang melakukan merge otomatis.

## Checkpoint kelulusan

Pemilik telah menyelesaikan checkpoint manual dan menyatakan eksplisit:

```text
Fase 2 lulus
```

Dengan keputusan tersebut:

- PR #3 boleh digabung ke `main` setelah CI pada commit checkpoint berhasil;
- tag `fase-2-selesai` boleh dibuat pada commit hasil merge;
- Fase 3 tetap belum dimulai sampai ada perintah terpisah dari pemilik.
