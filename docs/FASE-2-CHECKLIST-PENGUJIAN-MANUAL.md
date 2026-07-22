# Fase 2 — Checklist Pengujian Manual

## Status

Dokumen ini digunakan pemilik proyek untuk memvalidasi Fase 2 pada Linux Mint sebelum menyatakan `Fase 2 lulus`.

Gunakan database development kosong atau backup database uji. Jangan menjalankan pengujian pertama pada database produksi.

## A. Persiapan

- [ ] Checkout branch `fase-2-autentikasi-organisasi-rbac`.
- [ ] Jalankan `composer install` tanpa error.
- [ ] Salin `.env.example` menjadi `.env`.
- [ ] Konfigurasikan koneksi MySQL development.
- [ ] Jalankan `php artisan key:generate`.
- [ ] Jalankan `php scripts/salin-aset-template.php`.
- [ ] Jalankan `php artisan migrate` pada database kosong.
- [ ] Pastikan tabel bisnis berjumlah 70, tabel internal Laravel hanya `migrations`, dan view berjumlah 3.
- [ ] Jalankan `php artisan skema:verifikasi --rinci` dan pastikan hasil sesuai.
- [ ] Pastikan tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, dan `password_reset_tokens` tidak dibuat.
- [ ] Jalankan `php artisan fase2:siapkan` dengan kredensial administrator yang hanya diketahui pemilik.
- [ ] Buat backup database setelah baseline dan data akses awal berhasil dibuat.

## B. Halaman login

- [ ] Membuka `/` sebagai guest mengarah ke `/dashboard`, lalu diteruskan ke `/masuk`.
- [ ] Membuka `/dashboard` langsung sebagai guest mengarah ke `/masuk`, bukan error 500.
- [ ] Tampilan login memakai UBold dan asset lokal.
- [ ] Browser Network tidak memuat CDN eksternal.
- [ ] Login dengan nama pengguna yang tidak ada ditolak dengan pesan umum.
- [ ] Login dengan kata sandi salah ditolak.
- [ ] Login dengan akun nonaktif ditolak.
- [ ] Login dengan akun soft-deleted ditolak.
- [ ] Login Administrator dengan kredensial benar berhasil.
- [ ] Session ID berubah setelah login berhasil.
- [ ] Kolom `terakhir_masuk` terisi setelah login berhasil.
- [ ] Logout berhasil dan halaman terlindungi tidak dapat dibuka memakai tombol Back tanpa autentikasi ulang.

## C. Lockout keamanan

Gunakan akun uji, bukan satu-satunya Administrator.

- [ ] Masukkan kata sandi salah empat kali; akun masih dapat mencoba login.
- [ ] Masukkan kata sandi salah untuk kelima kalinya; akun dikunci 15 menit.
- [ ] Login dengan kata sandi benar selama masa lockout tetap ditolak.
- [ ] Kolom `dikunci_sampai` terisi dengan waktu yang benar.
- [ ] Setelah masa lockout selesai atau Administrator mereset kata sandi, akun dapat login kembali.
- [ ] `percobaan_masuk` kembali nol setelah login berhasil.
- [ ] Aktivitas login gagal dan berhasil muncul pada log audit tanpa menyimpan kata sandi.

## D. Cabang aktif dan isolasi organisasi

Siapkan minimal dua cabang aktif dan satu cabang nonaktif.

- [ ] Administrator dapat melihat seluruh cabang aktif.
- [ ] Cabang nonaktif tidak muncul pada pemilih cabang.
- [ ] Pengguna operasional hanya melihat cabang yang ditugaskan kepadanya.
- [ ] Pengguna tanpa cabang aktif menerima HTTP 403 dengan pesan yang sesuai.
- [ ] Cabang pertama dipilih otomatis ketika session belum memiliki cabang aktif.
- [ ] Mengganti cabang melalui topbar memperbarui nama cabang aktif.
- [ ] Manipulasi `id_cabang` melalui request tidak dapat memilih cabang di luar assignment pengguna.
- [ ] Cabang session yang dinonaktifkan otomatis diganti dengan cabang valid berikutnya.
- [ ] Perubahan cabang tercatat pada log audit.

## E. Role dan permission

- [ ] Role awal tersedia: Administrator, Pemilik, Kasir, Gudang, Pembelian, Penjualan, dan Keuangan.
- [ ] Katalog permission aktif berjumlah 13.
- [ ] Administrator dapat membuka seluruh menu Fase 2.
- [ ] Pemilik hanya melihat menu yang diberikan pada matriks awal.
- [ ] Kasir tidak melihat menu manajemen pengguna, role, atau audit.
- [ ] Kasir yang mengetik URL `/pengguna` langsung menerima HTTP 403.
- [ ] Kasir yang mengetik URL `/peran` langsung menerima HTTP 403.
- [ ] Kasir yang mengetik URL `/audit` langsung menerima HTTP 403.
- [ ] Perubahan permission role langsung memengaruhi sidebar setelah request berikutnya.
- [ ] Perubahan permission juga memengaruhi akses URL langsung, bukan hanya tampilan menu.
- [ ] Role Administrator tetap memiliki akses penuh.
- [ ] Assignment global dengan `id_cabang = NULL` berfungsi sesuai desain.
- [ ] Assignment role-cabang yang pernah soft-deleted dapat diaktifkan kembali tanpa duplicate-key error.

