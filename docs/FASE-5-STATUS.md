# Fase 5 — Status

## Checkpoint

- Fase 1: lulus.
- Fase 2: lulus dan merged ke `main`.
- Fase 3: lulus dan merged ke `main`.
- Fase 4: lulus dan merged ke `main` melalui PR #5.
- Fase 5: dimulai pada branch `fase-5-pembelian-hutang`.
- Fase 6: belum dimulai.

## Cakupan

Fase 5 membangun modul Pembelian dan Hutang Pemasok menggunakan tepat 13 tabel yang sudah tersedia pada SQL paten:

- `permintaan_pembelian` dan `permintaan_pembelian_detail`;
- `pesanan_pembelian` dan `pesanan_pembelian_detail`;
- `penerimaan_barang` dan `penerimaan_barang_detail`;
- `faktur_pembelian` dan `faktur_pembelian_detail`;
- `hutang_pemasok`;
- `pembayaran_hutang` dan `pembayaran_hutang_detail`;
- `retur_pembelian` dan `retur_pembelian_detail`.

## Alur sasaran

1. Permintaan pembelian dibuat, diajukan, disetujui/ditolak, lalu diproses.
2. Pesanan pembelian dibuat untuk pemasok dan dapat menerima barang sebagian atau seluruhnya.
3. Penerimaan barang menambah saldo serta mutasi stok melalui layanan persediaan Fase 4.
4. Faktur pembelian menghasilkan hutang pemasok untuk transaksi tempo.
5. Pembayaran hutang dialokasikan ke satu atau beberapa hutang dan memperbarui saldo hutang.
6. Retur pembelian mengurangi stok dan menyelesaikan pengembalian melalui potong hutang, tunai, transfer, atau barang pengganti.
7. Laporan pembelian, penerimaan, hutang, jatuh tempo, pembayaran, dan retur tersedia sesuai hak akses serta cabang pengguna.

## Pengaman

- Skema database paten tidak boleh diubah.
- Tidak boleh menambah tabel/kolom bisnis atau tabel infrastruktur Laravel di luar `migrations`.
- Seluruh mutasi persediaan wajib memakai layanan persediaan Fase 4 dan transaksi database.
- Validasi jumlah mengikuti satuan serta `satuan.jumlah_desimal`.
- Semua endpoint dilindungi permission server-side dan isolasi cabang.
- Backup wajib dibuat sebelum migration/setup/testing yang menyentuh data.
- Regression test Fase 1–Fase 4 wajib tetap dijalankan.
- Asset tetap UBold dan Nunito lokal tanpa CDN eksternal.
- Draft PR Fase 5 tidak boleh di-merge atau diubah menjadi ready sebelum pemilik menyatakan eksplisit `Fase 5 lulus`.
- Auto-merge dilarang.
- Fase 6 tidak boleh dimulai tanpa instruksi terpisah.
