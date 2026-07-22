<?php

namespace App\Http\Middleware;

use App\Models\Cabang;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanCabangAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();
        $idCabang = $request->session()->get('id_cabang_aktif');

        if (! $pengguna) {
            return redirect()->route('masuk');
        }

        $cabangTersedia = $pengguna->memilikiPeran('ADMINISTRATOR')
            ? Cabang::query()->aktif()->orderBy('nama_cabang')->get()
            : $pengguna->cabang()->aktif()->orderBy('nama_cabang')->get();

        if ($cabangTersedia->isEmpty()) {
            abort(403, 'Pengguna belum memiliki cabang aktif.');
        }

        if ($idCabang === null || ! $cabangTersedia->contains('id_cabang', (int) $idCabang)) {
            $cabang = $cabangTersedia->first();
            $request->session()->put('id_cabang_aktif', $cabang->id_cabang);
            $request->session()->put('nama_cabang_aktif', $cabang->nama_cabang);
        }

        view()->share('cabangTersedia', $cabangTersedia);
        view()->share('cabangAktif', $cabangTersedia->firstWhere('id_cabang', (int) $request->session()->get('id_cabang_aktif')));

        return $next($request);
    }
}
