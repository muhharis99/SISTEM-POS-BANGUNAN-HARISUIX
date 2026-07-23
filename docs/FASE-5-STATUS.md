# Fase 5 — Status

## Checkpoint

- Fase 1: lulus.
- Fase 2: lulus dan merged ke `main`.
- Fase 3: lulus dan merged ke `main`.
- Fase 4: lulus dan PR #5 merged ke `main`.
- Fase 5: **sedang dikerjakan** pada branch `fase-5-pembelian-hutang` melalui Draft PR #6.
- Fase 6: belum dimulai.

## Implementasi yang sudah masuk

- permission dan matriks peran pembelian/hutang;
- ringkasan pembelian dan hutang pemasok;
- permintaan pembelian: draf, ajukan, setujui, tolak, batalkan;
- pesanan pembelian: draf, ajukan, setujui, batalkan;
- penerimaan barang dengan mutasi stok `PEMBELIAN`;
- faktur tunai/tempo dan pembentukan hutang pemasok;
- pembayaran hutang dengan alokasi banyak faktur;
- retur pembelian dengan mutasi `RETUR_PEMBELIAN` dan potong hutang;
- isolasi cabang, audit aktivitas, transaksi database, dan penguncian baris;
- antarmuka UBold/Nunito lokal;
- workflow CI Fase 5 dan regression Fase 1–4.

## Integritas skema paten

- Menggunakan tepat 13 tabel pada bagian 5 SQL paten.
- Tidak menambah migration bisnis, tabel, kolom, index, foreign key, atau view.
- Target tetap 71 base table: 70 tabel bisnis dan `migrations`.
- Target tetap 3 view.

## Gate

- Draft PR #6 tidak boleh diubah menjadi ready atau di-merge sebelum seluruh CI hijau, checklist manual diterima, dan pemilik menyatakan eksplisit `Fase 5 lulus`.
- Auto-merge dilarang.
- Fase 6 tidak boleh dimulai tanpa instruksi terpisah.
