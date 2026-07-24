<?php

namespace App\Http\Middleware;

use App\Services\LayananAkuntansiRetur;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PastikanAkuntansiReturSiap
{
    public function handle(Request $request, Closure $next): Response
    {
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $idRetur = (int) $request->route('id');

        if ($idCabang > 0 && $idRetur > 0) {
            $cara = DB::table('retur_penjualan')
                ->where('id_retur_penjualan', $idRetur)
                ->where('id_cabang', $idCabang)
                ->whereNull('deleted_at')
                ->value('cara_pengembalian_dana');

            if (in_array($cara, ['POTONG_PIUTANG', 'TUNAI', 'TRANSFER'], true)) {
                app(LayananAkuntansiRetur::class)->pastikanSiap();
            }
        }

        return $next($request);
    }
}
