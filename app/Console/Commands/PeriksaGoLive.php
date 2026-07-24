<?php

namespace App\Console\Commands;

use App\Services\PemeriksaGoLive;
use Illuminate\Console\Command;
use Throwable;

class PeriksaGoLive extends Command
{
    protected $signature = 'sistem:periksa-go-live
        {--backup-direktori= : Direktori backup database}
        {--maks-usia-backup=24 : Maksimal usia backup dalam jam}
        {--paket= : Paket rilis final yang harus diverifikasi}
        {--ketat : Perlakukan peringatan sebagai kegagalan}
        {--json : Tampilkan hasil dalam format JSON}';

    protected $description = 'Memeriksa gate go-live, backup terbaru, paket final, dan kesiapan produksi';

    public function handle(PemeriksaGoLive $pemeriksa): int
    {
        $maksUsia = filter_var($this->option('maks-usia-backup'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 720],
        ]);

        if ($maksUsia === false) {
            return $this->gagal('Maksimal usia backup harus berupa angka antara 1 dan 720 jam.');
        }

        try {
            $hasil = $pemeriksa->periksa(
                $this->option('backup-direktori') ? (string) $this->option('backup-direktori') : null,
                (int) $maksUsia,
                $this->option('paket') ? (string) $this->option('paket') : null
            );
        } catch (Throwable $exception) {
            return $this->gagal($exception->getMessage());
        }

        $berhasil = $hasil['siap'] && (! $this->option('ketat') || ! $hasil['memiliki_peringatan']);

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'berhasil' => $berhasil,
                ...$hasil,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return $berhasil ? self::SUCCESS : self::FAILURE;
        }

        $this->table(['Pemeriksaan', 'Status', 'Keterangan'], collect($hasil['pemeriksaan'])
            ->map(fn (array $item): array => [$item['kode'], $item['status'], $item['pesan']])
            ->all());

        if ($hasil['backup_terbaru']) {
            $this->table(['Backup', 'Nilai'], [
                ['Nama', $hasil['backup_terbaru']['nama']],
                ['Usia', $hasil['backup_terbaru']['usia_jam'].' jam'],
                ['Ukuran', number_format((int) $hasil['backup_terbaru']['ukuran_byte'], 0, ',', '.').' byte'],
            ]);
        }

        if (! $berhasil) {
            $this->error('Gate go-live belum terpenuhi.');

            return self::FAILURE;
        }

        $this->info('Gate go-live berhasil dipenuhi.');

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
