<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use App\Models\PenggunaPeran;
use App\Models\Peran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FaseEmpatPersediaanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE4_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration Fase 4 hanya dijalankan pada job MySQL khusus.');
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_permission_dan_skema_fase_empat_tetap_paten(): void
    {
        $kodeWajib = [
            'PERSEDIAAN_LIHAT',
            'STOK_AWAL_KELOLA',
            'STOK_AWAL_SETUJUI',
            'TRANSFER_STOK_KELOLA',
            'TRANSFER_STOK_SETUJUI',
            'TRANSFER_STOK_KIRIM',
            'TRANSFER_STOK_TERIMA',
            'STOK_OPNAME_KELOLA',
            'STOK_OPNAME_SETUJUI',
            'PENYESUAIAN_STOK_KELOLA',
            'PENYESUAIAN_STOK_SETUJUI',
            'LAPORAN_STOK_LIHAT',
        ];

        $this->assertSame(41, DB::table('hak_akses')->whereNull('deleted_at')->count());
        $this->assertSame(
            collect($kodeWajib)->sort()->values()->all(),
            DB::table('hak_akses')
                ->whereIn('kode_hak_akses', $kodeWajib)
                ->whereNull('deleted_at')
                ->orderBy('kode_hak_akses')
                ->pluck('kode_hak_akses')
                ->all()
        );

        $database = config('database.connections.mysql.database');
        $this->assertSame(71, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'BASE TABLE')->count());
        $this->assertSame(3, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'VIEW')->count());
        $this->assertSame(0, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->whereIn('TABLE_NAME', ['sessions', 'cache', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'])->count());
    }

    public function test_stok_awal_disetujui_membentuk_saldo_dan_mutasi(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('PCS-STOK-AWAL', 0);
        [$gudang, $lokasi] = $this->buatGudangDanLokasi($cabang, 'G-SA', 'L-SA');

        $idStokAwal = $this->buatStokAwalDisetujui($admin, $cabang, $gudang, $lokasi, $barangSatuan, 12, 7500);

        $this->assertSame('DISETUJUI', DB::table('stok_awal')->where('id_stok_awal', $idStokAwal)->value('status_stok_awal'));
        $saldo = DB::table('saldo_stok')->where('id_gudang', $gudang)->where('id_lokasi_gudang', $lokasi)->where('id_barang', $barang)->firstOrFail();
        $this->assertEqualsWithDelta(12, (float) $saldo->jumlah_stok, 0.0001);
        $this->assertEqualsWithDelta(7500, (float) $saldo->harga_pokok_rata_rata, 0.0001);
        $this->assertSame(1, DB::table('mutasi_stok')->where('jenis_mutasi', 'STOK_AWAL')->where('id_dokumen', $idStokAwal)->count());
    }

    public function test_stok_awal_mengikuti_jumlah_desimal_satuan(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [, $barangSatuan] = $this->buatBarang('PCS-TANPA-DESIMAL', 0);
        [$gudang, $lokasi] = $this->buatGudangDanLokasi($cabang, 'G-DEC', 'L-DEC');

        $this->actingAs($admin)
            ->withSession($this->sessionCabang($cabang))
            ->post('/stok-awal', $this->payloadStokAwal($gudang, $lokasi, $barangSatuan, 1.5, 1000))
            ->assertSessionHasErrors('detail_stok.0.jumlah');

        $this->assertSame(0, DB::table('stok_awal')->count());
    }

    public function test_transfer_antar_lokasi_mengurangi_asal_dan_menambah_tujuan(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('PCS-TRANSFER', 0);
        [$gudang, $lokasiAsal] = $this->buatGudangDanLokasi($cabang, 'G-TR', 'L-ASAL');
        $lokasiTujuan = $this->buatLokasi($gudang, 'L-TUJUAN');
        $this->buatStokAwalDisetujui($admin, $cabang, $gudang, $lokasiAsal, $barangSatuan, 10, 5000);

        $session = $this->sessionCabang($cabang);
        $this->actingAs($admin)->withSession($session)->post('/transfer-stok', [
            'id_gudang_asal' => $gudang,
            'id_gudang_tujuan' => $gudang,
            'tanggal_transfer' => '2026-07-22',
            'detail_transfer' => [[
                'id_barang_satuan' => $barangSatuan,
                'id_lokasi_asal' => $lokasiAsal,
                'id_lokasi_tujuan' => $lokasiTujuan,
                'jumlah_diminta' => 4,
            ]],
        ])->assertSessionHasNoErrors();

        $idTransfer = (int) DB::table('transfer_stok')->value('id_transfer_stok');
        $this->actingAs($admin)->withSession($session)->patch('/transfer-stok/'.$idTransfer.'/setujui')->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch('/transfer-stok/'.$idTransfer.'/kirim')->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(6, $this->jumlahStok($gudang, $lokasiAsal, $barang), 0.0001);
        $this->assertEqualsWithDelta(0, $this->jumlahStok($gudang, $lokasiTujuan, $barang), 0.0001);
        $this->assertSame('DIKIRIM', DB::table('transfer_stok')->where('id_transfer_stok', $idTransfer)->value('status_transfer'));

        $this->actingAs($admin)->withSession($session)->patch('/transfer-stok/'.$idTransfer.'/terima')->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(4, $this->jumlahStok($gudang, $lokasiTujuan, $barang), 0.0001);
        $this->assertSame('DITERIMA', DB::table('transfer_stok')->where('id_transfer_stok', $idTransfer)->value('status_transfer'));
        $this->assertSame(1, DB::table('mutasi_stok')->where('id_dokumen', $idTransfer)->where('jenis_mutasi', 'TRANSFER_KELUAR')->count());
        $this->assertSame(1, DB::table('mutasi_stok')->where('id_dokumen', $idTransfer)->where('jenis_mutasi', 'TRANSFER_MASUK')->count());
    }

    public function test_transfer_stok_tidak_cukup_ditolak_dan_dirollback(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('PCS-TRANSFER-KURANG', 0);
        [$gudangAsal, $lokasiAsal] = $this->buatGudangDanLokasi($cabang, 'G-KURANG-A', 'L-KURANG-A');
        [$gudangTujuan, $lokasiTujuan] = $this->buatGudangDanLokasi($cabang, 'G-KURANG-B', 'L-KURANG-B');
        $this->buatStokAwalDisetujui($admin, $cabang, $gudangAsal, $lokasiAsal, $barangSatuan, 2, 3000);

        $session = $this->sessionCabang($cabang);
        $this->actingAs($admin)->withSession($session)->post('/transfer-stok', [
            'id_gudang_asal' => $gudangAsal,
            'id_gudang_tujuan' => $gudangTujuan,
            'tanggal_transfer' => '2026-07-22',
            'detail_transfer' => [[
                'id_barang_satuan' => $barangSatuan,
                'id_lokasi_asal' => $lokasiAsal,
                'id_lokasi_tujuan' => $lokasiTujuan,
                'jumlah_diminta' => 3,
            ]],
        ])->assertSessionHasNoErrors();
        $idTransfer = (int) DB::table('transfer_stok')->max('id_transfer_stok');
        $this->actingAs($admin)->withSession($session)->patch('/transfer-stok/'.$idTransfer.'/setujui')->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch('/transfer-stok/'.$idTransfer.'/kirim')->assertSessionHasErrors('jumlah');

        $this->assertSame('DISETUJUI', DB::table('transfer_stok')->where('id_transfer_stok', $idTransfer)->value('status_transfer'));
        $this->assertEqualsWithDelta(2, $this->jumlahStok($gudangAsal, $lokasiAsal, $barang), 0.0001);
        $this->assertSame(0, DB::table('mutasi_stok')->where('id_dokumen', $idTransfer)->count());
    }

    public function test_stok_opname_membentuk_penyesuaian_dan_menyamakan_stok_fisik(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('PCS-OPNAME', 0);
        [$gudang, $lokasi] = $this->buatGudangDanLokasi($cabang, 'G-OPNAME', 'L-OPNAME');
        $this->buatStokAwalDisetujui($admin, $cabang, $gudang, $lokasi, $barangSatuan, 10, 4000);

        $session = $this->sessionCabang($cabang);
        $this->actingAs($admin)->withSession($session)->post('/stok-opname', [
            'id_gudang' => $gudang,
            'tanggal_stok_opname' => '2026-07-22',
            'detail_opname' => [[
                'id_barang' => $barang,
                'id_lokasi_gudang' => $lokasi,
                'jumlah_fisik' => 7,
            ]],
        ])->assertSessionHasNoErrors();
        $idOpname = (int) DB::table('stok_opname')->value('id_stok_opname');

        $this->actingAs($admin)->withSession($session)->patch('/stok-opname/'.$idOpname.'/mulai')->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch('/stok-opname/'.$idOpname.'/selesai')->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession($session)->patch('/stok-opname/'.$idOpname.'/setujui')->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(7, $this->jumlahStok($gudang, $lokasi, $barang), 0.0001);
        $this->assertSame('DISETUJUI', DB::table('stok_opname')->where('id_stok_opname', $idOpname)->value('status_stok_opname'));
        $idPenyesuaian = (int) DB::table('penyesuaian_stok')->where('id_stok_opname', $idOpname)->value('id_penyesuaian_stok');
        $this->assertGreaterThan(0, $idPenyesuaian);
        $this->assertSame('DISETUJUI', DB::table('penyesuaian_stok')->where('id_penyesuaian_stok', $idPenyesuaian)->value('status_penyesuaian'));
        $this->assertSame(1, DB::table('mutasi_stok')->where('jenis_mutasi', 'STOK_OPNAME')->where('id_dokumen', $idPenyesuaian)->count());
    }

    public function test_penyesuaian_keluar_melebihi_stok_ditolak_atomik(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('PCS-PENYESUAIAN', 0);
        [$gudang, $lokasi] = $this->buatGudangDanLokasi($cabang, 'G-PEN', 'L-PEN');
        $this->buatStokAwalDisetujui($admin, $cabang, $gudang, $lokasi, $barangSatuan, 2, 2500);

        $session = $this->sessionCabang($cabang);
        $this->actingAs($admin)->withSession($session)->post('/penyesuaian-stok', [
            'id_gudang' => $gudang,
            'tanggal_penyesuaian' => '2026-07-22',
            'alasan_penyesuaian' => 'Uji koreksi kurang',
            'detail_penyesuaian' => [[
                'id_barang' => $barang,
                'id_lokasi_gudang' => $lokasi,
                'jenis_penyesuaian' => 'KURANG',
                'jumlah_dasar' => 3,
                'harga_pokok' => 2500,
            ]],
        ])->assertSessionHasNoErrors();
        $idPenyesuaian = (int) DB::table('penyesuaian_stok')->value('id_penyesuaian_stok');
        $this->actingAs($admin)->withSession($session)->patch('/penyesuaian-stok/'.$idPenyesuaian.'/setujui')->assertSessionHasErrors('jumlah');

        $this->assertSame('DRAF', DB::table('penyesuaian_stok')->where('id_penyesuaian_stok', $idPenyesuaian)->value('status_penyesuaian'));
        $this->assertEqualsWithDelta(2, $this->jumlahStok($gudang, $lokasi, $barang), 0.0001);
        $this->assertSame(0, DB::table('mutasi_stok')->where('jenis_dokumen', 'PENYESUAIAN_STOK')->where('id_dokumen', $idPenyesuaian)->count());
    }

    public function test_stok_di_gudang_rusak_tidak_menjadi_stok_tersedia(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        [$barang, $barangSatuan] = $this->buatBarang('PCS-RUSAK', 0);
        $gudang = (int) DB::table('gudang')->where('id_cabang', $cabang->id_cabang)->where('kode_gudang', 'RUSAK')->value('id_gudang');
        $lokasi = (int) DB::table('lokasi_gudang')->where('id_gudang', $gudang)->where('kode_lokasi', 'AREA-RUSAK')->value('id_lokasi_gudang');
        $this->assertGreaterThan(0, $gudang);
        $this->assertGreaterThan(0, $lokasi);

        $this->buatStokAwalDisetujui($admin, $cabang, $gudang, $lokasi, $barangSatuan, 3, 1000);
        $saldo = DB::table('saldo_stok')->where('id_gudang', $gudang)->where('id_lokasi_gudang', $lokasi)->where('id_barang', $barang)->firstOrFail();
        $this->assertEqualsWithDelta(3, (float) $saldo->jumlah_rusak, 0.0001);
        $this->assertEqualsWithDelta(0, (float) DB::table('tampilan_stok_tersedia')->where('id_saldo_stok', $saldo->id_saldo_stok)->value('jumlah_tersedia'), 0.0001);
    }

    public function test_kasir_hanya_dapat_melihat_persediaan_tanpa_mengelola(): void
    {
        [, $cabang] = $this->adminDanCabang();
        $kasir = Pengguna::query()->create([
            'nama_pengguna' => 'kasir_fase4',
            'kata_sandi' => Hash::make('KasirFase4!2026'),
            'nama_tampilan' => 'Kasir Fase 4',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $peran = Peran::query()->where('kode_peran', 'KASIR')->firstOrFail();
        PenggunaPeran::query()->create([
            'id_pengguna' => $kasir->id_pengguna,
            'id_peran' => $peran->id_peran,
            'id_cabang' => $cabang->id_cabang,
            'created_at' => now(),
        ]);

        $this->actingAs($kasir)->withSession($this->sessionCabang($cabang))->get('/persediaan')->assertOk();
        $this->actingAs($kasir)->withSession($this->sessionCabang($cabang))->post('/stok-awal', [])->assertForbidden();
    }

    public function test_dokumen_cabang_lain_tidak_dapat_disetujui_dengan_manipulasi_id(): void
    {
        [$admin, $cabangAktif] = $this->adminDanCabang();
        $idCabangLain = (int) DB::table('cabang')->insertGetId([
            'kode_cabang' => 'CAB-PERSEDIAAN-LAIN',
            'nama_cabang' => 'Cabang Persediaan Lain',
            'alamat' => 'Alamat lain',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $idGudang = (int) DB::table('gudang')->insertGetId([
            'id_cabang' => $idCabangLain,
            'kode_gudang' => 'G-LAIN',
            'nama_gudang' => 'Gudang Lain',
            'jenis_gudang' => 'UTAMA',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $idStokAwal = (int) DB::table('stok_awal')->insertGetId([
            'id_cabang' => $idCabangLain,
            'id_gudang' => $idGudang,
            'nomor_stok_awal' => 'SA-LAIN',
            'tanggal_stok_awal' => '2026-07-22',
            'status_stok_awal' => 'DRAF',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession($this->sessionCabang($cabangAktif))
            ->patch('/stok-awal/'.$idStokAwal.'/setujui')
            ->assertNotFound();
    }

    private function adminDanCabang(): array
    {
        return [
            Pengguna::query()->where('nama_pengguna', 'admin_fase4')->firstOrFail(),
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

    private function buatBarang(string $kodeSatuan, int $jumlahDesimal): array
    {
        $idKategori = (int) DB::table('kategori_barang')->insertGetId([
            'kode_kategori' => 'KAT-'.$kodeSatuan,
            'nama_kategori' => 'Kategori '.$kodeSatuan,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $idSatuan = (int) DB::table('satuan')->insertGetId([
            'kode_satuan' => $kodeSatuan,
            'nama_satuan' => 'Satuan '.$kodeSatuan,
            'jumlah_desimal' => $jumlahDesimal,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $idBarang = (int) DB::table('barang')->insertGetId([
            'id_kategori_barang' => $idKategori,
            'id_satuan_dasar' => $idSatuan,
            'kode_barang' => 'BRG-'.$kodeSatuan,
            'nama_barang' => 'Barang '.$kodeSatuan,
            'jenis_barang' => 'BARANG',
            'metode_persediaan' => 'RATA_RATA',
            'bisa_dibeli' => 1,
            'bisa_dijual' => 1,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $idBarangSatuan = (int) DB::table('barang_satuan')->insertGetId([
            'id_barang' => $idBarang,
            'id_satuan' => $idSatuan,
            'nilai_konversi' => 1,
            'harga_beli_acuan' => 0,
            'harga_jual_acuan' => 0,
            'satuan_utama_pembelian' => 1,
            'satuan_utama_penjualan' => 1,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);

        return [$idBarang, $idBarangSatuan];
    }

    private function buatGudangDanLokasi(Cabang $cabang, string $kodeGudang, string $kodeLokasi): array
    {
        $idGudang = (int) DB::table('gudang')->insertGetId([
            'id_cabang' => $cabang->id_cabang,
            'kode_gudang' => $kodeGudang,
            'nama_gudang' => 'Gudang '.$kodeGudang,
            'jenis_gudang' => 'UTAMA',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);

        return [$idGudang, $this->buatLokasi($idGudang, $kodeLokasi)];
    }

    private function buatLokasi(int $idGudang, string $kodeLokasi): int
    {
        return (int) DB::table('lokasi_gudang')->insertGetId([
            'id_gudang' => $idGudang,
            'kode_lokasi' => $kodeLokasi,
            'nama_lokasi' => 'Lokasi '.$kodeLokasi,
            'jenis_lokasi' => 'AREA_UMUM',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
    }

    private function buatStokAwalDisetujui(
        Pengguna $admin,
        Cabang $cabang,
        int $idGudang,
        int $idLokasi,
        int $idBarangSatuan,
        float $jumlah,
        float $hargaPokok,
    ): int {
        $session = $this->sessionCabang($cabang);
        $this->actingAs($admin)
            ->withSession($session)
            ->post('/stok-awal', $this->payloadStokAwal($idGudang, $idLokasi, $idBarangSatuan, $jumlah, $hargaPokok))
            ->assertSessionHasNoErrors();
        $id = (int) DB::table('stok_awal')->max('id_stok_awal');
        $this->actingAs($admin)->withSession($session)->patch('/stok-awal/'.$id.'/setujui')->assertSessionHasNoErrors();

        return $id;
    }

    private function payloadStokAwal(int $idGudang, int $idLokasi, int $idBarangSatuan, float $jumlah, float $hargaPokok): array
    {
        return [
            'id_gudang' => $idGudang,
            'tanggal_stok_awal' => '2026-07-22',
            'detail_stok' => [[
                'id_barang_satuan' => $idBarangSatuan,
                'id_lokasi_gudang' => $idLokasi,
                'jumlah' => $jumlah,
                'harga_pokok' => $hargaPokok,
            ]],
        ];
    }

    private function jumlahStok(int $idGudang, int $idLokasi, int $idBarang): float
    {
        return (float) (DB::table('saldo_stok')
            ->where('id_gudang', $idGudang)
            ->where('id_lokasi_gudang', $idLokasi)
            ->where('id_barang', $idBarang)
            ->value('jumlah_stok') ?? 0);
    }
}
