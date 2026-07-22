<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Services\AuditAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const BATAS_PERCOBAAN = 5;

    private const MENIT_KUNCI = 15;

    public function tampilkan(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.masuk');
    }

    public function masuk(Request $request, AuditAktivitas $audit): RedirectResponse
    {
        $data = $request->validate([
            'nama_pengguna' => ['required', 'string', 'max:100'],
            'kata_sandi' => ['required', 'string'],
            'ingat_saya' => ['nullable', 'boolean'],
        ], [], [
            'nama_pengguna' => 'nama pengguna',
            'kata_sandi' => 'kata sandi',
        ]);

        $pengguna = Pengguna::query()
            ->where('nama_pengguna', $data['nama_pengguna'])
            ->first();

        if (! $pengguna || ! $pengguna->status_aktif || $pengguna->deleted_at !== null) {
            $audit->catat($request, 'AUTENTIKASI', 'MASUK', 'pengguna', $pengguna?->id_pengguna, 'Login ditolak: pengguna tidak ditemukan atau tidak aktif.');

            return back()->withInput($request->only('nama_pengguna'))->withErrors([
                'nama_pengguna' => 'Nama pengguna atau kata sandi tidak sesuai.',
            ]);
        }

        if ($pengguna->dikunci_sampai?->isFuture()) {
            return back()->withInput($request->only('nama_pengguna'))->withErrors([
                'nama_pengguna' => 'Akun dikunci sementara sampai '.$pengguna->dikunci_sampai->format('d-m-Y H:i').'.',
            ]);
        }

        if (! Hash::check($data['kata_sandi'], $pengguna->kata_sandi)) {
            $percobaan = $pengguna->percobaan_masuk + 1;
            $dikunciSampai = $percobaan >= self::BATAS_PERCOBAAN ? now()->addMinutes(self::MENIT_KUNCI) : null;

            $pengguna->forceFill([
                'percobaan_masuk' => $dikunciSampai ? 0 : $percobaan,
                'dikunci_sampai' => $dikunciSampai,
                'updated_at' => now(),
            ])->save();

            $audit->catat($request, 'AUTENTIKASI', 'MASUK', 'pengguna', $pengguna->id_pengguna, 'Login gagal karena kata sandi tidak sesuai.');

            return back()->withInput($request->only('nama_pengguna'))->withErrors([
                'nama_pengguna' => $dikunciSampai
                    ? 'Akun dikunci selama '.self::MENIT_KUNCI.' menit karena terlalu banyak percobaan gagal.'
                    : 'Nama pengguna atau kata sandi tidak sesuai.',
            ]);
        }

        Auth::login($pengguna, (bool) ($data['ingat_saya'] ?? false));
        $request->session()->regenerate();

        $pengguna->forceFill([
            'terakhir_masuk' => now(),
            'percobaan_masuk' => 0,
            'dikunci_sampai' => null,
            'updated_at' => now(),
        ])->save();

        $audit->catat($request, 'AUTENTIKASI', 'MASUK', 'pengguna', $pengguna->id_pengguna, 'Login berhasil.');

        return redirect()->intended(route('dashboard'));
    }

    public function keluar(Request $request, AuditAktivitas $audit): RedirectResponse
    {
        $audit->catat($request, 'AUTENTIKASI', 'KELUAR', 'pengguna', $request->user()?->getAuthIdentifier(), 'Logout berhasil.');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('masuk')->with('berhasil', 'Anda berhasil keluar dari sistem.');
    }
}
