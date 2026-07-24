# Kebijakan Keamanan

## Versi yang didukung

Versi stabil yang menjadi baseline dukungan saat ini adalah `v1.0.x`. Perbaikan keamanan untuk versi lain ditentukan melalui keputusan pemilik dan analisis dampak.

## Pelaporan kerentanan

Jangan melaporkan kerentanan yang memuat rahasia atau data sensitif melalui GitHub Issue publik.

Gunakan tab **Security** repository dan pilih **Report a vulnerability** bila fitur pelaporan privat tersedia. Bila fitur tersebut tidak tersedia, gunakan kanal privat yang telah disepakati langsung dengan pemilik atau PIC keamanan.

Jangan menaruh informasi berikut pada issue, komentar, pull request, atau log publik:

- kata sandi, token, cookie sesi, private key, atau kredensial;
- isi `.env` atau konfigurasi produksi lengkap;
- backup database atau dump SQL;
- data pelanggan, pemasok, pegawai, maupun transaksi lengkap;
- alamat server privat, bukti yang belum disamarkan, atau informasi yang memudahkan eksploitasi.

## Informasi yang diperlukan

Laporan privat sebaiknya memuat:

- versi atau commit terdampak;
- modul dan lingkungan terdampak;
- dampak terhadap kerahasiaan, integritas, atau ketersediaan;
- langkah reproduksi paling kecil menggunakan data uji;
- bukti yang sudah disamarkan;
- kemungkinan eksploitasi dan jalan mitigasi sementara;
- identitas kontak pelapor yang dapat dihubungi secara privat.

## Batas pengujian

- Jangan melakukan pengujian destruktif pada produksi.
- Jangan mengakses, menyalin, atau mengubah data yang tidak diperlukan untuk pembuktian.
- Jangan meningkatkan hak akses, melakukan lateral movement, atau mengganggu layanan.
- Gunakan staging atau data uji apabila memungkinkan.
- Hentikan pengujian ketika ada risiko kerusakan data, kebocoran rahasia, atau gangguan operasional.

## Penanganan

Laporan akan ditriase berdasarkan dampak. Kerentanan yang mengancam akses lintas cabang, kredensial, integritas stok/keuangan, atau pemulihan data diperlakukan sebagai P0/P1 sesuai `docs/SLA-DAN-DUKUNGAN.md`.

Perbaikan keamanan tetap wajib melalui branch, Draft PR, pengujian relevan, verifikasi expected head SHA, dan merge manual. Auto-merge tidak digunakan. Informasi publik hanya diterbitkan setelah mitigasi tersedia dan pemilik menyetujui pengungkapan.
