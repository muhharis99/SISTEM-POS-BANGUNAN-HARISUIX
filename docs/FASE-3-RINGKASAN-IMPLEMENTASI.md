# Fase 3 — Ringkasan Implementasi Master Data dan Daftar Harga

## Status

**IMPLEMENTASI SELESAI — SELURUH CI OTOMATIS HIJAU — MENUNGGU CHECKLIST MANUAL DAN KELULUSAN FORMAL.**

- Branch: `fase-3-master-data`
- Pull request: Draft PR #4
- Target: `main`
- Merge otomatis: dilarang
- Implementasi Fase 4: belum dimulai

Fase 3 hanya boleh dinyatakan lulus setelah checklist manual selesai dan pemilik menyatakan eksplisit `Fase 3 lulus`. PR #4 tetap draft dan belum merged sampai pernyataan tersebut.

## Integritas skema paten

Implementasi memakai tabel yang sudah tersedia pada `struktur_database_toko_bangunan.sql`. Tidak ada migration baru yang menambah atau mengubah tabel bisnis, kolom, index, foreign key, maupun view. Satu-satunya tabel internal Laravel tetap `migrations`.

Tabel yang digunakan:

- `kategori_barang`, `merek_barang`, `satuan`, `barang`, `barang_satuan`;
- `jenis_pelanggan`, `pelanggan`, `alamat_pelanggan`;
- `pemasok`, `gudang`, `lokasi_gudang`;
- `kas_bank`, `metode_pembayaran`, `kategori_biaya`, `armada`, `tarif_pajak`;
- `daftar_harga`, `daftar_harga_detail`.

## Master barang

Fitur master barang meliputi:

- kategori barang bertingkat dengan pencegahan siklus induk;
- merek barang;
- satuan dan `jumlah_desimal`;
- barang atau jasa;
- satuan dasar dan satuan alternatif;
- konversi satuan hingga enam desimal sesuai kolom paten;
- barcode unik;
- harga beli dan jual acuan;
- stok minimum dan maksimum;
- metode persediaan rata-rata atau FIFO;
- penanda dapat dibeli, dapat dijual, nomor lot, dan kedaluwarsa;
- aktivasi dan nonaktivasi tanpa menghapus data fisik.

Jumlah kuantitas tidak memakai daftar satuan hardcode. Validasi membaca nilai `satuan.jumlah_desimal`. Nilai tersebut dibatasi 0–3 karena seluruh kolom kuantitas persediaan dan daftar harga pada skema paten bertipe `DECIMAL(...,3)`.

Mengedit barang nonaktif tidak mengaktifkan barang tersebut secara otomatis. Perubahan status hanya melalui aksi status tersendiri.

## Pelanggan dan pemasok

Data awal jenis pelanggan:

- Umum;
- Tukang;
- Kontraktor/Proyek;
- Toko/Reseller.

Data tersebut melengkapi data jenis pelanggan yang sudah ada pada baseline SQL paten dan tidak menghapus kode lama seperti `KONTRAKTOR` atau `TOKO`.

Pelanggan default:

- kode: `UMUM`;
- nama: `PELANGGAN TUNAI`;
- jenis: Umum;
- kredit, jatuh tempo, dan potongan: nol.

Identitas pelanggan tunai default dan status aktifnya dilindungi. Pelanggan lain dapat memiliki banyak alamat, satu alamat tambahan utama, koordinat, penerima, serta informasi kredit.

Master pemasok mencakup kontak, alamat, NPWP, rekening, batas hutang, dan jatuh tempo.

## Gudang dan lokasi

Gudang selalu terikat pada cabang aktif. Lokasi gudang dapat bertingkat dan mencegah siklus induk.

Setiap cabang aktif memperoleh data awal:

- gudang `RUSAK` dan lokasi `AREA-RUSAK`;
- gudang `RETUR` dan lokasi `AREA-RETUR`.

Kode, jenis, dan status data khusus tersebut dilindungi agar semantik barang rusak dan retur tidak hilang. Nama, alamat, penanggung jawab, dan keterangan masih dapat disesuaikan selama identitas sistem tetap terjaga.

## Kas, pembayaran, biaya, armada, dan pajak

Fitur yang tersedia:

- kas dan bank per cabang;
- validasi rekening wajib untuk jenis Bank;
- metode pembayaran dan biaya;
- kategori biaya bertingkat;
- armada per cabang;
- tarif pajak.

Data awal `NON_PAJAK` tetap 0% dan dilindungi. Sistem tetap dapat memiliki tarif pajak lain sesuai kebutuhan. Metode `TUNAI` juga dipertahankan sebagai data dasar aktif.

