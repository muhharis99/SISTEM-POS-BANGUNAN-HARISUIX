# Panduan Serah-Terima Operasional

## 1. Tujuan

Dokumen ini menjadi acuan penyerahan Sistem POS Toko Bangunan HARISUIX dari tim pengembang kepada pemilik, penanggung jawab operasional, dan petugas IT.

## 2. Ruang lingkup serah-terima

- kode sumber dan riwayat perubahan;
- SQL paten serta prosedur verifikasi skema;
- konfigurasi environment contoh;
- panduan pengguna dan matriks UAT;
- release notes dan changelog;
- manifest release candidate;
- runbook deployment, backup, restore, dan rollback;
- contoh konfigurasi Nginx, PHP-FPM, dan systemd;
- daftar akun, peran, dan akses cabang yang disetujui;
- jalur dukungan, eskalasi, dan kepemilikan keputusan.

## 3. Artefak wajib

| Artefak | Lokasi | Penanggung jawab | Status |
|---|---|---|---|
| Kode sumber | Repository GitHub | IT/Pengembang |  |
| SQL paten | `struktur_database_toko_bangunan.sql` | IT |  |
| Changelog | `CHANGELOG.md` | Pengembang |  |
| Release notes | `docs/RELEASE-NOTES-v1.0.0-rc1.md` | Pengembang |  |
| Panduan pengguna | `docs/PANDUAN-PENGGUNA.md` | Operasional |  |
| Matriks UAT | `docs/UAT-RELEASE-CANDIDATE.md` | Pemilik/Operasional |  |
| Deployment | `docs/DEPLOYMENT-PRODUKSI.md` | IT |  |
| Backup/restore | `docs/BACKUP-RESTORE-DATABASE.md` | IT |  |
| Manifest RC | `storage/app/release-candidate/manifest-rilis.json` | IT |  |
| Bukti UAT | Lokasi privat yang disepakati | Pemilik/Operasional |  |

## 4. Pembagian tanggung jawab

### Pemilik

- menyetujui kebijakan harga, kredit, pajak, dan persetujuan transaksi;
- menentukan penanggung jawab setiap fungsi;
- menerima atau menolak hasil UAT;
- menyetujui waktu go-live dan rollback;
- memastikan sumber daya untuk backup serta dukungan tersedia.

### Penanggung jawab operasional

- memastikan alur kerja sesuai proses toko;
- memimpin UAT pengguna;
- menjaga daftar master dan SOP operasional;
- mengumpulkan temuan serta bukti;
- mengoordinasikan pelatihan pengguna.

### Penanggung jawab keuangan

- memverifikasi kas, bank, hutang, piutang, jurnal, dan laporan;
- menyetujui prosedur penutupan harian;
- menjaga bukti pembayaran dan rekonsiliasi;
- memeriksa saldo awal sebelum go-live.

### Penanggung jawab gudang

- memverifikasi gudang, lokasi, stok awal, transfer, opname, dan penyesuaian;
- memastikan data fisik sesuai data sistem;
- menentukan prosedur barang rusak, hilang, dan retur.

### Petugas IT

- menjaga server, database, domain, sertifikat, dan repository;
- mengelola deployment, backup, restore, monitoring, dan rollback;
- menjaga rahasia environment dan kredensial;
- memastikan patch keamanan serta dependency dikelola;
- menyiapkan bukti health check dan backup.

### Pengembang

- menyerahkan dokumentasi teknis;
- menjelaskan arsitektur dan batasan sistem;
- memperbaiki temuan sesuai ruang lingkup dukungan;
- tidak menyimpan kredensial produksi;
- membantu transisi sesuai kesepakatan.

## 5. Pelatihan

### Sesi 1 — Administrator

- akun, peran, hak akses, dan cabang;
- master data;
- audit dan lampiran;
- pengaturan operasional;
- prinsip akses minimum.

### Sesi 2 — Gudang dan pembelian

