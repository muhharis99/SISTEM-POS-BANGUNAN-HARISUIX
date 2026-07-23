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

class StokAwalController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'STOK_AWAL_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $pencarian = trim((string) $request->query('pencarian'));

        $dokumen = DB::table('stok_awal as s')
            ->join('gudang as g', 'g.id_gudang', '=', 's.id_gudang')
            ->leftJoin('pengguna as p', 'p.id_pengguna', '=', 's.id_pengguna_penyetuju')
            ->where('s.id_cabang', $idCabang)
            ->whereNull('s.deleted_at')
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('s.nomor_stok_awal', 'like', "%{$pencarian}%")
                    ->orWhere('g.nama_gudang', 'like', "%{$pencarian}%")
                    ->orWhere('s.keterangan', 'like', "%{$pencarian}%");
            }))
            ->select('s.*', 'g.nama_gudang', 'p.nama_tampilan as nama_penyetuju')
            ->orderByDesc('s.tanggal_stok_awal')
            ->orderByDesc('s.id_stok_awal')
            ->paginate(15)
            ->withQueryString();

        $detail = DB::table('stok_awal_detail as d')
            ->join('barang_satuan as bs', 'bs.id_barang_satuan', '=', 'd.id_barang_satuan')
            ->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')
            ->join('satuan as u', 'u.id_satuan', '=', 'bs.id_satuan')
            ->join('lokasi_gudang as l', 'l.id_lokasi_gudang', '=', 'd.id_lokasi_gudang')
            ->whereIn('d.id_stok_awal', $dokumen->pluck('id_stok_awal')->all() ?: [0])
            ->whereNull('d.deleted_at')
            ->select('d.*', 'b.kode_barang', 'b.nama_barang', 'u.kode_satuan', 'l.nama_lokasi')
            ->orderBy('b.nama_barang')
            ->get()
            ->groupBy('id_stok_awal');

        return view('stok_awal.index', array_merge(
            compact('dokumen', 'detail', 'pencarian'),
            $this->pilihan($idCabang)
        ));
    }

    public function simpan(Request $request, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'STOK_AWAL_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $data = $this->validasi($request, $idCabang);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $persediaan): int {
            $nomor = $persediaan->nomorBerikutnya($idCabang, 'STOK_AWAL', 'SA', $data['tanggal_stok_awal']);
            $id = (int) DB::table('stok_awal')->insertGetId([
                'id_cabang' => $idCabang,
                'id_gudang' => $data['id_gudang'],
                'nomor_stok_awal' => $nomor,
                'tanggal_stok_awal' => $data['tanggal_stok_awal'],
                'status_stok_awal' => 'DRAF',
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);
            $this->sinkronkanDetail($request, $persediaan, $id, (int) $data['id_gudang'], $data['detail_stok']);

            return $id;
        });

        $audit->catat($request, 'PERSEDIAAN', 'TAMBAH', 'stok_awal', $id, 'Membuat dokumen stok awal.');

        return back()->with('berhasil', 'Dokumen stok awal berhasil dibuat sebagai draf.');
    }

    public function ubah(Request $request, int $id, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'STOK_AWAL_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $lama = $this->temukan($idCabang, $id);
        abort_unless($lama->status_stok_awal === 'DRAF', 422, 'Hanya stok awal berstatus draf yang dapat diubah.');
        $data = $this->validasi($request, $idCabang);

        DB::transaction(function () use ($request, $data, $id, $persediaan): void {
            DB::table('stok_awal')->where('id_stok_awal', $id)->update([
                'id_gudang' => $data['id_gudang'],
                'tanggal_stok_awal' => $data['tanggal_stok_awal'],
                'keterangan' => $data['keterangan'] ?? null,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
            $this->sinkronkanDetail($request, $persediaan, $id, (int) $data['id_gudang'], $data['detail_stok']);
        });

        $audit->catat($request, 'PERSEDIAAN', 'UBAH', 'stok_awal', $id, 'Mengubah draf stok awal.', (array) $lama, $data);

        return back()->with('berhasil', 'Draf stok awal berhasil diperbarui.');
    }

    public function setujui(Request $request, int $id, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'STOK_AWAL_SETUJUI');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');

        DB::transaction(function () use ($request, $id, $idCabang, $persediaan): void {
            $dokumen = DB::table('stok_awal')
                ->where('id_stok_awal', $id)
                ->where('id_cabang', $idCabang)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();
            abort_if(! $dokumen, 404);
            abort_unless($dokumen->status_stok_awal === 'DRAF', 422, 'Stok awal sudah diproses atau dibatalkan.');

            $detail = DB::table('stok_awal_detail')
                ->where('id_stok_awal', $id)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get();
            if ($detail->isEmpty()) {
                throw ValidationException::withMessages(['detail_stok' => 'Stok awal harus memiliki minimal satu detail.']);
            }

            foreach ($detail as $baris) {
                $barangSatuan = $persediaan->barangSatuan((int) $baris->id_barang_satuan);
                $persediaan->catatMutasi(
                    $idCabang,
                    (int) $dokumen->id_gudang,
                    (int) $baris->id_lokasi_gudang,
                    (int) $barangSatuan->id_barang,
                    (float) $baris->jumlah_dasar,
                    0,
                    (float) $baris->harga_pokok,
                    'STOK_AWAL',
                    'STOK_AWAL',
                    $id,
                    $dokumen->nomor_stok_awal,
                    $baris->keterangan,
                    (int) $request->user()->id_pengguna,
                );
            }

            DB::table('stok_awal')->where('id_stok_awal', $id)->update([
                'status_stok_awal' => 'DISETUJUI',
                'id_pengguna_penyetuju' => $request->user()->id_pengguna,
                'tanggal_disetujui' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PERSEDIAAN', 'SETUJUI', 'stok_awal', $id, 'Menyetujui stok awal dan membentuk saldo/mutasi.');

        return back()->with('berhasil', 'Stok awal disetujui dan saldo persediaan telah diperbarui.');
    }

    public function batalkan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'STOK_AWAL_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $dokumen = $this->temukan($idCabang, $id);
        abort_unless($dokumen->status_stok_awal === 'DRAF', 422, 'Hanya stok awal draf yang dapat dibatalkan.');

        DB::table('stok_awal')->where('id_stok_awal', $id)->update([
            'status_stok_awal' => 'DIBATALKAN',
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'PERSEDIAAN', 'BATAL', 'stok_awal', $id, 'Membatalkan draf stok awal.');

        return back()->with('berhasil', 'Dokumen stok awal berhasil dibatalkan.');
    }

    private function validasi(Request $request, int $idCabang): array
    {
        return $request->validate([
            'id_gudang' => [
                'required',
                'integer',
                Rule::exists('gudang', 'id_gudang')->where(fn ($query) => $query
                    ->where('id_cabang', $idCabang)
                    ->where('status_aktif', 1)
                    ->whereNull('deleted_at')),
            ],
            'tanggal_stok_awal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
            'detail_stok' => ['required', 'array', 'min:1'],
            'detail_stok.*.id_barang_satuan' => ['required', 'integer'],
            'detail_stok.*.id_lokasi_gudang' => ['required', 'integer'],
            'detail_stok.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail_stok.*.harga_pokok' => ['required', 'numeric', 'min:0'],
            'detail_stok.*.nomor_lot' => ['nullable', 'string', 'max:100'],
            'detail_stok.*.tanggal_kedaluwarsa' => ['nullable', 'date'],
            'detail_stok.*.keterangan' => ['nullable', 'string'],
        ]);
    }

    private function sinkronkanDetail(Request $request, LayananPersediaan $persediaan, int $idStokAwal, int $idGudang, array $detail): void
    {
        $lama = DB::table('stok_awal_detail')->where('id_stok_awal', $idStokAwal)->get()->keyBy(
            fn ($baris) => $this->kunci((int) $baris->id_barang_satuan, (int) $baris->id_lokasi_gudang, $baris->nomor_lot)
        );
        $dipakai = [];

        foreach ($detail as $index => $baris) {
            $barangSatuan = $persediaan->barangSatuan((int) $baris['id_barang_satuan']);
            $lokasiValid = DB::table('lokasi_gudang')
                ->where('id_lokasi_gudang', (int) $baris['id_lokasi_gudang'])
                ->where('id_gudang', $idGudang)
                ->where('status_aktif', 1)
                ->whereNull('deleted_at')
                ->exists();
            if (! $lokasiValid) {
                throw ValidationException::withMessages(["detail_stok.{$index}.id_lokasi_gudang" => 'Lokasi tidak termasuk gudang yang dipilih.']);
            }

            if ((bool) $barangSatuan->wajib_nomor_lot && blank($baris['nomor_lot'] ?? null)) {
                throw ValidationException::withMessages(["detail_stok.{$index}.nomor_lot" => 'Nomor lot wajib diisi untuk barang ini.']);
            }
            if ((bool) $barangSatuan->wajib_tanggal_kedaluwarsa && blank($baris['tanggal_kedaluwarsa'] ?? null)) {
                throw ValidationException::withMessages(["detail_stok.{$index}.tanggal_kedaluwarsa" => 'Tanggal kedaluwarsa wajib diisi untuk barang ini.']);
            }

            $jumlahDasar = $persediaan->jumlahDasar($barangSatuan, $baris['jumlah'], "detail_stok.{$index}.jumlah");
            $lot = blank($baris['nomor_lot'] ?? null) ? null : trim((string) $baris['nomor_lot']);
            $kunci = $this->kunci((int) $baris['id_barang_satuan'], (int) $baris['id_lokasi_gudang'], $lot);
            if (isset($dipakai[$kunci])) {
                throw ValidationException::withMessages(["detail_stok.{$index}.id_barang_satuan" => 'Kombinasi barang, lokasi, dan nomor lot tidak boleh duplikat.']);
            }
            $dipakai[$kunci] = true;

            $payload = [
                'id_stok_awal' => $idStokAwal,
                'id_barang_satuan' => (int) $baris['id_barang_satuan'],
                'id_lokasi_gudang' => (int) $baris['id_lokasi_gudang'],
                'nilai_konversi' => (float) $barangSatuan->nilai_konversi,
                'jumlah' => round((float) $baris['jumlah'], 3),
                'jumlah_dasar' => $jumlahDasar,
                'harga_pokok' => round((float) $baris['harga_pokok'], 4),
                'total_nilai' => round($jumlahDasar * (float) $baris['harga_pokok'], 2),
                'nomor_lot' => $lot,
                'tanggal_kedaluwarsa' => $baris['tanggal_kedaluwarsa'] ?? null,
                'keterangan' => $baris['keterangan'] ?? null,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
                'deleted_at' => null,
                'deleted_by' => null,
            ];

            $barisLama = $lama->get($kunci);
            if ($barisLama) {
                DB::table('stok_awal_detail')->where('id_stok_awal_detail', $barisLama->id_stok_awal_detail)->update($payload);
            } else {
                DB::table('stok_awal_detail')->insert(array_merge($payload, [
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]));
            }
        }

        foreach ($lama as $kunci => $barisLama) {
            if (! isset($dipakai[$kunci])) {
                DB::table('stok_awal_detail')->where('id_stok_awal_detail', $barisLama->id_stok_awal_detail)->update([
                    'deleted_at' => now(),
                    'deleted_by' => $request->user()->id_pengguna,
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);
            }
        }
    }

    private function kunci(int $idBarangSatuan, int $idLokasi, ?string $lot): string
    {
        return $idBarangSatuan.'|'.$idLokasi.'|'.($lot ?? '');
    }

    private function pilihan(int $idCabang): array
    {
        return [
            'gudangPilihan' => DB::table('gudang')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_gudang')->get(),
            'lokasiPilihan' => DB::table('lokasi_gudang as l')->join('gudang as g', 'g.id_gudang', '=', 'l.id_gudang')->where('g.id_cabang', $idCabang)->where('l.status_aktif', 1)->whereNull('l.deleted_at')->select('l.*')->orderBy('l.nama_lokasi')->get(),
            'barangSatuanPilihan' => DB::table('barang_satuan as bs')->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')->join('satuan as s', 's.id_satuan', '=', 'bs.id_satuan')->where('bs.status_aktif', 1)->where('b.status_aktif', 1)->where('b.jenis_barang', 'BARANG')->whereNull('bs.deleted_at')->whereNull('b.deleted_at')->select('bs.*', 'b.kode_barang', 'b.nama_barang', 's.kode_satuan', 's.jumlah_desimal')->orderBy('b.nama_barang')->get(),
        ];
    }

    private function temukan(int $idCabang, int $id): object
    {
        $item = DB::table('stok_awal')->where('id_stok_awal', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->first();
        abort_if(! $item, 404);

        return $item;
    }

    private function pastikanAkses(Request $request, string $izin): void
    {
        abort_unless($request->user()?->memilikiHakAkses($izin, (int) $request->session()->get('id_cabang_aktif')), 403);
    }
}
