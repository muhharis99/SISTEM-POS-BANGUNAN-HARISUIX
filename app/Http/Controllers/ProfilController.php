<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function tampilkan(Request $request): View
    {
        return view('profil.index', ['pengguna' => $request->user()]);
    }

    public function ubahKataSandi(Request $request, AuditAktivitas $audit): RedirectResponse
    {
        $data = $request->validate([
            'kata_sandi_saat_ini' => ['required', 'current_password'],
            'kata_sandi_baru' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [], [
            'kata_sandi_saat_ini' => 'kata sandi saat ini',
            'kata_sandi_baru' => 'kata sandi baru',
        ]);

        $pengguna = $request->user();
        $pengguna->forceFill([
            'kata_sandi' => Hash::make($data['kata_sandi_baru']),
            'updated_at' => now(),
            'updated_by' => $pengguna->id_pengguna,
        ])->save();

        $audit->catat($request, 'PROFIL', 'UBAH', 'pengguna', $pengguna->id_pengguna, 'Pengguna mengubah kata sandi sendiri.');

        return back()->with('berhasil', 'Kata sandi berhasil diubah.');
    }
}
