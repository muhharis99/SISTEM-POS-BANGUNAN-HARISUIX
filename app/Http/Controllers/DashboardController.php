<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $pengguna = $request->user();
        $idCabang = (int) $request->session()->get('id_cabang_aktif');

        return view('dashboard.index', [
            'jumlahPenggunaAktif' => Pengguna::query()->aktif()->count(),
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
