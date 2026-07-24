# Release Notes — v1.0.0-rc1

Tanggal kandidat: **24 Juli 2026**

Status: **Release Candidate — belum merupakan rilis produksi final**.

## Ringkasan

`v1.0.0-rc1` merupakan kandidat rilis pertama Sistem POS Toko Bangunan HARISUIX setelah penyelesaian Fase 1 sampai Fase 10. Kandidat ini mencakup alur operasional utama toko bangunan, kontrol cabang dan hak akses, audit, laporan, serta perangkat kesiapan produksi.

## Modul utama

1. Autentikasi, pengguna, peran, hak akses, dan cabang aktif.
2. Master barang, satuan, barcode, pelanggan, pemasok, gudang, harga, pajak, armada, kas, dan bank.
3. Persediaan, mutasi, stok awal, transfer, opname, serta penyesuaian.
4. Pembelian, penerimaan, faktur, retur, hutang, dan pembayaran pemasok.
5. Penawaran, pesanan, POS, penjualan, pembayaran, piutang, pengiriman, dan retur.
6. Kas, bank, daftar akun, pemetaan akun, transaksi kas, dan jurnal umum.
7. Lampiran privat dan audit aktivitas.
8. Dashboard, laporan operasional, CSV, serta nota 80 mm.
9. Readiness, backup, restore, deployment release, rollback, dan runbook produksi.
10. Pusat bantuan serta manifest release candidate.

## Cara membuat manifest kandidat rilis

```bash
php artisan sistem:buat-manifest-rilis v1.0.0-rc1
php artisan sistem:verifikasi-manifest-rilis storage/app/release-candidate/manifest-rilis.json
```

Manifest harus menunjukkan:

- 71 base table;
- 3 view;
- 98 permission aktif;
- 0 tabel infrastruktur Laravel yang dilarang;
- seluruh checksum berkas kritis valid.

## Prasyarat UAT

- database staging terpisah dari produksi;
- data uji yang tidak memuat data sensitif nyata;
- akun per peran bawaan;
- printer nota atau print preview;
- lokasi penyimpanan backup privat;
- browser desktop yang didukung;
- domain staging HTTPS bila pengujian server dilakukan.

## Risiko yang harus diperiksa

- konsistensi stok ketika transaksi dibatalkan atau diretur;
- perhitungan total transaksi banyak item;
- isolasi data antarcabang;
- pembatasan akses berdasarkan peran;
- transaksi tempo, hutang, piutang, dan alokasi pembayaran;
- keseimbangan jurnal;
- keamanan lampiran dan ekspor;
- backup, restore, deployment, serta rollback pada staging.

## Keputusan rilis

Kandidat hanya dapat dinaikkan menjadi rilis final setelah:

1. seluruh CI Fase 11 hijau;
2. matriks UAT selesai dan bukti penerimaan disimpan;
3. temuan kritis dan tinggi ditutup;
4. backup serta restore staging berhasil;
5. manifest release candidate valid;
6. pemilik menyatakan eksplisit `Fase 11 lulus`;
7. tag atau GitHub Release dibuat melalui keputusan terpisah setelah merge.

## Batasan

- Dokumen ini tidak membuktikan deployment produksi nyata.
- Repository tidak menjalankan deployment otomatis.
- Tag dan GitHub Release final belum dibuat.
- SQL paten tidak diubah.
