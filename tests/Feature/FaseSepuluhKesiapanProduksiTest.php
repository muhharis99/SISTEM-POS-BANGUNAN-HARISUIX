<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaseSepuluhKesiapanProduksiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE10_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration Fase 10 hanya dijalankan pada job MySQL khusus.');
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

    public function test_endpoint_kesiapan_tidak_membocorkan_data_sensitif(): void
    {
        $respons = $this->get('/kesiapan')
            ->assertOk()
            ->assertJsonPath('status', 'siap')
            ->assertJsonPath('komponen.database', 'berhasil')
            ->assertJsonPath('komponen.skema_tabel', 'berhasil')
            ->assertJsonPath('komponen.skema_view', 'berhasil');

        $cacheControl = (string) $respons->headers->get('cache-control');
        foreach (['no-store', 'no-cache', 'must-revalidate', 'private'] as $direktif) {
            $this->assertStringContainsString($direktif, $cacheControl);
        }

        $isi = $respons->getContent();
        $this->assertStringNotContainsString((string) config('database.connections.mysql.database'), $isi);
        $this->assertStringNotContainsString((string) config('database.connections.mysql.username'), $isi);
        $this->assertStringNotContainsString((string) config('database.connections.mysql.password'), $isi);
        $this->assertStringNotContainsString(base_path(), $isi);
    }

    public function test_command_pemeriksaan_produksi_berhasil_untuk_kondisi_kritis(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://pos.example.test',
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'session.driver' => 'file',
            'cache.default' => 'file',
            'queue.default' => 'sync',
            'database.default' => 'mysql',
        ]);

        $this->artisan('sistem:periksa-produksi', ['--json' => true])
            ->assertSuccessful();
    }

    public function test_backup_dan_restore_memiliki_mode_simulasi_aman(): void
    {
        $direktori = storage_path('framework/testing/fase10-'.uniqid());
        mkdir($direktori, 0700, true);
        $berkas = $direktori.'/contoh.sql.gz';
        $gzip = gzopen($berkas, 'wb9');
        $this->assertNotFalse($gzip);
        gzwrite($gzip, "SELECT 1;\n");
        gzclose($gzip);
        file_put_contents(
            $berkas.'.sha256',
            hash_file('sha256', $berkas).'  '.basename($berkas).PHP_EOL
        );

        try {
            $this->artisan('sistem:backup-database', [
                '--direktori' => $direktori,
                '--retensi-hari' => 14,
                '--simulasi' => true,
                '--json' => true,
            ])->assertSuccessful();

            $this->artisan('sistem:restore-database', [
                'berkas' => $berkas,
                '--konfirmasi' => 'RESTORE',
                '--simulasi' => true,
                '--json' => true,
            ])->assertSuccessful();
        } finally {
            @unlink($berkas.'.sha256');
            @unlink($berkas);
            @rmdir($direktori);
        }
    }

    public function test_pengguna_tetap_dapat_mengakses_dashboard_setelah_route_fase_sepuluh_didaftarkan(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();

        $this->actingAs($admin)
            ->withSession([
                'id_cabang_aktif' => $cabang->id_cabang,
                'nama_cabang_aktif' => $cabang->nama_cabang,
            ])
            ->get('/dashboard')
            ->assertOk();
    }

    private function adminDanCabang(): array
    {
        return [
            Pengguna::query()->where('nama_pengguna', 'admin_fase10')->firstOrFail(),
            Cabang::query()->where('kode_cabang', 'CAB-UJI')->firstOrFail(),
        ];
    }
}
