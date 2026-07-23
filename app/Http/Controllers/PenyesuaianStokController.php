<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use App\Services\LayananPersediaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenyesuaianStokController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'PENYESUAIAN_STOK_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $pencarian = trim((string) $request->query('pencarian'));

        $dokumen = DB::table('penyesuaian_stok as p')
            ->join('gudang as g', 'g.id_gudang', '=', 'p.id_gudang')
            ->leftJoin('stok_opname as o', 'o.id_stok_opname', '=', 'p.id_stok_opname')
            ->where('p.id_cabang', $idCabang)
            ->whereNull('p.deleted_at')
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('p.nomor_penyesuaian', 'like', "%{$pencarian}%")
                    ->orWhere('p.alasan_penyesuaian', 'like', "%{$pencarian}%")
                    ->orWhere('g.nama_gudang', 'like', "%{$pencarian}%");
            }))
            ->select('p.*', 'g.nama_gudang', 'o.nomor_stok_opname')
            ->orderByDesc('p.tanggal_penyesuaian')
            ->orderByDesc('p.id_penyesuaian_stok')
            ->paginate(15)
            ->withQueryString();

        $detail = DB::table('penyesuaian_stok_detail as d')
            ->join('barang as b', 'b.id_barang', '=', 'd.id_barang')
            ->join('satuan as s', 's.id_satuan', '=', 'b.id_satuan_dasar')
            ->join('lokasi_gudang as l', 'l.id_lokasi_gudang', '=', 'd.id_lokasi_gudang')
            ->whereIn('d.id_penyesuaian_stok', $dokumen->pluck('id_penyesuaian_stok')->all() ?: [0])
            ->whereNull('d.deleted_at')
            ->select('d.*', 'b.kode_barang', 'b.nama_barang', 's.kode_satuan', 'l.nama_lokasi')
            ->orderBy('b.nama_barang')
            ->get()
            ->groupBy('id_penyesuaian_stok');

        return view('penyesuaian_stok.index', array_merge(
            compact('dokumen', 'detail', 'pencarian'),
            $this->pilihan($idCabang)
        ));
    }

    public function simpan(Request $request, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PENYESUAIAN_STOK_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $data = $this->validasi($request, $idCabang);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $persediaan): int {
            $nomor = $persediaan->nomorBerikutnya($idCabang, 'PENYESUAIAN_STOK', 'PS', $data['tanggal_penyesuaian']);
            $detailDisiapkan = $this->siapkanDetail($request, $persediaan, (int) $data['id_gudang'], $data['detail_penyesuaian']);
            $id = (int) DB::table('penyesuaian_stok')->insertGetId([
                'id_cabang' => $idCabang,
                'id_gudang' => $data['id_gudang'],
                'id_stok_opname' => null,
                'nomor_penyesuaian' => $nomor,
                'tanggal_penyesuaian' => $data['tanggal_penyesuaian'],
                'alasan_penyesuaian' => $data['alasan_penyesuaian'],
                'status_penyesuaian' => 'DRAF',
                'total_nilai' => round(collect($detailDisiapkan)->sum('total_nilai'), 2),
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);
            foreach ($detailDisiapkan as $baris) {
                DB::table('penyesuaian_stok_detail')->insert(array_merge($baris, [
                    'id_penyesuaian_stok' => $id,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]));
            }

            return $id;
        });

        $audit->catat($request, 'PERSEDIAAN', 'TAMBAH', 'penyesuaian_stok', $id, 'Membuat draf penyesuaian stok.');

        return back()->with('berhasil', 'Penyesuaian stok berhasil dibuat sebagai draf.');
    }

    public function ubah(Request $request, int $id, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PENYESUAIAN_STOK_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $lama = $this->temukan($idCabang, $id);
        abort_unless($lama->status_penyesuaian === 'DRAF', 422, 'Hanya penyesuaian draf yang dapat diubah.');
        abort_if($lama->id_stok_opname !== null, 422, 'Penyesuaian hasil stok opname tidak dapat diubah manual.');
        $data = $this->validasi($request, $idCabang);

        DB::transaction(function () use ($request, $data, $id, $persediaan): void {
            $detailDisiapkan = $this->siapkanDetail($request, $persediaan, (int) $data['id_gudang'], $data['detail_penyesuaian']);
            DB::table('penyesuaian_stok')->where('id_penyesuaian_stok', $id)->update([
                'id_gudang' => $data['id_gudang'],
                'tanggal_penyesuaian' => $data['tanggal_penyesuaian'],
                'alasan_penyesuaian' => $data['alasan_penyesuaian'],
                'total_nilai' => round(collect($detailDisiapkan)->sum('total_nilai'), 2),
                'keterangan' => $data['keterangan'] ?? null,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
            DB::table('penyesuaian_stok_detail')->where('id_penyesuaian_stok', $id)->update([
                'deleted_at' => now(),
                'deleted_by' => $request->user()->id_pengguna,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
            foreach ($detailDisiapkan as $baris) {
                DB::table('penyesuaian_stok_detail')->insert(array_merge($baris, [
                    'id_penyesuaian_stok' => $id,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]));
            }
        });

        $audit->catat($request, 'PERSEDIAAN', 'UBAH', 'penyesuaian_stok', $id, 'Mengubah draf penyesuaian stok.', (array) $lama, $data);

        return back()->with('berhasil', 'Draf penyesuaian stok berhasil diperbarui.');
    }

    public function setujui(Request $request, int $id, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PENYESUAIAN_STOK_SETUJUI');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');

        DB::transaction(function () use ($request, $id, $idCabang, $persediaan): void {
            $dokumen = DB::table('penyesuaian_stok')->where('id_penyesuaian_stok', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            abort_if(! $dokumen, 404);
            abort_unless($dokumen->status_penyesuaian === 'DRAF', 422, 'Penyesuaian stok sudah diproses atau dibatalkan.');

            $detail = DB::table('penyesuaian_stok_detail')->where('id_penyesuaian_stok', $id)->whereNull('deleted_at')->lockForUpdate()->get();
            if ($detail->isEmpty()) {
                throw ValidationException::withMessages(['detail_penyesuaian' => 'Penyesuaian stok harus memiliki minimal satu detail.']);
            }

            foreach ($detail as $baris) {
                $jenisMasuk = $baris->jenis_penyesuaian === 'TAMBAH';
                $persediaan->catatMutasi(
                    $idCabang,
                    (int) $dokumen->id_gudang,
                    (int) $baris->id_lokasi_gudang,
                    (int) $baris->id_barang,
                    $jenisMasuk ? (float) $baris->jumlah_dasar : 0,
                    $jenisMasuk ? 0 : (float) $baris->jumlah_dasar,
                    (float) $baris->harga_pokok,
                    $jenisMasuk ? 'PENYESUAIAN_MASUK' : 'PENYESUAIAN_KELUAR',
                    'PENYESUAIAN_STOK',
                    $id,
                    $dokumen->nomor_penyesuaian,
                    $dokumen->alasan_penyesuaian,
                    (int) $request->user()->id_pengguna,
                );
            }

            DB::table('penyesuaian_stok')->where('id_penyesuaian_stok', $id)->update([
                'status_penyesuaian' => 'DISETUJUI',
                'id_pengguna_penyetuju' => $request->user()->id_pengguna,
                'tanggal_disetujui' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PERSEDIAAN', 'SETUJUI', 'penyesuaian_stok', $id, 'Menyetujui penyesuaian dan memperbarui saldo/mutasi stok.');

        return back()->with('berhasil', 'Penyesuaian disetujui dan saldo stok telah diperbarui.');
    }

    public function batalkan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PENYESUAIAN_STOK_KELOLA');
        $dokumen = $this->temukan((int) $request->session()->get('id_cabang_aktif'), $id);
        abort_unless($dokumen->status_penyesuaian === 'DRAF', 422, 'Hanya penyesuaian draf yang dapat dibatalkan.');

        DB::table('penyesuaian_stok')->where('id_penyesuaian_stok', $id)->update([
            'status_penyesuaian' => 'DIBATALKAN',
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'PERSEDIAAN', 'BATAL', 'penyesuaian_stok', $id, 'Membatalkan penyesuaian stok.');

        return back()->with('berhasil', 'Penyesuaian stok berhasil dibatalkan.');
    }

    private function validasi(Request $request, int $idCabang): array
    {
        return $request->validate([
            'id_gudang' => ['required', 'integer', Rule::exists('gudang', 'id_gudang')->where(fn ($query) => $query->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at'))],
            'tanggal_penyesuaian' => ['required', 'date'],
            'alasan_penyesuaian' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'detail_penyesuaian' => ['required', 'array', 'min:1'],
            'detail_penyesuaian.*.id_barang' => ['required', 'integer'],
            'detail_penyesuaian.*.id_lokasi_gudang' => ['required', 'integer'],
            'detail_penyesuaian.*.jenis_penyesuaian' => ['required', 'in:TAMBAH,KURANG'],
            'detail_penyesuaian.*.jumlah_dasar' => ['required', 'numeric', 'gt:0'],
            'detail_penyesuaian.*.harga_pokok' => ['required', 'numeric', 'min:0'],
            'detail_penyesuaian.*.nomor_lot' => ['nullable', 'string', 'max:100'],
            'detail_penyesuaian.*.tanggal_kedaluwarsa' => ['nullable', 'date'],
            'detail_penyesuaian.*.keterangan' => ['nullable', 'string'],
        ]);
    }

    private function siapkanDetail(Request $request, LayananPersediaan $persediaan, int $idGudang, array $detail): array
    {
        $hasil = [];
        $kombinasi = [];
        foreach ($detail as $index => $baris) {
            $barang = $this->barang((int) $baris['id_barang']);
            $lokasiValid = DB::table('lokasi_gudang')->where('id_lokasi_gudang', (int) $baris['id_lokasi_gudang'])->where('id_gudang', $idGudang)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
            if (! $lokasiValid) {
                throw ValidationException::withMessages(["detail_penyesuaian.{$index}.id_lokasi_gudang" => 'Lokasi tidak termasuk gudang yang dipilih.']);
            }
            $persediaan->pastikanDesimal((float) $baris['jumlah_dasar'], (int) $barang->jumlah_desimal, "detail_penyesuaian.{$index}.jumlah_dasar");
            if ((bool) $barang->wajib_nomor_lot && blank($baris['nomor_lot'] ?? null)) {
                throw ValidationException::withMessages(["detail_penyesuaian.{$index}.nomor_lot" => 'Nomor lot wajib diisi untuk barang ini.']);
            }
            if ((bool) $barang->wajib_tanggal_kedaluwarsa && blank($baris['tanggal_kedaluwarsa'] ?? null)) {
                throw ValidationException::withMessages(["detail_penyesuaian.{$index}.tanggal_kedaluwarsa" => 'Tanggal kedaluwarsa wajib diisi untuk barang ini.']);
            }

            $lot = blank($baris['nomor_lot'] ?? null) ? null : trim((string) $baris['nomor_lot']);
            $kunci = $baris['id_barang'].'|'.$baris['id_lokasi_gudang'].'|'.$baris['jenis_penyesuaian'].'|'.($lot ?? '');
            if (isset($kombinasi[$kunci])) {
                throw ValidationException::withMessages(["detail_penyesuaian.{$index}.id_barang" => 'Detail penyesuaian yang sama tidak boleh duplikat.']);
            }
            $kombinasi[$kunci] = true;

            $jumlah = round((float) $baris['jumlah_dasar'], 3);
            $harga = round((float) $baris['harga_pokok'], 4);
            $hasil[] = [
                'id_barang' => (int) $baris['id_barang'],
                'id_lokasi_gudang' => (int) $baris['id_lokasi_gudang'],
                'jenis_penyesuaian' => $baris['jenis_penyesuaian'],
                'jumlah_dasar' => $jumlah,
                'harga_pokok' => $harga,
                'total_nilai' => round($jumlah * $harga, 2),
                'nomor_lot' => $lot,
                'tanggal_kedaluwarsa' => $baris['tanggal_kedaluwarsa'] ?? null,
                'keterangan' => $baris['keterangan'] ?? null,
            ];
        }

        return $hasil;
    }

    private function barang(int $idBarang): object
    {
        $barang = DB::table('barang as b')->join('satuan as s', 's.id_satuan', '=', 'b.id_satuan_dasar')->where('b.id_barang', $idBarang)->where('b.jenis_barang', 'BARANG')->where('b.status_aktif', 1)->whereNull('b.deleted_at')->select('b.*', 's.jumlah_desimal', 's.kode_satuan')->first();
        if (! $barang) {
            throw ValidationException::withMessages(['id_barang' => 'Barang tidak valid atau tidak aktif.']);
        }

        return $barang;
    }

    private function pilihan(int $idCabang): array
    {
        return [
            'gudangPilihan' => DB::table('gudang')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_gudang')->get(),
            'lokasiPilihan' => DB::table('lokasi_gudang as l')->join('gudang as g', 'g.id_gudang', '=', 'l.id_gudang')->where('g.id_cabang', $idCabang)->where('l.status_aktif', 1)->whereNull('l.deleted_at')->select('l.*')->orderBy('l.nama_lokasi')->get(),
            'barangPilihan' => DB::table('barang as b')->join('satuan as s', 's.id_satuan', '=', 'b.id_satuan_dasar')->where('b.jenis_barang', 'BARANG')->where('b.status_aktif', 1)->whereNull('b.deleted_at')->select('b.*', 's.kode_satuan', 's.jumlah_desimal')->orderBy('b.nama_barang')->get(),
        ];
    }

    private function temukan(int $idCabang, int $id): object
    {
        $item = DB::table('penyesuaian_stok')->where('id_penyesuaian_stok', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->first();
        abort_if(! $item, 404);

        return $item;
    }

    private function pastikanAkses(Request $request, string $izin): void
    {
        abort_unless($request->user()?->memilikiHakAkses($izin, (int) $request->session()->get('id_cabang_aktif')), 403);
    }
}
