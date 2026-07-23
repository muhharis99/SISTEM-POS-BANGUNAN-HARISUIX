<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use App\Services\LayananPenjualan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenjualanController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'PENJUALAN_LIHAT');
        $idCabang = $this->idCabang($request);
        $pencarian = trim((string) $request->query('pencarian'));

        $penawaran = DB::table('penawaran_penjualan as h')
            ->leftJoin('pelanggan as p', 'p.id_pelanggan', '=', 'h.id_pelanggan')
            ->where('h.id_cabang', $idCabang)
            ->whereNull('h.deleted_at')
            ->when($pencarian !== '', fn ($q) => $q->where(function ($s) use ($pencarian): void {
                $s->where('h.nomor_penawaran', 'like', "%{$pencarian}%")
                    ->orWhere('p.nama_pelanggan', 'like', "%{$pencarian}%");
            }))
            ->select('h.*', 'p.nama_pelanggan')
            ->orderByDesc('h.id_penawaran_penjualan')
            ->limit(25)
            ->get();

        $pesanan = DB::table('pesanan_penjualan as h')
            ->join('pelanggan as p', 'p.id_pelanggan', '=', 'h.id_pelanggan')
            ->where('h.id_cabang', $idCabang)
            ->whereNull('h.deleted_at')
            ->when($pencarian !== '', fn ($q) => $q->where(function ($s) use ($pencarian): void {
                $s->where('h.nomor_pesanan', 'like', "%{$pencarian}%")
                    ->orWhere('p.nama_pelanggan', 'like', "%{$pencarian}%");
            }))
            ->select('h.*', 'p.nama_pelanggan')
            ->orderByDesc('h.id_pesanan_penjualan')
            ->limit(25)
            ->get();

        $penjualan = DB::table('penjualan as h')
            ->leftJoin('pelanggan as p', 'p.id_pelanggan', '=', 'h.id_pelanggan')
            ->join('gudang as g', 'g.id_gudang', '=', 'h.id_gudang')
            ->where('h.id_cabang', $idCabang)
            ->whereNull('h.deleted_at')
            ->when($pencarian !== '', fn ($q) => $q->where(function ($s) use ($pencarian): void {
                $s->where('h.nomor_penjualan', 'like', "%{$pencarian}%")
                    ->orWhere('p.nama_pelanggan', 'like', "%{$pencarian}%");
            }))
            ->select('h.*', 'p.nama_pelanggan', 'g.nama_gudang')
            ->orderByDesc('h.id_penjualan')
            ->limit(25)
            ->get();

        $pengiriman = DB::table('pengiriman as h')
            ->leftJoin('penjualan as j', 'j.id_penjualan', '=', 'h.id_penjualan')
            ->leftJoin('armada as a', 'a.id_armada', '=', 'h.id_armada')
            ->where('h.id_cabang', $idCabang)
            ->whereNull('h.deleted_at')
            ->select('h.*', 'j.nomor_penjualan', 'a.nomor_polisi')
            ->orderByDesc('h.id_pengiriman')
            ->limit(25)
            ->get();

        $retur = DB::table('retur_penjualan as h')
            ->join('penjualan as j', 'j.id_penjualan', '=', 'h.id_penjualan')
            ->leftJoin('pelanggan as p', 'p.id_pelanggan', '=', 'h.id_pelanggan')
            ->where('h.id_cabang', $idCabang)
            ->whereNull('h.deleted_at')
            ->select('h.*', 'j.nomor_penjualan', 'p.nama_pelanggan')
            ->orderByDesc('h.id_retur_penjualan')
            ->limit(25)
            ->get();

        $ringkasan = [
            'penawaran_aktif' => DB::table('penawaran_penjualan')->where('id_cabang', $idCabang)->whereIn('status_penawaran', ['DRAF', 'DIKIRIM', 'DISETUJUI_PELANGGAN'])->whereNull('deleted_at')->count(),
            'pesanan_aktif' => DB::table('pesanan_penjualan')->where('id_cabang', $idCabang)->whereNotIn('status_pesanan', ['SELESAI', 'DIBATALKAN'])->whereNull('deleted_at')->count(),
            'nilai_penjualan_bulan_ini' => (float) DB::table('penjualan')->where('id_cabang', $idCabang)->whereNotIn('status_penjualan', ['DRAF', 'DIBATALKAN'])->whereBetween('tanggal_penjualan', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_bersih'),
            'pengiriman_aktif' => DB::table('pengiriman')->where('id_cabang', $idCabang)->whereIn('status_pengiriman', ['DRAF', 'DIJADWALKAN', 'DALAM_PERJALANAN'])->whereNull('deleted_at')->count(),
        ];

        return view('penjualan.index', array_merge(
            compact('penawaran', 'pesanan', 'penjualan', 'pengiriman', 'retur', 'ringkasan', 'pencarian'),
            $this->pilihan($idCabang)
        ));
    }

    public function simpanPenawaran(Request $request, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PENAWARAN_PENJUALAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'id_pelanggan' => ['nullable', 'integer'],
            'tanggal_penawaran' => ['required', 'date'],
            'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:tanggal_penawaran'],
            'alamat_penagihan' => ['nullable', 'string'],
            'alamat_pengiriman' => ['nullable', 'string'],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'syarat_ketentuan' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.id_tarif_pajak' => ['nullable', 'integer'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'detail.*.potongan_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);
        $layanan->pelanggan(isset($data['id_pelanggan']) ? (int) $data['id_pelanggan'] : null, false);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            [$rincian, $total] = $this->rincianHarga($data['detail'], $layanan);
            $biayaPengiriman = (float) ($data['biaya_pengiriman'] ?? 0);
            $id = (int) DB::table('penawaran_penjualan')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pelanggan' => $data['id_pelanggan'] ?? null,
                'nomor_penawaran' => $layanan->nomorBerikutnya($idCabang, 'PENAWARAN_PENJUALAN', 'QT', $data['tanggal_penawaran']),
                'tanggal_penawaran' => $data['tanggal_penawaran'],
                'berlaku_sampai' => $data['berlaku_sampai'] ?? null,
                'status_penawaran' => 'DRAF',
                'alamat_penagihan' => $data['alamat_penagihan'] ?? null,
                'alamat_pengiriman' => $data['alamat_pengiriman'] ?? null,
                'total_kotor' => $total['kotor'],
                'total_potongan' => $total['potongan'],
                'total_pajak' => $total['pajak'],
                'biaya_pengiriman' => $biayaPengiriman,
                'total_bersih' => round($total['bersih'] + $biayaPengiriman, 2),
                'syarat_ketentuan' => $data['syarat_ketentuan'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                DB::table('penawaran_penjualan_detail')->insert([
                    'id_penawaran_penjualan' => $id,
                    'id_barang_satuan' => $item['barangSatuan']->id_barang_satuan,
                    'id_tarif_pajak' => $item['baris']['id_tarif_pajak'] ?? null,
                    'nilai_konversi' => $item['barangSatuan']->nilai_konversi,
                    'jumlah' => $item['baris']['jumlah'],
                    'jumlah_dasar' => $item['jumlahDasar'],
                    'harga_satuan' => $item['baris']['harga_satuan'],
                    'potongan_persen' => $item['baris']['potongan_persen'] ?? 0,
                    'potongan_nilai' => $item['hitung']['potongan'],
                    'pajak_persen' => $item['pajakPersen'],
                    'pajak_nilai' => $item['hitung']['pajak'],
                    'total_baris' => $item['hitung']['total'],
                    'keterangan' => $item['baris']['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PENJUALAN', 'TAMBAH', 'penawaran_penjualan', $id, 'Membuat draf penawaran penjualan.');

        return back()->with('berhasil', 'Penawaran berhasil dibuat sebagai draf.');
    }

    public function kirimPenawaran(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PENAWARAN_PENJUALAN_KELOLA', 'penawaran_penjualan', 'id_penawaran_penjualan', $id, 'status_penawaran', ['DRAF'], 'DIKIRIM', 'Penawaran berhasil ditandai telah dikirim.');
    }

    public function terimaPenawaran(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PENAWARAN_PENJUALAN_KELOLA', 'penawaran_penjualan', 'id_penawaran_penjualan', $id, 'status_penawaran', ['DIKIRIM'], 'DISETUJUI_PELANGGAN', 'Penawaran diterima pelanggan.');
    }

    public function tolakPenawaran(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PENAWARAN_PENJUALAN_KELOLA', 'penawaran_penjualan', 'id_penawaran_penjualan', $id, 'status_penawaran', ['DIKIRIM'], 'DITOLAK', 'Penawaran ditolak pelanggan.');
    }

    public function kedaluwarsaPenawaran(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PENAWARAN_PENJUALAN_KELOLA', 'penawaran_penjualan', 'id_penawaran_penjualan', $id, 'status_penawaran', ['DRAF', 'DIKIRIM'], 'KEDALUWARSA', 'Penawaran ditandai kedaluwarsa.');
    }

    public function batalkanPenawaran(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PENAWARAN_PENJUALAN_KELOLA', 'penawaran_penjualan', 'id_penawaran_penjualan', $id, 'status_penawaran', ['DRAF', 'DIKIRIM'], 'DIBATALKAN', 'Penawaran berhasil dibatalkan.');
    }

    public function jadikanPesanan(Request $request, int $id, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PESANAN_PENJUALAN_KELOLA');
        $idCabang = $this->idCabang($request);

        $idPesanan = DB::transaction(function () use ($request, $id, $idCabang, $layanan): int {
            $penawaran = DB::table('penawaran_penjualan')
                ->where('id_penawaran_penjualan', $id)
                ->where('id_cabang', $idCabang)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();
            if (! $penawaran) {
                abort(404);
            }
            if ($penawaran->status_penawaran !== 'DISETUJUI_PELANGGAN') {
                throw ValidationException::withMessages(['status_penawaran' => 'Hanya penawaran yang diterima pelanggan yang dapat menjadi pesanan.']);
            }
            if (! $penawaran->id_pelanggan) {
                throw ValidationException::withMessages(['id_pelanggan' => 'Pelanggan wajib ditentukan sebelum penawaran menjadi pesanan.']);
            }
            if (DB::table('pesanan_penjualan')->where('id_penawaran_penjualan', $id)->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['status_penawaran' => 'Penawaran ini sudah pernah menjadi pesanan.']);
            }

            $pelanggan = $layanan->pelanggan((int) $penawaran->id_pelanggan);
            $idPesanan = (int) DB::table('pesanan_penjualan')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'id_penawaran_penjualan' => $id,
                'nomor_pesanan' => $layanan->nomorBerikutnya($idCabang, 'PESANAN_PENJUALAN', 'SO'),
                'tanggal_pesanan' => now()->toDateString(),
                'sumber_pesanan' => 'TOKO',
                'status_pesanan' => 'DRAF',
                'cara_pembayaran' => 'TUNAI',
                'lama_jatuh_tempo' => $pelanggan->lama_jatuh_tempo,
                'alamat_penagihan' => $penawaran->alamat_penagihan,
                'alamat_pengiriman' => $penawaran->alamat_pengiriman,
                'total_kotor' => $penawaran->total_kotor,
                'total_potongan' => $penawaran->total_potongan,
                'total_pajak' => $penawaran->total_pajak,
                'biaya_pengiriman' => $penawaran->biaya_pengiriman,
                'total_bersih' => $penawaran->total_bersih,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            $detail = DB::table('penawaran_penjualan_detail')->where('id_penawaran_penjualan', $id)->whereNull('deleted_at')->get();
            foreach ($detail as $item) {
                DB::table('pesanan_penjualan_detail')->insert([
                    'id_pesanan_penjualan' => $idPesanan,
                    'id_penawaran_penjualan_detail' => $item->id_penawaran_penjualan_detail,
                    'id_barang_satuan' => $item->id_barang_satuan,
                    'id_tarif_pajak' => $item->id_tarif_pajak,
                    'nilai_konversi' => $item->nilai_konversi,
                    'jumlah' => $item->jumlah,
                    'jumlah_dasar' => $item->jumlah_dasar,
                    'harga_satuan' => $item->harga_satuan,
                    'potongan_persen' => $item->potongan_persen,
                    'potongan_nilai' => $item->potongan_nilai,
                    'pajak_persen' => $item->pajak_persen,
                    'pajak_nilai' => $item->pajak_nilai,
                    'total_baris' => $item->total_baris,
                    'jumlah_dikirim' => 0,
                    'jumlah_difakturkan' => 0,
                    'keterangan' => $item->keterangan,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            DB::table('penawaran_penjualan')->where('id_penawaran_penjualan', $id)->update([
                'status_penawaran' => 'MENJADI_PESANAN',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);

            return $idPesanan;
        });

        $audit->catat($request, 'PENJUALAN', 'TAMBAH', 'pesanan_penjualan', $idPesanan, 'Mengubah penawaran menjadi pesanan penjualan.');

        return back()->with('berhasil', 'Penawaran berhasil diubah menjadi pesanan penjualan.');
    }

    public function simpanPesanan(Request $request, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PESANAN_PENJUALAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'id_pelanggan' => ['required', 'integer'],
            'id_daftar_harga' => ['nullable', 'integer'],
            'nomor_pesanan_pelanggan' => ['nullable', 'string', 'max:100'],
            'tanggal_pesanan' => ['required', 'date'],
            'tanggal_rencana_pengiriman' => ['nullable', 'date', 'after_or_equal:tanggal_pesanan'],
            'sumber_pesanan' => ['required', Rule::in(['TOKO', 'TELEPON', 'WHATSAPP', 'SUREL', 'WEBSITE', 'TENAGA_PENJUAL', 'LAINNYA'])],
            'cara_pembayaran' => ['required', Rule::in(['TUNAI', 'TEMPO'])],
            'lama_jatuh_tempo' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'alamat_penagihan' => ['nullable', 'string'],
            'alamat_pengiriman' => ['nullable', 'string'],
            'nama_penerima' => ['nullable', 'string', 'max:150'],
            'telepon_penerima' => ['nullable', 'string', 'max:30'],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'biaya_lain' => ['nullable', 'numeric', 'min:0'],
            'uang_muka' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.id_tarif_pajak' => ['nullable', 'integer'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'detail.*.potongan_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);
        $pelanggan = $layanan->pelanggan((int) $data['id_pelanggan']);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan, $pelanggan): int {
            [$rincian, $total] = $this->rincianHarga($data['detail'], $layanan);
            $biayaPengiriman = (float) ($data['biaya_pengiriman'] ?? 0);
            $biayaLain = (float) ($data['biaya_lain'] ?? 0);
            $id = (int) DB::table('pesanan_penjualan')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'id_daftar_harga' => $data['id_daftar_harga'] ?? null,
                'nomor_pesanan' => $layanan->nomorBerikutnya($idCabang, 'PESANAN_PENJUALAN', 'SO', $data['tanggal_pesanan']),
                'nomor_pesanan_pelanggan' => $data['nomor_pesanan_pelanggan'] ?? null,
                'tanggal_pesanan' => $data['tanggal_pesanan'],
                'tanggal_rencana_pengiriman' => $data['tanggal_rencana_pengiriman'] ?? null,
                'sumber_pesanan' => $data['sumber_pesanan'],
                'status_pesanan' => 'DRAF',
                'cara_pembayaran' => $data['cara_pembayaran'],
                'lama_jatuh_tempo' => $data['lama_jatuh_tempo'] ?? $pelanggan->lama_jatuh_tempo,
                'alamat_penagihan' => $data['alamat_penagihan'] ?? $pelanggan->alamat_utama,
                'alamat_pengiriman' => $data['alamat_pengiriman'] ?? $pelanggan->alamat_utama,
                'nama_penerima' => $data['nama_penerima'] ?? $pelanggan->nama_pelanggan,
                'telepon_penerima' => $data['telepon_penerima'] ?? $pelanggan->telepon,
                'total_kotor' => $total['kotor'],
                'total_potongan' => $total['potongan'],
                'total_pajak' => $total['pajak'],
                'biaya_pengiriman' => $biayaPengiriman,
                'biaya_lain' => $biayaLain,
                'total_bersih' => round($total['bersih'] + $biayaPengiriman + $biayaLain, 2),
                'uang_muka' => $data['uang_muka'] ?? 0,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                DB::table('pesanan_penjualan_detail')->insert([
                    'id_pesanan_penjualan' => $id,
                    'id_barang_satuan' => $item['barangSatuan']->id_barang_satuan,
                    'id_tarif_pajak' => $item['baris']['id_tarif_pajak'] ?? null,
                    'nilai_konversi' => $item['barangSatuan']->nilai_konversi,
                    'jumlah' => $item['baris']['jumlah'],
                    'jumlah_dasar' => $item['jumlahDasar'],
                    'harga_satuan' => $item['baris']['harga_satuan'],
                    'potongan_persen' => $item['baris']['potongan_persen'] ?? 0,
                    'potongan_nilai' => $item['hitung']['potongan'],
                    'pajak_persen' => $item['pajakPersen'],
                    'pajak_nilai' => $item['hitung']['pajak'],
                    'total_baris' => $item['hitung']['total'],
                    'jumlah_dikirim' => 0,
                    'jumlah_difakturkan' => 0,
                    'keterangan' => $item['baris']['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PENJUALAN', 'TAMBAH', 'pesanan_penjualan', $id, 'Membuat draf pesanan penjualan.');

        return back()->with('berhasil', 'Pesanan penjualan berhasil dibuat sebagai draf.');
    }

    public function setujuiPesanan(Request $request, int $id, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PESANAN_PENJUALAN_SETUJUI');
        $idCabang = $this->idCabang($request);

        DB::transaction(function () use ($request, $id, $idCabang, $layanan): void {
            $pesanan = DB::table('pesanan_penjualan')->where('id_pesanan_penjualan', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $pesanan) {
                abort(404);
            }
            if ($pesanan->status_pesanan !== 'DRAF') {
                throw ValidationException::withMessages(['status_pesanan' => 'Hanya pesanan berstatus DRAF yang dapat disetujui.']);
            }
            if ($pesanan->cara_pembayaran === 'TEMPO') {
                $layanan->pastikanBatasKredit((int) $pesanan->id_pelanggan, max(0, (float) $pesanan->total_bersih - (float) $pesanan->uang_muka));
            }

            DB::table('pesanan_penjualan')->where('id_pesanan_penjualan', $id)->update([
                'status_pesanan' => 'DISETUJUI',
                'id_pengguna_penyetuju' => $request->user()->id_pengguna,
                'tanggal_disetujui' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PENJUALAN', 'SETUJUI', 'pesanan_penjualan', $id, 'Menyetujui pesanan penjualan.');

        return back()->with('berhasil', 'Pesanan penjualan berhasil disetujui.');
    }

    public function batalkanPesanan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PESANAN_PENJUALAN_KELOLA', 'pesanan_penjualan', 'id_pesanan_penjualan', $id, 'status_pesanan', ['DRAF', 'DISETUJUI'], 'DIBATALKAN', 'Pesanan penjualan berhasil dibatalkan.');
    }

    public function simpanPenjualan(Request $request, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'TRANSAKSI_PENJUALAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'id_gudang' => ['required', 'integer'],
            'id_pelanggan' => ['nullable', 'integer'],
            'id_pesanan_penjualan' => ['nullable', 'integer'],
            'id_daftar_harga' => ['nullable', 'integer'],
            'id_kas_bank' => ['nullable', 'integer'],
            'id_metode_pembayaran' => ['nullable', 'integer'],
            'tanggal_penjualan' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tanggal_penjualan'],
            'jenis_penjualan' => ['required', Rule::in(['TUNAI', 'TEMPO'])],
            'status_pengiriman' => ['nullable', Rule::in(['BELUM_DIKIRIM', 'DIAMBIL_SENDIRI'])],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'biaya_lain' => ['nullable', 'numeric', 'min:0'],
            'pembulatan' => ['nullable', 'numeric'],
            'total_dibayar' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_pesanan_penjualan_detail' => ['nullable', 'integer'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.id_lokasi_gudang' => ['required', 'integer'],
            'detail.*.id_tarif_pajak' => ['nullable', 'integer'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'detail.*.potongan_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'detail.*.nomor_lot' => ['nullable', 'string', 'max:100'],
            'detail.*.tanggal_kedaluwarsa' => ['nullable', 'date'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);
        if ($data['jenis_penjualan'] === 'TEMPO' && empty($data['id_pelanggan'])) {
            throw ValidationException::withMessages(['id_pelanggan' => 'Penjualan tempo wajib memiliki pelanggan.']);
        }
        $pelanggan = $layanan->pelanggan(isset($data['id_pelanggan']) ? (int) $data['id_pelanggan'] : null, false);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan, $pelanggan): int {
            $gudangValid = DB::table('gudang')->where('id_gudang', $data['id_gudang'])->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
            if (! $gudangValid) {
                throw ValidationException::withMessages(['id_gudang' => 'Gudang tidak valid untuk cabang aktif.']);
            }

            [$rincian, $total] = $this->rincianHarga($data['detail'], $layanan, (int) $data['id_gudang'], $idCabang);
            $biayaPengiriman = (float) ($data['biaya_pengiriman'] ?? 0);
            $biayaLain = (float) ($data['biaya_lain'] ?? 0);
            $pembulatan = (float) ($data['pembulatan'] ?? 0);
            $totalBersih = round($total['bersih'] + $biayaPengiriman + $biayaLain + $pembulatan, 2);
            $id = (int) DB::table('penjualan')->insertGetId([
                'id_cabang' => $idCabang,
                'id_gudang' => $data['id_gudang'],
                'id_pelanggan' => $pelanggan?->id_pelanggan,
                'id_pesanan_penjualan' => $data['id_pesanan_penjualan'] ?? null,
                'id_daftar_harga' => $data['id_daftar_harga'] ?? null,
                'id_kas_bank' => $data['id_kas_bank'] ?? null,
                'id_metode_pembayaran' => $data['id_metode_pembayaran'] ?? null,
                'nomor_penjualan' => $layanan->nomorBerikutnya($idCabang, 'PENJUALAN', 'INV', $data['tanggal_penjualan']),
                'tanggal_penjualan' => $data['tanggal_penjualan'],
                'tanggal_jatuh_tempo' => $data['tanggal_jatuh_tempo'] ?? null,
                'jenis_penjualan' => $data['jenis_penjualan'],
                'status_penjualan' => 'DRAF',
                'status_pengiriman' => $data['status_pengiriman'] ?? 'BELUM_DIKIRIM',
                'total_kotor' => $total['kotor'],
                'total_potongan' => $total['potongan'],
                'total_pajak' => $total['pajak'],
                'biaya_pengiriman' => $biayaPengiriman,
                'biaya_lain' => $biayaLain,
                'pembulatan' => $pembulatan,
                'total_bersih' => $totalBersih,
                'total_dibayar' => $data['total_dibayar'] ?? 0,
                'uang_kembali' => 0,
                'sisa_piutang' => $data['jenis_penjualan'] === 'TEMPO' ? $totalBersih : 0,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                DB::table('penjualan_detail')->insert([
                    'id_penjualan' => $id,
                    'id_pesanan_penjualan_detail' => $item['baris']['id_pesanan_penjualan_detail'] ?? null,
                    'id_barang_satuan' => $item['barangSatuan']->id_barang_satuan,
                    'id_lokasi_gudang' => $item['baris']['id_lokasi_gudang'],
                    'id_tarif_pajak' => $item['baris']['id_tarif_pajak'] ?? null,
                    'nilai_konversi' => $item['barangSatuan']->nilai_konversi,
                    'jumlah' => $item['baris']['jumlah'],
                    'jumlah_dasar' => $item['jumlahDasar'],
                    'harga_satuan' => $item['baris']['harga_satuan'],
                    'potongan_persen' => $item['baris']['potongan_persen'] ?? 0,
                    'potongan_nilai' => $item['hitung']['potongan'],
                    'pajak_persen' => $item['pajakPersen'],
                    'pajak_nilai' => $item['hitung']['pajak'],
                    'total_baris' => $item['hitung']['total'],
                    'harga_pokok' => 0,
                    'total_harga_pokok' => 0,
                    'laba_kotor' => $item['hitung']['total'],
                    'nomor_lot' => $item['baris']['nomor_lot'] ?? null,
                    'tanggal_kedaluwarsa' => $item['baris']['tanggal_kedaluwarsa'] ?? null,
                    'keterangan' => $item['baris']['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PENJUALAN', 'TAMBAH', 'penjualan', $id, 'Membuat draf transaksi penjualan.');

        return back()->with('berhasil', 'Transaksi penjualan berhasil dibuat sebagai draf.');
    }

    public function setujuiPenjualan(Request $request, int $id, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'TRANSAKSI_PENJUALAN_SETUJUI');
        $idCabang = $this->idCabang($request);

        DB::transaction(function () use ($request, $id, $idCabang, $layanan): void {
            $penjualan = DB::table('penjualan')->where('id_penjualan', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $penjualan) {
                abort(404);
            }
            if ($penjualan->status_penjualan !== 'DRAF') {
                throw ValidationException::withMessages(['status_penjualan' => 'Hanya penjualan DRAF yang dapat disetujui.']);
            }

            if ($penjualan->jenis_penjualan === 'TEMPO') {
                if (! $penjualan->id_pelanggan) {
                    throw ValidationException::withMessages(['id_pelanggan' => 'Penjualan tempo wajib memiliki pelanggan.']);
                }
                $layanan->pastikanBatasKredit((int) $penjualan->id_pelanggan, (float) $penjualan->total_bersih);
            } elseif ((float) $penjualan->total_dibayar + 0.009 < (float) $penjualan->total_bersih) {
                throw ValidationException::withMessages(['total_dibayar' => 'Pembayaran tunai belum mencukupi total penjualan.']);
            }

            $detail = DB::table('penjualan_detail')->where('id_penjualan', $id)->whereNull('deleted_at')->lockForUpdate()->get();
            foreach ($detail as $item) {
                $mutasi = $layanan->catatPenjualanStok($idCabang, $penjualan, $item, (int) $request->user()->id_pengguna);
                $totalHpp = round((float) $item->jumlah_dasar * (float) $mutasi->harga_pokok, 2);
                DB::table('penjualan_detail')->where('id_penjualan_detail', $item->id_penjualan_detail)->update([
                    'harga_pokok' => $mutasi->harga_pokok,
                    'total_harga_pokok' => $totalHpp,
                    'laba_kotor' => round((float) $item->total_baris - $totalHpp, 2),
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);

                if ($item->id_pesanan_penjualan_detail) {
                    $pesananDetail = DB::table('pesanan_penjualan_detail')->where('id_pesanan_penjualan_detail', $item->id_pesanan_penjualan_detail)->lockForUpdate()->first();
                    if (! $pesananDetail || (float) $pesananDetail->jumlah_difakturkan + (float) $item->jumlah > (float) $pesananDetail->jumlah + 0.0001) {
                        throw ValidationException::withMessages(['detail' => 'Jumlah penjualan melebihi jumlah pesanan.']);
                    }
                    DB::table('pesanan_penjualan_detail')->where('id_pesanan_penjualan_detail', $item->id_pesanan_penjualan_detail)->update([
                        'jumlah_difakturkan' => round((float) $pesananDetail->jumlah_difakturkan + (float) $item->jumlah, 3),
                        'updated_at' => now(),
                        'updated_by' => $request->user()->id_pengguna,
                    ]);
                }
            }

            $status = $penjualan->jenis_penjualan === 'TUNAI' ? 'LUNAS' : 'DISETUJUI';
            $sisa = $penjualan->jenis_penjualan === 'TEMPO' ? (float) $penjualan->total_bersih : 0;
            DB::table('penjualan')->where('id_penjualan', $id)->update([
                'status_penjualan' => $status,
                'total_dibayar' => $penjualan->jenis_penjualan === 'TUNAI' ? min((float) $penjualan->total_dibayar, (float) $penjualan->total_bersih) : 0,
                'uang_kembali' => $penjualan->jenis_penjualan === 'TUNAI' ? max(0, (float) $penjualan->total_dibayar - (float) $penjualan->total_bersih) : 0,
                'sisa_piutang' => $sisa,
                'id_pengguna_penyetuju' => $request->user()->id_pengguna,
                'tanggal_disetujui' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);

            if ($penjualan->jenis_penjualan === 'TEMPO') {
                DB::table('piutang_pelanggan')->insertOrIgnore([
                    'id_cabang' => $idCabang,
                    'id_pelanggan' => $penjualan->id_pelanggan,
                    'id_penjualan' => $id,
                    'tanggal_piutang' => date('Y-m-d', strtotime($penjualan->tanggal_penjualan)),
                    'tanggal_jatuh_tempo' => $penjualan->tanggal_jatuh_tempo,
                    'nilai_awal' => $penjualan->total_bersih,
                    'nilai_pembayaran' => 0,
                    'nilai_retur' => 0,
                    'nilai_penyesuaian' => 0,
                    'sisa_piutang' => $penjualan->total_bersih,
                    'status_piutang' => 'BELUM_LUNAS',
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            if ($penjualan->id_pesanan_penjualan) {
                $layanan->perbaruiStatusPesanan((int) $penjualan->id_pesanan_penjualan, (int) $request->user()->id_pengguna);
            }
        });

        $audit->catat($request, 'PENJUALAN', 'SETUJUI', 'penjualan', $id, 'Menyetujui penjualan, mengurangi stok, dan membentuk piutang bila tempo.');

        return back()->with('berhasil', 'Penjualan berhasil disetujui dan stok telah diperbarui.');
    }

    public function batalkanPenjualan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'TRANSAKSI_PENJUALAN_KELOLA', 'penjualan', 'id_penjualan', $id, 'status_penjualan', ['DRAF'], 'DIBATALKAN', 'Penjualan berhasil dibatalkan.');
    }

    public function simpanPengiriman(Request $request, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PENGIRIMAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'id_pesanan_penjualan' => ['nullable', 'integer'],
            'id_penjualan' => ['nullable', 'integer'],
            'id_armada' => ['nullable', 'integer'],
            'id_pegawai_pengemudi' => ['nullable', 'integer'],
            'tanggal_pengiriman' => ['required', 'date'],
            'tanggal_rencana_tiba' => ['nullable', 'date'],
            'nama_penerima' => ['nullable', 'string', 'max:150'],
            'telepon_penerima' => ['nullable', 'string', 'max:30'],
            'alamat_pengiriman' => ['required', 'string'],
            'garis_lintang' => ['nullable', 'numeric', 'between:-90,90'],
            'garis_bujur' => ['nullable', 'numeric', 'between:-180,180'],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_pesanan_penjualan_detail' => ['nullable', 'integer'],
            'detail.*.id_penjualan_detail' => ['nullable', 'integer'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.jumlah_dikirim' => ['required', 'numeric', 'gt:0'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);
        if (empty($data['id_penjualan']) && empty($data['id_pesanan_penjualan'])) {
            throw ValidationException::withMessages(['id_penjualan' => 'Pengiriman harus terkait penjualan atau pesanan.']);
        }

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            if (! empty($data['id_penjualan'])) {
                $valid = DB::table('penjualan')->where('id_penjualan', $data['id_penjualan'])->where('id_cabang', $idCabang)->whereIn('status_penjualan', ['DISETUJUI', 'SEBAGIAN_DIBAYAR', 'LUNAS'])->whereNull('deleted_at')->exists();
                if (! $valid) {
                    throw ValidationException::withMessages(['id_penjualan' => 'Penjualan tidak valid atau belum disetujui.']);
                }
            }
            if (! empty($data['id_pesanan_penjualan'])) {
                $valid = DB::table('pesanan_penjualan')->where('id_pesanan_penjualan', $data['id_pesanan_penjualan'])->where('id_cabang', $idCabang)->whereNotIn('status_pesanan', ['DRAF', 'DIBATALKAN'])->whereNull('deleted_at')->exists();
                if (! $valid) {
                    throw ValidationException::withMessages(['id_pesanan_penjualan' => 'Pesanan tidak valid atau belum disetujui.']);
                }
            }

            $id = (int) DB::table('pengiriman')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pesanan_penjualan' => $data['id_pesanan_penjualan'] ?? null,
                'id_penjualan' => $data['id_penjualan'] ?? null,
                'id_armada' => $data['id_armada'] ?? null,
                'id_pegawai_pengemudi' => $data['id_pegawai_pengemudi'] ?? null,
                'nomor_pengiriman' => $layanan->nomorBerikutnya($idCabang, 'PENGIRIMAN', 'DO', $data['tanggal_pengiriman']),
                'tanggal_pengiriman' => $data['tanggal_pengiriman'],
                'tanggal_rencana_tiba' => $data['tanggal_rencana_tiba'] ?? null,
                'status_pengiriman' => 'DRAF',
                'nama_penerima' => $data['nama_penerima'] ?? null,
                'telepon_penerima' => $data['telepon_penerima'] ?? null,
                'alamat_pengiriman' => $data['alamat_pengiriman'],
                'garis_lintang' => $data['garis_lintang'] ?? null,
                'garis_bujur' => $data['garis_bujur'] ?? null,
                'biaya_pengiriman' => $data['biaya_pengiriman'] ?? 0,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($data['detail'] as $index => $baris) {
                $barangSatuan = $layanan->barangSatuan((int) $baris['id_barang_satuan']);
                $jumlahDasar = $layanan->jumlahDasar($barangSatuan, $baris['jumlah_dikirim'], "detail.{$index}.jumlah_dikirim");
                $this->pastikanJumlahPengiriman($baris, (float) $baris['jumlah_dikirim']);
                DB::table('pengiriman_detail')->insert([
                    'id_pengiriman' => $id,
                    'id_pesanan_penjualan_detail' => $baris['id_pesanan_penjualan_detail'] ?? null,
                    'id_penjualan_detail' => $baris['id_penjualan_detail'] ?? null,
                    'id_barang_satuan' => $barangSatuan->id_barang_satuan,
                    'nilai_konversi' => $barangSatuan->nilai_konversi,
                    'jumlah_dikirim' => $baris['jumlah_dikirim'],
                    'jumlah_dasar_dikirim' => $jumlahDasar,
                    'jumlah_diterima' => 0,
                    'jumlah_dasar_diterima' => 0,
                    'keterangan' => $baris['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PENJUALAN', 'TAMBAH', 'pengiriman', $id, 'Membuat draf pengiriman.');

        return back()->with('berhasil', 'Pengiriman berhasil dibuat sebagai draf.');
    }

    public function jadwalkanPengiriman(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PENGIRIMAN_JADWALKAN', 'pengiriman', 'id_pengiriman', $id, 'status_pengiriman', ['DRAF'], 'DIJADWALKAN', 'Pengiriman berhasil dijadwalkan.');
    }

    public function berangkatkanPengiriman(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PENGIRIMAN_KIRIM');
        $idCabang = $this->idCabang($request);
        DB::transaction(function () use ($request, $id, $idCabang): void {
            $pengiriman = DB::table('pengiriman')->where('id_pengiriman', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $pengiriman) {
                abort(404);
            }
            if ($pengiriman->status_pengiriman !== 'DIJADWALKAN') {
                throw ValidationException::withMessages(['status_pengiriman' => 'Hanya pengiriman terjadwal yang dapat diberangkatkan.']);
            }
            DB::table('pengiriman')->where('id_pengiriman', $id)->update([
                'status_pengiriman' => 'DALAM_PERJALANAN',
                'tanggal_berangkat' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });
        $audit->catat($request, 'PENJUALAN', 'UBAH', 'pengiriman', $id, 'Memberangkatkan pengiriman.');

        return back()->with('berhasil', 'Pengiriman sedang dalam perjalanan.');
    }

    public function terimaPengiriman(Request $request, int $id, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PENGIRIMAN_TERIMA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'bukti_penerimaan' => ['nullable', 'string', 'max:255'],
            'catatan_penerima' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $id, $idCabang, $data, $layanan): void {
            $pengiriman = DB::table('pengiriman')->where('id_pengiriman', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $pengiriman) {
                abort(404);
            }
            if ($pengiriman->status_pengiriman !== 'DALAM_PERJALANAN') {
                throw ValidationException::withMessages(['status_pengiriman' => 'Hanya pengiriman dalam perjalanan yang dapat diterima.']);
            }

            $detail = DB::table('pengiriman_detail')->where('id_pengiriman', $id)->whereNull('deleted_at')->lockForUpdate()->get();
            foreach ($detail as $item) {
                DB::table('pengiriman_detail')->where('id_pengiriman_detail', $item->id_pengiriman_detail)->update([
                    'jumlah_diterima' => $item->jumlah_dikirim,
                    'jumlah_dasar_diterima' => $item->jumlah_dasar_dikirim,
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);
                if ($item->id_pesanan_penjualan_detail) {
                    $pesananDetail = DB::table('pesanan_penjualan_detail')->where('id_pesanan_penjualan_detail', $item->id_pesanan_penjualan_detail)->lockForUpdate()->first();
                    if (! $pesananDetail || (float) $pesananDetail->jumlah_dikirim + (float) $item->jumlah_dikirim > (float) $pesananDetail->jumlah + 0.0001) {
                        throw ValidationException::withMessages(['jumlah_dikirim' => 'Jumlah pengiriman melebihi pesanan.']);
                    }
                    DB::table('pesanan_penjualan_detail')->where('id_pesanan_penjualan_detail', $item->id_pesanan_penjualan_detail)->update([
                        'jumlah_dikirim' => round((float) $pesananDetail->jumlah_dikirim + (float) $item->jumlah_dikirim, 3),
                        'updated_at' => now(),
                        'updated_by' => $request->user()->id_pengguna,
                    ]);
                }
            }

            DB::table('pengiriman')->where('id_pengiriman', $id)->update([
                'status_pengiriman' => 'DITERIMA',
                'tanggal_tiba' => now(),
                'bukti_penerimaan' => $data['bukti_penerimaan'] ?? null,
                'catatan_penerima' => $data['catatan_penerima'] ?? null,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);

            if ($pengiriman->id_pesanan_penjualan) {
                $layanan->perbaruiStatusPesanan((int) $pengiriman->id_pesanan_penjualan, (int) $request->user()->id_pengguna);
            }
            if ($pengiriman->id_penjualan) {
                $layanan->perbaruiStatusPengirimanPenjualan((int) $pengiriman->id_penjualan, (int) $request->user()->id_pengguna);
            }
        });

        $audit->catat($request, 'PENJUALAN', 'UBAH', 'pengiriman', $id, 'Menyelesaikan penerimaan pengiriman.');

        return back()->with('berhasil', 'Pengiriman berhasil diterima.');
    }

    public function gagalPengiriman(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PENGIRIMAN_KELOLA', 'pengiriman', 'id_pengiriman', $id, 'status_pengiriman', ['DIJADWALKAN', 'DALAM_PERJALANAN'], 'GAGAL', 'Pengiriman ditandai gagal.');
    }

    public function batalkanPengiriman(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'PENGIRIMAN_KELOLA', 'pengiriman', 'id_pengiriman', $id, 'status_pengiriman', ['DRAF', 'DIJADWALKAN'], 'DIBATALKAN', 'Pengiriman berhasil dibatalkan.');
    }

    public function simpanRetur(Request $request, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'RETUR_PENJUALAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'id_penjualan' => ['required', 'integer'],
            'id_gudang' => ['required', 'integer'],
            'tanggal_retur' => ['required', 'date'],
            'alasan_retur' => ['required', 'string'],
            'cara_pengembalian_dana' => ['required', Rule::in(['POTONG_PIUTANG', 'TUNAI', 'TRANSFER', 'PENGGANTI_BARANG'])],
            'id_kas_bank' => ['nullable', 'integer'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_penjualan_detail' => ['required', 'integer'],
            'detail.*.id_barang_satuan' => ['required', 'integer'],
            'detail.*.id_lokasi_gudang' => ['required', 'integer'],
            'detail.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'detail.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'detail.*.nomor_lot' => ['nullable', 'string', 'max:100'],
            'detail.*.tanggal_kedaluwarsa' => ['nullable', 'date'],
            'detail.*.kondisi_barang' => ['required', Rule::in(['BAIK', 'RUSAK', 'CACAT', 'SALAH_KIRIM', 'LAINNYA'])],
            'detail.*.bisa_dijual_kembali' => ['required', 'boolean'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            $penjualan = DB::table('penjualan')->where('id_penjualan', $data['id_penjualan'])->where('id_cabang', $idCabang)->whereIn('status_penjualan', ['DISETUJUI', 'SEBAGIAN_DIBAYAR', 'LUNAS'])->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $penjualan) {
                throw ValidationException::withMessages(['id_penjualan' => 'Penjualan tidak valid atau belum disetujui.']);
            }
            $gudangValid = DB::table('gudang')->where('id_gudang', $data['id_gudang'])->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
            if (! $gudangValid) {
                throw ValidationException::withMessages(['id_gudang' => 'Gudang retur tidak valid.']);
            }

            $rincian = [];
            $totalRetur = 0.0;
            foreach ($data['detail'] as $index => $baris) {
                $barangSatuan = $layanan->barangSatuan((int) $baris['id_barang_satuan']);
                $jumlahDasar = $layanan->jumlahDasar($barangSatuan, $baris['jumlah'], "detail.{$index}.jumlah");
                $layanan->pastikanGudangLokasi($idCabang, (int) $data['id_gudang'], (int) $baris['id_lokasi_gudang']);
                $detailJual = DB::table('penjualan_detail')->where('id_penjualan_detail', $baris['id_penjualan_detail'])->where('id_penjualan', $penjualan->id_penjualan)->whereNull('deleted_at')->lockForUpdate()->first();
                if (! $detailJual || (int) $detailJual->id_barang_satuan !== (int) $barangSatuan->id_barang_satuan) {
                    throw ValidationException::withMessages(["detail.{$index}.id_penjualan_detail" => 'Detail penjualan sumber tidak valid.']);
                }
                $sudahRetur = (float) DB::table('retur_penjualan_detail as d')
                    ->join('retur_penjualan as h', 'h.id_retur_penjualan', '=', 'd.id_retur_penjualan')
                    ->where('d.id_penjualan_detail', $detailJual->id_penjualan_detail)
                    ->whereNotIn('h.status_retur', ['DIBATALKAN'])
                    ->whereNull('h.deleted_at')
                    ->whereNull('d.deleted_at')
                    ->sum('d.jumlah');
                if ($sudahRetur + (float) $baris['jumlah'] > (float) $detailJual->jumlah + 0.0001) {
                    throw ValidationException::withMessages(["detail.{$index}.jumlah" => 'Jumlah retur melebihi jumlah penjualan.']);
                }
                $totalBaris = round((float) $baris['jumlah'] * (float) $baris['harga_satuan'], 2);
                $totalRetur += $totalBaris;
                $rincian[] = compact('barangSatuan', 'jumlahDasar', 'detailJual', 'totalBaris', 'baris');
            }

            $id = (int) DB::table('retur_penjualan')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pelanggan' => $penjualan->id_pelanggan,
                'id_penjualan' => $penjualan->id_penjualan,
                'id_gudang' => $data['id_gudang'],
                'nomor_retur' => $layanan->nomorBerikutnya($idCabang, 'RETUR_PENJUALAN', 'SR', $data['tanggal_retur']),
                'tanggal_retur' => $data['tanggal_retur'],
                'alasan_retur' => $data['alasan_retur'],
                'cara_pengembalian_dana' => $data['cara_pengembalian_dana'],
                'id_kas_bank' => $data['id_kas_bank'] ?? null,
                'status_retur' => 'DRAF',
                'total_retur' => round($totalRetur, 2),
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                DB::table('retur_penjualan_detail')->insert([
                    'id_retur_penjualan' => $id,
                    'id_penjualan_detail' => $item['detailJual']->id_penjualan_detail,
                    'id_barang_satuan' => $item['barangSatuan']->id_barang_satuan,
                    'id_lokasi_gudang' => $item['baris']['id_lokasi_gudang'],
                    'nilai_konversi' => $item['barangSatuan']->nilai_konversi,
                    'jumlah' => $item['baris']['jumlah'],
                    'jumlah_dasar' => $item['jumlahDasar'],
                    'harga_satuan' => $item['baris']['harga_satuan'],
                    'total_baris' => $item['totalBaris'],
                    'nomor_lot' => $item['baris']['nomor_lot'] ?? null,
                    'tanggal_kedaluwarsa' => $item['baris']['tanggal_kedaluwarsa'] ?? null,
                    'kondisi_barang' => $item['baris']['kondisi_barang'],
                    'bisa_dijual_kembali' => $item['baris']['bisa_dijual_kembali'],
                    'keterangan' => $item['baris']['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PENJUALAN', 'TAMBAH', 'retur_penjualan', $id, 'Membuat draf retur penjualan.');

        return back()->with('berhasil', 'Retur penjualan berhasil dibuat sebagai draf.');
    }

    public function setujuiRetur(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'RETUR_PENJUALAN_SETUJUI');
        $idCabang = $this->idCabang($request);
        DB::transaction(function () use ($request, $id, $idCabang): void {
            $retur = DB::table('retur_penjualan')->where('id_retur_penjualan', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $retur) {
                abort(404);
            }
            if ($retur->status_retur !== 'DRAF') {
                throw ValidationException::withMessages(['status_retur' => 'Hanya retur DRAF yang dapat disetujui.']);
            }
            DB::table('retur_penjualan')->where('id_retur_penjualan', $id)->update([
                'status_retur' => 'DISETUJUI',
                'id_pengguna_penyetuju' => $request->user()->id_pengguna,
                'tanggal_disetujui' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });
        $audit->catat($request, 'PENJUALAN', 'SETUJUI', 'retur_penjualan', $id, 'Menyetujui retur penjualan.');

        return back()->with('berhasil', 'Retur penjualan berhasil disetujui.');
    }

    public function terimaRetur(Request $request, int $id, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'RETUR_PENJUALAN_TERIMA');
        $idCabang = $this->idCabang($request);

        DB::transaction(function () use ($request, $id, $idCabang, $layanan): void {
            $retur = DB::table('retur_penjualan')->where('id_retur_penjualan', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $retur) {
                abort(404);
            }
            if ($retur->status_retur !== 'DISETUJUI') {
                throw ValidationException::withMessages(['status_retur' => 'Hanya retur DISETUJUI yang dapat diterima.']);
            }

            $detail = DB::table('retur_penjualan_detail')->where('id_retur_penjualan', $id)->whereNull('deleted_at')->lockForUpdate()->get();
            foreach ($detail as $item) {
                $layanan->catatReturStok($idCabang, $retur, $item, (int) $request->user()->id_pengguna);
            }

            if ($retur->cara_pengembalian_dana === 'POTONG_PIUTANG') {
                $piutang = DB::table('piutang_pelanggan')->where('id_penjualan', $retur->id_penjualan)->lockForUpdate()->first();
                if (! $piutang) {
                    throw ValidationException::withMessages(['cara_pengembalian_dana' => 'Piutang penjualan tidak ditemukan untuk dipotong.']);
                }
                if ((float) $retur->total_retur > (float) $piutang->sisa_piutang + 0.009) {
                    throw ValidationException::withMessages(['total_retur' => 'Nilai retur melebihi sisa piutang.']);
                }
                DB::table('piutang_pelanggan')->where('id_piutang_pelanggan', $piutang->id_piutang_pelanggan)->update([
                    'nilai_retur' => round((float) $piutang->nilai_retur + (float) $retur->total_retur, 2),
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);
                $layanan->perbaruiPiutang((int) $piutang->id_piutang_pelanggan, (int) $request->user()->id_pengguna);
            }

            DB::table('retur_penjualan')->where('id_retur_penjualan', $id)->update([
                'status_retur' => 'DITERIMA',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PENJUALAN', 'UBAH', 'retur_penjualan', $id, 'Menerima retur dan menambah stok kembali.');

        return back()->with('berhasil', 'Retur berhasil diterima dan stok telah diperbarui.');
    }

    public function selesaikanRetur(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'RETUR_PENJUALAN_KELOLA', 'retur_penjualan', 'id_retur_penjualan', $id, 'status_retur', ['DITERIMA'], 'SELESAI', 'Retur penjualan berhasil diselesaikan.');
    }

    public function batalkanRetur(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        return $this->ubahStatusSederhana($request, $audit, 'RETUR_PENJUALAN_KELOLA', 'retur_penjualan', 'id_retur_penjualan', $id, 'status_retur', ['DRAF', 'DISETUJUI'], 'DIBATALKAN', 'Retur penjualan berhasil dibatalkan.');
    }

    private function rincianHarga(array $detail, LayananPenjualan $layanan, ?int $idGudang = null, ?int $idCabang = null): array
    {
        $rincian = [];
        $total = ['kotor' => 0.0, 'potongan' => 0.0, 'pajak' => 0.0, 'bersih' => 0.0];
        foreach ($detail as $index => $baris) {
            $barangSatuan = $layanan->barangSatuan((int) $baris['id_barang_satuan']);
            $jumlahDasar = $layanan->jumlahDasar($barangSatuan, $baris['jumlah'], "detail.{$index}.jumlah");
            if ($idGudang && $idCabang) {
                $layanan->pastikanGudangLokasi($idCabang, $idGudang, (int) $baris['id_lokasi_gudang']);
            }
            $pajakPersen = $layanan->persenPajak(isset($baris['id_tarif_pajak']) ? (int) $baris['id_tarif_pajak'] : null);
            $hitung = $layanan->hitungBaris((float) $baris['jumlah'], (float) $baris['harga_satuan'], (float) ($baris['potongan_persen'] ?? 0), $pajakPersen);
            $total['kotor'] += $hitung['kotor'];
            $total['potongan'] += $hitung['potongan'];
            $total['pajak'] += $hitung['pajak'];
            $total['bersih'] += $hitung['total'];
            $rincian[] = compact('barangSatuan', 'jumlahDasar', 'pajakPersen', 'hitung', 'baris');
        }

        foreach ($total as $kunci => $nilai) {
            $total[$kunci] = round($nilai, 2);
        }

        return [$rincian, $total];
    }

    private function pastikanJumlahPengiriman(array $baris, float $jumlah): void
    {
        if (! empty($baris['id_penjualan_detail'])) {
            $sumber = DB::table('penjualan_detail')->where('id_penjualan_detail', $baris['id_penjualan_detail'])->whereNull('deleted_at')->lockForUpdate()->first();
            $sudah = (float) DB::table('pengiriman_detail as d')
                ->join('pengiriman as h', 'h.id_pengiriman', '=', 'd.id_pengiriman')
                ->where('d.id_penjualan_detail', $baris['id_penjualan_detail'])
                ->whereNotIn('h.status_pengiriman', ['GAGAL', 'DIBATALKAN'])
                ->whereNull('h.deleted_at')
                ->whereNull('d.deleted_at')
                ->sum('d.jumlah_dikirim');
            if (! $sumber || $sudah + $jumlah > (float) $sumber->jumlah + 0.0001) {
                throw ValidationException::withMessages(['jumlah_dikirim' => 'Jumlah pengiriman melebihi jumlah penjualan.']);
            }
        }
        if (! empty($baris['id_pesanan_penjualan_detail'])) {
            $sumber = DB::table('pesanan_penjualan_detail')->where('id_pesanan_penjualan_detail', $baris['id_pesanan_penjualan_detail'])->whereNull('deleted_at')->lockForUpdate()->first();
            $sudah = (float) DB::table('pengiriman_detail as d')
                ->join('pengiriman as h', 'h.id_pengiriman', '=', 'd.id_pengiriman')
                ->where('d.id_pesanan_penjualan_detail', $baris['id_pesanan_penjualan_detail'])
                ->whereNotIn('h.status_pengiriman', ['GAGAL', 'DIBATALKAN'])
                ->whereNull('h.deleted_at')
                ->whereNull('d.deleted_at')
                ->sum('d.jumlah_dikirim');
            if (! $sumber || $sudah + $jumlah > (float) $sumber->jumlah + 0.0001) {
                throw ValidationException::withMessages(['jumlah_dikirim' => 'Jumlah pengiriman melebihi jumlah pesanan.']);
            }
        }
    }

    private function pilihan(int $idCabang): array
    {
        return [
            'pelangganPilihan' => DB::table('pelanggan')->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_pelanggan')->get(),
            'barangSatuanPilihan' => DB::table('barang_satuan as bs')->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')->join('satuan as s', 's.id_satuan', '=', 'bs.id_satuan')->where('bs.status_aktif', 1)->where('b.status_aktif', 1)->where('b.bisa_dijual', 1)->whereNull('bs.deleted_at')->whereNull('b.deleted_at')->select('bs.id_barang_satuan', 'b.kode_barang', 'b.nama_barang', 's.nama_satuan', 'bs.harga_jual_acuan')->orderBy('b.nama_barang')->get(),
            'pajakPilihan' => DB::table('tarif_pajak')->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_tarif_pajak')->get(),
            'gudangPilihan' => DB::table('gudang')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_gudang')->get(),
            'lokasiPilihan' => DB::table('lokasi_gudang as l')->join('gudang as g', 'g.id_gudang', '=', 'l.id_gudang')->where('g.id_cabang', $idCabang)->where('l.status_aktif', 1)->where('g.status_aktif', 1)->whereNull('l.deleted_at')->whereNull('g.deleted_at')->select('l.*', 'g.nama_gudang')->orderBy('g.nama_gudang')->orderBy('l.nama_lokasi')->get(),
            'daftarHargaPilihan' => DB::table('daftar_harga')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_daftar_harga')->get(),
            'kasPilihan' => DB::table('kas_bank')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_kas_bank')->get(),
            'metodePilihan' => DB::table('metode_pembayaran')->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_metode_pembayaran')->get(),
            'armadaPilihan' => DB::table('armada')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nomor_polisi')->get(),
            'pengemudiPilihan' => DB::table('pegawai')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_pegawai')->get(),
            'pesananPilihan' => DB::table('pesanan_penjualan')->where('id_cabang', $idCabang)->whereNotIn('status_pesanan', ['DRAF', 'SELESAI', 'DIBATALKAN'])->whereNull('deleted_at')->orderByDesc('id_pesanan_penjualan')->get(),
            'pesananDetailPilihan' => DB::table('pesanan_penjualan_detail as d')->join('pesanan_penjualan as h', 'h.id_pesanan_penjualan', '=', 'd.id_pesanan_penjualan')->join('barang_satuan as bs', 'bs.id_barang_satuan', '=', 'd.id_barang_satuan')->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')->where('h.id_cabang', $idCabang)->whereNotIn('h.status_pesanan', ['DRAF', 'SELESAI', 'DIBATALKAN'])->whereNull('h.deleted_at')->whereNull('d.deleted_at')->select('d.*', 'h.nomor_pesanan', 'b.nama_barang')->get(),
            'penjualanPilihan' => DB::table('penjualan')->where('id_cabang', $idCabang)->whereIn('status_penjualan', ['DISETUJUI', 'SEBAGIAN_DIBAYAR', 'LUNAS'])->whereNull('deleted_at')->orderByDesc('id_penjualan')->get(),
            'penjualanDetailPilihan' => DB::table('penjualan_detail as d')->join('penjualan as h', 'h.id_penjualan', '=', 'd.id_penjualan')->join('barang_satuan as bs', 'bs.id_barang_satuan', '=', 'd.id_barang_satuan')->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')->where('h.id_cabang', $idCabang)->whereIn('h.status_penjualan', ['DISETUJUI', 'SEBAGIAN_DIBAYAR', 'LUNAS'])->whereNull('h.deleted_at')->whereNull('d.deleted_at')->select('d.*', 'h.nomor_penjualan', 'b.nama_barang')->get(),
        ];
    }

    private function ubahStatusSederhana(Request $request, AuditAktivitas $audit, string $hak, string $tabel, string $kunci, int $id, string $kolomStatus, array $statusAsal, string $statusTujuan, string $pesan): RedirectResponse
    {
        $this->pastikanAkses($request, $hak);
        $idCabang = $this->idCabang($request);
        DB::transaction(function () use ($request, $idCabang, $tabel, $kunci, $id, $kolomStatus, $statusAsal, $statusTujuan): void {
            $data = DB::table($tabel)->where($kunci, $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $data) {
                abort(404);
            }
            if (! in_array($data->{$kolomStatus}, $statusAsal, true)) {
                throw ValidationException::withMessages([$kolomStatus => 'Status dokumen tidak mengizinkan tindakan ini.']);
            }
            DB::table($tabel)->where($kunci, $id)->update([
                $kolomStatus => $statusTujuan,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });
        $audit->catat($request, 'PENJUALAN', in_array($statusTujuan, ['DISETUJUI', 'DISETUJUI_PELANGGAN'], true) ? 'SETUJUI' : ($statusTujuan === 'DIBATALKAN' ? 'BATALKAN' : 'UBAH'), $tabel, $id, $pesan);

        return back()->with('berhasil', $pesan);
    }

    private function idCabang(Request $request): int
    {
        $id = (int) $request->session()->get('id_cabang_aktif');
        if ($id <= 0) {
            abort(403, 'Cabang aktif belum dipilih.');
        }

        return $id;
    }

    private function pastikanAkses(Request $request, string $kode): void
    {
        abort_unless($request->user()?->memilikiHakAkses($kode, $this->idCabang($request)), 403);
    }
}
