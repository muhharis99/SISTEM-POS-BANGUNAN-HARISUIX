<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PersediaanController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'PERSEDIAAN_LIHAT');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $pencarian = trim((string) $request->query('pencarian'));
        $idGudang = (int) $request->query('id_gudang', 0);
        $hanyaMenipis = $request->boolean('menipis');

        $saldo = DB::table('tampilan_stok_tersedia as s')
            ->join('barang as b', 'b.id_barang', '=', 's.id_barang')
            ->where('s.id_cabang', $idCabang)
            ->when($idGudang > 0, fn ($query) => $query->where('s.id_gudang', $idGudang))
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('s.kode_barang', 'like', "%{$pencarian}%")
                    ->orWhere('s.nama_barang', 'like', "%{$pencarian}%")
                    ->orWhere('s.kode_gudang', 'like', "%{$pencarian}%")
                    ->orWhere('s.kode_lokasi', 'like', "%{$pencarian}%");
            }))
            ->when($hanyaMenipis, fn ($query) => $query->whereColumn('s.jumlah_tersedia', '<=', 'b.stok_minimum'))
            ->select('s.*', 'b.stok_minimum', 'b.stok_maksimum')
            ->orderBy('s.nama_barang')
            ->orderBy('s.nama_gudang')
            ->paginate(20)
            ->withQueryString();

        $mutasi = DB::table('mutasi_stok as m')
            ->join('barang as b', 'b.id_barang', '=', 'm.id_barang')
            ->join('gudang as g', 'g.id_gudang', '=', 'm.id_gudang')
            ->join('lokasi_gudang as l', 'l.id_lokasi_gudang', '=', 'm.id_lokasi_gudang')
            ->where('m.id_cabang', $idCabang)
            ->select('m.*', 'b.kode_barang', 'b.nama_barang', 'g.nama_gudang', 'l.nama_lokasi')
            ->orderByDesc('m.tanggal_mutasi')
            ->limit(30)
            ->get();

        $gudang = DB::table('gudang')
            ->where('id_cabang', $idCabang)
            ->where('status_aktif', 1)
            ->whereNull('deleted_at')
            ->orderBy('nama_gudang')
            ->get();

        $ringkasan = DB::table('tampilan_stok_tersedia')
            ->where('id_cabang', $idCabang)
            ->selectRaw('COUNT(DISTINCT id_barang) AS jumlah_barang')
            ->selectRaw('COALESCE(SUM(jumlah_stok), 0) AS jumlah_stok')
            ->selectRaw('COALESCE(SUM(jumlah_rusak), 0) AS jumlah_rusak')
            ->selectRaw('COALESCE(SUM(jumlah_tersedia), 0) AS jumlah_tersedia')
            ->first();

        return view('persediaan.index', compact(
            'saldo',
            'mutasi',
            'gudang',
            'ringkasan',
            'pencarian',
            'idGudang',
            'hanyaMenipis'
        ));
    }

    public function kartu(Request $request, int $idBarang): View
    {
        $this->pastikanAkses($request, 'LAPORAN_STOK_LIHAT');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $barang = DB::table('barang')->where('id_barang', $idBarang)->whereNull('deleted_at')->first();
        abort_if(! $barang, 404);

        $mutasi = DB::table('mutasi_stok as m')
            ->join('gudang as g', 'g.id_gudang', '=', 'm.id_gudang')
            ->join('lokasi_gudang as l', 'l.id_lokasi_gudang', '=', 'm.id_lokasi_gudang')
            ->where('m.id_cabang', $idCabang)
            ->where('m.id_barang', $idBarang)
            ->when($request->filled('tanggal_awal'), fn ($query) => $query->whereDate('m.tanggal_mutasi', '>=', $request->query('tanggal_awal')))
            ->when($request->filled('tanggal_akhir'), fn ($query) => $query->whereDate('m.tanggal_mutasi', '<=', $request->query('tanggal_akhir')))
            ->select('m.*', 'g.nama_gudang', 'l.nama_lokasi')
            ->orderByDesc('m.tanggal_mutasi')
            ->paginate(50)
            ->withQueryString();

        return view('persediaan.kartu', compact('barang', 'mutasi'));
    }

    private function pastikanAkses(Request $request, string $izin): void
    {
        abort_unless($request->user()?->memilikiHakAkses($izin, (int) $request->session()->get('id_cabang_aktif')), 403);
    }
}
