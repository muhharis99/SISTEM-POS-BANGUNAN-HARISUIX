# Fase 13 — Ringkasan Implementasi Operasional dan Hardening Pascapeluncuran

## Status

**IMPLEMENTASI DAN HARDENING TEKNIS SELESAI — SELURUH 18 WORKFLOW PADA CHECKPOINT TEKNIS BERHASIL — MENUNGGU PENERIMAAN PEMILIK.**

- Branch: `fase-13-operasional-pascapeluncuran`
- Pull request: Draft PR #16
- Target: `main`
- Checkpoint teknis: `7f86710f69e922377551a270d621fa955af5989a`
- Versi aplikasi: tetap `v1.0.0`
- Auto-merge: dilarang dan tidak digunakan
- Deployment otomatis: tidak dilakukan

## Tata kelola operasional

Fase 13 mengubah pekerjaan pascapeluncuran menjadi proses yang dapat ditriase, diukur, diverifikasi, dan diaudit.

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
- checklist manual serta workflow CI Fase 13;
- artefak diagnostik untuk kegagalan integration test Fase 6.

## Penyempurnaan input penjualan

Route POST berikut sekarang menggunakan `InputPenjualanFinalController` dan Form Request final:

- `/penjualan/penawaran`;
- `/penjualan/pesanan`;
- `/penjualan/transaksi`.

Route final dimuat setelah `routes/web.php` melalui callback routing Laravel, sehingga action akhir tidak lagi bergantung pada binding controller lama yang sulit diverifikasi.

## Penyempurnaan retur penjualan

### Nilai retur

Nilai retur sekarang:

- mengunci detail penjualan sumber;
- memverifikasi detail merupakan bagian dari penjualan pada cabang aktif;
- memverifikasi barang/satuan dan lokasi gudang;
- menghitung nilai proporsional dari `penjualan_detail.total_baris` dan jumlah sumber;
- mengabaikan harga yang dikirim browser;
- mencegah jumlah kumulatif retur melebihi jumlah yang pernah dijual.

### Potong piutang dan jurnal

- `POTONG_PIUTANG` hanya dapat digunakan bila piutang tersedia dan mencukupi.
- Penerimaan retur mengurangi piutang sesuai nilai server-side.
- Sistem membentuk jurnal retur yang seimbang dan berstatus `DIPOSTING`.
- Akun `410110 — Retur dan Potongan Penjualan` serta mapping `RETUR_PENJUALAN` disiapkan secara idempoten.
- Penyiapan mapping tidak mengubah tabel, permission, atau kontrak skema paten.

### Refund tunai/transfer

- Kas/bank wajib aktif dan berada pada cabang yang sama.
- Penerimaan retur membuat transaksi kas KELUAR berstatus DRAF tepat satu kali.
- Retur tidak dapat diselesaikan sebelum transaksi kas disetujui.
- Persetujuan kas menggunakan akun kontra retur yang telah dipetakan.

### Penggantian barang

Alur `PENGGANTI_BARANG` telah diselesaikan:

1. retur dibuat dengan nilai yang dihitung server-side;
2. retur disetujui dan barang yang layak jual masuk kembali ke stok;
3. sistem membuat pengiriman pengganti berstatus DRAF;
4. retur belum boleh selesai selama pengiriman belum diterima;
5. saat pengiriman diberangkatkan, stok barang pengganti berkurang secara atomik;
6. pencatatan stok bersifat idempoten sehingga retry tidak menggandakan mutasi;
7. bila pengiriman gagal setelah berangkat, stok dipulihkan tepat satu kali;
8. setelah barang diterima pelanggan, retur dapat diselesaikan.

Skema paten tidak memiliki ENUM khusus untuk mutasi barang pengganti. Karena itu sistem menggunakan:

- `jenis_mutasi = LAINNYA`;
- `jenis_dokumen = PENGGANTI_RETUR_KELUAR` untuk stok keluar;
- `jenis_dokumen = PENGGANTI_RETUR_BATAL` untuk pemulihan stok.

## Penyempurnaan pengiriman

Pengiriman sekarang memverifikasi:

- header penjualan dan pesanan berasal dari cabang aktif;
- bila keduanya dipilih, pesanan merupakan sumber penjualan;
- setiap detail berasal dari header terpilih;
- barang pada detail penjualan dan pesanan sama;
- hubungan detail pesanan–penjualan benar;
- jumlah aktif tidak melebihi sisa sumber;
- armada dan pengemudi aktif pada cabang yang sama;
- detail duplikat ditolak.

## Struktur implementasi final

- `bootstrap/app.php`
- `routes/fase13.php`
- `app/Http/Controllers/InputPenjualanFinalController.php`
- `app/Http/Controllers/PenjualanFinalController.php`
- `app/Http/Controllers/PenjualanOperasionalFinalController.php`
- `app/Http/Requests/Penjualan/SimpanPenawaranFinalRequest.php`
- `app/Http/Requests/Penjualan/SimpanPesananFinalRequest.php`
- `app/Http/Requests/Penjualan/SimpanPenjualanFinalRequest.php`
- `app/Http/Requests/Penjualan/SimpanPengirimanDiperkuatRequest.php`
- `app/Http/Requests/Penjualan/SimpanReturPenjualanDiperkuatRequest.php`
- `app/Console/Commands/SiapkanPenjualanFaseEnam.php`
- `tests/Feature/FaseTigaBelasRouteFinalTest.php`
- `tests/Feature/FaseTigaBelasHardeningPenjualanTest.php`
- `tests/Feature/FaseTigaBelasFinalisasiKekuranganTest.php`
- `.github/workflows/fase-6-test.yml`
- `scripts/verifikasi-issue-form.rb`
- `SECURITY.md`
- `SUPPORT.md`
- `.github/pull_request_template.md`

## Batasan SQL paten

Tidak ada perubahan pada:

- tabel, kolom, index, foreign key, atau migration bisnis;
- 71 base table dan 3 view paten;
- 98 permission aktif;
- versi aplikasi `v1.0.0`;
- larangan tabel infrastruktur Laravel.

## Hasil pengujian

Pada checkpoint teknis `7f86710f69e922377551a270d621fa955af5989a`:

- seluruh 18 workflow berhasil;
- route final dan seluruh Form Request terverifikasi;
- manipulasi harga retur tidak memengaruhi nilai server-side;
- detail pengiriman silang ditolak;
- refund kas mempunyai gate persetujuan keuangan;
- jurnal retur seimbang dan diposting;
- alur penggantian barang, stok keluar, pemulihan gagal, dan gate penyelesaian berhasil;
- Fase 1 sampai Fase 12 serta full regression suite berhasil;
- paket final, backup, smoke test, strict go-live gate, dan hypercare check CI berhasil;
- UBold/Nunito lokal dan larangan auto-merge berhasil diverifikasi.

## Batasan operasional

- Checklist operasional manusia belum diterima pemilik.
- Deployment staging/produksi nyata belum dilakukan.
- Tag dan GitHub Release final belum dibuat.
- SLA nyata, simulasi insiden manusia, serta hypercare produksi belum dijalankan.
- Pihak akuntansi tetap perlu memvalidasi konfigurasi dan kebijakan akun pada lingkungan produksi.

## Gate

Fase 13 belum dinyatakan lulus secara tata kelola. Draft PR #16 tidak boleh ditandai ready, di-merge, atau dilanjutkan ke Fase 14 sampai checklist operasional diterima dan pemilik menyatakan eksplisit `Fase 13 lulus`.
