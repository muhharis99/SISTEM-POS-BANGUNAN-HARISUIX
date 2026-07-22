# Fase 4 — Status

## Checkpoint

- Fase 1: lulus.
- Fase 2: lulus dan merged ke `main`.
- Fase 3: lulus dan merged ke `main`.
- Fase 4: implementasi Persediaan dan Mutasi Stok dimulai pada branch `fase-4-persediaan`.
- Fase 5: belum dimulai.

## Cakupan

- stok awal dan detail stok awal;
- saldo stok per cabang, gudang, lokasi, barang, dan satuan dasar;
- mutasi stok sebagai buku besar persediaan;
- transfer stok asal–tujuan dengan proses kirim dan terima terpisah;
- stok opname dan detail hitung fisik;
- penyesuaian stok masuk/keluar dengan alasan dan persetujuan;
- pemisahan stok rusak dari stok tersedia;
- laporan saldo, kartu stok, mutasi, dan stok tersedia;
- konversi kuantitas berdasarkan satuan barang;
- penguncian saldo serta transaksi database untuk mencegah race condition.

## Aturan bisnis utama

- Semua pergerakan stok wajib menghasilkan baris `mutasi_stok` dan memperbarui `saldo_stok` dalam transaksi database yang sama.
- Jumlah transaksi dikonversi ke satuan dasar sebelum saldo diperbarui.
- Transfer mengurangi gudang/lokasi asal saat dikirim dan menambah gudang/lokasi tujuan saat diterima.
- Stok rusak dipisahkan dari jumlah tersedia.
- Selisih stok opname diproses melalui penyesuaian yang memiliki alasan dan persetujuan.
- Saldo yang diperbarui harus dikunci untuk mencegah transaksi bersamaan memakai stok yang sama.

## Pengaman

- Tidak ada perubahan skema paten.
- Tidak ada tabel atau kolom tambahan di luar tabel internal `migrations`.
- Tidak ada CDN eksternal; UBold dan Nunito tetap lokal.
- Backup wajib dibuat sebelum migration/testing yang menyentuh data.
- Regression test Fase 1, Fase 2, dan Fase 3 tetap dijalankan.
- Draft PR Fase 4 tidak boleh di-merge atau diubah menjadi ready sebelum pemilik menyatakan eksplisit `Fase 4 lulus`.
- Auto-merge dilarang.
- Fase 5 tidak boleh dimulai tanpa instruksi terpisah.
