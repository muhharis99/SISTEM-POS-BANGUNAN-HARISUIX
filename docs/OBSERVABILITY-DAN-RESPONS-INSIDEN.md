# Observability dan Respons Insiden

## Sasaran pemantauan

Pemantauan harus membantu tim menjawab empat pertanyaan:

1. apakah aplikasi dapat diakses;
2. apakah transaksi dapat diselesaikan dengan benar;
3. apakah data dan infrastruktur tetap sehat;
4. siapa yang harus bertindak ketika terjadi penyimpangan.

## Sinyal teknis minimum

- HTTP status `/up` dan `/kesiapan`;
- error 5xx pada Nginx dan Laravel;
- waktu respons halaman login, POS, dashboard, dan laporan;
- koneksi serta waktu query MySQL;
- penggunaan CPU, RAM, disk, inode, dan koneksi jaringan;
- status PHP-FPM, Nginx, MySQL, timer backup, dan timer hypercare;
- usia backup terbaru dan kecocokan checksum;
- kegagalan login yang tidak wajar;
- perubahan permission, unduhan lampiran, ekspor, dan aktivitas sensitif pada audit.

Endpoint `/kesiapan` tidak boleh digunakan untuk mengekspos kredensial, path server, query, atau exception internal.

## Sinyal bisnis minimum

- jumlah dan nilai penjualan harian;
- pembelian dan penerimaan yang belum selesai;
- stok negatif atau selisih stok tidak wajar;
- hutang/piutang jatuh tempo dan pembayaran yang tidak teralokasi;
- transaksi kas yang tertahan pada DRAF;
- jurnal tidak seimbang atau belum diposting;
- retur dan pembatalan yang meningkat tajam;
- kegagalan cetak nota atau ekspor laporan.

Sinyal bisnis harus dibandingkan dengan pola operasional toko, bukan hanya ambang teknis generik.

## Tingkat insiden

### KRITIS

Layanan tidak dapat digunakan, kehilangan/korupsi data, kebocoran data, transaksi salah secara luas, atau kegagalan database utama.

- respons awal: secepatnya;
- pemilik keputusan dan PIC IT wajib dihubungi;
- hentikan transaksi bila melanjutkan dapat memperburuk dampak;
- pertimbangkan maintenance mode dan rollback aplikasi.

### TINGGI

Fungsi inti terganggu untuk banyak pengguna atau satu cabang, tetapi data utama masih dapat diamankan.

- prioritaskan pemulihan layanan inti;
- gunakan prosedur manual sementara;
- rekonsiliasi transaksi yang terdampak.

### SEDANG

Fungsi noninti atau sebagian pengguna terdampak dengan solusi sementara tersedia.

- catat tiket dan target perbaikan;
- pantau agar tidak berkembang menjadi insiden tinggi.

### RENDAH

Masalah tampilan, dokumentasi, atau peningkatan yang tidak menghambat operasi.

## Prosedur respons

1. **Deteksi:** catat waktu, sumber alarm, cabang, pengguna, dan gejala.
2. **Validasi:** konfirmasi apakah masalah nyata dan tentukan ruang dampak.
3. **Klasifikasi:** tetapkan tingkat insiden dan PIC.
4. **Pengamanan:** hentikan tindakan yang memperparah, aktifkan maintenance bila perlu, dan jaga bukti.
5. **Pemulihan:** pilih restart layanan, koreksi konfigurasi, rollback aplikasi, atau prosedur lain yang disetujui.
6. **Verifikasi:** jalankan readiness, smoke test, dan validasi bisnis.
7. **Rekonsiliasi:** periksa transaksi, stok, kas, hutang, piutang, jurnal, dan lampiran terdampak.
8. **Komunikasi:** sampaikan status berdasarkan fakta, dampak, tindakan, dan waktu pembaruan berikutnya.
9. **Penutupan:** dokumentasikan akar masalah, timeline, dampak, tindakan, serta pencegahan.

## Bukti yang boleh dikumpulkan

- timestamp dan ID referensi transaksi;
- screenshot tanpa kata sandi/token;
- potongan log yang telah menyamarkan data sensitif;
- status service dan penggunaan sumber daya;
- hasil command pemeriksaan dalam JSON;
- checksum paket dan backup;
- commit dan versi aktif.

Jangan mengirim `.env`, cookie, session, token, private key, dump database, atau kata sandi melalui tiket biasa.

## Command pemeriksaan

```bash
php artisan sistem:periksa-produksi --json
php artisan sistem:smoke-test-pascadeploy --json
php artisan sistem:periksa-go-live --json
systemctl status sistem-pos-hypercare.timer
journalctl -u sistem-pos-hypercare.service
```

## Komunikasi insiden

Setiap pembaruan minimal berisi:

- tingkat insiden;
- waktu mulai dan waktu deteksi;
- fungsi/cabang yang terdampak;
- dampak terhadap transaksi dan data;
- tindakan yang telah dilakukan;
- risiko yang masih terbuka;
- keputusan lanjut atau rollback;
- waktu pembaruan berikutnya.

Hindari janji waktu pemulihan yang belum didukung bukti teknis.

## Tinjauan pascainsiden

Tinjauan dilakukan tanpa menyalahkan individu dan harus menghasilkan:

- akar masalah teknis dan proses;
- alasan deteksi tidak lebih awal bila relevan;
- dampak data dan hasil rekonsiliasi;
- efektivitas backup/rollback;
- perbaikan kode, konfigurasi, dokumentasi, pelatihan, atau monitoring;
- pemilik tindakan dan tanggal target.