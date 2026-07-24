# Operasional Pascapeluncuran

Dokumen ini menjadi pedoman pengelolaan aplikasi setelah rilis `v1.0.0`. Pedoman tidak menggantikan runbook insiden, backup, restore, deployment, atau hypercare yang sudah tersedia.

## Tujuan

- menjaga stabilitas aplikasi tanpa mengubah SQL paten secara tidak terkendali;
- memastikan setiap bug, insiden, dan permintaan perubahan memiliki bukti serta penanggung jawab;
- memisahkan hotfix darurat, maintenance release, dan pengembangan fitur baru;
- mempertahankan isolasi cabang, hak akses, audit, integritas stok, serta integritas keuangan.

## Kanal masuk pekerjaan

Setiap pekerjaan baru harus dibuat sebagai GitHub Issue menggunakan template yang sesuai:

- **Laporan Bug/Insiden** untuk perilaku salah, kegagalan proses, error, atau potensi kehilangan integritas data;
- **Permintaan Perubahan** untuk penyempurnaan alur, laporan, tampilan, atau kebutuhan baru.

Rahasia, `.env`, token, kata sandi, backup database, data pasien/pelanggan lengkap, atau tangkapan layar berisi data sensitif tidak boleh ditempel langsung pada issue.

## Klasifikasi prioritas

### P0 — Kritis

- aplikasi tidak dapat digunakan oleh mayoritas pengguna;
- transaksi berpotensi merusak stok, kas, jurnal, hutang, atau piutang;
- kebocoran kredensial atau akses lintas cabang;
- kehilangan data aktif atau backup tidak dapat dipulihkan.

Tindakan: hentikan proses terdampak, aktifkan prosedur insiden, pertimbangkan maintenance mode, dan siapkan rollback aplikasi bila aman.

### P1 — Tinggi

- fungsi utama gagal tetapi terdapat jalan sementara yang aman;
- nota, pembayaran, penerimaan, pengiriman, atau posting jurnal tidak konsisten;
- performa sangat buruk pada operasi inti.

Tindakan: triase pada hari yang sama dan jadwalkan hotfix atau maintenance release terdekat.

### P2 — Sedang

- fungsi nonkritis bermasalah;
- laporan, filter, ekspor, atau tampilan tidak sesuai namun data tetap aman;
- terdapat jalan sementara yang jelas.

Tindakan: masukkan ke backlog maintenance release.

### P3 — Rendah

- penyempurnaan kosmetik;
- perubahan teks, dokumentasi, atau pengalaman pengguna kecil;
- ide peningkatan tanpa dampak operasional langsung.

Tindakan: evaluasi pada siklus perencanaan berikutnya.

## Alur triase

1. Validasi bahwa issue tidak memuat data sensitif.
2. Tentukan modul, cabang terdampak, versi, prioritas, dan tingkat reproduksi.
3. Bedakan bug aplikasi, masalah konfigurasi, masalah data, masalah server, atau kesalahan penggunaan.
4. Reproduksi pada lingkungan aman menggunakan data uji.
5. Tentukan jalur penanganan: dokumentasi, konfigurasi, hotfix, maintenance release, atau fase fitur baru.
6. Setiap perubahan kode wajib melalui branch, Draft PR, test relevan, dan review gate.
7. Tutup issue hanya setelah bukti verifikasi tersedia dan dampak operasional dicatat.

## Aturan perubahan

- SQL paten tidak boleh diubah tanpa keputusan pemilik dan fase khusus.
- Perubahan stok, hutang, piutang, kas, jurnal, atau isolasi cabang wajib memiliki regression test.
- Hotfix tidak boleh melewati Form Request, transaksi database, locking, audit, dan kontrol cabang yang relevan.
- Auto-merge tetap dilarang.
- Deployment produksi tidak dijalankan otomatis oleh repository.
- Rollback aplikasi tidak boleh otomatis melakukan rollback database.

## Bukti penutupan

Issue dianggap selesai bila tersedia:

- akar masalah;
- ruang lingkup dampak;
- commit/PR perbaikan atau tindakan konfigurasi;
- test yang dijalankan;
- hasil verifikasi;
- catatan deployment atau alasan tidak memerlukan deployment;
- risiko tersisa dan tindak lanjut.

## Evaluasi berkala

Lakukan review operasional minimal bulanan terhadap:

- jumlah issue per prioritas;
- issue berulang;
- waktu respons dan penyelesaian;
- kegagalan backup/restore;
- error 5xx dan readiness;
- performa query dan kapasitas disk;
- perubahan hak akses;
- temuan audit dan akses lintas cabang;
- kebutuhan maintenance release berikutnya.
