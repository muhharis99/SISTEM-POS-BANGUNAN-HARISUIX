<?php

namespace App\Console\Commands;

use App\Models\HakAkses;
use App\Models\Peran;
use Database\Seeders\FaseTigaSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SiapkanMasterFaseTiga extends Command
{
    protected $signature = 'fase3:siapkan';

    protected $description = 'Menyiapkan permission dan data awal master Fase 3 tanpa mengubah skema paten';

    public function handle(FaseTigaSeeder $seeder): int
    {
        $hakAkses = [
            ['MASTER_BARANG_LIHAT', 'Melihat master barang', 'MASTER_BARANG'],
            ['MASTER_BARANG_KELOLA', 'Mengelola master barang', 'MASTER_BARANG'],
            ['MASTER_PELANGGAN_LIHAT', 'Melihat pelanggan', 'MASTER_PELANGGAN'],
            ['MASTER_PELANGGAN_KELOLA', 'Mengelola pelanggan', 'MASTER_PELANGGAN'],
            ['MASTER_PEMASOK_LIHAT', 'Melihat pemasok', 'MASTER_PEMASOK'],
            ['MASTER_PEMASOK_KELOLA', 'Mengelola pemasok', 'MASTER_PEMASOK'],
            ['MASTER_GUDANG_LIHAT', 'Melihat gudang dan lokasi', 'MASTER_GUDANG'],
            ['MASTER_GUDANG_KELOLA', 'Mengelola gudang dan lokasi', 'MASTER_GUDANG'],
            ['MASTER_KEUANGAN_LIHAT', 'Melihat kas, bank, metode pembayaran, dan kategori biaya', 'MASTER_KEUANGAN'],
            ['MASTER_KEUANGAN_KELOLA', 'Mengelola kas, bank, metode pembayaran, dan kategori biaya', 'MASTER_KEUANGAN'],
            ['MASTER_ARMADA_LIHAT', 'Melihat armada', 'MASTER_ARMADA'],
            ['MASTER_ARMADA_KELOLA', 'Mengelola armada', 'MASTER_ARMADA'],
            ['MASTER_PAJAK_LIHAT', 'Melihat tarif pajak', 'MASTER_PAJAK'],
            ['MASTER_PAJAK_KELOLA', 'Mengelola tarif pajak', 'MASTER_PAJAK'],
            ['DAFTAR_HARGA_LIHAT', 'Melihat daftar harga', 'DAFTAR_HARGA'],
            ['DAFTAR_HARGA_KELOLA', 'Mengelola daftar harga', 'DAFTAR_HARGA'],
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

            $matriks = [
                'ADMINISTRATOR' => array_column($hakAkses, 0),
                'PEMILIK' => array_column($hakAkses, 0),
                'GUDANG' => ['MASTER_BARANG_LIHAT', 'MASTER_BARANG_KELOLA', 'MASTER_GUDANG_LIHAT', 'MASTER_GUDANG_KELOLA'],
                'PEMBELIAN' => ['MASTER_BARANG_LIHAT', 'MASTER_PEMASOK_LIHAT', 'MASTER_PEMASOK_KELOLA'],
                'PENJUALAN' => ['MASTER_BARANG_LIHAT', 'MASTER_PELANGGAN_LIHAT', 'MASTER_PELANGGAN_KELOLA', 'DAFTAR_HARGA_LIHAT', 'DAFTAR_HARGA_KELOLA'],
                'KASIR' => ['MASTER_BARANG_LIHAT', 'MASTER_PELANGGAN_LIHAT', 'DAFTAR_HARGA_LIHAT'],
                'KEUANGAN' => ['MASTER_PELANGGAN_LIHAT', 'MASTER_PEMASOK_LIHAT', 'MASTER_KEUANGAN_LIHAT', 'MASTER_KEUANGAN_KELOLA', 'MASTER_PAJAK_LIHAT', 'MASTER_PAJAK_KELOLA'],
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

        $seeder->run();
        $this->info('Permission dan data awal Fase 3 berhasil disiapkan.');

        return self::SUCCESS;
    }
}
