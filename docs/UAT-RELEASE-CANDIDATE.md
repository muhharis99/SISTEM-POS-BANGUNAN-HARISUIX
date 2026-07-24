# Matriks UAT Release Candidate

## 1. Identitas

- Produk: Sistem POS Toko Bangunan HARISUIX
- Kandidat: `v1.0.0-rc1`
- Fase: 11
- Target: staging terpisah dari produksi
- Skema: 71 base table dan 3 view
- Permission aktif: 98

## 2. Aturan UAT

- Gunakan data uji yang tidak memuat data sensitif nyata.
- Setiap skenario memiliki pelaksana, hasil yang diharapkan, bukti, dan keputusan.
- Temuan diberi tingkat KRITIS, TINGGI, SEDANG, atau RENDAH.
- Temuan KRITIS dan TINGGI harus ditutup sebelum keputusan rilis.
- Bukti dapat berupa nomor dokumen, tangkapan layar, CSV, nota, log audit, atau checksum.
- Pengujian antarcabang wajib memakai sedikitnya dua cabang.

## 3. Format pencatatan hasil

| Kolom | Isi |
|---|---|
| ID | Kode skenario UAT |
| Pelaksana | Nama dan peran penguji |
| Tanggal | Waktu pengujian |
| Data uji | Nomor dokumen atau identitas data uji |
| Hasil | LULUS / GAGAL / DIBLOKIR |
| Bukti | Lokasi bukti pengujian |
| Temuan | Nomor temuan bila ada |
| Catatan | Penjelasan tambahan |

## 4. Skenario UAT

### A. Autentikasi, cabang, dan hak akses

| ID | Peran | Skenario | Hasil yang diharapkan |
|---|---|---|---|
| UAT-A01 | Semua | Login dengan akun aktif | Login berhasil dan pengguna diarahkan memilih/menggunakan cabang yang diizinkan |
| UAT-A02 | Semua | Login dengan kata sandi salah | Ditolak tanpa membocorkan detail akun |
| UAT-A03 | Semua | Mengakses URL modul tanpa izin | HTTP 403 atau akses ditolak |
| UAT-A04 | Semua | Mengakses data cabang lain melalui ID URL | Data tidak ditemukan atau ditolak |
| UAT-A05 | Administrator | Mengubah peran dan akses cabang pengguna | Perubahan hanya berlaku sesuai konfigurasi dan tercatat pada audit |
| UAT-A06 | Semua | Keluar dari aplikasi | Session berakhir dan halaman internal tidak dapat dibuka tanpa login |

### B. Master data

| ID | Peran | Skenario | Hasil yang diharapkan |
|---|---|---|---|
| UAT-B01 | Administrator | Menambah kategori, merek, dan satuan | Data tersimpan dan dapat dipakai pada barang |
| UAT-B02 | Administrator | Menambah barang dengan barcode dan konversi satuan | Barang dapat dicari serta dipakai pada transaksi sesuai satuan |
| UAT-B03 | Penjualan | Menambah pelanggan | Pelanggan tersedia pada penawaran dan penjualan |
| UAT-B04 | Pembelian | Menambah pemasok | Pemasok tersedia pada pembelian |
| UAT-B05 | Gudang | Menambah gudang dan lokasi | Lokasi hanya tersedia pada cabang terkait |
| UAT-B06 | Administrator | Menghapus data yang sudah digunakan | Soft delete bekerja tanpa merusak transaksi historis |
| UAT-B07 | Administrator | Mencoba kode unik yang sama | Validasi menolak duplikasi |

### C. Persediaan

| ID | Peran | Skenario | Hasil yang diharapkan |
|---|---|---|---|
| UAT-C01 | Gudang | Mencatat stok awal | Saldo dan mutasi bertambah sesuai jumlah serta harga pokok |
| UAT-C02 | Gudang | Transfer stok antarlokasi | Mutasi keluar dan masuk seimbang, sumber serta tujuan berbeda |
| UAT-C03 | Gudang | Transfer melebihi stok tersedia | Ditolak tanpa menghasilkan mutasi parsial |
| UAT-C04 | Gudang | Stok opname dengan selisih | Selisih tercatat dan menghasilkan penyesuaian setelah persetujuan |
| UAT-C05 | Gudang | Penyesuaian barang rusak | Stok rusak dan tersedia berubah sesuai alasan |
| UAT-C06 | Pemilik | Melihat kartu stok | Urutan mutasi dan saldo berjalan dapat ditelusuri |
| UAT-C07 | Semua terkait | Mengganti cabang aktif | Stok cabang lain tidak tercampur |

