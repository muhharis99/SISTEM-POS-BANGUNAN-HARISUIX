<?php

namespace App\Console\Commands;

use App\Models\HakAkses;
use App\Models\Peran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SiapkanDashboardLaporanFaseSembilan extends Command
{
    protected $signature = 'fase9:siapkan';

    protected $description = 'Menyiapkan permission dashboard, ekspor, dan cetak Fase 9 tanpa mengubah skema paten';

    public function handle(): int
    {
        $hakAkses = [
            ['DASHBOARD_BISNIS_LIHAT', 'Melihat ringkasan bisnis pada dashboard', 'LAPORAN'],
            ['LAPORAN_OPERASIONAL_UNDUH', 'Mengunduh laporan operasional', 'LAPORAN'],
            ['NOTA_PENJUALAN_CETAK', 'Mencetak nota penjualan', 'PENJUALAN'],
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
                'KEUANGAN' => ['DASHBOARD_BISNIS_LIHAT', 'LAPORAN_OPERASIONAL_UNDUH'],
                'KASIR' => ['DASHBOARD_BISNIS_LIHAT', 'NOTA_PENJUALAN_CETAK'],
                'GUDANG' => ['DASHBOARD_BISNIS_LIHAT'],
                'PEMBELIAN' => ['DASHBOARD_BISNIS_LIHAT'],
                'PENJUALAN' => ['DASHBOARD_BISNIS_LIHAT', 'NOTA_PENJUALAN_CETAK'],
            ];

            foreach ($matriks as $kodePeran => $daftarKode) {
                $peran = Peran::query()
                    ->where('kode_peran', $kodePeran)
                    ->whereNull('deleted_at')
                    ->first();

                if (! $peran) {
                    continue;
                }

                $idHak = HakAkses::query()
                    ->whereIn('kode_hak_akses', $daftarKode)
                    ->whereNull('deleted_at')
                    ->pluck('id_hak_akses');

                foreach ($idHak as $idHakAkses) {
                    DB::table('peran_hak_akses')->updateOrInsert(
                        ['id_peran' => $peran->id_peran, 'id_hak_akses' => $idHakAkses],
                        [
                            'created_at' => now(),
                            'created_by' => null,
                            'deleted_at' => null,
                            'deleted_by' => null,
                        ]
                    );
                }
            }
        });

        $this->info('Permission Fase 9 berhasil disiapkan tanpa perubahan skema.');

        return self::SUCCESS;
    }
}
