<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaseTigaSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->jenisPelangganDanPelangganTunai();
            $this->metodePembayaran();
            $this->tarifPajakNol();
            $this->gudangKhususSetiapCabang();
        });
    }

    private function jenisPelangganDanPelangganTunai(): void
    {
        $jenis = [
            ['UMUM', 'Umum'],
            ['TUKANG', 'Tukang'],
            ['KONTRAKTOR_PROYEK', 'Kontraktor/Proyek'],
            ['TOKO_RESELLER', 'Toko/Reseller'],
        ];

        foreach ($jenis as [$kode, $nama]) {
            DB::table('jenis_pelanggan')->updateOrInsert(
                ['kode_jenis_pelanggan' => $kode],
                [
                    'nama_jenis_pelanggan' => $nama,
                    'potongan_persen_bawaan' => 0,
                    'batas_piutang_bawaan' => 0,
                    'lama_jatuh_tempo_bawaan' => 0,
                    'status_aktif' => 1,
                    'updated_at' => now(),
                    'deleted_at' => null,
                    'deleted_by' => null,
                ]
            );
        }

        $idUmum = (int) DB::table('jenis_pelanggan')->where('kode_jenis_pelanggan', 'UMUM')->value('id_jenis_pelanggan');
        DB::table('pelanggan')->updateOrInsert(
            ['kode_pelanggan' => 'UMUM'],
            [
                'id_jenis_pelanggan' => $idUmum,
                'nama_pelanggan' => 'PELANGGAN TUNAI',
                'batas_piutang' => 0,
                'lama_jatuh_tempo' => 0,
                'potongan_persen' => 0,
                'status_aktif' => 1,
                'updated_at' => now(),
                'deleted_at' => null,
                'deleted_by' => null,
            ]
        );
    }

    private function metodePembayaran(): void
    {
        $metode = [
            ['TUNAI', 'Tunai', 'TUNAI'],
            ['TRANSFER', 'Transfer Bank', 'TRANSFER'],
            ['TEMPO', 'Pembayaran Tempo', 'TEMPO'],
        ];

        foreach ($metode as [$kode, $nama, $kelompok]) {
            DB::table('metode_pembayaran')->updateOrInsert(
                ['kode_metode_pembayaran' => $kode],
                [
                    'nama_metode_pembayaran' => $nama,
                    'kelompok_pembayaran' => $kelompok,
                    'biaya_persen' => 0,
                    'biaya_tetap' => 0,
                    'status_aktif' => 1,
                    'updated_at' => now(),
                    'deleted_at' => null,
                    'deleted_by' => null,
                ]
            );
        }
    }

    private function tarifPajakNol(): void
    {
        DB::table('tarif_pajak')->updateOrInsert(
            ['kode_tarif_pajak' => 'NON_PAJAK'],
            [
                'nama_tarif_pajak' => 'Non Pajak / 0%',
                'persen_pajak' => 0,
                'jenis_pajak' => 'KEDUANYA',
                'status_aktif' => 1,
                'updated_at' => now(),
                'deleted_at' => null,
                'deleted_by' => null,
            ]
        );
    }

    private function gudangKhususSetiapCabang(): void
    {
        $cabang = DB::table('cabang')->whereNull('deleted_at')->where('status_aktif', 1)->get();
        $jenis = [
            ['RUSAK', 'Gudang Barang Rusak', 'RUSAK', 'AREA-RUSAK', 'Area Barang Rusak'],
            ['RETUR', 'Gudang Retur', 'RETUR', 'AREA-RETUR', 'Area Barang Retur'],
        ];

        foreach ($cabang as $itemCabang) {
            foreach ($jenis as [$kode, $nama, $tipe, $kodeLokasi, $namaLokasi]) {
                DB::table('gudang')->updateOrInsert(
                    ['id_cabang' => $itemCabang->id_cabang, 'kode_gudang' => $kode],
                    [
                        'nama_gudang' => $nama,
                        'jenis_gudang' => $tipe,
                        'status_aktif' => 1,
                        'updated_at' => now(),
                        'deleted_at' => null,
                        'deleted_by' => null,
                    ]
                );
                $idGudang = (int) DB::table('gudang')
                    ->where('id_cabang', $itemCabang->id_cabang)
                    ->where('kode_gudang', $kode)
                    ->value('id_gudang');

                DB::table('lokasi_gudang')->updateOrInsert(
                    ['id_gudang' => $idGudang, 'kode_lokasi' => $kodeLokasi],
                    [
                        'id_lokasi_induk' => null,
                        'nama_lokasi' => $namaLokasi,
                        'jenis_lokasi' => 'AREA_UMUM',
                        'keterangan' => 'Lokasi khusus bawaan Fase 3.',
                        'status_aktif' => 1,
                        'updated_at' => now(),
                        'deleted_at' => null,
                        'deleted_by' => null,
                    ]
                );
            }
        }
    }
}
