<?php

namespace App\Console\Commands;

use App\Services\PembuatManifestRilis;
use Illuminate\Console\Command;
use RuntimeException;

class BuatManifestRilis extends Command
{
    protected $signature = 'sistem:buat-manifest-rilis
                            {versi=v1.0.0-rc1 : Versi semver release candidate}
                            {--nama=manifest-rilis.json : Nama berkas JSON pada storage/app/release-candidate}
                            {--json : Tampilkan hasil dalam format JSON}';

    protected $description = 'Membuat manifest release candidate tanpa kredensial atau data transaksi';

    public function handle(PembuatManifestRilis $pembuat): int
    {
        try {
            $manifest = $pembuat->buat((string) $this->argument('versi'));
            $lokasi = $pembuat->simpan($manifest, (string) $this->option('nama'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $hasil = [
            'berhasil' => true,
            'versi' => $manifest['versi'],
            'commit' => $manifest['aplikasi']['commit'],
            'lokasi' => $lokasi,
            'base_table' => $manifest['database']['base_table'],
            'view' => $manifest['database']['view'],
            'permission_aktif' => $manifest['database']['permission_aktif'],
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($hasil, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('Manifest release candidate berhasil dibuat.');
        $this->table(
            ['Komponen', 'Nilai'],
            [
                ['Versi', $hasil['versi']],
                ['Commit', $hasil['commit']],
                ['Base table', (string) $hasil['base_table']],
                ['View', (string) $hasil['view']],
                ['Permission aktif', (string) $hasil['permission_aktif']],
                ['Lokasi', $hasil['lokasi']],
            ]
        );

        return self::SUCCESS;
    }
}
