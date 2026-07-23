# Fase 8 — Checklist Pengujian Manual

Status: **belum diterima pemilik**.

## Persiapan

- [ ] Jalankan migration SQL paten pada database kosong.
- [ ] Jalankan setup administrator Fase 2 serta setup Fase 3 sampai Fase 8.
- [ ] Pastikan tetap 71 base table dan 3 view.
- [ ] Pastikan 95 permission aktif dan 6 permission Fase 8 tanpa duplikasi.
- [ ] Pastikan tidak ada tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, atau `password_reset_tokens`.

## Permission dan menu

- [ ] Pastikan menu Lampiran Dokumen hanya tampil bagi pengguna dengan `LAMPIRAN_LIHAT`.
- [ ] Pastikan tombol unggah, unduh, dan hapus mengikuti permission masing-masing.
- [ ] Pastikan detail data audit hanya tampil bagi `AUDIT_LIHAT_DATA`.
- [ ] Pastikan tombol ekspor audit hanya tampil bagi `AUDIT_UNDUH`.

## Unggah lampiran

- [ ] Unggah PDF yang valid ke dokumen cabang aktif.
- [ ] Pastikan metadata tersimpan di `lampiran_dokumen`.
- [ ] Pastikan nama fisik berupa UUID dan berbeda dari nama asli.
- [ ] Pastikan lokasi berkas berada di bawah `lampiran/{jenis_dokumen}/tahun/bulan`.
- [ ] Pastikan berkas tidak tersedia melalui URL publik langsung.
- [ ] Uji JPG, PNG, WEBP, CSV, XLS/XLSX, dan DOC/DOCX.
- [ ] Uji berkas di atas 10 MB dan pastikan ditolak.
- [ ] Uji EXE, PHP, JS, HTML, ZIP, dan format tidak diizinkan lalu pastikan ditolak.
- [ ] Uji ID dokumen tidak ada dan pastikan 404/validasi gagal.
- [ ] Uji ID dokumen cabang lain dan pastikan 404.

## Daftar dan unduh

- [ ] Pastikan daftar hanya menampilkan lampiran dokumen pada cabang aktif.
- [ ] Uji filter jenis dokumen, ID dokumen, dan pencarian nama/keterangan.
- [ ] Unduh berkas dan pastikan nama unduhan memakai nama asli.
- [ ] Pastikan aktivitas unduh tercatat di audit.
- [ ] Uji manipulasi ID lampiran cabang lain dan pastikan 404.
- [ ] Uji lokasi berkas `../` atau di luar direktori `lampiran/` dan pastikan ditolak.
- [ ] Uji metadata berkas yang fisiknya hilang dan pastikan 404 tanpa membocorkan path server.

## Hapus lampiran

- [ ] Hapus lampiran dengan permission `LAMPIRAN_HAPUS`.
- [ ] Pastikan `deleted_at` dan `deleted_by` terisi.
- [ ] Pastikan lampiran terhapus tidak muncul dalam daftar.
- [ ] Pastikan lampiran terhapus tidak dapat diunduh.
- [ ] Pastikan aktivitas hapus tercatat di audit.
- [ ] Pastikan metadata dan file tetap tersedia untuk kebutuhan retensi internal sesuai kebijakan proyek.

## Audit lanjutan

- [ ] Uji filter tanggal awal/akhir.
- [ ] Uji filter pengguna, modul, jenis aktivitas, tabel, ID referensi, dan alamat IP.
- [ ] Uji pencarian bebas pada pengguna dan keterangan.
- [ ] Buka detail audit dan periksa data sebelum/sesudah.
- [ ] Catat data yang mengandung `kata_sandi`, `password`, `token`, `secret`, `authorization`, `cookie`, atau `session`.
- [ ] Pastikan nilai sensitif tampil sebagai `[DISEMBUNYIKAN]` dan nilai asli tidak tersimpan.
- [ ] Pastikan log global dan log cabang aktif tampil, tetapi log cabang lain tidak tampil.

## Ekspor audit

- [ ] Terapkan beberapa filter lalu unduh CSV.
- [ ] Pastikan CSV mengikuti filter yang aktif.
- [ ] Pastikan encoding UTF-8 dan teks Indonesia terbaca dengan benar.
- [ ] Pastikan ekspor berukuran besar menggunakan streaming tanpa kehabisan memori.
- [ ] Pastikan aktivitas ekspor dicatat sebagai `AUDIT / UNDUH`.

## Keamanan dan regresi

- [ ] Uji endpoint lampiran/audit tanpa permission dan pastikan 403.
- [ ] Uji manipulasi `jenis_dokumen`, `id_dokumen`, dan `id_lampiran_dokumen`.
- [ ] Pastikan nama asli tidak pernah dipakai sebagai path fisik.
- [ ] Jalankan regression test Fase 1 sampai Fase 7.
- [ ] Pastikan aset UBold/Nunito tetap lokal dan tidak ada CDN eksternal.
- [ ] Pastikan tidak ada auto-merge.

## Gate akhir

Fase 8 hanya boleh di-merge setelah seluruh CI hijau, checklist ini diterima, dan pemilik menyatakan eksplisit:

```text
Fase 8 lulus
```
