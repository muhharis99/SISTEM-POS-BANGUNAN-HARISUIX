<?php

namespace Tests\Feature;

use App\Services\KontrakRilisFinal;
use App\Services\PembuatPaketRilisFinal;
use App\Services\PemeriksaGoLive;
use App\Services\PemeriksaPascadeploy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

class FaseDuaBelasFinalReleaseTest extends TestCase
{
    private string $direktoriUji;

    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE12_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration Fase 12 hanya dijalankan pada job MySQL khusus.');
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });

        $this->direktoriUji = storage_path('framework/testing/fase12-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($this->direktoriUji, 0700, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->direktoriUji);
        parent::tearDown();
    }

    public function test_kontrak_rilis_final_tetap_paten(): void
    {
        $hasil = app(KontrakRilisFinal::class)->periksa();

        $this->assertTrue($hasil['valid']);
        $this->assertSame('v1.0.0', $hasil['aktual']['versi']);
        $this->assertSame(71, $hasil['aktual']['base_table']);
        $this->assertSame(3, $hasil['aktual']['view']);
        $this->assertSame(98, $hasil['aktual']['permission_aktif']);
        $this->assertSame(0, $hasil['aktual']['tabel_infrastruktur_dilarang']);
    }

    public function test_paket_final_dapat_dibuat_diverifikasi_dan_mendeteksi_kerusakan(): void
    {
        $pembuat = app(PembuatPaketRilisFinal::class);
        $hasil = $pembuat->buat('v1.0.0', $this->direktoriUji);

        $this->assertFileExists($hasil['paket']);
        $this->assertFileExists($hasil['paket'].'.sha256');
        $this->assertSame(0600, fileperms($hasil['paket']) & 0777);
        $this->assertSame(0600, fileperms($hasil['paket'].'.sha256') & 0777);
        $this->assertSame(hash_file('sha256', $hasil['paket']), $hasil['checksum']);
        $this->assertGreaterThan(0, $hasil['ukuran_byte']);
        $this->assertGreaterThan(100, $hasil['jumlah_berkas']);

        $verifikasi = $pembuat->verifikasi($hasil['paket']);
        $this->assertTrue($verifikasi['valid']);
        $this->assertSame('v1.0.0', $verifikasi['versi']);
        $this->assertSame($hasil['commit'], $verifikasi['commit']);
        $this->assertTrue($verifikasi['pemeriksaan']['format_manifest']);
        $this->assertTrue($verifikasi['pemeriksaan']['inventaris_berkas']);
        $this->assertTrue($verifikasi['pemeriksaan']['integritas_kritis']);
        $this->assertTrue($verifikasi['pemeriksaan']['batasan_keamanan']);

        $paketRusak = $this->direktoriUji.'/paket-rusak.tar.gz';
        copy($hasil['paket'], $paketRusak);
        copy($hasil['paket'].'.sha256', $paketRusak.'.sha256');
        file_put_contents($paketRusak, 'rusak', FILE_APPEND | LOCK_EX);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Checksum paket rilis final tidak cocok.');
        $pembuat->verifikasi($paketRusak);
    }

    public function test_versi_prerelease_ditolak_oleh_pembuat_paket_final(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Versi final harus mengikuti pola semver stabil');

        app(PembuatPaketRilisFinal::class)->buat('v1.0.0-rc1', $this->direktoriUji);
    }

    public function test_smoke_test_dan_gate_go_live_berhasil_dengan_backup_dan_paket_valid(): void
    {
        $this->aturKonfigurasiProduksi();
        $backup = $this->buatBackupUji();
        $hasilPaket = app(PembuatPaketRilisFinal::class)->buat('v1.0.0', $this->direktoriUji.'/rilis');

        $smoke = app(PemeriksaPascadeploy::class)->periksa();
        $this->assertTrue($smoke['berhasil']);
        $this->assertNotContains('GAGAL', array_column($smoke['pemeriksaan'], 'status'));

        $goLive = app(PemeriksaGoLive::class)->periksa(
            dirname($backup),
            24,
            $hasilPaket['paket']
        );

        $this->assertTrue($goLive['siap']);
        $this->assertSame('v1.0.0', $goLive['kontrak']['aktual']['versi']);
        $this->assertSame(basename($backup), $goLive['backup_terbaru']['nama']);
        $this->assertNotContains('GAGAL', array_column($goLive['pemeriksaan'], 'status'));
    }

    public function test_gate_go_live_menolak_backup_dengan_checksum_salah(): void
    {
        $this->aturKonfigurasiProduksi();
        $backup = $this->buatBackupUji();
        file_put_contents($backup.'.sha256', str_repeat('0', 64).'  '.basename($backup).PHP_EOL, LOCK_EX);

        $hasil = app(PemeriksaGoLive::class)->periksa(dirname($backup), 24, null);
        $status = collect($hasil['pemeriksaan'])->keyBy('kode');

        $this->assertFalse($hasil['siap']);
        $this->assertSame('GAGAL', $status['checksum_backup']['status']);
        $this->assertSame('PERINGATAN', $status['paket_rilis']['status']);
    }

    private function aturKonfigurasiProduksi(): void
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
    }

    private function buatBackupUji(): string
    {
        $direktori = $this->direktoriUji.'/backup';
        File::ensureDirectoryExists($direktori, 0700, true);
        $backup = $direktori.'/backup-sistem-uji-'.now()->format('Ymd-His').'.sql.gz';
        $gzip = gzopen($backup, 'wb9');
        $this->assertNotFalse($gzip);
        gzwrite($gzip, "-- backup uji Fase 12\nSELECT 1;\n");
        gzclose($gzip);
        @chmod($backup, 0600);

        $checksum = hash_file('sha256', $backup);
        $this->assertNotFalse($checksum);
        file_put_contents($backup.'.sha256', $checksum.'  '.basename($backup).PHP_EOL, LOCK_EX);
        @chmod($backup.'.sha256', 0600);

        return $backup;
    }
}
