<?php

namespace App\Console\Commands;

use App\Models\HakAkses;
use App\Models\Peran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SiapkanPembelianFaseLima extends Command
{
    protected $signature = 'fase5:siapkan';

    protected $description = 'Menyiapkan permission modul pembelian dan hutang pemasok Fase 5 tanpa mengubah skema paten';

    public function handle(): int
    {
        $hakAkses = [
            ['PEMBELIAN_LIHAT', 'Melihat ringkasan dan laporan pembelian', 'PEMBELIAN'],
            ['PERMINTAAN_PEMBELIAN_KELOLA', 'Mengelola permintaan pembelian', 'PEMBELIAN'],
            ['PERMINTAAN_PEMBELIAN_SETUJUI', 'Menyetujui atau menolak permintaan pembelian', 'PEMBELIAN'],
            ['PESANAN_PEMBELIAN_KELOLA', 'Mengelola pesanan pembelian', 'PEMBELIAN'],
            ['PESANAN_PEMBELIAN_SETUJUI', 'Menyetujui pesanan pembelian', 'PEMBELIAN'],
            ['PENERIMAAN_BARANG_KELOLA', 'Mengelola dokumen penerimaan barang', 'PEMBELIAN'],
            ['PENERIMAAN_BARANG_TERIMA', 'Menerima barang dan membentuk mutasi stok', 'PEMBELIAN'],
            ['FAKTUR_PEMBELIAN_KELOLA', 'Mengelola faktur pembelian', 'PEMBELIAN'],
            ['FAKTUR_PEMBELIAN_SETUJUI', 'Menyetujui faktur dan membentuk hutang', 'PEMBELIAN'],
            ['HUTANG_PEMASOK_LIHAT', 'Melihat saldo dan jatuh tempo hutang pemasok', 'PEMBELIAN'],
            ['PEMBAYARAN_HUTANG_KELOLA', 'Mengelola pembayaran hutang pemasok', 'PEMBELIAN'],
            ['PEMBAYARAN_HUTANG_SETUJUI', 'Menyetujui pembayaran dan alokasi hutang', 'PEMBELIAN'],
            ['RETUR_PEMBELIAN_KELOLA', 'Mengelola retur pembelian', 'PEMBELIAN'],
            ['RETUR_PEMBELIAN_SETUJUI', 'Menyetujui retur pembelian', 'PEMBELIAN'],
            ['RETUR_PEMBELIAN_KIRIM', 'Mengirim retur dan mengurangi stok', 'PEMBELIAN'],
            ['LAPORAN_PEMBELIAN_LIHAT', 'Melihat laporan pembelian, penerimaan, hutang, dan retur', 'PEMBELIAN'],
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
                'PEMBELIAN' => $semua,
                'GUDANG' => ['PEMBELIAN_LIHAT', 'PENERIMAAN_BARANG_KELOLA', 'PENERIMAAN_BARANG_TERIMA', 'RETUR_PEMBELIAN_KELOLA', 'RETUR_PEMBELIAN_KIRIM', 'LAPORAN_PEMBELIAN_LIHAT'],
                'KEUANGAN' => ['PEMBELIAN_LIHAT', 'FAKTUR_PEMBELIAN_KELOLA', 'FAKTUR_PEMBELIAN_SETUJUI', 'HUTANG_PEMASOK_LIHAT', 'PEMBAYARAN_HUTANG_KELOLA', 'PEMBAYARAN_HUTANG_SETUJUI', 'LAPORAN_PEMBELIAN_LIHAT'],
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

        $this->info('Permission Fase 5 berhasil disiapkan tanpa perubahan skema.');

        return self::SUCCESS;
    }
}
