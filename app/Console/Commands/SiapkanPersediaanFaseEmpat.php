<?php

namespace App\Console\Commands;

use App\Models\HakAkses;
use App\Models\Peran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SiapkanPersediaanFaseEmpat extends Command
{
    protected $signature = 'fase4:siapkan';

    protected $description = 'Menyiapkan permission modul persediaan Fase 4 tanpa mengubah skema paten';

    public function handle(): int
    {
        $hakAkses = [
            ['PERSEDIAAN_LIHAT', 'Melihat saldo dan mutasi persediaan', 'PERSEDIAAN'],
            ['STOK_AWAL_KELOLA', 'Mengelola dokumen stok awal', 'PERSEDIAAN'],
            ['STOK_AWAL_SETUJUI', 'Menyetujui stok awal', 'PERSEDIAAN'],
            ['TRANSFER_STOK_KELOLA', 'Mengelola transfer stok', 'PERSEDIAAN'],
            ['TRANSFER_STOK_SETUJUI', 'Menyetujui transfer stok', 'PERSEDIAAN'],
            ['TRANSFER_STOK_KIRIM', 'Mengirim transfer stok', 'PERSEDIAAN'],
            ['TRANSFER_STOK_TERIMA', 'Menerima transfer stok', 'PERSEDIAAN'],
            ['STOK_OPNAME_KELOLA', 'Mengelola stok opname', 'PERSEDIAAN'],
            ['STOK_OPNAME_SETUJUI', 'Menyetujui hasil stok opname', 'PERSEDIAAN'],
            ['PENYESUAIAN_STOK_KELOLA', 'Mengelola penyesuaian stok', 'PERSEDIAAN'],
            ['PENYESUAIAN_STOK_SETUJUI', 'Menyetujui penyesuaian stok', 'PERSEDIAAN'],
            ['LAPORAN_STOK_LIHAT', 'Melihat laporan dan kartu stok', 'PERSEDIAAN'],
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
                'GUDANG' => $semua,
                'PEMBELIAN' => ['PERSEDIAAN_LIHAT', 'LAPORAN_STOK_LIHAT'],
                'PENJUALAN' => ['PERSEDIAAN_LIHAT', 'LAPORAN_STOK_LIHAT'],
                'KASIR' => ['PERSEDIAAN_LIHAT'],
                'KEUANGAN' => ['PERSEDIAAN_LIHAT', 'LAPORAN_STOK_LIHAT'],
            ];

            foreach ($matriks as $kodePeran => $daftarKode) {
                $peran = Peran::query()->where('kode_peran', $kodePeran)->whereNull('deleted_at')->first();
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
                        ['created_at' => now(), 'created_by' => null, 'deleted_at' => null, 'deleted_by' => null]
                    );
                }
            }
        });

        $this->info('Permission Fase 4 berhasil disiapkan tanpa perubahan skema.');

        return self::SUCCESS;
    }
}
