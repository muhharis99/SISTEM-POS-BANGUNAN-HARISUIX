<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananPembelian
{
    public function __construct(private readonly LayananPersediaan $persediaan) {}

    public function nomorBerikutnya(int $idCabang, string $jenis, string $awalan, ?string $tanggal = null): string
    {
        return $this->persediaan->nomorBerikutnya($idCabang, $jenis, $awalan, $tanggal);
    }

    public function barangSatuan(int $idBarangSatuan): object
    {
        return $this->persediaan->barangSatuan($idBarangSatuan);
    }

    public function jumlahDasar(object $barangSatuan, mixed $jumlah, string $atribut): float
    {
        return $this->persediaan->jumlahDasar($barangSatuan, $jumlah, $atribut);
    }

    public function hitungBaris(
        float $jumlah,
        float $hargaSatuan,
        float $potonganPersen = 0,
        float $pajakPersen = 0,
    ): array {
        $kotor = round($jumlah * $hargaSatuan, 2);
        $potongan = round($kotor * max(0, $potonganPersen) / 100, 2);
        $dasarPajak = max(0, $kotor - $potongan);
        $pajak = round($dasarPajak * max(0, $pajakPersen) / 100, 2);

        return [
            'kotor' => $kotor,
            'potongan' => $potongan,
            'pajak' => $pajak,
            'total' => round($dasarPajak + $pajak, 2),
        ];
    }

    public function persenPajak(?int $idTarifPajak): float
    {
        if (! $idTarifPajak) {
            return 0;
        }

        $pajak = DB::table('tarif_pajak')
            ->where('id_tarif_pajak', $idTarifPajak)
            ->where('status_aktif', 1)
            ->whereNull('deleted_at')
            ->first();

        if (! $pajak) {
            throw ValidationException::withMessages(['id_tarif_pajak' => 'Tarif pajak tidak valid atau tidak aktif.']);
        }

        return (float) $pajak->persen_pajak;
    }

    public function pastikanPemasok(int $idPemasok): object
    {
        $pemasok = DB::table('pemasok')
            ->where('id_pemasok', $idPemasok)
            ->where('status_aktif', 1)
            ->whereNull('deleted_at')
            ->first();

        if (! $pemasok) {
            throw ValidationException::withMessages(['id_pemasok' => 'Pemasok tidak valid atau tidak aktif.']);
        }

        return $pemasok;
    }

    public function pastikanGudangLokasi(int $idCabang, int $idGudang, int $idLokasi): void
    {
        $valid = DB::table('lokasi_gudang as l')
            ->join('gudang as g', 'g.id_gudang', '=', 'l.id_gudang')
            ->where('g.id_cabang', $idCabang)
            ->where('g.id_gudang', $idGudang)
            ->where('l.id_lokasi_gudang', $idLokasi)
            ->where('g.status_aktif', 1)
            ->where('l.status_aktif', 1)
            ->whereNull('g.deleted_at')
            ->whereNull('l.deleted_at')
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages(['id_lokasi_gudang' => 'Lokasi tidak sesuai dengan gudang dan cabang aktif.']);
        }
    }

    public function perbaruiStatusPesanan(int $idPesanan, ?int $idPengguna = null): void
    {
        $pesanan = DB::table('pesanan_pembelian')
            ->where('id_pesanan_pembelian', $idPesanan)
            ->lockForUpdate()
            ->first();

        if (! $pesanan || in_array($pesanan->status_pesanan, ['DIBATALKAN', 'SELESAI'], true)) {
            return;
        }

        $detail = DB::table('pesanan_pembelian_detail')
            ->where('id_pesanan_pembelian', $idPesanan)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->get();

        $jumlah = (float) $detail->sum('jumlah');
        $diterima = (float) $detail->sum('jumlah_diterima');
        $difakturkan = (float) $detail->sum('jumlah_difakturkan');

        $status = $pesanan->status_pesanan;
        if ($jumlah > 0 && $diterima + 0.0001 >= $jumlah && $difakturkan + 0.0001 >= $jumlah) {
            $status = 'SELESAI';
        } elseif ($jumlah > 0 && $diterima + 0.0001 >= $jumlah) {
            $status = 'DITERIMA';
        } elseif ($diterima > 0) {
            $status = 'DITERIMA_SEBAGIAN';
        }

        DB::table('pesanan_pembelian')->where('id_pesanan_pembelian', $idPesanan)->update([
            'status_pesanan' => $status,
            'updated_at' => now(),
            'updated_by' => $idPengguna,
        ]);
    }

    public function perbaruiHutang(int $idHutang, ?int $idPengguna = null): object
    {
        $hutang = DB::table('hutang_pemasok')
            ->where('id_hutang_pemasok', $idHutang)
            ->lockForUpdate()
            ->first();

        if (! $hutang) {
            throw ValidationException::withMessages(['id_hutang_pemasok' => 'Hutang pemasok tidak ditemukan.']);
        }

        $sisa = round(
            (float) $hutang->nilai_awal
            - (float) $hutang->nilai_pembayaran
            - (float) $hutang->nilai_retur
            - (float) $hutang->nilai_penyesuaian,
            2
        );
        $sisa = max(0, $sisa);

        $status = 'BELUM_LUNAS';
        if ($hutang->status_hutang === 'DIBATALKAN') {
            $status = 'DIBATALKAN';
        } elseif ($sisa <= 0.009) {
            $status = 'LUNAS';
        } elseif ($sisa + 0.009 < (float) $hutang->nilai_awal) {
            $status = 'SEBAGIAN';
        }

        DB::table('hutang_pemasok')->where('id_hutang_pemasok', $idHutang)->update([
            'sisa_hutang' => $sisa,
            'status_hutang' => $status,
            'updated_at' => now(),
            'updated_by' => $idPengguna,
        ]);

        $totalDibayar = round((float) $hutang->nilai_pembayaran + (float) $hutang->nilai_retur + (float) $hutang->nilai_penyesuaian, 2);
        DB::table('faktur_pembelian')->where('id_faktur_pembelian', $hutang->id_faktur_pembelian)->update([
            'total_dibayar' => $totalDibayar,
            'sisa_hutang' => $sisa,
            'status_faktur' => $status === 'LUNAS' ? 'LUNAS' : ($status === 'SEBAGIAN' ? 'SEBAGIAN_DIBAYAR' : 'DISETUJUI'),
            'updated_at' => now(),
            'updated_by' => $idPengguna,
        ]);

        return (object) ['sisa_hutang' => $sisa, 'status_hutang' => $status];
    }

    public function catatPenerimaanStok(
        int $idCabang,
        object $penerimaan,
        object $detail,
        int $idPengguna,
    ): void {
        $barangSatuan = $this->barangSatuan((int) $detail->id_barang_satuan);
        $this->pastikanGudangLokasi($idCabang, (int) $penerimaan->id_gudang, (int) $detail->id_lokasi_gudang);

        $this->persediaan->catatMutasi(
            $idCabang,
            (int) $penerimaan->id_gudang,
            (int) $detail->id_lokasi_gudang,
            (int) $barangSatuan->id_barang,
            (float) $detail->jumlah_dasar_diterima,
            0,
            (float) $detail->harga_pokok,
            'PEMBELIAN',
            'PENERIMAAN_BARANG',
            (int) $penerimaan->id_penerimaan_barang,
            $penerimaan->nomor_penerimaan,
            $detail->keterangan,
            $idPengguna,
        );
    }

    public function catatReturStok(
        int $idCabang,
        object $retur,
        object $detail,
        int $idPengguna,
    ): void {
        $barangSatuan = $this->barangSatuan((int) $detail->id_barang_satuan);
        $this->pastikanGudangLokasi($idCabang, (int) $retur->id_gudang, (int) $detail->id_lokasi_gudang);

        $this->persediaan->catatMutasi(
            $idCabang,
            (int) $retur->id_gudang,
            (int) $detail->id_lokasi_gudang,
            (int) $barangSatuan->id_barang,
            0,
            (float) $detail->jumlah_dasar,
            (float) $detail->harga_satuan,
            'RETUR_PEMBELIAN',
            'RETUR_PEMBELIAN',
            (int) $retur->id_retur_pembelian,
            $retur->nomor_retur,
            $detail->keterangan,
            $idPengguna,
        );
    }
}