- barang, satuan, barcode, gudang, dan lokasi;
- stok awal, transfer, opname, dan penyesuaian;
- pesanan, penerimaan, faktur, retur, dan hutang.

### Sesi 3 — Kasir dan penjualan

- pelanggan, penawaran, pesanan, POS, pembayaran, dan nota;
- transaksi tunai, multi-metode, dan tempo;
- pengiriman, retur, serta piutang.

### Sesi 4 — Keuangan dan pemilik

- kas masuk/keluar/pindah;
- akun dan pemetaan;
- jurnal umum;
- dashboard, laporan, ekspor, dan rekonsiliasi.

### Sesi 5 — IT

- environment dan hardening;
- deployment release;
- backup, restore, dan rollback;
- health check;
- manifest release candidate;
- troubleshooting serta eskalasi.

## 6. Persiapan go-live

- UAT disetujui.
- Temuan kritis dan tinggi ditutup.
- Data master final telah diperiksa.
- Stok awal dan saldo keuangan disetujui.
- Akun serta peran final tersedia.
- Backup sebelum go-live berhasil dan disalin off-server.
- Restore backup telah diuji.
- Domain, HTTPS, Nginx, PHP-FPM, permission, dan timer backup siap.
- Printer nota dan perangkat pengguna diuji.
- Jadwal go-live dan rollback disepakati.
- Kontak dukungan tersedia.

## 7. Penutupan harian operasional

- periksa transaksi draf, gagal, dan dibatalkan;
- rekonsiliasi kas serta bank;
- periksa penjualan tempo, hutang, dan piutang;
- tinjau pengiriman yang belum selesai;
- periksa stok negatif atau selisih tidak wajar;
- pastikan backup terjadwal berhasil;
- catat masalah pada log operasional.

## 8. Dukungan dan eskalasi

### Prioritas KRITIS

Contoh:

- aplikasi tidak dapat digunakan seluruh pengguna;
- kehilangan atau kerusakan data;
- kebocoran data antarcabang;
- transaksi keuangan atau stok salah secara luas;
- backup tidak dapat dipulihkan.

Tindakan:

1. hentikan proses terdampak;
2. aktifkan maintenance mode bila diperlukan;
3. simpan bukti dan log;
4. hubungi IT serta pemilik;
5. jangan melakukan perubahan database manual;
6. putuskan rollback atau restore berdasarkan analisis.

### Prioritas TINGGI

Contoh:

- satu modul utama tidak berfungsi;
- hak akses salah;
- transaksi tertentu tidak dapat diselesaikan;
- laporan utama tidak sesuai.

### Prioritas SEDANG/RENDAH

Contoh:

- tampilan tidak rapi;
- teks kurang jelas;
- kebutuhan peningkatan nonblokir;
- permintaan laporan tambahan.

## 9. Informasi tiket dukungan

- waktu kejadian;
- nama pengguna dan peran;
- cabang aktif;
- modul dan URL;
- nomor dokumen;
- langkah reproduksi;
- hasil yang diharapkan dan hasil aktual;
- pesan kesalahan;
- bukti tangkapan layar;
- dampak serta jumlah pengguna terdampak.

Dilarang melampirkan kata sandi, `.env`, kredensial database, atau backup mentah pada tiket biasa.

## 10. Perubahan setelah serah-terima

- Setiap perubahan harus memiliki tujuan, risiko, rencana pengujian, dan rollback.
- Perubahan skema wajib mendapat izin pemilik karena SQL bersifat paten.
- Hotfix harus dibuat melalui branch dan pull request.
- Auto-merge tetap dilarang.
- Backup dibuat sebelum migration atau perubahan berisiko.
- Changelog dan release notes diperbarui.

## 11. Form penerimaan

| Pihak | Nama | Tanggung jawab diterima | Tanggal | Keputusan/catatan |
|---|---|---|---|---|
| Pemilik |  |  |  |  |
| Operasional |  |  |  |  |
| Keuangan |  |  |  |  |
| Gudang |  |  |  |  |
| IT |  |  |  |  |
| Pengembang |  |  |  |  |
