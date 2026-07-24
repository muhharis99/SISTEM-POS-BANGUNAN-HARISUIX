<?php

namespace App\Console\Commands;

use App\Services\PembuatManifestRilis;
use Illuminate\Console\Command;
use RuntimeException;

class VerifikasiManifestRilis extends Command
{
    protected $signature = 'sistem:verifikasi-manifest-rilis
                            {berkas : Lokasi manifest release candidate}
                            {--json : Tampilkan hasil dalam format JSON}';

    protected $description = 'Memverifikasi skema, permission, dan checksum manifest release candidate';

    public function handle(PembuatManifestRilis $pembuat): int
    {
        try {
            $hasil = $pembuat->verifikasi((string) $this->argument('berkas'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($hasil, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $hasil['valid'] ? self::SUCCESS : self::FAILURE;
        }

        $this->table(
            ['Pemeriksaan', 'Status'],
            collect($hasil['pemeriksaan'])
                ->map(fn (bool $berhasil, string $nama): array => [$nama, $berhasil ? 'BERHASIL' : 'GAGAL'])
                ->values()
                ->all()
        );

        if (! $hasil['valid']) {
            $this->error('Manifest release candidate tidak valid.');

            return self::FAILURE;
        }

        $this->info("Manifest versi {$hasil['versi']} valid untuk commit {$hasil['commit']}.");

        return self::SUCCESS;
    }
}
