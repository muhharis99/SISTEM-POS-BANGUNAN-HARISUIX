# Fase 5 — Ringkasan Implementasi Pembelian dan Hutang Pemasok

## Status

**IMPLEMENTASI DIMULAI — BELUM LULUS.**

- Branch: `fase-5-pembelian-hutang`
- Target: `main`
- Pull request: Draft PR Fase 5
- Auto-merge: dilarang
- Fase 6: belum dimulai

Fase 5 hanya boleh dinyatakan lulus setelah implementasi selesai, seluruh CI otomatis hijau, checklist manual diterima, dan pemilik menyatakan eksplisit `Fase 5 lulus`.

## Integritas skema paten

Implementasi wajib menggunakan tepat 13 tabel pembelian dan hutang pemasok yang telah tersedia pada `struktur_database_toko_bangunan.sql`. Tidak boleh dibuat migration bisnis, tabel, kolom, index, foreign key, atau view tambahan.

## Modul yang dibangun

### 1. Permintaan pembelian

- CRUD dokumen draf dengan detail barang dan satuan.
- Pengajuan, persetujuan, penolakan, pembatalan, dan status diproses/selesai.
- Tingkat kepentingan serta tanggal kebutuhan.
- Jumlah diminta dan jumlah yang sudah dipesan selalu konsisten.

### 2. Pesanan pembelian

- Pemilihan pemasok, barang, pajak, harga, potongan, biaya pengiriman, dan biaya lain.
- Sumber detail dapat berasal dari permintaan pembelian.
- Persetujuan pesanan serta penerimaan sebagian/penuh.
- Total dokumen dihitung server-side dan tidak mempercayai total dari browser.

### 3. Penerimaan barang

- Penerimaan berdasarkan pesanan atau penerimaan langsung.
- Pencatatan lokasi gudang, jumlah diterima/ditolak, lot, produksi, kedaluwarsa, dan harga pokok.
- Saat status menjadi `DITERIMA`, stok bertambah melalui `LayananPersediaan` Fase 4 dan tercatat sebagai mutasi `PEMBELIAN`.
- Pembatalan tidak boleh meninggalkan saldo atau mutasi ganda.

### 4. Faktur dan hutang pemasok

- Faktur dapat mengacu pada pesanan dan penerimaan.
- Validasi nomor faktur pemasok unik per pemasok.
- Perhitungan total, pajak, potongan, pembulatan, pembayaran, dan sisa hutang dilakukan server-side.
- Faktur tempo yang disetujui membuat atau memperbarui satu baris `hutang_pemasok`.

### 5. Pembayaran hutang

- Satu pembayaran dapat dialokasikan ke beberapa hutang pemasok yang sama.
- Alokasi tidak boleh melebihi sisa hutang.
- Kas/bank dan metode pembayaran harus berada dalam cakupan yang sah.
- Persetujuan pembayaran memperbarui hutang serta status faktur secara atomik.

### 6. Retur pembelian

- Retur dapat mengacu pada faktur pembelian.
- Barang yang diretur mengurangi stok melalui layanan persediaan Fase 4 sebagai `RETUR_PEMBELIAN`.
- Penyelesaian retur mendukung potong hutang, tunai, transfer, atau barang pengganti.
- Retur tidak boleh melebihi jumlah yang pernah diterima/difakturkan dan belum diretur.

### 7. Laporan

- Daftar permintaan dan pesanan pembelian.
- Penerimaan barang serta selisih diterima/ditolak.
- Faktur pembelian dan pembelian per pemasok/barang/periode.
- Saldo hutang, hutang jatuh tempo, umur hutang, pembayaran, dan retur.
- Semua laporan dibatasi cabang serta permission pengguna.

## Standar teknis

- Seluruh operasi status dan keuangan memakai transaksi database serta penguncian baris yang relevan.
- Nomor dokumen memakai tabel paten `nomor_dokumen`.
- Audit aktivitas wajib dicatat.
- Soft delete hanya untuk dokumen yang masih boleh dihapus dan tidak pernah menghapus histori keuangan/persediaan yang telah disetujui.
- UI memakai komponen UBold/Nunito lokal yang sudah tersedia.
- Controller, service, command, route, view, dan test mengikuti pola Fase 2–Fase 4.
