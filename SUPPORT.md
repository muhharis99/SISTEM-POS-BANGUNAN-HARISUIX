# Panduan Dukungan

## Kanal dukungan

Gunakan GitHub Issue Form yang sesuai:

- **Laporan Bug atau Insiden** untuk error, perilaku salah, kegagalan proses, atau risiko integritas data;
- **Permintaan Perubahan** untuk penyempurnaan alur, laporan, tampilan, integrasi, atau fitur baru;
- **Security / Report a vulnerability** untuk dugaan kerentanan atau bukti yang memuat informasi sensitif.

Blank issue dinonaktifkan agar informasi minimum selalu tersedia.

## Sebelum membuat laporan

1. Pastikan masalah belum tercatat pada issue lain.
2. Catat versi atau commit aplikasi.
3. Tentukan lingkungan: pengembangan, staging, atau produksi.
4. Catat modul, cabang terdampak, waktu kejadian, dan peran pengguna.
5. Reproduksi dengan data uji apabila aman.
6. Samarkan seluruh bukti sebelum dilampirkan.

Jangan mengirim kata sandi, token, `.env`, backup, dump SQL, data transaksi lengkap, atau tangkapan layar yang masih memuat data sensitif.

## Prioritas

- **P0:** layanan mayoritas tidak dapat digunakan, kebocoran akses, kehilangan data, atau kerusakan integritas stok/keuangan;
- **P1:** fungsi utama gagal tetapi tersedia jalan sementara yang aman;
- **P2:** fungsi nonkritis, laporan, ekspor, atau tampilan bermasalah tanpa merusak data;
- **P3:** penyempurnaan kecil atau kebutuhan tanpa dampak operasional langsung.

Target respons mengikuti `docs/SLA-DAN-DUKUNGAN.md`. Nilai tersebut merupakan baseline internal dan harus disesuaikan dengan kontrak serta jam layanan nyata.

## Informasi minimum laporan bug

- prioritas awal;
- modul dan cabang terdampak;
- versi/commit dan lingkungan;
- ringkasan dampak;
- langkah reproduksi;
- hasil aktual dan hasil yang diharapkan;
- jalan sementara yang aman;
- bukti yang sudah disamarkan.

## Penutupan

Issue hanya ditutup setelah tersedia akar masalah, ruang lingkup dampak, tindakan/perbaikan, test yang dijalankan, hasil verifikasi, catatan deployment, dan risiko tersisa. Perubahan kode wajib melalui Draft PR, CI, expected head SHA, serta merge manual. Auto-merge tidak digunakan.
