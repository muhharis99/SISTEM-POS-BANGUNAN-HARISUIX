# Fase 5 — Checklist Pengujian Manual

Checklist ini belum dianggap selesai sampai seluruh skenario diuji dan diterima pemilik.

## A. Pengaman dan instalasi

- [ ] Backup database sebelum setup/migration/testing dapat dibuat dan dipulihkan.
- [ ] Migration tetap menghasilkan tepat 71 base table dan 3 view.
- [ ] Tidak ada tabel bisnis, kolom, index, foreign key, atau view tambahan.
- [ ] Tidak ada tabel `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`, atau `password_reset_tokens`.
- [ ] UBold dan Nunito dimuat lokal tanpa CDN eksternal.

## B. RBAC dan isolasi cabang

- [ ] Pengguna tanpa permission pembelian menerima 403 pada menu dan URL langsung.
- [ ] Permission lihat, kelola, ajukan, setujui, terima, bayar, retur, dan laporan terpisah dengan benar.
- [ ] Pengguna cabang A tidak dapat melihat atau memodifikasi dokumen cabang B.
- [ ] Pemasok, gudang, lokasi, kas/bank, dan data referensi yang dipilih sah untuk transaksi.
- [ ] Audit aktivitas menyimpan pengguna, waktu, aksi, dan referensi dokumen.

## C. Permintaan pembelian

- [ ] Draf dapat dibuat, diedit, dan dihapus sesuai aturan.
- [ ] Detail barang, satuan, konversi, jumlah, perkiraan harga, dan keterangan tersimpan benar.
- [ ] Jumlah mengikuti `satuan.jumlah_desimal`.
- [ ] Draf dapat diajukan lalu disetujui atau ditolak.
- [ ] Dokumen yang sudah diajukan/disetujui tidak dapat diedit melalui URL langsung.
- [ ] Jumlah dipesan diperbarui saat detail dipakai pada pesanan pembelian.
- [ ] Status permintaan menjadi diproses/selesai sesuai realisasi.

## D. Pesanan pembelian

- [ ] Pesanan dapat dibuat manual atau dari permintaan pembelian.
- [ ] Satu detail permintaan tidak dapat dipesan melebihi sisa jumlahnya.
- [ ] Harga, potongan, pajak, biaya pengiriman, biaya lain, dan total dihitung server-side.
- [ ] Pesanan tunai dan tempo menyimpan cara pembayaran serta jatuh tempo dengan benar.
- [ ] Persetujuan hanya dapat dilakukan oleh pengguna berwenang.
- [ ] Status diterima sebagian/penuh mengikuti penerimaan aktual.
- [ ] Pesanan yang telah menerima barang tidak dapat dibatalkan secara tidak sah.

## E. Penerimaan barang dan persediaan

- [ ] Penerimaan dapat dibuat dari pesanan atau secara langsung.
- [ ] Gudang dan lokasi tujuan harus konsisten dengan cabang.
- [ ] Jumlah diterima dan ditolak tervalidasi serta tidak negatif.
- [ ] Barang wajib lot/kedaluwarsa tidak dapat diterima tanpa data wajib.
- [ ] Persetujuan penerimaan menambah `saldo_stok` tepat satu kali.
- [ ] Mutasi `PEMBELIAN` tercatat dengan nomor dokumen, jumlah, harga pokok, nilai mutasi, dan saldo setelah transaksi.
- [ ] Penerimaan ulang melalui refresh/double submit tidak menggandakan stok.
- [ ] Harga pokok rata-rata serta harga beli terakhir diperbarui benar.
- [ ] Pembatalan draf tidak mengubah stok; dokumen diterima tidak dapat dihapus sembarangan.

## F. Faktur pembelian dan hutang

- [ ] Faktur dapat mengacu pada pesanan dan/atau penerimaan yang sah.
- [ ] Nomor faktur pemasok unik per pemasok.
- [ ] Jumlah difakturkan tidak melebihi sisa yang dapat difakturkan.
- [ ] Total kotor, potongan, pajak, biaya, pembulatan, total bersih, total dibayar, dan sisa hutang benar.
- [ ] Faktur tunai dan tempo menghasilkan status yang sesuai.
- [ ] Persetujuan faktur tempo membuat tepat satu `hutang_pemasok`.
- [ ] Tanggal jatuh tempo dan status hutang benar.
- [ ] Pembatalan tidak meninggalkan hutang ganda atau histori tidak konsisten.

## G. Pembayaran hutang

- [ ] Pembayaran draf dapat dibuat untuk satu pemasok dan dialokasikan ke beberapa hutang.
- [ ] Hutang pemasok lain tidak dapat dimasukkan melalui manipulasi request.
- [ ] Nilai alokasi tidak boleh melebihi sisa hutang.
- [ ] Total alokasi dan potongan pembayaran sesuai total dokumen.
- [ ] Persetujuan pembayaran memperbarui `hutang_pemasok`, faktur, dan status lunas/sebagian secara atomik.
- [ ] Double submit tidak menggandakan pembayaran.
- [ ] Pembatalan draf tidak mengubah hutang; pembayaran disetujui tidak dapat dihapus sembarangan.
- [ ] Laporan pembayaran sesuai kas/bank, pemasok, cabang, dan periode.

## H. Retur pembelian

- [ ] Retur dapat dibuat dari faktur/penerimaan yang sah.
- [ ] Jumlah retur tidak melebihi jumlah diterima/difakturkan yang belum diretur.
- [ ] Gudang dan lokasi asal benar serta stok tersedia cukup.
- [ ] Persetujuan/pengiriman retur mengurangi stok tepat satu kali.
- [ ] Mutasi `RETUR_PEMBELIAN` tercatat benar.
- [ ] Cara pengembalian `POTONG_HUTANG` mengurangi hutang dan sisa faktur dengan benar.
- [ ] Cara tunai/transfer mewajibkan kas/bank yang sah.
- [ ] Barang pengganti tidak dianggap sebagai uang masuk.
- [ ] Retur tidak dapat diproses ulang melalui refresh/double submit.

## I. Laporan

- [ ] Laporan permintaan, pesanan, penerimaan, faktur, pembayaran, dan retur dapat difilter.
- [ ] Laporan pembelian per pemasok/barang/periode benar.
- [ ] Saldo hutang dan hutang jatuh tempo sesuai transaksi.
- [ ] Umur hutang dihitung dari tanggal jatuh tempo yang benar.
- [ ] Total laporan cocok dengan detail sumber.
- [ ] Pengguna hanya melihat data cabang yang diizinkan.

## J. Regresi dan gate kelulusan

- [ ] Fase 1 Smoke Test berhasil.
- [ ] Fase 2 Authentication RBAC Test berhasil.
- [ ] Fase 3 Master Data Test berhasil.
- [ ] Fase 4 Persediaan Test berhasil.
- [ ] Integration test Fase 5 berhasil.
- [ ] Full regression suite Fase 1–Fase 5 berhasil.
- [ ] Sintaks PHP dan Pint berhasil tanpa patch diagnostik.
- [ ] Audit workflow memastikan auto-merge tidak aktif.
- [ ] PR tetap draft dan belum merged selama pengujian.
- [ ] Pemilik menyatakan eksplisit `Fase 5 lulus` sebelum PR diubah menjadi ready atau di-merge.
