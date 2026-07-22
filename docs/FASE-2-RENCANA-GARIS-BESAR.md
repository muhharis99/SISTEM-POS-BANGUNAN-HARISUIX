# Fase 2 — Rencana Garis Besar

## Status

**Perencanaan saja — belum dieksekusi.**

Implementasi Fase 2 hanya boleh dimulai setelah pemilik proyek menyatakan:

```text
Fase 1 lulus
```

## Tujuan

Membangun fondasi keamanan dan organisasi pengguna agar seluruh modul pada fase berikutnya memiliki aturan akses yang konsisten, dapat diaudit, dan mengikuti struktur database bisnis yang sudah paten.

## Ruang lingkup utama

### 1. Autentikasi

- login menggunakan identitas pengguna yang disepakati;
- logout;
- perlindungan session;
- pembatasan percobaan login;
- status pengguna aktif/nonaktif;
- pergantian password sendiri;
- reset password oleh administrator sesuai kebijakan;
- pencatatan login berhasil dan gagal;
- redirect setelah login berdasarkan hak akses.

### 2. Organisasi

- pemetaan pengguna ke cabang;
- pemetaan pengguna ke gudang bila diperlukan;
- cabang aktif pengguna;
- pembatasan data berdasarkan cabang;
- aturan pengguna yang boleh mengakses lebih dari satu cabang;
- pemilihan cabang aktif tanpa mencampur data antar cabang;
- validasi bahwa cabang/gudang yang dipilih masih aktif.

### 3. Role

Role awal mengikuti seed SQL:

- ADMINISTRATOR;
- PEMILIK;
- KASIR;
- GUDANG;
- PEMBELIAN;
- PENJUALAN;
- KEUANGAN.

Pekerjaan meliputi:

- daftar role;
- detail role;
- assignment role ke pengguna;
- pencabutan role;
- perlindungan role sistem;
- pencegahan penghapusan role yang masih digunakan;
- aturan pengguna dengan satu atau beberapa role sesuai keputusan bisnis.

### 4. Permission

- katalog permission berdasarkan modul dan aksi;
- permission code mengikuti konvensi yang sudah disetujui;
- assignment permission ke role;
- pengecualian permission langsung ke pengguna hanya jika benar-benar diperlukan;
- pemeriksaan akses di route, controller, service, policy, dan tampilan;
- menu/sidebar hanya tampil bila pengguna mempunyai izin;
- akses URL langsung tetap ditolak meskipun menu disembunyikan.

### 5. Policy dan pembatasan data

- policy per domain/model yang membutuhkan proteksi;
- pembatasan berdasarkan cabang aktif;
- pembatasan berdasarkan kepemilikan atau tanggung jawab data bila diperlukan;
- pemisahan izin melihat, membuat, mengubah, menghapus, menyetujui, membatalkan, mencetak, dan mengekspor;
- larangan mengandalkan pengecekan di Blade saja.

### 6. Manajemen pengguna

- daftar pengguna;
- tambah pengguna;
- ubah profil dasar;
- aktivasi/nonaktivasi;
- assignment cabang/gudang;
- assignment role;
- reset password administratif;
- larangan menghapus permanen pengguna yang sudah mempunyai riwayat transaksi;
- audit perubahan pengguna.

### 7. Audit keamanan

- pencatatan login berhasil/gagal;
- perubahan password;
- assignment dan pencabutan role;
- assignment dan pencabutan permission;
- perubahan cabang/gudang pengguna;
- aktivasi/nonaktivasi akun;
- pelaku, waktu, IP, user agent, dan konteks perubahan bila tersedia.

## Urutan eksekusi yang direncanakan

1. verifikasi tabel pengguna, role, permission, cabang, gudang, dan relasinya pada SQL final;
2. tetapkan matriks role-permission awal;
3. bangun autentikasi dasar;
4. bangun konteks cabang aktif;
5. bangun middleware autentikasi dan permission;
6. bangun policy dan pembatasan data;
7. bangun manajemen pengguna;
8. bangun manajemen role dan permission;
9. integrasikan sidebar/menu UBold;
10. tambahkan audit keamanan;
11. jalankan automated test;
12. jalankan checklist manual dan regression test Fase 1.

## Keputusan bisnis yang akan dikonfirmasi saat mulai Fase 2

- identitas login: username, email, nomor HP, atau kombinasi;
- apakah satu pengguna boleh mempunyai beberapa role;
- apakah satu pengguna boleh mempunyai beberapa cabang;
- apakah kasir terikat pada cabang dan kas tertentu;
- apakah staf gudang boleh mengakses beberapa gudang;
- siapa yang boleh mereset password pengguna lain;
- apakah pengguna wajib mengganti password pada login pertama;
- masa session dan kebijakan lockout;
- kebutuhan autentikasi dua faktor;
- format final permission code dan matriks akses.

## Deliverable Fase 2

- autentikasi berjalan;
- halaman login yang menggunakan gaya UBold;
- manajemen pengguna;
- manajemen role;
- manajemen permission;
- assignment cabang/gudang;
- middleware dan policy;
- sidebar dinamis berdasarkan permission;
- audit keamanan;
- matriks role-permission terdokumentasi;
- unit test dan feature test;
- checklist manual Fase 2;
- smoke test regresi Fase 1;
- dokumentasi checkpoint Git dan backup.

## Kriteria lulus yang direncanakan

- pengguna valid dapat login dan logout;
- pengguna nonaktif tidak dapat login;
- rate limit/lockout bekerja sesuai kebijakan;
- pengguna hanya melihat menu yang diizinkan;
- akses URL langsung tanpa permission menghasilkan penolakan;
- pembatasan cabang mencegah kebocoran data antar cabang;
- perubahan role/permission tercatat dalam audit;
- administrator tidak dapat menghapus akun/role sistem secara tidak aman;
- test otomatis berhasil;
- regression test Fase 1 berhasil;
- checklist manual diberikan kepada pemilik;
- pemilik menyatakan eksplisit `Fase 2 lulus` sebelum Fase 3 dimulai.

## Aturan version control

Fase 2 akan dikerjakan pada branch terpisah setelah Fase 1 lulus. Tag/checkpoint `fase-2-selesai` hanya dibuat setelah checklist manual selesai dan pemilik menyatakan `Fase 2 lulus`.
