# Fase 1 — Hasil Investigasi Import Font UBold

## Status

**Investigasi selesai — belum dilakukan perubahan asset.**

Keputusan perbaikan font tetap menunggu persetujuan pemilik proyek.

## Berkas sumber

Import ditemukan pada:

```text
template_admin/assets/css/app.min.css
```

Asset runtime hasil penyalinan berada pada:

```text
public/assets/admin/css/app.min.css
```

Import yang ditemukan:

```css
@import url(../../../../css2);
@import url(../../../../css2-1);
@import url(../../../../css2-2);
@import url(../../../../css2-3);
@import url(../../../../css2-4);
@import url(../../../../css2-5);
@import url(../../../../css2-6);
@import url(../../../../css2-7);
@import url(../../../../css2-6);
@import url(../../../../css2-8);
```

`css2-6` dipanggil dua kali karena memang tertulis dua kali pada asset asli.

## Metode investigasi

Pemeriksaan dilakukan pada GitHub Actions menggunakan:

- Laravel development server pada `127.0.0.1:8000`;
- Google Chrome headless;
- Chrome NetLog;
- pemeriksaan URL relatif sesuai aturan resolusi URL browser;
- request HTTP langsung untuk memastikan status;
- pencarian file `css2*` pada repository dan asset runtime.

Tidak ada CSS, font, atau asset UBold yang diubah selama investigasi.

## Hasil Network browser

Browser menyelesaikan import relatif tersebut menjadi URL pada origin aplikasi sendiri:

```text
http://127.0.0.1:8000/css2
http://127.0.0.1:8000/css2-1
http://127.0.0.1:8000/css2-2
http://127.0.0.1:8000/css2-3
http://127.0.0.1:8000/css2-4
http://127.0.0.1:8000/css2-5
http://127.0.0.1:8000/css2-6
http://127.0.0.1:8000/css2-7
http://127.0.0.1:8000/css2-8
```

Seluruh URL tersebut memberikan hasil:

```text
HTTP 404 Not Found
```

Laravel server log juga mencatat request `/css2` sampai `/css2-8` saat dashboard dimuat.

## Apakah memanggil Google Fonts CDN?

**Tidak.**

Pada asset yang sedang digunakan, import berbentuk URL relatif. Chrome NetLog mencatat request URL aktual hanya menuju:

```text
http://127.0.0.1:8000/css2...
```

Tidak terdapat request URL aktual menuju:

```text
https://fonts.googleapis.com/...
https://fonts.gstatic.com/...
```

String `https://fonts.gstatic.com` sempat muncul pada data mentah NetLog sebagai bagian dari daftar origin internal bawaan Chrome, bukan sebagai request halaman. Setelah NetLog diperiksa berdasarkan field URL request, origin tersebut tidak pernah diminta oleh dashboard.

## Apakah file lokal tersedia?

Tidak ditemukan file extensionless berikut di repository maupun asset runtime:

```text
css2
css2-1
css2-2
css2-3
css2-4
css2-5
css2-6
css2-7
css2-8
```

Script `scripts/salin-aset-template.php` hanya menyalin isi:

```text
template_admin/assets
```

ke:

```text
public/assets/admin
```

Karena file `css2*` tidak tersedia pada sumber tersebut, request ke root aplikasi tetap gagal `404`.

## Font yang digunakan oleh tema

Pada `app.min.css`, font utama tema dideklarasikan sebagai:

```css
--theme-font-sans-serif: "Nunito", sans-serif;
```

Karena stylesheet penyedia font gagal dimuat, browser akan mencoba font `Nunito` yang sudah terpasang pada sistem pengguna. Apabila tidak tersedia, browser memakai fallback generik `sans-serif`.

Artinya, saat ini tidak ada kebocoran request ke CDN, tetapi masih ada kemungkinan perbedaan tipografi terhadap preview UBold asli.

## Kesimpulan

1. `css2` bukan URL CDN langsung.
2. Referensi tersebut adalah path relatif yang salah atau tidak lengkap.
3. Browser benar-benar meminta `/css2` sampai `/css2-8` pada origin lokal.
4. Semua request menghasilkan `404`.
5. Tidak ada request aktual ke Google Fonts.
6. Font utama yang disebut asset adalah `Nunito`.
7. Tidak ada perubahan font yang diterapkan.

## Opsi keputusan pemilik

Pilihan yang paling menjaga tampilan adalah:

1. menemukan URL Google Fonts asli atau paket sumber UBold yang memuat stylesheet `css2*`;
2. mengambil file font **Nunito dengan weight/style persis yang diperlukan**;
3. menyimpan font secara lokal di `public/assets/admin/fonts/`;
4. membuat stylesheet lokal yang berisi deklarasi `@font-face`;
5. mengganti import `css2*` dengan referensi lokal;
6. memverifikasi checksum, Network browser, dan perbandingan visual.

Langkah tersebut belum dijalankan karena pemilik meminta laporan investigasi terlebih dahulu.
