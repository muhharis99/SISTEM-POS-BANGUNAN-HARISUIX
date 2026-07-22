<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PelangganController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'MASTER_PELANGGAN_LIHAT');
        $pencarian = trim((string) $request->query('pencarian'));

        $pelanggan = DB::table('pelanggan as p')
            ->join('jenis_pelanggan as j', 'j.id_jenis_pelanggan', '=', 'p.id_jenis_pelanggan')
            ->whereNull('p.deleted_at')
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('p.kode_pelanggan', 'like', "%{$pencarian}%")
                    ->orWhere('p.nama_pelanggan', 'like', "%{$pencarian}%")
                    ->orWhere('p.nomor_whatsapp', 'like', "%{$pencarian}%")
                    ->orWhere('p.nama_kontak', 'like', "%{$pencarian}%");
            }))
            ->select('p.*', 'j.nama_jenis_pelanggan')
            ->orderBy('p.nama_pelanggan')
            ->paginate(15)
            ->withQueryString();

        $alamat = DB::table('alamat_pelanggan')
            ->whereIn('id_pelanggan', $pelanggan->pluck('id_pelanggan')->all() ?: [0])
            ->whereNull('deleted_at')
            ->orderByDesc('alamat_utama')
            ->orderBy('nama_alamat')
            ->get()
            ->groupBy('id_pelanggan');

        return view('pelanggan.index', [
            'pelanggan' => $pelanggan,
            'alamat' => $alamat,
            'jenisPelanggan' => DB::table('jenis_pelanggan')->whereNull('deleted_at')->where('status_aktif', 1)->orderBy('nama_jenis_pelanggan')->get(),
            'pencarian' => $pencarian,
        ]);
    }

    public function simpan(Request $request, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_PELANGGAN_KELOLA');
        $data = $this->validasi($request, null);

        DB::transaction(function () use ($request, $data, $audit): void {
            $payload = $this->payload($data);
            $payload['status_aktif'] = 1;
            $payload['created_at'] = now();
            $payload['created_by'] = $request->user()->id_pengguna;
            $id = (int) DB::table('pelanggan')->insertGetId($payload);
            $this->simpanAlamat($request, $id, $data['alamat'] ?? []);
            $audit->catat($request, 'MASTER_PELANGGAN', 'TAMBAH', 'pelanggan', $id, 'Menambahkan pelanggan.', null, $payload);
        });

        return back()->with('berhasil', 'Pelanggan berhasil ditambahkan.');
    }

    public function ubah(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_PELANGGAN_KELOLA');
        $lama = $this->temukan($id);
        $data = $this->validasi($request, $id);
        if ($lama->kode_pelanggan === 'UMUM') {
            $idJenisUmum = (int) DB::table('jenis_pelanggan')->where('kode_jenis_pelanggan', 'UMUM')->value('id_jenis_pelanggan');
            if ($data['kode_pelanggan'] !== 'UMUM' || $data['nama_pelanggan'] !== 'PELANGGAN TUNAI' || (int) $data['id_jenis_pelanggan'] !== $idJenisUmum) {
                throw ValidationException::withMessages(['kode_pelanggan' => 'Identitas pelanggan tunai default tidak boleh diubah.']);
            }
        }

        DB::transaction(function () use ($request, $data, $audit, $lama, $id): void {
            $payload = $this->payload($data);
            $payload['updated_at'] = now();
            $payload['updated_by'] = $request->user()->id_pengguna;
            DB::table('pelanggan')->where('id_pelanggan', $id)->update($payload);
            $this->simpanAlamat($request, $id, $data['alamat'] ?? []);
            $audit->catat($request, 'MASTER_PELANGGAN', 'UBAH', 'pelanggan', $id, 'Mengubah pelanggan dan alamat.', (array) $lama, $payload);
        });

        return back()->with('berhasil', 'Pelanggan berhasil diperbarui.');
    }

    public function ubahStatus(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_PELANGGAN_KELOLA');
        $pelanggan = $this->temukan($id);
        abort_if($pelanggan->kode_pelanggan === 'UMUM', 422, 'Pelanggan tunai default tidak boleh dinonaktifkan.');
        $status = ! (bool) $pelanggan->status_aktif;
        DB::table('pelanggan')->where('id_pelanggan', $id)->update([
            'status_aktif' => $status,
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'MASTER_PELANGGAN', 'UBAH', 'pelanggan', $id, 'Mengubah status pelanggan.', ['status_aktif' => $pelanggan->status_aktif], ['status_aktif' => $status]);

        return back()->with('berhasil', 'Status pelanggan berhasil diubah.');
    }

    private function validasi(Request $request, ?int $id): array
    {
        $data = $request->validate([
            'id_jenis_pelanggan' => ['required', 'integer', Rule::exists('jenis_pelanggan', 'id_jenis_pelanggan')->where('status_aktif', 1)->whereNull('deleted_at')],
            'kode_pelanggan' => ['required', 'string', 'max:40', Rule::unique('pelanggan', 'kode_pelanggan')->ignore($id, 'id_pelanggan')],
            'nama_pelanggan' => ['required', 'string', 'max:200'],
            'jenis_identitas' => ['nullable', 'in:KTP,SIM,PASPOR,LAINNYA'],
            'nomor_identitas' => ['nullable', 'string', 'max:100'],
            'nomor_pokok_wajib_pajak' => ['nullable', 'string', 'max:40'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'nomor_whatsapp' => ['nullable', 'string', 'max:30'],
            'surel' => ['nullable', 'email', 'max:150'],
            'alamat_utama' => ['nullable', 'string'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kabupaten_kota' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kelurahan' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'nama_kontak' => ['nullable', 'string', 'max:150'],
            'batas_piutang' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'lama_jatuh_tempo' => ['required', 'integer', 'min:0', 'max:65535'],
            'potongan_persen' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,4'],
            'alamat' => ['nullable', 'array'],
            'alamat.*.nama_alamat' => ['required', 'string', 'max:100'],
            'alamat.*.nama_penerima' => ['nullable', 'string', 'max:150'],
            'alamat.*.telepon_penerima' => ['nullable', 'string', 'max:30'],
            'alamat.*.alamat' => ['required', 'string'],
            'alamat.*.provinsi' => ['nullable', 'string', 'max:100'],
            'alamat.*.kabupaten_kota' => ['nullable', 'string', 'max:100'],
            'alamat.*.kecamatan' => ['nullable', 'string', 'max:100'],
            'alamat.*.kelurahan' => ['nullable', 'string', 'max:100'],
            'alamat.*.kode_pos' => ['nullable', 'string', 'max:10'],
            'alamat.*.garis_lintang' => ['nullable', 'numeric', 'between:-90,90'],
            'alamat.*.garis_bujur' => ['nullable', 'numeric', 'between:-180,180'],
            'alamat.*.alamat_utama' => ['nullable', 'boolean'],
        ]);

        if (collect($data['alamat'] ?? [])->filter(fn ($item) => (bool) ($item['alamat_utama'] ?? false))->count() > 1) {
            throw ValidationException::withMessages(['alamat' => 'Hanya satu alamat tambahan yang boleh ditandai sebagai alamat utama.']);
        }

        return $data;
    }

    private function payload(array $data): array
    {
        return [
            'id_jenis_pelanggan' => (int) $data['id_jenis_pelanggan'],
            'kode_pelanggan' => trim($data['kode_pelanggan']),
            'nama_pelanggan' => trim($data['nama_pelanggan']),
            'jenis_identitas' => $data['jenis_identitas'] ?? null,
            'nomor_identitas' => $data['nomor_identitas'] ?? null,
            'nomor_pokok_wajib_pajak' => $data['nomor_pokok_wajib_pajak'] ?? null,
            'telepon' => $data['telepon'] ?? null,
            'nomor_whatsapp' => $data['nomor_whatsapp'] ?? null,
            'surel' => $data['surel'] ?? null,
            'alamat_utama' => $data['alamat_utama'] ?? null,
            'provinsi' => $data['provinsi'] ?? null,
            'kabupaten_kota' => $data['kabupaten_kota'] ?? null,
            'kecamatan' => $data['kecamatan'] ?? null,
            'kelurahan' => $data['kelurahan'] ?? null,
            'kode_pos' => $data['kode_pos'] ?? null,
            'nama_kontak' => $data['nama_kontak'] ?? null,
            'batas_piutang' => $data['batas_piutang'],
            'lama_jatuh_tempo' => $data['lama_jatuh_tempo'],
            'potongan_persen' => $data['potongan_persen'],
        ];
    }

    private function simpanAlamat(Request $request, int $idPelanggan, array $alamat): void
    {
        DB::table('alamat_pelanggan')->where('id_pelanggan', $idPelanggan)->whereNull('deleted_at')->update([
            'deleted_at' => now(),
            'deleted_by' => $request->user()->id_pengguna,
        ]);

        foreach ($alamat as $item) {
            DB::table('alamat_pelanggan')->insert([
                'id_pelanggan' => $idPelanggan,
                'nama_alamat' => trim($item['nama_alamat']),
                'nama_penerima' => $item['nama_penerima'] ?? null,
                'telepon_penerima' => $item['telepon_penerima'] ?? null,
                'alamat' => $item['alamat'],
                'provinsi' => $item['provinsi'] ?? null,
                'kabupaten_kota' => $item['kabupaten_kota'] ?? null,
                'kecamatan' => $item['kecamatan'] ?? null,
                'kelurahan' => $item['kelurahan'] ?? null,
                'kode_pos' => $item['kode_pos'] ?? null,
                'garis_lintang' => $item['garis_lintang'] ?? null,
                'garis_bujur' => $item['garis_bujur'] ?? null,
                'alamat_utama' => (bool) ($item['alamat_utama'] ?? false),
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);
        }
    }

    private function temukan(int $id): object
    {
        $item = DB::table('pelanggan')->where('id_pelanggan', $id)->whereNull('deleted_at')->first();
        abort_if($item === null, 404);

        return $item;
    }

    private function pastikanAkses(Request $request, string $izin): void
    {
        abort_unless($request->user()?->memilikiHakAkses($izin, (int) $request->session()->get('id_cabang_aktif')), 403);
    }
}
