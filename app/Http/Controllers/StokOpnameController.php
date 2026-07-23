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

class StokOpnameController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'STOK_OPNAME_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $pencarian = trim((string) $request->query('pencarian'));

        $dokumen = DB::table('stok_opname as o')
            ->join('gudang as g', 'g.id_gudang', '=', 'o.id_gudang')
            ->where('o.id_cabang', $idCabang)
            ->whereNull('o.deleted_at')
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('o.nomor_stok_opname', 'like', "%{$pencarian}%")
                    ->orWhere('g.nama_gudang', 'like', "%{$pencarian}%")
                    ->orWhere('o.keterangan', 'like', "%{$pencarian}%");
            }))
            ->select('o.*', 'g.nama_gudang')
            ->orderByDesc('o.tanggal_stok_opname')
            ->orderByDesc('o.id_stok_opname')
            ->paginate(15)
            ->withQueryString();

        $detail = DB::table('stok_opname_detail as d')
            ->join('barang as b', 'b.id_barang', '=', 'd.id_barang')
            ->join('satuan as s', 's.id_satuan', '=', 'b.id_satuan_dasar')
            ->join('lokasi_gudang as l', 'l.id_lokasi_gudang', '=', 'd.id_lokasi_gudang')
            ->whereIn('d.id_stok_opname', $dokumen->pluck('id_stok_opname')->all() ?: [0])
            ->whereNull('d.deleted_at')
            ->select('d.*', 'b.kode_barang', 'b.nama_barang', 's.kode_satuan', 'l.nama_lokasi')
            ->orderBy('b.nama_barang')
            ->get()
            ->groupBy('id_stok_opname');

        return view('stok_opname.index', array_merge(
            compact('dokumen', 'detail', 'pencarian'),
            $this->pilihan($idCabang)
        ));
    }

    public function simpan(Request $request, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'STOK_OPNAME_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $data = $this->validasi($request, $idCabang);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $persediaan): int {
            $nomor = $persediaan->nomorBerikutnya($idCabang, 'STOK_OPNAME', 'SO', $data['tanggal_stok_opname']);
            $id = (int) DB::table('stok_opname')->insertGetId([
                'id_cabang' => $idCabang,
                'id_gudang' => $data['id_gudang'],
                'nomor_stok_opname' => $nomor,
                'tanggal_stok_opname' => $data['tanggal_stok_opname'],
                'status_stok_opname' => 'DRAF',
                'id_pengguna_penanggung_jawab' => $request->user()->id_pengguna,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);
            $this->sinkronkanDetail($request, $persediaan, $id, (int) $data['id_gudang'], $data['detail_opname'], false);

            return $id;
        });

        $audit->catat($request, 'PERSEDIAAN', 'TAMBAH', 'stok_opname', $id, 'Membuat draf stok opname.');

        return back()->with('berhasil', 'Stok opname berhasil dibuat sebagai draf.');
    }

    public function ubah(Request $request, int $id, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'STOK_OPNAME_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $lama = $this->temukan($idCabang, $id);
        abort_unless(in_array($lama->status_stok_opname, ['DRAF', 'PROSES'], true), 422, 'Stok opname sudah selesai atau dibatalkan.');
        $data = $this->validasi($request, $idCabang);
        abort_if($lama->status_stok_opname === 'PROSES' && (int) $data['id_gudang'] !== (int) $lama->id_gudang, 422, 'Gudang tidak boleh diubah setelah opname dimulai.');

        DB::transaction(function () use ($request, $data, $id, $lama, $persediaan): void {
            DB::table('stok_opname')->where('id_stok_opname', $id)->update([
                'id_gudang' => $data['id_gudang'],
                'tanggal_stok_opname' => $data['tanggal_stok_opname'],
                'keterangan' => $data['keterangan'] ?? null,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
            $this->sinkronkanDetail($request, $persediaan, $id, (int) $data['id_gudang'], $data['detail_opname'], $lama->status_stok_opname === 'PROSES');
        });

        $audit->catat($request, 'PERSEDIAAN', 'UBAH', 'stok_opname', $id, 'Mengubah data stok opname.', (array) $lama, $data);

        return back()->with('berhasil', 'Stok opname berhasil diperbarui.');
    }

    public function mulai(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'STOK_OPNAME_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');

        DB::transaction(function () use ($request, $id, $idCabang): void {
            $dokumen = DB::table('stok_opname')->where('id_stok_opname', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            abort_if(! $dokumen, 404);
            abort_unless($dokumen->status_stok_opname === 'DRAF', 422, 'Hanya opname draf yang dapat dimulai.');

            $detail = DB::table('stok_opname_detail')->where('id_stok_opname', $id)->whereNull('deleted_at')->get();
            if ($detail->isEmpty()) {
                throw ValidationException::withMessages(['detail_opname' => 'Stok opname harus memiliki minimal satu detail.']);
            }

            foreach ($detail as $baris) {
                $saldo = DB::table('saldo_stok')
                    ->where('id_gudang', $dokumen->id_gudang)
                    ->where('id_lokasi_gudang', $baris->id_lokasi_gudang)
                    ->where('id_barang', $baris->id_barang)
                    ->first();
                $jumlahSistem = (float) ($saldo->jumlah_stok ?? 0);
                $hargaPokok = (float) ($saldo->harga_pokok_rata_rata ?? 0);
                $selisih = round((float) $baris->jumlah_fisik - $jumlahSistem, 3);
                DB::table('stok_opname_detail')->where('id_stok_opname_detail', $baris->id_stok_opname_detail)->update([
                    'jumlah_sistem' => $jumlahSistem,
                    'jumlah_selisih' => $selisih,
                    'harga_pokok' => $hargaPokok,
                    'nilai_selisih' => round($selisih * $hargaPokok, 2),
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);
            }

            DB::table('stok_opname')->where('id_stok_opname', $id)->update([
                'status_stok_opname' => 'PROSES',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PERSEDIAAN', 'MULAI', 'stok_opname', $id, 'Memulai penghitungan fisik stok opname.');

        return back()->with('berhasil', 'Stok opname dimulai. Saldo sistem telah dibekukan pada detail opname.');
    }

    public function selesai(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'STOK_OPNAME_KELOLA');
        $dokumen = $this->temukan((int) $request->session()->get('id_cabang_aktif'), $id);
        abort_unless($dokumen->status_stok_opname === 'PROSES', 422, 'Stok opname harus berstatus proses sebelum diselesaikan.');

        DB::table('stok_opname')->where('id_stok_opname', $id)->update([
            'status_stok_opname' => 'SELESAI',
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'PERSEDIAAN', 'SELESAI', 'stok_opname', $id, 'Menyelesaikan penghitungan fisik stok opname.');

        return back()->with('berhasil', 'Penghitungan fisik selesai dan menunggu persetujuan.');
    }

    public function setujui(Request $request, int $id, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'STOK_OPNAME_SETUJUI');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $idPenyesuaian = DB::transaction(function () use ($request, $id, $idCabang, $persediaan): int {
            $dokumen = DB::table('stok_opname')->where('id_stok_opname', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            abort_if(! $dokumen, 404);
            abort_unless($dokumen->status_stok_opname === 'SELESAI', 422, 'Hanya stok opname selesai yang dapat disetujui.');

            $detail = DB::table('stok_opname_detail')->where('id_stok_opname', $id)->whereNull('deleted_at')->lockForUpdate()->get();
            if ($detail->isEmpty()) {
                throw ValidationException::withMessages(['detail_opname' => 'Stok opname harus memiliki minimal satu detail.']);
            }
            $nomor = $persediaan->nomorBerikutnya($idCabang, 'PENYESUAIAN_STOK', 'PS', $dokumen->tanggal_stok_opname);
            $totalNilai = round((float) $detail->sum('nilai_selisih'), 2);
            $idPenyesuaian = (int) DB::table('penyesuaian_stok')->insertGetId([
                'id_cabang' => $idCabang,
                'id_gudang' => $dokumen->id_gudang,
                'id_stok_opname' => $id,
                'nomor_penyesuaian' => $nomor,
                'tanggal_penyesuaian' => $dokumen->tanggal_stok_opname,
                'alasan_penyesuaian' => 'Selisih hasil stok opname '.$dokumen->nomor_stok_opname,
                'status_penyesuaian' => 'DISETUJUI',
                'id_pengguna_penyetuju' => $request->user()->id_pengguna,
                'tanggal_disetujui' => now(),
                'total_nilai' => $totalNilai,
                'keterangan' => 'Dibentuk otomatis dari stok opname.',
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($detail as $baris) {
                $selisih = round((float) $baris->jumlah_selisih, 3);
                if (abs($selisih) < 0.0001) {
                    continue;
                }
                $jenis = $selisih > 0 ? 'TAMBAH' : 'KURANG';
                $jumlah = abs($selisih);
                DB::table('penyesuaian_stok_detail')->insert([
                    'id_penyesuaian_stok' => $idPenyesuaian,
                    'id_barang' => $baris->id_barang,
                    'id_lokasi_gudang' => $baris->id_lokasi_gudang,
                    'jenis_penyesuaian' => $jenis,
                    'jumlah_dasar' => $jumlah,
                    'harga_pokok' => $baris->harga_pokok,
                    'total_nilai' => round($jumlah * (float) $baris->harga_pokok, 2),
                    'nomor_lot' => $baris->nomor_lot,
                    'tanggal_kedaluwarsa' => $baris->tanggal_kedaluwarsa,
                    'keterangan' => $baris->keterangan,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);

                $persediaan->catatMutasi(
                    $idCabang,
                    (int) $dokumen->id_gudang,
                    (int) $baris->id_lokasi_gudang,
                    (int) $baris->id_barang,
                    $jenis === 'TAMBAH' ? $jumlah : 0,
                    $jenis === 'KURANG' ? $jumlah : 0,
                    (float) $baris->harga_pokok,
                    'STOK_OPNAME',
                    'PENYESUAIAN_STOK',
                    $idPenyesuaian,
                    $nomor,
                    'Selisih opname '.$dokumen->nomor_stok_opname,
                    (int) $request->user()->id_pengguna,
                );
            }

            DB::table('stok_opname')->where('id_stok_opname', $id)->update([
                'status_stok_opname' => 'DISETUJUI',
                'id_pengguna_penyetuju' => $request->user()->id_pengguna,
                'tanggal_disetujui' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);

            return $idPenyesuaian;
        });

        $audit->catat($request, 'PERSEDIAAN', 'SETUJUI', 'stok_opname', $id, 'Menyetujui stok opname dan membentuk penyesuaian stok.', null, ['id_penyesuaian_stok' => $idPenyesuaian]);

        return back()->with('berhasil', 'Stok opname disetujui. Selisih telah diproses menjadi penyesuaian stok.');
    }

    public function batalkan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'STOK_OPNAME_KELOLA');
        $dokumen = $this->temukan((int) $request->session()->get('id_cabang_aktif'), $id);
        abort_unless(in_array($dokumen->status_stok_opname, ['DRAF', 'PROSES', 'SELESAI'], true), 422, 'Stok opname yang sudah disetujui tidak dapat dibatalkan.');

        DB::table('stok_opname')->where('id_stok_opname', $id)->update([
            'status_stok_opname' => 'DIBATALKAN',
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'PERSEDIAAN', 'BATAL', 'stok_opname', $id, 'Membatalkan stok opname.');

        return back()->with('berhasil', 'Stok opname berhasil dibatalkan.');
    }

    private function validasi(Request $request, int $idCabang): array
    {
        return $request->validate([
            'id_gudang' => ['required', 'integer', Rule::exists('gudang', 'id_gudang')->where(fn ($query) => $query->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at'))],
            'tanggal_stok_opname' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
            'detail_opname' => ['required', 'array', 'min:1'],
            'detail_opname.*.id_barang' => ['required', 'integer'],
            'detail_opname.*.id_lokasi_gudang' => ['required', 'integer'],
            'detail_opname.*.jumlah_fisik' => ['required', 'numeric', 'min:0'],
            'detail_opname.*.nomor_lot' => ['nullable', 'string', 'max:100'],
            'detail_opname.*.tanggal_kedaluwarsa' => ['nullable', 'date'],
            'detail_opname.*.keterangan' => ['nullable', 'string'],
        ]);
    }

    private function sinkronkanDetail(Request $request, LayananPersediaan $persediaan, int $idOpname, int $idGudang, array $detail, bool $pertahankanSistem): void
    {
        $lama = DB::table('stok_opname_detail')->where('id_stok_opname', $idOpname)->get()->keyBy(
            fn ($baris) => $this->kunci((int) $baris->id_barang, (int) $baris->id_lokasi_gudang, $baris->nomor_lot)
        );
        $dipakai = [];

        foreach ($detail as $index => $baris) {
            $barang = $this->barang((int) $baris['id_barang']);
            $lokasiValid = DB::table('lokasi_gudang')->where('id_lokasi_gudang', (int) $baris['id_lokasi_gudang'])->where('id_gudang', $idGudang)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
            if (! $lokasiValid) {
                throw ValidationException::withMessages(["detail_opname.{$index}.id_lokasi_gudang" => 'Lokasi tidak termasuk gudang yang dipilih.']);
            }
            $persediaan->pastikanDesimal((float) $baris['jumlah_fisik'], (int) $barang->jumlah_desimal, "detail_opname.{$index}.jumlah_fisik");
            if ((bool) $barang->wajib_nomor_lot && blank($baris['nomor_lot'] ?? null)) {
                throw ValidationException::withMessages(["detail_opname.{$index}.nomor_lot" => 'Nomor lot wajib diisi untuk barang ini.']);
            }
            if ((bool) $barang->wajib_tanggal_kedaluwarsa && blank($baris['tanggal_kedaluwarsa'] ?? null)) {
                throw ValidationException::withMessages(["detail_opname.{$index}.tanggal_kedaluwarsa" => 'Tanggal kedaluwarsa wajib diisi untuk barang ini.']);
            }

            $lot = blank($baris['nomor_lot'] ?? null) ? null : trim((string) $baris['nomor_lot']);
            $kunci = $this->kunci((int) $baris['id_barang'], (int) $baris['id_lokasi_gudang'], $lot);
            if (isset($dipakai[$kunci])) {
                throw ValidationException::withMessages(["detail_opname.{$index}.id_barang" => 'Kombinasi barang, lokasi, dan nomor lot tidak boleh duplikat.']);
            }
            $dipakai[$kunci] = true;

            $barisLama = $lama->get($kunci);
            $saldo = DB::table('saldo_stok')->where('id_gudang', $idGudang)->where('id_lokasi_gudang', (int) $baris['id_lokasi_gudang'])->where('id_barang', (int) $baris['id_barang'])->first();
            $jumlahSistem = $pertahankanSistem && $barisLama ? (float) $barisLama->jumlah_sistem : (float) ($saldo->jumlah_stok ?? 0);
            $hargaPokok = $pertahankanSistem && $barisLama ? (float) $barisLama->harga_pokok : (float) ($saldo->harga_pokok_rata_rata ?? 0);
            $jumlahFisik = round((float) $baris['jumlah_fisik'], 3);
            $selisih = round($jumlahFisik - $jumlahSistem, 3);
            $payload = [
                'id_stok_opname' => $idOpname,
                'id_barang' => (int) $baris['id_barang'],
                'id_lokasi_gudang' => (int) $baris['id_lokasi_gudang'],
                'jumlah_sistem' => $jumlahSistem,
                'jumlah_fisik' => $jumlahFisik,
                'jumlah_selisih' => $selisih,
                'harga_pokok' => $hargaPokok,
                'nilai_selisih' => round($selisih * $hargaPokok, 2),
                'nomor_lot' => $lot,
                'tanggal_kedaluwarsa' => $baris['tanggal_kedaluwarsa'] ?? null,
                'keterangan' => $baris['keterangan'] ?? null,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
                'deleted_at' => null,
                'deleted_by' => null,
            ];

            if ($barisLama) {
                DB::table('stok_opname_detail')->where('id_stok_opname_detail', $barisLama->id_stok_opname_detail)->update($payload);
            } else {
                DB::table('stok_opname_detail')->insert(array_merge($payload, [
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]));
            }
        }

        foreach ($lama as $kunci => $barisLama) {
            if (! isset($dipakai[$kunci])) {
                DB::table('stok_opname_detail')->where('id_stok_opname_detail', $barisLama->id_stok_opname_detail)->update([
                    'deleted_at' => now(),
                    'deleted_by' => $request->user()->id_pengguna,
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);
            }
        }
    }

    private function barang(int $idBarang): object
    {
        $barang = DB::table('barang as b')->join('satuan as s', 's.id_satuan', '=', 'b.id_satuan_dasar')->where('b.id_barang', $idBarang)->where('b.jenis_barang', 'BARANG')->where('b.status_aktif', 1)->whereNull('b.deleted_at')->select('b.*', 's.jumlah_desimal', 's.kode_satuan')->first();
        if (! $barang) {
            throw ValidationException::withMessages(['id_barang' => 'Barang tidak valid atau tidak aktif.']);
        }

        return $barang;
    }

    private function kunci(int $idBarang, int $idLokasi, ?string $lot): string
    {
        return $idBarang.'|'.$idLokasi.'|'.($lot ?? '');
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
        $item = DB::table('stok_opname')->where('id_stok_opname', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->first();
        abort_if(! $item, 404);

        return $item;
    }

    private function pastikanAkses(Request $request, string $izin): void
    {
        abort_unless($request->user()?->memilikiHakAkses($izin, (int) $request->session()->get('id_cabang_aktif')), 403);
    }
}
