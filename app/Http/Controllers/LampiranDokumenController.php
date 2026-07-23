<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lampiran\SimpanLampiranDokumenRequest;
use App\Services\AuditAktivitas;
use App\Services\LayananLampiranDokumen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LampiranDokumenController extends Controller
{
    public function index(Request $request, LayananLampiranDokumen $layanan): View
    {
        $this->pastikanAkses($request, 'LAMPIRAN_LIHAT');

        $jenisDokumen = strtoupper(trim((string) $request->query('jenis_dokumen')));
        $idDokumen = (int) $request->query('id_dokumen');
        $pencarian = trim((string) $request->query('pencarian'));
        $jenisTersedia = $layanan->jenisTersediaUntuk($request);

        $lampiran = $layanan->queryLampiranCabang($request)
            ->leftJoin('pengguna as p', 'p.id_pengguna', '=', 'lampiran_dokumen.created_by')
            ->select('lampiran_dokumen.*', 'p.nama_tampilan as nama_pengunggah')
            ->when($jenisDokumen !== '', fn ($query) => $query->where('lampiran_dokumen.jenis_dokumen', $jenisDokumen))
            ->when($idDokumen > 0, fn ($query) => $query->where('lampiran_dokumen.id_dokumen', $idDokumen))
            ->when($pencarian !== '', fn ($query) => $query->where(function ($sub) use ($pencarian): void {
                $sub->where('lampiran_dokumen.nama_berkas_asli', 'like', "%{$pencarian}%")
                    ->orWhere('lampiran_dokumen.keterangan', 'like', "%{$pencarian}%")
                    ->orWhere('lampiran_dokumen.jenis_berkas', 'like', "%{$pencarian}%");
            }))
            ->orderByDesc('lampiran_dokumen.created_at')
            ->paginate(25)
            ->withQueryString();

        return view('lampiran.index', [
            'lampiran' => $lampiran,
            'jenisTersedia' => $jenisTersedia,
            'jenisDokumen' => $jenisDokumen,
            'idDokumen' => $idDokumen > 0 ? $idDokumen : null,
            'pencarian' => $pencarian,
        ]);
    }

    public function simpan(
        SimpanLampiranDokumenRequest $request,
        LayananLampiranDokumen $layanan,
        AuditAktivitas $audit,
    ): RedirectResponse {
        $this->pastikanAkses($request, 'LAMPIRAN_UNGGAH');
        $data = $request->validated();

        $lampiran = $layanan->simpan(
            $request,
            $request->file('berkas'),
            $data['jenis_dokumen'],
            (int) $data['id_dokumen'],
            $data['keterangan'] ?? null,
        );

        $audit->catat(
            $request,
            'LAMPIRAN',
            'TAMBAH',
            'lampiran_dokumen',
            (int) $lampiran->id_lampiran_dokumen,
            'Mengunggah lampiran '.$lampiran->nama_berkas_asli.' untuk '.$lampiran->jenis_dokumen.' #'.$lampiran->id_dokumen.'.',
            null,
            $lampiran->only(['jenis_dokumen', 'id_dokumen', 'nama_berkas_asli', 'jenis_berkas', 'ukuran_berkas', 'keterangan']),
        );

        return back()->with('berhasil', 'Lampiran berhasil diunggah ke storage privat.');
    }

    public function unduh(
        Request $request,
        int $id,
        LayananLampiranDokumen $layanan,
        AuditAktivitas $audit,
    ): StreamedResponse {
        $this->pastikanAkses($request, 'LAMPIRAN_UNDUH');
        $lampiran = $layanan->pastikanLampiran($request, $id);

        if (! Storage::disk('local')->exists($lampiran->lokasi_berkas)) {
            abort(404, 'Berkas fisik tidak ditemukan.');
        }

        $audit->catat(
            $request,
            'LAMPIRAN',
            'UNDUH',
            'lampiran_dokumen',
            $id,
            'Mengunduh lampiran '.$lampiran->nama_berkas_asli.'.',
        );

        return Storage::disk('local')->download(
            $lampiran->lokasi_berkas,
            $lampiran->nama_berkas_asli,
            ['Content-Type' => $lampiran->jenis_berkas ?: 'application/octet-stream'],
        );
    }

    public function hapus(
        Request $request,
        int $id,
        LayananLampiranDokumen $layanan,
        AuditAktivitas $audit,
    ): RedirectResponse {
        $this->pastikanAkses($request, 'LAMPIRAN_HAPUS');
        $lampiran = $layanan->hapus($request, $id);

        $audit->catat(
            $request,
            'LAMPIRAN',
            'HAPUS',
            'lampiran_dokumen',
            $id,
            'Menghapus secara logis lampiran '.$lampiran->nama_berkas_asli.'.',
            $lampiran->only(['jenis_dokumen', 'id_dokumen', 'nama_berkas_asli', 'jenis_berkas', 'ukuran_berkas', 'keterangan']),
        );

        return back()->with('berhasil', 'Lampiran berhasil dihapus secara logis.');
    }

    private function pastikanAkses(Request $request, string $kode): void
    {
        $idCabang = (int) $request->session()->get('id_cabang_aktif');
        abort_unless($idCabang > 0 && $request->user()?->memilikiHakAkses($kode, $idCabang), 403);
    }
}
