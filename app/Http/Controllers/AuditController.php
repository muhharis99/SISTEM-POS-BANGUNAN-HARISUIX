<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $pencarian = trim((string) $request->query('pencarian'));

        $aktivitas = LogAktivitas::query()
            ->where(function ($query) use ($idCabang): void {
                $query->whereNull('id_cabang')->orWhere('id_cabang', $idCabang);
            })
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('nama_modul', 'like', "%{$pencarian}%")
                    ->orWhere('jenis_aktivitas', 'like', "%{$pencarian}%")
                    ->orWhere('keterangan', 'like', "%{$pencarian}%")
                    ->orWhere('alamat_ip', 'like', "%{$pencarian}%");
            }))
            ->latest('tanggal_aktivitas')
            ->paginate(25)
            ->withQueryString();

        return view('audit.index', compact('aktivitas', 'pencarian'));
    }
}
