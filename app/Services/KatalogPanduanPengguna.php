<?php

namespace App\Services;

use App\Models\Pengguna;

class KatalogPanduanPengguna
{
    public function untuk(Pengguna $pengguna, ?int $idCabang): array
    {
        $punya = fn (string $izin): bool => $pengguna->memilikiHakAkses($izin, $idCabang);

        return collect($this->definisi())
            ->filter(function (array $panduan) use ($punya): bool {
                if ($panduan['izin'] === []) {
                    return true;
                }

                return collect($panduan['izin'])->contains(fn (string $izin): bool => $punya($izin));
            })
            ->values()
            ->all();
    }

    public function prosedurUmum(): array
    {
        return [
            [
                'judul' => 'Sebelum memulai pekerjaan',
                'ikon' => 'circle-check-big',
                'langkah' => [
                    'Pastikan cabang aktif sudah benar sebelum membuka modul transaksi.',
                    'Periksa tanggal dokumen, pihak terkait, gudang, dan metode pembayaran.',
                    'Gunakan tombol simpan atau setujui satu kali dan tunggu respons sistem.',
                ],
            ],
            [
                'judul' => 'Saat terjadi kesalahan',
                'ikon' => 'triangle-alert',
                'langkah' => [
                    'Catat nomor dokumen, waktu kejadian, nama pengguna, dan pesan kesalahan.',
                    'Jangan mengulang persetujuan transaksi berkali-kali.',
                    'Hubungi administrator atau petugas IT dengan bukti yang tidak memuat kata sandi.',
                ],
            ],
            [
                'judul' => 'Keamanan akun',
                'ikon' => 'shield-check',
                'langkah' => [
                    'Jangan membagikan kata sandi atau memakai akun pengguna lain.',
                    'Keluar dari sistem setelah selesai menggunakan komputer bersama.',
                    'Laporkan perubahan akses atau aktivitas yang tidak dikenali.',
                ],
            ],
        ];
    }

