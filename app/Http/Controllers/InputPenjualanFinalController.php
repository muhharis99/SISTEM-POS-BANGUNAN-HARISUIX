<?php

namespace App\Http\Controllers;

use App\Http\Requests\Penjualan\SimpanPenawaranFinalRequest;
use App\Http\Requests\Penjualan\SimpanPenjualanFinalRequest;
use App\Http\Requests\Penjualan\SimpanPesananFinalRequest;
use App\Services\AuditAktivitas;
use App\Services\LayananPenjualan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InputPenjualanFinalController extends Controller
{
    public function simpanPenawaran(
        SimpanPenawaranFinalRequest $request,
        LayananPenjualan $layanan,
        AuditAktivitas $audit
    ): RedirectResponse {
        $this->pastikanAkses($request, 'PENAWARAN_PENJUALAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validated();
        $pelanggan = $layanan->pelanggan(isset($data['id_pelanggan']) ? (int) $data['id_pelanggan'] : null, false);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan, $pelanggan): int {
            [$rincian, $total] = $this->rincianHarga($data['detail'], $layanan);
            $biayaPengiriman = round((float) ($data['biaya_pengiriman'] ?? 0), 2);

            $idPenawaran = (int) DB::table('penawaran_penjualan')->insertGetId([
                'id_cabang' => $idCabang,
                'id_pelanggan' => $pelanggan?->id_pelanggan,
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

            $this->simpanDetailHarga('penawaran_penjualan_detail', 'id_penawaran_penjualan', $idPenawaran, $rincian, $request);

            return $idPenawaran;
        });

        $audit->catat($request, 'PENJUALAN', 'TAMBAH', 'penawaran_penjualan', $id, 'Membuat draf penawaran dengan Form Request dan perhitungan server-side.');

        return back()->with('berhasil', 'Penawaran berhasil dibuat sebagai draf.');
    }

    public function simpanPesanan(
        SimpanPesananFinalRequest $request,
        LayananPenjualan $layanan,
        AuditAktivitas $audit
    ): RedirectResponse {
        $this->pastikanAkses($request, 'PESANAN_PENJUALAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validated();
        $pelanggan = $layanan->pelanggan((int) $data['id_pelanggan']);
        $this->pastikanDaftarHarga($idCabang, $data['id_daftar_harga'] ?? null);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan, $pelanggan): int {
            [$rincian, $total] = $this->rincianHarga($data['detail'], $layanan);
            $biayaPengiriman = round((float) ($data['biaya_pengiriman'] ?? 0), 2);
            $biayaLain = round((float) ($data['biaya_lain'] ?? 0), 2);
            $totalBersih = round($total['bersih'] + $biayaPengiriman + $biayaLain, 2);
            $uangMuka = round((float) ($data['uang_muka'] ?? 0), 2);

            if ($uangMuka > $totalBersih + 0.009) {
                throw ValidationException::withMessages(['uang_muka' => 'Uang muka tidak boleh melebihi total bersih pesanan.']);
            }

            $idPesanan = (int) DB::table('pesanan_penjualan')->insertGetId([
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
                'lama_jatuh_tempo' => $data['cara_pembayaran'] === 'TEMPO'
                    ? ($data['lama_jatuh_tempo'] ?? $pelanggan->lama_jatuh_tempo)
                    : 0,
                'alamat_penagihan' => $data['alamat_penagihan'] ?? $pelanggan->alamat_utama,
                'alamat_pengiriman' => $data['alamat_pengiriman'] ?? $pelanggan->alamat_utama,
                'nama_penerima' => $data['nama_penerima'] ?? $pelanggan->nama_pelanggan,
                'telepon_penerima' => $data['telepon_penerima'] ?? $pelanggan->telepon,
                'total_kotor' => $total['kotor'],
                'total_potongan' => $total['potongan'],
                'total_pajak' => $total['pajak'],
                'biaya_pengiriman' => $biayaPengiriman,
                'biaya_lain' => $biayaLain,
                'total_bersih' => $totalBersih,
                'uang_muka' => $uangMuka,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                DB::table('pesanan_penjualan_detail')->insert([
                    'id_pesanan_penjualan' => $idPesanan,
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

            return $idPesanan;
        });

        $audit->catat($request, 'PENJUALAN', 'TAMBAH', 'pesanan_penjualan', $id, 'Membuat draf pesanan dengan Form Request dan validasi referensi cabang.');

        return back()->with('berhasil', 'Pesanan penjualan berhasil dibuat sebagai draf.');
    }

    public function simpanPenjualan(
        SimpanPenjualanFinalRequest $request,
        LayananPenjualan $layanan,
        AuditAktivitas $audit
    ): RedirectResponse {
        $this->pastikanAkses($request, 'TRANSAKSI_PENJUALAN_KELOLA');
        $idCabang = $this->idCabang($request);
        $data = $request->validated();
        $pelanggan = $layanan->pelanggan(isset($data['id_pelanggan']) ? (int) $data['id_pelanggan'] : null, false);

        $this->pastikanGudang($idCabang, (int) $data['id_gudang']);
        $this->pastikanDaftarHarga($idCabang, $data['id_daftar_harga'] ?? null);
        $this->pastikanKasBank($idCabang, $data['id_kas_bank'] ?? null);
        $this->pastikanMetodePembayaran($data['id_metode_pembayaran'] ?? null);

        $id = DB::transaction(function () use ($request, $data, $idCabang, $layanan, $pelanggan): int {
            $pesanan = $this->pesananSumber($idCabang, $data['id_pesanan_penjualan'] ?? null, $pelanggan?->id_pelanggan);
            [$rincian, $total] = $this->rincianPenjualan($data['detail'], $pesanan, $layanan, (int) $data['id_gudang'], $idCabang);
            $biayaPengiriman = round((float) ($data['biaya_pengiriman'] ?? 0), 2);
            $biayaLain = round((float) ($data['biaya_lain'] ?? 0), 2);
            $pembulatan = round((float) ($data['pembulatan'] ?? 0), 2);
            $totalBersih = round($total['bersih'] + $biayaPengiriman + $biayaLain + $pembulatan, 2);

            $idPenjualan = (int) DB::table('penjualan')->insertGetId([
                'id_cabang' => $idCabang,
                'id_gudang' => $data['id_gudang'],
                'id_pelanggan' => $pelanggan?->id_pelanggan,
                'id_pesanan_penjualan' => $pesanan?->id_pesanan_penjualan,
                'id_daftar_harga' => $data['id_daftar_harga'] ?? null,
                'id_kas_bank' => $data['id_kas_bank'] ?? null,
                'id_metode_pembayaran' => $data['id_metode_pembayaran'] ?? null,
                'nomor_penjualan' => $layanan->nomorBerikutnya($idCabang, 'PENJUALAN', 'INV', $data['tanggal_penjualan']),
                'tanggal_penjualan' => $data['tanggal_penjualan'],
                'tanggal_jatuh_tempo' => $data['jenis_penjualan'] === 'TEMPO' ? $data['tanggal_jatuh_tempo'] : null,
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
                'total_dibayar' => round((float) ($data['total_dibayar'] ?? 0), 2),
                'uang_kembali' => 0,
                'sisa_piutang' => $data['jenis_penjualan'] === 'TEMPO' ? $totalBersih : 0,
                'keterangan' => $data['keterangan'] ?? null,
                'created_at' => now(),
                'created_by' => $request->user()->id_pengguna,
            ]);

            foreach ($rincian as $item) {
                DB::table('penjualan_detail')->insert([
                    'id_penjualan' => $idPenjualan,
                    'id_pesanan_penjualan_detail' => $item['detailPesanan']?->id_pesanan_penjualan_detail,
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

            return $idPenjualan;
        });

        $audit->catat($request, 'PENJUALAN', 'TAMBAH', 'penjualan', $id, 'Membuat draf transaksi penjualan dengan Form Request dan validasi sumber pesanan.');

        return back()->with('berhasil', 'Transaksi penjualan berhasil dibuat sebagai draf.');
    }

    private function rincianHarga(array $detail, LayananPenjualan $layanan): array
    {
        $rincian = [];
        $total = ['kotor' => 0.0, 'potongan' => 0.0, 'pajak' => 0.0, 'bersih' => 0.0];

        foreach ($detail as $index => $baris) {
            $barangSatuan = $layanan->barangSatuan((int) $baris['id_barang_satuan']);
            $jumlahDasar = $layanan->jumlahDasar($barangSatuan, $baris['jumlah'], "detail.{$index}.jumlah");
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

    private function rincianPenjualan(array $detail, ?object $pesanan, LayananPenjualan $layanan, int $idGudang, int $idCabang): array
    {
        [$rincian, $total] = $this->rincianHarga($detail, $layanan);

        foreach ($rincian as $index => &$item) {
            $baris = $item['baris'];
            $layanan->pastikanGudangLokasi($idCabang, $idGudang, (int) $baris['id_lokasi_gudang']);
            $detailPesanan = null;

            if ($pesanan) {
                if (empty($baris['id_pesanan_penjualan_detail'])) {
                    throw ValidationException::withMessages(["detail.{$index}.id_pesanan_penjualan_detail" => 'Detail pesanan wajib dipilih karena transaksi memakai header pesanan.']);
                }

                $detailPesanan = DB::table('pesanan_penjualan_detail')
                    ->where('id_pesanan_penjualan_detail', $baris['id_pesanan_penjualan_detail'])
                    ->where('id_pesanan_penjualan', $pesanan->id_pesanan_penjualan)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (! $detailPesanan || (int) $detailPesanan->id_barang_satuan !== (int) $item['barangSatuan']->id_barang_satuan) {
                    throw ValidationException::withMessages(["detail.{$index}.id_pesanan_penjualan_detail" => 'Detail pesanan bukan sumber barang transaksi penjualan ini.']);
                }

                if ((float) $detailPesanan->jumlah_difakturkan + (float) $baris['jumlah'] > (float) $detailPesanan->jumlah + 0.0001) {
                    throw ValidationException::withMessages(["detail.{$index}.jumlah" => 'Jumlah transaksi melebihi sisa jumlah pesanan yang belum difakturkan.']);
                }
            } elseif (! empty($baris['id_pesanan_penjualan_detail'])) {
                throw ValidationException::withMessages(["detail.{$index}.id_pesanan_penjualan_detail" => 'Detail pesanan tidak boleh diisi tanpa header pesanan.']);
            }

            $item['detailPesanan'] = $detailPesanan;
        }
        unset($item);

        return [$rincian, $total];
    }

    private function simpanDetailHarga(string $tabel, string $kolomInduk, int $idInduk, array $rincian, Request $request): void
    {
        foreach ($rincian as $item) {
            DB::table($tabel)->insert([
                $kolomInduk => $idInduk,
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
    }

    private function pesananSumber(int $idCabang, mixed $idPesanan, ?int $idPelanggan): ?object
    {
        if (! $idPesanan) {
            return null;
        }

        $pesanan = DB::table('pesanan_penjualan')
            ->where('id_pesanan_penjualan', $idPesanan)
            ->where('id_cabang', $idCabang)
            ->whereNotIn('status_pesanan', ['DRAF', 'DIBATALKAN', 'SELESAI'])
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if (! $pesanan) {
            throw ValidationException::withMessages(['id_pesanan_penjualan' => 'Pesanan tidak valid, berasal dari cabang lain, belum disetujui, atau sudah selesai.']);
        }

        if ($idPelanggan && (int) $pesanan->id_pelanggan !== $idPelanggan) {
            throw ValidationException::withMessages(['id_pelanggan' => 'Pelanggan transaksi berbeda dari pelanggan pada pesanan sumber.']);
        }

        return $pesanan;
    }

    private function pastikanGudang(int $idCabang, int $idGudang): void
    {
        $valid = DB::table('gudang')->where('id_gudang', $idGudang)->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
        if (! $valid) {
            throw ValidationException::withMessages(['id_gudang' => 'Gudang tidak valid untuk cabang aktif.']);
        }
    }

    private function pastikanDaftarHarga(int $idCabang, mixed $idDaftarHarga): void
    {
        if (! $idDaftarHarga) {
            return;
        }

        $valid = DB::table('daftar_harga')->where('id_daftar_harga', $idDaftarHarga)->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
        if (! $valid) {
            throw ValidationException::withMessages(['id_daftar_harga' => 'Daftar harga tidak valid untuk cabang aktif.']);
        }
    }

    private function pastikanKasBank(int $idCabang, mixed $idKasBank): void
    {
        if (! $idKasBank) {
            return;
        }

        $valid = DB::table('kas_bank')->where('id_kas_bank', $idKasBank)->where('id_cabang', $idCabang)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
        if (! $valid) {
            throw ValidationException::withMessages(['id_kas_bank' => 'Kas atau bank tidak valid untuk cabang aktif.']);
        }
    }

    private function pastikanMetodePembayaran(mixed $idMetode): void
    {
        if (! $idMetode) {
            return;
        }

        $valid = DB::table('metode_pembayaran')->where('id_metode_pembayaran', $idMetode)->where('status_aktif', 1)->whereNull('deleted_at')->exists();
        if (! $valid) {
            throw ValidationException::withMessages(['id_metode_pembayaran' => 'Metode pembayaran tidak valid atau tidak aktif.']);
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
