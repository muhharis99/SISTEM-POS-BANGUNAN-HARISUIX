<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JsonException;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class PembuatPaketRilisFinal
{
    private const FORMAT_PAKET = 'harisuix-pos-final-release-v1';

    private const FORMAT_INVENTARIS = 'harisuix-pos-file-inventory-v1';

    private const JUMLAH_TABEL_PATEN = 71;

    private const JUMLAH_VIEW_PATEN = 3;

    private const JUMLAH_PERMISSION = 98;

    private const BERKAS_KRITIS = [
        'VERSION',
        'composer.json',
        'composer.lock',
        'struktur_database_toko_bangunan.sql',
        'app/Providers/AppServiceProvider.php',
        'app/Services/PemeriksaKesiapanProduksi.php',
        'app/Services/PembuatManifestRilis.php',
        'app/Services/PembuatPaketRilisFinal.php',
        'scripts/deploy-production.sh',
        'scripts/rollback-production.sh',
        'scripts/post-deploy-smoke.sh',
        'docs/RELEASE-NOTES-v1.0.0.md',
    ];

    public function buat(string $versi, ?string $direktori = null): array
    {
        $versi = $this->normalisasiVersi($versi);
        $this->pastikanVersiAplikasi($versi);

        $direktori = rtrim($direktori ?: storage_path('app/release-final'), DIRECTORY_SEPARATOR);
        $this->pastikanDirektori($direktori);

        $git = $this->cariProgram('git');
        $tar = $this->cariProgram('tar');
        $commit = $this->commitAktif($git);
        $inventarisBerkas = $this->inventarisBerkasGit($git);
        $this->pastikanTidakAdaBerkasTerlarang(array_keys($inventarisBerkas));

        $namaAman = str_replace('.', '-', ltrim($versi, 'v'));
        $namaPaket = "sistem-pos-bangunan-{$namaAman}.tar.gz";
        $lokasiPaket = $direktori.DIRECTORY_SEPARATOR.$namaPaket;
        $direktoriSementara = storage_path('framework/cache/release-final-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($direktoriSementara, 0700, true);

        try {
            $namaArsipSumber = 'source-'.$versi.'.tar.gz';
            $lokasiArsipSumber = $direktoriSementara.DIRECTORY_SEPARATOR.$namaArsipSumber;
            $this->jalankan([
                $git,
                'archive',
                '--format=tar.gz',
                '--output='.$lokasiArsipSumber,
                'HEAD',
            ], base_path(), 180);
            @chmod($lokasiArsipSumber, 0600);

            $manifest = $this->manifest($versi, $commit, $namaArsipSumber, $inventarisBerkas);
            $inventaris = [
                'format' => self::FORMAT_INVENTARIS,
                'versi' => $versi,
                'commit' => $commit,
                'jumlah_berkas' => count($inventarisBerkas),
                'berkas' => $inventarisBerkas,
            ];

            $lokasiManifest = $direktoriSementara.DIRECTORY_SEPARATOR.'manifest-rilis-final.json';
            $lokasiInventaris = $direktoriSementara.DIRECTORY_SEPARATOR.'inventaris-berkas.json';
            $lokasiCatatan = $direktoriSementara.DIRECTORY_SEPARATOR.'RELEASE-NOTES.md';

            $this->simpanJson($lokasiManifest, $manifest);
            $this->simpanJson($lokasiInventaris, $inventaris);
            $this->salinCatatanRilis($lokasiCatatan);

            $namaKomponen = [
                $namaArsipSumber,
                'manifest-rilis-final.json',
                'inventaris-berkas.json',
                'RELEASE-NOTES.md',
            ];
            $this->buatDaftarChecksum($direktoriSementara, $namaKomponen);
            $namaKomponen[] = 'checksums.sha256';

            if (is_file($lokasiPaket)) {
                @unlink($lokasiPaket);
            }

            $this->jalankan([
                $tar,
                '-czf',
                $lokasiPaket,
                '-C',
                $direktoriSementara,
                ...$namaKomponen,
            ], base_path(), 300);
            @chmod($lokasiPaket, 0600);

            $checksumPaket = hash_file('sha256', $lokasiPaket);
            if ($checksumPaket === false) {
                throw new RuntimeException('Checksum paket rilis final tidak dapat dibuat.');
            }

            $lokasiChecksum = $lokasiPaket.'.sha256';
            file_put_contents($lokasiChecksum, $checksumPaket.'  '.basename($lokasiPaket).PHP_EOL, LOCK_EX);
            @chmod($lokasiChecksum, 0600);

            return [
                'versi' => $versi,
                'commit' => $commit,
                'paket' => $lokasiPaket,
                'checksum' => $checksumPaket,
                'ukuran_byte' => filesize($lokasiPaket) ?: 0,
                'jumlah_berkas' => count($inventarisBerkas),
            ];
        } finally {
            File::deleteDirectory($direktoriSementara);
        }
    }

    public function verifikasi(string $berkas): array
    {
        $lokasiPaket = realpath($berkas) ?: '';
        if ($lokasiPaket === '' || ! is_file($lokasiPaket) || ! is_readable($lokasiPaket)) {
            throw new RuntimeException('Paket rilis final tidak ditemukan atau tidak dapat dibaca.');
        }

        if (! str_ends_with(strtolower($lokasiPaket), '.tar.gz')) {
            throw new RuntimeException('Paket rilis final harus berekstensi .tar.gz.');
        }

        $this->verifikasiChecksumPaket($lokasiPaket);
        $tar = $this->cariProgram('tar');
        $direktoriSementara = storage_path('framework/cache/verifikasi-rilis-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($direktoriSementara, 0700, true);

        try {
            $daftarPaket = $this->daftarArsip($tar, $lokasiPaket);
            $this->pastikanPathArsipAman($daftarPaket);
            $this->jalankan([
                $tar,
                '-xzf',
                $lokasiPaket,
                '--no-same-owner',
                '--no-same-permissions',
                '-C',
                $direktoriSementara,
            ], base_path(), 300);

            foreach (['manifest-rilis-final.json', 'inventaris-berkas.json', 'RELEASE-NOTES.md', 'checksums.sha256'] as $wajib) {
                if (! is_file($direktoriSementara.DIRECTORY_SEPARATOR.$wajib)) {
                    throw new RuntimeException("Komponen paket {$wajib} tidak ditemukan.");
                }
            }

            $this->verifikasiDaftarChecksum($direktoriSementara);
            $manifest = $this->bacaJson($direktoriSementara.DIRECTORY_SEPARATOR.'manifest-rilis-final.json');
            $inventaris = $this->bacaJson($direktoriSementara.DIRECTORY_SEPARATOR.'inventaris-berkas.json');
            $hasilManifest = $this->verifikasiManifest($manifest);
            $hasilInventaris = $this->verifikasiInventaris($tar, $direktoriSementara, $inventaris, $manifest);
            $pemeriksaan = [
                ...$hasilManifest,
                ...$hasilInventaris,
            ];

            return [
                'valid' => ! in_array(false, $pemeriksaan, true),
                'versi' => (string) ($manifest['versi'] ?? ''),
                'commit' => (string) ($manifest['aplikasi']['commit'] ?? ''),
                'checksum_paket' => hash_file('sha256', $lokasiPaket),
                'jumlah_berkas' => (int) ($inventaris['jumlah_berkas'] ?? 0),
                'pemeriksaan' => $pemeriksaan,
            ];
        } finally {
            File::deleteDirectory($direktoriSementara);
        }
    }

    private function manifest(string $versi, string $commit, string $namaArsipSumber, array $inventaris): array
    {
        $database = (string) config('database.connections.mysql.database');

        return [
            'format' => self::FORMAT_PAKET,
            'versi' => $versi,
            'dibuat_pada' => now()->toIso8601String(),
            'aplikasi' => [
                'nama' => (string) config('app.name'),
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'commit' => $commit,
            ],
            'database' => [
                'base_table' => $this->jumlahObjekDatabase($database, 'BASE TABLE'),
                'view' => $this->jumlahObjekDatabase($database, 'VIEW'),
                'permission_aktif' => (int) DB::table('hak_akses')->whereNull('deleted_at')->count(),
                'tabel_infrastruktur_dilarang' => $this->jumlahTabelDilarang($database),
            ],
            'sumber' => [
                'arsip' => $namaArsipSumber,
                'jumlah_berkas' => count($inventaris),
            ],
            'integritas' => [
                'algoritma' => 'sha256',
                'berkas_kritis' => $this->checksumBerkasKritis(),
            ],
            'batasan' => [
                'tanpa_kredensial' => true,
                'tanpa_env_runtime' => true,
                'tanpa_backup_database' => true,
                'tanpa_log_runtime' => true,
                'tanpa_data_transaksi' => true,
                'deployment_otomatis' => false,
                'auto_merge' => false,
            ],
        ];
    }

    private function inventarisBerkasGit(string $git): array
    {
        $proses = new Process([$git, 'ls-files', '-z'], base_path());
        $proses->setTimeout(60);
        $proses->run();

        if (! $proses->isSuccessful()) {
            throw new RuntimeException('Daftar berkas Git tidak dapat dibuat.');
        }

        $hasil = [];
        $daftar = array_filter(explode("\0", $proses->getOutput()), fn (string $item): bool => $item !== '');

        foreach ($daftar as $berkas) {
            $lokasi = base_path($berkas);
            if (! is_file($lokasi) || ! is_readable($lokasi)) {
                throw new RuntimeException("Berkas Git {$berkas} tidak ditemukan atau tidak dapat dibaca.");
            }

            $checksum = hash_file('sha256', $lokasi);
            if ($checksum === false) {
                throw new RuntimeException("Checksum berkas {$berkas} tidak dapat dibuat.");
            }

            $hasil[$berkas] = [
                'ukuran_byte' => filesize($lokasi) ?: 0,
                'sha256' => $checksum,
            ];
        }

        ksort($hasil);

        return $hasil;
    }

    private function verifikasiInventaris(string $tar, string $direktori, array $inventaris, array $manifest): array
    {
        $arsip = basename((string) ($manifest['sumber']['arsip'] ?? ''));
        $lokasiArsip = $direktori.DIRECTORY_SEPARATOR.$arsip;
        if ($arsip === '' || ! is_file($lokasiArsip)) {
            return ['arsip_sumber' => false, 'inventaris_berkas' => false];
        }

        $daftar = $this->daftarArsip($tar, $lokasiArsip);
        $this->pastikanPathArsipAman($daftar);
        $berkasInventaris = $inventaris['berkas'] ?? null;

        if (($inventaris['format'] ?? null) !== self::FORMAT_INVENTARIS || ! is_array($berkasInventaris)) {
            return ['arsip_sumber' => true, 'inventaris_berkas' => false];
        }

        $daftarNormal = array_values(array_filter($daftar, fn (string $item): bool => $item !== '' && ! str_ends_with($item, '/')));
        sort($daftarNormal);
        $daftarInventaris = array_keys($berkasInventaris);
        sort($daftarInventaris);

        return [
            'arsip_sumber' => true,
            'inventaris_berkas' => $daftarNormal === $daftarInventaris
                && (int) ($inventaris['jumlah_berkas'] ?? -1) === count($daftarInventaris)
                && ! $this->mengandungBerkasTerlarang($daftarInventaris),
        ];
    }

    private function verifikasiManifest(array $manifest): array
    {
        $database = (string) config('database.connections.mysql.database');
        $versi = (string) ($manifest['versi'] ?? '');

        return [
            'format_manifest' => ($manifest['format'] ?? null) === self::FORMAT_PAKET,
            'versi_final' => preg_match('/^v\d+\.\d+\.\d+$/', $versi) === 1,
            'versi_aplikasi' => trim((string) @file_get_contents(base_path('VERSION'))) === $versi,
            'base_table' => ($manifest['database']['base_table'] ?? null) === $this->jumlahObjekDatabase($database, 'BASE TABLE'),
            'view' => ($manifest['database']['view'] ?? null) === $this->jumlahObjekDatabase($database, 'VIEW'),
            'permission_aktif' => ($manifest['database']['permission_aktif'] ?? null) === (int) DB::table('hak_akses')->whereNull('deleted_at')->count(),
            'tabel_dilarang' => ($manifest['database']['tabel_infrastruktur_dilarang'] ?? null) === $this->jumlahTabelDilarang($database),
            'integritas_kritis' => $this->verifikasiChecksumKritis($manifest['integritas']['berkas_kritis'] ?? []),
            'batasan_keamanan' => collect($manifest['batasan'] ?? [])->every(fn (mixed $nilai): bool => $nilai === true || $nilai === false)
                && ($manifest['batasan']['tanpa_kredensial'] ?? false) === true
                && ($manifest['batasan']['tanpa_data_transaksi'] ?? false) === true
                && ($manifest['batasan']['deployment_otomatis'] ?? true) === false,
        ];
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
                throw new RuntimeException("Checksum berkas kritis {$berkas} tidak dapat dibuat.");
            }

            $hasil[$berkas] = $checksum;
        }

        ksort($hasil);

        return $hasil;
    }

    private function verifikasiChecksumKritis(mixed $daftar): bool
    {
        if (! is_array($daftar) || array_keys($daftar) !== array_keys($this->checksumBerkasKritis())) {
            return false;
        }

        foreach ($this->checksumBerkasKritis() as $berkas => $checksum) {
            if (! isset($daftar[$berkas]) || ! hash_equals($checksum, (string) $daftar[$berkas])) {
                return false;
            }
        }

        return true;
    }

    private function buatDaftarChecksum(string $direktori, array $berkas): void
    {
        $baris = [];

        foreach ($berkas as $nama) {
            $checksum = hash_file('sha256', $direktori.DIRECTORY_SEPARATOR.$nama);
            if ($checksum === false) {
                throw new RuntimeException("Checksum komponen {$nama} tidak dapat dibuat.");
            }
            $baris[] = $checksum.'  '.$nama;
        }

        $lokasi = $direktori.DIRECTORY_SEPARATOR.'checksums.sha256';
        file_put_contents($lokasi, implode(PHP_EOL, $baris).PHP_EOL, LOCK_EX);
        @chmod($lokasi, 0600);
    }

    private function verifikasiDaftarChecksum(string $direktori): void
    {
        $baris = file($direktori.DIRECTORY_SEPARATOR.'checksums.sha256', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($baris === false || $baris === []) {
            throw new RuntimeException('Daftar checksum paket kosong atau tidak dapat dibaca.');
        }

        foreach ($baris as $isi) {
            if (! preg_match('/^([a-f0-9]{64})\s{2}([0-9A-Za-z._-]+)$/i', $isi, $cocok)) {
                throw new RuntimeException('Format daftar checksum paket tidak valid.');
            }

            $lokasi = $direktori.DIRECTORY_SEPARATOR.$cocok[2];
            $aktual = is_file($lokasi) ? hash_file('sha256', $lokasi) : false;
            if ($aktual === false || ! hash_equals(strtolower($cocok[1]), strtolower($aktual))) {
                throw new RuntimeException("Checksum komponen {$cocok[2]} tidak cocok.");
            }
        }
    }

    private function verifikasiChecksumPaket(string $lokasiPaket): void
    {
        $sidecar = $lokasiPaket.'.sha256';
        if (! is_file($sidecar)) {
            throw new RuntimeException('Berkas checksum paket rilis final tidak ditemukan.');
        }

        $isi = trim((string) file_get_contents($sidecar));
        if (! preg_match('/^([a-f0-9]{64})\s{2}(.+)$/i', $isi, $cocok)) {
            throw new RuntimeException('Format checksum paket rilis final tidak valid.');
        }

        $aktual = hash_file('sha256', $lokasiPaket);
        if ($aktual === false || ! hash_equals(strtolower($cocok[1]), strtolower($aktual))) {
            throw new RuntimeException('Checksum paket rilis final tidak cocok.');
        }
    }

    private function daftarArsip(string $tar, string $arsip): array
    {
        $proses = new Process([$tar, '-tzf', $arsip], base_path());
        $proses->setTimeout(180);
        $proses->run();

        if (! $proses->isSuccessful()) {
            throw new RuntimeException('Daftar isi arsip tidak dapat dibaca.');
        }

        return array_values(array_filter(preg_split('/\r\n|\r|\n/', trim($proses->getOutput())) ?: []));
    }

    private function pastikanPathArsipAman(array $daftar): void
    {
        foreach ($daftar as $path) {
            $path = str_replace('\\', '/', $path);
            if ($path === '' || str_starts_with($path, '/') || preg_match('/(^|\/)\.\.($|\/)/', $path)) {
                throw new RuntimeException('Arsip mengandung path yang tidak aman.');
            }
        }
    }

    private function pastikanTidakAdaBerkasTerlarang(array $daftar): void
    {
        if ($this->mengandungBerkasTerlarang($daftar)) {
            throw new RuntimeException('Repository mengandung berkas runtime atau sensitif yang tidak boleh masuk paket rilis.');
        }
    }

    private function mengandungBerkasTerlarang(array $daftar): bool
    {
        foreach ($daftar as $path) {
            $path = ltrim(str_replace('\\', '/', strtolower($path)), './');
            $nama = basename($path);

            if (in_array($nama, ['.env', 'database.sqlite'], true)) {
                return true;
            }
            if (str_starts_with($nama, '.env.') && ! str_ends_with($nama, '.example')) {
                return true;
            }
            if (preg_match('#^(storage|vendor|node_modules|\.git)/#', $path)) {
                return true;
            }
            if (preg_match('/\.(log|bak|dump|pem|p12|key|sql\.gz)$/', $nama)) {
                return true;
            }
            if (preg_match('/^backup-.*\.(sql|sql\.gz|gz)$/', $nama)) {
                return true;
            }
        }

        return false;
    }

    private function salinCatatanRilis(string $tujuan): void
    {
        $sumber = base_path('docs/RELEASE-NOTES-v1.0.0.md');
        if (! is_file($sumber) || ! copy($sumber, $tujuan)) {
            throw new RuntimeException('Release notes final tidak dapat disalin ke paket.');
        }
        @chmod($tujuan, 0600);
    }

    private function simpanJson(string $lokasi, array $data): void
    {
        try {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Data paket tidak dapat diubah menjadi JSON: '.$exception->getMessage(), 0, $exception);
        }

        file_put_contents($lokasi, $json.PHP_EOL, LOCK_EX);
        @chmod($lokasi, 0600);
    }

    private function bacaJson(string $lokasi): array
    {
        try {
            $data = json_decode((string) file_get_contents($lokasi), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Komponen JSON paket tidak valid: '.$exception->getMessage(), 0, $exception);
        }

        if (! is_array($data)) {
            throw new RuntimeException('Komponen JSON paket harus berupa objek.');
        }

        return $data;
    }

    private function normalisasiVersi(string $versi): string
    {
        $versi = trim($versi);
        if (! preg_match('/^v\d+\.\d+\.\d+$/', $versi)) {
            throw new RuntimeException('Versi final harus mengikuti pola semver stabil, contoh v1.0.0.');
        }

        return $versi;
    }

    private function pastikanVersiAplikasi(string $versi): void
    {
        $versiAplikasi = trim((string) @file_get_contents(base_path('VERSION')));
        if ($versiAplikasi !== $versi) {
            throw new RuntimeException("VERSION berisi {$versiAplikasi}, tetapi paket diminta untuk {$versi}.");
        }
    }

    private function pastikanDirektori(string $direktori): void
    {
        if (! is_dir($direktori) && ! mkdir($direktori, 0700, true) && ! is_dir($direktori)) {
            throw new RuntimeException('Direktori paket rilis final tidak dapat dibuat.');
        }
        if (! is_writable($direktori)) {
            throw new RuntimeException('Direktori paket rilis final tidak dapat ditulis.');
        }
    }

    private function cariProgram(string $nama): string
    {
        $program = (new ExecutableFinder)->find($nama);
        if (! $program) {
            throw new RuntimeException("Program {$nama} tidak ditemukan pada PATH.");
        }

        return $program;
    }

    private function commitAktif(string $git): string
    {
        $dariLingkungan = trim((string) env('RELEASE_COMMIT', env('GITHUB_SHA', '')));
        if ($dariLingkungan !== '') {
            return $dariLingkungan;
        }

        $proses = new Process([$git, 'rev-parse', 'HEAD'], base_path());
        $proses->setTimeout(10);
        $proses->run();

        if (! $proses->isSuccessful()) {
            throw new RuntimeException('Commit Git aktif tidak dapat ditentukan.');
        }

        return trim($proses->getOutput());
    }

    private function jumlahObjekDatabase(string $database, string $jenis): int
    {
        return (int) DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_TYPE', $jenis)
            ->count();
    }

    private function jumlahTabelDilarang(string $database): int
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

    private function jalankan(array $perintah, string $direktori, int $timeout): void
    {
        $proses = new Process($perintah, $direktori);
        $proses->setTimeout($timeout);
        $proses->run();

        if (! $proses->isSuccessful()) {
            $pesan = trim($proses->getErrorOutput() ?: $proses->getOutput());
            throw new RuntimeException('Proses paket rilis gagal'.($pesan !== '' ? ': '.$pesan : '.'));
        }
    }
}
