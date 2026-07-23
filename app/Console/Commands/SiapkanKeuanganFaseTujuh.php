<?php

namespace App\Console\Commands;

use App\Models\HakAkses;
use App\Models\Peran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SiapkanKeuanganFaseTujuh extends Command
{
    protected $signature = 'fase7:siapkan';

    protected $description = 'Menyiapkan permission, bagan akun, dan pemetaan akun Fase 7 tanpa mengubah skema paten';

    public function handle(): int
    {
        $hakAkses = [
            ['KEUANGAN_LIHAT', 'Melihat ringkasan kas, bank, dan akuntansi', 'KEUANGAN'],
            ['AKUN_KEUANGAN_LIHAT', 'Melihat bagan akun keuangan', 'KEUANGAN'],
            ['AKUN_KEUANGAN_KELOLA', 'Mengelola bagan akun keuangan', 'KEUANGAN'],
            ['PEMETAAN_AKUN_KELOLA', 'Mengelola pemetaan akun per cabang', 'KEUANGAN'],
            ['TRANSAKSI_KAS_LIHAT', 'Melihat transaksi kas dan bank', 'KEUANGAN'],
            ['TRANSAKSI_KAS_KELOLA', 'Mengelola transaksi kas dan bank', 'KEUANGAN'],
            ['TRANSAKSI_KAS_SETUJUI', 'Menyetujui transaksi kas dan bank', 'KEUANGAN'],
            ['JURNAL_UMUM_LIHAT', 'Melihat jurnal umum', 'KEUANGAN'],
            ['JURNAL_UMUM_KELOLA', 'Mengelola jurnal umum', 'KEUANGAN'],
            ['JURNAL_UMUM_POSTING', 'Memposting jurnal umum', 'KEUANGAN'],
            ['LAPORAN_KAS_BANK_LIHAT', 'Melihat buku kas dan bank', 'KEUANGAN'],
            ['LAPORAN_BUKU_BESAR_LIHAT', 'Melihat buku besar', 'KEUANGAN'],
            ['LAPORAN_NERACA_SALDO_LIHAT', 'Melihat neraca saldo', 'KEUANGAN'],
            ['LAPORAN_KEUANGAN_LIHAT', 'Melihat laba rugi dan neraca', 'KEUANGAN'],
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
                'KASIR' => [
                    'KEUANGAN_LIHAT',
                    'TRANSAKSI_KAS_LIHAT',
                    'TRANSAKSI_KAS_KELOLA',
                    'LAPORAN_KAS_BANK_LIHAT',
                ],
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

            $akun = [];
            $simpanAkun = function (
                string $kode,
                string $nama,
                string $kelompok,
                string $saldoNormal,
                bool $rincian,
                ?string $kodeInduk = null
            ) use (&$akun): int {
                $idInduk = $kodeInduk ? ($akun[$kodeInduk] ?? null) : null;

                DB::table('akun_keuangan')->updateOrInsert(
                    ['kode_akun' => $kode],
                    [
                        'id_akun_induk' => $idInduk,
                        'nama_akun' => $nama,
                        'kelompok_akun' => $kelompok,
                        'saldo_normal' => $saldoNormal,
                        'akun_rincian' => $rincian ? 1 : 0,
                        'status_aktif' => 1,
                        'updated_at' => now(),
                        'updated_by' => null,
                        'deleted_at' => null,
                        'deleted_by' => null,
                    ]
                );

                $id = (int) DB::table('akun_keuangan')->where('kode_akun', $kode)->value('id_akun_keuangan');
                $akun[$kode] = $id;

                return $id;
            };

            $simpanAkun('100000', 'Aset', 'ASET', 'DEBET', false);
            $simpanAkun('110000', 'Aset Lancar', 'ASET', 'DEBET', false, '100000');
            $simpanAkun('110100', 'Kas', 'ASET', 'DEBET', true, '110000');
            $simpanAkun('110200', 'Bank', 'ASET', 'DEBET', true, '110000');
            $simpanAkun('110300', 'Piutang Usaha', 'ASET', 'DEBET', true, '110000');
            $simpanAkun('110400', 'Persediaan Barang', 'ASET', 'DEBET', true, '110000');
            $simpanAkun('110500', 'Pajak Masukan', 'ASET', 'DEBET', true, '110000');

            $simpanAkun('200000', 'Kewajiban', 'KEWAJIBAN', 'KREDIT', false);
            $simpanAkun('210000', 'Kewajiban Lancar', 'KEWAJIBAN', 'KREDIT', false, '200000');
            $simpanAkun('210100', 'Hutang Usaha', 'KEWAJIBAN', 'KREDIT', true, '210000');
            $simpanAkun('210200', 'Pajak Keluaran', 'KEWAJIBAN', 'KREDIT', true, '210000');

            $simpanAkun('300000', 'Modal', 'MODAL', 'KREDIT', false);
            $simpanAkun('310100', 'Modal Pemilik', 'MODAL', 'KREDIT', true, '300000');

            $simpanAkun('400000', 'Pendapatan', 'PENDAPATAN', 'KREDIT', false);
            $simpanAkun('410100', 'Penjualan', 'PENDAPATAN', 'KREDIT', true, '400000');
            $simpanAkun('410200', 'Pendapatan Lain-lain', 'PENDAPATAN', 'KREDIT', true, '400000');

            $simpanAkun('500000', 'Harga Pokok Penjualan', 'BEBAN', 'DEBET', false);
            $simpanAkun('510100', 'Harga Pokok Penjualan', 'BEBAN', 'DEBET', true, '500000');

            $simpanAkun('600000', 'Beban Operasional', 'BEBAN', 'DEBET', false);
            $simpanAkun('610100', 'Beban Operasional Umum', 'BEBAN', 'DEBET', true, '600000');
            $simpanAkun('610200', 'Beban Pengiriman', 'BEBAN', 'DEBET', true, '600000');
            $simpanAkun('610300', 'Beban Administrasi Bank', 'BEBAN', 'DEBET', true, '600000');

            $pemetaan = [
                'KAS' => '110100',
                'BANK' => '110200',
                'PIUTANG_USAHA' => '110300',
                'PERSEDIAAN' => '110400',
                'PAJAK_MASUKAN' => '110500',
                'HUTANG_USAHA' => '210100',
                'PAJAK_KELUARAN' => '210200',
                'MODAL_PEMILIK' => '310100',
                'PENJUALAN' => '410100',
                'PENDAPATAN_LAIN' => '410200',
                'HARGA_POKOK_PENJUALAN' => '510100',
                'BEBAN_OPERASIONAL' => '610100',
                'BEBAN_PENGIRIMAN' => '610200',
                'BEBAN_ADMIN_BANK' => '610300',
            ];

            foreach ($pemetaan as $kunci => $kodeAkun) {
                DB::table('pemetaan_akun')->updateOrInsert(
                    ['id_cabang' => null, 'kunci_pemetaan' => $kunci],
                    [
                        'id_akun_keuangan' => $akun[$kodeAkun],
                        'keterangan' => 'Pemetaan bawaan Fase 7',
                        'updated_at' => now(),
                        'updated_by' => null,
                    ]
                );
            }

            DB::table('kas_bank')
                ->where('status_aktif', 1)
                ->whereNull('deleted_at')
                ->orderBy('id_kas_bank')
                ->get()
                ->each(function (object $kasBank) use ($akun): void {
                    $kodeAkun = $kasBank->jenis_kas_bank === 'BANK' ? '110200' : '110100';
                    DB::table('pemetaan_akun')->updateOrInsert(
                        [
                            'id_cabang' => $kasBank->id_cabang,
                            'kunci_pemetaan' => 'KAS_BANK_'.$kasBank->id_kas_bank,
                        ],
                        [
                            'id_akun_keuangan' => $akun[$kodeAkun],
                            'keterangan' => 'Pemetaan otomatis '.$kasBank->nama_kas_bank,
                            'updated_at' => now(),
                            'updated_by' => null,
                        ]
                    );
                });
        });

        $this->info('Permission, bagan akun, dan pemetaan akun Fase 7 berhasil disiapkan tanpa perubahan skema.');

        return self::SUCCESS;
    }
}
