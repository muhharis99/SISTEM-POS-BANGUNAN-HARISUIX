# Fase 7 — Checklist Pengujian Manual

Status: **DITERIMA PEMILIK — FASE 7 LULUS pada 23 Juli 2026 berdasarkan keputusan eksplisit pemilik proyek.**

Checklist berikut dicatat diterima sebagai checkpoint manual pemilik. Pencatatan ini tidak mengubah skema paten dan tidak menyatakan bahwa pengujian browser dilakukan oleh agen.

## Persiapan

- [x] Jalankan migration SQL paten pada database kosong.
- [x] Jalankan setup administrator Fase 2 serta setup Fase 3 sampai Fase 7.
- [x] Pastikan tetap 71 base table dan 3 view.
- [x] Pastikan 89 permission aktif dan 14 permission Fase 7 tanpa duplikasi.
- [x] Pastikan tidak ada tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, atau `password_reset_tokens`.

## Bagan akun

- [x] Buka menu Kas & Akuntansi dan pastikan tab Bagan Akun tampil.
- [x] Tambahkan akun induk baru.
- [x] Tambahkan akun rincian di bawah akun induk.
- [x] Uji kode akun duplikat dan pastikan ditolak.
- [x] Uji akun menjadi induk dirinya sendiri dan pastikan ditolak.
- [x] Uji relasi induk yang membentuk siklus dan pastikan ditolak.
- [x] Pastikan akun yang masih mempunyai anak tidak dapat diubah menjadi akun rincian.

## Pemetaan akun

- [x] Pastikan pemetaan global bawaan tersedia.
- [x] Pastikan setiap kas/bank aktif memiliki pemetaan `KAS_BANK_{id}` ke akun rincian yang berbeda.
- [x] Tambahkan atau ubah pemetaan akun khusus cabang.
- [x] Pastikan pemetaan cabang diprioritaskan dibanding pemetaan global.
- [x] Pastikan akun induk/nonrincian ditolak sebagai tujuan pemetaan.

## Transaksi kas masuk

- [x] Buat transaksi kas masuk sebagai draf.
- [x] Pastikan draf belum mengubah saldo kas dan belum masuk laporan.
- [x] Setujui transaksi dan pastikan status menjadi `DISETUJUI`.
- [x] Pastikan terbentuk satu jurnal otomatis berstatus `DIPOSTING`.
- [x] Pastikan akun kas/bank didebet dan akun lawan dikredit dengan nilai yang sama.
- [x] Pastikan saldo berjalan kas/bank bertambah sesuai transaksi.

## Transaksi kas keluar

- [x] Buat transaksi kas keluar dengan kategori biaya.
- [x] Setujui transaksi.
- [x] Pastikan akun beban didebet dan kas/bank dikredit.
- [x] Pastikan saldo kas/bank berkurang.
- [x] Pastikan nilai nol atau negatif ditolak.

## Pemindahan kas/bank

- [x] Buat transaksi pindah dari kas ke bank.
- [x] Pastikan sumber dan tujuan yang sama ditolak.
- [x] Setujui transaksi.
- [x] Pastikan akun tujuan didebet dan akun sumber dikredit.
- [x] Pastikan saldo sumber berkurang dan saldo tujuan bertambah.
- [x] Pastikan total saldo gabungan tidak berubah akibat pemindahan internal.

## Jurnal umum manual

- [x] Buat jurnal minimal dua baris yang seimbang.
- [x] Pastikan jurnal tersimpan sebagai `DRAF`.
- [x] Pastikan jurnal draf belum memengaruhi laporan.
- [x] Posting jurnal dan pastikan status menjadi `DIPOSTING`.
- [x] Pastikan jurnal yang telah diposting masuk neraca saldo.
- [x] Uji jurnal tidak seimbang dan pastikan seluruh proses ditolak atomik.
- [x] Uji satu baris berisi debet sekaligus kredit dan pastikan ditolak.
- [x] Uji jurnal dengan akun induk dan pastikan ditolak.

## Laporan

- [x] Uji filter tanggal awal dan tanggal akhir.
- [x] Pastikan transaksi di luar periode tidak muncul.
- [x] Cocokkan saldo kas/bank dengan saldo awal + masuk - keluar.
- [x] Cocokkan total debet dan kredit pada neraca saldo.
- [x] Cocokkan pendapatan, beban, dan laba/rugi periode.
- [x] Periksa perbandingan aset dengan kewajiban + modal + laba/rugi.
- [x] Pastikan hanya jurnal `DIPOSTING` yang masuk laporan.

## Keamanan dan regresi

- [x] Uji endpoint tanpa permission menghasilkan 403.
- [x] Uji manipulasi ID transaksi/jurnal cabang lain menghasilkan 404/403.
- [x] Uji penyetujuan ganda dan posting ganda ditolak.
- [x] Jalankan regression test Fase 1 sampai Fase 6.
- [x] Pastikan aset UBold/Nunito tetap lokal dan tidak ada CDN eksternal.
- [x] Pastikan tidak ada auto-merge.

## Gate akhir

Pemilik proyek telah menyatakan secara eksplisit:

```text
Fase 7 lulus
```

Checklist manual dinyatakan diterima. PR #10 boleh diproses menuju ready-for-review dan merge manual setelah seluruh CI pada head terbaru tetap hijau dan expected head SHA dikunci.