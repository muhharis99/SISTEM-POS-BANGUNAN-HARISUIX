<?php

namespace App\Http\Controllers;

use App\Rules\JumlahSesuaiSatuan;
use App\Services\AuditAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DaftarHargaController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'DAFTAR_HARGA_LIHAT');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $pencarian = trim((string) $request->query('pencarian'));

        $daftarHarga = DB::table('daftar_harga as d')
            ->leftJoin('jenis_pelanggan as j', 'j.id_jenis_pelanggan', '=', 'd.id_jenis_pelanggan')
            ->where('d.id_cabang', $idCabang)
            ->whereNull('d.deleted_at')
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('d.kode_daftar_harga', 'like', "%{$pencarian}%")
                    ->orWhere('d.nama_daftar_harga', 'like', "%{$pencarian}%")
                    ->orWhere('j.nama_jenis_pelanggan', 'like', "%{$pencarian}%");
            }))
            ->select('d.*', 'j.nama_jenis_pelanggan')
            ->orderByDesc('d.tanggal_mulai')
            ->orderByDesc('d.prioritas')
            ->paginate(12)
            ->withQueryString();

        $detail = DB::table('daftar_harga_detail as dd')
            ->join('barang_satuan as bs', 'bs.id_barang_satuan', '=', 'dd.id_barang_satuan')
            ->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')
            ->join('satuan as s', 's.id_satuan', '=', 'bs.id_satuan')
            ->whereIn('dd.id_daftar_harga', $daftarHarga->pluck('id_daftar_harga')->all() ?: [0])
            ->whereNull('dd.deleted_at')
            ->select('dd.*', 'b.kode_barang', 'b.nama_barang', 's.kode_satuan', 's.nama_satuan', 's.jumlah_desimal')
            ->orderBy('b.nama_barang')
            ->orderBy('dd.jumlah_minimum')
            ->get()
            ->groupBy('id_daftar_harga');

        $barangSatuan = DB::table('barang_satuan as bs')
            ->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')
            ->join('satuan as s', 's.id_satuan', '=', 'bs.id_satuan')
            ->whereNull('bs.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNull('s.deleted_at')
            ->where('bs.status_aktif', 1)
            ->where('b.status_aktif', 1)
            ->select('bs.id_barang_satuan', 'b.kode_barang', 'b.nama_barang', 's.kode_satuan', 's.nama_satuan', 's.jumlah_desimal')
            ->orderBy('b.nama_barang')
            ->orderBy('s.nama_satuan')
            ->get();

        return view('daftar_harga.index', [
            'daftarHarga' => $daftarHarga,
            'detail' => $detail,
            'jenisPelanggan' => DB::table('jenis_pelanggan')->whereNull('deleted_at')->where('status_aktif', 1)->orderBy('nama_jenis_pelanggan')->get(),
            'barangSatuan' => $barangSatuan,
            'pencarian' => $pencarian,
        ]);
    }

    public function simpan(Request $request, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'DAFTAR_HARGA_KELOLA');
        $data = $this->validasi($request, null, true);

        DB::transaction(function () use ($request, $data, $audit): void {
            $payload = $this->payload($request, $data);
            $payload['status_aktif'] = 1;
            $payload['created_at'] = now();
            $payload['created_by'] = $request->user()->id_pengguna;
            $id = (int) DB::table('daftar_harga')->insertGetId($payload);
            $this->simpanDetail($request, $id, $data['detail_harga']);
            $audit->catat($request, 'DAFTAR_HARGA', 'TAMBAH', 'daftar_harga', $id, 'Menambahkan daftar harga.', null, $payload);
        });

        return back()->with('berhasil', 'Daftar harga berhasil ditambahkan.');
    }

    public function ubah(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'DAFTAR_HARGA_KELOLA');
        $lama = $this->temukan($request, $id);
        $data = $this->validasi($request, $id, (bool) $lama->status_aktif);

        DB::transaction(function () use ($request, $data, $audit, $lama, $id): void {
            $payload = $this->payload($request, $data);
            $payload['updated_at'] = now();
            $payload['updated_by'] = $request->user()->id_pengguna;
            DB::table('daftar_harga')->where('id_daftar_harga', $id)->update($payload);
            $this->simpanDetail($request, $id, $data['detail_harga']);
            $audit->catat($request, 'DAFTAR_HARGA', 'UBAH', 'daftar_harga', $id, 'Mengubah daftar harga dan detail.', (array) $lama, $payload);
        });

        return back()->with('berhasil', 'Daftar harga berhasil diperbarui.');
    }

    public function ubahStatus(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'DAFTAR_HARGA_KELOLA');
        $item = $this->temukan($request, $id);
        $status = ! (bool) $item->status_aktif;

        if ($status) {
            $this->pastikanTidakTumpangTindih([
                'id_cabang' => $item->id_cabang,
                'id_jenis_pelanggan' => $item->id_jenis_pelanggan,
                'tanggal_mulai' => $item->tanggal_mulai,
                'tanggal_selesai' => $item->tanggal_selesai,
            ], $id);
        }

        DB::table('daftar_harga')->where('id_daftar_harga', $id)->update([
            'status_aktif' => $status,
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'DAFTAR_HARGA', 'UBAH', 'daftar_harga', $id, 'Mengubah status daftar harga.', ['status_aktif' => $item->status_aktif], ['status_aktif' => $status]);

        return back()->with('berhasil', 'Status daftar harga berhasil diubah.');
    }

    private function validasi(Request $request, ?int $id, bool $periksaTumpangTindih): array
    {
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $data = $request->validate([
            'id_jenis_pelanggan' => ['nullable', 'integer', Rule::exists('jenis_pelanggan', 'id_jenis_pelanggan')->where('status_aktif', 1)->whereNull('deleted_at')],
            'kode_daftar_harga' => ['required', 'string', 'max:30', Rule::unique('daftar_harga', 'kode_daftar_harga')->ignore($id, 'id_daftar_harga')->where('id_cabang', $idCabang)],
            'nama_daftar_harga' => ['required', 'string', 'max:150'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'prioritas' => ['required', 'integer', 'min:0', 'max:65535'],
            'detail_harga' => ['required', 'array', 'min:1'],
            'detail_harga.*.id_barang_satuan' => ['required', 'integer', Rule::exists('barang_satuan', 'id_barang_satuan')->where('status_aktif', 1)->whereNull('deleted_at')],
            'detail_harga.*.jumlah_minimum' => ['required', 'numeric', 'gt:0'],
            'detail_harga.*.harga_jual' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'detail_harga.*.potongan_persen' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,4'],
        ]);

        if ($periksaTumpangTindih) {
            $this->pastikanTidakTumpangTindih([
                'id_cabang' => $idCabang,
                'id_jenis_pelanggan' => $data['id_jenis_pelanggan'] ?? null,
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
            ], $id);
        }

        $unik = [];
        foreach ($data['detail_harga'] as $indeks => $baris) {
            $unit = DB::table('barang_satuan as bs')
                ->join('satuan as s', 's.id_satuan', '=', 'bs.id_satuan')
                ->where('bs.id_barang_satuan', $baris['id_barang_satuan'])
                ->select('s.id_satuan', 's.jumlah_desimal')
                ->first();
            abort_if($unit === null, 422);

            Validator::make(
                ['jumlah_minimum' => $baris['jumlah_minimum']],
                ['jumlah_minimum' => [new JumlahSesuaiSatuan((int) $unit->id_satuan)]]
            )->validate();

            $kunci = $baris['id_barang_satuan'].'|'.number_format((float) $baris['jumlah_minimum'], 3, '.', '');
            if (isset($unik[$kunci])) {
                throw ValidationException::withMessages(["detail_harga.{$indeks}.jumlah_minimum" => 'Barang, satuan, dan jumlah minimum tidak boleh duplikat.']);
            }
            $unik[$kunci] = true;
        }

        return $data;
    }

    private function pastikanTidakTumpangTindih(array $data, ?int $id): void
    {
        $akhir = $data['tanggal_selesai'] ?: '9999-12-31';
        $query = DB::table('daftar_harga')
            ->where('id_cabang', $data['id_cabang'])
            ->whereNull('deleted_at')
            ->where('status_aktif', 1)
            ->when($data['id_jenis_pelanggan'] === null,
                fn ($q) => $q->whereNull('id_jenis_pelanggan'),
                fn ($q) => $q->where('id_jenis_pelanggan', $data['id_jenis_pelanggan']))
            ->where('tanggal_mulai', '<=', $akhir)
            ->where(function ($q) use ($data): void {
                $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $data['tanggal_mulai']);
            })
            ->when($id !== null, fn ($q) => $q->where('id_daftar_harga', '!=', $id));

        if ($query->exists()) {
            throw ValidationException::withMessages(['tanggal_mulai' => 'Periode daftar harga bertabrakan dengan daftar harga aktif pada cabang dan jenis pelanggan yang sama.']);
        }
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'id_cabang' => (int) $request->session()->get('id_cabang_aktif'),
            'id_jenis_pelanggan' => ($data['id_jenis_pelanggan'] ?? null) ?: null,
            'kode_daftar_harga' => trim($data['kode_daftar_harga']),
            'nama_daftar_harga' => trim($data['nama_daftar_harga']),
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
            'prioritas' => $data['prioritas'],
        ];
    }

    private function simpanDetail(Request $request, int $idDaftarHarga, array $detail): void
    {
        $pelaku = $request->user()->id_pengguna;
        DB::table('daftar_harga_detail')->where('id_daftar_harga', $idDaftarHarga)->whereNull('deleted_at')->update([
            'deleted_at' => now(),
            'deleted_by' => $pelaku,
        ]);

        foreach ($detail as $item) {
            $jumlah = number_format((float) $item['jumlah_minimum'], 3, '.', '');
            $lama = DB::table('daftar_harga_detail')
                ->where('id_daftar_harga', $idDaftarHarga)
                ->where('id_barang_satuan', $item['id_barang_satuan'])
                ->where('jumlah_minimum', $jumlah)
                ->first();
            $payload = [
                'harga_jual' => $item['harga_jual'],
                'potongan_persen' => $item['potongan_persen'],
                'status_aktif' => 1,
                'deleted_at' => null,
                'deleted_by' => null,
                'updated_at' => now(),
                'updated_by' => $pelaku,
            ];

            if ($lama) {
                DB::table('daftar_harga_detail')->where('id_daftar_harga_detail', $lama->id_daftar_harga_detail)->update($payload);
            } else {
                DB::table('daftar_harga_detail')->insert(array_merge($payload, [
                    'id_daftar_harga' => $idDaftarHarga,
                    'id_barang_satuan' => $item['id_barang_satuan'],
                    'jumlah_minimum' => $jumlah,
                    'created_at' => now(),
                    'created_by' => $pelaku,
                ]));
            }
        }
    }

    private function temukan(Request $request, int $id): object
    {
        $item = DB::table('daftar_harga')
            ->where('id_daftar_harga', $id)
            ->where('id_cabang', (int) $request->session()->get('id_cabang_aktif'))
            ->whereNull('deleted_at')
            ->first();
        abort_if($item === null, 404);

        return $item;
    }

    private function pastikanAkses(Request $request, string $izin): void
    {
        abort_unless($request->user()?->memilikiHakAkses($izin, (int) $request->session()->get('id_cabang_aktif')), 403);
    }
}
