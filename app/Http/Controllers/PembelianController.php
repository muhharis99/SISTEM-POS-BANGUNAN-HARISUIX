<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use App\Services\LayananPembelian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PembelianController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'PEMBELIAN_LIHAT');
        $idCabang = $this->idCabang($request);
        $pencarian = trim((string) $request->query('pencarian'));

        $permintaan = DB::table('permintaan_pembelian as h')
            ->leftJoin('pengguna as u', 'u.id_pengguna', '=', 'h.id_pengguna_peminta')
            ->where('h.id_cabang', $idCabang)
            ->whereNull('h.deleted_at')
            ->when($pencarian !== '', fn ($q) => $q->where('h.nomor_permintaan', 'like', "%{$pencarian}%"))
            ->select('h.*', 'u.nama_tampilan as nama_peminta')
            ->orderByDesc('h.id_permintaan_pembelian')->limit(25)->get();

        $pesanan = DB::table('pesanan_pembelian as h')
            ->join('pemasok as p', 'p.id_pemasok', '=', 'h.id_pemasok')
            ->where('h.id_cabang', $idCabang)->whereNull('h.deleted_at')
            ->when($pencarian !== '', fn ($q) => $q->where(function ($s) use ($pencarian): void {
                $s->where('h.nomor_pesanan', 'like', "%{$pencarian}%")->orWhere('p.nama_pemasok', 'like', "%{$pencarian}%");
            }))
            ->select('h.*', 'p.nama_pemasok')->orderByDesc('h.id_pesanan_pembelian')->limit(25)->get();

        $penerimaan = DB::table('penerimaan_barang as h')
            ->join('pemasok as p', 'p.id_pemasok', '=', 'h.id_pemasok')
            ->join('gudang as g', 'g.id_gudang', '=', 'h.id_gudang')
            ->where('h.id_cabang', $idCabang)->whereNull('h.deleted_at')
            ->select('h.*', 'p.nama_pemasok', 'g.nama_gudang')->orderByDesc('h.id_penerimaan_barang')->limit(25)->get();

        $faktur = DB::table('faktur_pembelian as h')
            ->join('pemasok as p', 'p.id_pemasok', '=', 'h.id_pemasok')
            ->where('h.id_cabang', $idCabang)->whereNull('h.deleted_at')
            ->select('h.*', 'p.nama_pemasok')->orderByDesc('h.id_faktur_pembelian')->limit(25)->get();

        $retur = DB::table('retur_pembelian as h')
            ->join('pemasok as p', 'p.id_pemasok', '=', 'h.id_pemasok')
            ->join('gudang as g', 'g.id_gudang', '=', 'h.id_gudang')
            ->where('h.id_cabang', $idCabang)->whereNull('h.deleted_at')
            ->select('h.*', 'p.nama_pemasok', 'g.nama_gudang')->orderByDesc('h.id_retur_pembelian')->limit(25)->get();

        $ringkasan = [
            'permintaan_aktif' => DB::table('permintaan_pembelian')->where('id_cabang', $idCabang)->whereIn('status_permintaan', ['DRAF', 'DIAJUKAN', 'DISETUJUI', 'DIPROSES'])->whereNull('deleted_at')->count(),
            'pesanan_aktif' => DB::table('pesanan_pembelian')->where('id_cabang', $idCabang)->whereNotIn('status_pesanan', ['SELESAI', 'DIBATALKAN'])->whereNull('deleted_at')->count(),
            'penerimaan_bulan_ini' => DB::table('penerimaan_barang')->where('id_cabang', $idCabang)->where('status_penerimaan', 'DITERIMA')->whereBetween('tanggal_penerimaan', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->count(),
            'nilai_faktur_bulan_ini' => (float) DB::table('faktur_pembelian')->where('id_cabang', $idCabang)->whereNotIn('status_faktur', ['DRAF', 'DIBATALKAN'])->whereBetween('tanggal_faktur', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('total_bersih'),
        ];

        return view('pembelian.index', array_merge(
            compact('permintaan', 'pesanan', 'penerimaan', 'faktur', 'retur', 'ringkasan', 'pencarian'),
            $this->pilihan($idCabang)
        ));
    }

    public function simpanPermintaan(Request $request, LayananPembelian $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PERMINTAAN_PEMBELIAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'tanggal_permintaan' => ['required', 'date'],
            'tanggal_kebutuhan' => ['nullable', 'date', 'after_or_equal:tanggal_permintaan'],
            'tingkat_kepentingan' => ['required', Rule::in(['RENDAH', 'NORMAL', 'TINGGI', 'MENDESAK'])],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail.*.perkiraan_harga' => ['nullable', 'numeric', 'min:0'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            $id = (int) DB::table('permintaan_pembelian')->insertGetId([
                'id_cabang' => $idCabang,
                'nomor_permintaan' => $layanan->nomorBerikutnya($idCabang, 'PERMINTAAN_PEMBELIAN', 'PP', $data['tanggal_permintaan']),
                'tanggal_permintaan' => $data['tanggal_permintaan'],
                'tanggal_kebutuhan' => $data['tanggal_kebutuhan'] ?? null,
                'tingkat_kepentingan' => $data['tingkat_kepentingan'],
                'status_permintaan' => 'DRAF',
                'id_pengguna_peminta' => $request->user()->id_pengguna,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($data['detail'] as $index => $baris) {
                $barangSatuan = $layanan->barangSatuan((int) $baris['id_barang_satuan']);
                $jumlahDasar = $layanan->jumlahDasar($barangSatuan, $baris['jumlah'], "detail.{$index}.jumlah");
                DB::table('permintaan_pembelian_detail')->insert([
                    'id_permintaan_pembelian' => $id,
                    'id_barang_satuan' => $barangSatuan->id_barang_satuan,
                    'nilai_konversi' => $barangSatuan->nilai_konversi,
                    'jumlah_diminta' => $baris['jumlah'],
                    'jumlah_dasar_diminta' => $jumlahDasar,
                    'jumlah_dipesan' => 0,
                    'perkiraan_harga' => $baris['perkiraan_harga'] ?? 0,
                    'keterangan' => $baris['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PEMBELIAN', 'TAMBAH', 'permintaan_pembelian', $id, 'Membuat draf permintaan pembelian.');

        return back()->with('berhasil', 'Permintaan pembelian berhasil dibuat sebagai draf.');
    }

    public function ajukanPermintaan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PERMINTAAN_PEMBELIAN_KELOLA', 'permintaan_pembelian', 'id_permintaan_pembelian', $id, 'status_permintaan', ['DRAF'], 'DIAJUKAN', 'Permintaan pembelian berhasil diajukan.');
    }

    public function setujuiPermintaan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PERMINTAAN_PEMBELIAN_SETUJUI', 'permintaan_pembelian', 'id_permintaan_pembelian', $id, 'status_permintaan', ['DIAJUKAN'], 'DISETUJUI', 'Permintaan pembelian berhasil disetujui.', true);
    }

    public function tolakPermintaan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PERMINTAAN_PEMBELIAN_SETUJUI', 'permintaan_pembelian', 'id_permintaan_pembelian', $id, 'status_permintaan', ['DIAJUKAN'], 'DITOLAK', 'Permintaan pembelian berhasil ditolak.');
    }

    public function batalkanPermintaan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PERMINTAAN_PEMBELIAN_KELOLA', 'permintaan_pembelian', 'id_permintaan_pembelian', $id, 'status_permintaan', ['DRAF', 'DIAJUKAN'], 'DIBATALKAN', 'Permintaan pembelian berhasil dibatalkan.');
    }

    public function simpanPesanan(Request $request, LayananPembelian $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PESANAN_PEMBELIAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'id_pemasok' => ['required', 'integer'],
            'tanggal_pesanan' => ['required', 'date'],
            'tanggal_perkiraan_tiba' => ['nullable', 'date', 'after_or_equal:tanggal_pesanan'],
            'cara_pembayaran' => ['required', Rule::in(['TUNAI', 'TEMPO'])],
            'lama_jatuh_tempo' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'alamat_pengiriman' => ['nullable', 'string'],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'biaya_lain' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_permintaan_pembelian_detail' => ['nullable', 'integer'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.id_tarif_pajak' => ['nullable', 'integer'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'detail.*.potongan_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);
        $layanan->pastikanPemasok((int) $data['id_pemasok']);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            $rincian = [];
            $totalKotor = $totalPotongan = $totalPajak = 0.0;
            foreach ($data['detail'] as $index => $baris) {
                $barangSatuan = $layanan->barangSatuan((int) $baris['id_barang_satuan']);
                $jumlahDasar = $layanan->jumlahDasar($barangSatuan, $baris['jumlah'], "detail.{$index}.jumlah");
                $pajakPersen = $layanan->persenPajak(isset($baris['id_tarif_pajak']) ? (int) $baris['id_tarif_pajak'] : null);
                $hitung = $layanan->hitungBaris((float) $baris['jumlah'], (float) $baris['harga_satuan'], (float) ($baris['potongan_persen'] ?? 0), $pajakPersen);
                $totalKotor += $hitung['kotor'];
                $totalPotongan += $hitung['potongan'];
                $totalPajak += $hitung['pajak'];
                $rincian[] = compact('barangSatuan', 'jumlahDasar', 'pajakPersen', 'hitung', 'baris');
            }

            $biayaPengiriman = (float) ($data['biaya_pengiriman'] ?? 0);
            $biayaLain = (float) ($data['biaya_lain'] ?? 0);
            $id = (int) DB::table('pesanan_pembelian')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pemasok' => $data['id_pemasok'],
                'nomor_pesanan' => $layanan->nomorBerikutnya($idCabang, 'PESANAN_PEMBELIAN', 'PO', $data['tanggal_pesanan']),
                'tanggal_pesanan' => $data['tanggal_pesanan'],
                'tanggal_perkiraan_tiba' => $data['tanggal_perkiraan_tiba'] ?? null,
                'status_pesanan' => 'DRAF',
                'cara_pembayaran' => $data['cara_pembayaran'],
                'lama_jatuh_tempo' => $data['lama_jatuh_tempo'] ?? 0,
                'alamat_pengiriman' => $data['alamat_pengiriman'] ?? null,
                'total_kotor' => round($totalKotor, 2),
                'total_potongan' => round($totalPotongan, 2),
                'total_pajak' => round($totalPajak, 2),
                'biaya_pengiriman' => $biayaPengiriman,
                'biaya_lain' => $biayaLain,
                'total_bersih' => round($totalKotor - $totalPotongan + $totalPajak + $biayaPengiriman + $biayaLain, 2),
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                $baris = $item['baris'];
                DB::table('pesanan_pembelian_detail')->insert([
                    'id_pesanan_pembelian' => $id,
                    'id_permintaan_pembelian_detail' => $baris['id_permintaan_pembelian_detail'] ?? null,
                    'id_barang_satuan' => $item['barangSatuan']->id_barang_satuan,
                    'id_tarif_pajak' => $baris['id_tarif_pajak'] ?? null,
                    'nilai_konversi' => $item['barangSatuan']->nilai_konversi,
                    'jumlah' => $baris['jumlah'],
                    'jumlah_dasar' => $item['jumlahDasar'],
                    'harga_satuan' => $baris['harga_satuan'],
                    'potongan_persen' => $baris['potongan_persen'] ?? 0,
                    'potongan_nilai' => $item['hitung']['potongan'],
                    'pajak_persen' => $item['pajakPersen'],
                    'pajak_nilai' => $item['hitung']['pajak'],
                    'total_baris' => $item['hitung']['total'],
                    'jumlah_diterima' => 0,
                    'jumlah_difakturkan' => 0,
                    'keterangan' => $baris['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PEMBELIAN', 'TAMBAH', 'pesanan_pembelian', $id, 'Membuat draf pesanan pembelian.');

        return back()->with('berhasil', 'Pesanan pembelian berhasil dibuat sebagai draf.');
    }

    public function ajukanPesanan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PESANAN_PEMBELIAN_KELOLA', 'pesanan_pembelian', 'id_pesanan_pembelian', $id, 'status_pesanan', ['DRAF'], 'DIAJUKAN', 'Pesanan pembelian berhasil diajukan.');
    }

    public function setujuiPesanan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PESANAN_PEMBELIAN_SETUJUI');
        $idCabang = $this->idCabang($request);

        DB::transaction(function () use ($request, $id, $idCabang): void {
            $pesanan = $this->temukanTerkunci('pesanan_pembelian', 'id_pesanan_pembelian', $idCabang, $id);
            abort_unless($pesanan->status_pesanan === 'DIAJUKAN', 422, 'Hanya pesanan yang diajukan dapat disetujui.');
            $detail = DB::table('pesanan_pembelian_detail')->where('id_pesanan_pembelian', $id)->whereNull('deleted_at')->lockForUpdate()->get();
            if ($detail->isEmpty()) {
                throw ValidationException::withMessages(['detail' => 'Pesanan harus memiliki minimal satu detail.']);
            }

            foreach ($detail as $baris) {
                if (! $baris->id_permintaan_pembelian_detail) {
                    continue;
                }
                $permintaanDetail = DB::table('permintaan_pembelian_detail')->where('id_permintaan_pembelian_detail', $baris->id_permintaan_pembelian_detail)->lockForUpdate()->first();
                if (! $permintaanDetail) {
                    continue;
                }
                DB::table('permintaan_pembelian_detail')->where('id_permintaan_pembelian_detail', $baris->id_permintaan_pembelian_detail)->update([
                    'jumlah_dipesan' => round((float) $permintaanDetail->jumlah_dipesan + (float) $baris->jumlah, 3),
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);
                DB::table('permintaan_pembelian')->where('id_permintaan_pembelian', $permintaanDetail->id_permintaan_pembelian)->update([
                    'status_permintaan' => 'DIPROSES',
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);
            }

            DB::table('pesanan_pembelian')->where('id_pesanan_pembelian', $id)->update([
                'status_pesanan' => 'DISETUJUI',
                'id_pengguna_penyetuju' => $request->user()->id_pengguna,
                'tanggal_disetujui' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PEMBELIAN', 'SETUJUI', 'pesanan_pembelian', $id, 'Menyetujui pesanan pembelian.');

        return back()->with('berhasil', 'Pesanan pembelian berhasil disetujui.');
    }

    public function batalkanPesanan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PESANAN_PEMBELIAN_KELOLA', 'pesanan_pembelian', 'id_pesanan_pembelian', $id, 'status_pesanan', ['DRAF', 'DIAJUKAN'], 'DIBATALKAN', 'Pesanan pembelian berhasil dibatalkan.');
    }

    public function simpanPenerimaan(Request $request, LayananPembelian $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PENERIMAAN_BARANG_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'id_gudang' => ['required', 'integer'],
            'id_pemasok' => ['required', 'integer'],
            'id_pesanan_pembelian' => ['nullable', 'integer'],
            'tanggal_penerimaan' => ['required', 'date'],
            'nomor_surat_jalan' => ['nullable', 'string', 'max:100'],
            'tanggal_surat_jalan' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_pesanan_pembelian_detail' => ['nullable', 'integer'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.id_lokasi_gudang' => ['required', 'integer'],
            'detail.*.jumlah_diterima' => ['required', 'numeric', 'gt:0'],
            'detail.*.jumlah_ditolak' => ['nullable', 'numeric', 'min:0'],
            'detail.*.harga_pokok' => ['required', 'numeric', 'min:0'],
            'detail.*.nomor_lot' => ['nullable', 'string', 'max:100'],
            'detail.*.tanggal_produksi' => ['nullable', 'date'],
            'detail.*.tanggal_kedaluwarsa' => ['nullable', 'date'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);
        $layanan->pastikanPemasok((int) $data['id_pemasok']);

        $gudangValid = DB::table('gudang')->where('id_gudang', $data['id_gudang'])->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
        abort_unless($gudangValid, 422, 'Gudang tidak valid untuk cabang aktif.');

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            $id = (int) DB::table('penerimaan_barang')->insertGetId([
                'id_cabang' => $idCabang,
                'id_gudang' => $data['id_gudang'],
                'id_pemasok' => $data['id_pemasok'],
                'id_pesanan_pembelian' => $data['id_pesanan_pembelian'] ?? null,
                'nomor_penerimaan' => $layanan->nomorBerikutnya($idCabang, 'PENERIMAAN_BARANG', 'PB', $data['tanggal_penerimaan']),
                'tanggal_penerimaan' => $data['tanggal_penerimaan'],
                'nomor_surat_jalan' => $data['nomor_surat_jalan'] ?? null,
                'tanggal_surat_jalan' => $data['tanggal_surat_jalan'] ?? null,
                'status_penerimaan' => 'DRAF',
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($data['detail'] as $index => $baris) {
                $barangSatuan = $layanan->barangSatuan((int) $baris['id_barang_satuan']);
                $layanan->pastikanGudangLokasi($idCabang, (int) $data['id_gudang'], (int) $baris['id_lokasi_gudang']);
                $jumlahDasar = $layanan->jumlahDasar($barangSatuan, $baris['jumlah_diterima'], "detail.{$index}.jumlah_diterima");
                if ((bool) $barangSatuan->wajib_nomor_lot && blank($baris['nomor_lot'] ?? null)) {
                    throw ValidationException::withMessages(["detail.{$index}.nomor_lot" => 'Nomor lot wajib diisi untuk barang ini.']);
                }
                if ((bool) $barangSatuan->wajib_tanggal_kedaluwarsa && blank($baris['tanggal_kedaluwarsa'] ?? null)) {
                    throw ValidationException::withMessages(["detail.{$index}.tanggal_kedaluwarsa" => 'Tanggal kedaluwarsa wajib diisi untuk barang ini.']);
                }

                DB::table('penerimaan_barang_detail')->insert([
                    'id_penerimaan_barang' => $id,
                    'id_pesanan_pembelian_detail' => $baris['id_pesanan_pembelian_detail'] ?? null,
                    'id_barang_satuan' => $barangSatuan->id_barang_satuan,
                    'id_lokasi_gudang' => $baris['id_lokasi_gudang'],
                    'nilai_konversi' => $barangSatuan->nilai_konversi,
                    'jumlah_diterima' => $baris['jumlah_diterima'],
                    'jumlah_dasar_diterima' => $jumlahDasar,
                    'jumlah_ditolak' => $baris['jumlah_ditolak'] ?? 0,
                    'harga_pokok' => $baris['harga_pokok'],
                    'nomor_lot' => $baris['nomor_lot'] ?? null,
                    'tanggal_produksi' => $baris['tanggal_produksi'] ?? null,
                    'tanggal_kedaluwarsa' => $baris['tanggal_kedaluwarsa'] ?? null,
                    'keterangan' => $baris['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PEMBELIAN', 'TAMBAH', 'penerimaan_barang', $id, 'Membuat draf penerimaan barang.');

        return back()->with('berhasil', 'Penerimaan barang berhasil dibuat sebagai draf.');
    }

    public function terimaPenerimaan(Request $request, int $id, LayananPembelian $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PENERIMAAN_BARANG_TERIMA');
        $idCabang = $this->idCabang($request);

        DB::transaction(function () use ($request, $id, $idCabang, $layanan): void {
            $penerimaan = $this->temukanTerkunci('penerimaan_barang', 'id_penerimaan_barang', $idCabang, $id);
            abort_unless($penerimaan->status_penerimaan === 'DRAF', 422, 'Penerimaan sudah diproses atau dibatalkan.');
            $detail = DB::table('penerimaan_barang_detail')->where('id_penerimaan_barang', $id)->whereNull('deleted_at')->lockForUpdate()->get();
            if ($detail->isEmpty()) {
                throw ValidationException::withMessages(['detail' => 'Penerimaan harus memiliki minimal satu detail.']);
            }

            foreach ($detail as $baris) {
                $layanan->catatPenerimaanStok($idCabang, $penerimaan, $baris, (int) $request->user()->id_pengguna);
                if ($baris->id_pesanan_pembelian_detail) {
                    $pesananDetail = DB::table('pesanan_pembelian_detail')->where('id_pesanan_pembelian_detail', $baris->id_pesanan_pembelian_detail)->lockForUpdate()->first();
                    if ($pesananDetail) {
                        $baru = round((float) $pesananDetail->jumlah_diterima + (float) $baris->jumlah_diterima, 3);
                        if ($baru > (float) $pesananDetail->jumlah + 0.0001) {
                            throw ValidationException::withMessages(['detail' => 'Jumlah diterima melebihi jumlah pesanan.']);
                        }
                        DB::table('pesanan_pembelian_detail')->where('id_pesanan_pembelian_detail', $baris->id_pesanan_pembelian_detail)->update([
                            'jumlah_diterima' => $baru,
                            'updated_at' => now(),
                            'updated_by' => $request->user()->id_pengguna,
                        ]);
                    }
                }
            }

            DB::table('penerimaan_barang')->where('id_penerimaan_barang', $id)->update([
                'status_penerimaan' => 'DITERIMA',
                'id_pengguna_penerima' => $request->user()->id_pengguna,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);

            if ($penerimaan->id_pesanan_pembelian) {
                $layanan->perbaruiStatusPesanan((int) $penerimaan->id_pesanan_pembelian, (int) $request->user()->id_pengguna);
            }
        });

        $audit->catat($request, 'PEMBELIAN', 'TERIMA', 'penerimaan_barang', $id, 'Menerima barang dan membentuk mutasi stok pembelian.');

        return back()->with('berhasil', 'Barang berhasil diterima dan stok telah diperbarui.');
    }

    public function batalkanPenerimaan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PENERIMAAN_BARANG_KELOLA', 'penerimaan_barang', 'id_penerimaan_barang', $id, 'status_penerimaan', ['DRAF'], 'DIBATALKAN', 'Penerimaan barang berhasil dibatalkan.');
    }

    public function simpanFaktur(Request $request, LayananPembelian $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'FAKTUR_PEMBELIAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'id_pemasok' => ['required', 'integer'],
            'id_pesanan_pembelian' => ['nullable', 'integer'],
            'id_penerimaan_barang' => ['nullable', 'integer'],
            'nomor_faktur_pemasok' => ['required', 'string', 'max:100'],
            'tanggal_faktur' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tanggal_faktur'],
            'cara_pembayaran' => ['required', Rule::in(['TUNAI', 'TEMPO'])],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'biaya_lain' => ['nullable', 'numeric', 'min:0'],
            'pembulatan' => ['nullable', 'numeric'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_penerimaan_barang_detail' => ['nullable', 'integer'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.id_tarif_pajak' => ['nullable', 'integer'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'detail.*.potongan_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);
        $layanan->pastikanPemasok((int) $data['id_pemasok']);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            $rincian = [];
            $totalKotor = $totalPotongan = $totalPajak = 0.0;
            foreach ($data['detail'] as $index => $baris) {
                $barangSatuan = $layanan->barangSatuan((int) $baris['id_barang_satuan']);
                $jumlahDasar = $layanan->jumlahDasar($barangSatuan, $baris['jumlah'], "detail.{$index}.jumlah");
                $pajakPersen = $layanan->persenPajak(isset($baris['id_tarif_pajak']) ? (int) $baris['id_tarif_pajak'] : null);
                $hitung = $layanan->hitungBaris((float) $baris['jumlah'], (float) $baris['harga_satuan'], (float) ($baris['potongan_persen'] ?? 0), $pajakPersen);
                $totalKotor += $hitung['kotor'];
                $totalPotongan += $hitung['potongan'];
                $totalPajak += $hitung['pajak'];
                $rincian[] = compact('barangSatuan', 'jumlahDasar', 'pajakPersen', 'hitung', 'baris');
            }

            $biayaPengiriman = (float) ($data['biaya_pengiriman'] ?? 0);
            $biayaLain = (float) ($data['biaya_lain'] ?? 0);
            $pembulatan = (float) ($data['pembulatan'] ?? 0);
            $totalBersih = round($totalKotor - $totalPotongan + $totalPajak + $biayaPengiriman + $biayaLain + $pembulatan, 2);
            $id = (int) DB::table('faktur_pembelian')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pemasok' => $data['id_pemasok'],
                'id_pesanan_pembelian' => $data['id_pesanan_pembelian'] ?? null,
                'id_penerimaan_barang' => $data['id_penerimaan_barang'] ?? null,
                'nomor_faktur_internal' => $layanan->nomorBerikutnya($idCabang, 'FAKTUR_PEMBELIAN', 'FP', $data['tanggal_faktur']),
                'nomor_faktur_pemasok' => $data['nomor_faktur_pemasok'],
                'tanggal_faktur' => $data['tanggal_faktur'],
                'tanggal_jatuh_tempo' => $data['tanggal_jatuh_tempo'] ?? null,
                'cara_pembayaran' => $data['cara_pembayaran'],
                'status_faktur' => 'DRAF',
                'total_kotor' => round($totalKotor, 2),
                'total_potongan' => round($totalPotongan, 2),
                'total_pajak' => round($totalPajak, 2),
                'biaya_pengiriman' => $biayaPengiriman,
                'biaya_lain' => $biayaLain,
                'pembulatan' => $pembulatan,
                'total_bersih' => $totalBersih,
                'total_dibayar' => 0,
                'sisa_hutang' => $data['cara_pembayaran'] === 'TEMPO' ? $totalBersih : 0,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                $baris = $item['baris'];
                DB::table('faktur_pembelian_detail')->insert([
                    'id_faktur_pembelian' => $id,
                    'id_penerimaan_barang_detail' => $baris['id_penerimaan_barang_detail'] ?? null,
                    'id_barang_satuan' => $item['barangSatuan']->id_barang_satuan,
                    'id_tarif_pajak' => $baris['id_tarif_pajak'] ?? null,
                    'nilai_konversi' => $item['barangSatuan']->nilai_konversi,
                    'jumlah' => $baris['jumlah'],
                    'jumlah_dasar' => $item['jumlahDasar'],
                    'harga_satuan' => $baris['harga_satuan'],
                    'potongan_persen' => $baris['potongan_persen'] ?? 0,
                    'potongan_nilai' => $item['hitung']['potongan'],
                    'pajak_persen' => $item['pajakPersen'],
                    'pajak_nilai' => $item['hitung']['pajak'],
                    'total_baris' => $item['hitung']['total'],
                    'keterangan' => $baris['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PEMBELIAN', 'TAMBAH', 'faktur_pembelian', $id, 'Membuat draf faktur pembelian.');

        return back()->with('berhasil', 'Faktur pembelian berhasil dibuat sebagai draf.');
    }

    public function setujuiFaktur(Request $request, int $id, LayananPembelian $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'FAKTUR_PEMBELIAN_SETUJUI');
        $idCabang = $this->idCabang($request);

        DB::transaction(function () use ($request, $id, $idCabang, $layanan): void {
            $faktur = $this->temukanTerkunci('faktur_pembelian', 'id_faktur_pembelian', $idCabang, $id);
            abort_unless($faktur->status_faktur === 'DRAF', 422, 'Faktur sudah diproses atau dibatalkan.');
            $detail = DB::table('faktur_pembelian_detail')->where('id_faktur_pembelian', $id)->whereNull('deleted_at')->lockForUpdate()->get();
            if ($detail->isEmpty()) {
                throw ValidationException::withMessages(['detail' => 'Faktur harus memiliki minimal satu detail.']);
            }

            foreach ($detail as $baris) {
                if (! $baris->id_penerimaan_barang_detail) {
                    continue;
                }
                $penerimaanDetail = DB::table('penerimaan_barang_detail')->where('id_penerimaan_barang_detail', $baris->id_penerimaan_barang_detail)->first();
                if (! $penerimaanDetail?->id_pesanan_pembelian_detail) {
                    continue;
                }
                $pesananDetail = DB::table('pesanan_pembelian_detail')->where('id_pesanan_pembelian_detail', $penerimaanDetail->id_pesanan_pembelian_detail)->lockForUpdate()->first();
                if ($pesananDetail) {
                    $baru = round((float) $pesananDetail->jumlah_difakturkan + (float) $baris->jumlah, 3);
                    if ($baru > (float) $pesananDetail->jumlah + 0.0001) {
                        throw ValidationException::withMessages(['detail' => 'Jumlah difakturkan melebihi jumlah pesanan.']);
                    }
                    DB::table('pesanan_pembelian_detail')->where('id_pesanan_pembelian_detail', $pesananDetail->id_pesanan_pembelian_detail)->update([
                        'jumlah_difakturkan' => $baru,
                        'updated_at' => now(),
                        'updated_by' => $request->user()->id_pengguna,
                    ]);
                }
            }

            $status = $faktur->cara_pembayaran === 'TUNAI' ? 'LUNAS' : 'DISETUJUI';
            DB::table('faktur_pembelian')->where('id_faktur_pembelian', $id)->update([
                'status_faktur' => $status,
                'total_dibayar' => $faktur->cara_pembayaran === 'TUNAI' ? $faktur->total_bersih : 0,
                'sisa_hutang' => $faktur->cara_pembayaran === 'TEMPO' ? $faktur->total_bersih : 0,
                'id_pengguna_penyetuju' => $request->user()->id_pengguna,
                'tanggal_disetujui' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);

            if ($faktur->cara_pembayaran === 'TEMPO') {
                DB::table('hutang_pemasok')->updateOrInsert(
                    ['id_faktur_pembelian' => $id],
                    [
                        'id_cabang' => $idCabang,
                        'id_pemasok' => $faktur->id_pemasok,
                        'tanggal_hutang' => $faktur->tanggal_faktur,
                        'tanggal_jatuh_tempo' => $faktur->tanggal_jatuh_tempo,
                        'nilai_awal' => $faktur->total_bersih,
                        'nilai_pembayaran' => 0,
                        'nilai_retur' => 0,
                        'nilai_penyesuaian' => 0,
                        'sisa_hutang' => $faktur->total_bersih,
                        'status_hutang' => 'BELUM_LUNAS',
                        'created_at' => now(),
                        'created_by' => $request->user()->id_pengguna,
                        'updated_at' => now(),
                        'updated_by' => $request->user()->id_pengguna,
                    ]
                );
            }

            if ($faktur->id_pesanan_pembelian) {
                $layanan->perbaruiStatusPesanan((int) $faktur->id_pesanan_pembelian, (int) $request->user()->id_pengguna);
            }
        });

        $audit->catat($request, 'PEMBELIAN', 'SETUJUI', 'faktur_pembelian', $id, 'Menyetujui faktur dan membentuk hutang bila pembayaran tempo.');

        return back()->with('berhasil', 'Faktur pembelian berhasil disetujui.');
    }

    public function batalkanFaktur(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'FAKTUR_PEMBELIAN_KELOLA', 'faktur_pembelian', 'id_faktur_pembelian', $id, 'status_faktur', ['DRAF'], 'DIBATALKAN', 'Faktur pembelian berhasil dibatalkan.');
    }

    public function simpanRetur(Request $request, LayananPembelian $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'RETUR_PEMBELIAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'id_pemasok' => ['required', 'integer'],
            'id_faktur_pembelian' => ['nullable', 'integer'],
            'id_gudang' => ['required', 'integer'],
            'tanggal_retur' => ['required', 'date'],
            'alasan_retur' => ['required', 'string'],
            'cara_pengembalian_dana' => ['required', Rule::in(['POTONG_HUTANG', 'TUNAI', 'TRANSFER', 'PENGGANTI_BARANG'])],
            'id_kas_bank' => ['nullable', 'integer'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_faktur_pembelian_detail' => ['nullable', 'integer'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.id_lokasi_gudang' => ['required', 'integer'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'detail.*.nomor_lot' => ['nullable', 'string', 'max:100'],
            'detail.*.tanggal_kedaluwarsa' => ['nullable', 'date'],
            'detail.*.kondisi_barang' => ['required', Rule::in(['BAIK', 'RUSAK', 'SALAH_KIRIM', 'KEDALUWARSA', 'LAINNYA'])],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);
        $layanan->pastikanPemasok((int) $data['id_pemasok']);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            $rincian = [];
            $total = 0.0;
            foreach ($data['detail'] as $index => $baris) {
                $barangSatuan = $layanan->barangSatuan((int) $baris['id_barang_satuan']);
                $layanan->pastikanGudangLokasi($idCabang, (int) $data['id_gudang'], (int) $baris['id_lokasi_gudang']);
                $jumlahDasar = $layanan->jumlahDasar($barangSatuan, $baris['jumlah'], "detail.{$index}.jumlah");
                $totalBaris = round((float) $baris['jumlah'] * (float) $baris['harga_satuan'], 2);
                $total += $totalBaris;
                $rincian[] = compact('barangSatuan', 'jumlahDasar', 'totalBaris', 'baris');
            }

            $id = (int) DB::table('retur_pembelian')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pemasok' => $data['id_pemasok'],
                'id_faktur_pembelian' => $data['id_faktur_pembelian'] ?? null,
                'id_gudang' => $data['id_gudang'],
                'nomor_retur' => $layanan->nomorBerikutnya($idCabang, 'RETUR_PEMBELIAN', 'RP', $data['tanggal_retur']),
                'tanggal_retur' => $data['tanggal_retur'],
                'alasan_retur' => $data['alasan_retur'],
                'cara_pengembalian_dana' => $data['cara_pengembalian_dana'],
                'id_kas_bank' => $data['id_kas_bank'] ?? null,
                'status_retur' => 'DRAF',
                'total_retur' => round($total, 2),
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                $baris = $item['baris'];
                DB::table('retur_pembelian_detail')->insert([
                    'id_retur_pembelian' => $id,
                    'id_faktur_pembelian_detail' => $baris['id_faktur_pembelian_detail'] ?? null,
                    'id_barang_satuan' => $item['barangSatuan']->id_barang_satuan,
                    'id_lokasi_gudang' => $baris['id_lokasi_gudang'],
                    'nilai_konversi' => $item['barangSatuan']->nilai_konversi,
                    'jumlah' => $baris['jumlah'],
                    'jumlah_dasar' => $item['jumlahDasar'],
                    'harga_satuan' => $baris['harga_satuan'],
                    'total_baris' => $item['totalBaris'],
                    'nomor_lot' => $baris['nomor_lot'] ?? null,
                    'tanggal_kedaluwarsa' => $baris['tanggal_kedaluwarsa'] ?? null,
                    'kondisi_barang' => $baris['kondisi_barang'],
                    'keterangan' => $baris['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PEMBELIAN', 'TAMBAH', 'retur_pembelian', $id, 'Membuat draf retur pembelian.');

        return back()->with('berhasil', 'Retur pembelian berhasil dibuat sebagai draf.');
    }

    public function setujuiRetur(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'RETUR_PEMBELIAN_SETUJUI', 'retur_pembelian', 'id_retur_pembelian', $id, 'status_retur', ['DRAF'], 'DISETUJUI', 'Retur pembelian berhasil disetujui.', true);
    }

    public function kirimRetur(Request $request, int $id, LayananPembelian $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'RETUR_PEMBELIAN_KIRIM');
        $idCabang = $this->idCabang($request);

        DB::transaction(function () use ($request, $id, $idCabang, $layanan): void {
            $retur = $this->temukanTerkunci('retur_pembelian', 'id_retur_pembelian', $idCabang, $id);
            abort_unless($retur->status_retur === 'DISETUJUI', 422, 'Hanya retur yang disetujui dapat dikirim.');
            $detail = DB::table('retur_pembelian_detail')->where('id_retur_pembelian', $id)->whereNull('deleted_at')->lockForUpdate()->get();
            if ($detail->isEmpty()) {
                throw ValidationException::withMessages(['detail' => 'Retur harus memiliki minimal satu detail.']);
            }

            foreach ($detail as $baris) {
                $layanan->catatReturStok($idCabang, $retur, $baris, (int) $request->user()->id_pengguna);
            }

            if ($retur->cara_pengembalian_dana === 'POTONG_HUTANG' && $retur->id_faktur_pembelian) {
                $hutang = DB::table('hutang_pemasok')->where('id_faktur_pembelian', $retur->id_faktur_pembelian)->lockForUpdate()->first();
                if ($hutang) {
                    DB::table('hutang_pemasok')->where('id_hutang_pemasok', $hutang->id_hutang_pemasok)->update([
                        'nilai_retur' => round((float) $hutang->nilai_retur + (float) $retur->total_retur, 2),
                        'updated_at' => now(),
                        'updated_by' => $request->user()->id_pengguna,
                    ]);
                    $layanan->perbaruiHutang((int) $hutang->id_hutang_pemasok, (int) $request->user()->id_pengguna);
                }
            }

            DB::table('retur_pembelian')->where('id_retur_pembelian', $id)->update([
                'status_retur' => 'DIKIRIM',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PEMBELIAN', 'KIRIM', 'retur_pembelian', $id, 'Mengirim retur, mengurangi stok, dan memperbarui hutang bila diperlukan.');

        return back()->with('berhasil', 'Retur pembelian berhasil dikirim dan stok telah dikurangi.');
    }

    public function selesaikanRetur(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'RETUR_PEMBELIAN_KELOLA', 'retur_pembelian', 'id_retur_pembelian', $id, 'status_retur', ['DIKIRIM'], 'SELESAI', 'Retur pembelian berhasil diselesaikan.');
    }

    public function batalkanRetur(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'RETUR_PEMBELIAN_KELOLA', 'retur_pembelian', 'id_retur_pembelian', $id, 'status_retur', ['DRAF'], 'DIBATALKAN', 'Retur pembelian berhasil dibatalkan.');
    }

    private function pilihan(int $idCabang): array
    {
        return [
            'pemasokPilihan' => DB::table('pemasok')->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_pemasok')->get(),
            'barangSatuanPilihan' => DB::table('barang_satuan as bs')->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')->join('satuan as s', 's.id_satuan', '=', 'bs.id_satuan')->where('bs.status_aktif', 1)->where('b.status_aktif', 1)->where('b.bisa_dibeli', 1)->whereNull('bs.deleted_at')->whereNull('b.deleted_at')->select('bs.*', 'b.kode_barang', 'b.nama_barang', 's.kode_satuan', 's.jumlah_desimal')->orderBy('b.nama_barang')->get(),
            'gudangPilihan' => DB::table('gudang')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_gudang')->get(),
            'lokasiPilihan' => DB::table('lokasi_gudang as l')->join('gudang as g', 'g.id_gudang', '=', 'l.id_gudang')->where('g.id_cabang', $idCabang)->where('l.status_aktif', 1)->whereNull('l.deleted_at')->select('l.*')->orderBy('l.nama_lokasi')->get(),
            'pajakPilihan' => DB::table('tarif_pajak')->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_tarif_pajak')->get(),
            'permintaanDetailPilihan' => DB::table('permintaan_pembelian_detail as d')->join('permintaan_pembelian as h', 'h.id_permintaan_pembelian', '=', 'd.id_permintaan_pembelian')->join('barang_satuan as bs', 'bs.id_barang_satuan', '=', 'd.id_barang_satuan')->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')->where('h.id_cabang', $idCabang)->whereIn('h.status_permintaan', ['DISETUJUI', 'DIPROSES'])->whereNull('h.deleted_at')->whereNull('d.deleted_at')->select('d.*', 'h.nomor_permintaan', 'b.nama_barang')->orderByDesc('d.id_permintaan_pembelian_detail')->get(),
            'pesananPilihan' => DB::table('pesanan_pembelian as h')->join('pemasok as p', 'p.id_pemasok', '=', 'h.id_pemasok')->where('h.id_cabang', $idCabang)->whereIn('h.status_pesanan', ['DISETUJUI', 'DIKIRIM_PEMASOK', 'DITERIMA_SEBAGIAN', 'DITERIMA'])->whereNull('h.deleted_at')->select('h.*', 'p.nama_pemasok')->orderByDesc('h.id_pesanan_pembelian')->get(),
            'pesananDetailPilihan' => DB::table('pesanan_pembelian_detail as d')->join('pesanan_pembelian as h', 'h.id_pesanan_pembelian', '=', 'd.id_pesanan_pembelian')->join('barang_satuan as bs', 'bs.id_barang_satuan', '=', 'd.id_barang_satuan')->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')->where('h.id_cabang', $idCabang)->whereNotIn('h.status_pesanan', ['DRAF', 'DIAJUKAN', 'DIBATALKAN', 'SELESAI'])->whereNull('d.deleted_at')->select('d.*', 'h.nomor_pesanan', 'b.nama_barang')->orderByDesc('d.id_pesanan_pembelian_detail')->get(),
            'penerimaanPilihan' => DB::table('penerimaan_barang as h')->join('pemasok as p', 'p.id_pemasok', '=', 'h.id_pemasok')->where('h.id_cabang', $idCabang)->where('h.status_penerimaan', 'DITERIMA')->whereNull('h.deleted_at')->select('h.*', 'p.nama_pemasok')->orderByDesc('h.id_penerimaan_barang')->get(),
            'penerimaanDetailPilihan' => DB::table('penerimaan_barang_detail as d')->join('penerimaan_barang as h', 'h.id_penerimaan_barang', '=', 'd.id_penerimaan_barang')->join('barang_satuan as bs', 'bs.id_barang_satuan', '=', 'd.id_barang_satuan')->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')->where('h.id_cabang', $idCabang)->where('h.status_penerimaan', 'DITERIMA')->whereNull('d.deleted_at')->select('d.*', 'h.nomor_penerimaan', 'b.nama_barang')->orderByDesc('d.id_penerimaan_barang_detail')->get(),
            'fakturPilihan' => DB::table('faktur_pembelian as h')->join('pemasok as p', 'p.id_pemasok', '=', 'h.id_pemasok')->where('h.id_cabang', $idCabang)->whereNotIn('h.status_faktur', ['DIBATALKAN'])->whereNull('h.deleted_at')->select('h.*', 'p.nama_pemasok')->orderByDesc('h.id_faktur_pembelian')->get(),
            'fakturDetailPilihan' => DB::table('faktur_pembelian_detail as d')->join('faktur_pembelian as h', 'h.id_faktur_pembelian', '=', 'd.id_faktur_pembelian')->join('barang_satuan as bs', 'bs.id_barang_satuan', '=', 'd.id_barang_satuan')->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')->where('h.id_cabang', $idCabang)->whereNotIn('h.status_faktur', ['DRAF', 'DIBATALKAN'])->whereNull('d.deleted_at')->select('d.*', 'h.nomor_faktur_internal', 'b.nama_barang')->orderByDesc('d.id_faktur_pembelian_detail')->get(),
            'kasBankPilihan' => DB::table('kas_bank')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_kas_bank')->get(),
        ];
    }

    private function ubahStatusSederhana(
        Request $request,
        AuditAktivitas $audit,
        string $izin,
        string $tabel,
        string $kunci,
        int $id,
        string $kolomStatus,
        array $statusAsal,
        string $statusTujuan,
        string $pesan,
        bool $catatPenyetuju = false,
    ): RedirectResponse {
        $this->pastikanAkses($request, $izin);
        $idCabang = $this->idCabang($request);
        DB::transaction(function () use ($request, $tabel, $kunci, $id, $kolomStatus, $statusAsal, $statusTujuan, $idCabang, $catatPenyetuju): void {
            $item = $this->temukanTerkunci($tabel, $kunci, $idCabang, $id);
            abort_unless(in_array($item->{$kolomStatus}, $statusAsal, true), 422, 'Status dokumen tidak memungkinkan aksi ini.');
            $perubahan = [
                $kolomStatus => $statusTujuan,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ];
            if ($catatPenyetuju) {
                $perubahan['id_pengguna_penyetuju'] = $request->user()->id_pengguna;
                $perubahan['tanggal_disetujui'] = now();
            }
            DB::table($tabel)->where($kunci, $id)->update($perubahan);
        });
        $audit->catat($request, 'PEMBELIAN', $statusTujuan, $tabel, $id, $pesan);

        return back()->with('berhasil', $pesan);
    }

    private function temukanTerkunci(string $tabel, string $kunci, int $idCabang, int $id): object
    {
        $item = DB::table($tabel)->where($kunci, $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
        abort_if(! $item, 404);

        return $item;
    }

    private function idCabang(Request $request): int
    {
        return (int) $request->session()->get('id_cabang_aktif');
    }

    private function pastikanAkses(Request $request, string $izin): void
    {
        abort_unless($request->user()?->memilikiHakAkses($izin, $this->idCabang($request)), 403);
    }
}
