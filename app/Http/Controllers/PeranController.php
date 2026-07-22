<?php

namespace App\Http\Controllers;

use App\Models\HakAkses;
use App\Models\Peran;
use App\Services\AuditAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeranController extends Controller
{
    private const PERAN_SISTEM = ['ADMINISTRATOR', 'PEMILIK', 'KASIR', 'GUDANG', 'PEMBELIAN', 'PENJUALAN', 'KEUANGAN'];

    public function index(): View
    {
        return view('peran.index', [
            'peran' => Peran::query()->withCount(['hakAkses', 'penugasanPengguna'])->whereNull('deleted_at')->orderBy('nama_peran')->get(),
            'hakAksesPerModul' => HakAkses::query()->aktif()->orderBy('nama_modul')->orderBy('nama_hak_akses')->get()->groupBy('nama_modul'),
        ]);
    }

    public function simpan(Request $request, AuditAktivitas $audit): RedirectResponse
    {
        $data = $this->validasi($request);

        DB::transaction(function () use ($data, $request, $audit): void {
            $peran = Peran::query()->create([
                'kode_peran' => strtoupper($data['kode_peran']),
                'nama_peran' => $data['nama_peran'],
                'keterangan' => $data['keterangan'] ?? null,
                'status_aktif' => 1,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            $this->sinkronHakAkses($peran, $data['hak_akses'] ?? [], $request->user()->id_pengguna);
            $audit->catat($request, 'PERAN', 'TAMBAH', 'peran', $peran->id_peran, 'Menambahkan peran.', null, $peran->only(['kode_peran', 'nama_peran', 'keterangan']));
        });

        return back()->with('berhasil', 'Peran berhasil ditambahkan.');
    }

    public function ubah(Request $request, Peran $peran, AuditAktivitas $audit): RedirectResponse
    {
        abort_if($peran->deleted_at !== null, 404);
        $data = $this->validasi($request, $peran);
        $sebelum = $peran->only(['kode_peran', 'nama_peran', 'keterangan', 'status_aktif']);

        DB::transaction(function () use ($data, $request, $peran, $audit, $sebelum): void {
            $kode = in_array($peran->kode_peran, self::PERAN_SISTEM, true)
                ? $peran->kode_peran
                : strtoupper($data['kode_peran']);

            $peran->forceFill([
                'kode_peran' => $kode,
                'nama_peran' => $data['nama_peran'],
                'keterangan' => $data['keterangan'] ?? null,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ])->save();

            $this->sinkronHakAkses($peran, $data['hak_akses'] ?? [], $request->user()->id_pengguna);
            $audit->catat($request, 'PERAN', 'UBAH', 'peran', $peran->id_peran, 'Mengubah peran dan hak akses.', $sebelum, $peran->only(['kode_peran', 'nama_peran', 'keterangan', 'status_aktif']));
        });

        return back()->with('berhasil', 'Peran berhasil diperbarui.');
    }

    public function ubahStatus(Request $request, Peran $peran, AuditAktivitas $audit): RedirectResponse
    {
        abort_if($peran->kode_peran === 'ADMINISTRATOR', 422, 'Peran Administrator tidak dapat dinonaktifkan.');

        $sebelum = $peran->status_aktif;
        $peran->forceFill([
            'status_aktif' => ! $peran->status_aktif,
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ])->save();

        $audit->catat($request, 'PERAN', 'UBAH', 'peran', $peran->id_peran, 'Mengubah status peran.', ['status_aktif' => $sebelum], ['status_aktif' => $peran->status_aktif]);

        return back()->with('berhasil', 'Status peran berhasil diubah.');
    }

    private function validasi(Request $request, ?Peran $peran = null): array
    {
        return $request->validate([
            'kode_peran' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/',
                Rule::unique('peran', 'kode_peran')->ignore($peran?->id_peran, 'id_peran'),
            ],
            'nama_peran' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            'hak_akses' => ['nullable', 'array'],
            'hak_akses.*' => ['integer', Rule::exists('hak_akses', 'id_hak_akses')->whereNull('deleted_at')],
        ]);
    }

    private function sinkronHakAkses(Peran $peran, array $hakAkses, int $pelaku): void
    {
        DB::table('peran_hak_akses')
            ->where('id_peran', $peran->id_peran)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'deleted_by' => $pelaku]);

        foreach (array_unique($hakAkses) as $idHakAkses) {
            $ada = DB::table('peran_hak_akses')
                ->where('id_peran', $peran->id_peran)
                ->where('id_hak_akses', $idHakAkses)
                ->first();

            if ($ada) {
                DB::table('peran_hak_akses')->where('id_peran_hak_akses', $ada->id_peran_hak_akses)->update([
                    'deleted_at' => null,
                    'deleted_by' => null,
                    'created_at' => now(),
                    'created_by' => $pelaku,
                ]);
            } else {
                DB::table('peran_hak_akses')->insert([
                    'id_peran' => $peran->id_peran,
                    'id_hak_akses' => $idHakAkses,
                    'created_at' => now(),
                    'created_by' => $pelaku,
                ]);
            }
        }
    }
}
