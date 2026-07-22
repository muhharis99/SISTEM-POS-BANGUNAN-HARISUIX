<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanHakAkses
{
    public function handle(Request $request, Closure $next, string $kodeHakAkses): Response
    {
        $pengguna = $request->user();
        $idCabang = $request->session()->get('id_cabang_aktif');

        abort_unless($pengguna?->memilikiHakAkses($kodeHakAkses, $idCabang), 403, 'Anda tidak memiliki hak akses untuk tindakan ini.');

        return $next($request);
    }
}
