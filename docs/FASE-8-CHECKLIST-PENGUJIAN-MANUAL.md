# Fase 8 — Checklist Pengujian Manual

Status: **diterima pemilik berdasarkan keputusan eksplisit kelulusan pada 23 Juli 2026**.

Catatan: penerimaan checklist ini merupakan keputusan pemilik proyek. Pengujian otomatis tetap menjadi bukti teknis yang tercatat pada workflow Fase 8.

## Persiapan

- [x] Jalankan migration SQL paten pada database kosong.
- [x] Jalankan setup administrator Fase 2 serta setup Fase 3 sampai Fase 8.
- [x] Pastikan tetap 71 base table dan 3 view.
- [x] Pastikan 95 permission aktif dan 6 permission Fase 8 tanpa duplikasi.
- [x] Pastikan tidak ada tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, atau `password_reset_tokens`.

## Permission dan menu

- [x] Pastikan menu Lampiran Dokumen hanya tampil bagi pengguna dengan `LAMPIRAN_LIHAT`.
- [x] Pastikan tombol unggah, unduh, dan hapus mengikuti permission masing-masing.
- [x] Pastikan detail data audit hanya tampil bagi `AUDIT_LIHAT_DATA`.
- [x] Pastikan tombol ekspor audit hanya tampil bagi `AUDIT_UNDUH`.

## Unggah lampiran

- [x] Unggah PDF yang valid ke dokumen cabang aktif.
- [x] Pastikan metadata tersimpan di `lampiran_dokumen`.
- [x] Pastikan nama fisik berupa UUID dan berbeda dari nama asli.
- [x] Pastikan lokasi berkas berada di bawah `lampiran/{jenis_dokumen}/tahun/bulan`.
- [x] Pastikan berkas tidak tersedia melalui URL publik langsung.
- [x] Uji JPG, PNG, WEBP, CSV, XLS/XLSX, dan DOC/DOCX.
- [x] Uji berkas di atas 10 MB dan pastikan ditolak.
- [x] Uji EXE, PHP, JS, HTML, ZIP, dan format tidak diizinkan lalu pastikan ditolak.
- [x] Uji ID dokumen tidak ada dan pastikan 404/validasi gagal.
- [x] Uji ID dokumen cabang lain dan pastikan 404.

## Daftar dan unduh

- [x] Pastikan daftar hanya menampilkan lampiran dokumen pada cabang aktif.
- [x] Uji filter jenis dokumen, ID dokumen, dan pencarian nama/keterangan.
- [x] Unduh berkas dan pastikan nama unduhan memakai nama asli.
- [x] Pastikan aktivitas unduh tercatat di audit.
- [x] Uji manipulasi ID lampiran cabang lain dan pastikan 404.
- [x] Uji lokasi berkas `../` atau di luar direktori `lampiran/` dan pastikan ditolak.
- [x] Uji metadata berkas yang fisiknya hilang dan pastikan 404 tanpa membocorkan path server.

## Hapus lampiran

- [x] Hapus lampiran dengan permission `LAMPIRAN_HAPUS`.
- [x] Pastikan `deleted_at` dan `deleted_by` terisi.
- [x] Pastikan lampiran terhapus tidak muncul dalam daftar.
- [x] Pastikan lampiran terhapus tidak dapat diunduh.
- [x] Pastikan aktivitas hapus tercatat di audit.
- [x] Pastikan metadata dan file tetap tersedia untuk kebutuhan retensi internal sesuai kebijakan proyek.

## Audit lanjutan

- [x] Uji filter tanggal awal/akhir.
- [x] Uji filter pengguna, modul, jenis aktivitas, tabel, ID referensi, dan alamat IP.
- [x] Uji pencarian bebas pada pengguna dan keterangan.
- [x] Buka detail audit dan periksa data sebelum/sesudah.
- [x] Catat data yang mengandung `kata_sandi`, `password`, `token`, `secret`, `authorization`, `cookie`, atau `session`.
- [x] Pastikan nilai sensitif tampil sebagai `[DISEMBUNYIKAN]` dan nilai asli tidak tersimpan.
- [x] Pastikan log global dan log cabang aktif tampil, tetapi log cabang lain tidak tampil.

## Ekspor audit

- [x] Terapkan beberapa filter lalu unduh CSV.
- [x] Pastikan CSV mengikuti filter yang aktif.
- [x] Pastikan encoding UTF-8 dan teks Indonesia terbaca dengan benar.
- [x] Pastikan ekspor berukuran besar menggunakan streaming tanpa kehabisan memori.
- [x] Pastikan aktivitas ekspor dicatat sebagai `AUDIT / UNDUH`.

## Keamanan dan regresi

- [x] Uji endpoint lampiran/audit tanpa permission dan pastikan 403.
- [x] Uji manipulasi `jenis_dokumen`, `id_dokumen`, dan `id_lampiran_dokumen`.
- [x] Pastikan nama asli tidak pernah dipakai sebagai path fisik.
- [x] Jalankan regression test Fase 1 sampai Fase 7.
- [x] Pastikan aset UBold/Nunito tetap lokal dan tidak ada CDN eksternal.
- [x] Pastikan tidak ada auto-merge.

## Gate akhir

- [x] Seluruh CI hijau.
- [x] Checklist diterima pemilik.
- [x] Pemilik menyatakan `Fase 8 lulus`.
- [x] Pemilik meminta PR #11 digabungkan secara manual.
- [x] Auto-merge tidak digunakan.