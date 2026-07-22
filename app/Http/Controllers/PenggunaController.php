<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Pengguna;
use App\Models\PenggunaPeran;
use App\Models\Peran;
use App\Services\AuditAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $pencarian = trim((string) $request->query('pencarian'));

        $pengguna = Pengguna::query()
            ->with(['pegawai.cabang', 'penugasanPeran.peran', 'penugasanPeran.cabang'])
            ->whereNull('deleted_at')
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('nama_pengguna', 'like', "%{$pencarian}%")
                    ->orWhere('nama_tampilan', 'like', "%{$pencarian}%")
                    ->orWhere('surel', 'like', "%{$pencarian}%");
            }))
            ->orderBy('nama_tampilan')
            ->paginate(15)
            ->withQueryString();

        return view('pengguna.index', [
            'pengguna' => $pengguna,
            'peran' => Peran::query()->aktif()->orderBy('nama_peran')->get(),
            'cabang' => Cabang::query()->aktif()->orderBy('nama_cabang')->get(),
            'pencarian' => $pencarian,
        ]);
    }

    public function simpan(Request $request, AuditAktivitas $audit): RedirectResponse
    {
        $data = $this->validasi($request, null, true);

        DB::transaction(function () use ($data, $request, $audit): void {
            $pengguna = Pengguna::query()->create([
                'nama_pengguna' => $data['nama_pengguna'],
                'kata_sandi' => Hash::make($data['kata_sandi']),
                'nama_tampilan' => $data['nama_tampilan'],
                'surel' => $data['surel'] ?? null,
                'telepon' => $data['telepon'] ?? null,
                'status_aktif' => 1,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            $this->simpanPenugasan($pengguna, $data['penugasan'], $request->user()->id_pengguna);
            $audit->catat($request, 'PENGGUNA', 'TAMBAH', 'pengguna', $pengguna->id_pengguna, 'Menambahkan pengguna.', null, $pengguna->only(['nama_pengguna', 'nama_tampilan', 'surel', 'telepon']));
        });

        return back()->with('berhasil', 'Pengguna berhasil ditambahkan.');
    }

    public function ubah(Request $request, Pengguna $pengguna, AuditAktivitas $audit): RedirectResponse
    {
        abort_if($pengguna->deleted_at !== null, 404);
        $data = $this->validasi($request, $pengguna, false);
        $sebelum = $pengguna->only(['nama_pengguna', 'nama_tampilan', 'surel', 'telepon', 'status_aktif']);

        DB::transaction(function () use ($data, $request, $pengguna, $audit, $sebelum): void {
            $pengguna->forceFill([
                'nama_pengguna' => $data['nama_pengguna'],
                'nama_tampilan' => $data['nama_tampilan'],
                'surel' => $data['surel'] ?? null,
                'telepon' => $data['telepon'] ?? null,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ])->save();

            $this->simpanPenugasan($pengguna, $data['penugasan'], $request->user()->id_pengguna);
            $audit->catat($request, 'PENGGUNA', 'UBAH', 'pengguna', $pengguna->id_pengguna, 'Mengubah pengguna.', $sebelum, $pengguna->only(['nama_pengguna', 'nama_tampilan', 'surel', 'telepon', 'status_aktif']));
        });

        return back()->with('berhasil', 'Pengguna berhasil diperbarui.');
    }

    public function ubahStatus(Request $request, Pengguna $pengguna, AuditAktivitas $audit): RedirectResponse
    {
        abort_if($pengguna->id_pengguna === $request->user()->id_pengguna, 422, 'Akun yang sedang dipakai tidak dapat dinonaktifkan.');

        $statusSebelum = $pengguna->status_aktif;
        $pengguna->forceFill([
            'status_aktif' => ! $pengguna->status_aktif,
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ])->save();

        $audit->catat($request, 'PENGGUNA', 'UBAH', 'pengguna', $pengguna->id_pengguna, 'Mengubah status aktif pengguna.', ['status_aktif' => $statusSebelum], ['status_aktif' => $pengguna->status_aktif]);

        return back()->with('berhasil', 'Status pengguna berhasil diubah.');
    }

    public function resetKataSandi(Request $request, Pengguna $pengguna, AuditAktivitas $audit): RedirectResponse
    {
        $data = $request->validate([
            'kata_sandi_baru' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        $pengguna->forceFill([
            'kata_sandi' => Hash::make($data['kata_sandi_baru']),
            'percobaan_masuk' => 0,
            'dikunci_sampai' => null,
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ])->save();

        $audit->catat($request, 'PENGGUNA', 'UBAH', 'pengguna', $pengguna->id_pengguna, 'Administrator mereset kata sandi pengguna.');

        return back()->with('berhasil', 'Kata sandi pengguna berhasil direset.');
    }

    private function validasi(Request $request, ?Pengguna $pengguna, bool $wajibKataSandi): array
    {
        return $request->validate([
            'nama_pengguna' => [
                'required', 'string', 'max:100',
                Rule::unique('pengguna', 'nama_pengguna')->ignore($pengguna?->id_pengguna, 'id_pengguna'),
            ],
            'nama_tampilan' => ['required', 'string', 'max:150'],
            'surel' => ['nullable', 'email', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'kata_sandi' => [$wajibKataSandi ? 'required' : 'nullable', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'penugasan' => ['required', 'array', 'min:1'],
            'penugasan.*.id_peran' => ['required', 'integer', Rule::exists('peran', 'id_peran')->whereNull('deleted_at')],
            'penugasan.*.id_cabang' => ['nullable', 'integer', Rule::exists('cabang', 'id_cabang')->whereNull('deleted_at')],
        ]);
    }

    private function simpanPenugasan(Pengguna $pengguna, array $penugasan, int $pelaku): void
    {
        $unik = collect($penugasan)
            ->map(fn (array $item): array => [
                'id_peran' => (int) $item['id_peran'],
                'id_cabang' => isset($item['id_cabang']) && $item['id_cabang'] !== '' ? (int) $item['id_cabang'] : null,
            ])
            ->unique(fn (array $item): string => $item['id_peran'].'|'.($item['id_cabang'] ?? 'NULL'))
            ->values();

        PenggunaPeran::query()
            ->where('id_pengguna', $pengguna->id_pengguna)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'deleted_by' => $pelaku]);

        foreach ($unik as $item) {
            $query = PenggunaPeran::query()
                ->where('id_pengguna', $pengguna->id_pengguna)
                ->where('id_peran', $item['id_peran']);

            $item['id_cabang'] === null
                ? $query->whereNull('id_cabang')
                : $query->where('id_cabang', $item['id_cabang']);

            $penugasanLama = $query->first();

            if ($penugasanLama) {
                $penugasanLama->forceFill([
                    'deleted_at' => null,
                    'deleted_by' => null,
                    'created_at' => now(),
                    'created_by' => $pelaku,
                ])->save();

                continue;
            }

            PenggunaPeran::query()->create([
                'id_pengguna' => $pengguna->id_pengguna,
                'id_peran' => $item['id_peran'],
                'id_cabang' => $item['id_cabang'],
                'created_at' => now(),
                'created_by' => $pelaku,
            ]);
        }
    }
}
