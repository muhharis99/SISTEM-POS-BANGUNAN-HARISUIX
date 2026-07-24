<?php

namespace Tests\Feature;

use App\Http\Controllers\InputPenjualanFinalController;
use App\Models\Cabang;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaseTigaBelasFinalisasiKekuranganTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE13_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test finalisasi kekurangan Fase 13 hanya dijalankan pada job MySQL khusus.');
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_route_input_penjualan_menggunakan_controller_form_request_final(): void
    {
        foreach (['/penjualan/penawaran', '/penjualan/pesanan', '/penjualan/transaksi'] as $uri) {
            $route = app('router')->getRoutes()->match(Request::create($uri, 'POST'));
            $this->assertStringContainsString(InputPenjualanFinalController::class, $route->getActionName());
        }
    }

    public function test_pemetaan_retur_dan_jurnal_refund_kas_menggunakan_akun_kontra_penjualan(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $kas = $this->buatKas($cabang, 'JURNAL');
        $idAkunRetur = (int) DB::table('akun_keuangan')->where('kode_akun', '410110')->value('id_akun_keuangan');

        $this->assertGreaterThan(0, $idAkunRetur);
        $this->assertSame(
            $idAkunRetur,
            (int) DB::table('pemetaan_akun')->whereNull('id_cabang')->where('kunci_pemetaan', 'RETUR_PENJUALAN')->value('id_akun_keuangan')
        );

        $idTransaksi = (int) DB::table('transaksi_kas')->insertGetId([
            'id_cabang' => $cabang->id_cabang,
            'id_kas_bank' => $kas,
            'nomor_transaksi' => 'KB/F13/REFUND',
            'tanggal_transaksi' => '2026-07-24',
            'jenis_transaksi' => 'KELUAR',
            'sumber_transaksi' => 'RETUR_PENJUALAN',
            'id_sumber' => 9913,
            'nomor_sumber' => 'SR/F13/9913',
            'nilai_transaksi' => 25000,
            'keterangan' => 'Refund retur pengujian',
            'status_transaksi' => 'DRAF',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession($this->sessionCabang($cabang))
            ->patch("/keuangan/transaksi-kas/{$idTransaksi}/setujui")
            ->assertSessionHasNoErrors();

        $idJurnal = (int) DB::table('jurnal_umum')->where('sumber_jurnal', 'TRANSAKSI_KAS')->where('id_sumber', $idTransaksi)->value('id_jurnal_umum');
        $this->assertGreaterThan(0, $idJurnal);
        $this->assertEqualsWithDelta(25000, (float) DB::table('jurnal_umum_detail')->where('id_jurnal_umum', $idJurnal)->where('id_akun_keuangan', $idAkunRetur)->value('debet'), 0.01);
    }

    public function test_potong_piutang_membentuk_jurnal_retur_diposting(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('PIUTANG');
        [$gudang, $lokasi] = $this->buatGudang($cabang, 'PIUTANG');
        $pelanggan = $this->buatPelanggan('PIUTANG');
        $this->isiStok($gudang, $lokasi, $barang, 10, 5000);
        [$idPenjualan, $idDetail] = $this->buatPenjualan($cabang, $gudang, $lokasi, $pelanggan, $barangSatuan, 'INV-F13-PIUTANG', 'TEMPO');
        $session = $this->sessionCabang($cabang);

        $this->actingAs($admin)->withSession($session)->post('/penjualan/retur', [
            'id_penjualan' => $idPenjualan,
            'id_gudang' => $gudang,
            'tanggal_retur' => '2026-07-24',
            'alasan_retur' => 'Retur potong piutang',
            'cara_pengembalian_dana' => 'POTONG_PIUTANG',
            'detail' => [[
                'id_penjualan_detail' => $idDetail,
                'id_barang_satuan' => $barangSatuan,
                'id_lokasi_gudang' => $lokasi,
                'jumlah' => 1,
                'harga_satuan' => 1,
                'kondisi_barang' => 'BAIK',
                'bisa_dijual_kembali' => 1,
            ]],
        ])->assertSessionHasNoErrors();

        $idRetur = (int) DB::table('retur_penjualan')->where('id_penjualan', $idPenjualan)->max('id_retur_penjualan');
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/retur/{$idRetur}/setujui")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/retur/{$idRetur}/terima")->assertSessionHasNoErrors();

        $jurnal = DB::table('jurnal_umum')->where('sumber_jurnal', 'RETUR_PENJUALAN')->where('id_sumber', $idRetur)->first();
        $this->assertNotNull($jurnal);
        $this->assertSame('DIPOSTING', $jurnal->status_jurnal);
        $this->assertEqualsWithDelta(10000, (float) DB::table('jurnal_umum_detail')->where('id_jurnal_umum', $jurnal->id_jurnal_umum)->sum('debet'), 0.01);
        $this->assertEqualsWithDelta(10000, (float) DB::table('jurnal_umum_detail')->where('id_jurnal_umum', $jurnal->id_jurnal_umum)->sum('kredit'), 0.01);
    }

    public function test_barang_pengganti_memiliki_pengiriman_stok_dan_gate_penyelesaian_lengkap(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('PENGGANTI');
        [$gudang, $lokasi] = $this->buatGudang($cabang, 'PENGGANTI');
        $pelanggan = $this->buatPelanggan('PENGGANTI');
        $this->isiStok($gudang, $lokasi, $barang, 10, 5000);
        [$idPenjualan, $idDetail] = $this->buatPenjualan($cabang, $gudang, $lokasi, $pelanggan, $barangSatuan, 'INV-F13-PENGGANTI', 'TUNAI');
        DB::table('penjualan')->where('id_penjualan', $idPenjualan)->update(['status_pengiriman' => 'DIKIRIM']);
        $this->buatPengirimanAwal($cabang, $idPenjualan, $idDetail, $barangSatuan);
        $session = $this->sessionCabang($cabang);

        $this->actingAs($admin)->withSession($session)->post('/penjualan/retur', [
            'id_penjualan' => $idPenjualan,
            'id_gudang' => $gudang,
            'tanggal_retur' => '2026-07-24',
            'alasan_retur' => 'Diganti barang baru',
            'cara_pengembalian_dana' => 'PENGGANTI_BARANG',
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

        $pengiriman = DB::table('pengiriman')->where('keterangan', '[PENGGANTI_RETUR:'.$idRetur.']')->first();
        $this->assertNotNull($pengiriman);
        $this->assertSame('DRAF', $pengiriman->status_pengiriman);
        $this->assertEqualsWithDelta(11, $this->stok($gudang, $lokasi, $barang), 0.0001);

        $this->actingAs($admin)->withSession($session)->patch("/penjualan/retur/{$idRetur}/selesai")->assertSessionHasErrors('status_retur');
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/pengiriman/{$pengiriman->id_pengiriman}/jadwalkan")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/pengiriman/{$pengiriman->id_pengiriman}/berangkat")->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(10, $this->stok($gudang, $lokasi, $barang), 0.0001);
        $this->assertSame(1, DB::table('mutasi_stok')
            ->where('jenis_mutasi', 'LAINNYA')
            ->where('jenis_dokumen', 'PENGGANTI_RETUR_KELUAR')
            ->where('id_dokumen', $pengiriman->id_pengiriman)
            ->count());

        $this->actingAs($admin)->withSession($session)->patch("/penjualan/pengiriman/{$pengiriman->id_pengiriman}/terima")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/retur/{$idRetur}/selesai")->assertSessionHasNoErrors();
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
        return ['id_cabang_aktif' => $cabang->id_cabang, 'nama_cabang_aktif' => $cabang->nama_cabang];
    }

    private function buatBarang(string $kode): array
    {
        $kategori = (int) DB::table('kategori_barang')->insertGetId(['kode_kategori' => 'KAT-F13-'.$kode, 'nama_kategori' => 'Kategori '.$kode, 'status_aktif' => 1, 'created_at' => now()]);
        $satuan = (int) DB::table('satuan')->insertGetId(['kode_satuan' => 'ST-F13-'.$kode, 'nama_satuan' => 'Satuan '.$kode, 'jumlah_desimal' => 0, 'status_aktif' => 1, 'created_at' => now()]);
        $barang = (int) DB::table('barang')->insertGetId(['id_kategori_barang' => $kategori, 'id_satuan_dasar' => $satuan, 'kode_barang' => 'BRG-F13-'.$kode, 'nama_barang' => 'Barang '.$kode, 'jenis_barang' => 'BARANG', 'metode_persediaan' => 'RATA_RATA', 'bisa_dibeli' => 1, 'bisa_dijual' => 1, 'status_aktif' => 1, 'created_at' => now()]);
        $barangSatuan = (int) DB::table('barang_satuan')->insertGetId(['id_barang' => $barang, 'id_satuan' => $satuan, 'nilai_konversi' => 1, 'harga_beli_acuan' => 5000, 'harga_jual_acuan' => 10000, 'satuan_utama_pembelian' => 1, 'satuan_utama_penjualan' => 1, 'status_aktif' => 1, 'created_at' => now()]);

        return [$barang, $barangSatuan];
    }

    private function buatGudang(Cabang $cabang, string $kode): array
    {
        $gudang = (int) DB::table('gudang')->insertGetId(['id_cabang' => $cabang->id_cabang, 'kode_gudang' => 'G-F13-'.$kode, 'nama_gudang' => 'Gudang '.$kode, 'jenis_gudang' => 'UTAMA', 'status_aktif' => 1, 'created_at' => now()]);
        $lokasi = (int) DB::table('lokasi_gudang')->insertGetId(['id_gudang' => $gudang, 'kode_lokasi' => 'L-F13-'.$kode, 'nama_lokasi' => 'Lokasi '.$kode, 'jenis_lokasi' => 'AREA_UMUM', 'status_aktif' => 1, 'created_at' => now()]);

        return [$gudang, $lokasi];
    }

    private function buatPelanggan(string $kode): int
    {
        $jenis = (int) DB::table('jenis_pelanggan')->where('kode_jenis_pelanggan', 'UMUM')->value('id_jenis_pelanggan');

        return (int) DB::table('pelanggan')->insertGetId(['id_jenis_pelanggan' => $jenis, 'kode_pelanggan' => 'CUS-F13-'.$kode, 'nama_pelanggan' => 'Pelanggan '.$kode, 'alamat_utama' => 'Alamat pelanggan', 'batas_piutang' => 1000000, 'lama_jatuh_tempo' => 30, 'status_aktif' => 1, 'created_at' => now()]);
    }

    private function buatKas(Cabang $cabang, string $kode): int
    {
        return (int) DB::table('kas_bank')->insertGetId(['id_cabang' => $cabang->id_cabang, 'kode_kas_bank' => 'KAS-F13-'.$kode, 'nama_kas_bank' => 'Kas '.$kode, 'jenis_kas_bank' => 'KAS', 'saldo_awal' => 1000000, 'status_aktif' => 1, 'created_at' => now()]);
    }

    private function buatPenjualan(Cabang $cabang, int $gudang, int $lokasi, int $pelanggan, int $barangSatuan, string $nomor, string $jenis): array
    {
        $idPenjualan = (int) DB::table('penjualan')->insertGetId([
            'id_cabang' => $cabang->id_cabang,
            'id_gudang' => $gudang,
            'id_pelanggan' => $pelanggan,
            'nomor_penjualan' => $nomor,
            'tanggal_penjualan' => '2026-07-24 09:00:00',
            'tanggal_jatuh_tempo' => $jenis === 'TEMPO' ? '2026-08-23' : null,
            'jenis_penjualan' => $jenis,
            'status_penjualan' => $jenis === 'TEMPO' ? 'DISETUJUI' : 'LUNAS',
            'status_pengiriman' => 'BELUM_DIKIRIM',
            'total_kotor' => 10000,
            'total_potongan' => 0,
            'total_pajak' => 0,
            'biaya_pengiriman' => 0,
            'biaya_lain' => 0,
            'pembulatan' => 0,
            'total_bersih' => 10000,
            'total_dibayar' => $jenis === 'TEMPO' ? 0 : 10000,
            'uang_kembali' => 0,
            'sisa_piutang' => $jenis === 'TEMPO' ? 10000 : 0,
            'created_at' => now(),
        ]);
        $idDetail = (int) DB::table('penjualan_detail')->insertGetId(['id_penjualan' => $idPenjualan, 'id_barang_satuan' => $barangSatuan, 'id_lokasi_gudang' => $lokasi, 'nilai_konversi' => 1, 'jumlah' => 1, 'jumlah_dasar' => 1, 'harga_satuan' => 10000, 'potongan_persen' => 0, 'potongan_nilai' => 0, 'pajak_persen' => 0, 'pajak_nilai' => 0, 'total_baris' => 10000, 'harga_pokok' => 5000, 'total_harga_pokok' => 5000, 'laba_kotor' => 5000, 'created_at' => now()]);

        if ($jenis === 'TEMPO') {
            DB::table('piutang_pelanggan')->insert(['id_cabang' => $cabang->id_cabang, 'id_pelanggan' => $pelanggan, 'id_penjualan' => $idPenjualan, 'tanggal_piutang' => '2026-07-24', 'tanggal_jatuh_tempo' => '2026-08-23', 'nilai_awal' => 10000, 'nilai_pembayaran' => 0, 'nilai_retur' => 0, 'nilai_penyesuaian' => 0, 'sisa_piutang' => 10000, 'status_piutang' => 'BELUM_LUNAS', 'created_at' => now()]);
        }

        return [$idPenjualan, $idDetail];
    }

    private function buatPengirimanAwal(Cabang $cabang, int $idPenjualan, int $idDetail, int $barangSatuan): void
    {
        $id = (int) DB::table('pengiriman')->insertGetId(['id_cabang' => $cabang->id_cabang, 'id_penjualan' => $idPenjualan, 'nomor_pengiriman' => 'DO-F13-AWAL-'.$idPenjualan, 'tanggal_pengiriman' => '2026-07-23', 'status_pengiriman' => 'DITERIMA', 'nama_penerima' => 'Pelanggan Uji', 'telepon_penerima' => '08123456789', 'alamat_pengiriman' => 'Alamat pelanggan', 'biaya_pengiriman' => 0, 'tanggal_berangkat' => now(), 'tanggal_tiba' => now(), 'created_at' => now()]);
        DB::table('pengiriman_detail')->insert(['id_pengiriman' => $id, 'id_penjualan_detail' => $idDetail, 'id_barang_satuan' => $barangSatuan, 'nilai_konversi' => 1, 'jumlah_dikirim' => 1, 'jumlah_dasar_dikirim' => 1, 'jumlah_diterima' => 1, 'jumlah_dasar_diterima' => 1, 'created_at' => now()]);
    }

    private function isiStok(int $gudang, int $lokasi, int $barang, float $jumlah, float $hpp): void
    {
        DB::table('saldo_stok')->insert(['id_gudang' => $gudang, 'id_lokasi_gudang' => $lokasi, 'id_barang' => $barang, 'jumlah_stok' => $jumlah, 'jumlah_dipesan' => 0, 'jumlah_rusak' => 0, 'harga_pokok_rata_rata' => $hpp, 'harga_beli_terakhir' => $hpp, 'updated_at' => now()]);
    }

    private function stok(int $gudang, int $lokasi, int $barang): float
    {
        return (float) DB::table('saldo_stok')->where('id_gudang', $gudang)->where('id_lokasi_gudang', $lokasi)->where('id_barang', $barang)->value('jumlah_stok');
    }
}