### D. Pembelian dan hutang

| ID | Peran | Skenario | Hasil yang diharapkan |
|---|---|---|---|
| UAT-D01 | Pembelian | Membuat pesanan pembelian banyak item | Total dan jumlah item benar |
| UAT-D02 | Gudang | Menerima sebagian pesanan | Stok bertambah sesuai penerimaan, sisa pesanan tetap dapat ditelusuri |
| UAT-D03 | Pembelian | Membuat faktur tunai | Pembayaran dan status faktur benar |
| UAT-D04 | Pembelian | Membuat faktur tempo | Hutang pemasok terbentuk dengan jatuh tempo |
| UAT-D05 | Keuangan | Membayar sebagian hutang | Sisa hutang berkurang dan alokasi pembayaran benar |
| UAT-D06 | Pembelian | Retur pembelian | Stok dan nilai hutang/pembayaran mengikuti transaksi sumber |
| UAT-D07 | Pembelian | Membatalkan dokumen draf | Tidak ada mutasi atau jurnal yang tertinggal |

### E. Penjualan, POS, piutang, dan pengiriman

| ID | Peran | Skenario | Hasil yang diharapkan |
|---|---|---|---|
| UAT-E01 | Penjualan | Membuat penawaran dan pesanan | Nilai, masa berlaku, pelanggan, serta detail benar |
| UAT-E02 | Kasir | Penjualan tunai satu item | Stok berkurang, pembayaran serta kembalian benar |
| UAT-E03 | Kasir | Penjualan tunai banyak item | Header tidak terhitung ganda dan total detail benar |
| UAT-E04 | Kasir | Penjualan multi-metode | Total pembayaran sama dengan alokasi metode |
| UAT-E05 | Penjualan | Penjualan tempo | Piutang terbentuk dan batas kredit diperiksa |
| UAT-E06 | Keuangan | Pembayaran sebagian piutang | Sisa piutang dan status berubah tepat |
| UAT-E07 | Pengiriman | Menjadwalkan dan menyelesaikan pengiriman | Status, kendaraan, pengemudi, serta bukti dapat ditelusuri |
| UAT-E08 | Kasir | Retur penjualan | Stok, pembayaran/piutang, dan audit mengikuti transaksi sumber |
| UAT-E09 | Kasir | Cetak nota 80 mm | Barang, nilai, pembayaran, kembalian, dan identitas toko benar |
| UAT-E10 | Kasir | Menjual stok tidak mencukupi | Transaksi ditolak tanpa stok negatif tidak sah |

### F. Kas, bank, dan akuntansi

| ID | Peran | Skenario | Hasil yang diharapkan |
|---|---|---|---|
| UAT-F01 | Keuangan | Kas masuk | Saldo bertambah dan jurnal seimbang |
| UAT-F02 | Keuangan | Kas keluar | Saldo berkurang dan jurnal seimbang |
| UAT-F03 | Keuangan | Pindah kas/bank | Sumber dan tujuan berbeda, total perpindahan seimbang |
| UAT-F04 | Keuangan | Jurnal manual seimbang | Posting berhasil |
| UAT-F05 | Keuangan | Jurnal manual tidak seimbang | Posting ditolak |
| UAT-F06 | Pemilik | Rekonsiliasi saldo kas/bank | Saldo awal dan transaksi menghasilkan saldo yang sama dengan laporan |
| UAT-F07 | Keuangan | Membatalkan transaksi yang sudah disetujui | Ditolak sesuai kebijakan; koreksi mengikuti prosedur resmi |

### G. Lampiran dan audit

| ID | Peran | Skenario | Hasil yang diharapkan |
|---|---|---|---|
| UAT-G01 | Pengguna berizin | Mengunggah lampiran valid | Berkas tersimpan privat dan terhubung ke dokumen |
| UAT-G02 | Pengguna berizin | Mengunggah tipe/ukuran tidak diizinkan | Ditolak tanpa berkas sisa |
| UAT-G03 | Tanpa izin | Membuka URL lampiran | Ditolak |
| UAT-G04 | Auditor | Memfilter aktivitas | Hasil sesuai pengguna, modul, waktu, dan referensi |
| UAT-G05 | Auditor | Mengunduh audit | CSV sesuai filter dan aktivitas unduh tercatat |
| UAT-G06 | Administrator | Memeriksa perubahan hak akses | Data sebelum/sesudah serta pelaku tersedia |

