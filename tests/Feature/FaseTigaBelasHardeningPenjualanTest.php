<?php

namespace Tests\Feature;

use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PenjualanDiperkuatController;
use App\Models\Cabang;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaseTigaBelasHardeningPenjualanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE13_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration hardening Fase 13 hanya dijalankan pada job MySQL khusus.');
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_controller_penjualan_diperkuat_aktif_melalui_container(): void
    {
        $this->assertInstanceOf(
            PenjualanDiperkuatController::class,
            app(PenjualanController::class)
        );
    }

    public function test_harga_retur_selalu_dihitung_dari_detail_penjualan_sumber(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('RETUR-HARGA');
        [$gudang, $lokasi] = $this->buatGudang($cabang, 'RETUR-HARGA');
        $pelanggan = $this->buatPelanggan('RETUR-HARGA');
        [$idPenjualan, $idDetail, $idPiutang] = $this->buatPenjualan(
            $cabang,
            $gudang,
            $lokasi,
            $pelanggan,
            $barangSatuan,
            'INV-F13-RETUR-HARGA',
            'TEMPO',
            10,
            10000,
            10
        );

        $response = $this->actingAs($admin)
            ->withSession($this->sessionCabang($cabang))
            ->post('/penjualan/retur', [
                'id_penjualan' => $idPenjualan,
                'id_gudang' => $gudang,
                'tanggal_retur' => '2026-07-24',
                'alasan_retur' => 'Uji nilai sumber',
                'cara_pengembalian_dana' => 'POTONG_PIUTANG',
                'detail' => [[
                    'id_penjualan_detail' => $idDetail,
                    'id_barang_satuan' => $barangSatuan,
                    'id_lokasi_gudang' => $lokasi,
                    'jumlah' => 2,
                    'harga_satuan' => 1,
                    'kondisi_barang' => 'BAIK',
                    'bisa_dijual_kembali' => 1,
                ]],
            ]);

        $response->assertSessionHasNoErrors();

        $retur = DB::table('retur_penjualan')->where('id_penjualan', $idPenjualan)->latest('id_retur_penjualan')->first();
        $detail = DB::table('retur_penjualan_detail')->where('id_retur_penjualan', $retur->id_retur_penjualan)->first();

        $this->assertEqualsWithDelta(18000, (float) $retur->total_retur, 0.01);
        $this->assertEqualsWithDelta(9000, (float) $detail->harga_satuan, 0.01);
        $this->assertEqualsWithDelta(18000, (float) $detail->total_baris, 0.01);
        $this->assertEqualsWithDelta(90000, (float) DB::table('piutang_pelanggan')->where('id_piutang_pelanggan', $idPiutang)->value('sisa_piutang'), 0.01);
    }

    public function test_pengiriman_menolak_detail_yang_bukan_milik_header_penjualan(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [, $barangSatuan] = $this->buatBarang('KIRIM-SUMBER');
        [$gudang, $lokasi] = $this->buatGudang($cabang, 'KIRIM-SUMBER');
        $pelanggan = $this->buatPelanggan('KIRIM-SUMBER');
        [$idPenjualanA] = $this->buatPenjualan($cabang, $gudang, $lokasi, $pelanggan, $barangSatuan, 'INV-F13-A');
        [, $idDetailB] = $this->buatPenjualan($cabang, $gudang, $lokasi, $pelanggan, $barangSatuan, 'INV-F13-B');

        $response = $this->actingAs($admin)
            ->withSession($this->sessionCabang($cabang))
            ->post('/penjualan/pengiriman', [
                'id_penjualan' => $idPenjualanA,
                'tanggal_pengiriman' => '2026-07-24',
                'alamat_pengiriman' => 'Alamat uji',
                'detail' => [[
                    'id_penjualan_detail' => $idDetailB,
                    'id_barang_satuan' => $barangSatuan,
                    'jumlah_dikirim' => 1,
                ]],
            ]);

        $response->assertSessionHasErrors('detail.0.id_penjualan_detail');
        $this->assertSame(0, DB::table('pengiriman')->where('id_penjualan', $idPenjualanA)->count());
    }

    public function test_pengembalian_tunai_membuat_draf_kas_dan_memblokir_penyelesaian_sebelum_disetujui(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('RETUR-TUNAI');
        [$gudang, $lokasi] = $this->buatGudang($cabang, 'RETUR-TUNAI');
        $pelanggan = $this->buatPelanggan('RETUR-TUNAI');
        $kas = $this->buatKas($cabang, 'RETUR-TUNAI');
        $this->isiStok($gudang, $lokasi, $barang, 0, 5000);
        [$idPenjualan, $idDetail] = $this->buatPenjualan(
            $cabang,
            $gudang,
            $lokasi,
            $pelanggan,
            $barangSatuan,
            'INV-F13-TUNAI',
            'TUNAI'
        );
        $session = $this->sessionCabang($cabang);

        $this->actingAs($admin)->withSession($session)->post('/penjualan/retur', [
            'id_penjualan' => $idPenjualan,
            'id_gudang' => $gudang,
            'tanggal_retur' => '2026-07-24',
            'alasan_retur' => 'Pengembalian tunai',
            'cara_pengembalian_dana' => 'TUNAI',
            'id_kas_bank' => $kas,
            'detail' => [[
                'id_penjualan_detail' => $idDetail,
                'id_barang_satuan' => $barangSatuan,
                'id_lokasi_gudang' => $lokasi,
                'jumlah' => 1,
                'harga_satuan' => 999999,
                'kondisi_barang' => 'BAIK',
                'bisa_dijual_kembali' => 1,
            ]],
        ])->assertSessionHasNoErrors();

        $idRetur = (int) DB::table('retur_penjualan')->where('id_penjualan', $idPenjualan)->max('id_retur_penjualan');
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/retur/{$idRetur}/setujui")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/retur/{$idRetur}/terima")->assertSessionHasNoErrors();

        $transaksiKas = DB::table('transaksi_kas')
            ->where('sumber_transaksi', 'RETUR_PENJUALAN')
            ->where('id_sumber', $idRetur)
            ->first();

        $this->assertNotNull($transaksiKas);
        $this->assertSame('DRAF', $transaksiKas->status_transaksi);
        $this->assertSame('KELUAR', $transaksiKas->jenis_transaksi);
        $this->assertEqualsWithDelta(10000, (float) $transaksiKas->nilai_transaksi, 0.01);

        $this->actingAs($admin)
            ->withSession($session)
            ->patch("/penjualan/retur/{$idRetur}/selesai")
            ->assertSessionHasErrors('status_retur');

        DB::table('transaksi_kas')->where('id_transaksi_kas', $transaksiKas->id_transaksi_kas)->update([
            'status_transaksi' => 'DISETUJUI',
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession($session)
            ->patch("/penjualan/retur/{$idRetur}/selesai")
            ->assertSessionHasNoErrors();

        $this->assertSame('SELESAI', DB::table('retur_penjualan')->where('id_retur_penjualan', $idRetur)->value('status_retur'));
    }

    private function adminDanCabang(): array
    {
        return [
            Pengguna::query()->where('nama_pengguna', 'admin_fase13')->firstOrFail(),
            Cabang::query()->where('kode_cabang', 'CAB-UJI')->firstOrFail(),
        ];
    }

    private function sessionCabang(Cabang $cabang): array
    {
        return [
            'id_cabang_aktif' => $cabang->id_cabang,
            'nama_cabang_aktif' => $cabang->nama_cabang,
        ];
    }

    private function buatBarang(string $kode): array
    {
        $kategori = (int) DB::table('kategori_barang')->insertGetId([
            'kode_kategori' => 'KAT-'.$kode,
            'nama_kategori' => 'Kategori '.$kode,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $satuan = (int) DB::table('satuan')->insertGetId([
            'kode_satuan' => 'ST-'.$kode,
            'nama_satuan' => 'Satuan '.$kode,
            'jumlah_desimal' => 0,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $barang = (int) DB::table('barang')->insertGetId([
            'id_kategori_barang' => $kategori,
            'id_satuan_dasar' => $satuan,
            'kode_barang' => 'BRG-'.$kode,
            'nama_barang' => 'Barang '.$kode,
            'jenis_barang' => 'BARANG',
            'metode_persediaan' => 'RATA_RATA',
            'bisa_dibeli' => 1,
            'bisa_dijual' => 1,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $barangSatuan = (int) DB::table('barang_satuan')->insertGetId([
            'id_barang' => $barang,
            'id_satuan' => $satuan,
            'nilai_konversi' => 1,
            'harga_beli_acuan' => 5000,
            'harga_jual_acuan' => 10000,
            'satuan_utama_pembelian' => 1,
            'satuan_utama_penjualan' => 1,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);

        return [$barang, $barangSatuan];
    }

    private function buatGudang(Cabang $cabang, string $kode): array
    {
        $gudang = (int) DB::table('gudang')->insertGetId([
            'id_cabang' => $cabang->id_cabang,
            'kode_gudang' => 'G-'.$kode,
            'nama_gudang' => 'Gudang '.$kode,
            'jenis_gudang' => 'UTAMA',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $lokasi = (int) DB::table('lokasi_gudang')->insertGetId([
            'id_gudang' => $gudang,
            'kode_lokasi' => 'L-'.$kode,
            'nama_lokasi' => 'Lokasi '.$kode,
            'jenis_lokasi' => 'AREA_UMUM',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);

        return [$gudang, $lokasi];
    }

    private function buatPelanggan(string $kode): int
    {
        $jenis = (int) DB::table('jenis_pelanggan')->where('kode_jenis_pelanggan', 'UMUM')->value('id_jenis_pelanggan');

        return (int) DB::table('pelanggan')->insertGetId([
            'id_jenis_pelanggan' => $jenis,
            'kode_pelanggan' => 'CUS-'.$kode,
            'nama_pelanggan' => 'Pelanggan '.$kode,
            'alamat_utama' => 'Alamat pelanggan',
            'batas_piutang' => 1000000,
            'lama_jatuh_tempo' => 30,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
    }

    private function buatKas(Cabang $cabang, string $kode): int
    {
        return (int) DB::table('kas_bank')->insertGetId([
            'id_cabang' => $cabang->id_cabang,
            'kode_kas_bank' => 'KAS-'.$kode,
            'nama_kas_bank' => 'Kas '.$kode,
            'jenis_kas_bank' => 'KAS',
            'saldo_awal' => 0,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
    }

    private function buatPenjualan(
        Cabang $cabang,
        int $gudang,
        int $lokasi,
        int $pelanggan,
        int $barangSatuan,
        string $nomor,
        string $jenis = 'TEMPO',
        float $jumlah = 10,
        float $harga = 10000,
        float $potonganPersen = 0
    ): array {
        $totalKotor = round($jumlah * $harga, 2);
        $totalPotongan = round($totalKotor * $potonganPersen / 100, 2);
        $totalBersih = round($totalKotor - $totalPotongan, 2);
        $status = $jenis === 'TEMPO' ? 'DISETUJUI' : 'LUNAS';

        $idPenjualan = (int) DB::table('penjualan')->insertGetId([
            'id_cabang' => $cabang->id_cabang,
            'id_gudang' => $gudang,
            'id_pelanggan' => $pelanggan,
            'nomor_penjualan' => $nomor,
            'tanggal_penjualan' => '2026-07-24 09:00:00',
            'jenis_penjualan' => $jenis,
            'status_penjualan' => $status,
            'status_pengiriman' => 'BELUM_DIKIRIM',
            'total_kotor' => $totalKotor,
            'total_potongan' => $totalPotongan,
            'total_pajak' => 0,
            'biaya_pengiriman' => 0,
            'biaya_lain' => 0,
            'pembulatan' => 0,
            'total_bersih' => $totalBersih,
            'total_dibayar' => $jenis === 'TEMPO' ? 0 : $totalBersih,
            'uang_kembali' => 0,
            'sisa_piutang' => $jenis === 'TEMPO' ? $totalBersih : 0,
            'created_at' => now(),
        ]);

        $idDetail = (int) DB::table('penjualan_detail')->insertGetId([
            'id_penjualan' => $idPenjualan,
            'id_barang_satuan' => $barangSatuan,
            'id_lokasi_gudang' => $lokasi,
            'nilai_konversi' => 1,
            'jumlah' => $jumlah,
            'jumlah_dasar' => $jumlah,
            'harga_satuan' => $harga,
            'potongan_persen' => $potonganPersen,
            'potongan_nilai' => $totalPotongan,
            'pajak_persen' => 0,
            'pajak_nilai' => 0,
            'total_baris' => $totalBersih,
            'harga_pokok' => 5000,
            'total_harga_pokok' => round($jumlah * 5000, 2),
            'laba_kotor' => round($totalBersih - ($jumlah * 5000), 2),
            'created_at' => now(),
        ]);

        $idPiutang = null;
        if ($jenis === 'TEMPO') {
            $idPiutang = (int) DB::table('piutang_pelanggan')->insertGetId([
                'id_cabang' => $cabang->id_cabang,
                'id_pelanggan' => $pelanggan,
                'id_penjualan' => $idPenjualan,
                'tanggal_piutang' => '2026-07-24',
                'tanggal_jatuh_tempo' => '2026-08-23',
                'nilai_awal' => $totalBersih,
                'nilai_pembayaran' => 0,
                'nilai_retur' => 0,
                'nilai_penyesuaian' => 0,
                'sisa_piutang' => $totalBersih,
                'status_piutang' => 'BELUM_LUNAS',
                'created_at' => now(),
            ]);
        }

        return [$idPenjualan, $idDetail, $idPiutang];
    }

    private function isiStok(int $gudang, int $lokasi, int $barang, float $jumlah, float $hpp): void
    {
        DB::table('saldo_stok')->insert([
            'id_gudang' => $gudang,
            'id_lokasi_gudang' => $lokasi,
            'id_barang' => $barang,
            'jumlah_stok' => $jumlah,
            'jumlah_dipesan' => 0,
            'jumlah_rusak' => 0,
            'harga_pokok_rata_rata' => $hpp,
            'harga_beli_terakhir' => $hpp,
            'updated_at' => now(),
        ]);
    }
}
