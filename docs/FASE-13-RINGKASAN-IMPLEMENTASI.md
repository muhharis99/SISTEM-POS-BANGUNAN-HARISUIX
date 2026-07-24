# Fase 13 — Ringkasan Implementasi Operasional dan Hardening Pascapeluncuran

## Status

**IMPLEMENTASI OPERASIONAL DAN HARDENING TEKNIS SELESAI DIKEMBANGKAN — MENUNGGU VERIFIKASI CI TERBARU DAN BELUM LULUS.**

- Branch: `fase-13-operasional-pascapeluncuran`
- Pull request: Draft PR #16
- Target: `main`
- Versi aplikasi: tetap `v1.0.0`
- Auto-merge: dilarang dan tidak digunakan
- Deployment otomatis: tidak dilakukan

## Tata kelola operasional

Fase 13 mengubah pekerjaan pascapeluncuran dari komunikasi bebas menjadi proses yang dapat ditriase, diukur, diverifikasi, dan diaudit.

Implementasi meliputi:

- panduan operasional pascapeluncuran;
- SLA respons dan penanganan P0 sampai P3;
- alur triase, reproduksi, mitigasi, perbaikan, verifikasi, deployment, dan penutupan;
- kebijakan hotfix serta maintenance release;
- Issue Form laporan bug/insiden dan permintaan perubahan;
- blank issue dinonaktifkan;
- kebijakan keamanan `SECURITY.md`;
- panduan dukungan `SUPPORT.md`;
- template pull request operasional;
- validator semantik Issue Form;
- checklist manual serta workflow CI Fase 13.

## Evaluasi kode transaksi

Evaluasi ulang menemukan kekurangan nyata pada modul penjualan lama:

### Nilai retur

Sebelumnya nilai retur menggunakan `harga_satuan` yang dikirim browser. Implementasi diperkuat sekarang:

- mengunci detail penjualan sumber;
- memverifikasi detail merupakan bagian dari penjualan pada cabang aktif;
- memverifikasi barang/satuan sama dengan sumber;
- menghitung nilai retur proporsional dari `total_baris` sumber;
- mengabaikan nilai harga yang dikirim browser;
- mencegah total jumlah retur melebihi jumlah yang pernah dijual.

### Konsistensi pengiriman

Pengiriman sekarang memverifikasi:

- header penjualan dan pesanan berasal dari cabang aktif;
- bila keduanya dipilih, pesanan merupakan sumber penjualan;
- setiap detail benar-benar berasal dari header terpilih;
- barang pada detail penjualan dan pesanan sama;
- hubungan detail pesanan–penjualan benar;
- jumlah yang telah dan akan dikirim tidak melebihi sumber;
- armada dan pengemudi aktif pada cabang yang sama.

### Pengembalian dana

- `POTONG_PIUTANG` hanya dapat digunakan bila piutang aktif tersedia dan cukup.
- `TUNAI` atau `TRANSFER` wajib memilih kas/bank cabang aktif.
- Saat retur diterima, sistem membuat transaksi kas/bank KELUAR berstatus DRAF.
- Retur belum dapat diselesaikan sebelum transaksi kas tersebut berstatus DISETUJUI.
- `PENGGANTI_BARANG` ditolak karena alur pengiriman pengganti yang dapat diaudit belum tersedia.

## Struktur implementasi

- `app/Http/Requests/Penjualan/SimpanPengirimanDiperkuatRequest.php`
- `app/Http/Requests/Penjualan/SimpanReturPenjualanDiperkuatRequest.php`
- `app/Http/Controllers/PenjualanDiperkuatController.php`
- binding controller pada `app/Providers/AppServiceProvider.php`
- `tests/Feature/FaseTigaBelasHardeningPenjualanTest.php`
- `scripts/verifikasi-issue-form.rb`
- `SECURITY.md`
- `SUPPORT.md`
- `.github/pull_request_template.md`

Route lama tetap digunakan. Container Laravel mengarahkan `PenjualanController` ke controller diperkuat sehingga perubahan berisiko tinggi dapat diterapkan tanpa menggandakan atau mengubah struktur route bisnis.

## Batasan SQL paten

Tidak ada perubahan pada:

- tabel, kolom, index, foreign key, atau migration bisnis;
- 71 base table dan 3 view paten;
- 98 permission aktif;
- versi aplikasi `v1.0.0`;
- larangan tabel infrastruktur Laravel.

## Batasan yang belum diselesaikan

- Validasi inline pada jalur penawaran, pesanan, dan transaksi penjualan lama belum seluruhnya dipindah ke Form Request.
- Persetujuan transaksi kas refund masih memakai mekanisme jurnal kas keluar Fase 7. Akun kontra-penjualan/retur khusus membutuhkan keputusan akuntansi dan fase perubahan terpisah.
- Penggantian barang belum tersedia dan sengaja ditolak agar tidak menghasilkan proses setengah jadi.
- Tag/GitHub Release final serta deployment produksi tetap belum dilakukan dari lingkungan ini.

## Gate

Fase 13 tetap belum lulus. Draft PR #16 tidak boleh di-merge sampai seluruh workflow pada head hardening terbaru hijau, checklist operasional diterima, dan pemilik menyatakan eksplisit `Fase 13 lulus`.