## Daftar harga

Daftar harga memiliki:

- cabang aktif;
- jenis pelanggan opsional;
- tanggal mulai dan tanggal selesai opsional;
- prioritas;
- detail barang-satuan;
- jumlah minimum;
- harga jual;
- potongan persen;
- status aktif.

Dua daftar harga aktif pada cabang dan jenis pelanggan yang sama tidak boleh memiliki periode bertabrakan. Daftar harga nonaktif dapat dipersiapkan dengan periode yang sama, tetapi akan diperiksa kembali saat diaktifkan.

Jumlah minimum mengikuti `satuan.jumlah_desimal`. Kombinasi daftar harga, barang-satuan, dan jumlah minimum tidak boleh duplikat.

## Permission Fase 3

Fase 3 menambahkan 16 permission melalui data katalog, bukan perubahan skema:

- `MASTER_BARANG_LIHAT`, `MASTER_BARANG_KELOLA`;
- `MASTER_PELANGGAN_LIHAT`, `MASTER_PELANGGAN_KELOLA`;
- `MASTER_PEMASOK_LIHAT`, `MASTER_PEMASOK_KELOLA`;
- `MASTER_GUDANG_LIHAT`, `MASTER_GUDANG_KELOLA`;
- `MASTER_KEUANGAN_LIHAT`, `MASTER_KEUANGAN_KELOLA`;
- `MASTER_ARMADA_LIHAT`, `MASTER_ARMADA_KELOLA`;
- `MASTER_PAJAK_LIHAT`, `MASTER_PAJAK_KELOLA`;
- `DAFTAR_HARGA_LIHAT`, `DAFTAR_HARGA_KELOLA`.

Akses URL dilindungi middleware server-side. Penyembunyian menu sidebar bukan satu-satunya perlindungan.

## Data awal dan command

Setelah migration baseline Fase 1/Fase 2 tersedia, buat backup database sebelum menulis data awal:

```bash
mysqldump -u root -p --single-transaction sistem_informasi_toko_bangunan \
  > backup-sebelum-fase-3.sql
```

Kemudian jalankan:

```bash
php artisan fase3:siapkan
```

Command tersebut idempotent dan menyiapkan permission serta data awal melalui `updateOrCreate`/`updateOrInsert` tanpa membuat tabel baru.

## Antarmuka

Semua halaman menggunakan layout UBold lokal dan Nunito lokal. Tidak ada CDN eksternal.

Halaman Fase 3:

- dashboard ringkas master data;
- kategori barang, merek, satuan, dan barang;
- jenis pelanggan, pelanggan, dan alamat;
- pemasok;
- gudang dan lokasi;
- kas/bank, metode pembayaran, dan kategori biaya;
- armada;
- tarif pajak;
- daftar harga.

Tambah dan edit dilakukan melalui modal pada halaman daftar yang sama.

## Hasil pengujian otomatis final

Workflow Fase 3 telah berhasil menjalankan:

1. validasi sintaks PHP dan Pint;
2. backup database kosong sebelum migration;
3. migration SQL paten;
4. penyiapan fixture Administrator Fase 2 dan Fase 3;
5. backup baseline sebelum seeder dan integration test Fase 3;
6. pengunggahan kedua backup sebagai artifact;
7. penyiapan permission dan data awal Fase 3;
8. verifikasi tepat 71 base table, yaitu 70 tabel bisnis dan `migrations`;
9. verifikasi tepat 3 view;
10. verifikasi tidak ada tabel session/cache/queue/password-reset tambahan;
11. verifikasi 29 permission aktif setelah Fase 2 dan Fase 3;
12. verifikasi data awal pelanggan, gudang khusus, dan pajak 0%;
13. `php artisan skema:verifikasi --rinci`;
14. sembilan integration test Fase 3;
15. lima integration test Fase 2 sebagai regresi;
16. seluruh regression test suite Fase 1–Fase 3.

Workflow Fase 3 bersifat read-only dan hard-failing. Tidak ada langkah merge, tag, auto-merge, atau penulisan ke `main`. Workflow UBold, Nunito, smoke test Fase 1, test Fase 2, dan audit auto-merge juga berhasil pada checkpoint otomatis terakhir.

## Batas fase

Sebelum pernyataan eksplisit `Fase 3 lulus`:

- PR #4 harus tetap draft dan belum merged;
- auto-merge tidak boleh diaktifkan;
- tag/checkpoint Fase 3 tidak boleh dibuat;
- Fase 4 tidak boleh dimulai.
