# Fase 13 — Checklist Pengujian Manual

Status: **belum diterima pemilik**.

> Tanda `[x]` hanya boleh diberikan berdasarkan bukti CI atau pengujian manual yang benar-benar dilakukan. Dokumen ini tidak mengklaim deployment produksi, SLA nyata, simulasi insiden manusia, atau persetujuan akuntansi telah dilakukan.

## Integritas proyek

- [ ] Pastikan Fase 1 sampai Fase 12 sudah berada pada `main`.
- [ ] Pastikan branch aktif `fase-13-operasional-pascapeluncuran`.
- [ ] Pastikan versi tetap `v1.0.0` selama belum ada maintenance release.
- [ ] Pastikan tetap 71 base table, 3 view, dan 98 permission aktif.
- [ ] Pastikan tidak ada migration bisnis atau perubahan SQL paten.
- [ ] Pastikan tidak ada tabel infrastruktur Laravel yang dilarang.
- [ ] Pastikan auto-merge tidak digunakan.

## Tata kelola repository

- [ ] Tinjau `SECURITY.md`.
- [ ] Tinjau `SUPPORT.md`.
- [ ] Pastikan `.github/pull_request_template.md` tampil pada PR baru.
- [ ] Pastikan laporan keamanan diarahkan ke kanal privat dan tidak meminta rahasia pada issue publik.
- [ ] Pastikan template PR memeriksa SQL paten, cabang, transaksi, locking, audit, test, rollback, dan expected head SHA.

## Issue Form

- [ ] Buka menu pembuatan issue dan pastikan blank issue dinonaktifkan.
- [ ] Pastikan form Laporan Bug atau Insiden dapat dibuka.
- [ ] Pastikan prioritas, modul, versi, lingkungan, reproduksi, hasil aktual, dan hasil harapan wajib diisi.
- [ ] Pastikan pengguna diperingatkan untuk tidak memasukkan data sensitif.
- [ ] Pastikan form Permintaan Perubahan dapat dibuka.
- [ ] Pastikan kriteria penerimaan dan potensi dampak dapat dicatat.
- [ ] Jalankan `ruby scripts/verifikasi-issue-form.rb`.
- [ ] Pastikan validator menolak id duplikat, option kosong, required nonboolean, contact link non-HTTPS, dan konfirmasi keamanan yang tidak wajib.

## Hardening retur penjualan

- [ ] Buat penjualan dengan diskon dan pajak sesuai data uji.
- [ ] Kirim nilai `harga_satuan` retur yang dimanipulasi dari browser.
- [ ] Pastikan sistem mengabaikan harga browser dan menghitung nilai proporsional dari `penjualan_detail.total_baris`.
- [ ] Pastikan detail dari penjualan lain ditolak.
- [ ] Pastikan jumlah kumulatif retur tidak melebihi jumlah penjualan.
- [ ] Pastikan lokasi retur harus berasal dari gudang dan cabang aktif.
- [ ] Pastikan barang non-BAIK tidak dapat ditandai layak dijual kembali.
- [ ] Pastikan `POTONG_PIUTANG` ditolak bila piutang tidak ada atau sisa piutang tidak cukup.
- [ ] Pastikan metode `PENGGANTI_BARANG` ditolak sampai alur pengiriman pengganti tersedia.

## Hardening pengiriman

- [ ] Pastikan detail penjualan harus berasal dari header penjualan yang dipilih.
- [ ] Pastikan detail pesanan harus berasal dari header pesanan yang dipilih.
- [ ] Bila header penjualan dan pesanan dipilih, pastikan keduanya saling terkait.
- [ ] Pastikan barang pada detail sumber sama dengan barang yang dikirim.
- [ ] Pastikan jumlah pengiriman aktif tidak melebihi sisa jumlah sumber.
- [ ] Pastikan armada dan pengemudi dari cabang lain ditolak.
- [ ] Pastikan detail duplikat dalam satu dokumen ditolak.

## Refund tunai/transfer

- [ ] Pastikan kas/bank wajib dipilih dan berasal dari cabang aktif.
- [ ] Terima retur tunai dan pastikan transaksi kas KELUAR berstatus DRAF dibuat tepat satu kali.
- [ ] Pastikan retur tidak dapat diselesaikan sebelum transaksi kas disetujui.
- [ ] Setujui transaksi kas melalui alur keuangan dan pastikan retur dapat diselesaikan.
- [ ] Tinjau pemetaan akun jurnal refund bersama pihak akuntansi sebelum digunakan pada produksi.

## Operasional dan SLA

- [ ] Tinjau `docs/OPERASIONAL-PASCAPELUNCURAN.md`.
- [ ] Tinjau `docs/SLA-DAN-DUKUNGAN.md`.
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
- [ ] Pastikan paket rilis, checksum, smoke test, go-live gate, dan regresi tetap digunakan.
- [ ] Pastikan tag/release/deployment hanya dilakukan setelah keputusan pemilik.

## Regresi dan CI

- [ ] Validasi Issue Form secara semantik.
- [ ] Jalankan syntax check Ruby/PHP dan Laravel Pint.
- [ ] Jalankan migration SQL paten pada MySQL 8.4.
- [ ] Verifikasi 71 base table, 3 view, 98 permission, dan nol tabel terlarang.
- [ ] Jalankan `FaseTigaBelasHardeningPenjualanTest`.
- [ ] Jalankan regresi Fase 12.
- [ ] Jalankan full regression suite.
- [ ] Pastikan UBold dan Nunito tetap lokal.
- [ ] Pastikan audit larangan auto-merge berhasil.

## Gate akhir

Fase 13 hanya boleh di-merge setelah seluruh CI pada head terbaru hijau, checklist operasional diterima, dan pemilik menyatakan eksplisit:

```text
Fase 13 lulus
```

Fase 14 tidak boleh dimulai dari `main` sebelum Fase 13 berhasil digabung.
