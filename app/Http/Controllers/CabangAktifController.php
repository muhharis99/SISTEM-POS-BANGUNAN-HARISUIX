<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Services\AuditAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CabangAktifController extends Controller
{
    public function ubah(Request $request, AuditAktivitas $audit): RedirectResponse
    {
        $data = $request->validate([
            'id_cabang' => ['required', 'integer'],
        ]);

        $pengguna = $request->user();
        $cabang = $pengguna->memilikiPeran('ADMINISTRATOR')
            ? Cabang::query()->aktif()->find($data['id_cabang'])
            : $pengguna->cabang()->aktif()->find($data['id_cabang']);

        abort_unless($cabang, 403, 'Cabang tidak tersedia untuk pengguna ini.');

        $sebelum = $request->session()->get('id_cabang_aktif');
        $request->session()->put('id_cabang_aktif', $cabang->id_cabang);
        $request->session()->put('nama_cabang_aktif', $cabang->nama_cabang);

        $audit->catat(
            $request,
            'ORGANISASI',
            'UBAH',
            'cabang',
            $cabang->id_cabang,
            'Mengubah cabang aktif.',
            ['id_cabang' => $sebelum],
            ['id_cabang' => $cabang->id_cabang],
        );

        return back()->with('berhasil', 'Cabang aktif diubah menjadi '.$cabang->nama_cabang.'.');
    }
}
