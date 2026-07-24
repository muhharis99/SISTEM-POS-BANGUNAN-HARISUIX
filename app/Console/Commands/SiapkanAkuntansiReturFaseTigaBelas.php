<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SiapkanAkuntansiReturFaseTigaBelas extends Command
{
    protected $signature = 'fase13:siapkan-akuntansi-retur';

    protected $description = 'Menyiapkan akun kontra-pendapatan dan pemetaan retur penjualan tanpa mengubah skema paten';

    public function handle(): int
    {
        DB::transaction(function (): void {
            $idInduk = (int) DB::table('akun_keuangan')->where('kode_akun', '400000')->value('id_akun_keuangan');

            DB::table('akun_keuangan')->updateOrInsert(
                ['kode_akun' => '410110'],
                [
                    'id_akun_induk' => $idInduk ?: null,
                    'nama_akun' => 'Retur dan Potongan Penjualan',
                    'kelompok_akun' => 'PENDAPATAN',
                    'saldo_normal' => 'DEBET',
                    'akun_rincian' => 1,
                    'status_aktif' => 1,
                    'updated_at' => now(),
                    'updated_by' => null,
                    'deleted_at' => null,
                    'deleted_by' => null,
                ]
            );

            $idAkun = (int) DB::table('akun_keuangan')->where('kode_akun', '410110')->value('id_akun_keuangan');

            DB::table('pemetaan_akun')->updateOrInsert(
                ['id_cabang' => null, 'kunci_pemetaan' => 'RETUR_PENJUALAN'],
                [
                    'id_akun_keuangan' => $idAkun,
                    'keterangan' => 'Akun kontra-pendapatan untuk retur dan pengembalian dana penjualan',
                    'updated_at' => now(),
                    'updated_by' => null,
                ]
            );
        });

        $this->info('Akun dan pemetaan RETUR_PENJUALAN berhasil disiapkan tanpa perubahan skema.');

        return self::SUCCESS;
    }
}