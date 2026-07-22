<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $pengguna = $request->user();
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $hariIni = now()->toDateString();

        return view('dashboard.index', [
            'jumlahPenggunaAktif' => Pengguna::query()->aktif()->count(),
            'jumlahBarangAktif' => DB::table('barang')->whereNull('deleted_at')->where('status_aktif', 1)->count(),
            'jumlahPelangganAktif' => DB::table('pelanggan')->whereNull('deleted_at')->where('status_aktif', 1)->count(),
            'jumlahGudangAktif' => DB::table('gudang')->where('id_cabang', $idCabang)->whereNull('deleted_at')->where('status_aktif', 1)->count(),
            'jumlahDaftarHargaAktif' => DB::table('daftar_harga')
                ->where('id_cabang', $idCabang)
                ->whereNull('deleted_at')
                ->where('status_aktif', 1)
                ->where('tanggal_mulai', '<=', $hariIni)
                ->where(function ($query) use ($hariIni): void {
                    $query->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $hariIni);
                })
                ->count(),
            'aktivitasTerbaru' => LogAktivitas::query()
                ->where(function ($query) use ($idCabang): void {
                    $query->whereNull('id_cabang')->orWhere('id_cabang', $idCabang);
                })
                ->latest('tanggal_aktivitas')
                ->limit(8)
                ->get(),
            'daftarPeran' => $pengguna->peran()->aktif()->pluck('nama_peran'),
        ]);
    }
}
