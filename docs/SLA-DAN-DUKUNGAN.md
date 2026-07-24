# SLA dan Dukungan Pascapeluncuran

Dokumen ini menyediakan target layanan internal. Nilai berikut menjadi baseline dan harus disesuaikan dengan jam operasional, jumlah personel, serta kontrak dukungan nyata.

## Jam layanan

- Dukungan reguler: mengikuti jam operasional yang disepakati pemilik dan tim IT.
- Insiden P0 dapat diekskalasikan di luar jam layanan melalui kanal darurat yang ditetapkan secara terpisah.
- Repository tidak menyimpan nomor telepon pribadi, kata sandi, token, atau kredensial kanal darurat.

## Target respons

| Prioritas | Respons awal | Target mitigasi | Target rencana permanen |
|---|---:|---:|---:|
| P0 Kritis | 15 menit | 1 jam | 1 hari kerja |
| P1 Tinggi | 1 jam kerja | 4 jam kerja | 3 hari kerja |
| P2 Sedang | 1 hari kerja | 3 hari kerja | 10 hari kerja |
| P3 Rendah | 3 hari kerja | sesuai backlog | sesuai perencanaan |

Target bukan jaminan penyelesaian mutlak. Kompleksitas, akses server, ketergantungan vendor, kebutuhan pemulihan data, dan persetujuan pemilik dapat memengaruhi waktu penyelesaian.

## Status issue

- `baru`: belum ditriase;
- `triase`: dampak dan prioritas sedang diperiksa;
- `menunggu-informasi`: data reproduksi belum cukup;
- `dalam-perbaikan`: perubahan atau mitigasi sedang dikerjakan;
- `siap-verifikasi`: menunggu bukti pengujian;
- `menunggu-deployment`: perbaikan sudah lolos gate tetapi belum dipasang;
- `selesai`: verifikasi dan catatan penutupan lengkap;
- `ditolak`: bukan bug, duplikat, tidak aman, atau di luar ruang lingkup.

## Matriks eskalasi

- P0: pemilik keputusan, PIC aplikasi, PIC database, dan PIC infrastruktur.
- P1: PIC aplikasi dan pemilik modul; eskalasi ke pemilik keputusan bila mitigasi membutuhkan downtime.
- P2: pemilik modul dan backlog maintenance release.
- P3: backlog perbaikan berkelanjutan.

## Syarat hotfix

Hotfix hanya digunakan bila:

- dampak P0/P1 terkonfirmasi;
- reproduksi atau akar masalah cukup jelas;
- perubahan paling kecil dan aman telah dipilih;
- test regresi relevan tersedia;
- backup dan rencana rollback siap;
- expected head SHA serta CI hijau diverifikasi;
- merge dan deployment dilakukan manual sesuai gate pemilik.

## Maintenance release

- Gunakan Semantic Versioning, misalnya `v1.0.1` untuk perbaikan kompatibel.
- Satukan hanya perubahan yang telah ditriase dan memiliki issue.
- Perbarui changelog dan release notes.
- Buat paket rilis serta checksum menggunakan mekanisme Fase 12.
- Jalankan smoke test, gate go-live, regresi, dan hypercare sesuai risiko perubahan.
- Tag dan GitHub Release dibuat setelah merge serta persetujuan pemilik.

## Pelaporan layanan

Laporan bulanan minimal memuat:

- jumlah issue masuk/selesai per prioritas;
- median waktu respons dan penyelesaian;
- issue melewati SLA;
- insiden berulang;
- maintenance release/hotfix yang diterbitkan;
- downtime dan dampak operasional;
- status backup, restore test, kapasitas disk, dan readiness;
- risiko terbuka serta keputusan yang dibutuhkan.