## F. Manajemen pengguna

- [ ] Daftar pengguna dapat dicari berdasarkan nama pengguna, nama tampilan, atau surel.
- [ ] Pagination berjalan tanpa kehilangan parameter pencarian.
- [ ] Tambah pengguna dibuka melalui modal pada halaman yang sama.
- [ ] Nama pengguna duplikat ditolak.
- [ ] Kata sandi lemah ditolak.
- [ ] Pengguna baru wajib memiliki minimal satu assignment role.
- [ ] Role dan cabang yang tidak valid ditolak oleh validasi server.
- [ ] Edit pengguna dibuka melalui modal pada halaman yang sama.
- [ ] Edit assignment mengganti assignment aktif tanpa menghapus riwayat lama secara fisik.
- [ ] Pengguna dapat diberi lebih dari satu role dan lebih dari satu cabang.
- [ ] Administrator dapat menonaktifkan pengguna lain.
- [ ] Pengguna yang sedang login tidak dapat menonaktifkan dirinya sendiri.
- [ ] Reset kata sandi administratif berhasil.
- [ ] Setelah reset kata sandi, lockout akun dibersihkan.
- [ ] Kata sandi baru tidak tampil pada log audit atau respons HTML.

## G. Manajemen role dan permission

- [ ] Daftar role menampilkan status aktif dan jumlah permission.
- [ ] Tambah role melalui modal berhasil.
- [ ] Kode role duplikat ditolak.
- [ ] Edit nama, keterangan, dan daftar permission berhasil.
- [ ] Menonaktifkan role non-sistem berhasil.
- [ ] Role nonaktif tidak dapat memberikan akses baru.
- [ ] Role sistem yang dilindungi tidak dapat dirusak melalui request langsung.
- [ ] Relasi permission yang pernah soft-deleted dapat diaktifkan kembali tanpa duplicate-key error.
- [ ] Semua perubahan role dan permission tercatat pada audit.

## H. Profil dan kata sandi sendiri

- [ ] Pengguna dapat membuka profilnya sendiri.
- [ ] Perubahan kata sandi mewajibkan kata sandi saat ini yang benar.
- [ ] Konfirmasi kata sandi baru wajib cocok.
- [ ] Kata sandi baru harus memenuhi kebijakan minimum.
- [ ] Setelah berubah, kata sandi lama tidak dapat digunakan.
- [ ] Kata sandi baru dapat digunakan untuk login.
- [ ] Nilai kata sandi tidak tercatat pada audit.

## I. Log audit

- [ ] Login berhasil tercatat.
- [ ] Login gagal tercatat.
- [ ] Logout tercatat.
- [ ] Perubahan cabang tercatat.
- [ ] Tambah/edit/status/reset pengguna tercatat.
- [ ] Tambah/edit/status role dan permission tercatat.
- [ ] Ubah kata sandi sendiri tercatat tanpa nilai rahasia.
- [ ] Log menampilkan waktu, pengguna, cabang, modul, aktivitas, IP, dan user-agent.
- [ ] Pengguna tanpa `AUDIT_LIHAT` tidak dapat membuka log audit.
- [ ] Filter/pagination audit tetap konsisten.

## J. Tampilan UBold

- [ ] Login tampil baik pada desktop.
- [ ] Login tampil baik pada viewport mobile.
- [ ] Dashboard tampil baik pada desktop dan mobile.
- [ ] Sidebar dapat dibuka, ditutup, dan diperkecil.
- [ ] Topbar menampilkan pengguna dan cabang aktif yang benar.
- [ ] Modal tambah/edit tidak terpotong pada layar kecil.
- [ ] Tabel pengguna, role, dan audit dapat di-scroll pada mobile.
- [ ] Tidak ada teks sisa `Fase 1` atau `Autentikasi belum tersedia` pada halaman aktif.
- [ ] Tidak ada request `/css2*` 404.
- [ ] Nunito lokal termuat.
- [ ] Console browser tidak menampilkan error JavaScript yang memblokir fungsi.

## K. Regresi dan database

- [ ] `php artisan test` berhasil.
- [ ] `vendor/bin/pint --test` berhasil.
- [ ] `php artisan skema:verifikasi --rinci` tetap berhasil setelah pengujian CRUD akses.
- [ ] Tidak ada tabel bisnis, kolom, index, foreign key, atau view paten yang berubah.
- [ ] Tidak ada tabel infrastruktur Laravel tambahan selain `migrations`.
- [ ] Asset UBold dan font lokal tetap lolos pemeriksaan Network.
- [ ] Backup database dapat di-restore ke database development lain.
- [ ] Setelah restore, login, role, permission, cabang, dan audit masih berfungsi.

## L. Keputusan kelulusan

Fase 2 hanya boleh dinyatakan lulus apabila:

- [ ] seluruh item kritis autentikasi dan keamanan berhasil;
- [ ] seluruh akses URL langsung sesuai permission;
- [ ] multi-cabang tervalidasi;
- [ ] audit tidak menyimpan data rahasia;
- [ ] regression test berhasil;
- [ ] backup dan restore berhasil;
- [ ] pemilik menyatakan eksplisit `Fase 2 lulus`.

Sebelum pernyataan tersebut, Draft PR #3 tetap draft dan tidak boleh digabung ke `main`.
