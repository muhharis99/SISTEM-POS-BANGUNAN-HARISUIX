<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use App\Services\LayananPenjualan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PiutangPelangganController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'PIUTANG_PELANGGAN_LIHAT');
        $idCabang = $this->idCabang($request);
        $pencarian = trim((string) $request->query('pencarian'));

        $piutang = DB::table('piutang_pelanggan as h')
            ->join('pelanggan as p', 'p.id_pelanggan', '=', 'h.id_pelanggan')
            ->join('penjualan as j', 'j.id_penjualan', '=', 'h.id_penjualan')
            ->where('h.id_cabang', $idCabang)
            ->when($pencarian !== '', fn ($q) => $q->where(function ($s) use ($pencarian): void {
                $s->where('j.nomor_penjualan', 'like', "%{$pencarian}%")
                    ->orWhere('p.nama_pelanggan', 'like', "%{$pencarian}%");
            }))
            ->select('h.*', 'p.nama_pelanggan', 'j.nomor_penjualan')
            ->orderByRaw("FIELD(h.status_piutang, 'BELUM_LUNAS', 'SEBAGIAN', 'LUNAS', 'DIBATALKAN')")
            ->orderBy('h.tanggal_jatuh_tempo')
            ->limit(100)
            ->get();

        $pembayaran = DB::table('pembayaran_piutang as h')
            ->join('pelanggan as p', 'p.id_pelanggan', '=', 'h.id_pelanggan')
            ->join('kas_bank as k', 'k.id_kas_bank', '=', 'h.id_kas_bank')
            ->where('h.id_cabang', $idCabang)
            ->whereNull('h.deleted_at')
            ->select('h.*', 'p.nama_pelanggan', 'k.nama_kas_bank')
            ->orderByDesc('h.id_pembayaran_piutang')
            ->limit(50)
            ->get();

        $ringkasan = [
            'total_piutang' => (float) DB::table('piutang_pelanggan')->where('id_cabang', $idCabang)->whereNotIn('status_piutang', ['LUNAS', 'DIBATALKAN'])->sum('sisa_piutang'),
            'jatuh_tempo' => (float) DB::table('piutang_pelanggan')->where('id_cabang', $idCabang)->whereNotIn('status_piutang', ['LUNAS', 'DIBATALKAN'])->whereNotNull('tanggal_jatuh_tempo')->where('tanggal_jatuh_tempo', '<', now()->toDateString())->sum('sisa_piutang'),
            'pelanggan_berpiutang' => DB::table('piutang_pelanggan')->where('id_cabang', $idCabang)->whereNotIn('status_piutang', ['LUNAS', 'DIBATALKAN'])->distinct('id_pelanggan')->count('id_pelanggan'),
            'pembayaran_bulan_ini' => (float) DB::table('pembayaran_piutang')->where('id_cabang', $idCabang)->where('status_pembayaran', 'DISETUJUI')->whereBetween('tanggal_pembayaran', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('total_pembayaran'),
        ];

        return view('piutang-pelanggan.index', [
            'piutang' => $piutang,
            'pembayaran' => $pembayaran,
            'ringkasan' => $ringkasan,
            'pencarian' => $pencarian,
            'pelangganPilihan' => DB::table('pelanggan')->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_pelanggan')->get(),
            'piutangPilihan' => DB::table('piutang_pelanggan as h')->join('pelanggan as p', 'p.id_pelanggan', '=', 'h.id_pelanggan')->join('penjualan as j', 'j.id_penjualan', '=', 'h.id_penjualan')->where('h.id_cabang', $idCabang)->whereNotIn('h.status_piutang', ['LUNAS', 'DIBATALKAN'])->select('h.*', 'p.nama_pelanggan', 'j.nomor_penjualan')->orderBy('p.nama_pelanggan')->get(),
            'kasPilihan' => DB::table('kas_bank')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_kas_bank')->get(),
            'metodePilihan' => DB::table('metode_pembayaran')->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_metode_pembayaran')->get(),
        ]);
    }

    public function simpan(Request $request, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PEMBAYARAN_PIUTANG_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'id_pelanggan' => ['required', 'integer'],
            'id_kas_bank' => ['required', 'integer'],
            'id_metode_pembayaran' => ['required', 'integer'],
            'tanggal_pembayaran' => ['required', 'date'],
            'nomor_bukti' => ['nullable', 'string', 'max:100'],
            'biaya_pembayaran' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_piutang_pelanggan' => ['required', 'integer'],
            'detail.*.nilai_dialokasikan' => ['required', 'numeric', 'gt:0'],
            'detail.*.potongan_pembayaran' => ['nullable', 'numeric', 'min:0'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);
        $layanan->pelanggan((int) $data['id_pelanggan']);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            $kasValid = DB::table('kas_bank')->where('id_kas_bank', $data['id_kas_bank'])->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
            $metodeValid = DB::table('metode_pembayaran')->where('id_metode_pembayaran', $data['id_metode_pembayaran'])->where('status_aktif', 1)->whereNull('deleted_at')->exists();
            if (! $kasValid || ! $metodeValid) {
                throw ValidationException::withMessages(['id_kas_bank' => 'Kas/bank atau metode pembayaran tidak valid.']);
            }

            $rincian = [];
            $total = 0.0;
            $idUnik = [];
            foreach ($data['detail'] as $index => $baris) {
                $idPiutang = (int) $baris['id_piutang_pelanggan'];
                if (in_array($idPiutang, $idUnik, true)) {
                    throw ValidationException::withMessages(["detail.{$index}.id_piutang_pelanggan" => 'Piutang yang sama tidak boleh dipilih lebih dari satu kali.']);
                }
                $idUnik[] = $idPiutang;
                $piutang = DB::table('piutang_pelanggan')->where('id_piutang_pelanggan', $idPiutang)->where('id_cabang', $idCabang)->where('id_pelanggan', $data['id_pelanggan'])->whereNotIn('status_piutang', ['LUNAS', 'DIBATALKAN'])->lockForUpdate()->first();
                if (! $piutang) {
                    throw ValidationException::withMessages(["detail.{$index}.id_piutang_pelanggan" => 'Piutang tidak valid, berbeda pelanggan, atau sudah lunas.']);
                }
                $alokasi = round((float) $baris['nilai_dialokasikan'], 2);
                $potongan = round((float) ($baris['potongan_pembayaran'] ?? 0), 2);
                if ($alokasi + $potongan > (float) $piutang->sisa_piutang + 0.009) {
                    throw ValidationException::withMessages(["detail.{$index}.nilai_dialokasikan" => 'Alokasi dan potongan melebihi sisa piutang.']);
                }
                $total += $alokasi;
                $rincian[] = compact('piutang', 'alokasi', 'potongan', 'baris');
            }

            $id = (int) DB::table('pembayaran_piutang')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pelanggan' => $data['id_pelanggan'],
                'id_kas_bank' => $data['id_kas_bank'],
                'id_metode_pembayaran' => $data['id_metode_pembayaran'],
                'nomor_pembayaran' => $layanan->nomorBerikutnya($idCabang, 'PEMBAYARAN_PIUTANG', 'AR', $data['tanggal_pembayaran']),
                'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                'nomor_bukti' => $data['nomor_bukti'] ?? null,
                'total_pembayaran' => round($total, 2),
                'biaya_pembayaran' => $data['biaya_pembayaran'] ?? 0,
                'status_pembayaran' => 'DRAF',
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                DB::table('pembayaran_piutang_detail')->insert([
                    'id_pembayaran_piutang' => $id,
                    'id_piutang_pelanggan' => $item['piutang']->id_piutang_pelanggan,
                    'nilai_dialokasikan' => $item['alokasi'],
                    'potongan_pembayaran' => $item['potongan'],
                    'keterangan' => $item['baris']['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PIUTANG', 'TAMBAH', 'pembayaran_piutang', $id, 'Membuat draf pembayaran piutang pelanggan.');

        return back()->with('berhasil', 'Pembayaran piutang berhasil dibuat sebagai draf.');
    }

    public function setujui(Request $request, int $id, LayananPenjualan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PEMBAYARAN_PIUTANG_SETUJUI');
        $idCabang = $this->idCabang($request);

        DB::transaction(function () use ($request, $id, $idCabang, $layanan): void {
            $pembayaran = DB::table('pembayaran_piutang')->where('id_pembayaran_piutang', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $pembayaran) {
                abort(404);
            }
            if ($pembayaran->status_pembayaran !== 'DRAF') {
                throw ValidationException::withMessages(['status_pembayaran' => 'Hanya pembayaran DRAF yang dapat disetujui.']);
            }

            $detail = DB::table('pembayaran_piutang_detail')->where('id_pembayaran_piutang', $id)->lockForUpdate()->get();
            foreach ($detail as $item) {
                $piutang = DB::table('piutang_pelanggan')->where('id_piutang_pelanggan', $item->id_piutang_pelanggan)->where('id_cabang', $idCabang)->where('id_pelanggan', $pembayaran->id_pelanggan)->lockForUpdate()->first();
                if (! $piutang || in_array($piutang->status_piutang, ['LUNAS', 'DIBATALKAN'], true)) {
                    throw ValidationException::withMessages(['id_piutang_pelanggan' => 'Piutang sudah lunas, dibatalkan, atau tidak valid.']);
                }
                $nilai = round((float) $item->nilai_dialokasikan + (float) $item->potongan_pembayaran, 2);
                if ($nilai > (float) $piutang->sisa_piutang + 0.009) {
                    throw ValidationException::withMessages(['nilai_dialokasikan' => 'Alokasi pembayaran melebihi sisa piutang saat diproses.']);
                }
                DB::table('piutang_pelanggan')->where('id_piutang_pelanggan', $piutang->id_piutang_pelanggan)->update([
                    'nilai_pembayaran' => round((float) $piutang->nilai_pembayaran + $nilai, 2),
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);
                $layanan->perbaruiPiutang((int) $piutang->id_piutang_pelanggan, (int) $request->user()->id_pengguna);
            }

            DB::table('pembayaran_piutang')->where('id_pembayaran_piutang', $id)->update([
                'status_pembayaran' => 'DISETUJUI',
                'id_pengguna_penyetuju' => $request->user()->id_pengguna,
                'tanggal_disetujui' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PIUTANG', 'SETUJUI', 'pembayaran_piutang', $id, 'Menyetujui pembayaran dan alokasi piutang.');

        return back()->with('berhasil', 'Pembayaran piutang berhasil disetujui.');
    }

    public function batalkan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PEMBAYARAN_PIUTANG_KELOLA');
        $idCabang = $this->idCabang($request);
        DB::transaction(function () use ($request, $id, $idCabang): void {
            $pembayaran = DB::table('pembayaran_piutang')->where('id_pembayaran_piutang', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $pembayaran) {
                abort(404);
            }
            if ($pembayaran->status_pembayaran !== 'DRAF') {
                throw ValidationException::withMessages(['status_pembayaran' => 'Hanya pembayaran DRAF yang dapat dibatalkan.']);
            }
            DB::table('pembayaran_piutang')->where('id_pembayaran_piutang', $id)->update([
                'status_pembayaran' => 'DIBATALKAN',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });
        $audit->catat($request, 'PIUTANG', 'BATALKAN', 'pembayaran_piutang', $id, 'Membatalkan pembayaran piutang.');

        return back()->with('berhasil', 'Pembayaran piutang berhasil dibatalkan.');
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
