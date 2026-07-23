<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananPersediaan
{
    public function nomorBerikutnya(int $idCabang, string $jenisDokumen, string $awalan, ?string $tanggal = null): string
    {
        $waktu = $tanggal ? Carbon::parse($tanggal) : now();
        $tahun = (int) $waktu->format('Y');
        $bulan = (int) $waktu->format('m');

        DB::table('nomor_dokumen')->insertOrIgnore([
            'id_cabang' => $idCabang,
            'jenis_dokumen' => $jenisDokumen,
            'awalan' => $awalan,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'nomor_terakhir' => 0,
            'jumlah_digit' => 5,
            'pola_nomor' => '{AWALAN}/{TAHUN}{BULAN}/{NOMOR}',
            'created_at' => now(),
        ]);

        $nomor = DB::table('nomor_dokumen')
            ->where('id_cabang', $idCabang)
            ->where('jenis_dokumen', $jenisDokumen)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->lockForUpdate()
            ->first();

        if (! $nomor) {
            throw ValidationException::withMessages([
                'nomor_dokumen' => 'Konfigurasi nomor dokumen tidak dapat disiapkan.',
            ]);
        }

        $berikutnya = (int) $nomor->nomor_terakhir + 1;
        DB::table('nomor_dokumen')->where('id_nomor_dokumen', $nomor->id_nomor_dokumen)->update([
            'nomor_terakhir' => $berikutnya,
            'updated_at' => now(),
        ]);

        return sprintf('%s/%04d%02d/%0'.$nomor->jumlah_digit.'d', $awalan, $tahun, $bulan, $berikutnya);
    }

    public function barangSatuan(int $idBarangSatuan): object
    {
        $item = DB::table('barang_satuan as bs')
            ->join('barang as b', 'b.id_barang', '=', 'bs.id_barang')
            ->join('satuan as s', 's.id_satuan', '=', 'bs.id_satuan')
            ->where('bs.id_barang_satuan', $idBarangSatuan)
            ->where('bs.status_aktif', 1)
            ->where('b.status_aktif', 1)
            ->where('b.jenis_barang', 'BARANG')
            ->whereNull('bs.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNull('s.deleted_at')
            ->select(
                'bs.*',
                'b.id_barang',
                'b.kode_barang',
                'b.nama_barang',
                'b.id_satuan_dasar',
                'b.wajib_nomor_lot',
                'b.wajib_tanggal_kedaluwarsa',
                's.jumlah_desimal'
            )
            ->first();

        if (! $item) {
            throw ValidationException::withMessages([
                'detail' => 'Barang dan satuan yang dipilih tidak valid atau tidak aktif.',
            ]);
        }

        return $item;
    }

    public function jumlahDasar(object $barangSatuan, mixed $jumlah, string $atribut = 'jumlah'): float
    {
        if (! is_numeric($jumlah) || (float) $jumlah <= 0) {
            throw ValidationException::withMessages([$atribut => 'Jumlah harus lebih besar dari nol.']);
        }

        $this->pastikanDesimal((float) $jumlah, (int) $barangSatuan->jumlah_desimal, $atribut);
        $jumlahDasar = round((float) $jumlah * (float) $barangSatuan->nilai_konversi, 3);

        if ($jumlahDasar <= 0) {
            throw ValidationException::withMessages([$atribut => 'Hasil konversi ke satuan dasar harus lebih besar dari nol.']);
        }

        return $jumlahDasar;
    }

    public function pastikanDesimal(float $jumlah, int $jumlahDesimal, string $atribut = 'jumlah'): void
    {
        $teks = rtrim(rtrim(number_format($jumlah, 12, '.', ''), '0'), '.');
        $bagian = explode('.', $teks, 2);
        $aktual = isset($bagian[1]) ? strlen($bagian[1]) : 0;

        if ($aktual > $jumlahDesimal) {
            throw ValidationException::withMessages([
                $atribut => "Jumlah maksimal memiliki {$jumlahDesimal} angka di belakang koma sesuai satuan.",
            ]);
        }
    }

    public function saldoTerkunci(int $idGudang, int $idLokasi, int $idBarang): object
    {
        DB::table('saldo_stok')->insertOrIgnore([
            'id_gudang' => $idGudang,
            'id_lokasi_gudang' => $idLokasi,
            'id_barang' => $idBarang,
            'jumlah_stok' => 0,
            'jumlah_dipesan' => 0,
            'jumlah_rusak' => 0,
            'harga_pokok_rata_rata' => 0,
            'harga_beli_terakhir' => 0,
            'updated_at' => now(),
        ]);

        return DB::table('saldo_stok')
            ->where('id_gudang', $idGudang)
            ->where('id_lokasi_gudang', $idLokasi)
            ->where('id_barang', $idBarang)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function catatMutasi(
        int $idCabang,
        int $idGudang,
        int $idLokasi,
        int $idBarang,
        float $jumlahMasuk,
        float $jumlahKeluar,
        float $hargaPokok,
        string $jenisMutasi,
        string $jenisDokumen,
        ?int $idDokumen,
        ?string $nomorDokumen,
        ?string $keterangan,
        ?int $idPengguna,
    ): object {
        $gudang = DB::table('gudang')
            ->where('id_gudang', $idGudang)
            ->where('id_cabang', $idCabang)
            ->whereNull('deleted_at')
            ->first();
        $lokasiValid = DB::table('lokasi_gudang')
            ->where('id_lokasi_gudang', $idLokasi)
            ->where('id_gudang', $idGudang)
            ->whereNull('deleted_at')
            ->exists();

        if (! $gudang || ! $lokasiValid) {
            throw ValidationException::withMessages(['lokasi' => 'Gudang atau lokasi tidak sesuai dengan cabang aktif.']);
        }

        if ($jumlahMasuk < 0 || $jumlahKeluar < 0 || ($jumlahMasuk <= 0 && $jumlahKeluar <= 0) || ($jumlahMasuk > 0 && $jumlahKeluar > 0)) {
            throw ValidationException::withMessages(['jumlah' => 'Mutasi harus berupa satu arah masuk atau keluar dengan jumlah positif.']);
        }

        $saldo = $this->saldoTerkunci($idGudang, $idLokasi, $idBarang);
        $stokSebelum = (float) $saldo->jumlah_stok;
        $stokSesudah = round($stokSebelum + $jumlahMasuk - $jumlahKeluar, 3);
        $koreksiFisik = in_array($jenisMutasi, ['PENYESUAIAN_KELUAR', 'STOK_OPNAME'], true);
        $dapatKeluar = $koreksiFisik
            ? $stokSebelum
            : round($stokSebelum - (float) $saldo->jumlah_dipesan - (float) $saldo->jumlah_rusak, 3);

        if ($jumlahKeluar > 0 && $dapatKeluar + 0.0001 < $jumlahKeluar) {
            throw ValidationException::withMessages([
                'jumlah' => 'Stok pada lokasi asal tidak mencukupi. Tersedia untuk keluar: '.number_format($dapatKeluar, 3, ',', '.').'.',
            ]);
        }

        $hargaLama = (float) $saldo->harga_pokok_rata_rata;
        $hargaEfektif = $hargaPokok > 0 ? $hargaPokok : $hargaLama;
        $hargaBaru = $hargaLama;

        if ($jumlahMasuk > 0) {
            $pembagi = $stokSebelum + $jumlahMasuk;
            $hargaBaru = $pembagi > 0
                ? (($stokSebelum * $hargaLama) + ($jumlahMasuk * $hargaEfektif)) / $pembagi
                : $hargaEfektif;
        }

        if ($stokSesudah <= 0) {
            $hargaBaru = 0;
        }

        $jumlahRusak = (float) $saldo->jumlah_rusak;
        if ($gudang->jenis_gudang === 'RUSAK') {
            $jumlahRusak = $stokSesudah;
        } elseif ($jumlahRusak > $stokSesudah) {
            $jumlahRusak = max(0, $stokSesudah);
        }

        DB::table('saldo_stok')->where('id_saldo_stok', $saldo->id_saldo_stok)->update([
            'jumlah_stok' => $stokSesudah,
            'jumlah_rusak' => round($jumlahRusak, 3),
            'harga_pokok_rata_rata' => round($hargaBaru, 4),
            'harga_beli_terakhir' => in_array($jenisMutasi, ['STOK_AWAL', 'PEMBELIAN'], true) && $hargaEfektif > 0
                ? round($hargaEfektif, 4)
                : $saldo->harga_beli_terakhir,
            'tanggal_mutasi_terakhir' => now(),
            'updated_at' => now(),
            'updated_by' => $idPengguna,
        ]);

        DB::table('mutasi_stok')->insert([
            'id_cabang' => $idCabang,
            'id_gudang' => $idGudang,
            'id_lokasi_gudang' => $idLokasi,
            'id_barang' => $idBarang,
            'tanggal_mutasi' => now(),
            'jenis_mutasi' => $jenisMutasi,
            'jenis_dokumen' => $jenisDokumen,
            'id_dokumen' => $idDokumen,
            'nomor_dokumen' => $nomorDokumen,
            'jumlah_masuk' => round($jumlahMasuk, 3),
            'jumlah_keluar' => round($jumlahKeluar, 3),
            'harga_pokok' => round($hargaEfektif, 4),
            'nilai_mutasi' => round(($jumlahMasuk - $jumlahKeluar) * $hargaEfektif, 2),
            'saldo_setelah' => $stokSesudah,
            'keterangan' => $keterangan,
            'created_at' => now(),
            'created_by' => $idPengguna,
        ]);

        return (object) [
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSesudah,
            'harga_pokok' => round($hargaEfektif, 4),
        ];
    }
}
