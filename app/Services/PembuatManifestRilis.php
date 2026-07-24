<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;
use Symfony\Component\Process\Process;

class PembuatManifestRilis
{
    private const FORMAT = 'harisuix-pos-release-manifest-v1';

    private const BERKAS_KRITIS = [
        'composer.json',
        'composer.lock',
        'struktur_database_toko_bangunan.sql',
        'app/Providers/AppServiceProvider.php',
        'routes/web.php',
        'routes/fase8.php',
        'routes/fase9.php',
        'routes/fase10.php',
        'routes/fase11.php',
        'scripts/deploy-production.sh',
        'scripts/rollback-production.sh',
    ];

    public function buat(string $versi): array
    {
        $versi = trim($versi);
        if (! preg_match('/^v?\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $versi)) {
            throw new RuntimeException('Versi harus mengikuti pola semver, contoh v1.0.0-rc1.');
        }

        $database = (string) config('database.connections.mysql.database');
        if ($database === '') {
            throw new RuntimeException('Nama database MySQL tidak tersedia.');
        }

        return [
            'format' => self::FORMAT,
            'versi' => $versi,
            'dibuat_pada' => now()->toIso8601String(),
            'aplikasi' => [
                'nama' => (string) config('app.name'),
                'lingkungan_pembuat' => (string) config('app.env'),
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'commit' => $this->commitAktif(),
            ],
            'database' => [
                'base_table' => $this->jumlahObjekDatabase($database, 'BASE TABLE'),
                'view' => $this->jumlahObjekDatabase($database, 'VIEW'),
                'permission_aktif' => (int) DB::table('hak_akses')->whereNull('deleted_at')->count(),
                'tabel_infrastruktur_dilarang' => $this->jumlahTabelInfrastrukturDilarang($database),
            ],
            'integritas' => [
                'algoritma' => 'sha256',
                'berkas' => $this->checksumBerkasKritis(),
            ],
            'batasan' => [
                'manifest_tidak_memuat_kredensial' => true,
                'manifest_tidak_memuat_data_transaksi' => true,
                'deployment_otomatis' => false,
                'auto_merge' => false,
            ],
        ];
    }

    public function simpan(array $manifest, ?string $namaBerkas = null): string
    {
        $namaBerkas = trim((string) ($namaBerkas ?: 'manifest-rilis.json'));
        $namaBerkas = basename($namaBerkas);

        if (! preg_match('/^[0-9A-Za-z._-]+\.json$/', $namaBerkas)) {
            throw new RuntimeException('Nama manifest hanya boleh memuat huruf, angka, titik, garis bawah, dan tanda hubung.');
        }

        $direktori = storage_path('app/release-candidate');
        File::ensureDirectoryExists($direktori, 0700, true);
        $lokasi = $direktori.DIRECTORY_SEPARATOR.$namaBerkas;

        try {
            $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Manifest tidak dapat diubah menjadi JSON: '.$exception->getMessage(), 0, $exception);
        }

        if (File::put($lokasi, $json.PHP_EOL, true) === false) {
            throw new RuntimeException('Manifest release candidate tidak dapat disimpan.');
        }

        @chmod($lokasi, 0600);

        return $lokasi;
    }

    public function verifikasi(string $lokasi): array
    {
        $lokasi = realpath($lokasi) ?: '';
        if ($lokasi === '' || ! is_file($lokasi) || ! is_readable($lokasi)) {
            throw new RuntimeException('Berkas manifest tidak ditemukan atau tidak dapat dibaca.');
        }

        try {
            $manifest = json_decode((string) file_get_contents($lokasi), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Manifest bukan JSON yang valid: '.$exception->getMessage(), 0, $exception);
        }

        if (! is_array($manifest) || ($manifest['format'] ?? null) !== self::FORMAT) {
            throw new RuntimeException('Format manifest release candidate tidak dikenali.');
        }

        $database = (string) config('database.connections.mysql.database');
        $pemeriksaan = [
            'format' => true,
            'base_table' => ($manifest['database']['base_table'] ?? null) === $this->jumlahObjekDatabase($database, 'BASE TABLE'),
            'view' => ($manifest['database']['view'] ?? null) === $this->jumlahObjekDatabase($database, 'VIEW'),
            'permission_aktif' => ($manifest['database']['permission_aktif'] ?? null) === (int) DB::table('hak_akses')->whereNull('deleted_at')->count(),
            'tabel_infrastruktur_dilarang' => ($manifest['database']['tabel_infrastruktur_dilarang'] ?? null) === $this->jumlahTabelInfrastrukturDilarang($database),
            'integritas_berkas' => $this->verifikasiChecksum($manifest['integritas']['berkas'] ?? []),
        ];

        return [
            'valid' => ! in_array(false, $pemeriksaan, true),
            'versi' => (string) ($manifest['versi'] ?? ''),
            'commit' => (string) ($manifest['aplikasi']['commit'] ?? ''),
            'pemeriksaan' => $pemeriksaan,
        ];
    }

    private function jumlahObjekDatabase(string $database, string $jenis): int
    {
        return (int) DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_TYPE', $jenis)
            ->count();
    }

    private function jumlahTabelInfrastrukturDilarang(string $database): int
    {
        return (int) DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $database)
            ->whereIn('TABLE_NAME', [
                'sessions',
                'cache',
                'jobs',
                'job_batches',
                'failed_jobs',
                'password_reset_tokens',
            ])
            ->count();
    }

    private function checksumBerkasKritis(): array
    {
        $hasil = [];

        foreach (self::BERKAS_KRITIS as $berkas) {
            $lokasi = base_path($berkas);
            if (! is_file($lokasi) || ! is_readable($lokasi)) {
                throw new RuntimeException("Berkas kritis {$berkas} tidak ditemukan atau tidak dapat dibaca.");
            }

            $checksum = hash_file('sha256', $lokasi);
            if ($checksum === false) {
                throw new RuntimeException("Checksum berkas {$berkas} tidak dapat dibuat.");
            }

            $hasil[$berkas] = $checksum;
        }

        ksort($hasil);

        return $hasil;
    }

    private function verifikasiChecksum(mixed $daftar): bool
    {
        if (! is_array($daftar) || $daftar === []) {
            return false;
        }

        foreach ($daftar as $berkas => $checksumTersimpan) {
            if (! is_string($berkas) || ! is_string($checksumTersimpan)) {
                return false;
            }

            $lokasi = base_path($berkas);
            $checksumAktual = is_file($lokasi) ? hash_file('sha256', $lokasi) : false;

            if ($checksumAktual === false || ! hash_equals(strtolower($checksumTersimpan), strtolower($checksumAktual))) {
                return false;
            }
        }

        return true;
    }

    private function commitAktif(): string
    {
        $dariLingkungan = trim((string) env('RELEASE_COMMIT', env('GITHUB_SHA', '')));
        if ($dariLingkungan !== '') {
            return $dariLingkungan;
        }

        $proses = new Process(['git', 'rev-parse', 'HEAD'], base_path());
        $proses->setTimeout(5);
        $proses->run();

        return $proses->isSuccessful() ? trim($proses->getOutput()) : 'tidak-diketahui';
    }
}