    private function definisi(): array
    {
        return [
            [
                'kode' => 'mulai',
                'judul' => 'Mulai Menggunakan Sistem',
                'ikon' => 'rocket',
                'ringkasan' => 'Langkah dasar login, memilih cabang, membaca dashboard, dan keluar dengan aman.',
                'izin' => [],
                'route' => 'dashboard',
                'langkah' => [
                    'Masuk menggunakan akun pribadi yang diberikan administrator.',
                    'Pilih cabang aktif sesuai lokasi kerja Anda.',
                    'Periksa nama pengguna dan cabang pada bagian atas halaman.',
                    'Gunakan menu di sisi kiri sesuai tugas dan hak akses Anda.',
                    'Buka Profil Saya untuk memperbarui data akun yang diizinkan.',
                ],
            ],
            [
                'kode' => 'master',
                'judul' => 'Master Data',
                'ikon' => 'database',
                'ringkasan' => 'Menyiapkan barang, pelanggan, pemasok, gudang, harga, pajak, dan data referensi.',
                'izin' => [
                    'MASTER_BARANG_LIHAT',
                    'MASTER_PELANGGAN_LIHAT',
                    'MASTER_PEMASOK_LIHAT',
                    'MASTER_GUDANG_LIHAT',
                    'DAFTAR_HARGA_LIHAT',
                ],
                'route' => 'barang.index',
                'langkah' => [
                    'Cari data terlebih dahulu sebelum menambahkan data baru agar tidak terjadi duplikasi.',
                    'Isi kode, nama, satuan, kategori, dan status aktif secara konsisten.',
                    'Periksa konversi satuan dan barcode sebelum barang dipakai pada transaksi.',
                    'Gunakan soft delete hanya untuk data yang tidak lagi dipakai.',
                    'Pastikan daftar harga dan stok minimum sesuai kebijakan toko.',
                ],
            ],
            [
                'kode' => 'persediaan',
                'judul' => 'Persediaan dan Gudang',
                'ikon' => 'boxes',
                'ringkasan' => 'Mengelola saldo, mutasi, stok awal, transfer, opname, dan penyesuaian stok.',
                'izin' => [
                    'PERSEDIAAN_LIHAT',
                    'STOK_AWAL_KELOLA',
                    'TRANSFER_STOK_KELOLA',
                    'STOK_OPNAME_KELOLA',
                    'PENYESUAIAN_STOK_KELOLA',
                ],
                'route' => 'persediaan.index',
                'langkah' => [
                    'Pastikan gudang dan lokasi penyimpanan sudah benar sebelum mencatat stok.',
                    'Gunakan stok awal hanya pada tahap inisialisasi atau koreksi resmi.',
                    'Transfer harus memiliki sumber dan tujuan yang berbeda.',
                    'Stok opname dilakukan berdasarkan hasil hitung fisik yang dapat dipertanggungjawabkan.',
                    'Setiap penyesuaian wajib memiliki alasan yang jelas untuk kebutuhan audit.',
                ],
            ],
            [
                'kode' => 'pembelian',
                'judul' => 'Pembelian dan Hutang Pemasok',
                'ikon' => 'shopping-bag',
                'ringkasan' => 'Alur permintaan, pesanan, penerimaan, faktur, retur, dan pembayaran hutang.',
                'izin' => [
                    'PEMBELIAN_LIHAT',
                    'HUTANG_PEMASOK_LIHAT',
                ],
                'route' => 'pembelian.index',
                'langkah' => [
                    'Pilih pemasok, gudang tujuan, dan tanggal dokumen dengan benar.',
                    'Cocokkan barang diterima dengan pesanan serta dokumen pemasok.',
                    'Periksa jumlah, satuan, harga, potongan, pajak, dan jatuh tempo sebelum persetujuan.',
                    'Catat retur berdasarkan transaksi sumber agar nilai dan stok tetap konsisten.',
                    'Alokasikan pembayaran hutang ke dokumen yang benar.',
                ],
            ],
            [
                'kode' => 'penjualan',
                'judul' => 'POS, Penjualan, dan Piutang',
                'ikon' => 'shopping-cart',
                'ringkasan' => 'Alur penawaran, pesanan, transaksi kasir, pembayaran, pengiriman, retur, dan piutang.',
                'izin' => [
                    'PENJUALAN_LIHAT',
                    'PIUTANG_PELANGGAN_LIHAT',
                ],
                'route' => 'penjualan.index',
                'langkah' => [
                    'Pilih pelanggan dan pastikan cabang transaksi sudah benar.',
                    'Pindai barcode atau pilih barang, lalu periksa satuan serta jumlah.',
                    'Periksa harga, potongan, pajak, biaya pengiriman, dan metode pembayaran.',
                    'Untuk transaksi tempo, pastikan batas kredit serta jatuh tempo telah disetujui.',
                    'Cetak nota hanya setelah transaksi aktif dan pembayaran tercatat benar.',
                ],
            ],
            [
                'kode' => 'keuangan',
                'judul' => 'Kas, Bank, dan Akuntansi',
                'ikon' => 'landmark',
                'ringkasan' => 'Mengelola transaksi kas, pemindahan dana, jurnal, pemetaan akun, dan laporan keuangan.',
                'izin' => [
                    'KEUANGAN_LIHAT',
                    'JURNAL_UMUM_LIHAT',
                    'LAPORAN_KAS_BANK_LIHAT',
                ],
                'route' => 'keuangan.index',
                'langkah' => [
                    'Pastikan rekening kas atau bank dan kategori transaksi sudah benar.',
                    'Periksa nilai serta keterangan sebelum menyetujui transaksi kas.',
                    'Pemindahan dana harus memakai sumber dan tujuan yang berbeda.',
                    'Jurnal manual wajib seimbang antara debit dan kredit.',
                    'Gunakan laporan untuk rekonsiliasi dan jangan mengubah transaksi yang sudah disetujui.',
                ],
            ],
            [
                'kode' => 'laporan',
                'judul' => 'Dashboard, Laporan, Ekspor, dan Nota',
                'ikon' => 'file-chart-column',
                'ringkasan' => 'Membaca KPI, memakai filter laporan, mengunduh CSV, dan mencetak dokumen.',
                'izin' => [
                    'DASHBOARD_BISNIS_LIHAT',
                    'LAPORAN_PENJUALAN_LIHAT',
                    'LAPORAN_PEMBELIAN_LIHAT',
                    'LAPORAN_STOK_LIHAT',
                    'LAPORAN_OPERASIONAL_UNDUH',
                ],
                'route' => 'laporan.index',
                'langkah' => [
                    'Pilih periode yang sesuai dan pastikan cabang aktif sudah benar.',
                    'Gunakan pencarian untuk mempersempit dokumen atau pihak terkait.',
                    'Cocokkan total ringkasan dengan transaksi sumber bila melakukan rekonsiliasi.',
                    'CSV mengikuti filter halaman dan harus disimpan pada lokasi yang aman.',
                    'Nota hanya boleh dicetak untuk transaksi aktif pada cabang yang sama.',
                ],
            ],
            [
                'kode' => 'audit',
                'judul' => 'Lampiran dan Audit Aktivitas',
                'ikon' => 'history',
                'ringkasan' => 'Mengelola dokumen pendukung dan menelusuri aktivitas penting pengguna.',
                'izin' => [
                    'LAMPIRAN_LIHAT',
                    'AUDIT_LIHAT',
                ],
                'route' => 'audit.index',
                'langkah' => [
                    'Unggah hanya berkas yang relevan, aman, dan sesuai batas ukuran.',
                    'Pastikan lampiran terhubung ke dokumen transaksi yang benar.',
                    'Gunakan filter audit berdasarkan tanggal, pengguna, modul, dan aktivitas.',
                    'Jangan menghapus bukti operasional tanpa otorisasi.',
                    'Laporkan aktivitas yang tidak dikenali kepada administrator.',
                ],
            ],
            [
                'kode' => 'administrasi',
                'judul' => 'Pengguna, Peran, dan Hak Akses',
                'ikon' => 'shield-check',
                'ringkasan' => 'Mengelola akun, peran, akses cabang, dan prinsip hak akses minimum.',
                'izin' => [
                    'PENGGUNA_LIHAT',
                    'PERAN_LIHAT',
                ],
                'route' => 'pengguna.index',
                'langkah' => [
                    'Buat akun terpisah untuk setiap pengguna dan jangan memakai akun bersama.',
                    'Berikan peran sesuai tanggung jawab kerja, bukan berdasarkan permintaan informal.',
                    'Batasi akses cabang hanya pada lokasi yang diperlukan.',
                    'Nonaktifkan akun pegawai yang sudah tidak bertugas.',
                    'Tinjau hak akses secara berkala dan simpan bukti persetujuan perubahan.',
                ],
            ],
            [
                'kode' => 'dukungan',
                'judul' => 'Dukungan dan Pelaporan Masalah',
                'ikon' => 'life-buoy',
                'ringkasan' => 'Informasi yang perlu disiapkan ketika meminta bantuan teknis.',
                'izin' => [],
                'route' => null,
                'langkah' => [
                    'Sertakan waktu kejadian, cabang, modul, dan nomor dokumen.',
                    'Tuliskan langkah yang dilakukan sebelum masalah muncul.',
                    'Lampirkan tangkapan layar tanpa kata sandi atau informasi rahasia.',
                    'Jelaskan apakah masalah dapat diulang dan siapa saja yang terdampak.',
                    'Jangan mengubah database secara manual tanpa prosedur dan backup.',
                ],
            ],
        ];
    }
}
