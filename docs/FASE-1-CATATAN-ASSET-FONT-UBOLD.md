# Fase 1 — Catatan Import Font UBold

## Status

**Temuan terbuka — belum diubah sepihak.**

## Temuan

Berkas berikut:

```text
template_admin/assets/css/app.min.css
```

memuat beberapa import relatif pada awal berkas:

```css
@import url(../../../../css2);
@import url(../../../../css2-1);
@import url(../../../../css2-2);
@import url(../../../../css2-3);
@import url(../../../../css2-4);
@import url(../../../../css2-5);
@import url(../../../../css2-6);
@import url(../../../../css2-7);
@import url(../../../../css2-8);
```

Berkas extensionless `css2`, `css2-1`, dan seterusnya belum ditemukan pada path yang dapat dipetakan dari repository melalui pemeriksaan konektor GitHub.

## Dampak potensial

Apabila request tersebut menghasilkan `404`, browser akan menggunakan font fallback sistem. Komponen Bootstrap dan UBold tetap dapat tampil, tetapi tipografi mungkin berbeda dari preview template asli.

## Tindakan yang sengaja belum dilakukan

- Tidak mengganti import dengan Google Fonts CDN.
- Tidak menghapus import dari `app.min.css`.
- Tidak menebak isi berkas font.
- Tidak mengunduh font versi lain.
- Tidak mengubah asset minified UBold.

Langkah-langkah tersebut ditahan agar aturan penguncian asset tetap dipatuhi.

## Verifikasi manual wajib

Pada saat dashboard dijalankan:

1. Buka Developer Tools browser.
2. Buka tab **Network**.
3. Muat ulang `/dashboard`.
4. Cari request dengan nama `css2`, `css2-1`, dan seterusnya.
5. Catat status HTTP dan URL final masing-masing request.
6. Bandingkan tipografi dengan halaman HTML asli di `template_admin/`.

## Keputusan setelah pengujian

Jika request berhasil, tidak ada perubahan yang diperlukan.

Jika request gagal, pemilik proyek perlu memilih salah satu tindakan berikut:

1. Menyediakan salinan persis berkas sumber font yang digunakan template.
2. Mengizinkan font lokal pengganti yang dicatat versinya.
3. Mengizinkan font fallback sistem.

Tidak ada opsi yang diterapkan sebelum hasil Network browser tersedia dan pemilik proyek memberikan persetujuan.
