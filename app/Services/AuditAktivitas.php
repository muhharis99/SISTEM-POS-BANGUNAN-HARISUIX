<?php

namespace App\Services;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;

class AuditAktivitas
{
    public function catat(
        Request $request,
        string $modul,
        string $jenis,
        ?string $tabel = null,
        ?int $referensi = null,
        ?string $keterangan = null,
        mixed $sebelum = null,
        mixed $sesudah = null,
    ): void {
        LogAktivitas::query()->create([
            'id_pengguna' => $request->user()?->getAuthIdentifier(),
            'id_cabang' => $request->session()->get('id_cabang_aktif'),
            'tanggal_aktivitas' => now(),
            'nama_modul' => $modul,
            'jenis_aktivitas' => $jenis,
            'nama_tabel' => $tabel,
            'id_referensi' => $referensi,
            'keterangan' => $keterangan,
            'data_sebelum' => $sebelum,
            'data_sesudah' => $sesudah,
            'alamat_ip' => $request->ip(),
            'peramban' => $request->userAgent(),
        ]);
    }
}