### H. Dashboard, laporan, dan ekspor

| ID | Peran | Skenario | Hasil yang diharapkan |
|---|---|---|---|
| UAT-H01 | Pemilik | Membandingkan KPI dengan transaksi | Nilai sesuai periode dan cabang aktif |
| UAT-H02 | Pemilik | Transaksi multi-item pada dashboard | Total header tidak berganda |
| UAT-H03 | Pengguna laporan | Filter penjualan/pembelian | Dokumen draf dan batal tidak muncul |
| UAT-H04 | Gudang | Laporan persediaan | Stok, rusak, dipesan, tersedia, dan minimum benar |
| UAT-H05 | Keuangan | Laporan hutang/piutang | Sisa dan jatuh tempo sesuai view paten |
| UAT-H06 | Pengguna berizin | Ekspor CSV | Filter, UTF-8, delimiter, dan isolasi cabang benar |
| UAT-H07 | Tanpa izin unduh | Mengakses URL CSV | Ditolak |

### I. Produksi, backup, restore, dan rollback

| ID | Peran | Skenario | Hasil yang diharapkan |
|---|---|---|---|
| UAT-I01 | IT | Menjalankan pemeriksaan produksi ketat | Seluruh komponen kritis berhasil |
| UAT-I02 | IT | Mengakses `/up` dan `/kesiapan` | Status HTTP sesuai kesehatan infrastruktur tanpa kebocoran rahasia |
| UAT-I03 | IT | Membuat backup nyata | `.sql.gz` dan `.sha256` valid serta berizin privat |
| UAT-I04 | IT | Restore ke database staging kedua | 71 base table dan 3 view terpulihkan |
| UAT-I05 | IT | Deployment staging | Release dan symlink `current` berpindah atomik |
| UAT-I06 | IT | Rollback staging | Release lama aktif kembali tanpa rollback database otomatis |
| UAT-I07 | IT | Menjalankan timer backup | Backup terjadwal dan retensi bekerja |

### J. Pusat bantuan dan release candidate

| ID | Peran | Skenario | Hasil yang diharapkan |
|---|---|---|---|
| UAT-J01 | Semua | Membuka Pusat Bantuan | Halaman dapat dibuka setelah login dan memilih cabang |
| UAT-J02 | Kasir | Melihat panduan | Panduan penjualan terlihat, panduan administrasi akses tidak terlihat bila tidak berizin |
| UAT-J03 | Administrator | Melihat panduan | Seluruh panduan yang relevan terlihat |
| UAT-J04 | Semua | Mencari topik panduan | Kartu tersaring tanpa permintaan eksternal |
| UAT-J05 | IT | Membuat manifest RC | JSON terbentuk pada storage privat |
| UAT-J06 | IT | Memverifikasi manifest RC | Skema, permission, dan checksum valid |
| UAT-J07 | IT | Mengubah berkas kritis setelah manifest dibuat | Verifikasi checksum gagal |
| UAT-J08 | IT | Memeriksa isi manifest | Tidak ada kredensial atau data transaksi |

## 5. Kriteria keputusan

### LULUS

- seluruh skenario kritis lulus;
- tidak ada temuan KRITIS atau TINGGI terbuka;
- skema tetap 71 base table dan 3 view;
- permission tetap 98;
- manifest release candidate valid;
- backup dan restore staging berhasil;
- bukti UAT disimpan;
- pemilik menyetujui kandidat rilis.

### DIBLOKIR

- lingkungan staging belum tersedia;
- akun penguji atau data uji belum lengkap;
- perangkat printer atau backup belum tersedia;
- ketergantungan eksternal tidak dapat diuji.

### GAGAL

- terjadi kebocoran data antarcabang;
- pengguna tanpa izin dapat menjalankan aksi;
- stok, pembayaran, hutang, piutang, atau jurnal tidak konsisten;
- backup tidak dapat dipulihkan;
- manifest tidak valid;
- ada temuan KRITIS atau TINGGI yang belum ditutup.

## 6. Persetujuan

| Peran | Nama | Keputusan | Tanggal | Tanda tangan/catatan |
|---|---|---|---|---|
| Pemilik usaha |  |  |  |  |
| Penanggung jawab operasional |  |  |  |  |
| Penanggung jawab keuangan |  |  |  |  |
| Penanggung jawab gudang |  |  |  |  |
| Penanggung jawab IT |  |  |  |  |
