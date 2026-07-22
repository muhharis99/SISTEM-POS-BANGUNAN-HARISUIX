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

class BarangController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'MASTER_BARANG_LIHAT');
        $pencarian = trim((string) $request->query('pencarian'));

        $barang = DB::table('barang as b')
            ->join('kategori_barang as k', 'k.id_kategori_barang', '=', 'b.id_kategori_barang')
            ->leftJoin('merek_barang as m', 'm.id_merek_barang', '=', 'b.id_merek_barang')
            ->join('satuan as s', 's.id_satuan', '=', 'b.id_satuan_dasar')
            ->whereNull('b.deleted_at')
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('b.kode_barang', 'like', "%{$pencarian}%")
                    ->orWhere('b.nama_barang', 'like', "%{$pencarian}%")
                    ->orWhere('m.nama_merek', 'like', "%{$pencarian}%")
                    ->orWhereExists(function ($barcode) use ($pencarian): void {
                        $barcode->selectRaw('1')->from('barang_satuan as bs')
                            ->whereColumn('bs.id_barang', 'b.id_barang')
                            ->whereNull('bs.deleted_at')
                            ->where('bs.kode_batang', 'like', "%{$pencarian}%");
                    });
            }))
            ->select('b.*', 'k.nama_kategori', 'm.nama_merek', 's.nama_satuan as nama_satuan_dasar', 's.jumlah_desimal')
            ->orderBy('b.nama_barang')
            ->paginate(15)
            ->withQueryString();

        $idBarang = $barang->pluck('id_barang')->all();
        $satuanBarang = DB::table('barang_satuan as bs')
            ->join('satuan as s', 's.id_satuan', '=', 'bs.id_satuan')
            ->whereIn('bs.id_barang', $idBarang === [] ? [0] : $idBarang)
            ->whereNull('bs.deleted_at')
            ->select('bs.*', 's.nama_satuan', 's.kode_satuan', 's.jumlah_desimal')
            ->orderByDesc('bs.satuan_utama_penjualan')
            ->get()
            ->groupBy('id_barang');

        return view('barang.index', [
            'barang' => $barang,
            'satuanBarang' => $satuanBarang,
            'kategori' => DB::table('kategori_barang')->whereNull('deleted_at')->where('status_aktif', 1)->orderBy('urutan_tampil')->orderBy('nama_kategori')->get(),
            'merek' => DB::table('merek_barang')->whereNull('deleted_at')->where('status_aktif', 1)->orderBy('nama_merek')->get(),
            'satuan' => DB::table('satuan')->whereNull('deleted_at')->where('status_aktif', 1)->orderBy('nama_satuan')->get(),
            'pencarian' => $pencarian,
        ]);
    }

    public function simpan(Request $request, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_BARANG_KELOLA');
        $data = $this->validasi($request, null);

        DB::transaction(function () use ($request, $data, $audit): void {
            $payload = $this->payloadBarang($request, $data);
            $payload['status_aktif'] = 1;
            $payload['created_at'] = now();
            $payload['created_by'] = $request->user()->id_pengguna;
            $idBarang = (int) DB::table('barang')->insertGetId($payload);
            $this->simpanSatuan($request, $idBarang, $data);
            $audit->catat($request, 'MASTER_BARANG', 'TAMBAH', 'barang', $idBarang, 'Menambahkan barang.', null, $payload);
        });

        return back()->with('berhasil', 'Barang berhasil ditambahkan.');
    }

    public function ubah(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_BARANG_KELOLA');
        $lama = $this->temukan($id);
        $data = $this->validasi($request, $id);

        DB::transaction(function () use ($request, $data, $audit, $lama, $id): void {
            $payload = $this->payloadBarang($request, $data);
            $payload['updated_at'] = now();
            $payload['updated_by'] = $request->user()->id_pengguna;
            DB::table('barang')->where('id_barang', $id)->update($payload);
            $this->simpanSatuan($request, $id, $data);
            $audit->catat($request, 'MASTER_BARANG', 'UBAH', 'barang', $id, 'Mengubah barang dan satuannya.', (array) $lama, $payload);
        });

        return back()->with('berhasil', 'Barang berhasil diperbarui.');
    }

    public function ubahStatus(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'MASTER_BARANG_KELOLA');
        $barang = $this->temukan($id);
        $status = ! (bool) $barang->status_aktif;
        DB::table('barang')->where('id_barang', $id)->update([
            'status_aktif' => $status,
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'MASTER_BARANG', 'UBAH', 'barang', $id, 'Mengubah status barang.', ['status_aktif' => $barang->status_aktif], ['status_aktif' => $status]);

        return back()->with('berhasil', 'Status barang berhasil diubah.');
    }

    private function validasi(Request $request, ?int $idBarang): array
    {
        $data = $request->validate([
            'kode_barang' => ['required', 'string', 'max:50', Rule::unique('barang', 'kode_barang')->ignore($idBarang, 'id_barang')],
            'nama_barang' => ['required', 'string', 'max:200'],
            'id_kategori_barang' => ['required', 'integer', Rule::exists('kategori_barang', 'id_kategori_barang')->where('status_aktif', 1)->whereNull('deleted_at')],
            'id_merek_barang' => ['nullable', 'integer', Rule::exists('merek_barang', 'id_merek_barang')->where('status_aktif', 1)->whereNull('deleted_at')],
            'id_satuan_dasar' => ['required', 'integer', Rule::exists('satuan', 'id_satuan')->where('status_aktif', 1)->whereNull('deleted_at')],
            'jenis_barang' => ['required', 'in:BARANG,JASA'],
            'spesifikasi' => ['nullable', 'string'],
            'warna' => ['nullable', 'string', 'max:100'],
            'ukuran' => ['nullable', 'string', 'max:100'],
            'berat_kilogram' => ['required', 'numeric', 'min:0'],
            'panjang_sentimeter' => ['required', 'numeric', 'min:0'],
            'lebar_sentimeter' => ['required', 'numeric', 'min:0'],
            'tinggi_sentimeter' => ['required', 'numeric', 'min:0'],
            'stok_minimum' => ['required', 'numeric', 'min:0'],
            'stok_maksimum' => ['required', 'numeric', 'min:0'],
            'metode_persediaan' => ['required', 'in:RATA_RATA,MASUK_PERTAMA_KELUAR_PERTAMA'],
            'bisa_dibeli' => ['nullable', 'boolean'],
            'bisa_dijual' => ['nullable', 'boolean'],
            'wajib_nomor_lot' => ['nullable', 'boolean'],
            'wajib_tanggal_kedaluwarsa' => ['nullable', 'boolean'],
            'satuan_barang' => ['required', 'array', 'min:1'],
            'satuan_barang.*.id_satuan' => ['required', 'integer', Rule::exists('satuan', 'id_satuan')->where('status_aktif', 1)->whereNull('deleted_at')],
            'satuan_barang.*.kode_batang' => ['nullable', 'string', 'max:100'],
            'satuan_barang.*.nilai_konversi' => ['required', 'numeric', 'gt:0', 'decimal:0,6'],
            'satuan_barang.*.harga_beli_acuan' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'satuan_barang.*.harga_jual_acuan' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'satuan_barang.*.satuan_utama_pembelian' => ['nullable', 'boolean'],
            'satuan_barang.*.satuan_utama_penjualan' => ['nullable', 'boolean'],
        ]);

        Validator::make($data, [
            'stok_minimum' => [new JumlahSesuaiSatuan((int) $data['id_satuan_dasar'])],
            'stok_maksimum' => [new JumlahSesuaiSatuan((int) $data['id_satuan_dasar'])],
        ])->validate();

        if ((float) $data['stok_maksimum'] > 0 && (float) $data['stok_minimum'] > (float) $data['stok_maksimum']) {
            throw ValidationException::withMessages(['stok_minimum' => 'Stok minimum tidak boleh melebihi stok maksimum.']);
        }

        $idSatuan = collect($data['satuan_barang'])->pluck('id_satuan')->map(fn ($nilai) => (int) $nilai);
        if ($idSatuan->unique()->count() !== $idSatuan->count()) {
            throw ValidationException::withMessages(['satuan_barang' => 'Satuan alternatif tidak boleh duplikat.']);
        }

        foreach (['satuan_utama_pembelian', 'satuan_utama_penjualan'] as $penanda) {
            if (collect($data['satuan_barang'])->filter(fn ($baris) => (bool) ($baris[$penanda] ?? false))->count() > 1) {
                throw ValidationException::withMessages(['satuan_barang' => 'Hanya satu satuan yang boleh menjadi '.str_replace('_', ' ', $penanda).'.']);
            }
        }

        $barcodeDalamForm = collect($data['satuan_barang'])
            ->pluck('kode_batang')->filter(fn ($kode) => trim((string) $kode) !== '')->map(fn ($kode) => trim((string) $kode));
        if ($barcodeDalamForm->unique()->count() !== $barcodeDalamForm->count()) {
            throw ValidationException::withMessages(['satuan_barang' => 'Kode batang dalam satu barang tidak boleh duplikat.']);
        }

        foreach ($barcodeDalamForm as $kode) {
            $dipakai = DB::table('barang_satuan')
                ->where('kode_batang', $kode)
                ->when($idBarang !== null, fn ($query) => $query->where('id_barang', '!=', $idBarang))
                ->exists();
            if ($dipakai) {
                throw ValidationException::withMessages(['satuan_barang' => 'Kode batang '.$kode.' sudah digunakan barang lain.']);
            }
        }

        return $data;
    }

    private function payloadBarang(Request $request, array $data): array
    {
        $jasa = $data['jenis_barang'] === 'JASA';

        return [
            'id_kategori_barang' => (int) $data['id_kategori_barang'],
            'id_merek_barang' => ($data['id_merek_barang'] ?? null) ?: null,
            'id_satuan_dasar' => (int) $data['id_satuan_dasar'],
            'kode_barang' => trim($data['kode_barang']),
            'nama_barang' => trim($data['nama_barang']),
            'jenis_barang' => $data['jenis_barang'],
            'spesifikasi' => $data['spesifikasi'] ?? null,
            'warna' => $data['warna'] ?? null,
            'ukuran' => $data['ukuran'] ?? null,
            'berat_kilogram' => $data['berat_kilogram'],
            'panjang_sentimeter' => $data['panjang_sentimeter'],
            'lebar_sentimeter' => $data['lebar_sentimeter'],
            'tinggi_sentimeter' => $data['tinggi_sentimeter'],
            'stok_minimum' => $jasa ? 0 : $data['stok_minimum'],
            'stok_maksimum' => $jasa ? 0 : $data['stok_maksimum'],
            'metode_persediaan' => $data['metode_persediaan'],
            'bisa_dibeli' => $request->boolean('bisa_dibeli'),
            'bisa_dijual' => $request->boolean('bisa_dijual'),
            'wajib_nomor_lot' => $jasa ? 0 : $request->boolean('wajib_nomor_lot'),
            'wajib_tanggal_kedaluwarsa' => $jasa ? 0 : $request->boolean('wajib_tanggal_kedaluwarsa'),
        ];
    }

    private function simpanSatuan(Request $request, int $idBarang, array $data): void
    {
        $pelaku = $request->user()->id_pengguna;
        $baris = collect($data['satuan_barang'])->map(fn (array $item): array => [
            'id_satuan' => (int) $item['id_satuan'],
            'kode_batang' => trim((string) ($item['kode_batang'] ?? '')) ?: null,
            'nilai_konversi' => (float) $item['nilai_konversi'],
            'harga_beli_acuan' => (float) $item['harga_beli_acuan'],
            'harga_jual_acuan' => (float) $item['harga_jual_acuan'],
            'satuan_utama_pembelian' => (bool) ($item['satuan_utama_pembelian'] ?? false),
            'satuan_utama_penjualan' => (bool) ($item['satuan_utama_penjualan'] ?? false),
        ])->keyBy('id_satuan');

        $idDasar = (int) $data['id_satuan_dasar'];
        $dasar = $baris->get($idDasar, [
            'id_satuan' => $idDasar,
            'kode_batang' => null,
            'nilai_konversi' => 1,
            'harga_beli_acuan' => 0,
            'harga_jual_acuan' => 0,
            'satuan_utama_pembelian' => false,
            'satuan_utama_penjualan' => false,
        ]);
        $dasar['nilai_konversi'] = 1;
        $baris->put($idDasar, $dasar);

        DB::table('barang_satuan')->where('id_barang', $idBarang)->whereNull('deleted_at')->update([
            'deleted_at' => now(),
            'deleted_by' => $pelaku,
        ]);

        foreach ($baris as $item) {
            $lama = DB::table('barang_satuan')
                ->where('id_barang', $idBarang)
                ->where('id_satuan', $item['id_satuan'])
                ->first();

            $payload = array_merge($item, [
                'status_aktif' => 1,
                'deleted_at' => null,
                'deleted_by' => null,
                'updated_at' => now(),
                'updated_by' => $pelaku,
            ]);

            if ($lama) {
                DB::table('barang_satuan')->where('id_barang_satuan', $lama->id_barang_satuan)->update($payload);
            } else {
                DB::table('barang_satuan')->insert(array_merge($payload, [
                    'id_barang' => $idBarang,
                    'created_at' => now(),
                    'created_by' => $pelaku,
                ]));
            }
        }
    }

    private function temukan(int $id): object
    {
        $barang = DB::table('barang')->where('id_barang', $id)->whereNull('deleted_at')->first();
        abort_if($barang === null, 404);

        return $barang;
    }

    private function pastikanAkses(Request $request, string $izin): void
    {
        abort_unless($request->user()?->memilikiHakAkses($izin, (int) $request->session()->get('id_cabang_aktif')), 403);
    }
}
