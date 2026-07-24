<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LayananAkuntansiRetur
{
    public function pastikanSiap(): void
    {
        DB::transaction(function (): void {
            $aset = $this->simpanAkun('100000', 'Aset', 'ASET', 'DEBET', false);
            $asetLancar = $this->simpanAkun('110000', 'Aset Lancar', 'ASET', 'DEBET', false, $aset);
            $piutang = $this->simpanAkun('110300', 'Piutang Usaha', 'ASET', 'DEBET', true, $asetLancar);
            $pendapatan = $this->simpanAkun('400000', 'Pendapatan', 'PENDAPATAN', 'KREDIT', false);
            $retur = $this->simpanAkun('410110', 'Retur dan Potongan Penjualan', 'PENDAPATAN', 'DEBET', true, $pendapatan);

            $this->simpanPemetaan('PIUTANG_USAHA', $piutang, 'Akun piutang pelanggan untuk jurnal retur.');
            $this->simpanPemetaan('RETUR_PENJUALAN', $retur, 'Akun kontra-pendapatan untuk retur dan pengembalian dana penjualan.');
        });
    }

    private function simpanAkun(
        string $kode,
        string $nama,
        string $kelompok,
        string $saldoNormal,
        bool $rincian,
        ?int $idInduk = null
    ): int {
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

        return (int) DB::table('akun_keuangan')->where('kode_akun', $kode)->value('id_akun_keuangan');
    }

    private function simpanPemetaan(string $kunci, int $idAkun, string $keterangan): void
    {
        DB::table('pemetaan_akun')->updateOrInsert(
            ['id_cabang' => null, 'kunci_pemetaan' => $kunci],
            [
                'id_akun_keuangan' => $idAkun,
                'keterangan' => $keterangan,
                'updated_at' => now(),
                'updated_by' => null,
            ]
        );
    }
}
