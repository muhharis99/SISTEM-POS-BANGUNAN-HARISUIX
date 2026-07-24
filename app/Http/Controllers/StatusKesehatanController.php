<?php

namespace App\Http\Controllers;

use App\Services\PemeriksaKesiapanProduksi;
use Illuminate\Http\JsonResponse;

class StatusKesehatanController extends Controller
{
    public function siap(PemeriksaKesiapanProduksi $pemeriksa): JsonResponse
    {
        $hasil = $pemeriksa->ringkasUntukEndpoint();
        $status = $hasil['status'] === 'siap' ? 200 : 503;

        return response()
            ->json($hasil, $status)
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
                'Pragma' => 'no-cache',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
    }
}
