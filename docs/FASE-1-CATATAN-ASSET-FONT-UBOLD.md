# Fase 1 — Pemasangan Font Nunito Lokal UBold

## Status

**Pemasangan dan pengujian otomatis selesai — Fase 1 tetap belum lulus sampai pemilik menyatakan eksplisit `Fase 1 lulus`.**

## Masalah awal

Berkas berikut:

```text
template_admin/assets/css/app.min.css
```

memuat import relatif `css2`, `css2-1` sampai `css2-8`. Browser menyelesaikannya menjadi `/css2*` pada origin aplikasi dan seluruh request menghasilkan HTTP 404.

Tidak ditemukan request aktual ke:

```text
fonts.googleapis.com
fonts.gstatic.com
```

Pemindaian rekursif terhadap seluruh 528 file dalam `template_admin/`, termasuk root dan semua subfolder di luar `assets/`, memastikan file `css2*` dan berkas Nunito tidak tersedia pada paket asli.

## Keputusan pemilik

- self-host Nunito;
- sumber resmi repository `google/fonts`;
- format akhir dua variable WOFF2;
- rentang weight 300–700;
- style normal dan italic;
- lisensi SIL Open Font License 1.1;
- hanya blok import `css2*` yang boleh diganti pada CSS runtime.

## Sumber dan hasil font

Sumber dikunci pada commit Google Fonts:

```text
684b69db51d59a3137ec0152fa3a3afc6f1b3814
```

File sumber:

```text
ofl/nunito/Nunito[wght].ttf
ofl/nunito/Nunito-Italic[wght].ttf
ofl/nunito/OFL.txt
```

FontTools 4.63.0 digunakan untuk membatasi axis `wght` menjadi 300–700 dan mengubah font ke WOFF2.

File hasil:

```text
template_admin/assets/fonts/nunito/Nunito-Variable.woff2
template_admin/assets/fonts/nunito/Nunito-Italic-Variable.woff2
template_admin/assets/fonts/nunito/OFL.txt
template_admin/assets/fonts/nunito/SUMBER.md
```

SHA-256:

```text
Nunito-Variable.woff2
38a5cfb67d0b85874f3954c63a6448e818150508d375db114b1c409bb942bd15

Nunito-Italic-Variable.woff2
e8a824fcc2f755d018a0cb72acf9e3a75d909ac63aa042b58c5187525d034188

OFL.txt
580df76c95a1ec5ab878ceb25bb3d85c6a076804e9c970c8c6972aea775fdf65
```

## Implementasi CSS terkontrol

Sumber UBold asli tetap dipertahankan. Saat asset disalin ke runtime, `scripts/salin-aset-template.php` mencari rangkaian import `css2*` yang persis cocok dan menggantinya dengan:

```css
@font-face {
    font-family: "Nunito";
    font-style: normal;
    font-weight: 300 700;
    font-display: swap;
    src: url("../fonts/nunito/Nunito-Variable.woff2") format("woff2");
}

@font-face {
    font-family: "Nunito";
    font-style: italic;
    font-weight: 300 700;
    font-display: swap;
    src: url("../fonts/nunito/Nunito-Italic-Variable.woff2") format("woff2");
}
```

Perlindungan implementasi:

- penggantian harus terjadi tepat satu kali;
- proses gagal jika blok sumber tidak ditemukan atau ditemukan lebih dari sekali;
- proses gagal jika kedua file WOFF2 tidak tersedia;
- checksum CSS runtime dihitung ulang setelah penggantian;
- manifest mencatat perubahan terkontrol tersebut;
- pengujian byte-level membuktikan CSS runtime hanya berbeda pada blok import `css2*`.

## Hasil Network browser setelah pemasangan

```text
Request css2* sebelum : 9
Request css2* sesudah : 0
Request Nunito lokal  : 2
Request font eksternal: 0
```

Browser meminta:

```text
/assets/admin/fonts/nunito/Nunito-Variable.woff2
/assets/admin/fonts/nunito/Nunito-Italic-Variable.woff2
```

Kedua file dapat dilayani oleh Laravel development server dan tidak ada request ke Google Fonts CDN.

## Hasil perbandingan visual

Screenshot before/after dibuat dengan kondisi, URL, viewport, dashboard, dan konfigurasi tema yang sama.

```text
Ukuran screenshot : 1440 × 1100
Area perbedaan     : (30, 29, 1384, 1099)
Pixel berubah      : 98.440 dari 1.584.000
Persentase berubah : 6,2146%
```

Perbedaan terlihat pada bentuk, lebar, dan jarak tipografi setelah browser memakai Nunito asli. Struktur dashboard, warna, panel, sidebar, card, tabel, dan pengaturan tema tetap sama.

## Pengujian otomatis

Workflow berikut berhasil:

- `Verifikasi Font Nunito Lokal`;
- `Verifikasi Visual Font Nunito`;
- `Fase 1 Smoke Test`;
- `Investigasi Font UBold`;
- `Verifikasi Paket Font UBold`.

Cakupan validasi:

- file dan checksum;
- format WOFF2;
- variable axis `wght` 300–700;
- style normal dan italic;
- lisensi OFL;
- tidak ada import `css2*` pada CSS runtime;
- tidak ada request font eksternal;
- screenshot before/after;
- unit dan feature test;
- 70 tabel bisnis dan 3 view tetap sesuai SQL final.

## Batas status fase

Pemasangan font ini menutup temuan asset `css2*`, tetapi tidak mengubah aturan kelulusan fase. Draft PR #2 tidak boleh digabung dan tag `fase-1-selesai` tidak boleh dibuat sebelum checklist manual pemilik selesai dan pemilik menyatakan eksplisit:

```text
Fase 1 lulus
```
