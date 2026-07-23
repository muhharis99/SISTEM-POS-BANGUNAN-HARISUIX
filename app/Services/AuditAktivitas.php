<?php

namespace App\Services;

use App\Models\LogAktivitas;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use JsonSerializable;

class AuditAktivitas
{
    private const KUNCI_SENSITIF = [
        'password',
        'kata_sandi',
        'token',
        'secret',
        'rahasia',
        'api_key',
        'authorization',
        'cookie',
        'session',
    ];

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
            'nama_modul' => strtoupper(trim($modul)),
            'jenis_aktivitas' => strtoupper(trim($jenis)),
            'nama_tabel' => $tabel,
            'id_referensi' => $referensi,
            'keterangan' => $keterangan,
            'data_sebelum' => $this->bersihkan($sebelum),
            'data_sesudah' => $this->bersihkan($sesudah),
            'alamat_ip' => $request->ip(),
            'peramban' => $request->userAgent(),
        ]);
    }

    public function bersihkan(mixed $nilai, int $kedalaman = 0): mixed
    {
        if ($nilai === null || is_bool($nilai) || is_int($nilai) || is_float($nilai)) {
            return $nilai;
        }

        if ($kedalaman >= 8) {
            return '[DATA TERLALU DALAM]';
        }

        if ($nilai instanceof Arrayable) {
            $nilai = $nilai->toArray();
        } elseif ($nilai instanceof JsonSerializable) {
            $nilai = $nilai->jsonSerialize();
        } elseif (is_object($nilai)) {
            $nilai = get_object_vars($nilai);
        }

        if (is_array($nilai)) {
            $hasil = [];
            foreach ($nilai as $kunci => $isi) {
                if (is_string($kunci) && $this->kunciSensitif($kunci)) {
                    $hasil[$kunci] = '[DISEMBUNYIKAN]';
                    continue;
                }

                $hasil[$kunci] = $this->bersihkan($isi, $kedalaman + 1);
            }

            return $hasil;
        }

        if (is_string($nilai)) {
            return mb_strlen($nilai) > 10000
                ? mb_substr($nilai, 0, 10000).'…[DIPOTONG]'
                : $nilai;
        }

        return (string) $nilai;
    }

    private function kunciSensitif(string $kunci): bool
    {
        $kunci = strtolower($kunci);

        foreach (self::KUNCI_SENSITIF as $bagian) {
            if (str_contains($kunci, $bagian)) {
                return true;
            }
        }

        return false;
    }
}
