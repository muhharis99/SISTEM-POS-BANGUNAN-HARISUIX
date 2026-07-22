# Fase 2 — Checklist Pengujian Manual

## Status

**DITERIMA PEMILIK — FASE 2 LULUS pada 22 Juli 2026.**

Dokumen ini menjadi arsip checklist pengujian manual Fase 2. Pemilik telah menyatakan eksplisit `Fase 2 lulus` setelah menerima hasil implementasi, pengujian otomatis, dan pengujian manual.

Gunakan database development kosong atau backup database uji. Jangan menjalankan pengujian pertama pada database produksi.

## A. Persiapan

- [x] Checkout branch `fase-2-autentikasi-organisasi-rbac`.
- [x] Jalankan `composer install` tanpa error.
- [x] Salin `.env.example` menjadi `.env`.
- [x] Konfigurasikan koneksi MySQL development.
- [x] Jalankan `php artisan key:generate`.
- [x] Jalankan `php scripts/salin-aset-template.php`.
- [x] Jalankan `php artisan migrate` pada database kosong.
- [x] Pastikan tabel bisnis berjumlah 70, tabel internal Laravel hanya `migrations`, dan view berjumlah 3.
- [x] Jalankan `php artisan skema:verifikasi --rinci` dan pastikan hasil sesuai.
- [x] Pastikan tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, dan `password_reset_tokens` tidak dibuat.
- [x] Jalankan `php artisan fase2:siapkan` dengan kredensial administrator yang hanya diketahui pemilik.
- [x] Buat backup database setelah baseline dan data akses awal berhasil dibuat.

## B. Halaman login

- [x] Membuka `/` sebagai guest mengarah ke `/dashboard`, lalu diteruskan ke `/masuk`.
- [x] Membuka `/dashboard` langsung sebagai guest mengarah ke `/masuk`, bukan error 500.
- [x] Tampilan login memakai UBold dan asset lokal.
- [x] Browser Network tidak memuat CDN eksternal.
- [x] Login dengan nama pengguna yang tidak ada ditolak dengan pesan umum.
- [x] Login dengan kata sandi salah ditolak.
- [x] Login dengan akun nonaktif ditolak.
- [x] Login dengan akun soft-deleted ditolak.
- [x] Login Administrator dengan kredensial benar berhasil.
- [x] Session ID berubah setelah login berhasil.
- [x] Kolom `terakhir_masuk` terisi setelah login berhasil.
- [x] Logout berhasil dan halaman terlindungi tidak dapat dibuka memakai tombol Back tanpa autentikasi ulang.

## C. Lockout keamanan

Gunakan akun uji, bukan satu-satunya Administrator.

- [x] Masukkan kata sandi salah empat kali; akun masih dapat mencoba login.
- [x] Masukkan kata sandi salah untuk kelima kalinya; akun dikunci 15 menit.
- [x] Login dengan kata sandi benar selama masa lockout tetap ditolak.
- [x] Kolom `dikunci_sampai` terisi dengan waktu yang benar.
- [x] Setelah masa lockout selesai atau Administrator mereset kata sandi, akun dapat login kembali.
- [x] `percobaan_masuk` kembali nol setelah login berhasil.
- [x] Aktivitas login gagal dan berhasil muncul pada log audit tanpa menyimpan kata sandi.

## D. Cabang aktif dan isolasi organisasi

Siapkan minimal dua cabang aktif dan satu cabang nonaktif.

- [x] Administrator dapat melihat seluruh cabang aktif.
- [x] Cabang nonaktif tidak muncul pada pemilih cabang.
- [x] Pengguna operasional hanya melihat cabang yang ditugaskan kepadanya.
- [x] Pengguna tanpa cabang aktif menerima HTTP 403 dengan pesan yang sesuai.
- [x] Cabang pertama dipilih otomatis ketika session belum memiliki cabang aktif.
- [x] Mengganti cabang melalui topbar memperbarui nama cabang aktif.
- [x] Manipulasi `id_cabang` melalui request tidak dapat memilih cabang di luar assignment pengguna.
- [x] Cabang session yang dinonaktifkan otomatis diganti dengan cabang valid berikutnya.
- [x] Perubahan cabang tercatat pada log audit.

## E. Role dan permission

- [x] Role awal tersedia: Administrator, Pemilik, Kasir, Gudang, Pembelian, Penjualan, dan Keuangan.
- [x] Katalog permission aktif berjumlah 13.
- [x] Administrator dapat membuka seluruh menu Fase 2.
- [x] Pemilik hanya melihat menu yang diberikan pada matriks awal.
- [x] Kasir tidak melihat menu manajemen pengguna, role, atau audit.
- [x] Kasir yang mengetik URL `/pengguna` langsung menerima HTTP 403.
- [x] Kasir yang mengetik URL `/peran` langsung menerima HTTP 403.
- [x] Kasir yang mengetik URL `/audit` langsung menerima HTTP 403.
- [x] Perubahan permission role langsung memengaruhi sidebar setelah request berikutnya.
- [x] Perubahan permission juga memengaruhi akses URL langsung, bukan hanya tampilan menu.
- [x] Role Administrator tetap memiliki akses penuh.
- [x] Assignment global dengan `id_cabang = NULL` berfungsi sesuai desain.
- [x] Assignment role-cabang yang pernah soft-deleted dapat diaktifkan kembali tanpa duplicate-key error.

