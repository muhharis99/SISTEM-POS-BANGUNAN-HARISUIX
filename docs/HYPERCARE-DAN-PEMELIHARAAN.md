# Hypercare dan Pemeliharaan

## Masa hypercare

Masa hypercare yang disarankan adalah 14 hari kalender setelah go-live. Durasi dapat diperpanjang bila terdapat insiden tinggi, perubahan proses besar, atau rekonsiliasi yang belum stabil.

## Frekuensi pemeriksaan

### Dua jam pertama

- readiness dan smoke test setiap 15 menit;
- pantau error 5xx, database, CPU, RAM, dan disk;
- dampingi transaksi penjualan, stok, kas, dan cetak nota;
- pastikan backup sebelum go-live tetap aman.

### Hari pertama

- pemeriksaan teknis setiap 15–30 menit selama jam operasional;
- rekonsiliasi penjualan, mutasi stok, kas, hutang, piutang, dan jurnal;
- tinjau audit aktivitas sensitif;
- catat kendala pengguna dan kebutuhan pelatihan tambahan.

### Hari ke-2 sampai ke-3

- pemeriksaan teknis minimal tiap jam selama jam operasional;
- rekonsiliasi akhir hari;
- tinjau kapasitas dan error berulang;
- selesaikan temuan tinggi yang masih terbuka.

### Hari ke-4 sampai ke-7

- pemeriksaan awal, tengah, dan akhir hari;
- uji backup harian dan salinan off-server;
- tinjau pola retur, pembatalan, selisih stok, dan pembayaran.

### Hari ke-8 sampai ke-14

- pemeriksaan harian;
- tinjau tren stabilitas dan beban;
- pastikan dokumentasi serta PIC operasional memadai;
- siapkan keputusan keluar dari hypercare.

## Otomasi pemeriksaan

Contoh systemd:

```bash
sudo cp deploy/systemd/sistem-pos-hypercare.service /etc/systemd/system/
sudo cp deploy/systemd/sistem-pos-hypercare.timer /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now sistem-pos-hypercare.timer
systemctl list-timers sistem-pos-hypercare.timer
```

Timer menjalankan `scripts/hypercare-check.sh`, menyimpan hasil JSON secara privat, memakai lock agar tidak tumpang tindih, dan membersihkan hasil yang lebih lama dari 14 hari.

Konfigurasi path, user, PHP, backup, dan hak akses wajib disesuaikan dengan server nyata.

## Checklist harian

- `/up` dan `/kesiapan` sehat;
- tidak ada error 5xx berulang;
- backup terbaru valid dan checksum cocok;
- ruang disk serta inode aman;
- service Nginx, PHP-FPM, MySQL, backup timer, dan hypercare timer aktif;
- transaksi penjualan/pembelian dapat diselesaikan;
- stok dan mutasi konsisten;
- kas dan jurnal tidak menunjukkan anomali;
- hutang/piutang serta alokasi pembayaran konsisten;
- lampiran privat dan audit berfungsi;
- dashboard, CSV, dan nota berfungsi;
- tidak ada akses lintas cabang atau permission yang tidak semestinya.

## Pemeliharaan rutin

### Harian

- backup database dan verifikasi checksum;
- rekonsiliasi transaksi serta saldo utama;
- tinjau error log dan audit sensitif;
- pastikan storage serta disk tidak mendekati penuh.

### Mingguan

- uji restore pada database terpisah;
- tinjau akun pengguna dan permission yang berubah;
- tinjau stok negatif, selisih opname, retur, serta pembatalan;
- tinjau hutang/piutang jatuh tempo;
- tinjau performa query dan kapasitas database.

### Bulanan

- uji rollback aplikasi pada staging;
- tinjau sertifikat TLS dan pembaruan sistem operasi;
- tinjau retensi backup on-server/off-server;
- tinjau prosedur insiden dan kontak eskalasi;
- evaluasi kebutuhan pembaruan aplikasi.

## Perubahan setelah v1.0.0

Setiap perubahan harus:

1. memiliki ruang lingkup dan risiko yang jelas;
2. menggunakan branch serta PR terpisah;
3. tidak mengubah SQL paten tanpa persetujuan pemilik;
4. memiliki test dan regression yang relevan;
5. memperbarui changelog/release notes;
6. diuji pada staging;
7. memiliki rencana deployment dan rollback;
8. tidak menggunakan auto-merge bila kebijakan proyek tetap melarangnya.

## Kriteria keluar dari hypercare

Hypercare dapat ditutup bila:

- tidak ada insiden KRITIS atau TINGGI terbuka;
- readiness dan smoke test stabil;
- backup serta restore test berhasil;
- rekonsiliasi bisnis konsisten minimal beberapa hari operasional;
- kapasitas server stabil;
- PIC operasional mampu menjalankan prosedur harian;
- jalur dukungan dan eskalasi aktif;
- dokumentasi insiden serta perubahan lengkap;
- pemilik menyetujui penutupan hypercare.

## Serah-terima ke operasi normal

Catat tanggal penutupan, versi aktif, commit, insiden yang pernah terjadi, risiko tersisa, daftar tindak lanjut, PIC dukungan, jadwal maintenance berikutnya, serta lokasi backup dan dokumentasi.