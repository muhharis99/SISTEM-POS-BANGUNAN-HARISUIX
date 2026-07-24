# Fase 13 — Checklist Pengujian Manual

Status: **bukti teknis CI lengkap; checklist operasional belum diterima pemilik**.

> Tanda `[x]` diberikan bila terdapat bukti CI atau pemeriksaan repository yang benar-benar berhasil. Item yang memerlukan keputusan, simulasi manusia, atau lingkungan produksi tetap `[ ]`.

## Integritas proyek

- [x] Fase 1 sampai Fase 12 berada pada `main`.
- [x] Branch aktif `fase-13-operasional-pascapeluncuran`.
- [x] Versi tetap `v1.0.0` selama belum ada maintenance release.
- [x] Tetap 71 base table, 3 view, dan 98 permission aktif.
- [x] Tidak ada migration bisnis atau perubahan SQL paten.
- [x] Tidak ada tabel infrastruktur Laravel yang dilarang.
- [x] Auto-merge tidak digunakan.

## Tata kelola repository

- [x] `SECURITY.md` tersedia dan mengarahkan laporan kerentanan ke kanal privat.
- [x] `SUPPORT.md` tersedia.
- [x] `.github/pull_request_template.md` tersedia.
- [x] Template PR memeriksa SQL paten, cabang, transaksi, locking, audit, test, rollback, dan expected head SHA.
- [ ] Pemilik meninjau dan menerima isi dokumen keamanan, dukungan, serta template PR.

## Issue Form

- [x] Blank issue dinonaktifkan.
- [x] Form Laporan Bug atau Insiden tersedia.
- [x] Prioritas, modul, versi, lingkungan, reproduksi, hasil aktual, dan hasil harapan divalidasi.
- [x] Pengguna diperingatkan untuk tidak memasukkan data sensitif.
- [x] Form Permintaan Perubahan tersedia.
- [x] Kriteria penerimaan dan potensi dampak dapat dicatat.
- [x] `ruby scripts/verifikasi-issue-form.rb` berhasil.
- [x] Validator memeriksa id duplikat, option kosong, required nonboolean, contact link non-HTTPS, dan konfirmasi keamanan.
- [ ] Pemilik mencoba kedua Issue Form melalui antarmuka GitHub.

## Hardening input penjualan

- [x] POST penawaran menggunakan `InputPenjualanFinalController` dan Form Request final.
- [x] POST pesanan menggunakan `InputPenjualanFinalController` dan Form Request final.
- [x] POST transaksi penjualan menggunakan `InputPenjualanFinalController` dan Form Request final.
- [x] Route final dimuat setelah route utama dan terverifikasi oleh integration test.

## Hardening retur penjualan

- [x] Sistem mengabaikan harga retur yang dimanipulasi dari browser.
- [x] Nilai retur dihitung proporsional dari `penjualan_detail.total_baris`.
- [x] Detail dari penjualan lain ditolak.
- [x] Jumlah kumulatif retur tidak dapat melebihi jumlah penjualan.
- [x] Lokasi retur wajib berasal dari gudang dan cabang aktif.
- [x] Barang non-BAIK tidak dapat ditandai layak dijual kembali.
- [x] `POTONG_PIUTANG` ditolak bila piutang tidak tersedia atau tidak mencukupi.
- [x] Jurnal retur potong piutang seimbang dan berstatus `DIPOSTING`.
- [ ] Pemilik mengulang skenario retur menggunakan antarmuka aplikasi dan data uji operasional.

## Penggantian barang

- [x] `PENGGANTI_BARANG` dapat dibuat tanpa mewajibkan kas/bank.
- [x] Penerimaan retur membuat pengiriman pengganti berstatus DRAF.
- [x] Barang retur layak jual masuk kembali ke stok.
- [x] Retur belum dapat diselesaikan sebelum pengiriman pengganti diterima.
- [x] Pemberangkatan pengiriman mengurangi stok secara atomik.
- [x] Retry tidak menggandakan mutasi stok.
- [x] Pengiriman gagal setelah berangkat memulihkan stok tepat satu kali.
- [x] Pengiriman diterima membuka gate penyelesaian retur.
- [x] Mutasi menggunakan `jenis_mutasi = LAINNYA` dan kode proses pada `jenis_dokumen`, sesuai skema paten.
- [ ] Pemilik menguji alur penggantian barang secara manual dari UI sampai selesai.

