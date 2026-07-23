<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use App\Services\LayananPembelian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HutangPemasokController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'HUTANG_PEMASOK_LIHAT');
        $idCabang = $this->idCabang($request);
        $pencarian = trim((string) $request->query('pencarian'));

        $hutang = DB::table('hutang_pemasok as h')
            ->join('pemasok as p', 'p.id_pemasok', '=', 'h.id_pemasok')
            ->join('faktur_pembelian as f', 'f.id_faktur_pembelian', '=', 'h.id_faktur_pembelian')
            ->where('h.id_cabang', $idCabang)
            ->when($pencarian !== '', fn ($q) => $q->where(function ($s) use ($pencarian): void {
                $s->where('p.nama_pemasok', 'like', "%{$pencarian}%")
                    ->orWhere('f.nomor_faktur_internal', 'like', "%{$pencarian}%")
                    ->orWhere('f.nomor_faktur_pemasok', 'like', "%{$pencarian}%");
            }))
            ->select('h.*', 'p.nama_pemasok', 'f.nomor_faktur_internal', 'f.nomor_faktur_pemasok')
            ->orderByRaw("CASE WHEN h.status_hutang IN ('BELUM_LUNAS','SEBAGIAN') THEN 0 ELSE 1 END")
            ->orderBy('h.tanggal_jatuh_tempo')
            ->paginate(25)
            ->withQueryString();

        $pembayaran = DB::table('pembayaran_hutang as h')
            ->join('pemasok as p', 'p.id_pemasok', '=', 'h.id_pemasok')
            ->join('kas_bank as k', 'k.id_kas_bank', '=', 'h.id_kas_bank')
            ->join('metode_pembayaran as m', 'm.id_metode_pembayaran', '=', 'h.id_metode_pembayaran')
            ->where('h.id_cabang', $idCabang)
            ->whereNull('h.deleted_at')
            ->select('h.*', 'p.nama_pemasok', 'k.nama_kas_bank', 'm.nama_metode_pembayaran')
            ->orderByDesc('h.id_pembayaran_hutang')
            ->limit(25)
            ->get();

        $ringkasan = [
            'total_hutang' => (float) DB::table('hutang_pemasok')->where('id_cabang', $idCabang)->whereIn('status_hutang', ['BELUM_LUNAS', 'SEBAGIAN'])->sum('sisa_hutang'),
            'jatuh_tempo' => (float) DB::table('hutang_pemasok')->where('id_cabang', $idCabang)->whereIn('status_hutang', ['BELUM_LUNAS', 'SEBAGIAN'])->whereDate('tanggal_jatuh_tempo', '<=', now()->toDateString())->sum('sisa_hutang'),
            'pembayaran_bulan_ini' => (float) DB::table('pembayaran_hutang')->where('id_cabang', $idCabang)->where('status_pembayaran', 'DISETUJUI')->whereBetween('tanggal_pembayaran', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('total_pembayaran'),
        ];

        return view('pembelian.hutang', [
            'hutang' => $hutang,
            'pembayaran' => $pembayaran,
            'ringkasan' => $ringkasan,
            'pencarian' => $pencarian,
            'pemasokPilihan' => DB::table('pemasok')->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_pemasok')->get(),
            'kasBankPilihan' => DB::table('kas_bank')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_kas_bank')->get(),
            'metodePembayaranPilihan' => DB::table('metode_pembayaran')->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_metode_pembayaran')->get(),
            'hutangPilihan' => DB::table('hutang_pemasok as h')->join('pemasok as p', 'p.id_pemasok', '=', 'h.id_pemasok')->join('faktur_pembelian as f', 'f.id_faktur_pembelian', '=', 'h.id_faktur_pembelian')->where('h.id_cabang', $idCabang)->whereIn('h.status_hutang', ['BELUM_LUNAS', 'SEBAGIAN'])->select('h.*', 'p.nama_pemasok', 'f.nomor_faktur_internal')->orderBy('h.tanggal_jatuh_tempo')->get(),
        ]);
    }

    public function simpan(Request $request, LayananPembelian $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PEMBAYARAN_HUTANG_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validate([
            'id_pemasok' => ['required', 'integer'],
            'id_kas_bank' => ['required', 'integer'],
            'id_metode_pembayaran' => ['required', 'integer'],
            'tanggal_pembayaran' => ['required', 'date'],
            'nomor_bukti' => ['nullable', 'string', 'max:100'],
            'biaya_pembayaran' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_hutang_pemasok' => ['required', 'integer'],
            'detail.*.nilai_dialokasikan' => ['required', 'numeric', 'gt:0'],
            'detail.*.potongan_pembayaran' => ['nullable', 'numeric', 'min:0'],
            'detail.*.keterangan' => ['nullable', 'string'],
        ]);
        $layanan->pastikanPemasok((int) $data['id_pemasok']);

        $kasValid = DB::table('kas_bank')->where('id_kas_bank', $data['id_kas_bank'])->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
        $metodeValid = DB::table('metode_pembayaran')->where('id_metode_pembayaran', $data['id_metode_pembayaran'])->where('status_aktif', 1)->whereNull('deleted_at')->exists();
        abort_unless($kasValid && $metodeValid, 422, 'Kas/bank atau metode pembayaran tidak valid.');

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan): int {
            $total = 0.0;
            $terpakai = [];
            foreach ($data['detail'] as $index => $baris) {
                $idHutang = (int) $baris['id_hutang_pemasok'];
                if (isset($terpakai[$idHutang])) {
                    throw ValidationException::withMessages(["detail.{$index}.id_hutang_pemasok" => 'Satu hutang tidak boleh dialokasikan lebih dari satu kali.']);
                }
                $terpakai[$idHutang] = true;
                $hutang = DB::table('hutang_pemasok')->where('id_hutang_pemasok', $idHutang)->where('id_cabang', $idCabang)->where('id_pemasok', $data['id_pemasok'])->whereIn('status_hutang', ['BELUM_LUNAS', 'SEBAGIAN'])->first();
                if (! $hutang) {
                    throw ValidationException::withMessages(["detail.{$index}.id_hutang_pemasok" => 'Hutang tidak valid, berbeda pemasok, atau sudah lunas.']);
                }
                $alokasi = (float) $baris['nilai_dialokasikan'];
                $potongan = (float) ($baris['potongan_pembayaran'] ?? 0);
                if ($alokasi + $potongan > (float) $hutang->sisa_hutang + 0.009) {
                    throw ValidationException::withMessages(["detail.{$index}.nilai_dialokasikan" => 'Alokasi dan potongan melebihi sisa hutang.']);
                }
                $total += $alokasi;
            }

            $id = (int) DB::table('pembayaran_hutang')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pemasok' => $data['id_pemasok'],
                'id_kas_bank' => $data['id_kas_bank'],
                'id_metode_pembayaran' => $data['id_metode_pembayaran'],
                'nomor_pembayaran' => $layanan->nomorBerikutnya($idCabang, 'PEMBAYARAN_HUTANG', 'PH', $data['tanggal_pembayaran']),
                'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                'nomor_bukti' => $data['nomor_bukti'] ?? null,
                'total_pembayaran' => round($total, 2),
                'biaya_pembayaran' => $data['biaya_pembayaran'] ?? 0,
                'status_pembayaran' => 'DRAF',
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($data['detail'] as $baris) {
                DB::table('pembayaran_hutang_detail')->insert([
                    'id_pembayaran_hutang' => $id,
                    'id_hutang_pemasok' => $baris['id_hutang_pemasok'],
                    'nilai_dialokasikan' => $baris['nilai_dialokasikan'],
                    'potongan_pembayaran' => $baris['potongan_pembayaran'] ?? 0,
                    'keterangan' => $baris['keterangan'] ?? null,
                    'created_at' => now(),
                    'created_by' => $request->user()->id_pengguna,
                ]);
            }

            return $id;
        });

        $audit->catat($request, 'PEMBELIAN', 'TAMBAH', 'pembayaran_hutang', $id, 'Membuat draf pembayaran hutang pemasok.');

        return back()->with('berhasil', 'Pembayaran hutang berhasil dibuat sebagai draf.');
    }

    public function setujui(Request $request, int $id, LayananPembelian $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PEMBAYARAN_HUTANG_SETUJUI');
        $idCabang = $this->idCabang($request);

        DB::transaction(function () use ($request, $id, $idCabang, $layanan): void {
            $pembayaran = DB::table('pembayaran_hutang')->where('id_pembayaran_hutang', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            abort_if(! $pembayaran, 404);
            abort_unless($pembayaran->status_pembayaran === 'DRAF', 422, 'Pembayaran sudah diproses atau dibatalkan.');

            $detail = DB::table('pembayaran_hutang_detail')->where('id_pembayaran_hutang', $id)->lockForUpdate()->get();
            if ($detail->isEmpty()) {
                throw ValidationException::withMessages(['detail' => 'Pembayaran harus memiliki minimal satu alokasi.']);
            }

            foreach ($detail as $baris) {
                $hutang = DB::table('hutang_pemasok')->where('id_hutang_pemasok', $baris->id_hutang_pemasok)->where('id_cabang', $idCabang)->where('id_pemasok', $pembayaran->id_pemasok)->lockForUpdate()->first();
                if (! $hutang || ! in_array($hutang->status_hutang, ['BELUM_LUNAS', 'SEBAGIAN'], true)) {
                    throw ValidationException::withMessages(['detail' => 'Salah satu hutang sudah lunas atau tidak valid.']);
                }
                $alokasi = (float) $baris->nilai_dialokasikan;
                $potongan = (float) $baris->potongan_pembayaran;
                if ($alokasi + $potongan > (float) $hutang->sisa_hutang + 0.009) {
                    throw ValidationException::withMessages(['detail' => 'Alokasi pembayaran melebihi sisa hutang saat persetujuan.']);
                }
                DB::table('hutang_pemasok')->where('id_hutang_pemasok', $hutang->id_hutang_pemasok)->update([
                    'nilai_pembayaran' => round((float) $hutang->nilai_pembayaran + $alokasi, 2),
                    'nilai_penyesuaian' => round((float) $hutang->nilai_penyesuaian + $potongan, 2),
                    'updated_at' => now(),
                    'updated_by' => $request->user()->id_pengguna,
                ]);
                $layanan->perbaruiHutang((int) $hutang->id_hutang_pemasok, (int) $request->user()->id_pengguna);
            }

            DB::table('pembayaran_hutang')->where('id_pembayaran_hutang', $id)->update([
                'status_pembayaran' => 'DISETUJUI',
                'id_pengguna_penyetuju' => $request->user()->id_pengguna,
                'tanggal_disetujui' => now(),
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'PEMBELIAN', 'SETUJUI', 'pembayaran_hutang', $id, 'Menyetujui pembayaran dan memperbarui saldo hutang.');

        return back()->with('berhasil', 'Pembayaran hutang disetujui dan saldo hutang telah diperbarui.');
    }

    public function batalkan(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PEMBAYARAN_HUTANG_KELOLA');
        $idCabang = $this->idCabang($request);
        $item = DB::table('pembayaran_hutang')->where('id_pembayaran_hutang', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->first();
        abort_if(! $item, 404);
        abort_unless($item->status_pembayaran === 'DRAF', 422, 'Hanya pembayaran draf yang dapat dibatalkan.');

        DB::table('pembayaran_hutang')->where('id_pembayaran_hutang', $id)->update([
            'status_pembayaran' => 'DIBATALKAN',
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);
        $audit->catat($request, 'PEMBELIAN', 'BATAL', 'pembayaran_hutang', $id, 'Membatalkan draf pembayaran hutang.');

        return back()->with('berhasil', 'Pembayaran hutang berhasil dibatalkan.');
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
