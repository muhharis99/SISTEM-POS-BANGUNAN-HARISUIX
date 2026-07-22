<?php

namespace App\Http\Controllers;

use App\Services\AuditAktivitas;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    private const DATA_DILINDUNGI = [
        'jenis_pelanggan' => ['kolom' => 'kode_jenis_pelanggan', 'nilai' => ['UMUM']],
        'metode_pembayaran' => ['kolom' => 'kode_metode_pembayaran', 'nilai' => ['TUNAI']],
        'tarif_pajak' => ['kolom' => 'kode_tarif_pajak', 'nilai' => ['NON_PAJAK']],
    ];
    public function index(Request $request, string $slug): View
    {
        $konfigurasi = $this->konfigurasi($slug);
        $this->pastikanAkses($request, $konfigurasi['izin_lihat']);

        $pencarian = trim((string) $request->query('pencarian'));
        $query = $this->queryDasar($request, $konfigurasi);

        if ($pencarian !== '') {
            $query->where(function (Builder $sub) use ($konfigurasi, $pencarian): void {
                foreach ($konfigurasi['pencarian'] as $indeks => $kolom) {
                    $metode = $indeks === 0 ? 'where' : 'orWhere';
                    $sub->{$metode}($kolom, 'like', "%{$pencarian}%");
                }
            });
        }

        foreach ($konfigurasi['urutan'] as $kolom) {
            $query->orderBy($kolom);
        }

        $data = $query->paginate(15)->withQueryString();
        $opsiRelasi = $this->opsiRelasi($request, $konfigurasi);

        return view('master.index', compact('slug', 'konfigurasi', 'data', 'opsiRelasi', 'pencarian'));
    }

    public function simpan(Request $request, string $slug, AuditAktivitas $audit): RedirectResponse
    {
        $konfigurasi = $this->konfigurasi($slug);
        $this->pastikanAkses($request, $konfigurasi['izin_kelola']);
        $data = $this->validasi($request, $konfigurasi, null);

        $id = DB::transaction(function () use ($request, $konfigurasi, $data, $audit): int {
            $payload = $this->payload($request, $konfigurasi, $data);
            $payload['status_aktif'] = 1;
            $payload['created_at'] = now();
            $payload['created_by'] = $request->user()->id_pengguna;

            $id = (int) DB::table($konfigurasi['tabel'])->insertGetId($payload);
            $audit->catat($request, strtoupper(str_replace('-', '_', $konfigurasi['tabel'])), 'TAMBAH', $konfigurasi['tabel'], $id, 'Menambahkan '.$konfigurasi['judul'].'.', null, $payload);

            return $id;
        });

        return back()->with('berhasil', $konfigurasi['judul'].' berhasil ditambahkan. ID '.$id.'.');
    }

    public function ubah(Request $request, string $slug, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $konfigurasi = $this->konfigurasi($slug);
        $this->pastikanAkses($request, $konfigurasi['izin_kelola']);
        $lama = $this->temukan($request, $konfigurasi, $id);
        $data = $this->validasi($request, $konfigurasi, $id);
        $this->pastikanDataDilindungiTidakDiubah($konfigurasi, $lama, $data);

        DB::transaction(function () use ($request, $konfigurasi, $data, $lama, $id, $audit): void {
            $payload = $this->payload($request, $konfigurasi, $data);
            $payload['updated_at'] = now();
            $payload['updated_by'] = $request->user()->id_pengguna;

            DB::table($konfigurasi['tabel'])
                ->where($konfigurasi['kunci'], $id)
                ->update($payload);

            $audit->catat($request, strtoupper(str_replace('-', '_', $konfigurasi['tabel'])), 'UBAH', $konfigurasi['tabel'], $id, 'Mengubah '.$konfigurasi['judul'].'.', (array) $lama, $payload);
        });

        return back()->with('berhasil', $konfigurasi['judul'].' berhasil diperbarui.');
    }

    public function ubahStatus(Request $request, string $slug, int $id, AuditAktivitas $audit): RedirectResponse
    {
        $konfigurasi = $this->konfigurasi($slug);
        $this->pastikanAkses($request, $konfigurasi['izin_kelola']);
        $lama = $this->temukan($request, $konfigurasi, $id);
        $this->pastikanBolehUbahStatus($konfigurasi, $lama);
        $statusBaru = ! (bool) $lama->status_aktif;

        DB::table($konfigurasi['tabel'])
            ->where($konfigurasi['kunci'], $id)
            ->update([
                'status_aktif' => $statusBaru,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_pengguna,
            ]);

        $audit->catat($request, strtoupper(str_replace('-', '_', $konfigurasi['tabel'])), 'UBAH', $konfigurasi['tabel'], $id, 'Mengubah status '.$konfigurasi['judul'].'.', ['status_aktif' => $lama->status_aktif], ['status_aktif' => $statusBaru]);

        return back()->with('berhasil', 'Status '.$konfigurasi['judul'].' berhasil diubah.');
    }


    private function pastikanDataDilindungiTidakDiubah(array $konfigurasi, object $lama, array $data): void
    {
        $pelindung = self::DATA_DILINDUNGI[$konfigurasi['tabel']] ?? null;
        if ($pelindung === null || ! in_array($lama->{$pelindung['kolom']}, $pelindung['nilai'], true)) {
            return;
        }

        if (($data[$pelindung['kolom']] ?? null) !== $lama->{$pelindung['kolom']}) {
            throw ValidationException::withMessages([$pelindung['kolom'] => 'Kode data bawaan sistem tidak boleh diubah.']);
        }

        if ($konfigurasi['tabel'] === 'tarif_pajak' && (float) ($data['persen_pajak'] ?? 0) !== 0.0) {
            throw ValidationException::withMessages(['persen_pajak' => 'Tarif NON_PAJAK harus tetap 0%.']);
        }

        if ($konfigurasi['tabel'] === 'metode_pembayaran' && ($data['kelompok_pembayaran'] ?? null) !== 'TUNAI') {
            throw ValidationException::withMessages(['kelompok_pembayaran' => 'Metode TUNAI harus tetap berada pada kelompok Tunai.']);
        }
    }

    private function pastikanBolehUbahStatus(array $konfigurasi, object $item): void
    {
        $pelindung = self::DATA_DILINDUNGI[$konfigurasi['tabel']] ?? null;
        if ($pelindung !== null && in_array($item->{$pelindung['kolom']}, $pelindung['nilai'], true)) {
            abort(422, 'Data bawaan sistem ini harus tetap aktif.');
        }
    }

    private function konfigurasi(string $slug): array
    {
        $konfigurasi = config('master_data.'.$slug);
        abort_unless(is_array($konfigurasi), 404);

        return $konfigurasi;
    }

    private function pastikanAkses(Request $request, string $izin): void
    {
        abort_unless($request->user()?->memilikiHakAkses($izin, (int) $request->session()->get('id_cabang_aktif')), 403);
    }

    private function queryDasar(Request $request, array $konfigurasi): Builder
    {
        return DB::table($konfigurasi['tabel'])
            ->whereNull($konfigurasi['tabel'].'.deleted_at')
            ->when($konfigurasi['cabang'] ?? false, fn (Builder $query) => $query->where($konfigurasi['tabel'].'.id_cabang', (int) $request->session()->get('id_cabang_aktif')));
    }

    private function temukan(Request $request, array $konfigurasi, int $id): object
    {
        $item = $this->queryDasar($request, $konfigurasi)
            ->where($konfigurasi['kunci'], $id)
            ->first();

        abort_if($item === null, 404);

        return $item;
    }

    private function validasi(Request $request, array $konfigurasi, ?int $id): array
    {
        $aturan = [];

        foreach ($konfigurasi['kolom'] as $nama => $kolom) {
            $aturanKolom = $kolom['aturan'] ?? [($kolom['nullable'] ?? false) ? 'nullable' : 'required'];

            if ($kolom['tipe'] === 'relation') {
                $relasi = $kolom['relasi'];
                $aturanKolom[] = Rule::exists($relasi['tabel'], $relasi['kunci'])->whereNull('deleted_at');
            }

            if ($kolom['unik'] ?? false) {
                $unik = Rule::unique($konfigurasi['tabel'], $nama)
                    ->ignore($id, $konfigurasi['kunci']);

                if (($kolom['unik_cabang'] ?? false) && ($konfigurasi['cabang'] ?? false)) {
                    $unik->where('id_cabang', (int) $request->session()->get('id_cabang_aktif'));
                }

                $aturanKolom[] = $unik;
            }

            $aturan[$nama] = $aturanKolom;
        }

        $data = $request->validate($aturan);
        $this->validasiTambahan($request, $konfigurasi, $data, $id);

        return $data;
    }

    private function validasiTambahan(Request $request, array $konfigurasi, array $data, ?int $id): void
    {
        if (in_array($konfigurasi['tabel'], ['kategori_barang', 'kategori_biaya'], true)) {
            $induk = $data['id_kategori_induk'] ?? null;
            if ($induk !== null && $id !== null && (int) $induk === $id) {
                throw ValidationException::withMessages(['id_kategori_induk' => 'Kategori tidak boleh menjadi induk bagi dirinya sendiri.']);
            }

            if ($induk !== null && $id !== null && $this->indukAdalahTurunan($konfigurasi, $id, (int) $induk)) {
                throw ValidationException::withMessages(['id_kategori_induk' => 'Kategori induk tidak boleh berasal dari turunannya sendiri.']);
            }
        }

        if ($konfigurasi['tabel'] === 'kas_bank' && ($data['jenis_kas_bank'] ?? null) === 'BANK') {
            $pesan = [];
            foreach (['nama_bank', 'nomor_rekening', 'nama_pemilik_rekening'] as $kolom) {
                if (trim((string) ($data[$kolom] ?? '')) === '') {
                    $pesan[$kolom] = 'Data ini wajib diisi untuk jenis Bank.';
                }
            }
            if ($pesan !== []) {
                throw ValidationException::withMessages($pesan);
            }
        }
    }

    private function indukAdalahTurunan(array $konfigurasi, int $id, int $calonInduk): bool
    {
        $kunci = $konfigurasi['kunci'];
        $tabel = $konfigurasi['tabel'];
        $saatIni = $calonInduk;
        $pengaman = 0;

        while ($saatIni > 0 && $pengaman < 100) {
            if ($saatIni === $id) {
                return true;
            }

            $saatIni = (int) (DB::table($tabel)->where($kunci, $saatIni)->value('id_kategori_induk') ?? 0);
            $pengaman++;
        }

        return false;
    }

    private function payload(Request $request, array $konfigurasi, array $data): array
    {
        $payload = [];
        foreach ($konfigurasi['kolom'] as $nama => $kolom) {
            $nilai = $data[$nama] ?? ($kolom['default'] ?? null);
            $payload[$nama] = is_string($nilai) && trim($nilai) === '' ? null : $nilai;
        }

        if ($konfigurasi['cabang'] ?? false) {
            $payload['id_cabang'] = (int) $request->session()->get('id_cabang_aktif');
        }

        return $payload;
    }

    private function opsiRelasi(Request $request, array $konfigurasi): array
    {
        $hasil = [];

        foreach ($konfigurasi['kolom'] as $nama => $kolom) {
            if ($kolom['tipe'] !== 'relation') {
                continue;
            }

            $relasi = $kolom['relasi'];
            $hasil[$nama] = DB::table($relasi['tabel'])
                ->whereNull('deleted_at')
                ->where('status_aktif', 1)
                ->when($relasi['cabang'] ?? false, fn (Builder $query) => $query->where('id_cabang', (int) $request->session()->get('id_cabang_aktif')))
                ->orderBy($relasi['label'])
                ->get([$relasi['kunci'], $relasi['label']]);
        }

        return $hasil;
    }
}
