<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaseSembilanLaporanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE9_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration Fase 9 hanya dijalankan pada job MySQL khusus.');
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_permission_dan_skema_fase_sembilan_tetap_paten(): void
    {
        $kode = [
            'DASHBOARD_BISNIS_LIHAT',
            'LAPORAN_OPERASIONAL_UNDUH',
            'NOTA_PENJUALAN_CETAK',
        ];

        $this->assertSame(98, DB::table('hak_akses')->whereNull('deleted_at')->count());
        $this->assertSame(3, DB::table('hak_akses')->whereIn('kode_hak_akses', $kode)->whereNull('deleted_at')->count());

        $database = config('database.connections.mysql.database');
        $this->assertSame(71, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'BASE TABLE')->count());
        $this->assertSame(3, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'VIEW')->count());
        $this->assertSame(0, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->whereIn('TABLE_NAME', ['sessions', 'cache', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'])->count());

        foreach (['tampilan_stok_tersedia', 'tampilan_hutang_pemasok', 'tampilan_piutang_pelanggan'] as $view) {
            $this->assertSame(1, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_NAME', $view)->where('TABLE_TYPE', 'VIEW')->count());
        }
    }

    public function test_dashboard_laporan_dan_csv_hanya_memakai_cabang_aktif_tanpa_duplikasi_header(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $penjualanAktif = $this->buatPenjualan($cabang, $admin, 'PJ-F9-AKTIF', 150000, [
            ['jumlah' => 1, 'harga' => 50000, 'hpp' => 30000],
            ['jumlah' => 2, 'harga' => 50000, 'hpp' => 35000],
        ]);

        $cabangLain = $this->buatCabangLain();
        $this->buatPenjualan($cabangLain, $admin, 'PJ-F9-LAIN', 900000, [
            ['jumlah' => 1, 'harga' => 900000, 'hpp' => 600000],
        ]);

        $session = $this->sessionCabang($cabang);
        $parameter = [
            'jenis_laporan' => 'penjualan',
            'tanggal_awal' => '2026-07-01',
            'tanggal_akhir' => '2026-07-31',
        ];

        $this->actingAs($admin)
            ->withSession($session)
            ->get('/dashboard?'.http_build_query($parameter))
            ->assertOk()
            ->assertSee('Rp 150.000')
            ->assertSee('PJ-F9-AKTIF')
            ->assertDontSee('Rp 900.000')
            ->assertDontSee('PJ-F9-LAIN');

        $this->actingAs($admin)
            ->withSession($session)
            ->get('/laporan?'.http_build_query($parameter))
            ->assertOk()
            ->assertSee('PJ-F9-AKTIF')
            ->assertSee('Rp 150.000')
            ->assertDontSee('PJ-F9-LAIN')
            ->assertDontSee('Rp 900.000');

        $responsCsv = $this->actingAs($admin)
            ->withSession($session)
            ->get('/laporan/unduh/csv?'.http_build_query($parameter));

        $responsCsv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $isiCsv = $responsCsv->streamedContent();
        $this->assertStringContainsString('PJ-F9-AKTIF', $isiCsv);
        $this->assertStringNotContainsString('PJ-F9-LAIN', $isiCsv);
        $this->assertSame(1, substr_count($isiCsv, 'PJ-F9-AKTIF'));

        $this->assertSame(1, DB::table('log_aktivitas')
            ->where('nama_modul', 'LAPORAN')
            ->where('jenis_aktivitas', 'UNDUH')
            ->count());

        $this->assertSame($penjualanAktif, (int) DB::table('penjualan')->where('nomor_penjualan', 'PJ-F9-AKTIF')->value('id_penjualan'));
    }

    public function test_nota_penjualan_dapat_dicetak_dan_transaksi_cabang_lain_menghasilkan_404(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $idAktif = $this->buatPenjualan($cabang, $admin, 'PJ-F9-NOTA', 125000, [
            ['jumlah' => 5, 'harga' => 25000, 'hpp' => 15000],
        ]);

        $cabangLain = $this->buatCabangLain();
        $idLain = $this->buatPenjualan($cabangLain, $admin, 'PJ-F9-NOTA-LAIN', 300000, [
            ['jumlah' => 3, 'harga' => 100000, 'hpp' => 70000],
        ]);

        $session = $this->sessionCabang($cabang);

        $this->actingAs($admin)
            ->withSession($session)
            ->get('/penjualan/transaksi/'.$idAktif.'/nota')
            ->assertOk()
            ->assertSee('PJ-F9-NOTA')
            ->assertSee('Barang PJ-F9-NOTA')
            ->assertSee('Rp 125.000');

        $this->actingAs($admin)
            ->withSession($session)
            ->get('/penjualan/transaksi/'.$idLain.'/nota')
            ->assertNotFound();

        $this->assertSame(1, DB::table('log_aktivitas')
            ->where('nama_modul', 'PENJUALAN')
            ->where('jenis_aktivitas', 'CETAK')
            ->where('id_referensi', $idAktif)
            ->count());
    }

    private function adminDanCabang(): array
    {
        return [
            Pengguna::query()->where('nama_pengguna', 'admin_fase9')->firstOrFail(),
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

    private function buatCabangLain(): Cabang
    {
        $id = (int) DB::table('cabang')->insertGetId([
            'kode_cabang' => 'CAB-F9-'.uniqid(),
            'nama_cabang' => 'Cabang Fase 9 Lain',
            'alamat' => 'Alamat cabang lain',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);

        return Cabang::query()->findOrFail($id);
    }

    private function buatPenjualan(Cabang $cabang, Pengguna $admin, string $nomor, float $total, array $detail): int
    {
        $kategori = (int) DB::table('kategori_barang')->insertGetId([
            'kode_kategori' => 'KAT-'.$nomor,
            'nama_kategori' => 'Kategori '.$nomor,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $satuan = (int) DB::table('satuan')->insertGetId([
            'kode_satuan' => 'ST-'.substr(md5($nomor), 0, 8),
            'nama_satuan' => 'Satuan '.$nomor,
            'jumlah_desimal' => 0,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $barang = (int) DB::table('barang')->insertGetId([
            'id_kategori_barang' => $kategori,
            'id_satuan_dasar' => $satuan,
            'kode_barang' => 'BRG-'.$nomor,
            'nama_barang' => 'Barang '.$nomor,
            'jenis_barang' => 'BARANG',
            'metode_persediaan' => 'RATA_RATA',
            'stok_minimum' => 1,
            'bisa_dibeli' => 1,
            'bisa_dijual' => 1,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $barangSatuan = (int) DB::table('barang_satuan')->insertGetId([
            'id_barang' => $barang,
            'id_satuan' => $satuan,
            'nilai_konversi' => 1,
            'harga_beli_acuan' => 15000,
            'harga_jual_acuan' => 25000,
            'satuan_utama_pembelian' => 1,
            'satuan_utama_penjualan' => 1,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $gudang = (int) DB::table('gudang')->insertGetId([
            'id_cabang' => $cabang->id_cabang,
            'kode_gudang' => 'G-F9-'.uniqid(),
            'nama_gudang' => 'Gudang '.$nomor,
            'jenis_gudang' => 'UTAMA',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $lokasi = (int) DB::table('lokasi_gudang')->insertGetId([
            'id_gudang' => $gudang,
            'kode_lokasi' => 'L-F9-'.uniqid(),
            'nama_lokasi' => 'Lokasi '.$nomor,
            'jenis_lokasi' => 'AREA_UMUM',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);

        DB::table('saldo_stok')->insert([
            'id_gudang' => $gudang,
            'id_lokasi_gudang' => $lokasi,
            'id_barang' => $barang,
            'jumlah_stok' => 20,
            'jumlah_dipesan' => 0,
            'jumlah_rusak' => 0,
            'harga_pokok_rata_rata' => 15000,
            'harga_beli_terakhir' => 15000,
            'updated_at' => now(),
        ]);

        $idPenjualan = (int) DB::table('penjualan')->insertGetId([
            'id_cabang' => $cabang->id_cabang,
            'id_gudang' => $gudang,
            'nomor_penjualan' => $nomor,
            'tanggal_penjualan' => '2026-07-24 09:00:00',
            'jenis_penjualan' => 'TUNAI',
            'status_penjualan' => 'LUNAS',
            'status_pengiriman' => 'DIAMBIL_SENDIRI',
            'total_kotor' => $total,
            'total_bersih' => $total,
            'total_dibayar' => $total,
            'uang_kembali' => 0,
            'sisa_piutang' => 0,
            'created_at' => now(),
            'created_by' => $admin->id_pengguna,
        ]);

        foreach ($detail as $baris) {
            $jumlah = (float) $baris['jumlah'];
            $harga = (float) $baris['harga'];
            $hpp = (float) $baris['hpp'];
            DB::table('penjualan_detail')->insert([
                'id_penjualan' => $idPenjualan,
                'id_barang_satuan' => $barangSatuan,
                'id_lokasi_gudang' => $lokasi,
                'nilai_konversi' => 1,
                'jumlah' => $jumlah,
                'jumlah_dasar' => $jumlah,
                'harga_satuan' => $harga,
                'total_baris' => $jumlah * $harga,
                'harga_pokok' => $hpp,
                'total_harga_pokok' => $jumlah * $hpp,
                'laba_kotor' => ($jumlah * $harga) - ($jumlah * $hpp),
                'created_at' => now(),
                'created_by' => $admin->id_pengguna,
            ]);
        }

        return $idPenjualan;
    }
}
