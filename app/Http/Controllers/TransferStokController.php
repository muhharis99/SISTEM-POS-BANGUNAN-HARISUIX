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

class TransferStokController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'TRANSFER_STOK_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $pencarian = trim((string) $request->query('pencarian'));

        $dokumen = DB::table('transfer_stok as t')
            ->join('gudang as asal', 'asal.id_gudang', '=', 't.id_gudang_asal')
            ->join('gudang as tujuan', 'tujuan.id_gudang', '=', 't.id_gudang_tujuan')
            ->where('t.id_cabang', $idCabang)
            ->whereNull('t.deleted_at')
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('t.nomor_transfer', 'like', "%{$pencarian}%")
                    ->orWhere('asal.nama_gudang', 'like', "%{$pencarian}%")
                    ->orWhere('tujuan.nama_gudang', 'like', "%{$pencarian}%");
            }))
            ->select('t.*', 'asal.nama_gudang as nama_gudang_asal', 'tujuan.nama_gudang as nama_gudang_tujuan')
            ->orderByDesc('t.tanggal_transfer')
            ->orderByDesc('t.id_transfer_stok')
            ->paginate(15)
            ->withQueryString();

        $detail = DB::table('transfer_stok_detail as d')
            ->join('barang_satuan as bs', 'bs.id_barang_satuan', '=', 'd.id_barang_satuan')
            ->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')
            ->join('satuan as s', 's.id_satuan', '=', 'bs.id_satuan')
            ->join('lokasi_gudang as la', 'la.id_lokasi_gudang', '=', 'd.id_lokasi_asal')
            ->join('lokasi_gudang as lt', 'lt.id_lokasi_gudang', '=', 'd.id_lokasi_tujuan')
            ->whereIn('d.id_transfer_stok', $dokumen->pluck('id_transfer_stok')->all() ?: [0])
            ->whereNull('d.deleted_at')
            ->select('d.*', 'b.kode_barang', 'b.nama_barang', 's.kode_satuan', 'la.nama_lokasi as nama_lokasi_asal', 'lt.nama_lokasi as nama_lokasi_tujuan')
            ->orderBy('b.nama_barang')
            ->get()
            ->groupBy('id_transfer_stok');

        return view('transfer_stok.index', array_merge(
            compact('dokumen', 'detail', 'pencarian'),
            $this->pilihan($idCabang)
        ));
    }

    public function simpan(Request $request, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'TRANSFER_STOK_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $data = $this->validasi($request, $idCabang);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $persediaan): int {
            $nomor = $persediaan->nomorBerikutnya($idCabang, 'TRANSFER_STOK', 'TS', $data['tanggal_transfer']);
            $id = (int) DB::table('transfer_stok')->insertGetId([
                'id_cabang' => $idCabang,
                'id_gudang_asal' => $data['id_gudang_asal'],
                'id_gudang_tujuan' => $data['id_gudang_tujuan'],
                'nomor_transfer' => $nomor,
                'tanggal_transfer' => $data['tanggal_transfer'],
                'tanggal_kebutuhan' => $data['tanggal_kebutuhan'] ?? null,
                'status_transfer' => 'DRAF',
                'id_pengguna_peminta' => $request->user()->id_pengguna,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);
            $this->sinkronkanDetail($request, $persediaan, $id, (int) $data['id_gudang_asal'], (int) $data['id_gudang_tujuan'], $data['detail_transfer']);

            return $id;
        });

        $audit->catat($request, 'PERSEDIAAN', 'TAMBAH', 'transfer_stok', $id, 'Membuat draf transfer stok.');

        return back()->with('berhasil', 'Transfer stok berhasil dibuat sebagai draf.');
    }

    public function ubah(Request $request, int $id, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'TRANSFER_STOK_KELOLA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $lama = $this->temukan($idCabang, $id);
        abort_unless($lama->status_transfer === 'DRAF', 422, 'Hanya transfer berstatus draf yang dapat diubah.');
        $data = $this->validasi($request, $idCabang);

        DB::transaction(function () use ($request, $data, $id, $persediaan): void {
            DB::table('transfer_stok')->where('id_transfer_stok', $id)->update([
                'id_gudang_asal' => $data['id_gudang_asal'],
                'id_gudang_tujuan' => $data['id_gudang_tujuan'],
                'tanggal_transfer' => $data['tanggal_transfer'],
                'tanggal_kebutuhan' => $data['tanggal_kebutuhan'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
            $this->sinkronkanDetail($request, $persediaan, $id, (int) $data['id_gudang_asal'], (int) $data['id_gudang_tujuan'], $data['detail_transfer']);
        });

        $audit->catat($request, 'PERSEDIAAN', 'UBAH', 'transfer_stok', $id, 'Mengubah draf transfer stok.', (array) $lama, $data);

        return back()->with('berhasil', 'Draf transfer stok berhasil diperbarui.');
    }

    public function setujui(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'TRANSFER_STOK_SETUJUI');
        $dokumen = $this->temukan((int) $request->session()->get('id_cabang_aktif'), $id);
        abort_unless($dokumen->status_transfer === 'DRAF', 422, 'Hanya transfer draf yang dapat disetujui.');
        abort_unless(DB::table('transfer_stok_detail')->where('id_transfer_stok', $id)->whereNull('deleted_at')->exists(), 422, 'Transfer harus memiliki detail.');

        DB::table('transfer_stok')->where('id_transfer_stok', $id)->update([
            'status_transfer' => 'DISETUJUI',
            'id_pengguna_penyetuju' => $request->user()->id_pengguna,
            'tanggal_disetujui' => now(),
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'PERSEDIAAN', 'SETUJUI', 'transfer_stok', $id, 'Menyetujui transfer stok.');

        return back()->with('berhasil', 'Transfer stok berhasil disetujui dan siap dikirim.');
    }

    public function kirim(Request $request, int $id, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'TRANSFER_STOK_KIRIM');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');

        DB::transaction(function () use ($request, $id, $idCabang, $persediaan): void {
            $dokumen = DB::table('transfer_stok')->where('id_transfer_stok', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            abort_if(! $dokumen, 404);
            abort_unless($dokumen->status_transfer === 'DISETUJUI', 422, 'Transfer harus disetujui sebelum dikirim.');

            $detail = DB::table('transfer_stok_detail')->where('id_transfer_stok', $id)->whereNull('deleted_at')->lockForUpdate()->get();
            if ($detail->isEmpty()) {
                throw ValidationException::withMessages(['detail_transfer' => 'Transfer tidak memiliki detail.']);
            }

            foreach ($detail as $baris) {
                $barangSatuan = $persediaan->barangSatuan((int) $baris->id_barang_satuan);
                $jumlahDikirim = (float) $baris->jumlah_diminta;
                $jumlahDasar = $persediaan->jumlahDasar($barangSatuan, $jumlahDikirim, 'jumlah_dikirim');
                $saldo = $persediaan->saldoTerkunci((int) $dokumen->id_gudang_asal, (int) $baris->id_lokasi_asal, (int) $barangSatuan->id_barang);
                $hargaPokok = (float) $saldo->harga_pokok_rata_rata;

                $persediaan->catatMutasi(
                    $idCabang,
                    (int) $dokumen->id_gudang_asal,
                    (int) $baris->id_lokasi_asal,
                    (int) $barangSatuan->id_barang,
                    0,
                    $jumlahDasar,
                    $hargaPokok,
                    'TRANSFER_KELUAR',
                    'TRANSFER_STOK',
                    $id,
                    $dokumen->nomor_transfer,
                    $baris->keterangan,
                    (int) $request->user()->id_pengguna,
                );

                DB::table('transfer_stok_detail')->where('id_transfer_stok_detail', $baris->id_transfer_stok_detail)->update([
                    'jumlah_dikirim' => round($jumlahDikirim, 3),
                    'jumlah_dasar_dikirim' => $jumlahDasar,
                    'harga_pokok' => round($hargaPokok, 4),
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);
            }

            DB::table('transfer_stok')->where('id_transfer_stok', $id)->update([
                'status_transfer' => 'DIKIRIM',
                'id_pengguna_pengirim' => $request->user()->id_pengguna,
                'tanggal_dikirim' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PERSEDIAAN', 'KIRIM', 'transfer_stok', $id, 'Mengirim transfer dan mengurangi stok asal.');

        return back()->with('berhasil', 'Transfer dikirim dan stok lokasi asal telah berkurang.');
    }

    public function terima(Request $request, int $id, LayananPersediaan $persediaan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'TRANSFER_STOK_TERIMA');
        $idCabang = (int) $request->session()->get('id_cabang_aktif');

        DB::transaction(function () use ($request, $id, $idCabang, $persediaan): void {
            $dokumen = DB::table('transfer_stok')->where('id_transfer_stok', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            abort_if(! $dokumen, 404);
            abort_unless($dokumen->status_transfer === 'DIKIRIM', 422, 'Hanya transfer yang sudah dikirim yang dapat diterima.');

            $detail = DB::table('transfer_stok_detail')->where('id_transfer_stok', $id)->whereNull('deleted_at')->lockForUpdate()->get();
            foreach ($detail as $baris) {
                $barangSatuan = $persediaan->barangSatuan((int) $baris->id_barang_satuan);
                $jumlahDiterima = (float) $baris->jumlah_dikirim;
                $jumlahDasar = (float) $baris->jumlah_dasar_dikirim;

                $persediaan->catatMutasi(
                    $idCabang,
                    (int) $dokumen->id_gudang_tujuan,
                    (int) $baris->id_lokasi_tujuan,
                    (int) $barangSatuan->id_barang,
                    $jumlahDasar,
                    0,
                    (float) $baris->harga_pokok,
                    'TRANSFER_MASUK',
                    'TRANSFER_STOK',
                    $id,
                    $dokumen->nomor_transfer,
                    $baris->keterangan,
                    (int) $request->user()->id_pengguna,
                );

                DB::table('transfer_stok_detail')->where('id_transfer_stok_detail', $baris->id_transfer_stok_detail)->update([
                    'jumlah_diterima' => round($jumlahDiterima, 3),
                    'jumlah_dasar_diterima' => round($jumlahDasar, 3),
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);
            }

            DB::table('transfer_stok')->where('id_transfer_stok', $id)->update([
                'status_transfer' => 'DITERIMA',
                'id_pengguna_penerima' => $request->user()->id_pengguna,
                'tanggal_diterima' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PERSEDIAAN', 'TERIMA', 'transfer_stok', $id, 'Menerima transfer dan menambah stok tujuan.');

        return back()->with('berhasil', 'Transfer diterima dan stok lokasi tujuan telah bertambah.');
    }

    public function batalkan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'TRANSFER_STOK_KELOLA');
        $dokumen = $this->temukan((int) $request->session()->get('id_cabang_aktif'), $id);
        abort_unless(in_array($dokumen->status_transfer, ['DRAF', 'DISETUJUI'], true), 422, 'Transfer yang sudah dikirim tidak dapat dibatalkan.');

        DB::table('transfer_stok')->where('id_transfer_stok', $id)->update([
            'status_transfer' => 'DIBATALKAN',
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'PERSEDIAAN', 'BATAL', 'transfer_stok', $id, 'Membatalkan transfer stok.');

        return back()->with('berhasil', 'Transfer stok berhasil dibatalkan.');
    }

    private function validasi(Request $request, int $idCabang): array
    {
        $data = $request->validate([
            'id_gudang_asal' => ['required', 'integer', Rule::exists('gudang', 'id_gudang')->where(fn ($query) => $query->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at'))],
            'id_gudang_tujuan' => ['required', 'integer', Rule::exists('gudang', 'id_gudang')->where(fn ($query) => $query->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at'))],
            'tanggal_transfer' => ['required', 'date'],
            'tanggal_kebutuhan' => ['nullable', 'date', 'after_or_equal:tanggal_transfer'],
            'keterangan' => ['nullable', 'string'],
            'detail_transfer' => ['required', 'array', 'min:1'],
            'detail_transfer.*.id_barang_satuan' => ['required', 'integer'],
            'detail_transfer.*.id_lokasi_asal' => ['required', 'integer'],
            'detail_transfer.*.id_lokasi_tujuan' => ['required', 'integer'],
            'detail_transfer.*.jumlah_diminta' => ['required', 'numeric', 'gt:0'],
            'detail_transfer.*.nomor_lot' => ['nullable', 'string', 'max:100'],
            'detail_transfer.*.tanggal_kedaluwarsa' => ['nullable', 'date'],
            'detail_transfer.*.keterangan' => ['nullable', 'string'],
        ]);

        return $data;
    }

    private function sinkronkanDetail(Request $request, LayananPersediaan $persediaan, int $idTransfer, int $idGudangAsal, int $idGudangTujuan, array $detail): void
    {
        DB::table('transfer_stok_detail')->where('id_transfer_stok', $idTransfer)->update([
            'deleted_at' => now(),
            'deleted_by' => $request->user()->id_pengguna,
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $kombinasi = [];

        foreach ($detail as $index => $baris) {
            $barangSatuan = $persediaan->barangSatuan((int) $baris['id_barang_satuan']);
            $asalValid = DB::table('lokasi_gudang')->where('id_lokasi_gudang', (int) $baris['id_lokasi_asal'])->where('id_gudang', $idGudangAsal)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
            $tujuanValid = DB::table('lokasi_gudang')->where('id_lokasi_gudang', (int) $baris['id_lokasi_tujuan'])->where('id_gudang', $idGudangTujuan)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
            if (! $asalValid || ! $tujuanValid) {
                throw ValidationException::withMessages(["detail_transfer.{$index}.id_lokasi_asal" => 'Lokasi asal atau tujuan tidak sesuai dengan gudang yang dipilih.']);
            }
            if ((int) $baris['id_lokasi_asal'] === (int) $baris['id_lokasi_tujuan']) {
                throw ValidationException::withMessages(["detail_transfer.{$index}.id_lokasi_tujuan" => 'Lokasi tujuan harus berbeda dari lokasi asal.']);
            }
            if ((bool) $barangSatuan->wajib_nomor_lot && blank($baris['nomor_lot'] ?? null)) {
                throw ValidationException::withMessages(["detail_transfer.{$index}.nomor_lot" => 'Nomor lot wajib diisi untuk barang ini.']);
            }
            if ((bool) $barangSatuan->wajib_tanggal_kedaluwarsa && blank($baris['tanggal_kedaluwarsa'] ?? null)) {
                throw ValidationException::withMessages(["detail_transfer.{$index}.tanggal_kedaluwarsa" => 'Tanggal kedaluwarsa wajib diisi untuk barang ini.']);
            }

            $jumlahDasar = $persediaan->jumlahDasar($barangSatuan, $baris['jumlah_diminta'], "detail_transfer.{$index}.jumlah_diminta");
            $lot = blank($baris['nomor_lot'] ?? null) ? null : trim((string) $baris['nomor_lot']);
            $kunci = $baris['id_barang_satuan'].'|'.$baris['id_lokasi_asal'].'|'.$baris['id_lokasi_tujuan'].'|'.($lot ?? '');
            if (isset($kombinasi[$kunci])) {
                throw ValidationException::withMessages(["detail_transfer.{$index}.id_barang_satuan" => 'Detail transfer yang sama tidak boleh duplikat.']);
            }
            $kombinasi[$kunci] = true;

            DB::table('transfer_stok_detail')->insert([
                'id_transfer_stok' => $idTransfer,
                'id_barang_satuan' => (int) $baris['id_barang_satuan'],
                'id_lokasi_asal' => (int) $baris['id_lokasi_asal'],
                'id_lokasi_tujuan' => (int) $baris['id_lokasi_tujuan'],
                'nilai_konversi' => (float) $barangSatuan->nilai_konversi,
                'jumlah_diminta' => round((float) $baris['jumlah_diminta'], 3),
                'jumlah_dikirim' => 0,
                'jumlah_diterima' => 0,
                'jumlah_dasar_dikirim' => 0,
                'jumlah_dasar_diterima' => 0,
                'harga_pokok' => 0,
                'nomor_lot' => $lot,
                'tanggal_kedaluwarsa' => $baris['tanggal_kedaluwarsa'] ?? null,
                'keterangan' => $baris['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);
        }
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
        $item = DB::table('transfer_stok')->where('id_transfer_stok', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->first();
        abort_if(! $item, 404);

        return $item;
    }

    private function pastikanAkses(Request $request, string $izin): void
    {
        abort_unless($request->user()?->memilikiHakAkses($izin, (int) $request->session()->get('id_cabang_aktif')), 403);
    }
}
