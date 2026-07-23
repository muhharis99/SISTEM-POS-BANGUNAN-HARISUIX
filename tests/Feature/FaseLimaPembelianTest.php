<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaseLimaPembelianTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE5_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration Fase 5 hanya dijalankan pada job MySQL khusus.');
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_permission_dan_skema_fase_lima_tetap_paten(): void
    {
        $kode = [
            'PEMBELIAN_LIHAT',
            'PERMINTAAN_PEMBELIAN_KELOLA',
            'PERMINTAAN_PEMBELIAN_SETUJUI',
            'PESANAN_PEMBELIAN_KELOLA',
            'PESANAN_PEMBELIAN_SETUJUI',
            'PENERIMAAN_BARANG_KELOLA',
            'PENERIMAAN_BARANG_TERIMA',
            'FAKTUR_PEMBELIAN_KELOLA',
            'FAKTUR_PEMBELIAN_SETUJUI',
            'HUTANG_PEMASOK_LIHAT',
            'PEMBAYARAN_HUTANG_KELOLA',
            'PEMBAYARAN_HUTANG_SETUJUI',
            'RETUR_PEMBELIAN_KELOLA',
            'RETUR_PEMBELIAN_SETUJUI',
            'RETUR_PEMBELIAN_KIRIM',
            'LAPORAN_PEMBELIAN_LIHAT',
        ];

        $this->assertSame(57, DB::table('hak_akses')->whereNull('deleted_at')->count());
        $this->assertSame(16, DB::table('hak_akses')->whereIn('kode_hak_akses', $kode)->whereNull('deleted_at')->count());

        $database = config('database.connections.mysql.database');
        $this->assertSame(71, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'BASE TABLE')->count());
        $this->assertSame(3, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'VIEW')->count());
        $this->assertSame(0, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->whereIn('TABLE_NAME', ['sessions', 'cache', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'])->count());
    }

    public function test_alur_pembelian_tempo_pembayaran_dan_retur_terintegrasi_stok(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('BELI-UTAMA');
        [$gudang, $lokasi] = $this->buatGudang($cabang);
        $pemasok = $this->buatPemasok('SUP-UTAMA');
        [$kas, $metode] = $this->buatKasDanMetode($cabang);
        $session = $this->sessionCabang($cabang);

        $this->actingAs($admin)->withSession($session)->post('/pembelian/permintaan', [
            'tanggal_permintaan' => '2026-07-23',
            'tanggal_kebutuhan' => '2026-07-25',
            'tingkat_kepentingan' => 'NORMAL',
            'detail' => [[
                'id_barang_satuan' => $barangSatuan,
                'jumlah' => 10,
                'perkiraan_harga' => 10000,
            ]],
        ])->assertSessionHasNoErrors();
        $idPermintaan = (int) DB::table('permintaan_pembelian')->max('id_permintaan_pembelian');
        $idPermintaanDetail = (int) DB::table('permintaan_pembelian_detail')->where('id_permintaan_pembelian', $idPermintaan)->value('id_permintaan_pembelian_detail');
        $this->actingAs($admin)->withSession($session)->patch("/pembelian/permintaan/{$idPermintaan}/ajukan")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/pembelian/permintaan/{$idPermintaan}/setujui")->assertSessionHasNoErrors();

        $this->actingAs($admin)->withSession($session)->post('/pembelian/pesanan', [
            'id_pemasok' => $pemasok,
            'tanggal_pesanan' => '2026-07-23',
            'cara_pembayaran' => 'TEMPO',
            'lama_jatuh_tempo' => 30,
            'detail' => [[
                'id_permintaan_pembelian_detail' => $idPermintaanDetail,
                'id_barang_satuan' => $barangSatuan,
                'jumlah' => 10,
                'harga_satuan' => 10000,
                'potongan_persen' => 0,
            ]],
        ])->assertSessionHasNoErrors();
        $idPesanan = (int) DB::table('pesanan_pembelian')->max('id_pesanan_pembelian');
        $idPesananDetail = (int) DB::table('pesanan_pembelian_detail')->where('id_pesanan_pembelian', $idPesanan)->value('id_pesanan_pembelian_detail');
        $this->actingAs($admin)->withSession($session)->patch("/pembelian/pesanan/{$idPesanan}/ajukan")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/pembelian/pesanan/{$idPesanan}/setujui")->assertSessionHasNoErrors();
        $this->assertSame('DIPROSES', DB::table('permintaan_pembelian')->where('id_permintaan_pembelian', $idPermintaan)->value('status_permintaan'));

        $this->actingAs($admin)->withSession($session)->post('/pembelian/penerimaan', [
            'id_gudang' => $gudang,
            'id_pemasok' => $pemasok,
            'id_pesanan_pembelian' => $idPesanan,
            'tanggal_penerimaan' => '2026-07-24',
            'detail' => [[
                'id_pesanan_pembelian_detail' => $idPesananDetail,
                'id_barang_satuan' => $barangSatuan,
                'id_lokasi_gudang' => $lokasi,
                'jumlah_diterima' => 10,
                'jumlah_ditolak' => 0,
                'harga_pokok' => 10000,
            ]],
        ])->assertSessionHasNoErrors();
        $idPenerimaan = (int) DB::table('penerimaan_barang')->max('id_penerimaan_barang');
        $idPenerimaanDetail = (int) DB::table('penerimaan_barang_detail')->where('id_penerimaan_barang', $idPenerimaan)->value('id_penerimaan_barang_detail');
        $this->actingAs($admin)->withSession($session)->patch("/pembelian/penerimaan/{$idPenerimaan}/terima")->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(10, $this->stok($gudang, $lokasi, $barang), 0.0001);
        $this->assertSame(1, DB::table('mutasi_stok')->where('jenis_mutasi', 'PEMBELIAN')->where('id_dokumen', $idPenerimaan)->count());

        $this->actingAs($admin)->withSession($session)->post('/pembelian/faktur', [
            'id_pemasok' => $pemasok,
            'id_pesanan_pembelian' => $idPesanan,
            'id_penerimaan_barang' => $idPenerimaan,
            'nomor_faktur_pemasok' => 'INV-SUP-001',
            'tanggal_faktur' => '2026-07-24',
            'tanggal_jatuh_tempo' => '2026-08-23',
            'cara_pembayaran' => 'TEMPO',
            'detail' => [[
                'id_penerimaan_barang_detail' => $idPenerimaanDetail,
                'id_barang_satuan' => $barangSatuan,
                'jumlah' => 10,
                'harga_satuan' => 10000,
                'potongan_persen' => 0,
            ]],
        ])->assertSessionHasNoErrors();
        $idFaktur = (int) DB::table('faktur_pembelian')->max('id_faktur_pembelian');
        $idFakturDetail = (int) DB::table('faktur_pembelian_detail')->where('id_faktur_pembelian', $idFaktur)->value('id_faktur_pembelian_detail');
        $this->actingAs($admin)->withSession($session)->patch("/pembelian/faktur/{$idFaktur}/setujui")->assertSessionHasNoErrors();
        $idHutang = (int) DB::table('hutang_pemasok')->where('id_faktur_pembelian', $idFaktur)->value('id_hutang_pemasok');
        $this->assertEqualsWithDelta(100000, (float) DB::table('hutang_pemasok')->where('id_hutang_pemasok', $idHutang)->value('sisa_hutang'), 0.01);

        $this->actingAs($admin)->withSession($session)->post('/hutang-pemasok', [
            'id_pemasok' => $pemasok,
            'id_kas_bank' => $kas,
            'id_metode_pembayaran' => $metode,
            'tanggal_pembayaran' => '2026-07-25',
            'detail' => [[
                'id_hutang_pemasok' => $idHutang,
                'nilai_dialokasikan' => 60000,
                'potongan_pembayaran' => 0,
            ]],
        ])->assertSessionHasNoErrors();
        $idPembayaran = (int) DB::table('pembayaran_hutang')->max('id_pembayaran_hutang');
        $this->actingAs($admin)->withSession($session)->patch("/hutang-pemasok/{$idPembayaran}/setujui")->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(40000, (float) DB::table('hutang_pemasok')->where('id_hutang_pemasok', $idHutang)->value('sisa_hutang'), 0.01);
        $this->assertSame('SEBAGIAN', DB::table('hutang_pemasok')->where('id_hutang_pemasok', $idHutang)->value('status_hutang'));

        $this->actingAs($admin)->withSession($session)->post('/pembelian/retur', [
            'id_pemasok' => $pemasok,
            'id_faktur_pembelian' => $idFaktur,
            'id_gudang' => $gudang,
            'tanggal_retur' => '2026-07-26',
            'alasan_retur' => 'Barang rusak',
            'cara_pengembalian_dana' => 'POTONG_HUTANG',
            'detail' => [[
                'id_faktur_pembelian_detail' => $idFakturDetail,
                'id_barang_satuan' => $barangSatuan,
                'id_lokasi_gudang' => $lokasi,
                'jumlah' => 2,
                'harga_satuan' => 10000,
                'kondisi_barang' => 'RUSAK',
            ]],
        ])->assertSessionHasNoErrors();
        $idRetur = (int) DB::table('retur_pembelian')->max('id_retur_pembelian');
        $this->actingAs($admin)->withSession($session)->patch("/pembelian/retur/{$idRetur}/setujui")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/pembelian/retur/{$idRetur}/kirim")->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(8, $this->stok($gudang, $lokasi, $barang), 0.0001);
        $this->assertEqualsWithDelta(20000, (float) DB::table('hutang_pemasok')->where('id_hutang_pemasok', $idHutang)->value('sisa_hutang'), 0.01);
        $this->assertSame(1, DB::table('mutasi_stok')->where('jenis_mutasi', 'RETUR_PEMBELIAN')->where('id_dokumen', $idRetur)->count());
    }

    public function test_retur_melebihi_stok_ditolak_dan_atomik(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('RETUR-KURANG');
        [$gudang, $lokasi] = $this->buatGudang($cabang);
        $pemasok = $this->buatPemasok('SUP-RETUR');
        $session = $this->sessionCabang($cabang);

        DB::table('saldo_stok')->insert([
            'id_gudang' => $gudang,
            'id_lokasi_gudang' => $lokasi,
            'id_barang' => $barang,
            'jumlah_stok' => 1,
            'jumlah_dipesan' => 0,
            'jumlah_rusak' => 0,
            'harga_pokok_rata_rata' => 5000,
            'harga_beli_terakhir' => 5000,
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->withSession($session)->post('/pembelian/retur', [
            'id_pemasok' => $pemasok,
            'id_gudang' => $gudang,
            'tanggal_retur' => '2026-07-26',
            'alasan_retur' => 'Uji stok kurang',
            'cara_pengembalian_dana' => 'PENGGANTI_BARANG',
            'detail' => [[
                'id_barang_satuan' => $barangSatuan,
                'id_lokasi_gudang' => $lokasi,
                'jumlah' => 2,
                'harga_satuan' => 5000,
                'kondisi_barang' => 'RUSAK',
            ]],
        ])->assertSessionHasNoErrors();
        $idRetur = (int) DB::table('retur_pembelian')->max('id_retur_pembelian');
        $this->actingAs($admin)->withSession($session)->patch("/pembelian/retur/{$idRetur}/setujui")->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch("/pembelian/retur/{$idRetur}/kirim")->assertSessionHasErrors('jumlah');

        $this->assertEqualsWithDelta(1, $this->stok($gudang, $lokasi, $barang), 0.0001);
        $this->assertSame('DISETUJUI', DB::table('retur_pembelian')->where('id_retur_pembelian', $idRetur)->value('status_retur'));
        $this->assertSame(0, DB::table('mutasi_stok')->where('jenis_mutasi', 'RETUR_PEMBELIAN')->where('id_dokumen', $idRetur)->count());
    }

    public function test_dokumen_cabang_lain_tidak_dapat_diproses(): void
    {
        [$admin, $cabangAktif] = $this->adminDanCabang();
        $cabangLain = (int) DB::table('cabang')->insertGetId([
            'kode_cabang' => 'CAB-F5-LAIN',
            'nama_cabang' => 'Cabang F5 Lain',
            'alamat' => 'Alamat lain',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $id = (int) DB::table('permintaan_pembelian')->insertGetId([
            'id_cabang' => $cabangLain,
            'nomor_permintaan' => 'PP-LAIN',
            'tanggal_permintaan' => '2026-07-23',
            'tingkat_kepentingan' => 'NORMAL',
            'status_permintaan' => 'DIAJUKAN',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->withSession($this->sessionCabang($cabangAktif))->patch("/pembelian/permintaan/{$id}/setujui")->assertNotFound();
    }

    private function adminDanCabang(): array
    {
        return [
            Pengguna::query()->where('nama_pengguna', 'admin_fase5')->firstOrFail(),
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
        $barangSatuan = (int) DB::table('barang_satuan')->insertGetId(['id_barang' => $barang, 'id_satuan' => $satuan, 'nilai_konversi' => 1, 'harga_beli_acuan' => 0, 'harga_jual_acuan' => 0, 'satuan_utama_pembelian' => 1, 'satuan_utama_penjualan' => 1, 'status_aktif' => 1, 'created_at' => now()]);

        return [$barang, $barangSatuan];
    }

    private function buatGudang(Cabang $cabang): array
    {
        $gudang = (int) DB::table('gudang')->insertGetId(['id_cabang' => $cabang->id_cabang, 'kode_gudang' => 'G-F5-'.uniqid(), 'nama_gudang' => 'Gudang Fase 5', 'jenis_gudang' => 'UTAMA', 'status_aktif' => 1, 'created_at' => now()]);
        $lokasi = (int) DB::table('lokasi_gudang')->insertGetId(['id_gudang' => $gudang, 'kode_lokasi' => 'L-F5-'.uniqid(), 'nama_lokasi' => 'Lokasi Fase 5', 'jenis_lokasi' => 'AREA_UMUM', 'status_aktif' => 1, 'created_at' => now()]);

        return [$gudang, $lokasi];
    }

    private function buatPemasok(string $kode): int
    {
        return (int) DB::table('pemasok')->insertGetId(['kode_pemasok' => $kode, 'nama_pemasok' => 'Pemasok '.$kode, 'batas_hutang' => 10000000, 'lama_jatuh_tempo' => 30, 'status_aktif' => 1, 'created_at' => now()]);
    }

    private function buatKasDanMetode(Cabang $cabang): array
    {
        $kas = (int) DB::table('kas_bank')->insertGetId(['id_cabang' => $cabang->id_cabang, 'kode_kas_bank' => 'KAS-F5', 'nama_kas_bank' => 'Kas Fase 5', 'jenis_kas_bank' => 'KAS', 'saldo_awal' => 0, 'status_aktif' => 1, 'created_at' => now()]);
        $metode = (int) DB::table('metode_pembayaran')->insertGetId(['kode_metode_pembayaran' => 'TRF-F5', 'nama_metode_pembayaran' => 'Transfer Fase 5', 'kelompok_pembayaran' => 'TRANSFER', 'biaya_persen' => 0, 'biaya_tetap' => 0, 'status_aktif' => 1, 'created_at' => now()]);

        return [$kas, $metode];
    }

    private function stok(int $gudang, int $lokasi, int $barang): float
    {
        return (float) (DB::table('saldo_stok')->where('id_gudang', $gudang)->where('id_lokasi_gudang', $lokasi)->where('id_barang', $barang)->value('jumlah_stok') ?? 0);
    }
}
