# Release dan Deployment Produksi

## Prinsip

Release dan deployment produksi tidak boleh dijalankan dari workstation atau percakapan tanpa akses server yang sah. Jalur resmi menggunakan workflow `.github/workflows/release-produksi.yml`, GitHub Environment `production`, expected head SHA, dan secret repository/environment.

Auto-merge tetap dilarang. Workflow ini hanya dapat dijalankan manual melalui `workflow_dispatch` setelah Pull Request fase terkait selesai di-merge ke `main` dan seluruh gate pada commit yang sama berhasil.

## Secret environment production

Siapkan secret berikut pada GitHub Environment `production`:

- `PRODUCTION_HOST`: hostname atau alamat IP server;
- `PRODUCTION_USER`: pengguna SSH terbatas untuk deployment;
- `PRODUCTION_SSH_KEY`: private key khusus deployment;
- `PRODUCTION_PATH`: direktori aplikasi produksi;
- `PRODUCTION_DEPLOY_COMMAND`: perintah deployment yang telah ditinjau, termasuk backup, ekstraksi release, pemasangan dependensi, konfigurasi `.env`, migration bila benar-benar diperlukan, cache, symlink/current release, smoke test, dan rollback otomatis bila smoke test gagal.

Secret tidak boleh disimpan di repository, issue, Pull Request, artifact publik, atau log aplikasi.

## Gate sebelum menjalankan

1. Pastikan PR telah di-merge manual menggunakan expected head SHA.
2. Pastikan commit merge berada pada `main`.
3. Pastikan semua workflow pada commit tersebut hijau.
4. Pastikan `VERSION` sama dengan input versi.
5. Pastikan backup database terbaru tersedia dan restore telah pernah diuji.
6. Pastikan GitHub Environment `production` memiliki required reviewer.
7. Pastikan server memiliki kapasitas, PHP, ekstensi, web server, database, dan permission direktori yang sesuai.
8. Pastikan `PRODUCTION_DEPLOY_COMMAND` sudah diuji pada staging.

## Menjalankan release

Buka GitHub Actions, pilih **Release dan Deployment Produksi**, lalu isi:

- `versi`: contoh `v1.0.0`;
- `expected_sha`: SHA commit `main` yang sudah lulus gate;
- `jalankan_deployment`: `false` untuk membuat tag/release saja, atau `true` setelah akses server dan approval production tersedia.

Workflow akan:

1. checkout SHA terkunci;
2. membuktikan SHA merupakan ancestor `main`;
3. memverifikasi `VERSION`;
4. menjalankan sintaks, Pint, dan test;
5. membuat arsip release dan checksum SHA-256;
6. membuat annotated tag dan GitHub Release yang tidak dapat menimpa tag lama;
7. bila deployment disetujui, mengunggah arsip melalui SSH dan menjalankan perintah deployment yang disimpan sebagai secret.

## Rollback

`PRODUCTION_DEPLOY_COMMAND` wajib menggunakan pola release directory dan symlink sehingga rollback tidak bergantung pada edit file langsung. Minimum rollback:

1. hentikan aktivasi release baru;
2. kembalikan symlink ke release sebelumnya;
3. pulihkan database hanya bila perubahan database memang terjadi dan backup tervalidasi;
4. bersihkan cache;
5. jalankan smoke test;
6. catat insiden dan SHA yang dipulihkan.

## Batasan lingkungan ChatGPT

Repository dapat disiapkan, diuji, di-merge, dan diberi workflow release melalui GitHub connector. Eksekusi deployment produksi memerlukan host, user, private key, path, dan perintah deployment yang tidak tersedia pada percakapan ini. Karena itu, tidak boleh ada klaim bahwa produksi telah dideploy sebelum workflow production benar-benar dijalankan dan menghasilkan bukti sukses.