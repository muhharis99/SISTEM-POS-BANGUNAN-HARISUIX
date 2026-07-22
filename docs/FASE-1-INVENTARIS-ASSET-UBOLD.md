# Fase 1 — Inventaris dan Penguncian Asset UBold

Dokumen ini menjadi acuan tetap untuk integrasi frontend Sistem POS Toko Bangunan. Seluruh asset berasal dari folder `template_admin/` dan **tidak boleh diganti dengan CDN, npm package versi terbaru, atau asset template lain** tanpa persetujuan tertulis pemilik proyek.

## Aturan penguncian

1. Sumber asset resmi proyek adalah `template_admin/assets/`.
2. Asset disalin ke `public/assets/admin/` menggunakan `php scripts/salin-aset-template.php`.
3. Script menghasilkan manifest SHA-256 untuk mendeteksi perubahan isi berkas.
4. Versi vendor dibaca dari header berkas yang tersedia. Versi yang tidak ditulis oleh vendor tidak boleh ditebak.
5. File minified tetap digunakan sebagaimana disediakan template.
6. `template_admin_1/` tidak digunakan dalam aplikasi.

## Asset inti layout

| Asset | Lokasi sumber | Fungsi | Identitas penguncian |
|---|---|---|---|
| Theme config awal | `template_admin/assets/js/config-html.js` | Menerapkan preferensi layout sebelum halaman dirender | Dikunci melalui manifest SHA-256 |
| Theme config | `template_admin/assets/js/config.js` | Konfigurasi tema, topbar, sidebar, dan mode warna | Dikunci melalui manifest SHA-256 |
| Vendor CSS | `template_admin/assets/css/vendors.min.css` | Kumpulan CSS vendor | Git blob `3551e3712fb602f81f6a326d027d86d9e5d9296e` |
| UBold application CSS | `template_admin/assets/css/app.min.css` | Style utama UBold | Git blob `9fab313174361770172d5682ca25617b24978bab` |
| Vendor JavaScript | `template_admin/assets/js/vendors.min.js` | Kumpulan JavaScript vendor | Git blob `db4425e3e6d127f3dbd3feff4a0045c624000695` |
| UBold application JS | `template_admin/assets/js/app.js` | Interaksi layout UBold | Dikunci melalui manifest SHA-256 |
| Custom table | `template_admin/assets/js/pages/custom-table.js` | Sort, filter, pilihan baris, dan pagination tabel template | Dikunci melalui manifest SHA-256 |

## Versi vendor yang teridentifikasi langsung dari berkas

| Vendor | Versi persis | Berkas asal | Catatan penggunaan |
|---|---:|---|---|
| Bootstrap | `5.3.8` | `assets/js/vendors.min.js`, `assets/css/app.min.css` | Komponen layout, modal, dropdown, collapse, offcanvas, alert, dan form |
| SimpleBar | `6.3.3` | `assets/js/vendors.min.js` | Scrollbar sidebar dan panel dengan atribut `data-simplebar` |
| Flatpickr | `4.6.13` | `assets/js/vendors.min.js` | Input tanggal dan waktu |
| Choices.js | `11.2.2` | `assets/js/vendors.min.js` | Select dengan pencarian dan multiple choice |
| Chart.js | `4.5.1` | `assets/plugins/chartjs/chart.umd.js` | Grafik dashboard dan laporan |
| @kurkle/color | `0.3.2` | Terbundel di `chart.umd.js` | Dependency internal Chart.js |
| Prism | Versi tidak tercantum pada header yang tersedia | `assets/js/vendors.min.js` | Syntax highlighting; isi dikunci dengan hash bundle |
| Lucide Icons | Versi tidak tercantum pada header yang tersedia | Terbundel/diinisialisasi oleh asset UBold | Ikon dengan atribut `data-lucide`; isi dikunci dengan hash bundle |

## jQuery

Pada halaman inti UBold yang dijadikan acuan, tidak ditemukan tag `<script>` terpisah yang memuat jQuery. Layout inti menggunakan Bootstrap 5 dan JavaScript UBold. Karena itu:

- aplikasi tidak menambahkan jQuery dari CDN;
- jika halaman template tertentu membutuhkan jQuery, kebutuhan tersebut harus dibuktikan dari asset lokal halaman yang bersangkutan;
- versi jQuery tidak boleh ditentukan berdasarkan asumsi.

## Asset grafik dashboard

Halaman `template_admin/index.html` memuat asset berikut secara lokal:

```text
template_admin/assets/plugins/chartjs/chart.umd.js
template_admin/assets/js/pages/dashboard-ecommerce.js
```

Dashboard Laravel wajib memakai berkas tersebut atau asset lokal lain yang memang sudah tersedia di `template_admin`. Tidak diperbolehkan mengganti Chart.js dengan ApexCharts, ECharts, atau library lain tanpa persetujuan.

## Cara menyalin dan mengunci asset

Jalankan dari root proyek:

```bash
php scripts/salin-aset-template.php
```

Hasil:

```text
public/assets/admin/
docs/manifests/aset-ubold-sha256.json
```

Folder output tidak disimpan ulang di Git karena sumber aslinya sudah berada di `template_admin/`. Asset harus dibuat ulang setelah clone atau deployment menggunakan script yang sama.

## Pemeriksaan wajib

Sesudah asset disalin:

1. Pastikan `public/assets/admin/css/app.min.css` tersedia.
2. Pastikan `public/assets/admin/js/vendors.min.js` tersedia.
3. Pastikan `public/assets/admin/js/app.js` tersedia.
4. Buka dashboard dan periksa console browser.
5. Pastikan tidak ada request asset menuju CDN.
6. Pastikan tidak ada asset yang diambil dari `template_admin_1/`.
7. Simpan manifest SHA-256 bersama bukti testing fase.

## Kebijakan perubahan

Perubahan vendor hanya dapat dilakukan melalui pekerjaan terpisah yang mencakup:

- alasan perubahan;
- daftar file yang berubah;
- dampak visual dan JavaScript;
- regression test seluruh halaman terdampak;
- persetujuan eksplisit pemilik proyek.
