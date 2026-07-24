<?php

namespace App\Services;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Throwable;

class PemeriksaPascadeploy
{
    public function periksa(): array
    {
        $hasil = [];
        $this->tambahkan($hasil, 'versi', trim((string) @file_get_contents(base_path('VERSION'))) === 'v1.0.0');
        $this->tambahkan($hasil, 'maintenance_mode', ! app()->isDownForMaintenance());
        $this->tambahkan($hasil, 'route_masuk', Route::has('masuk'));
        $this->tambahkan($hasil, 'route_dashboard', Route::has('dashboard'));
        $this->tambahkan($hasil, 'route_panduan', Route::has('panduan.index'));
        $this->tambahkan($hasil, 'route_kesiapan', Route::has('kesiapan-produksi'));
        $this->tambahkan($hasil, 'database', $this->databaseSehat());
        $this->tambahkan($hasil, 'skema', $this->skemaPaten());
        $this->tambahkan($hasil, 'penyimpanan', $this->penyimpananDapatDitulis());
        $this->tambahkan($hasil, 'http_up', $this->statusHttp('/up') === 200);
        $this->tambahkan($hasil, 'http_kesiapan', $this->statusHttp('/kesiapan') === 200);

        return [
            'berhasil' => collect($hasil)->every(fn (array $item): bool => $item['status'] === 'BERHASIL'),
            'waktu' => now()->toIso8601String(),
            'pemeriksaan' => $hasil,
        ];
    }

    private function databaseSehat(): bool
    {
        try {
            return (int) (DB::selectOne('SELECT 1 AS sehat')->sehat ?? 0) === 1;
        } catch (Throwable) {
            return false;
        }
    }

    private function skemaPaten(): bool
    {
        try {
            $database = (string) config('database.connections.mysql.database');
            $tabel = (int) DB::table('information_schema.TABLES')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_TYPE', 'BASE TABLE')
                ->count();
            $view = (int) DB::table('information_schema.TABLES')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_TYPE', 'VIEW')
                ->count();
            $permission = (int) DB::table('hak_akses')->whereNull('deleted_at')->count();

            return $tabel === 71 && $view === 3 && $permission === 98;
        } catch (Throwable) {
            return false;
        }
    }

    private function penyimpananDapatDitulis(): bool
    {
        $direktori = storage_path('framework/cache');
        if (! is_dir($direktori) || ! is_writable($direktori)) {
            return false;
        }

        $berkas = $direktori.DIRECTORY_SEPARATOR.'smoke-'.bin2hex(random_bytes(6));

        try {
            return file_put_contents($berkas, 'ok', LOCK_EX) !== false && is_readable($berkas);
        } finally {
            @unlink($berkas);
        }
    }

    private function statusHttp(string $path): int
    {
        try {
            $kernel = app(HttpKernel::class);
            $permintaan = Request::create($path, 'GET');
            $respons = $kernel->handle($permintaan);
            $status = $respons->getStatusCode();
            $kernel->terminate($permintaan, $respons);

            return $status;
        } catch (Throwable) {
            return 0;
        }
    }

    private function tambahkan(array &$hasil, string $kode, bool $berhasil): void
    {
        $hasil[] = [
            'kode' => $kode,
            'status' => $berhasil ? 'BERHASIL' : 'GAGAL',
        ];
    }
}
