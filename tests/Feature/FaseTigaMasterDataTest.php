<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use App\Models\PenggunaPeran;
use App\Models\Peran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FaseTigaMasterDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE3_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration Fase 3 hanya dijalankan pada job MySQL khusus.');
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_data_awal_fase_tiga_lengkap_dan_pajak_default_nol(): void
    {
        $this->assertSame(
            ['KONTRAKTOR_PROYEK', 'TOKO_RESELLER', 'TUKANG', 'UMUM'],
            DB::table('jenis_pelanggan')->whereNull('deleted_at')->orderBy('kode_jenis_pelanggan')->pluck('kode_jenis_pelanggan')->all()
        );
        $this->assertDatabaseHas('pelanggan', ['kode_pelanggan' => 'UMUM', 'nama_pelanggan' => 'PELANGGAN TUNAI', 'status_aktif' => 1]);
        $this->assertDatabaseHas('tarif_pajak', ['kode_tarif_pajak' => 'NON_PAJAK', 'persen_pajak' => 0]);

        $cabang = Cabang::query()->firstOrFail();
        foreach (['RUSAK', 'RETUR'] as $jenis) {
            $this->assertDatabaseHas('gudang', ['id_cabang' => $cabang->id_cabang, 'kode_gudang' => $jenis, 'jenis_gudang' => $jenis, 'status_aktif' => 1]);
        }
    }

    public function test_stok_barang_mengikuti_jumlah_desimal_satuan(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $kategori = $this->buatKategori();
        $satuan = $this->buatSatuan('PCS0', 0);

        $response = $this->actingAs($admin)->withSession($this->sessionCabang($cabang))->post('/barang', $this->payloadBarang($kategori, $satuan, 1.5));
        $response->assertSessionHasErrors('stok_minimum');
        $this->assertDatabaseMissing('barang', ['kode_barang' => 'BRG-UJI-DESIMAL']);
    }

    public function test_stok_desimal_diterima_bila_satuan_mengizinkan(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $kategori = $this->buatKategori();
        $satuan = $this->buatSatuan('MTR2', 2);

        $response = $this->actingAs($admin)->withSession($this->sessionCabang($cabang))->post('/barang', $this->payloadBarang($kategori, $satuan, 1.25));
        $response->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('barang', ['kode_barang' => 'BRG-UJI-DESIMAL', 'stok_minimum' => 1.250]);
    }

    public function test_periode_daftar_harga_yang_bertumpang_tindih_ditolak(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $barangSatuan = $this->buatBarangSatuan();
        $jenis = (int) DB::table('jenis_pelanggan')->where('kode_jenis_pelanggan', 'UMUM')->value('id_jenis_pelanggan');
        $session = $this->sessionCabang($cabang);

        $pertama = $this->actingAs($admin)->withSession($session)->post('/daftar-harga', [
            'id_jenis_pelanggan' => $jenis,
            'kode_daftar_harga' => 'HARGA-JULI',
            'nama_daftar_harga' => 'Harga Juli',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'prioritas' => 1,
            'detail_harga' => [['id_barang_satuan' => $barangSatuan, 'jumlah_minimum' => 1, 'harga_jual' => 10000, 'potongan_persen' => 0]],
        ]);
        $pertama->assertSessionHasNoErrors();

        $kedua = $this->actingAs($admin)->withSession($session)->post('/daftar-harga', [
            'id_jenis_pelanggan' => $jenis,
            'kode_daftar_harga' => 'HARGA-TUMPANG',
            'nama_daftar_harga' => 'Harga Tumpang Tindih',
            'tanggal_mulai' => '2026-07-15',
            'tanggal_selesai' => '2026-08-15',
            'prioritas' => 2,
            'detail_harga' => [['id_barang_satuan' => $barangSatuan, 'jumlah_minimum' => 1, 'harga_jual' => 11000, 'potongan_persen' => 0]],
        ]);
        $kedua->assertSessionHasErrors('tanggal_mulai');
        $this->assertDatabaseMissing('daftar_harga', ['kode_daftar_harga' => 'HARGA-TUMPANG']);
    }

    public function test_kasir_dapat_melihat_barang_tetapi_tidak_dapat_mengelola(): void
    {
        $cabang = Cabang::query()->firstOrFail();
        $kasir = Pengguna::query()->create([
            'nama_pengguna' => 'kasir_fase3',
            'kata_sandi' => Hash::make('KasirFase3!2026'),
            'nama_tampilan' => 'Kasir Fase 3',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $peran = Peran::query()->where('kode_peran', 'KASIR')->firstOrFail();
        PenggunaPeran::query()->create(['id_pengguna' => $kasir->id_pengguna, 'id_peran' => $peran->id_peran, 'id_cabang' => $cabang->id_cabang, 'created_at' => now()]);

        $this->actingAs($kasir)->withSession($this->sessionCabang($cabang))->get('/barang')->assertOk();
        $this->actingAs($kasir)->withSession($this->sessionCabang($cabang))->post('/barang', [])->assertForbidden();
    }

    public function test_edit_barang_nonaktif_tidak_mengaktifkan_ulang_status(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $kategori = $this->buatKategori();
        $satuan = $this->buatSatuan('PCS-STATUS', 0);
        $payload = $this->payloadBarang($kategori, $satuan, 1);

        $this->actingAs($admin)->withSession($this->sessionCabang($cabang))->post('/barang', $payload)->assertSessionHasNoErrors();
        $idBarang = (int) DB::table('barang')->where('kode_barang', 'BRG-UJI-DESIMAL')->value('id_barang');
        DB::table('barang')->where('id_barang', $idBarang)->update(['status_aktif' => 0]);

        $payload['nama_barang'] = 'Barang Nonaktif Diperbarui';
        $this->actingAs($admin)
            ->withSession($this->sessionCabang($cabang))
            ->put('/barang/'.$idBarang, $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('barang', [
            'id_barang' => $idBarang,
            'nama_barang' => 'Barang Nonaktif Diperbarui',
            'status_aktif' => 0,
        ]);
    }

    public function test_tarif_non_pajak_dilindungi_agar_tetap_nol(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $idPajak = (int) DB::table('tarif_pajak')->where('kode_tarif_pajak', 'NON_PAJAK')->value('id_tarif_pajak');

        $this->actingAs($admin)
            ->withSession($this->sessionCabang($cabang))
            ->put('/master/tarif-pajak/'.$idPajak, [
                'kode_tarif_pajak' => 'NON_PAJAK',
                'nama_tarif_pajak' => 'Non Pajak',
                'persen_pajak' => 11,
                'jenis_pajak' => 'KEDUANYA',
            ])
            ->assertSessionHasErrors('persen_pajak');

        $this->assertDatabaseHas('tarif_pajak', [
            'id_tarif_pajak' => $idPajak,
            'kode_tarif_pajak' => 'NON_PAJAK',
            'persen_pajak' => 0,
        ]);
    }

    public function test_gudang_cabang_lain_tidak_dapat_diubah_dengan_manipulasi_id(): void
    {
        [$admin, $cabangAktif] = $this->adminDanCabang();
        $idCabangLain = (int) DB::table('cabang')->insertGetId([
            'kode_cabang' => 'CAB-LAIN',
            'nama_cabang' => 'Cabang Lain',
            'alamat' => 'Alamat Cabang Lain',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $idGudangLain = (int) DB::table('gudang')->insertGetId([
            'id_cabang' => $idCabangLain,
            'kode_gudang' => 'G-LAIN',
            'nama_gudang' => 'Gudang Cabang Lain',
            'jenis_gudang' => 'UTAMA',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession($this->sessionCabang($cabangAktif))
            ->put('/gudang/'.$idGudangLain, [
                'kode_gudang' => 'G-LAIN',
                'nama_gudang' => 'Manipulasi Gudang',
                'jenis_gudang' => 'UTAMA',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('gudang', [
            'id_gudang' => $idGudangLain,
            'nama_gudang' => 'Gudang Cabang Lain',
        ]);
    }

    public function test_skema_tetap_paten_tanpa_tabel_infrastruktur_tambahan(): void
    {
        $database = config('database.connections.mysql.database');
        $baseTables = (int) DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'BASE TABLE')->count();
        $views = (int) DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'VIEW')->count();
        $this->assertSame(71, $baseTables);
        $this->assertSame(3, $views);
        $this->assertSame(0, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->whereIn('TABLE_NAME', ['sessions', 'cache', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'])->count());
    }

    private function adminDanCabang(): array
    {
        return [Pengguna::query()->where('nama_pengguna', 'admin_fase3')->firstOrFail(), Cabang::query()->firstOrFail()];
    }

    private function sessionCabang(Cabang $cabang): array
    {
        return ['id_cabang_aktif' => $cabang->id_cabang, 'nama_cabang_aktif' => $cabang->nama_cabang];
    }

    private function buatKategori(): int
    {
        return (int) DB::table('kategori_barang')->insertGetId(['kode_kategori' => 'KAT-UJI', 'nama_kategori' => 'Kategori Uji', 'status_aktif' => 1, 'created_at' => now()]);
    }

    private function buatSatuan(string $kode, int $desimal): int
    {
        return (int) DB::table('satuan')->insertGetId(['kode_satuan' => $kode, 'nama_satuan' => 'Satuan '.$kode, 'jumlah_desimal' => $desimal, 'status_aktif' => 1, 'created_at' => now()]);
    }

    private function payloadBarang(int $kategori, int $satuan, float $stokMinimum): array
    {
        return [
            'kode_barang' => 'BRG-UJI-DESIMAL', 'nama_barang' => 'Barang Uji Desimal', 'id_kategori_barang' => $kategori,
            'id_satuan_dasar' => $satuan, 'jenis_barang' => 'BARANG', 'berat_kilogram' => 0, 'panjang_sentimeter' => 0,
            'lebar_sentimeter' => 0, 'tinggi_sentimeter' => 0, 'stok_minimum' => $stokMinimum, 'stok_maksimum' => 100,
            'metode_persediaan' => 'RATA_RATA', 'bisa_dibeli' => 1, 'bisa_dijual' => 1,
            'satuan_barang' => [['id_satuan' => $satuan, 'kode_batang' => null, 'nilai_konversi' => 1, 'harga_beli_acuan' => 5000, 'harga_jual_acuan' => 10000, 'satuan_utama_pembelian' => 1, 'satuan_utama_penjualan' => 1]],
        ];
    }

    private function buatBarangSatuan(): int
    {
        $kategori = $this->buatKategori();
        $satuan = $this->buatSatuan('PCS-HARGA', 0);
        $barang = (int) DB::table('barang')->insertGetId([
            'id_kategori_barang' => $kategori, 'id_satuan_dasar' => $satuan, 'kode_barang' => 'BRG-HARGA', 'nama_barang' => 'Barang Harga',
            'jenis_barang' => 'BARANG', 'metode_persediaan' => 'RATA_RATA', 'bisa_dibeli' => 1, 'bisa_dijual' => 1, 'status_aktif' => 1, 'created_at' => now(),
        ]);

        return (int) DB::table('barang_satuan')->insertGetId([
            'id_barang' => $barang, 'id_satuan' => $satuan, 'nilai_konversi' => 1, 'harga_beli_acuan' => 5000, 'harga_jual_acuan' => 10000,
            'satuan_utama_pembelian' => 1, 'satuan_utama_penjualan' => 1, 'status_aktif' => 1, 'created_at' => now(),
        ]);
    }
}
