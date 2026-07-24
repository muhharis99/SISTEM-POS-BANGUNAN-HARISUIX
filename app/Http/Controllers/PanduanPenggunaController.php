<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use App\Services\KatalogPanduanPengguna;
use Illuminate\Contracts\View\View;

class PanduanPenggunaController extends Controller
{
    public function index(KatalogPanduanPengguna $katalog): View
    {
        $pengguna = auth()->user();
        abort_unless($pengguna instanceof Pengguna, 403);

        $panduan = $katalog->untuk($pengguna, session('id_cabang_aktif'));

        return view('panduan.index', [
            'panduan' => $panduan,
            'prosedurUmum' => $katalog->prosedurUmum(),
            'jumlahPanduan' => count($panduan),
        ]);
    }
}
