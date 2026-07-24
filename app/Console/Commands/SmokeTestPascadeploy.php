<?php

namespace App\Console\Commands;

use App\Services\PemeriksaPascadeploy;
use Illuminate\Console\Command;
use Throwable;

class SmokeTestPascadeploy extends Command
{
    protected $signature = 'sistem:smoke-test-pascadeploy
        {--json : Tampilkan hasil dalam format JSON}';

    protected $description = 'Menjalankan smoke test aplikasi, route, database, skema, storage, dan endpoint kesehatan';

    public function handle(PemeriksaPascadeploy $pemeriksa): int
    {
        try {
            $hasil = $pemeriksa->periksa();
        } catch (Throwable $exception) {
            return $this->gagal($exception->getMessage());
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($hasil, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $hasil['berhasil'] ? self::SUCCESS : self::FAILURE;
        }

        $this->table(['Pemeriksaan', 'Status'], collect($hasil['pemeriksaan'])
            ->map(fn (array $item): array => [$item['kode'], $item['status']])
            ->all());

        if (! $hasil['berhasil']) {
            $this->error('Smoke test pascadeploy gagal.');

            return self::FAILURE;
        }

        $this->info('Smoke test pascadeploy berhasil.');

        return self::SUCCESS;
    }

    private function gagal(string $pesan): int
    {
        if ($this->option('json')) {
            $this->line((string) json_encode([
                'berhasil' => false,
                'pesan' => $pesan,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->error($pesan);
        }

        return self::FAILURE;
    }
}
