# Fase 13 — Status

## Checkpoint evaluasi dan hardening

- Fase 1 sampai Fase 12: lulus dan sudah digabung ke `main`.
- Fase 12 merge commit: `f9ff81a4b0e0e02fe05b61f03db95db10c8e5a6b`.
- Fase 13: **implementasi operasional dan hardening teknis sedang diverifikasi — belum lulus**.
- Branch: `fase-13-operasional-pascapeluncuran`.
- Pull request: Draft PR #16.
- Versi aplikasi tetap `v1.0.0` sampai maintenance release diterima pemilik.
- Auto-merge: dilarang dan tidak digunakan.
- Deployment otomatis ke server: tidak dilakukan.
- Fase 14: belum dimulai.

## Batasan yang dipertahankan

- Tidak menambah atau mengubah tabel, kolom, index, foreign key, migration bisnis, maupun view.
- Tetap 71 base table dan 3 view paten.
- Tidak menambah permission bisnis; total tetap 98 permission aktif.
- Tidak menambahkan tabel infrastruktur Laravel yang dilarang.
- Tidak menyimpan kredensial, data transaksi lengkap, backup, `.env`, atau bukti insiden sensitif pada repository.
- Tidak membuat maintenance release, tag, GitHub Release, atau deployment tanpa gate pemilik.

## Evaluasi ulang yang dilakukan

Evaluasi tidak berhenti pada dokumentasi. Kode transaksi penjualan Fase 6 ditinjau kembali karena area tersebut memengaruhi stok, piutang, kas, dan pengiriman.

Temuan yang dikonfirmasi:

1. nilai retur sebelumnya masih dapat dihitung dari `harga_satuan` yang dikirim browser;
2. detail pengiriman sebelumnya belum selalu dipastikan berasal dari header penjualan/pesanan yang dipilih;
3. pengembalian dana tunai/transfer sebelumnya belum memiliki gate transaksi kas/bank yang dapat diverifikasi;
4. metode `PENGGANTI_BARANG` belum memiliki alur pengiriman pengganti yang dapat diaudit;
5. repository belum memiliki `SECURITY.md`, `SUPPORT.md`, dan template PR operasional;
6. validasi Issue Form sebelumnya hanya memeriksa sintaks YAML, belum struktur semantik.

## Hardening yang diterapkan

- Form Request khusus pengiriman dan retur berisiko tinggi.
- Nilai retur dihitung proporsional dari `penjualan_detail.total_baris` dan jumlah sumber; harga dari browser tidak dipercaya.
- Detail pengiriman wajib berasal dari header dokumen yang dipilih, barang harus sama, dan hubungan detail pesanan–penjualan diverifikasi.
- Armada, pengemudi, kas/bank, gudang, lokasi, piutang, dan dokumen sumber dibatasi pada cabang aktif.
- Pengembalian TUNAI/TRANSFER membuat transaksi kas/bank KELUAR berstatus DRAF.
- Retur tunai/transfer tidak dapat diselesaikan sebelum transaksi kas/bank disetujui bagian keuangan.
- Metode `PENGGANTI_BARANG` ditolak sampai tersedia alur pengiriman pengganti yang dapat diaudit.
- `SECURITY.md`, `SUPPORT.md`, dan `.github/pull_request_template.md` ditambahkan.
- Validator semantik `scripts/verifikasi-issue-form.rb` ditambahkan.
- Integration test Fase 13 membuktikan manipulasi harga retur ditolak secara logis, detail pengiriman silang ditolak, dan refund tunai memiliki gate keuangan.

## Risiko tersisa yang dicatat

- Form Request baru mencakup jalur pengiriman dan retur berisiko tinggi; validasi inline lama pada penawaran, pesanan, dan transaksi penjualan belum seluruhnya dipindahkan.
- Transaksi kas refund memakai mekanisme transaksi kas Fase 7 yang sudah ada. Pemetaan jurnal khusus kontra-penjualan/retur belum ditambahkan karena memerlukan keputusan akuntansi dan gate terpisah.
- Tag serta GitHub Release `v1.0.0` belum dibuat karena tindakan tersebut tidak tersedia pada konektor yang digunakan.
- Deployment, SLA nyata, simulasi insiden manusia, dan hypercare produksi tidak diklaim telah dilakukan.

## Gate

Fase 13 tetap belum lulus sampai seluruh CI pada head hardening terbaru hijau, checklist operasional diterima, dan pemilik menyatakan eksplisit `Fase 13 lulus`. PR #16 harus tetap draft dan tidak boleh di-merge sebelum gate tersebut terpenuhi. Fase 14 tidak boleh dimulai dari `main` sebelum Fase 13 berhasil digabung.
