## Referensi

- Issue terkait: <!-- wajib untuk bug, hotfix, maintenance release, atau perubahan fitur -->
- Jenis perubahan: <!-- bug / hotfix / maintenance / keamanan / dokumentasi / fitur -->

## Ringkasan

<!-- Jelaskan akar masalah, perubahan yang dibuat, dan dampaknya bagi pengguna. -->

## Dampak dan risiko

- Modul terdampak:
- Cabang/peran terdampak:
- Risiko stok/keuangan:
- Risiko keamanan atau data sensitif:
- Jalan sementara sebelum deployment:

## Integritas SQL paten

- [ ] Tidak mengubah tabel, kolom, index, foreign key, migration bisnis, atau view.
- [ ] Tetap 71 base table dan 3 view paten.
- [ ] Tetap 98 permission aktif, atau perubahan permission telah mendapat gate pemilik.
- [ ] Tidak menambahkan tabel infrastruktur Laravel yang dilarang.

Jelaskan bila salah satu butir di atas tidak terpenuhi:

<!-- Perubahan SQL paten tidak boleh diproses tanpa fase khusus dan keputusan pemilik. -->

## Keamanan dan isolasi cabang

- [ ] Validasi backend diterapkan.
- [ ] Data cabang lain tidak dapat diakses melalui manipulasi ID.
- [ ] Nilai penting dihitung dari sumber tepercaya, bukan hanya input browser.
- [ ] Audit dan penyamaran data sensitif tetap berlaku.
- [ ] Lampiran atau bukti tidak memuat kredensial, `.env`, backup, atau data sensitif.

## Transaksi dan konkurensi

- [ ] Operasi kritis memakai transaksi database.
- [ ] Locking digunakan pada saldo, nomor dokumen, status, atau alokasi yang berpotensi race condition.
- [ ] Kegagalan tidak meninggalkan perubahan sebagian.
- [ ] Dampak terhadap stok, hutang, piutang, kas, bank, dan jurnal telah diperiksa.

## Pengujian

- [ ] Syntax check berhasil.
- [ ] Laravel Pint berhasil.
- [ ] Integration test relevan berhasil.
- [ ] Regression test modul terdampak berhasil.
- [ ] Full regression suite berhasil atau alasan pengecualian dicatat.
- [ ] Isolasi cabang dan skenario manipulasi input diuji.

Perintah/hasil pengujian:

```text
Tuliskan perintah dan ringkasan hasil di sini.
```

## Deployment dan rollback

- [ ] Backup terbaru dan checksum tersedia bila perubahan menyentuh operasi produksi.
- [ ] Rencana rollback aplikasi tersedia.
- [ ] Rollback database tidak dilakukan otomatis.
- [ ] Smoke test pascadeploy dan gate go-live telah direncanakan.
- [ ] Changelog/release notes diperbarui bila diperlukan.

## Gate merge

- [ ] PR tetap draft sampai implementasi dan CI selesai.
- [ ] Seluruh workflow pada head terbaru hijau.
- [ ] Expected head SHA akan dikunci saat merge.
- [ ] Merge dilakukan manual.
- [ ] Auto-merge tidak digunakan.
- [ ] Keputusan pemilik tersedia apabila perubahan memerlukan gate fase, SQL, permission, rilis, atau deployment.
