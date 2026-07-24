<?php

namespace App\Console\Commands;

use App\Services\KontrakRilisFinal;
use App\Services\PembuatPaketRilisFinal;
use Illuminate\Console\Command;
use Throwable;

class VerifikasiPaketRilisFinal extends Command
{
    protected $signature = 'sistem:verifikasi-paket-rilis-final
        {berkas : Lokasi paket .tar.gz}
        {--json : Tampilkan hasil dalam format JSON}';

    protected $description = 'Memverifikasi checksum, manifest, inventaris, skema, dan isi paket rilis final';

    public function handle(PembuatPaketRilisFinal $pembuat, KontrakRilisFinal $kontrak): int
    {
        try {
            $kontrak->pastikan();
            $hasil = $pembuat->verifikasi((string) $this->argument('berkas'));
        } catch (Throwable $exception) {
            return $this->gagal($exception->getMessage());
        }

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'berhasil' => $hasil['valid'],
                ...$hasil,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $hasil['valid'] ? self::SUCCESS : self::FAILURE;
        }

        $this->table(['Pemeriksaan', 'Hasil'], collect($hasil['pemeriksaan'])
            ->map(fn (bool $berhasil, string $nama): array => [$nama, $berhasil ? 'BERHASIL' : 'GAGAL'])
            ->values()
            ->all());

        if (! $hasil['valid']) {
            $this->error('Paket rilis final tidak valid.');

            return self::FAILURE;
        }

        $this->info('Paket rilis final valid.');
        $this->table(['Komponen', 'Nilai'], [
            ['Versi', $hasil['versi']],
            ['Commit', $hasil['commit']],
            ['Checksum paket', $hasil['checksum_paket']],
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
