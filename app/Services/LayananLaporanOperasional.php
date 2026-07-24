<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LayananLaporanOperasional
{
    private const STATUS_PENJUALAN_AKTIF = ['DISETUJUI', 'SEBAGIAN_DIBAYAR', 'LUNAS'];

    private const STATUS_FAKTUR_AKTIF = ['DISETUJUI', 'SEBAGIAN_DIBAYAR', 'LUNAS'];

    public function periode(string $tanggalAwal, string $tanggalAkhir): array
    {
        $awal = CarbonImmutable::createFromFormat('Y-m-d', $tanggalAwal)->startOfDay();
        $akhir = CarbonImmutable::createFromFormat('Y-m-d', $tanggalAkhir)->endOfDay();

        return [$awal, $akhir];
    }

    public function ringkasan(int $idCabang, string $tanggalAwal, string $tanggalAkhir): array
    {
        [$awal, $akhir] = $this->periode($tanggalAwal, $tanggalAkhir);

        $penjualan = $this->queryPenjualan($idCabang, $awal, $akhir);
        $pembelian = $this->queryPembelian($idCabang, $awal, $akhir);

        $totalPenjualan = (float) (clone $penjualan)->sum('pj.total_bersih');
        $jumlahPenjualan = (int) (clone $penjualan)->count('pj.id_penjualan');
        $totalPembelian = (float) (clone $pembelian)->sum('fp.total_bersih');
        $jumlahPembelian = (int) (clone $pembelian)->count('fp.id_faktur_pembelian');

        $labaKotor = (float) DB::table('penjualan_detail as pd')
            ->join('penjualan as pj', 'pj.id_penjualan', '=', 'pd.id_penjualan')
            ->where('pj.id_cabang', $idCabang)
            ->whereIn('pj.status_penjualan', self::STATUS_PENJUALAN_AKTIF)
            ->whereNull('pj.deleted_at')
            ->whereNull('pd.deleted_at')
            ->whereBetween('pj.tanggal_penjualan', [$awal, $akhir])
            ->sum('pd.laba_kotor');

        $sisaHutang = (float) DB::table('tampilan_hutang_pemasok')
            ->where('id_cabang', $idCabang)
            ->where('status_hutang', '!=', 'LUNAS')
            ->sum('sisa_hutang');

        $sisaPiutang = (float) DB::table('tampilan_piutang_pelanggan')
            ->where('id_cabang', $idCabang)
            ->where('status_piutang', '!=', 'LUNAS')
            ->sum('sisa_piutang');

        $stokMenipis = (int) DB::table('tampilan_stok_tersedia as ts')
            ->join('barang as b', 'b.id_barang', '=', 'ts.id_barang')
            ->where('ts.id_cabang', $idCabang)
            ->where('b.status_aktif', 1)
            ->whereNull('b.deleted_at')
            ->whereColumn('ts.jumlah_tersedia', '<=', 'b.stok_minimum')
            ->count();

        return [
            'total_penjualan' => $totalPenjualan,
            'jumlah_penjualan' => $jumlahPenjualan,
            'total_pembelian' => $totalPembelian,
            'jumlah_pembelian' => $jumlahPembelian,
            'laba_kotor' => $labaKotor,
            'sisa_hutang' => $sisaHutang,
            'sisa_piutang' => $sisaPiutang,
            'stok_menipis' => $stokMenipis,
            'saldo_kas_bank' => $this->saldoKasBank($idCabang, $akhir),
        ];
    }

    public function trenPenjualan(int $idCabang, string $tanggalAwal, string $tanggalAkhir): Collection
    {
        [$awal, $akhir] = $this->periode($tanggalAwal, $tanggalAkhir);

        return DB::table('penjualan as pj')
            ->where('pj.id_cabang', $idCabang)
            ->whereIn('pj.status_penjualan', self::STATUS_PENJUALAN_AKTIF)
            ->whereNull('pj.deleted_at')
            ->whereBetween('pj.tanggal_penjualan', [$awal, $akhir])
            ->groupByRaw('DATE(pj.tanggal_penjualan)')
            ->orderByRaw('DATE(pj.tanggal_penjualan)')
            ->selectRaw('DATE(pj.tanggal_penjualan) as tanggal, COUNT(*) as jumlah_transaksi, SUM(pj.total_bersih) as total_penjualan')
            ->get();
    }

    public function barangTerlaris(int $idCabang, string $tanggalAwal, string $tanggalAkhir, int $batas = 10): Collection
    {
        [$awal, $akhir] = $this->periode($tanggalAwal, $tanggalAkhir);

        return DB::table('penjualan_detail as pd')
            ->join('penjualan as pj', 'pj.id_penjualan', '=', 'pd.id_penjualan')
            ->join('barang_satuan as bs', 'bs.id_barang_satuan', '=', 'pd.id_barang_satuan')
            ->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')
            ->join('satuan as s', 's.id_satuan', '=', 'b.id_satuan_dasar')
            ->where('pj.id_cabang', $idCabang)
            ->whereIn('pj.status_penjualan', self::STATUS_PENJUALAN_AKTIF)
            ->whereNull('pj.deleted_at')
            ->whereNull('pd.deleted_at')
            ->whereBetween('pj.tanggal_penjualan', [$awal, $akhir])
            ->groupBy('b.id_barang', 'b.kode_barang', 'b.nama_barang', 's.kode_satuan')
            ->orderByDesc('jumlah_dasar')
            ->limit($batas)
            ->selectRaw('b.id_barang, b.kode_barang, b.nama_barang, s.kode_satuan, SUM(pd.jumlah_dasar) as jumlah_dasar, SUM(pd.total_baris) as nilai_penjualan, SUM(pd.laba_kotor) as laba_kotor')
            ->get();
    }

    public function laporan(
        string $jenis,
        int $idCabang,
        string $tanggalAwal,
        string $tanggalAkhir,
        ?string $pencarian = null,
        int $batas = 250
    ): Collection {
        [$awal, $akhir] = $this->periode($tanggalAwal, $tanggalAkhir);
        $query = $this->queryJenis($jenis, $idCabang, $awal, $akhir, $pencarian);

        return $query->limit($batas)->get();
    }

    public function barisEkspor(
        string $jenis,
        int $idCabang,
        string $tanggalAwal,
        string $tanggalAkhir,
        ?string $pencarian = null
    ): iterable {
        [$awal, $akhir] = $this->periode($tanggalAwal, $tanggalAkhir);

        foreach ($this->queryJenis($jenis, $idCabang, $awal, $akhir, $pencarian)->cursor() as $baris) {
            yield $this->ubahBarisEkspor($jenis, $baris);
        }
    }

    public function headerEkspor(string $jenis): array
    {
        return match ($jenis) {
            'penjualan' => ['Tanggal', 'Nomor Penjualan', 'Pelanggan', 'Jenis', 'Status', 'Total Bersih', 'Dibayar', 'Piutang', 'Laba Kotor'],
            'pembelian' => ['Tanggal', 'Nomor Internal', 'Nomor Pemasok', 'Pemasok', 'Cara Bayar', 'Status', 'Total Bersih', 'Dibayar', 'Hutang'],
            'persediaan' => ['Kode Barang', 'Nama Barang', 'Gudang', 'Lokasi', 'Satuan', 'Stok', 'Dipesan', 'Rusak', 'Tersedia', 'Stok Minimum'],
            'hutang' => ['Tanggal', 'Jatuh Tempo', 'Pemasok', 'Nomor Faktur', 'Nilai Awal', 'Pembayaran', 'Retur', 'Sisa Hutang', 'Status', 'Hari Terlambat'],
            'piutang' => ['Tanggal', 'Jatuh Tempo', 'Pelanggan', 'Nomor Penjualan', 'Nilai Awal', 'Pembayaran', 'Retur', 'Sisa Piutang', 'Status', 'Hari Terlambat'],
            'kas' => ['Tanggal', 'Nomor', 'Kas/Bank', 'Tujuan', 'Jenis', 'Nilai', 'Status', 'Keterangan'],
            default => [],
        };
    }

    public function notaPenjualan(int $idCabang, int $idPenjualan): array
    {
        $penjualan = DB::table('penjualan as pj')
            ->leftJoin('pelanggan as pl', 'pl.id_pelanggan', '=', 'pj.id_pelanggan')
            ->leftJoin('metode_pembayaran as mp', 'mp.id_metode_pembayaran', '=', 'pj.id_metode_pembayaran')
            ->leftJoin('pengguna as pg', 'pg.id_pengguna', '=', 'pj.created_by')
            ->where('pj.id_penjualan', $idPenjualan)
            ->where('pj.id_cabang', $idCabang)
            ->whereIn('pj.status_penjualan', self::STATUS_PENJUALAN_AKTIF)
            ->whereNull('pj.deleted_at')
            ->select('pj.*', 'pl.nama_pelanggan', 'pl.telepon as telepon_pelanggan', 'mp.nama_metode_pembayaran', 'pg.nama_tampilan as nama_kasir')
            ->first();

        if (! $penjualan) {
            abort(404);
        }

        $detail = DB::table('penjualan_detail as pd')
            ->join('barang_satuan as bs', 'bs.id_barang_satuan', '=', 'pd.id_barang_satuan')
            ->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')
            ->join('satuan as s', 's.id_satuan', '=', 'bs.id_satuan')
            ->where('pd.id_penjualan', $idPenjualan)
            ->whereNull('pd.deleted_at')
            ->orderBy('pd.id_penjualan_detail')
            ->select('pd.*', 'b.kode_barang', 'b.nama_barang', 's.kode_satuan', 's.nama_satuan')
            ->get();

        $cabang = DB::table('cabang')
            ->where('id_cabang', $idCabang)
            ->whereNull('deleted_at')
            ->first();

        return compact('penjualan', 'detail', 'cabang');
    }

    private function saldoKasBank(int $idCabang, CarbonImmutable $sampai): float
    {
        $saldoAwal = (float) DB::table('kas_bank')
            ->where('id_cabang', $idCabang)
            ->where('status_aktif', 1)
            ->whereNull('deleted_at')
            ->sum('saldo_awal');

        $masuk = (float) DB::table('transaksi_kas')
            ->where('id_cabang', $idCabang)
            ->where('status_transaksi', 'DISETUJUI')
            ->where('jenis_transaksi', 'MASUK')
            ->whereNull('deleted_at')
            ->where('tanggal_transaksi', '<=', $sampai)
            ->sum('nilai_transaksi');

        $keluar = (float) DB::table('transaksi_kas')
            ->where('id_cabang', $idCabang)
            ->where('status_transaksi', 'DISETUJUI')
            ->where('jenis_transaksi', 'KELUAR')
            ->whereNull('deleted_at')
            ->where('tanggal_transaksi', '<=', $sampai)
            ->sum('nilai_transaksi');

        return $saldoAwal + $masuk - $keluar;
    }

    private function queryJenis(
        string $jenis,
        int $idCabang,
        CarbonImmutable $awal,
        CarbonImmutable $akhir,
        ?string $pencarian
    ): Builder {
        return match ($jenis) {
            'penjualan' => $this->queryPenjualan($idCabang, $awal, $akhir, $pencarian)
                ->leftJoin('penjualan_detail as pd', function ($join): void {
                    $join->on('pd.id_penjualan', '=', 'pj.id_penjualan')->whereNull('pd.deleted_at');
                })
                ->orderByDesc('pj.tanggal_penjualan')
                ->select(
                    'pj.id_penjualan',
                    'pj.tanggal_penjualan',
                    'pj.nomor_penjualan',
                    'pl.nama_pelanggan',
                    'pj.jenis_penjualan',
                    'pj.status_penjualan',
                    'pj.total_bersih',
                    'pj.total_dibayar',
                    'pj.sisa_piutang',
                    DB::raw('COALESCE(SUM(pd.laba_kotor), 0) as laba_kotor')
                )
                ->groupBy(
                    'pj.id_penjualan',
                    'pj.tanggal_penjualan',
                    'pj.nomor_penjualan',
                    'pl.nama_pelanggan',
                    'pj.jenis_penjualan',
                    'pj.status_penjualan',
                    'pj.total_bersih',
                    'pj.total_dibayar',
                    'pj.sisa_piutang'
                ),
            'pembelian' => $this->queryPembelian($idCabang, $awal, $akhir, $pencarian)
                ->orderByDesc('fp.tanggal_faktur')
                ->select(
                    'fp.id_faktur_pembelian',
                    'fp.tanggal_faktur',
                    'fp.nomor_faktur_internal',
                    'fp.nomor_faktur_pemasok',
                    'p.nama_pemasok',
                    'fp.cara_pembayaran',
                    'fp.status_faktur',
                    'fp.total_bersih',
                    'fp.total_dibayar',
                    'fp.sisa_hutang'
                ),
            'persediaan' => $this->queryPersediaan($idCabang, $pencarian),
            'hutang' => $this->queryHutang($idCabang, $awal, $akhir, $pencarian),
            'piutang' => $this->queryPiutang($idCabang, $awal, $akhir, $pencarian),
            'kas' => $this->queryKas($idCabang, $awal, $akhir, $pencarian),
            default => abort(404),
        };
    }

    private function queryPenjualan(
        int $idCabang,
        CarbonImmutable $awal,
        CarbonImmutable $akhir,
        ?string $pencarian = null
    ): Builder {
        return DB::table('penjualan as pj')
            ->leftJoin('pelanggan as pl', 'pl.id_pelanggan', '=', 'pj.id_pelanggan')
            ->where('pj.id_cabang', $idCabang)
            ->whereIn('pj.status_penjualan', self::STATUS_PENJUALAN_AKTIF)
            ->whereNull('pj.deleted_at')
            ->whereBetween('pj.tanggal_penjualan', [$awal, $akhir])
            ->when($pencarian, function (Builder $query, string $cari): void {
                $query->where(function (Builder $sub) use ($cari): void {
                    $sub->where('pj.nomor_penjualan', 'like', "%{$cari}%")
                        ->orWhere('pl.nama_pelanggan', 'like', "%{$cari}%");
                });
            });
    }

    private function queryPembelian(
        int $idCabang,
        CarbonImmutable $awal,
        CarbonImmutable $akhir,
        ?string $pencarian = null
    ): Builder {
        return DB::table('faktur_pembelian as fp')
            ->join('pemasok as p', 'p.id_pemasok', '=', 'fp.id_pemasok')
            ->where('fp.id_cabang', $idCabang)
            ->whereIn('fp.status_faktur', self::STATUS_FAKTUR_AKTIF)
            ->whereNull('fp.deleted_at')
            ->whereBetween('fp.tanggal_faktur', [$awal->toDateString(), $akhir->toDateString()])
            ->when($pencarian, function (Builder $query, string $cari): void {
                $query->where(function (Builder $sub) use ($cari): void {
                    $sub->where('fp.nomor_faktur_internal', 'like', "%{$cari}%")
                        ->orWhere('fp.nomor_faktur_pemasok', 'like', "%{$cari}%")
                        ->orWhere('p.nama_pemasok', 'like', "%{$cari}%");
                });
            });
    }

    private function queryPersediaan(int $idCabang, ?string $pencarian): Builder
    {
        return DB::table('tampilan_stok_tersedia as ts')
            ->join('barang as b', 'b.id_barang', '=', 'ts.id_barang')
            ->where('ts.id_cabang', $idCabang)
            ->where('b.status_aktif', 1)
            ->whereNull('b.deleted_at')
            ->when($pencarian, function (Builder $query, string $cari): void {
                $query->where(function (Builder $sub) use ($cari): void {
                    $sub->where('ts.kode_barang', 'like', "%{$cari}%")
                        ->orWhere('ts.nama_barang', 'like', "%{$cari}%")
                        ->orWhere('ts.nama_gudang', 'like', "%{$cari}%")
                        ->orWhere('ts.nama_lokasi', 'like', "%{$cari}%");
                });
            })
            ->orderBy('ts.nama_barang')
            ->select('ts.*', 'b.stok_minimum');
    }

    private function queryHutang(
        int $idCabang,
        CarbonImmutable $awal,
        CarbonImmutable $akhir,
        ?string $pencarian
    ): Builder {
        return DB::table('tampilan_hutang_pemasok')
            ->where('id_cabang', $idCabang)
            ->whereBetween('tanggal_hutang', [$awal->toDateString(), $akhir->toDateString()])
            ->when($pencarian, function (Builder $query, string $cari): void {
                $query->where(function (Builder $sub) use ($cari): void {
                    $sub->where('nama_pemasok', 'like', "%{$cari}%")
                        ->orWhere('nomor_faktur_internal', 'like', "%{$cari}%")
                        ->orWhere('nomor_faktur_pemasok', 'like', "%{$cari}%");
                });
            })
            ->orderByDesc('tanggal_hutang');
    }

    private function queryPiutang(
        int $idCabang,
        CarbonImmutable $awal,
        CarbonImmutable $akhir,
        ?string $pencarian
    ): Builder {
        return DB::table('tampilan_piutang_pelanggan')
            ->where('id_cabang', $idCabang)
            ->whereBetween('tanggal_piutang', [$awal->toDateString(), $akhir->toDateString()])
            ->when($pencarian, function (Builder $query, string $cari): void {
                $query->where(function (Builder $sub) use ($cari): void {
                    $sub->where('nama_pelanggan', 'like', "%{$cari}%")
                        ->orWhere('nomor_penjualan', 'like', "%{$cari}%");
                });
            })
            ->orderByDesc('tanggal_piutang');
    }

    private function queryKas(
        int $idCabang,
        CarbonImmutable $awal,
        CarbonImmutable $akhir,
        ?string $pencarian
    ): Builder {
        return DB::table('transaksi_kas as tk')
            ->join('kas_bank as kb', 'kb.id_kas_bank', '=', 'tk.id_kas_bank')
            ->leftJoin('kas_bank as tujuan', 'tujuan.id_kas_bank', '=', 'tk.id_kas_bank_tujuan')
            ->where('tk.id_cabang', $idCabang)
            ->whereNull('tk.deleted_at')
            ->whereBetween('tk.tanggal_transaksi', [$awal, $akhir])
            ->when($pencarian, function (Builder $query, string $cari): void {
                $query->where(function (Builder $sub) use ($cari): void {
                    $sub->where('tk.nomor_transaksi', 'like', "%{$cari}%")
                        ->orWhere('tk.keterangan', 'like', "%{$cari}%")
                        ->orWhere('kb.nama_kas_bank', 'like', "%{$cari}%");
                });
            })
            ->orderByDesc('tk.tanggal_transaksi')
            ->select('tk.*', 'kb.nama_kas_bank', 'tujuan.nama_kas_bank as nama_kas_bank_tujuan');
    }

    private function ubahBarisEkspor(string $jenis, object $baris): array
    {
        return match ($jenis) {
            'penjualan' => [
                $baris->tanggal_penjualan,
                $baris->nomor_penjualan,
                $baris->nama_pelanggan ?: 'Pelanggan Umum',
                $baris->jenis_penjualan,
                $baris->status_penjualan,
                $baris->total_bersih,
                $baris->total_dibayar,
                $baris->sisa_piutang,
                $baris->laba_kotor,
            ],
            'pembelian' => [
                $baris->tanggal_faktur,
                $baris->nomor_faktur_internal,
                $baris->nomor_faktur_pemasok,
                $baris->nama_pemasok,
                $baris->cara_pembayaran,
                $baris->status_faktur,
                $baris->total_bersih,
                $baris->total_dibayar,
                $baris->sisa_hutang,
            ],
            'persediaan' => [
                $baris->kode_barang,
                $baris->nama_barang,
                $baris->nama_gudang,
                $baris->nama_lokasi,
                $baris->satuan_dasar,
                $baris->jumlah_stok,
                $baris->jumlah_dipesan,
                $baris->jumlah_rusak,
                $baris->jumlah_tersedia,
                $baris->stok_minimum,
            ],
            'hutang' => [
                $baris->tanggal_hutang,
                $baris->tanggal_jatuh_tempo,
                $baris->nama_pemasok,
                $baris->nomor_faktur_internal,
                $baris->nilai_awal,
                $baris->nilai_pembayaran,
                $baris->nilai_retur,
                $baris->sisa_hutang,
                $baris->status_hutang,
                $baris->jumlah_hari_terlambat,
            ],
            'piutang' => [
                $baris->tanggal_piutang,
                $baris->tanggal_jatuh_tempo,
                $baris->nama_pelanggan,
                $baris->nomor_penjualan,
                $baris->nilai_awal,
                $baris->nilai_pembayaran,
                $baris->nilai_retur,
                $baris->sisa_piutang,
                $baris->status_piutang,
                $baris->jumlah_hari_terlambat,
            ],
            'kas' => [
                $baris->tanggal_transaksi,
                $baris->nomor_transaksi,
                $baris->nama_kas_bank,
                $baris->nama_kas_bank_tujuan,
                $baris->jenis_transaksi,
                $baris->nilai_transaksi,
                $baris->status_transaksi,
                $baris->keterangan,
            ],
            default => [],
        };
    }
}
