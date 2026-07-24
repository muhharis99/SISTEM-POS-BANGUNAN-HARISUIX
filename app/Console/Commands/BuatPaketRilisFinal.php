<?php

namespace App\Console\Commands;

use App\Services\KontrakRilisFinal;
use App\Services\PengelolaPaketRilisFinal;
use Illuminate\Console\Command;
use Throwable;

class BuatPaketRilisFinal extends Command
{
    protected $signature = 'sistem:buat-paket-rilis-final
        {versi=v1.0.0 : Versi final semver stabil}
        {--direktori= : Direktori privat tujuan paket}
        {--json : Tampilkan hasil dalam format JSON}';

    protected $description = 'Membuat paket rilis final, inventaris berkas, manifest, dan checksum SHA-256';

    public function handle(PengelolaPaketRilisFinal $pembuat, KontrakRilisFinal $kontrak): int
    {
        try {
            $kontrak->pastikan();
            $hasil = $pembuat->buat(
                (string) $this->argument('versi'),
                $this->option('direktori') ? (string) $this->option('direktori') : null
            );
        } catch (Throwable $exception) {
            return $this->gagal($exception->getMessage());
        }

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'berhasil' => true,
                ...$hasil,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('Paket rilis final berhasil dibuat.');
        $this->table(['Komponen', 'Nilai'], [
            ['Versi', $hasil['versi']],
            ['Commit', $hasil['commit']],
            ['Paket', $hasil['paket']],
            ['Checksum SHA-256', $hasil['checksum']],
            ['Ukuran', number_format((int) $hasil['ukuran_byte'], 0, ',', '.').' byte'],
            ['Jumlah berkas', number_format((int) $hasil['jumlah_berkas'], 0, ',', '.')],
        ]);

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
