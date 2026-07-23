<?php

namespace App\Services;

use App\Models\LampiranDokumen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LayananLampiranDokumen
{
    public function jenisTersediaUntuk(Request $request): array
    {
        $idCabang = $this->idCabang($request);

        return collect(config('lampiran.dokumen', []))
            ->filter(fn (array $konfigurasi): bool => $request->user()?->memilikiHakAkses($konfigurasi['izin_modul'], $idCabang) ?? false)
            ->all();
    }

    public function queryLampiranCabang(Request $request): Builder
    {
        $idCabang = $this->idCabang($request);
        $jenisTersedia = $this->jenisTersediaUntuk($request);

        return LampiranDokumen::query()
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($idCabang, $jenisTersedia): void {
                foreach ($jenisTersedia as $jenis => $konfigurasi) {
                    $query->orWhere(function (Builder $sub) use ($jenis, $konfigurasi, $idCabang): void {
                        $sub->where('jenis_dokumen', $jenis)
                            ->whereIn('id_dokumen', function ($dokumen) use ($konfigurasi, $idCabang): void {
                                $dokumen->select($konfigurasi['kunci'])
                                    ->from($konfigurasi['tabel'])
                                    ->where($konfigurasi['kolom_cabang'], $idCabang)
                                    ->whereNull('deleted_at');
                            });
                    });
                }
            });
    }

    public function pastikanDokumen(Request $request, string $jenisDokumen, int $idDokumen): object
    {
        $jenisDokumen = strtoupper(trim($jenisDokumen));
        $konfigurasi = config('lampiran.dokumen.'.$jenisDokumen);

        if (! is_array($konfigurasi)) {
            throw ValidationException::withMessages([
                'jenis_dokumen' => 'Jenis dokumen tidak didukung.',
            ]);
        }

        $idCabang = $this->idCabang($request);
        abort_unless(
            $request->user()?->memilikiHakAkses($konfigurasi['izin_modul'], $idCabang),
            403,
            'Anda tidak memiliki akses ke modul dokumen tersebut.'
        );

        $dokumen = DB::table($konfigurasi['tabel'])
            ->where($konfigurasi['kunci'], $idDokumen)
            ->where($konfigurasi['kolom_cabang'], $idCabang)
            ->whereNull('deleted_at')
            ->first();

        if (! $dokumen) {
            abort(404, 'Dokumen tidak ditemukan pada cabang aktif.');
        }

        return $dokumen;
    }

    public function simpan(
        Request $request,
        UploadedFile $berkas,
        string $jenisDokumen,
        int $idDokumen,
        ?string $keterangan = null,
    ): LampiranDokumen {
        $jenisDokumen = strtoupper(trim($jenisDokumen));
        $this->pastikanDokumen($request, $jenisDokumen, $idDokumen);

        $ekstensi = strtolower($berkas->getClientOriginalExtension() ?: $berkas->extension() ?: 'bin');
        $namaBerkas = Str::uuid()->toString().'.'.$ekstensi;
        $direktori = 'lampiran/'.strtolower($jenisDokumen).'/'.now()->format('Y/m');
        $lokasiBerkas = $berkas->storeAs($direktori, $namaBerkas, 'local');

        if (! is_string($lokasiBerkas) || ! $this->lokasiAman($lokasiBerkas)) {
            throw ValidationException::withMessages([
                'berkas' => 'Berkas gagal disimpan ke storage privat.',
            ]);
        }

        try {
            return DB::transaction(function () use ($request, $berkas, $jenisDokumen, $idDokumen, $keterangan, $namaBerkas, $lokasiBerkas): LampiranDokumen {
                return LampiranDokumen::query()->create([
                    'jenis_dokumen' => $jenisDokumen,
                    'id_dokumen' => $idDokumen,
                    'nama_berkas' => $namaBerkas,
                    'nama_berkas_asli' => Str::limit(basename($berkas->getClientOriginalName()), 255, ''),
                    'lokasi_berkas' => $lokasiBerkas,
                    'jenis_berkas' => Str::limit((string) $berkas->getMimeType(), 100, ''),
                    'ukuran_berkas' => (int) $berkas->getSize(),
                    'keterangan' => $keterangan,
                    'created_at' => now(),
                    'created_by' => $request->user()?->getAuthIdentifier(),
                ]);
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($lokasiBerkas);
            throw $e;
        }
    }

    public function pastikanLampiran(Request $request, int $idLampiran): LampiranDokumen
    {
        $lampiran = LampiranDokumen::query()
            ->whereKey($idLampiran)
            ->whereNull('deleted_at')
            ->first();

        if (! $lampiran) {
            abort(404);
        }

        $this->pastikanDokumen($request, $lampiran->jenis_dokumen, (int) $lampiran->id_dokumen);

        if (! $this->lokasiAman($lampiran->lokasi_berkas)) {
            abort(404, 'Lokasi berkas tidak valid.');
        }

        return $lampiran;
    }

    public function hapus(Request $request, int $idLampiran): LampiranDokumen
    {
        return DB::transaction(function () use ($request, $idLampiran): LampiranDokumen {
            $lampiran = LampiranDokumen::query()
                ->whereKey($idLampiran)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $lampiran) {
                abort(404);
            }

            $this->pastikanDokumen($request, $lampiran->jenis_dokumen, (int) $lampiran->id_dokumen);

            $lampiran->forceFill([
                'deleted_at' => now(),
                'deleted_by' => $request->user()?->getAuthIdentifier(),
            ])->save();

            return $lampiran;
        });
    }

    public function labelJenis(string $jenisDokumen): string
    {
        return (string) config('lampiran.dokumen.'.strtoupper($jenisDokumen).'.label', $jenisDokumen);
    }

    public function lokasiAman(string $lokasi): bool
    {
        $lokasi = str_replace('\\', '/', $lokasi);

        return str_starts_with($lokasi, 'lampiran/')
            && ! str_contains($lokasi, '../');
    }

    private function idCabang(Request $request): int
    {
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        if ($idCabang <= 0) {
            abort(403, 'Cabang aktif belum dipilih.');
        }

        return $idCabang;
    }
}
