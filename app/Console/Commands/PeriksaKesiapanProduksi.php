<?php

namespace App\Console\Commands;

use App\Services\PemeriksaKesiapanProduksi;
use Illuminate\Console\Command;

class PeriksaKesiapanProduksi extends Command
{
    protected $signature = 'sistem:periksa-produksi
        {--json : Tampilkan hasil dalam format JSON}
        {--ketat : Perlakukan peringatan sebagai kegagalan}';

    protected $description = 'Memeriksa konfigurasi, database, skema paten, dan direktori aplikasi sebelum produksi';

    public function handle(PemeriksaKesiapanProduksi $pemeriksa): int
    {
        $hasil = $pemeriksa->periksa();
        $ketat = (bool) $this->option('ketat');
        $berhasil = collect($hasil['pemeriksaan'])->every(
            fn (array $item): bool => $ketat
                ? $item['status'] === 'BERHASIL'
                : $item['status'] !== 'GAGAL'
        );

        if ($this->option('json')) {
            $this->line((string) json_encode([
                ...$hasil,
                'ketat' => $ketat,
                'berhasil' => $berhasil,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $berhasil ? self::SUCCESS : self::FAILURE;
        }

        $this->newLine();
        $this->info('Pemeriksaan Kesiapan Produksi');
        $this->table(
            ['Pemeriksaan', 'Status', 'Keterangan'],
            collect($hasil['pemeriksaan'])
                ->map(fn (array $item): array => [
                    $item['kode'],
                    $item['status'],
                    $item['pesan'],
                ])
                ->all()
        );

        if ($berhasil) {
            $this->info($ketat
                ? 'Seluruh pemeriksaan dan rekomendasi produksi berhasil.'
                : 'Seluruh pemeriksaan kritis produksi berhasil.');

            return self::SUCCESS;
        }

        $this->error($ketat
            ? 'Masih ada kegagalan atau peringatan yang harus diselesaikan.'
            : 'Masih ada pemeriksaan kritis yang gagal.');

        return self::FAILURE;
    }
}
