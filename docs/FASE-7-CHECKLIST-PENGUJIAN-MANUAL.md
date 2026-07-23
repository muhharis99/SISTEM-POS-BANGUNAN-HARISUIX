# Fase 7 — Checklist Pengujian Manual

Status: **belum diterima pemilik**.

## Persiapan

- [ ] Jalankan migration SQL paten pada database kosong.
- [ ] Jalankan setup administrator Fase 2 serta setup Fase 3 sampai Fase 7.
- [ ] Pastikan tetap 71 base table dan 3 view.
- [ ] Pastikan 89 permission aktif dan 14 permission Fase 7 tanpa duplikasi.
- [ ] Pastikan tidak ada tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, atau `password_reset_tokens`.

## Bagan akun

- [ ] Buka menu Kas & Akuntansi dan pastikan tab Bagan Akun tampil.
- [ ] Tambahkan akun induk baru.
- [ ] Tambahkan akun rincian di bawah akun induk.
- [ ] Uji kode akun duplikat dan pastikan ditolak.
- [ ] Uji akun menjadi induk dirinya sendiri dan pastikan ditolak.
- [ ] Uji relasi induk yang membentuk siklus dan pastikan ditolak.
- [ ] Pastikan akun yang masih mempunyai anak tidak dapat diubah menjadi akun rincian.

## Pemetaan akun

- [ ] Pastikan pemetaan global bawaan tersedia.
- [ ] Pastikan setiap kas/bank aktif memiliki pemetaan `KAS_BANK_{id}` ke akun rincian yang berbeda.
- [ ] Tambahkan atau ubah pemetaan akun khusus cabang.
- [ ] Pastikan pemetaan cabang diprioritaskan dibanding pemetaan global.
- [ ] Pastikan akun induk/nonrincian ditolak sebagai tujuan pemetaan.

## Transaksi kas masuk

- [ ] Buat transaksi kas masuk sebagai draf.
- [ ] Pastikan draf belum mengubah saldo kas dan belum masuk laporan.
- [ ] Setujui transaksi dan pastikan status menjadi `DISETUJUI`.
- [ ] Pastikan terbentuk satu jurnal otomatis berstatus `DIPOSTING`.
- [ ] Pastikan akun kas/bank didebet dan akun lawan dikredit dengan nilai yang sama.
- [ ] Pastikan saldo berjalan kas/bank bertambah sesuai transaksi.

## Transaksi kas keluar

- [ ] Buat transaksi kas keluar dengan kategori biaya.
- [ ] Setujui transaksi.
- [ ] Pastikan akun beban didebet dan kas/bank dikredit.
- [ ] Pastikan saldo kas/bank berkurang.
- [ ] Pastikan nilai nol atau negatif ditolak.

## Pemindahan kas/bank

- [ ] Buat transaksi pindah dari kas ke bank.
- [ ] Pastikan sumber dan tujuan yang sama ditolak.
- [ ] Setujui transaksi.
- [ ] Pastikan akun tujuan didebet dan akun sumber dikredit.
- [ ] Pastikan saldo sumber berkurang dan saldo tujuan bertambah.
- [ ] Pastikan total saldo gabungan tidak berubah akibat pemindahan internal.

## Jurnal umum manual

- [ ] Buat jurnal minimal dua baris yang seimbang.
- [ ] Pastikan jurnal tersimpan sebagai `DRAF`.
- [ ] Pastikan jurnal draf belum memengaruhi laporan.
- [ ] Posting jurnal dan pastikan status menjadi `DIPOSTING`.
- [ ] Pastikan jurnal yang telah diposting masuk neraca saldo.
- [ ] Uji jurnal tidak seimbang dan pastikan seluruh proses ditolak atomik.
- [ ] Uji satu baris berisi debet sekaligus kredit dan pastikan ditolak.
- [ ] Uji jurnal dengan akun induk dan pastikan ditolak.

## Laporan

- [ ] Uji filter tanggal awal dan tanggal akhir.
- [ ] Pastikan transaksi di luar periode tidak muncul.
- [ ] Cocokkan saldo kas/bank dengan saldo awal + masuk - keluar.
- [ ] Cocokkan total debet dan kredit pada neraca saldo.
- [ ] Cocokkan pendapatan, beban, dan laba/rugi periode.
- [ ] Periksa perbandingan aset dengan kewajiban + modal + laba/rugi.
- [ ] Pastikan hanya jurnal `DIPOSTING` yang masuk laporan.

## Keamanan dan regresi

- [ ] Uji endpoint tanpa permission menghasilkan 403.
- [ ] Uji manipulasi ID transaksi/jurnal cabang lain menghasilkan 404/403.
- [ ] Uji penyetujuan ganda dan posting ganda ditolak.
- [ ] Jalankan regression test Fase 1 sampai Fase 6.
- [ ] Pastikan aset UBold/Nunito tetap lokal dan tidak ada CDN eksternal.
- [ ] Pastikan tidak ada auto-merge.

## Gate akhir

Fase 7 hanya boleh di-merge setelah seluruh CI hijau, checklist ini diterima, dan pemilik menyatakan eksplisit:

```text
Fase 7 lulus
```
