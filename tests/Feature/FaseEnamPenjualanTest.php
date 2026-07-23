<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaseEnamPenjualanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE6_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration Fase 6 hanya dijalankan pada job MySQL khusus.');
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_permission_dan_skema_fase_enam_tetap_paten(): void
    {
        $kode = [
            'PENJUALAN_LIHAT',
            'PENAWARAN_PENJUALAN_KELOLA',
            'PESANAN_PENJUALAN_KELOLA',
            'PESANAN_PENJUALAN_SETUJUI',
            'TRANSAKSI_PENJUALAN_KELOLA',
            'TRANSAKSI_PENJUALAN_SETUJUI',
            'PIUTANG_PELANGGAN_LIHAT',
            'PEMBAYARAN_PIUTANG_KELOLA',
            'PEMBAYARAN_PIUTANG_SETUJUI',
            'PENGIRIMAN_KELOLA',
            'PENGIRIMAN_JADWALKAN',
            'PENGIRIMAN_KIRIM',
            'PENGIRIMAN_TERIMA',
            'RETUR_PENJUALAN_KELOLA',
            'RETUR_PENJUALAN_SETUJUI',
            'RETUR_PENJUALAN_TERIMA',
            'LAPORAN_PENJUALAN_LIHAT',
            'LAPORAN_PIUTANG_LIHAT',
        ];

        $this->assertSame(75, DB::table('hak_akses')->whereNull('deleted_at')->count());
        $this->assertSame(18, DB::table('hak_akses')->whereIn('kode_hak_akses', $kode)->whereNull('deleted_at')->count());

        $database = config('database.connections.mysql.database');
        $this->assertSame(71, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'BASE TABLE')->count());
        $this->assertSame(3, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'VIEW')->count());
        $this->assertSame(0, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->whereIn('TABLE_NAME', ['sessions', 'cache', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'])->count());
    }

    public function test_alur_penjualan_tempo_piutang_pengiriman_dan_retur_terintegrasi_stok(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('JUAL-UTAMA');
        [$gudang, $lokasi] = $this->buatGudang($cabang);
        $pelanggan = $this->buatPelanggan('CUS-UTAMA', 1000000);
        [$kas, $metode] = $this->buatKasDanMetode($cabang);
        $this->isiStok($gudang, $lokasi, $barang, 20, 5000);
        $session = $this->sessionCabang($cabang);

        $this->actingAs($admin)->withSession($session)->post('/penjualan/penawaran', [
            'id_pelanggan' => $pelanggan,
            'tanggal_penawaran' => '2026-07-23',
            'berlaku_sampai' => '2026-07-30',
            'biaya_pengiriman' => 0,
            'detail' => [[
                'id_barang_satuan' => $barangSatuan,
                'jumlah' => 10,
                'harga_satuan' => 10000,
                'potongan_persen' => 0,
            ]],
        ])->assertSessionHasNoErrors();
        $idPenawaran = (int) DB::table('penawaran_penjualan')->max('id_penawaran_penjualan');
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/penawaran/{$idPenawaran}/kirim")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/penawaran/{$idPenawaran}/terima")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->post("/penjualan/penawaran/{$idPenawaran}/jadikan-pesanan")->assertSessionHasNoErrors();

        $idPesanan = (int) DB::table('pesanan_penjualan')->max('id_pesanan_penjualan');
        $idPesananDetail = (int) DB::table('pesanan_penjualan_detail')->where('id_pesanan_penjualan', $idPesanan)->value('id_pesanan_penjualan_detail');
        DB::table('pesanan_penjualan')->where('id_pesanan_penjualan', $idPesanan)->update([
            'cara_pembayaran' => 'TEMPO',
            'lama_jatuh_tempo' => 30,
            'updated_at' => now(),
        ]);
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/pesanan/{$idPesanan}/setujui")->assertSessionHasNoErrors();

        $this->actingAs($admin)->withSession($session)->post('/penjualan/transaksi', [
            'id_gudang' => $gudang,
            'id_pelanggan' => $pelanggan,
            'id_pesanan_penjualan' => $idPesanan,
            'tanggal_penjualan' => '2026-07-24 09:00:00',
            'tanggal_jatuh_tempo' => '2026-08-23',
            'jenis_penjualan' => 'TEMPO',
            'total_dibayar' => 0,
            'detail' => [[
                'id_pesanan_penjualan_detail' => $idPesananDetail,
                'id_barang_satuan' => $barangSatuan,
                'id_lokasi_gudang' => $lokasi,
                'jumlah' => 10,
                'harga_satuan' => 10000,
                'potongan_persen' => 0,
            ]],
        ])->assertSessionHasNoErrors();
        $idPenjualan = (int) DB::table('penjualan')->max('id_penjualan');
        $idPenjualanDetail = (int) DB::table('penjualan_detail')->where('id_penjualan', $idPenjualan)->value('id_penjualan_detail');
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/transaksi/{$idPenjualan}/setujui")->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(10, $this->stok($gudang, $lokasi, $barang), 0.0001);
        $this->assertSame(1, DB::table('mutasi_stok')->where('jenis_mutasi', 'PENJUALAN')->where('id_dokumen', $idPenjualan)->count());
        $this->assertEqualsWithDelta(5000, (float) DB::table('penjualan_detail')->where('id_penjualan_detail', $idPenjualanDetail)->value('harga_pokok'), 0.0001);
        $this->assertEqualsWithDelta(50000, (float) DB::table('penjualan_detail')->where('id_penjualan_detail', $idPenjualanDetail)->value('laba_kotor'), 0.01);

        $idPiutang = (int) DB::table('piutang_pelanggan')->where('id_penjualan', $idPenjualan)->value('id_piutang_pelanggan');
        $this->assertEqualsWithDelta(100000, (float) DB::table('piutang_pelanggan')->where('id_piutang_pelanggan', $idPiutang)->value('sisa_piutang'), 0.01);

        $this->actingAs($admin)->withSession($session)->post('/piutang-pelanggan', [
            'id_pelanggan' => $pelanggan,
            'id_kas_bank' => $kas,
            'id_metode_pembayaran' => $metode,
            'tanggal_pembayaran' => '2026-07-25',
            'detail' => [[
                'id_piutang_pelanggan' => $idPiutang,
                'nilai_dialokasikan' => 60000,
                'potongan_pembayaran' => 0,
            ]],
        ])->assertSessionHasNoErrors();
        $idPembayaran = (int) DB::table('pembayaran_piutang')->max('id_pembayaran_piutang');
        $this->actingAs($admin)->withSession($session)->patch("/piutang-pelanggan/{$idPembayaran}/setujui")->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(40000, (float) DB::table('piutang_pelanggan')->where('id_piutang_pelanggan', $idPiutang)->value('sisa_piutang'), 0.01);
        $this->assertSame('SEBAGIAN', DB::table('piutang_pelanggan')->where('id_piutang_pelanggan', $idPiutang)->value('status_piutang'));

        $this->actingAs($admin)->withSession($session)->post('/penjualan/pengiriman', [
            'id_pesanan_penjualan' => $idPesanan,
            'id_penjualan' => $idPenjualan,
            'tanggal_pengiriman' => '2026-07-26',
            'alamat_pengiriman' => 'Alamat pelanggan',
            'detail' => [[
                'id_pesanan_penjualan_detail' => $idPesananDetail,
                'id_penjualan_detail' => $idPenjualanDetail,
                'id_barang_satuan' => $barangSatuan,
                'jumlah_dikirim' => 10,
            ]],
        ])->assertSessionHasNoErrors();
        $idPengiriman = (int) DB::table('pengiriman')->max('id_pengiriman');
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/pengiriman/{$idPengiriman}/jadwalkan")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/pengiriman/{$idPengiriman}/berangkat")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/pengiriman/{$idPengiriman}/terima")->assertSessionHasNoErrors();
        $this->assertSame('DIKIRIM', DB::table('penjualan')->where('id_penjualan', $idPenjualan)->value('status_pengiriman'));
        $this->assertSame('SELESAI', DB::table('pesanan_penjualan')->where('id_pesanan_penjualan', $idPesanan)->value('status_pesanan'));

        $this->actingAs($admin)->withSession($session)->post('/penjualan/retur', [
            'id_penjualan' => $idPenjualan,
            'id_gudang' => $gudang,
            'tanggal_retur' => '2026-07-27',
            'alasan_retur' => 'Barang dikembalikan pelanggan',
            'cara_pengembalian_dana' => 'POTONG_PIUTANG',
            'detail' => [[
                'id_penjualan_detail' => $idPenjualanDetail,
                'id_barang_satuan' => $barangSatuan,
                'id_lokasi_gudang' => $lokasi,
                'jumlah' => 2,
                'harga_satuan' => 10000,
                'kondisi_barang' => 'BAIK',
                'bisa_dijual_kembali' => 1,
            ]],
        ])->assertSessionHasNoErrors();
        $idRetur = (int) DB::table('retur_penjualan')->max('id_retur_penjualan');
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/retur/{$idRetur}/setujui")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/retur/{$idRetur}/terima")->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(12, $this->stok($gudang, $lokasi, $barang), 0.0001);
        $this->assertEqualsWithDelta(20000, (float) DB::table('piutang_pelanggan')->where('id_piutang_pelanggan', $idPiutang)->value('sisa_piutang'), 0.01);
        $this->assertSame(1, DB::table('mutasi_stok')->where('jenis_mutasi', 'RETUR_PENJUALAN')->where('id_dokumen', $idRetur)->count());
    }

    public function test_penjualan_melebihi_stok_ditolak_dan_atomik(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('STOK-KURANG');
        [$gudang, $lokasi] = $this->buatGudang($cabang);
        $this->isiStok($gudang, $lokasi, $barang, 1, 5000);
        $session = $this->sessionCabang($cabang);

        $this->actingAs($admin)->withSession($session)->post('/penjualan/transaksi', [
            'id_gudang' => $gudang,
            'tanggal_penjualan' => '2026-07-24 09:00:00',
            'jenis_penjualan' => 'TUNAI',
            'total_dibayar' => 20000,
            'detail' => [[
                'id_barang_satuan' => $barangSatuan,
                'id_lokasi_gudang' => $lokasi,
                'jumlah' => 2,
                'harga_satuan' => 10000,
                'potongan_persen' => 0,
            ]],
        ])->assertSessionHasNoErrors();
        $idPenjualan = (int) DB::table('penjualan')->max('id_penjualan');
        $this->actingAs($admin)->withSession($session)->patch("/penjualan/transaksi/{$idPenjualan}/setujui")->assertSessionHasErrors('jumlah');

        $this->assertEqualsWithDelta(1, $this->stok($gudang, $lokasi, $barang), 0.0001);
        $this->assertSame('DRAF', DB::table('penjualan')->where('id_penjualan', $idPenjualan)->value('status_penjualan'));
        $this->assertSame(0, DB::table('mutasi_stok')->where('jenis_mutasi', 'PENJUALAN')->where('id_dokumen', $idPenjualan)->count());
    }

    public function test_dokumen_cabang_lain_tidak_dapat_diproses(): void
    {
        [$admin, $cabangAktif] = $this->adminDanCabang();
        $cabangLain = (int) DB::table('cabang')->insertGetId([
            'kode_cabang' => 'CAB-F6-LAIN',
            'nama_cabang' => 'Cabang F6 Lain',
            'alamat' => 'Alamat lain',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $id = (int) DB::table('penawaran_penjualan')->insertGetId([
            'id_cabang' => $cabangLain,
            'nomor_penawaran' => 'QT-LAIN',
            'tanggal_penawaran' => '2026-07-23',
            'status_penawaran' => 'DRAF',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->withSession($this->sessionCabang($cabangAktif))->patch("/penjualan/penawaran/{$id}/kirim")->assertNotFound();
    }

    private function adminDanCabang(): array
    {
        return [
            Pengguna::query()->where('nama_pengguna', 'admin_fase6')->firstOrFail(),
            Cabang::query()->where('kode_cabang', 'CAB-UJI')->firstOrFail(),
        ];
    }

    private function sessionCabang(Cabang $cabang): array
    {
        return ['id_cabang_aktif' => $cabang->id_cabang, 'nama_cabang_aktif' => $cabang->nama_cabang];
    }

    private function buatBarang(string $kode): array
    {
        $kategori = (int) DB::table('kategori_barang')->insertGetId(['kode_kategori' => 'KAT-'.$kode, 'nama_kategori' => 'Kategori '.$kode, 'status_aktif' => 1, 'created_at' => now()]);
        $satuan = (int) DB::table('satuan')->insertGetId(['kode_satuan' => 'ST-'.$kode, 'nama_satuan' => 'Satuan '.$kode, 'jumlah_desimal' => 0, 'status_aktif' => 1, 'created_at' => now()]);
        $barang = (int) DB::table('barang')->insertGetId(['id_kategori_barang' => $kategori, 'id_satuan_dasar' => $satuan, 'kode_barang' => 'BRG-'.$kode, 'nama_barang' => 'Barang '.$kode, 'jenis_barang' => 'BARANG', 'metode_persediaan' => 'RATA_RATA', 'bisa_dibeli' => 1, 'bisa_dijual' => 1, 'status_aktif' => 1, 'created_at' => now()]);
        $barangSatuan = (int) DB::table('barang_satuan')->insertGetId(['id_barang' => $barang, 'id_satuan' => $satuan, 'nilai_konversi' => 1, 'harga_beli_acuan' => 5000, 'harga_jual_acuan' => 10000, 'satuan_utama_pembelian' => 1, 'satuan_utama_penjualan' => 1, 'status_aktif' => 1, 'created_at' => now()]);

        return [$barang, $barangSatuan];
    }

    private function buatGudang(Cabang $cabang): array
    {
        $gudang = (int) DB::table('gudang')->insertGetId(['id_cabang' => $cabang->id_cabang, 'kode_gudang' => 'G-F6-'.uniqid(), 'nama_gudang' => 'Gudang Fase 6', 'jenis_gudang' => 'UTAMA', 'status_aktif' => 1, 'created_at' => now()]);
        $lokasi = (int) DB::table('lokasi_gudang')->insertGetId(['id_gudang' => $gudang, 'kode_lokasi' => 'L-F6-'.uniqid(), 'nama_lokasi' => 'Lokasi Fase 6', 'jenis_lokasi' => 'AREA_UMUM', 'status_aktif' => 1, 'created_at' => now()]);

        return [$gudang, $lokasi];
    }

    private function buatPelanggan(string $kode, float $batas): int
    {
        $jenis = (int) DB::table('jenis_pelanggan')->where('kode_jenis_pelanggan', 'UMUM')->value('id_jenis_pelanggan');

        return (int) DB::table('pelanggan')->insertGetId(['id_jenis_pelanggan' => $jenis, 'kode_pelanggan' => $kode, 'nama_pelanggan' => 'Pelanggan '.$kode, 'alamat_utama' => 'Alamat pelanggan', 'batas_piutang' => $batas, 'lama_jatuh_tempo' => 30, 'status_aktif' => 1, 'created_at' => now()]);
    }

    private function buatKasDanMetode(Cabang $cabang): array
    {
        $kas = (int) DB::table('kas_bank')->insertGetId(['id_cabang' => $cabang->id_cabang, 'kode_kas_bank' => 'KAS-F6-'.uniqid(), 'nama_kas_bank' => 'Kas Fase 6', 'jenis_kas_bank' => 'KAS', 'saldo_awal' => 0, 'status_aktif' => 1, 'created_at' => now()]);
        $metode = (int) DB::table('metode_pembayaran')->insertGetId(['kode_metode_pembayaran' => 'TRF-F6-'.uniqid(), 'nama_metode_pembayaran' => 'Transfer Fase 6', 'kelompok_pembayaran' => 'TRANSFER', 'biaya_persen' => 0, 'biaya_tetap' => 0, 'status_aktif' => 1, 'created_at' => now()]);

        return [$kas, $metode];
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

    private function stok(int $gudang, int $lokasi, int $barang): float
    {
        return (float) (DB::table('saldo_stok')->where('id_gudang', $gudang)->where('id_lokasi_gudang', $lokasi)->where('id_barang', $barang)->value('jumlah_stok') ?? 0);
    }
}
