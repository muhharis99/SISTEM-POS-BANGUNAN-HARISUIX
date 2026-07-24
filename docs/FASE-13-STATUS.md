# Fase 13 — Status

## Checkpoint penyelesaian teknis

- Fase 1 sampai Fase 12: lulus dan sudah digabung ke `main`.
- Fase 12 merge commit: `f9ff81a4b0e0e02fe05b61f03db95db10c8e5a6b`.
- Fase 13: **implementasi dan hardening teknis selesai — menunggu penerimaan checklist operasional dan keputusan pemilik**.
- Checkpoint teknis tervalidasi: `7f86710f69e922377551a270d621fa955af5989a`.
- Seluruh 18 workflow pada checkpoint teknis tersebut berhasil.
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

## Temuan evaluasi yang telah dituntaskan

1. Nilai retur tidak lagi mempercayai `harga_satuan` dari browser; nilai dihitung server-side dari detail penjualan sumber.
2. Detail pengiriman wajib berasal dari header penjualan/pesanan yang dipilih dan dari cabang aktif.
3. Refund tunai/transfer memiliki gate transaksi kas/bank yang dapat diverifikasi.
4. Jalur `PENGGANTI_BARANG` sekarang memiliki dokumen pengiriman pengganti, mutasi stok keluar, gate penyelesaian, dan pemulihan stok bila pengiriman gagal.
5. Input penawaran, pesanan, dan transaksi penjualan menggunakan controller serta Form Request final.
6. Pemetaan akun kontra `RETUR_PENJUALAN` disiapkan secara idempoten tanpa mengubah skema maupun jumlah permission.
7. Repository memiliki `SECURITY.md`, `SUPPORT.md`, template PR operasional, Issue Form, dan validator semantik.
8. Workflow Fase 6 menyimpan artefak diagnostik test agar kegagalan regresi dapat ditelusuri.

## Implementasi final

- Route Fase 13 dimuat setelah route utama sehingga controller final menjadi action efektif.
- Form Request final diterapkan pada penawaran, pesanan, transaksi penjualan, pengiriman, dan retur berisiko tinggi.
- Harga retur dihitung proporsional dari `penjualan_detail.total_baris`; manipulasi harga browser tidak memengaruhi nilai retur.
- Retur `POTONG_PIUTANG` membentuk jurnal retur seimbang dan diposting.
- Refund TUNAI/TRANSFER membuat transaksi kas KELUAR berstatus DRAF dan retur baru dapat selesai setelah transaksi disetujui.
- `PENGGANTI_BARANG` membuat pengiriman pengganti berstatus DRAF.
- Saat barang pengganti diberangkatkan, stok berkurang secara atomik dan idempoten.
- Bila pengiriman pengganti gagal setelah berangkat, stok dipulihkan tepat satu kali.
- Retur penggantian barang tidak dapat diselesaikan sebelum pengiriman diterima pelanggan.
- Mutasi khusus penggantian barang menggunakan `jenis_mutasi = LAINNYA` dan kode proses pada `jenis_dokumen`, sesuai ENUM skema paten.

## Bukti pengujian otomatis

Pada checkpoint teknis `7f86710f69e922377551a270d621fa955af5989a`:

- seluruh 18 workflow berhasil;
- syntax check PHP/Ruby, Laravel Pint, dan pemeriksaan shell berhasil;
- migration pada MySQL 8.4 berhasil;
- skema tetap 71 base table, 3 view, 98 permission, dan nol tabel infrastruktur terlarang;
- test route final dan hardening Fase 13 berhasil;
- test finalisasi penjualan, jurnal retur, refund, dan penggantian barang berhasil;
- regresi Fase 1 sampai Fase 12 berhasil;
- full regression suite berhasil;
- paket final, backup, smoke test, strict go-live gate, dan hypercare check dalam lingkungan CI berhasil;
- verifikasi UBold/Nunito lokal dan audit larangan auto-merge berhasil.

## Batasan operasional tersisa

- Checklist manual operasional belum diterima pemilik.
- Deployment staging/produksi nyata belum dilakukan.
- Tag dan GitHub Release final belum dibuat.
- SLA nyata, simulasi insiden manusia, serta hypercare produksi belum dijalankan dan tidak diklaim selesai.
- Konfigurasi akun dan prosedur akuntansi produksi tetap perlu ditinjau pihak akuntansi sebelum go-live nyata.

## Gate

Fase 13 tetap berstatus belum lulus secara tata kelola sampai checklist operasional diterima dan pemilik menyatakan eksplisit `Fase 13 lulus`. PR #16 harus tetap Draft dan tidak boleh di-merge sebelum keputusan tersebut. Fase 14 tidak boleh dimulai dari `main` sebelum Fase 13 berhasil digabung secara manual dengan expected head SHA terbaru.
