<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class PemeriksaKesiapanProduksi
{
    private const JUMLAH_TABEL_PATEN = 71;

    private const JUMLAH_VIEW_PATEN = 3;

    private const TABEL_INFRASTRUKTUR_DILARANG = [
        'sessions',
        'cache',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ];

    public function periksa(bool $sertakanKonfigurasi = true): array
    {
        $hasil = [];

        if ($sertakanKonfigurasi) {
            $this->periksaKonfigurasi($hasil);
        }

        $this->periksaDatabase($hasil);
        $this->periksaPenyimpanan($hasil);

        return [
            'siap' => collect($hasil)->every(fn (array $item): bool => $item['status'] !== 'GAGAL'),
            'waktu' => now()->toIso8601String(),
            'pemeriksaan' => $hasil,
        ];
    }

    public function ringkasUntukEndpoint(): array
    {
        $hasil = $this->periksa(false);

        return [
            'status' => $hasil['siap'] ? 'siap' : 'tidak_siap',
            'waktu' => $hasil['waktu'],
            'komponen' => collect($hasil['pemeriksaan'])
                ->mapWithKeys(fn (array $item): array => [$item['kode'] => strtolower($item['status'])])
                ->all(),
        ];
    }

    private function periksaKonfigurasi(array &$hasil): void
    {
        $this->tambahkan(
            $hasil,
            'lingkungan',
            config('app.env') === 'production',
            'APP_ENV harus bernilai production.'
        );
        $this->tambahkan(
            $hasil,
            'debug',
            config('app.debug') === false,
            'APP_DEBUG harus false pada produksi.'
        );
        $this->tambahkan(
            $hasil,
            'kunci_aplikasi',
            filled(config('app.key')),
            'APP_KEY belum tersedia.'
        );

        $url = (string) config('app.url');
        $this->tambahkan(
            $hasil,
            'url_https',
            strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https',
            'APP_URL produksi harus menggunakan HTTPS.'
        );
        $this->tambahkan(
            $hasil,
            'session_file',
            config('session.driver') === 'file',
            'SESSION_DRIVER wajib file agar tidak menambah tabel di luar SQL paten.'
        );
        $this->tambahkan(
            $hasil,
            'cache_file',
            config('cache.default') === 'file',
            'CACHE_STORE wajib file agar tidak menambah tabel di luar SQL paten.'
        );
        $this->tambahkan(
            $hasil,
            'queue_sync',
            config('queue.default') === 'sync',
            'QUEUE_CONNECTION wajib sync selama skema paten tidak menyediakan tabel antrean.'
        );
        $this->tambahkan(
            $hasil,
            'database_mysql',
            config('database.default') === 'mysql',
            'DB_CONNECTION produksi harus mysql.'
        );

        $this->tambahkanPeringatan(
            $hasil,
            'konfigurasi_cache',
            app()->configurationIsCached(),
            'Konfigurasi Laravel belum di-cache.'
        );
        $this->tambahkanPeringatan(
            $hasil,
            'route_cache',
            app()->routesAreCached(),
            'Route Laravel belum di-cache.'
        );
    }

    private function periksaDatabase(array &$hasil): void
    {
        try {
            DB::connection()->selectOne('SELECT 1 AS sehat');
            $this->tambahkan($hasil, 'database', true, 'Koneksi database gagal.');
        } catch (Throwable) {
            $this->tambahkan($hasil, 'database', false, 'Koneksi database gagal.');

            return;
        }

        try {
            $database = (string) config('database.connections.mysql.database');
            $jumlahTabel = (int) DB::table('information_schema.TABLES')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_TYPE', 'BASE TABLE')
                ->count();
            $jumlahView = (int) DB::table('information_schema.TABLES')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_TYPE', 'VIEW')
                ->count();
            $jumlahDilarang = (int) DB::table('information_schema.TABLES')
                ->where('TABLE_SCHEMA', $database)
                ->whereIn('TABLE_NAME', self::TABEL_INFRASTRUKTUR_DILARANG)
                ->count();

            $this->tambahkan(
                $hasil,
                'skema_tabel',
                $jumlahTabel === self::JUMLAH_TABEL_PATEN,
                'Jumlah base table harus '.self::JUMLAH_TABEL_PATEN.", ditemukan {$jumlahTabel}."
            );
            $this->tambahkan(
                $hasil,
                'skema_view',
                $jumlahView === self::JUMLAH_VIEW_PATEN,
                'Jumlah view harus '.self::JUMLAH_VIEW_PATEN.", ditemukan {$jumlahView}."
            );
            $this->tambahkan(
                $hasil,
                'tabel_dilarang',
                $jumlahDilarang === 0,
                "Ditemukan {$jumlahDilarang} tabel infrastruktur yang tidak diizinkan."
            );
        } catch (Throwable) {
            $this->tambahkan($hasil, 'skema_tabel', false, 'Skema database tidak dapat diverifikasi.');
            $this->tambahkan($hasil, 'skema_view', false, 'View database tidak dapat diverifikasi.');
            $this->tambahkan($hasil, 'tabel_dilarang', false, 'Tabel infrastruktur tidak dapat diverifikasi.');
        }
    }

    private function periksaPenyimpanan(array &$hasil): void
    {
        $direktori = [
            'storage_cache' => storage_path('framework/cache/data'),
            'storage_session' => storage_path('framework/sessions'),
            'storage_view' => storage_path('framework/views'),
            'storage_log' => storage_path('logs'),
            'bootstrap_cache' => base_path('bootstrap/cache'),
        ];

        foreach ($direktori as $kode => $lokasi) {
            $this->tambahkan(
                $hasil,
                $kode,
                is_dir($lokasi) && is_writable($lokasi),
                "Direktori {$lokasi} harus tersedia dan dapat ditulis oleh pengguna PHP-FPM."
            );
        }
    }

    private function tambahkan(array &$hasil, string $kode, bool $berhasil, string $pesanGagal): void
    {
        $hasil[] = [
            'kode' => $kode,
            'status' => $berhasil ? 'BERHASIL' : 'GAGAL',
            'pesan' => $berhasil ? 'Pemeriksaan berhasil.' : $pesanGagal,
        ];
    }

    private function tambahkanPeringatan(array &$hasil, string $kode, bool $berhasil, string $pesan): void
    {
        $hasil[] = [
            'kode' => $kode,
            'status' => $berhasil ? 'BERHASIL' : 'PERINGATAN',
            'pesan' => $berhasil ? 'Pemeriksaan berhasil.' : $pesan,
        ];
    }
}
