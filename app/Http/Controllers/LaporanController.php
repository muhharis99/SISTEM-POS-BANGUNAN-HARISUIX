<?php

namespace App\Http\Controllers;

use App\Http\Requests\Laporan\FilterLaporanRequest;
use App\Services\AuditAktivitas;
use App\Services\LayananLaporanOperasional;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    private const IZIN_JENIS = [
        'penjualan' => 'LAPORAN_PENJUALAN_LIHAT',
        'pembelian' => 'LAPORAN_PEMBELIAN_LIHAT',
        'persediaan' => 'LAPORAN_PERSEDIAAN_LIHAT',
        'hutang' => 'LAPORAN_HUTANG_PIUTANG_LIHAT',
        'piutang' => 'LAPORAN_HUTANG_PIUTANG_LIHAT',
        'kas' => 'KEUANGAN_LIHAT',
    ];

    private const JUDUL_JENIS = [
        'penjualan' => 'Laporan Penjualan',
        'pembelian' => 'Laporan Pembelian',
        'persediaan' => 'Laporan Persediaan',
        'hutang' => 'Laporan Hutang Pemasok',
        'piutang' => 'Laporan Piutang Pelanggan',
        'kas' => 'Laporan Kas dan Bank',
    ];

    public function index(
        FilterLaporanRequest $request,
        LayananLaporanOperasional $layanan,
        AuditAktivitas $audit
    ): View|RedirectResponse {
        $data = $request->validated();
        $jenis = $data['jenis_laporan'];
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $this->pastikanBoleh($request, $jenis, $idCabang);

        $baris = $layanan->laporan(
            $jenis,
            $idCabang,
            $data['tanggal_awal'],
            $data['tanggal_akhir'],
            $data['pencarian'] ?: null
        );

        $audit->catat(
            $request,
            'LAPORAN',
            'LIHAT',
            null,
            null,
            self::JUDUL_JENIS[$jenis].' periode '.$data['tanggal_awal'].' s.d. '.$data['tanggal_akhir'],
            null,
            [
                'jenis_laporan' => $jenis,
                'tanggal_awal' => $data['tanggal_awal'],
                'tanggal_akhir' => $data['tanggal_akhir'],
                'pencarian' => $data['pencarian'],
                'jumlah_baris' => $baris->count(),
            ]
        );

        return view('laporan.index', [
            'jenis' => $jenis,
            'judulLaporan' => self::JUDUL_JENIS[$jenis],
            'baris' => $baris,
            'filter' => $data,
            'ringkasan' => $layanan->ringkasan($idCabang, $data['tanggal_awal'], $data['tanggal_akhir']),
            'daftarJenis' => $this->daftarJenisYangBoleh($request, $idCabang),
        ]);
    }

    public function unduh(
        FilterLaporanRequest $request,
        LayananLaporanOperasional $layanan,
        AuditAktivitas $audit
    ): StreamedResponse {
        $data = $request->validated();
        $jenis = $data['jenis_laporan'];
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        $this->pastikanBoleh($request, $jenis, $idCabang);

        abort_unless(
            $request->user()->memilikiHakAkses('LAPORAN_OPERASIONAL_UNDUH', $idCabang),
            403
        );

        $audit->catat(
            $request,
            'LAPORAN',
            'UNDUH',
            null,
            null,
            'Ekspor '.self::JUDUL_JENIS[$jenis].' ke CSV',
            null,
            $data
        );

        $namaBerkas = sprintf(
            'laporan-%s-%s-sampai-%s.csv',
            $jenis,
            $data['tanggal_awal'],
            $data['tanggal_akhir']
        );

        return response()->streamDownload(function () use ($layanan, $jenis, $idCabang, $data): void {
            $keluaran = fopen('php://output', 'wb');
            fwrite($keluaran, "\xEF\xBB\xBF");
            fputcsv($keluaran, $layanan->headerEkspor($jenis), ';');

            foreach ($layanan->barisEkspor(
                $jenis,
                $idCabang,
                $data['tanggal_awal'],
                $data['tanggal_akhir'],
                $data['pencarian'] ?: null
            ) as $baris) {
                fputcsv($keluaran, $baris, ';');
            }

            fclose($keluaran);
        }, $namaBerkas, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function pastikanBoleh(FilterLaporanRequest $request, string $jenis, int $idCabang): void
    {
        $izin = self::IZIN_JENIS[$jenis] ?? null;
        abort_unless($izin && $request->user()->memilikiHakAkses($izin, $idCabang), 403);
    }

    private function daftarJenisYangBoleh(FilterLaporanRequest $request, int $idCabang): array
    {
        $hasil = [];

        foreach (self::IZIN_JENIS as $jenis => $izin) {
            if ($request->user()->memilikiHakAkses($izin, $idCabang)) {
                $hasil[$jenis] = self::JUDUL_JENIS[$jenis];
            }
        }

        return $hasil;
    }
}