## F. Manajemen pengguna

- [x] Daftar pengguna dapat dicari berdasarkan nama pengguna, nama tampilan, atau surel.
- [x] Pagination berjalan tanpa kehilangan parameter pencarian.
- [x] Tambah pengguna dibuka melalui modal pada halaman yang sama.
- [x] Nama pengguna duplikat ditolak.
- [x] Kata sandi lemah ditolak.
- [x] Pengguna baru wajib memiliki minimal satu assignment role.
- [x] Role dan cabang yang tidak valid ditolak oleh validasi server.
- [x] Edit pengguna dibuka melalui modal pada halaman yang sama.
- [x] Edit assignment mengganti assignment aktif tanpa menghapus riwayat lama secara fisik.
- [x] Pengguna dapat diberi lebih dari satu role dan lebih dari satu cabang.
- [x] Administrator dapat menonaktifkan pengguna lain.
- [x] Pengguna yang sedang login tidak dapat menonaktifkan dirinya sendiri.
- [x] Reset kata sandi administratif berhasil.
- [x] Setelah reset kata sandi, lockout akun dibersihkan.
- [x] Kata sandi baru tidak tampil pada log audit atau respons HTML.

## G. Manajemen role dan permission

- [x] Daftar role menampilkan status aktif dan jumlah permission.
- [x] Tambah role melalui modal berhasil.
- [x] Kode role duplikat ditolak.
- [x] Edit nama, keterangan, dan daftar permission berhasil.
- [x] Menonaktifkan role non-sistem berhasil.
- [x] Role nonaktif tidak dapat memberikan akses baru.
- [x] Role sistem yang dilindungi tidak dapat dirusak melalui request langsung.
- [x] Relasi permission yang pernah soft-deleted dapat diaktifkan kembali tanpa duplicate-key error.
- [x] Semua perubahan role dan permission tercatat pada audit.

## H. Profil dan kata sandi sendiri

- [x] Pengguna dapat membuka profilnya sendiri.
- [x] Perubahan kata sandi mewajibkan kata sandi saat ini yang benar.
- [x] Konfirmasi kata sandi baru wajib cocok.
- [x] Kata sandi baru harus memenuhi kebijakan minimum.
- [x] Setelah berubah, kata sandi lama tidak dapat digunakan.
- [x] Kata sandi baru dapat digunakan untuk login.
- [x] Nilai kata sandi tidak tercatat pada audit.

## I. Log audit

- [x] Login berhasil tercatat.
- [x] Login gagal tercatat.
- [x] Logout tercatat.
- [x] Perubahan cabang tercatat.
- [x] Tambah/edit/status/reset pengguna tercatat.
- [x] Tambah/edit/status role dan permission tercatat.
- [x] Ubah kata sandi sendiri tercatat tanpa nilai rahasia.
- [x] Log menampilkan waktu, pengguna, cabang, modul, aktivitas, IP, dan user-agent.
- [x] Pengguna tanpa `AUDIT_LIHAT` tidak dapat membuka log audit.
- [x] Filter/pagination audit tetap konsisten.

## J. Tampilan UBold

- [x] Login tampil baik pada desktop.
- [x] Login tampil baik pada viewport mobile.
- [x] Dashboard tampil baik pada desktop dan mobile.
- [x] Sidebar dapat dibuka, ditutup, dan diperkecil.
- [x] Topbar menampilkan pengguna dan cabang aktif yang benar.
- [x] Modal tambah/edit tidak terpotong pada layar kecil.
- [x] Tabel pengguna, role, dan audit dapat di-scroll pada mobile.
- [x] Tidak ada teks sisa `Fase 1` atau `Autentikasi belum tersedia` pada halaman aktif.
- [x] Tidak ada request `/css2*` 404.
- [x] Nunito lokal termuat.
- [x] Console browser tidak menampilkan error JavaScript yang memblokir fungsi.

## K. Regresi dan database

- [x] `php artisan test` berhasil.
- [x] `vendor/bin/pint --test` berhasil.
- [x] `php artisan skema:verifikasi --rinci` tetap berhasil setelah pengujian CRUD akses.
- [x] Tidak ada tabel bisnis, kolom, index, foreign key, atau view paten yang berubah.
- [x] Tidak ada tabel infrastruktur Laravel tambahan selain `migrations`.
- [x] Asset UBold dan font lokal tetap lolos pemeriksaan Network.
- [x] Backup database dapat di-restore ke database development lain.
- [x] Setelah restore, login, role, permission, cabang, dan audit masih berfungsi.

## L. Keputusan kelulusan

Fase 2 dinyatakan lulus karena:

- [x] seluruh item kritis autentikasi dan keamanan berhasil;
- [x] seluruh akses URL langsung sesuai permission;
- [x] multi-cabang tervalidasi;
- [x] audit tidak menyimpan data rahasia;
- [x] regression test berhasil;
- [x] backup dan restore berhasil;
- [x] pemilik menyatakan eksplisit `Fase 2 lulus`.

PR #3 boleh digabung setelah CI pada commit checkpoint kelulusan berhasil. Fase 3 tetap belum dimulai sampai ada perintah terpisah dari pemilik.
