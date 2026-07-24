<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use App\Services\KatalogPanduanPengguna;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class FaseSebelasUatSerahTerimaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE11_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration Fase 11 hanya dijalankan pada job MySQL khusus.');
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_skema_dan_permission_tetap_paten(): void
    {
        $database = config('database.connections.mysql.database');

        $this->assertSame(98, DB::table('hak_akses')->whereNull('deleted_at')->count());
        $this->assertSame(71, DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->count());
        $this->assertSame(3, DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_TYPE', 'VIEW')
            ->count());
        $this->assertSame(0, DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $database)
            ->whereIn('TABLE_NAME', [
                'sessions',
                'cache',
                'jobs',
                'job_batches',
                'failed_jobs',
                'password_reset_tokens',
            ])
            ->count());
    }

    public function test_pusat_bantuan_memerlukan_login_dan_cabang_aktif(): void
    {
        $this->get('/panduan')->assertRedirect('/masuk');

        [$admin, $cabang] = $this->adminDanCabang();

        $this->actingAs($admin)
            ->withSession([
                'id_cabang_aktif' => $cabang->id_cabang,
                'nama_cabang_aktif' => $cabang->nama_cabang,
            ])
            ->get('/panduan')
            ->assertOk()
            ->assertSee('Pusat Bantuan')
            ->assertSee('POS, Penjualan, dan Piutang')
            ->assertSee('Pengguna, Peran, dan Hak Akses')
            ->assertSee('Dukungan dan Pelaporan Masalah');
    }

    public function test_katalog_panduan_menyembunyikan_modul_tanpa_permission(): void
    {
        $pengguna = Mockery::mock(Pengguna::class)->makePartial();
        $pengguna->shouldReceive('memilikiHakAkses')
            ->andReturnUsing(fn (string $izin): bool => in_array($izin, [
                'PENJUALAN_LIHAT',
                'PIUTANG_PELANGGAN_LIHAT',
            ], true));

        $panduan = app(KatalogPanduanPengguna::class)->untuk($pengguna, 1);
        $kode = collect($panduan)->pluck('kode')->all();

        $this->assertContains('mulai', $kode);
        $this->assertContains('penjualan', $kode);
        $this->assertContains('dukungan', $kode);
        $this->assertNotContains('administrasi', $kode);
        $this->assertNotContains('pembelian', $kode);
        $this->assertNotContains('keuangan', $kode);
    }

    public function test_manifest_release_candidate_dapat_dibuat_dan_diverifikasi(): void
    {
        $nama = 'manifest-fase11-'.uniqid().'.json';
        $lokasi = storage_path('app/release-candidate/'.$nama);

        try {
            $this->artisan('sistem:buat-manifest-rilis', [
                'versi' => 'v1.0.0-rc1',
                '--nama' => $nama,
                '--json' => true,
            ])->assertSuccessful();

            $this->assertFileExists($lokasi);
            $manifest = json_decode((string) file_get_contents($lokasi), true, flags: JSON_THROW_ON_ERROR);

            $this->assertSame('harisuix-pos-release-manifest-v1', $manifest['format']);
            $this->assertSame('v1.0.0-rc1', $manifest['versi']);
            $this->assertSame(71, $manifest['database']['base_table']);
            $this->assertSame(3, $manifest['database']['view']);
            $this->assertSame(98, $manifest['database']['permission_aktif']);
            $this->assertSame(0, $manifest['database']['tabel_infrastruktur_dilarang']);
            $this->assertTrue($manifest['batasan']['manifest_tidak_memuat_kredensial']);

            $isi = (string) file_get_contents($lokasi);
            $this->assertStringNotContainsString((string) config('database.connections.mysql.password'), $isi);
            $this->assertStringNotContainsString((string) config('database.connections.mysql.username').'@', $isi);

            $this->artisan('sistem:verifikasi-manifest-rilis', [
                'berkas' => $lokasi,
                '--json' => true,
            ])->assertSuccessful();
        } finally {
            @unlink($lokasi);
        }
    }

    public function test_manifest_dengan_checksum_diubah_ditolak(): void
    {
        $nama = 'manifest-fase11-rusak-'.uniqid().'.json';
        $lokasi = storage_path('app/release-candidate/'.$nama);

        try {
            $this->artisan('sistem:buat-manifest-rilis', [
                'versi' => 'v1.0.0-rc1',
                '--nama' => $nama,
            ])->assertSuccessful();

            $manifest = json_decode((string) file_get_contents($lokasi), true, flags: JSON_THROW_ON_ERROR);
            $berkasPertama = array_key_first($manifest['integritas']['berkas']);
            $manifest['integritas']['berkas'][$berkasPertama] = str_repeat('0', 64);
            file_put_contents(
                $lokasi,
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            );

            $this->artisan('sistem:verifikasi-manifest-rilis', [
                'berkas' => $lokasi,
            ])->assertFailed();
        } finally {
            @unlink($lokasi);
        }
    }

    private function adminDanCabang(): array
    {
        return [
            Pengguna::query()->where('nama_pengguna', 'admin_fase11')->firstOrFail(),
            Cabang::query()->where('kode_cabang', 'CAB-UJI')->firstOrFail(),
        ];
    }
}
