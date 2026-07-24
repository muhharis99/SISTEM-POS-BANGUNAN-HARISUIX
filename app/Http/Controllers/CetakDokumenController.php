<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use App\Services\LayananLaporanOperasional;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CetakDokumenController extends Controller
{
    public function notaPenjualan(
        Request $request,
        int $id,
        LayananLaporanOperasional $layanan,
        AuditAktivitas $audit
    ): View {
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $data = $layanan->notaPenjualan($idCabang, $id);

        $audit->catat(
            $request,
            'PENJUALAN',
            'CETAK',
            'penjualan',
            $id,
            'Mencetak nota penjualan '.$data['penjualan']->nomor_penjualan
        );

        return view('cetak.nota-penjualan', $data);
    }
}
