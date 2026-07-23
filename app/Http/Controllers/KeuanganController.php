<?php

namespace App\Http\Controllers;

use App\Http\Requests\Keuangan\SimpanAkunKeuanganRequest;
use App\Http\Requests\Keuangan\SimpanJurnalUmumRequest;
use App\Http\Requests\Keuangan\SimpanPemetaanAkunRequest;
use App\Http\Requests\Keuangan\SimpanTransaksiKasRequest;
use App\Services\AuditAktivitas;
use App\Services\LayananKeuangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KeuanganController extends Controller
{
    public function index(Request $request, LayananKeuangan $layanan): View
    {
        $this->pastikanAkses($request, 'KEUANGAN_LIHAT');
        $idCabang = $this->idCabang($request);
        $tanggalAwal = $request->date('tanggal_awal')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $tanggalAkhir = $request->date('tanggal_akhir')?->toDateString() ?? now()->endOfMonth()->toDateString();
        if ($tanggalAwal > $tanggalAkhir) {
            [$tanggalAwal, $tanggalAkhir] = [$tanggalAkhir, $tanggalAwal];
        }

        $akun = DB::table('akun_keuangan as a')
            ->leftJoin('akun_keuangan as i', 'i.id_akun_keuangan', '=', 'a.id_akun_induk')
            ->whereNull('a.deleted_at')
            ->select('a.*', 'i.kode_akun as kode_akun_induk', 'i.nama_akun as nama_akun_induk')
            ->orderBy('a.kode_akun')
            ->get();

        $pemetaan = DB::table('pemetaan_akun as p')
            ->join('akun_keuangan as a', 'a.id_akun_keuangan', '=', 'p.id_akun_keuangan')
            ->where(function ($query) use ($idCabang): void {
                $query->where('p.id_cabang', $idCabang)->orWhereNull('p.id_cabang');
            })
            ->whereNull('a.deleted_at')
            ->select('p.*', 'a.kode_akun', 'a.nama_akun')
            ->orderByRaw('p.id_cabang IS NULL')
            ->orderBy('p.kunci_pemetaan')
            ->get();

        $transaksiKas = DB::table('transaksi_kas as t')
            ->join('kas_bank as k', 'k.id_kas_bank', '=', 't.id_kas_bank')
            ->leftJoin('kas_bank as kt', 'kt.id_kas_bank', '=', 't.id_kas_bank_tujuan')
            ->leftJoin('kategori_biaya as b', 'b.id_kategori_biaya', '=', 't.id_kategori_biaya')
            ->where('t.id_cabang', $idCabang)
            ->whereBetween(DB::raw('DATE(t.tanggal_transaksi)'), [$tanggalAwal, $tanggalAkhir])
            ->whereNull('t.deleted_at')
            ->select('t.*', 'k.nama_kas_bank', 'kt.nama_kas_bank as nama_kas_bank_tujuan', 'b.nama_kategori_biaya')
            ->orderByDesc('t.tanggal_transaksi')
            ->orderByDesc('t.id_transaksi_kas')
            ->limit(100)
            ->get();

        $jurnal = DB::table('jurnal_umum as j')
            ->leftJoin('jurnal_umum_detail as d', 'd.id_jurnal_umum', '=', 'j.id_jurnal_umum')
            ->where('j.id_cabang', $idCabang)
            ->whereBetween('j.tanggal_jurnal', [$tanggalAwal, $tanggalAkhir])
            ->whereNull('j.deleted_at')
            ->groupBy(
                'j.id_jurnal_umum', 'j.id_cabang', 'j.nomor_jurnal', 'j.tanggal_jurnal',
                'j.sumber_jurnal', 'j.id_sumber', 'j.nomor_sumber', 'j.keterangan',
                'j.status_jurnal', 'j.id_pengguna_pemosting', 'j.tanggal_diposting',
                'j.created_at', 'j.created_by', 'j.updated_at', 'j.updated_by',
                'j.deleted_at', 'j.deleted_by'
            )
            ->select('j.*', DB::raw('COALESCE(SUM(d.debet), 0) as total_debet'), DB::raw('COALESCE(SUM(d.kredit), 0) as total_kredit'))
            ->orderByDesc('j.tanggal_jurnal')
            ->orderByDesc('j.id_jurnal_umum')
            ->limit(100)
            ->get();

        $saldoAkun = DB::table('akun_keuangan as a')
            ->leftJoin('jurnal_umum_detail as d', 'd.id_akun_keuangan', '=', 'a.id_akun_keuangan')
            ->leftJoin('jurnal_umum as j', 'j.id_jurnal_umum', '=', 'd.id_jurnal_umum')
            ->where('a.akun_rincian', 1)
            ->where('a.status_aktif', 1)
            ->whereNull('a.deleted_at')
            ->groupBy('a.id_akun_keuangan', 'a.kode_akun', 'a.nama_akun', 'a.kelompok_akun', 'a.saldo_normal')
            ->select(
                'a.id_akun_keuangan', 'a.kode_akun', 'a.nama_akun', 'a.kelompok_akun', 'a.saldo_normal',
                DB::raw("COALESCE(SUM(CASE WHEN j.id_cabang = {$idCabang} AND j.status_jurnal = 'DIPOSTING' AND j.tanggal_jurnal BETWEEN ".DB::getPdo()->quote($tanggalAwal).' AND '.DB::getPdo()->quote($tanggalAkhir).' THEN d.debet ELSE 0 END), 0) as total_debet'),
                DB::raw("COALESCE(SUM(CASE WHEN j.id_cabang = {$idCabang} AND j.status_jurnal = 'DIPOSTING' AND j.tanggal_jurnal BETWEEN ".DB::getPdo()->quote($tanggalAwal).' AND '.DB::getPdo()->quote($tanggalAkhir).' THEN d.kredit ELSE 0 END), 0) as total_kredit')
            )
            ->orderBy('a.kode_akun')
            ->get()
            ->map(function (object $baris): object {
                $baris->saldo = $baris->saldo_normal === 'DEBET'
                    ? round((float) $baris->total_debet - (float) $baris->total_kredit, 2)
                    : round((float) $baris->total_kredit - (float) $baris->total_debet, 2);

                return $baris;
            });

        $totalPendapatan = (float) $saldoAkun->where('kelompok_akun', 'PENDAPATAN')->sum('saldo');
        $totalBeban = (float) $saldoAkun->where('kelompok_akun', 'BEBAN')->sum('saldo');
        $labaRugi = round($totalPendapatan - $totalBeban, 2);
        $totalAset = (float) $saldoAkun->where('kelompok_akun', 'ASET')->sum('saldo');
        $totalKewajiban = (float) $saldoAkun->where('kelompok_akun', 'KEWAJIBAN')->sum('saldo');
        $totalModal = (float) $saldoAkun->where('kelompok_akun', 'MODAL')->sum('saldo');

        return view('keuangan.index', [
            'akun' => $akun,
            'akunRincian' => $akun->where('akun_rincian', 1)->where('status_aktif', 1)->values(),
            'akunInduk' => $akun->where('akun_rincian', 0)->where('status_aktif', 1)->values(),
            'pemetaan' => $pemetaan,
            'transaksiKas' => $transaksiKas,
            'jurnal' => $jurnal,
            'saldoKasBank' => $layanan->saldoKasBank($idCabang, $tanggalAkhir),
            'saldoAkun' => $saldoAkun,
            'kasBankPilihan' => DB::table('kas_bank')->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_kas_bank')->get(),
            'kategoriBiayaPilihan' => DB::table('kategori_biaya')->where('status_aktif', 1)->whereNull('deleted_at')->orderBy('nama_kategori_biaya')->get(),
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
            'ringkasan' => [
                'total_saldo_kas_bank' => (float) $layanan->saldoKasBank($idCabang, $tanggalAkhir)->sum('saldo_berjalan'),
                'total_pendapatan' => $totalPendapatan,
                'total_beban' => $totalBeban,
                'laba_rugi' => $labaRugi,
                'total_aset' => $totalAset,
                'total_kewajiban_modal' => round($totalKewajiban + $totalModal + $labaRugi, 2),
            ],
        ]);
    }

    public function simpanAkun(SimpanAkunKeuanganRequest $request, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'AKUN_KEUANGAN_KELOLA');
        $data = $request->validated();
        $this->pastikanKodeAkunUnik($data['kode_akun']);
        $this->pastikanIndukAkun(isset($data['id_akun_induk']) ? (int) $data['id_akun_induk'] : null, null);

        $id = (int) DB::table('akun_keuangan')->insertGetId([
            'id_akun_induk' => $data['id_akun_induk'] ?? null,
            'kode_akun' => strtoupper(trim($data['kode_akun'])),
            'nama_akun' => trim($data['nama_akun']),
            'kelompok_akun' => $data['kelompok_akun'],
            'saldo_normal' => $data['saldo_normal'],
            'akun_rincian' => $data['akun_rincian'],
            'status_aktif' => $data['status_aktif'],
            'created_at' => now(),
            'created_by' => $request->user()->id_pengguna,
        ]);

        $audit->catat($request, 'KEUANGAN', 'TAMBAH', 'akun_keuangan', $id, 'Menambahkan akun keuangan.');

        return back()->with('berhasil', 'Akun keuangan berhasil ditambahkan.');
    }

    public function ubahAkun(SimpanAkunKeuanganRequest $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'AKUN_KEUANGAN_KELOLA');
        $akun = DB::table('akun_keuangan')->where('id_akun_keuangan', $id)->whereNull('deleted_at')->first();
        if (! $akun) {
            abort(404);
        }

        $data = $request->validated();
        $this->pastikanKodeAkunUnik($data['kode_akun'], $id);
        $this->pastikanIndukAkun(isset($data['id_akun_induk']) ? (int) $data['id_akun_induk'] : null, $id);

        if ((int) $data['akun_rincian'] === 1 && DB::table('akun_keuangan')->where('id_akun_induk', $id)->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages(['akun_rincian' => 'Akun yang masih memiliki anak tidak dapat diubah menjadi akun rincian.']);
        }

        DB::table('akun_keuangan')->where('id_akun_keuangan', $id)->update([
            'id_akun_induk' => $data['id_akun_induk'] ?? null,
            'kode_akun' => strtoupper(trim($data['kode_akun'])),
            'nama_akun' => trim($data['nama_akun']),
            'kelompok_akun' => $data['kelompok_akun'],
            'saldo_normal' => $data['saldo_normal'],
            'akun_rincian' => $data['akun_rincian'],
            'status_aktif' => $data['status_aktif'],
            'updated_at' => now(),
            'updated_by' => $request->user()->id_pengguna,
        ]);

        $audit->catat($request, 'KEUANGAN', 'UBAH', 'akun_keuangan', $id, 'Mengubah akun keuangan.', $akun, $data);

        return back()->with('berhasil', 'Akun keuangan berhasil diperbarui.');
    }

    public function simpanPemetaan(SimpanPemetaanAkunRequest $request, LayananKeuangan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'PEMETAAN_AKUN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validated();
        $akun = $layanan->akunRincian((int) $data['id_akun_keuangan']);
        $kunci = strtoupper(trim($data['kunci_pemetaan']));

        DB::table('pemetaan_akun')->updateOrInsert(
            ['id_cabang' => $idCabang, 'kunci_pemetaan' => $kunci],
            [
                'id_akun_keuangan' => $akun->id_akun_keuangan,
                'keterangan' => $data['keterangan'] ?? null,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]
        );

        $id = (int) DB::table('pemetaan_akun')->where('id_cabang', $idCabang)->where('kunci_pemetaan', $kunci)->value('id_pemetaan_akun');
        $audit->catat($request, 'KEUANGAN', 'UBAH', 'pemetaan_akun', $id, 'Mengatur pemetaan akun cabang.');

        return back()->with('berhasil', 'Pemetaan akun berhasil disimpan.');
    }

    public function simpanTransaksiKas(SimpanTransaksiKasRequest $request, LayananKeuangan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'TRANSAKSI_KAS_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validated();
        $layanan->kasBank($idCabang, (int) $data['id_kas_bank']);

        if ($data['jenis_transaksi'] === 'PINDAH') {
            if (empty($data['id_kas_bank_tujuan'])) {
                throw ValidationException::withMessages(['id_kas_bank_tujuan' => 'Kas/bank tujuan wajib dipilih untuk transaksi pindah.']);
            }
            $layanan->kasBank($idCabang, (int) $data['id_kas_bank_tujuan']);
        }

        if (! empty($data['id_kategori_biaya'])) {
            $kategoriValid = DB::table('kategori_biaya')->where('id_kategori_biaya', $data['id_kategori_biaya'])->where('status_aktif', 1)->whereNull('deleted_at')->exists();
            if (! $kategoriValid) {
                throw ValidationException::withMessages(['id_kategori_biaya' => 'Kategori biaya tidak valid atau tidak aktif.']);
            }
        }

        $id = (int) DB::table('transaksi_kas')->insertGetId([
            'id_cabang' => $idCabang,
            'id_kas_bank' => $data['id_kas_bank'],
            'id_kas_bank_tujuan' => $data['id_kas_bank_tujuan'] ?? null,
            'id_kategori_biaya' => $data['id_kategori_biaya'] ?? null,
            'nomor_transaksi' => $layanan->nomorBerikutnya($idCabang, 'TRANSAKSI_KAS', 'KB', $data['tanggal_transaksi']),
            'tanggal_transaksi' => $data['tanggal_transaksi'],
            'jenis_transaksi' => $data['jenis_transaksi'],
            'sumber_transaksi' => $data['sumber_transaksi'] ?? null,
            'id_sumber' => $data['id_sumber'] ?? null,
            'nomor_sumber' => $data['nomor_sumber'] ?? null,
            'nilai_transaksi' => round((float) $data['nilai_transaksi'], 2),
            'keterangan' => $data['keterangan'],
            'status_transaksi' => 'DRAF',
            'created_at' => now(),
            'created_by' => $request->user()->id_pengguna,
        ]);

        $audit->catat($request, 'KEUANGAN', 'TAMBAH', 'transaksi_kas', $id, 'Membuat draf transaksi kas/bank.');

        return back()->with('berhasil', 'Transaksi kas/bank berhasil dibuat sebagai draf.');
    }

    public function setujuiTransaksiKas(Request $request, int $id, LayananKeuangan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'TRANSAKSI_KAS_SETUJUI');
        $idJurnal = $layanan->setujuiTransaksiKas($this->idCabang($request), $id, (int) $request->user()->id_pengguna);
        $audit->catat($request, 'KEUANGAN', 'SETUJUI', 'transaksi_kas', $id, 'Menyetujui transaksi kas/bank dan membentuk jurnal otomatis. Jurnal #'.$idJurnal.'.');

        return back()->with('berhasil', 'Transaksi kas/bank disetujui dan jurnal otomatis berhasil diposting.');
    }

    public function batalkanTransaksiKas(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'TRANSAKSI_KAS_KELOLA');
        $idCabang = $this->idCabang($request);

        DB::transaction(function () use ($request, $id, $idCabang): void {
            $transaksi = DB::table('transaksi_kas')->where('id_transaksi_kas', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $transaksi) {
                abort(404);
            }
            if ($transaksi->status_transaksi !== 'DRAF') {
                throw ValidationException::withMessages(['status_transaksi' => 'Hanya transaksi kas DRAF yang dapat dibatalkan.']);
            }

            DB::table('transaksi_kas')->where('id_transaksi_kas', $id)->update([
                'status_transaksi' => 'DIBATALKAN',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'KEUANGAN', 'BATALKAN', 'transaksi_kas', $id, 'Membatalkan transaksi kas/bank.');

        return back()->with('berhasil', 'Transaksi kas/bank berhasil dibatalkan.');
    }

    public function simpanJurnal(SimpanJurnalUmumRequest $request, LayananKeuangan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'JURNAL_UMUM_KELOLA');
        $data = $request->validated();
        $id = DB::transaction(fn (): int => $layanan->simpanJurnal(
            $this->idCabang($request),
            $data['tanggal_jurnal'],
            $data['keterangan'],
            $data['detail'],
            (int) $request->user()->id_pengguna,
            $data['sumber_jurnal'] ?? 'MANUAL',
            $data['id_sumber'] ?? null,
            $data['nomor_sumber'] ?? null,
            false
        ));

        $audit->catat($request, 'KEUANGAN', 'TAMBAH', 'jurnal_umum', $id, 'Membuat draf jurnal umum.');

        return back()->with('berhasil', 'Jurnal umum berhasil dibuat sebagai draf.');
    }

    public function postingJurnal(Request $request, int $id, LayananKeuangan $layanan, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'JURNAL_UMUM_POSTING');
        $layanan->postingJurnal($this->idCabang($request), $id, (int) $request->user()->id_pengguna);
        $audit->catat($request, 'KEUANGAN', 'SETUJUI', 'jurnal_umum', $id, 'Memposting jurnal umum.');

        return back()->with('berhasil', 'Jurnal umum berhasil diposting.');
    }

    public function batalkanJurnal(Request $request, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $this->pastikanAkses($request, 'JURNAL_UMUM_KELOLA');
        $idCabang = $this->idCabang($request);

        DB::transaction(function () use ($request, $id, $idCabang): void {
            $jurnal = DB::table('jurnal_umum')->where('id_jurnal_umum', $id)->where('id_cabang', $idCabang)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $jurnal) {
                abort(404);
            }
            if ($jurnal->status_jurnal !== 'DRAF') {
                throw ValidationException::withMessages(['status_jurnal' => 'Hanya jurnal DRAF yang dapat dibatalkan.']);
            }

            DB::table('jurnal_umum')->where('id_jurnal_umum', $id)->update([
                'status_jurnal' => 'DIBATALKAN',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);
        });

        $audit->catat($request, 'KEUANGAN', 'BATALKAN', 'jurnal_umum', $id, 'Membatalkan jurnal umum.');

        return back()->with('berhasil', 'Jurnal umum berhasil dibatalkan.');
    }

    private function pastikanKodeAkunUnik(string $kode, ?int $abaikanId = null): void
    {
        $query = DB::table('akun_keuangan')->where('kode_akun', strtoupper(trim($kode)))->whereNull('deleted_at');
        if ($abaikanId) {
            $query->where('id_akun_keuangan', '!=', $abaikanId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['kode_akun' => 'Kode akun sudah digunakan.']);
        }
    }

    private function pastikanIndukAkun(?int $idInduk, ?int $idAkun): void
    {
        if (! $idInduk) {
            return;
        }
        if ($idAkun && $idInduk === $idAkun) {
            throw ValidationException::withMessages(['id_akun_induk' => 'Akun tidak dapat menjadi induk bagi dirinya sendiri.']);
        }

        $induk = DB::table('akun_keuangan')->where('id_akun_keuangan', $idInduk)->where('akun_rincian', 0)->whereNull('deleted_at')->first();
        if (! $induk) {
            throw ValidationException::withMessages(['id_akun_induk' => 'Akun induk tidak valid atau masih berupa akun rincian.']);
        }

        $penunjuk = $induk;
        for ($i = 0; $i < 100 && $penunjuk?->id_akun_induk; $i++) {
            if ($idAkun && (int) $penunjuk->id_akun_induk === $idAkun) {
                throw ValidationException::withMessages(['id_akun_induk' => 'Relasi induk akan membentuk siklus akun.']);
            }
            $penunjuk = DB::table('akun_keuangan')->where('id_akun_keuangan', $penunjuk->id_akun_induk)->whereNull('deleted_at')->first();
        }
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
