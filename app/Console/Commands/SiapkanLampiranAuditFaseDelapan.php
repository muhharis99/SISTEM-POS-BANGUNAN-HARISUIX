<?php

namespace App\Console\Commands;

use App\Models\HakAkses;
use App\Models\Peran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SiapkanLampiranAuditFaseDelapan extends Command
{
    protected $signature = 'fase8:siapkan';

    protected $description = 'Menyiapkan permission Fase 8 tanpa mengubah skema paten';

    public function handle(): int
    {
        $hakAkses = [
            ['LAMPIRAN_LIHAT', 'Melihat lampiran dokumen', 'LAMPIRAN'],
            ['LAMPIRAN_UNGGAH', 'Mengunggah lampiran dokumen', 'LAMPIRAN'],
            ['LAMPIRAN_UNDUH', 'Mengunduh lampiran dokumen', 'LAMPIRAN'],
            ['LAMPIRAN_HAPUS', 'Menghapus lampiran dokumen', 'LAMPIRAN'],
            ['AUDIT_LIHAT_DATA', 'Melihat data sebelum dan sesudah pada audit', 'AUDIT'],
            ['AUDIT_UNDUH', 'Mengunduh hasil audit', 'AUDIT'],
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
                'KEUANGAN' => $semua,
                'KASIR' => ['LAMPIRAN_LIHAT', 'LAMPIRAN_UNGGAH', 'LAMPIRAN_UNDUH'],
                'GUDANG' => ['LAMPIRAN_LIHAT', 'LAMPIRAN_UNGGAH', 'LAMPIRAN_UNDUH'],
                'PEMBELIAN' => ['LAMPIRAN_LIHAT', 'LAMPIRAN_UNGGAH', 'LAMPIRAN_UNDUH'],
                'PENJUALAN' => ['LAMPIRAN_LIHAT', 'LAMPIRAN_UNGGAH', 'LAMPIRAN_UNDUH'],
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

        $this->info('Permission Fase 8 berhasil disiapkan tanpa perubahan skema.');

        return self::SUCCESS;
    }
}
