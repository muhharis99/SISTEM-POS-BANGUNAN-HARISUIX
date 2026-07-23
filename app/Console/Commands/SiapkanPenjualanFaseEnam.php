<?php

namespace App\Console\Commands;

use App\Models\HakAkses;
use App\Models\Peran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SiapkanPenjualanFaseEnam extends Command
{
    protected $signature = 'fase6:siapkan';

    protected $description = 'Menyiapkan permission modul penjualan, piutang, pengiriman, dan retur Fase 6 tanpa mengubah skema paten';

    public function handle(): int
    {
        $hakAkses = [
            ['PENJUALAN_LIHAT', 'Melihat ringkasan dan transaksi penjualan', 'PENJUALAN'],
            ['PENAWARAN_PENJUALAN_KELOLA', 'Mengelola penawaran penjualan', 'PENJUALAN'],
            ['PESANAN_PENJUALAN_KELOLA', 'Mengelola pesanan penjualan', 'PENJUALAN'],
            ['PESANAN_PENJUALAN_SETUJUI', 'Menyetujui pesanan penjualan', 'PENJUALAN'],
            ['TRANSAKSI_PENJUALAN_KELOLA', 'Mengelola transaksi penjualan', 'PENJUALAN'],
            ['TRANSAKSI_PENJUALAN_SETUJUI', 'Menyetujui penjualan dan mutasi stok', 'PENJUALAN'],
            ['PIUTANG_PELANGGAN_LIHAT', 'Melihat saldo dan jatuh tempo piutang pelanggan', 'PENJUALAN'],
            ['PEMBAYARAN_PIUTANG_KELOLA', 'Mengelola pembayaran piutang pelanggan', 'PENJUALAN'],
            ['PEMBAYARAN_PIUTANG_SETUJUI', 'Menyetujui pembayaran dan alokasi piutang', 'PENJUALAN'],
            ['PENGIRIMAN_KELOLA', 'Mengelola dokumen pengiriman', 'PENJUALAN'],
            ['PENGIRIMAN_JADWALKAN', 'Menjadwalkan pengiriman', 'PENJUALAN'],
            ['PENGIRIMAN_KIRIM', 'Memberangkatkan pengiriman', 'PENJUALAN'],
            ['PENGIRIMAN_TERIMA', 'Menerima dan menyelesaikan pengiriman', 'PENJUALAN'],
            ['RETUR_PENJUALAN_KELOLA', 'Mengelola retur penjualan', 'PENJUALAN'],
            ['RETUR_PENJUALAN_SETUJUI', 'Menyetujui retur penjualan', 'PENJUALAN'],
            ['RETUR_PENJUALAN_TERIMA', 'Menerima retur dan menambah stok', 'PENJUALAN'],
            ['LAPORAN_PENJUALAN_LIHAT', 'Melihat laporan penjualan, laba, piutang, pengiriman, dan retur', 'PENJUALAN'],
            ['LAPORAN_PIUTANG_LIHAT', 'Melihat laporan piutang dan pembayaran pelanggan', 'PENJUALAN'],
        ];

        DB::transaction(function () use ($hakAkses): void {
            foreach ($hakAkses as [$kode, $nama, $modul]) {
                HakAkses::query()->updateOrCreate(
                    ['kode_hak_akses' => $kode],
                    [
                        'nama_hak_akses' => $nama,
                        'nama_modul' => $modul,
                        'keterangan' => null,
                        'status_aktif' => 1,
                        'updated_at' => now(),
                        'deleted_at' => null,
                        'deleted_by' => null,
                    ]
                );
            }

            $semua = array_column($hakAkses, 0);
            $matriks = [
                'ADMINISTRATOR' => $semua,
                'PEMILIK' => $semua,
                'PENJUALAN' => $semua,
                'KASIR' => [
                    'PENJUALAN_LIHAT',
                    'PENAWARAN_PENJUALAN_KELOLA',
                    'PESANAN_PENJUALAN_KELOLA',
                    'TRANSAKSI_PENJUALAN_KELOLA',
                    'TRANSAKSI_PENJUALAN_SETUJUI',
                    'PIUTANG_PELANGGAN_LIHAT',
                    'PEMBAYARAN_PIUTANG_KELOLA',
                    'LAPORAN_PENJUALAN_LIHAT',
                ],
                'GUDANG' => [
                    'PENJUALAN_LIHAT',
                    'PENGIRIMAN_KELOLA',
                    'PENGIRIMAN_JADWALKAN',
                    'PENGIRIMAN_KIRIM',
                    'PENGIRIMAN_TERIMA',
                    'RETUR_PENJUALAN_KELOLA',
                    'RETUR_PENJUALAN_TERIMA',
                    'LAPORAN_PENJUALAN_LIHAT',
                ],
                'KEUANGAN' => [
                    'PENJUALAN_LIHAT',
                    'PIUTANG_PELANGGAN_LIHAT',
                    'PEMBAYARAN_PIUTANG_KELOLA',
                    'PEMBAYARAN_PIUTANG_SETUJUI',
                    'RETUR_PENJUALAN_SETUJUI',
                    'LAPORAN_PENJUALAN_LIHAT',
                    'LAPORAN_PIUTANG_LIHAT',
                ],
            ];

            foreach ($matriks as $kodePeran => $daftarKode) {
                $peran = Peran::query()->where('kode_peran', $kodePeran)->whereNull('deleted_at')->first();
                if (! $peran) {
                    continue;
                }

                $idHak = HakAkses::query()->whereIn('kode_hak_akses', $daftarKode)->whereNull('deleted_at')->pluck('id_hak_akses');
                foreach ($idHak as $idHakAkses) {
                    DB::table('peran_hak_akses')->updateOrInsert(
                        ['id_peran' => $peran->id_peran, 'id_hak_akses' => $idHakAkses],
                        ['created_at' => now(), 'created_by' => null, 'deleted_at' => null, 'deleted_by' => null]
                    );
                }
            }
        });

        $this->info('Permission Fase 6 berhasil disiapkan tanpa perubahan skema.');

        return self::SUCCESS;
    }
}
