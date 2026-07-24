<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class KontrakRilisFinal
{
    public const VERSI = 'v1.0.0';

    public const JUMLAH_TABEL = 71;

    public const JUMLAH_VIEW = 3;

    public const JUMLAH_PERMISSION = 98;

    private const TABEL_INFRASTRUKTUR_DILARANG = [
        'sessions',
        'cache',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ];

    public function periksa(): array
    {
        $database = trim((string) config('database.connections.mysql.database'));
        if ($database === '') {
            throw new RuntimeException('Nama database MySQL tidak tersedia untuk kontrak rilis final.');
        }

        $versi = trim((string) @file_get_contents(base_path('VERSION')));
        $jumlahTabel = $this->jumlahObjekDatabase($database, 'BASE TABLE');
        $jumlahView = $this->jumlahObjekDatabase($database, 'VIEW');
        $jumlahPermission = (int) DB::table('hak_akses')->whereNull('deleted_at')->count();
        $jumlahDilarang = (int) DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $database)
            ->whereIn('TABLE_NAME', self::TABEL_INFRASTRUKTUR_DILARANG)
            ->count();

        $pemeriksaan = [
            'versi' => $versi === self::VERSI,
            'base_table' => $jumlahTabel === self::JUMLAH_TABEL,
            'view' => $jumlahView === self::JUMLAH_VIEW,
            'permission_aktif' => $jumlahPermission === self::JUMLAH_PERMISSION,
            'tabel_infrastruktur_dilarang' => $jumlahDilarang === 0,
        ];

        return [
            'valid' => ! in_array(false, $pemeriksaan, true),
            'aktual' => [
                'versi' => $versi,
                'base_table' => $jumlahTabel,
                'view' => $jumlahView,
                'permission_aktif' => $jumlahPermission,
                'tabel_infrastruktur_dilarang' => $jumlahDilarang,
            ],
            'pemeriksaan' => $pemeriksaan,
        ];
    }

    public function pastikan(): array
    {
        $hasil = $this->periksa();
        if ($hasil['valid']) {
            return $hasil;
        }

        $gagal = collect($hasil['pemeriksaan'])
            ->filter(fn (bool $berhasil): bool => ! $berhasil)
            ->keys()
            ->implode(', ');

        throw new RuntimeException('Kontrak rilis final tidak terpenuhi: '.$gagal.'.');
    }

    private function jumlahObjekDatabase(string $database, string $jenis): int
    {
        return (int) DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_TYPE', $jenis)
            ->count();
    }
}
