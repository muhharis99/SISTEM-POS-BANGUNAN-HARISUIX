<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GudangController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'MASTER_GUDANG_LIHAT');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $pencarian = trim((string) $request->query('pencarian'));

        $gudang = DB::table('gudang')
            ->where('id_cabang', $idCabang)
            ->whereNull('deleted_at')
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('kode_gudang', 'like', "%{$pencarian}%")
                    ->orWhere('nama_gudang', 'like', "%{$pencarian}%")
                    ->orWhere('nama_penanggung_jawab', 'like', "%{$pencarian}%");
            }))
            ->orderBy('nama_gudang')
            ->paginate(12)
            ->withQueryString();

        $lokasi = DB::table('lokasi_gudang as l')
            ->leftJoin('lokasi_gudang as induk', 'induk.id_lokasi_gudang', '=', 'l.id_lokasi_induk')
            ->whereIn('l.id_gudang', $gudang->pluck('id_gudang')->all() ?: [0])
            ->whereNull('l.deleted_at')
            ->select('l.*', 'induk.nama_lokasi as nama_lokasi_induk')
            ->orderBy('l.kode_lokasi')
            ->get()
            ->groupBy('id_gudang');

        return view('gudang.index', compact('gudang', 'lokasi', 'pencarian'));
    }

    public function simpan(Request $request, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_GUDANG_KELOLA');
        $data = $this->validasiGudang($request, null);
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $payload = array_merge($data, [
            'id_cabang' => $idCabang,
            'status_aktif' => 1,
            'created_at' => now(),
            'created_by' => $request->user()->id_pengguna,
        ]);
        $id = (int) DB::table('gudang')->insertGetId($payload);
        $audit->catat($request, 'MASTER_GUDANG', 'TAMBAH', 'gudang', $id, 'Menambahkan gudang.', null, $payload);

        return back()->with('berhasil', 'Gudang berhasil ditambahkan.');
    }

    public function ubah(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_GUDANG_KELOLA');
        $lama = $this->temukanGudang($request, $id);
        $data = $this->validasiGudang($request, $id);
        if (in_array($lama->kode_gudang, ['RUSAK', 'RETUR'], true) && ($data['kode_gudang'] !== $lama->kode_gudang || $data['jenis_gudang'] !== $lama->jenis_gudang)) {
            throw ValidationException::withMessages(['kode_gudang' => 'Kode dan jenis gudang khusus rusak/retur tidak boleh diubah.']);
        }
        $payload = array_merge($data, [
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        DB::table('gudang')->where('id_gudang', $id)->update($payload);
        $audit->catat($request, 'MASTER_GUDANG', 'UBAH', 'gudang', $id, 'Mengubah gudang.', (array) $lama, $payload);

        return back()->with('berhasil', 'Gudang berhasil diperbarui.');
    }

    public function ubahStatus(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_GUDANG_KELOLA');
        $gudang = $this->temukanGudang($request, $id);
        abort_if(in_array($gudang->jenis_gudang, ['RUSAK', 'RETUR'], true), 422, 'Gudang khusus rusak dan retur tidak boleh dinonaktifkan.');
        $status = ! (bool) $gudang->status_aktif;
        DB::table('gudang')->where('id_gudang', $id)->update([
            'status_aktif' => $status,
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'MASTER_GUDANG', 'UBAH', 'gudang', $id, 'Mengubah status gudang.', ['status_aktif' => $gudang->status_aktif], ['status_aktif' => $status]);

        return back()->with('berhasil', 'Status gudang berhasil diubah.');
    }

    public function simpanLokasi(Request $request, int $idGudang, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_GUDANG_KELOLA');
        $this->temukanGudang($request, $idGudang);
        $data = $this->validasiLokasi($request, $idGudang, null);
        $payload = array_merge($data, [
            'id_gudang' => $idGudang,
            'status_aktif' => 1,
            'created_at' => now(),
            'created_by' => $request->user()->id_pengguna,
        ]);
        $id = (int) DB::table('lokasi_gudang')->insertGetId($payload);
        $audit->catat($request, 'MASTER_GUDANG', 'TAMBAH', 'lokasi_gudang', $id, 'Menambahkan lokasi gudang.', null, $payload);

        return back()->with('berhasil', 'Lokasi gudang berhasil ditambahkan.');
    }

    public function ubahLokasi(Request $request, int $idGudang, int $idLokasi, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_GUDANG_KELOLA');
        $this->temukanGudang($request, $idGudang);
        $lama = $this->temukanLokasi($idGudang, $idLokasi);
        $data = $this->validasiLokasi($request, $idGudang, $idLokasi);
        $kodeKhusus = ['AREA-RUSAK', 'AREA-RETUR'];
        if (in_array($lama->kode_lokasi, $kodeKhusus, true) && ($data['kode_lokasi'] !== $lama->kode_lokasi || $data['jenis_lokasi'] !== 'AREA_UMUM' || ($data['id_lokasi_induk'] ?? null) !== null)) {
            throw ValidationException::withMessages(['kode_lokasi' => 'Identitas lokasi khusus rusak/retur tidak boleh diubah.']);
        }
        if (($data['id_lokasi_induk'] ?? null) !== null && $this->indukAdalahTurunan($idLokasi, (int) $data['id_lokasi_induk'])) {
            throw ValidationException::withMessages(['id_lokasi_induk' => 'Lokasi induk tidak boleh berasal dari turunannya sendiri.']);
        }
        $payload = array_merge($data, [
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        DB::table('lokasi_gudang')->where('id_lokasi_gudang', $idLokasi)->update($payload);
        $audit->catat($request, 'MASTER_GUDANG', 'UBAH', 'lokasi_gudang', $idLokasi, 'Mengubah lokasi gudang.', (array) $lama, $payload);

        return back()->with('berhasil', 'Lokasi gudang berhasil diperbarui.');
    }

    public function ubahStatusLokasi(Request $request, int $idGudang, int $idLokasi, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_GUDANG_KELOLA');
        $gudang = $this->temukanGudang($request, $idGudang);
        $lokasi = $this->temukanLokasi($idGudang, $idLokasi);
        $kodeLokasiKhusus = $gudang->jenis_gudang === 'RUSAK' ? 'AREA-RUSAK' : 'AREA-RETUR';
        abort_if(in_array($gudang->jenis_gudang, ['RUSAK', 'RETUR'], true) && $lokasi->kode_lokasi === $kodeLokasiKhusus, 422, 'Lokasi khusus rusak dan retur tidak boleh dinonaktifkan.');
        $status = ! (bool) $lokasi->status_aktif;
        DB::table('lokasi_gudang')->where('id_lokasi_gudang', $idLokasi)->update([
            'status_aktif' => $status,
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'MASTER_GUDANG', 'UBAH', 'lokasi_gudang', $idLokasi, 'Mengubah status lokasi gudang.', ['status_aktif' => $lokasi->status_aktif], ['status_aktif' => $status]);

        return back()->with('berhasil', 'Status lokasi gudang berhasil diubah.');
    }

    private function validasiGudang(Request $request, ?int $id): array
    {
        $idCabang = (int) $request->session()->get('id_cabang_aktif');

        return $request->validate([
            'kode_gudang' => ['required', 'string', 'max:30', Rule::unique('gudang', 'kode_gudang')->ignore($id, 'id_gudang')->where('id_cabang', $idCabang)],
            'nama_gudang' => ['required', 'string', 'max:150'],
            'jenis_gudang' => ['required', 'in:UTAMA,TOKO,TRANSIT,RUSAK,RETUR'],
            'alamat' => ['nullable', 'string'],
            'nama_penanggung_jawab' => ['nullable', 'string', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:30'],
        ]);
    }

    private function validasiLokasi(Request $request, int $idGudang, ?int $idLokasi): array
    {
        $data = $request->validate([
            'kode_lokasi' => ['required', 'string', 'max:50', Rule::unique('lokasi_gudang', 'kode_lokasi')->ignore($idLokasi, 'id_lokasi_gudang')->where('id_gudang', $idGudang)],
            'nama_lokasi' => ['required', 'string', 'max:150'],
            'jenis_lokasi' => ['required', 'in:ZONA,RAK,BARIS,TINGKAT,AREA_UMUM'],
            'id_lokasi_induk' => ['nullable', 'integer', Rule::exists('lokasi_gudang', 'id_lokasi_gudang')->where('id_gudang', $idGudang)->whereNull('deleted_at')],
            'keterangan' => ['nullable', 'string'],
        ]);

        if ($idLokasi !== null && (int) ($data['id_lokasi_induk'] ?? 0) === $idLokasi) {
            throw ValidationException::withMessages(['id_lokasi_induk' => 'Lokasi tidak boleh menjadi induk bagi dirinya sendiri.']);
        }

        return $data;
    }

    private function temukanGudang(Request $request, int $id): object
    {
        $item = DB::table('gudang')
            ->where('id_gudang', $id)
            ->where('id_cabang', (int) $request->session()->get('id_cabang_aktif'))
            ->whereNull('deleted_at')
            ->first();
        abort_if($item === null, 404);

        return $item;
    }

    private function temukanLokasi(int $idGudang, int $idLokasi): object
    {
        $item = DB::table('lokasi_gudang')->where('id_gudang', $idGudang)->where('id_lokasi_gudang', $idLokasi)->whereNull('deleted_at')->first();
        abort_if($item === null, 404);

        return $item;
    }

    private function indukAdalahTurunan(int $idLokasi, int $calonInduk): bool
    {
        $saatIni = $calonInduk;
        for ($i = 0; $i < 100 && $saatIni > 0; $i++) {
            if ($saatIni === $idLokasi) {
                return true;
            }
            $saatIni = (int) (DB::table('lokasi_gudang')->where('id_lokasi_gudang', $saatIni)->value('id_lokasi_induk') ?? 0);
        }

        return false;
    }

    private function pastikanAkses(Request $request, string $izin): void
    {
        abort_unless($request->user()?->memilikiHakAkses($izin, (int) $request->session()->get('id_cabang_aktif')), 403);
    }
}
