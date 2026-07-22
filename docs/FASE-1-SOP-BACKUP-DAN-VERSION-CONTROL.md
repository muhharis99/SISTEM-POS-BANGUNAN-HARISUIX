# Fase 1 — SOP Backup dan Version Control

SOP ini berlaku sejak Fase 1 dan terus digunakan pada seluruh fase proyek.

## 1. Aturan branch

- `main` hanya berisi fase yang sudah dinyatakan lulus oleh pemilik proyek.
- Pengerjaan Fase 1 dilakukan pada branch `fase-1-fondasi`.
- Fase berikutnya menggunakan branch baru dari checkpoint fase sebelumnya.
- Branch fase tidak boleh digabung ke `main` sebelum checklist manual selesai dan pemilik proyek menyatakan secara eksplisit `Fase X lulus`.

Contoh:

```text
main
└── fase-1-fondasi
    └── fase-2-autentikasi-otorisasi
```

Branch fase berikutnya baru dibuat setelah fase sebelumnya lulus dan sudah masuk `main`.

## 2. Aturan commit granular

Satu commit hanya berisi satu tujuan yang jelas. Format pesan commit:

```text
<jenis>(fase-X): <ringkasan perubahan>
```

Jenis yang digunakan:

- `feat`: fitur atau kemampuan baru;
- `fix`: koreksi bug atau konfigurasi;
- `refactor`: perubahan struktur tanpa mengubah tujuan fitur;
- `test`: pengujian;
- `docs`: dokumentasi;
- `chore`: konfigurasi dan pekerjaan pendukung.

Contoh:

```text
feat(fase-1): tambahkan migration baseline skema final
fix(fase-1): gunakan sesi file tanpa tabel tambahan
test(fase-1): tambahkan smoke test dashboard fondasi
docs(fase-1): kunci inventaris asset UBold
```

## 3. Checkpoint fase

Setelah pemilik menyatakan fase lulus:

1. Pastikan working tree bersih.
2. Pastikan seluruh test fase berhasil.
3. Buat backup database.
4. Gabungkan branch fase ke `main` melalui pull request.
5. Buat tag checkpoint setelah merge.

Format tag:

```text
fase-1-selesai
fase-2-selesai
fase-3-selesai
```

Tag `fase-1-selesai` **belum boleh dibuat** sebelum ada pernyataan eksplisit `Fase 1 lulus`.

## 4. Backup database sebelum tindakan berisiko

Backup wajib dibuat sebelum:

- migration;
- rollback;
- import data;
- pengujian transaksi besar;
- perubahan relasi atau index yang sudah disetujui;
- merge fase;
- deployment staging atau production.

Linux Mint:

```bash
bash scripts/backup-database.sh
```

Windows/Laragon PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/backup-database.ps1
```

Hasil backup berada di:

```text
backups/database/
```

Folder backup diabaikan Git. Setiap backup memiliki checksum SHA-256.

## 5. Uji pemulihan backup

Backup belum dianggap valid hanya karena berkas berhasil dibuat. Minimal satu kali per fase:

1. Buat database kosong khusus pemulihan.
2. Import backup ke database tersebut.
3. Jalankan pemeriksaan jumlah tabel dan view.
4. Buka beberapa tabel utama.
5. Catat hasil uji pemulihan.

Database uji pemulihan tidak boleh menggunakan database development aktif.

## 6. Larangan

- Jangan menyimpan `.env` ke Git.
- Jangan menyimpan file backup database ke Git.
- Jangan melakukan `git push --force` ke `main`.
- Jangan menghapus tag checkpoint fase.
- Jangan menjalankan `migrate:fresh`, `db:wipe`, atau import SQL pada database yang berisi data penting sebelum backup dan konfirmasi.
- Jangan menganggap commit terakhir otomatis sebagai fase lulus.

## 7. Rollback kode

Untuk memeriksa checkpoint tanpa mengubah branch aktif:

```bash
git show fase-1-selesai
```

Untuk membuat branch perbaikan dari checkpoint:

```bash
git switch -c perbaikan-dari-fase-1 fase-1-selesai
```

Mengembalikan production ke checkpoint harus dilakukan melalui prosedur deployment dan restore database yang sesuai. Kode dan database tidak boleh di-rollback secara terpisah tanpa analisis kompatibilitas.

## 8. Bukti kelulusan fase

Setiap fase menyimpan bukti berikut:

- daftar commit;
- hasil automated test;
- hasil smoke test regresi;
- hasil checklist manual;
- hasil backup dan uji restore;
- catatan masalah yang belum selesai;
- pernyataan eksplisit pemilik proyek bahwa fase lulus.
