<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Services\AuditAktivitas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $this->pastikanAkses($request, 'AUDIT_LIHAT');
        $filter = $this->filter($request);

        $aktivitas = $this->queryAktivitas($request, $filter)
            ->latest('log_aktivitas.tanggal_aktivitas')
            ->paginate(25)
            ->withQueryString();

        $idCabang = $this->idCabang($request);

        return view('audit.index', [
            'aktivitas' => $aktivitas,
            'filter' => $filter,
            'modulPilihan' => LogAktivitas::query()
                ->where(fn ($query) => $query->whereNull('id_cabang')->orWhere('id_cabang', $idCabang))
                ->select('nama_modul')
                ->distinct()
                ->orderBy('nama_modul')
                ->pluck('nama_modul'),
            'jenisPilihan' => [
                'MASUK', 'KELUAR', 'TAMBAH', 'UBAH', 'HAPUS', 'LIHAT',
                'CETAK', 'UNDUH', 'SETUJUI', 'BATALKAN', 'LAINNYA',
            ],
            'penggunaPilihan' => DB::table('pengguna')
                ->whereNull('deleted_at')
                ->orderBy('nama_tampilan')
                ->get(['id_pengguna', 'nama_tampilan', 'nama_pengguna']),
            'bolehLihatData' => $request->user()?->memilikiHakAkses('AUDIT_LIHAT_DATA', $idCabang) ?? false,
            'bolehUnduh' => $request->user()?->memilikiHakAkses('AUDIT_UNDUH', $idCabang) ?? false,
        ]);
    }

    public function detail(Request $request, int $id): View
    {
        $this->pastikanAkses($request, 'AUDIT_LIHAT_DATA');

        $aktivitas = $this->queryAktivitas($request, [])
            ->where('log_aktivitas.id_log_aktivitas', $id)
            ->firstOrFail();

        return view('audit.detail', compact('aktivitas'));
    }

    public function unduh(Request $request, AuditAktivitas $audit): StreamedResponse
    {
        $this->pastikanAkses($request, 'AUDIT_UNDUH');
        $filter = $this->filter($request);
        $namaBerkas = 'audit-aktivitas-'.now()->format('Ymd-His').'.csv';

        $audit->catat(
            $request,
            'AUDIT',
            'UNDUH',
            'log_aktivitas',
            null,
            'Mengunduh audit aktivitas dalam format CSV.',
            null,
            ['filter' => $filter],
        );

        return response()->streamDownload(function () use ($request, $filter): void {
            $keluaran = fopen('php://output', 'wb');
            fwrite($keluaran, "\xEF\xBB\xBF");
            fputcsv($keluaran, [
                'Waktu', 'Pengguna', 'Cabang', 'Modul', 'Aktivitas',
                'Tabel', 'ID Referensi', 'Keterangan', 'Alamat IP', 'Peramban',
            ]);

            $this->queryAktivitas($request, $filter)
                ->orderBy('log_aktivitas.id_log_aktivitas')
                ->chunkById(500, function ($baris) use ($keluaran): void {
                    foreach ($baris as $item) {
                        fputcsv($keluaran, [
                            optional($item->tanggal_aktivitas)->format('Y-m-d H:i:s'),
                            $item->nama_tampilan ?: $item->nama_pengguna ?: '-',
                            $item->nama_cabang ?: 'Global',
                            $item->nama_modul,
                            $item->jenis_aktivitas,
                            $item->nama_tabel,
                            $item->id_referensi,
                            $item->keterangan,
                            $item->alamat_ip,
                            $item->peramban,
                        ]);
                    }
                }, 'log_aktivitas.id_log_aktivitas', 'id_log_aktivitas');

            fclose($keluaran);
        }, $namaBerkas, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function queryAktivitas(Request $request, array $filter): Builder
    {
        $idCabang = $this->idCabang($request);

        return LogAktivitas::query()
            ->leftJoin('pengguna as p', 'p.id_pengguna', '=', 'log_aktivitas.id_pengguna')
            ->leftJoin('cabang as c', 'c.id_cabang', '=', 'log_aktivitas.id_cabang')
            ->where(function ($query) use ($idCabang): void {
                $query->whereNull('log_aktivitas.id_cabang')
                    ->orWhere('log_aktivitas.id_cabang', $idCabang);
            })
            ->when(($filter['tanggal_awal'] ?? '') !== '', fn ($query) => $query->whereDate('log_aktivitas.tanggal_aktivitas', '>=', $filter['tanggal_awal']))
            ->when(($filter['tanggal_akhir'] ?? '') !== '', fn ($query) => $query->whereDate('log_aktivitas.tanggal_aktivitas', '<=', $filter['tanggal_akhir']))
            ->when(($filter['id_pengguna'] ?? 0) > 0, fn ($query) => $query->where('log_aktivitas.id_pengguna', $filter['id_pengguna']))
            ->when(($filter['nama_modul'] ?? '') !== '', fn ($query) => $query->where('log_aktivitas.nama_modul', $filter['nama_modul']))
            ->when(($filter['jenis_aktivitas'] ?? '') !== '', fn ($query) => $query->where('log_aktivitas.jenis_aktivitas', $filter['jenis_aktivitas']))
            ->when(($filter['nama_tabel'] ?? '') !== '', fn ($query) => $query->where('log_aktivitas.nama_tabel', $filter['nama_tabel']))
            ->when(($filter['id_referensi'] ?? 0) > 0, fn ($query) => $query->where('log_aktivitas.id_referensi', $filter['id_referensi']))
            ->when(($filter['alamat_ip'] ?? '') !== '', fn ($query) => $query->where('log_aktivitas.alamat_ip', 'like', '%'.$filter['alamat_ip'].'%'))
            ->when(($filter['pencarian'] ?? '') !== '', fn ($query) => $query->where(function ($sub) use ($filter): void {
                $pencarian = $filter['pencarian'];
                $sub->where('log_aktivitas.nama_modul', 'like', "%{$pencarian}%")
                    ->orWhere('log_aktivitas.jenis_aktivitas', 'like', "%{$pencarian}%")
                    ->orWhere('log_aktivitas.keterangan', 'like', "%{$pencarian}%")
                    ->orWhere('log_aktivitas.alamat_ip', 'like', "%{$pencarian}%")
                    ->orWhere('p.nama_tampilan', 'like', "%{$pencarian}%")
                    ->orWhere('p.nama_pengguna', 'like', "%{$pencarian}%");
            }))
            ->select(
                'log_aktivitas.*',
                'p.nama_tampilan',
                'p.nama_pengguna',
                'c.nama_cabang',
            );
    }

    private function filter(Request $request): array
    {
        return [
            'tanggal_awal' => trim((string) $request->query('tanggal_awal')),
            'tanggal_akhir' => trim((string) $request->query('tanggal_akhir')),
            'id_pengguna' => (int) $request->query('id_pengguna'),
            'nama_modul' => strtoupper(trim((string) $request->query('nama_modul'))),
            'jenis_aktivitas' => strtoupper(trim((string) $request->query('jenis_aktivitas'))),
            'nama_tabel' => trim((string) $request->query('nama_tabel')),
            'id_referensi' => (int) $request->query('id_referensi'),
            'alamat_ip' => trim((string) $request->query('alamat_ip')),
            'pencarian' => trim((string) $request->query('pencarian')),
        ];
    }

    private function idCabang(Request $request): int
    {
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        if ($idCabang <= 0) {
            abort(403, 'Cabang aktif belum dipilih.');
        }

        return $idCabang;
    }

    private function pastikanAkses(Request $request, string $kode): void
    {
        $idCabang = $this->idCabang($request);
        abort_unless($request->user()?->memilikiHakAkses($kode, $idCabang), 403);
    }
}