## Hardening pengiriman

- [x] Detail penjualan harus berasal dari header penjualan yang dipilih.
- [x] Detail pesanan harus berasal dari header pesanan yang dipilih.
- [x] Bila header penjualan dan pesanan dipilih, keduanya wajib saling terkait.
- [x] Barang pada detail sumber wajib sama dengan barang yang dikirim.
- [x] Jumlah pengiriman aktif tidak dapat melebihi sisa sumber.
- [x] Armada dan pengemudi dari cabang lain ditolak.
- [x] Detail duplikat dalam satu dokumen ditolak.
- [ ] Pemilik menguji pengiriman reguler dan pengiriman pengganti dari UI.

## Refund tunai/transfer dan akuntansi

- [x] Kas/bank wajib dipilih dan berasal dari cabang aktif.
- [x] Penerimaan retur membuat transaksi kas KELUAR berstatus DRAF tepat satu kali.
- [x] Retur tidak dapat diselesaikan sebelum transaksi kas disetujui.
- [x] Setelah transaksi kas disetujui, retur dapat diselesaikan.
- [x] Akun `410110 — Retur dan Potongan Penjualan` tersedia.
- [x] Mapping `RETUR_PENJUALAN` disiapkan secara idempoten.
- [x] Penambahan mapping tidak mengubah skema atau jumlah permission.
- [ ] Pihak akuntansi meninjau kode akun, saldo normal, dan kebijakan posting untuk produksi.

## Operasional dan SLA

- [x] `docs/OPERASIONAL-PASCAPELUNCURAN.md` tersedia.
- [x] `docs/SLA-DAN-DUKUNGAN.md` tersedia.
- [ ] Tetapkan jam layanan nyata.
- [ ] Tetapkan kanal eskalasi P0 yang tidak disimpan di repository publik.
- [ ] Tetapkan PIC pemilik keputusan, aplikasi, database, infrastruktur, dan operasional.
- [ ] Pastikan target respons P0–P3 sesuai kemampuan tim.
- [ ] Simulasikan triase satu bug dan satu permintaan perubahan menggunakan data uji.
- [ ] Pastikan issue tidak ditutup tanpa akar masalah, bukti verifikasi, dan risiko tersisa.

## Hotfix dan maintenance release

- [ ] Simulasikan keputusan apakah masalah membutuhkan dokumentasi, konfigurasi, hotfix, maintenance release, atau fase fitur baru.
- [ ] Pastikan hotfix P0/P1 tetap menggunakan branch, Draft PR, test, backup, rollback, dan expected head SHA.
- [ ] Pastikan maintenance release memperbarui changelog dan release notes.
- [x] Paket rilis, checksum, backup, smoke test, strict go-live gate, dan hypercare check berhasil pada CI.
- [ ] Pastikan tag, GitHub Release, dan deployment hanya dilakukan setelah keputusan pemilik.

## Regresi dan CI

Checkpoint teknis: `7f86710f69e922377551a270d621fa955af5989a`.

- [x] Validasi Issue Form secara semantik.
- [x] Syntax check Ruby/PHP, shell, dan Laravel Pint.
- [x] Migration SQL paten pada MySQL 8.4.
- [x] Verifikasi 71 base table, 3 view, 98 permission, dan nol tabel terlarang.
- [x] `FaseTigaBelasRouteFinalTest`.
- [x] `FaseTigaBelasHardeningPenjualanTest`.
- [x] `FaseTigaBelasFinalisasiKekuranganTest`.
- [x] Regresi Fase 1 sampai Fase 12.
- [x] Full regression suite.
- [x] UBold dan Nunito tetap lokal.
- [x] Audit larangan auto-merge.
- [x] Seluruh 18 workflow berhasil.

## Gate akhir

Gate teknis telah terpenuhi. Fase 13 hanya boleh di-merge setelah checklist operasional diterima dan pemilik menyatakan eksplisit:

```text
Fase 13 lulus
```

Setelah pernyataan tersebut, PR #16 dapat ditandai ready-for-review dan digabung secara manual menggunakan expected head SHA terbaru. Fase 14 tidak boleh dimulai dari `main` sebelum Fase 13 berhasil digabung.
