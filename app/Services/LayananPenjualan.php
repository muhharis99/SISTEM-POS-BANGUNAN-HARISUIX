<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananPenjualan
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

    public function hitungBaris(float $jumlah, float $hargaSatuan, float $potonganPersen = 0, float $pajakPersen = 0): array
    {
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

    public function pelanggan(?int $idPelanggan, bool $wajib = true): ?object
    {
        if (! $idPelanggan) {
            if ($wajib) {
                throw ValidationException::withMessages(['id_pelanggan' => 'Pelanggan wajib dipilih.']);
            }

            return null;
        }

        $pelanggan = DB::table('pelanggan')
            ->where('id_pelanggan', $idPelanggan)
            ->where('status_aktif', 1)
            ->whereNull('deleted_at')
            ->first();

        if (! $pelanggan) {
            throw ValidationException::withMessages(['id_pelanggan' => 'Pelanggan tidak valid atau tidak aktif.']);
        }

        return $pelanggan;
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

    public function pastikanBatasKredit(int $idPelanggan, float $tambahan): void
    {
        $pelanggan = $this->pelanggan($idPelanggan);
        $batas = (float) $pelanggan->batas_piutang;
        if ($batas <= 0) {
            return;
        }

        $berjalan = (float) DB::table('piutang_pelanggan')
            ->where('id_pelanggan', $idPelanggan)
            ->whereNotIn('status_piutang', ['LUNAS', 'DIBATALKAN'])
            ->sum('sisa_piutang');

        if ($berjalan + $tambahan > $batas + 0.009) {
            throw ValidationException::withMessages([
                'id_pelanggan' => 'Batas piutang pelanggan terlampaui. Batas Rp '.number_format($batas, 0, ',', '.').'.',
            ]);
        }
    }

    public function catatPenjualanStok(int $idCabang, object $penjualan, object $detail, int $idPengguna): object
    {
        $barangSatuan = $this->barangSatuan((int) $detail->id_barang_satuan);
        $this->pastikanGudangLokasi($idCabang, (int) $penjualan->id_gudang, (int) $detail->id_lokasi_gudang);

        return $this->persediaan->catatMutasi(
            $idCabang,
            (int) $penjualan->id_gudang,
            (int) $detail->id_lokasi_gudang,
            (int) $barangSatuan->id_barang,
            0,
            (float) $detail->jumlah_dasar,
            0,
            'PENJUALAN',
            'PENJUALAN',
            (int) $penjualan->id_penjualan,
            $penjualan->nomor_penjualan,
            $detail->keterangan,
            $idPengguna,
        );
    }

    public function catatReturStok(int $idCabang, object $retur, object $detail, int $idPengguna): object
    {
        $barangSatuan = $this->barangSatuan((int) $detail->id_barang_satuan);
        $idGudang = (int) $retur->id_gudang;
        $idLokasi = (int) $detail->id_lokasi_gudang;

        if (! (bool) $detail->bisa_dijual_kembali) {
            $rusak = DB::table('gudang as g')
                ->join('lokasi_gudang as l', 'l.id_gudang', '=', 'g.id_gudang')
                ->where('g.id_cabang', $idCabang)
                ->where('g.jenis_gudang', 'RUSAK')
                ->where('g.status_aktif', 1)
                ->where('l.status_aktif', 1)
                ->whereNull('g.deleted_at')
                ->whereNull('l.deleted_at')
                ->select('g.id_gudang', 'l.id_lokasi_gudang')
                ->orderBy('l.id_lokasi_gudang')
                ->first();

            if (! $rusak) {
                throw ValidationException::withMessages(['kondisi_barang' => 'Gudang dan lokasi RUSAK aktif belum tersedia.']);
            }

            $idGudang = (int) $rusak->id_gudang;
            $idLokasi = (int) $rusak->id_lokasi_gudang;
        }

        $this->pastikanGudangLokasi($idCabang, $idGudang, $idLokasi);

        return $this->persediaan->catatMutasi(
            $idCabang,
            $idGudang,
            $idLokasi,
            (int) $barangSatuan->id_barang,
            (float) $detail->jumlah_dasar,
            0,
            (float) DB::table('saldo_stok')
                ->where('id_gudang', (int) $retur->id_gudang)
                ->where('id_lokasi_gudang', (int) $detail->id_lokasi_gudang)
                ->where('id_barang', (int) $barangSatuan->id_barang)
                ->value('harga_pokok_rata_rata'),
            'RETUR_PENJUALAN',
            'RETUR_PENJUALAN',
            (int) $retur->id_retur_penjualan,
            $retur->nomor_retur,
            $detail->keterangan,
            $idPengguna,
        );
    }

    public function perbaruiPiutang(int $idPiutang, ?int $idPengguna = null): object
    {
        $piutang = DB::table('piutang_pelanggan')
            ->where('id_piutang_pelanggan', $idPiutang)
            ->lockForUpdate()
            ->first();

        if (! $piutang) {
            throw ValidationException::withMessages(['id_piutang_pelanggan' => 'Piutang pelanggan tidak ditemukan.']);
        }

        $sisa = max(0, round(
            (float) $piutang->nilai_awal
            - (float) $piutang->nilai_pembayaran
            - (float) $piutang->nilai_retur
            - (float) $piutang->nilai_penyesuaian,
            2
        ));

        $status = 'BELUM_LUNAS';
        if ($piutang->status_piutang === 'DIBATALKAN') {
            $status = 'DIBATALKAN';
        } elseif ($sisa <= 0.009) {
            $status = 'LUNAS';
        } elseif ($sisa + 0.009 < (float) $piutang->nilai_awal) {
            $status = 'SEBAGIAN';
        }

        DB::table('piutang_pelanggan')->where('id_piutang_pelanggan', $idPiutang)->update([
            'sisa_piutang' => $sisa,
            'status_piutang' => $status,
            'updated_at' => now(),
            'updated_by' => $idPengguna,
        ]);

        $totalDibayar = round((float) $piutang->nilai_pembayaran + (float) $piutang->nilai_retur + (float) $piutang->nilai_penyesuaian, 2);
        DB::table('penjualan')->where('id_penjualan', $piutang->id_penjualan)->update([
            'total_dibayar' => $totalDibayar,
            'sisa_piutang' => $sisa,
            'status_penjualan' => $status === 'LUNAS' ? 'LUNAS' : ($status === 'SEBAGIAN' ? 'SEBAGIAN_DIBAYAR' : 'DISETUJUI'),
            'updated_at' => now(),
            'updated_by' => $idPengguna,
        ]);

        return (object) ['sisa_piutang' => $sisa, 'status_piutang' => $status];
    }

    public function perbaruiStatusPesanan(int $idPesanan, ?int $idPengguna = null): void
    {
        $pesanan = DB::table('pesanan_penjualan')->where('id_pesanan_penjualan', $idPesanan)->lockForUpdate()->first();
        if (! $pesanan || in_array($pesanan->status_pesanan, ['DIBATALKAN', 'SELESAI'], true)) {
            return;
        }

        $detail = DB::table('pesanan_penjualan_detail')
            ->where('id_pesanan_penjualan', $idPesanan)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->get();
        $jumlah = (float) $detail->sum('jumlah');
        $dikirim = (float) $detail->sum('jumlah_dikirim');
        $difakturkan = (float) $detail->sum('jumlah_difakturkan');

        $status = $pesanan->status_pesanan;
        if ($jumlah > 0 && $dikirim + 0.0001 >= $jumlah && $difakturkan + 0.0001 >= $jumlah) {
            $status = 'SELESAI';
        } elseif ($jumlah > 0 && $dikirim + 0.0001 >= $jumlah) {
            $status = 'DIKIRIM';
        } elseif ($dikirim > 0) {
            $status = 'DIKIRIM_SEBAGIAN';
        } elseif ($difakturkan > 0) {
            $status = 'DIPROSES';
        }

        DB::table('pesanan_penjualan')->where('id_pesanan_penjualan', $idPesanan)->update([
            'status_pesanan' => $status,
            'updated_at' => now(),
            'updated_by' => $idPengguna,
        ]);
    }

    public function perbaruiStatusPengirimanPenjualan(int $idPenjualan, ?int $idPengguna = null): void
    {
        $penjualan = DB::table('penjualan')->where('id_penjualan', $idPenjualan)->lockForUpdate()->first();
        if (! $penjualan) {
            return;
        }

        $detail = DB::table('penjualan_detail')->where('id_penjualan', $idPenjualan)->whereNull('deleted_at')->get();
        $jumlah = (float) $detail->sum('jumlah');
        $diterima = (float) DB::table('pengiriman_detail as d')
            ->join('pengiriman as h', 'h.id_pengiriman', '=', 'd.id_pengiriman')
            ->where('h.id_penjualan', $idPenjualan)
            ->where('h.status_pengiriman', 'DITERIMA')
            ->whereNull('h.deleted_at')
            ->whereNull('d.deleted_at')
            ->sum('d.jumlah_diterima');

        $status = 'BELUM_DIKIRIM';
        if ($jumlah > 0 && $diterima + 0.0001 >= $jumlah) {
            $status = 'DIKIRIM';
        } elseif ($diterima > 0) {
            $status = 'SEBAGIAN';
        }

        DB::table('penjualan')->where('id_penjualan', $idPenjualan)->update([
            'status_pengiriman' => $status,
            'updated_at' => now(),
            'updated_by' => $idPengguna,
        ]);
    }
}
