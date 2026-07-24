<?php

namespace App\Console\Commands;

use App\Services\PengelolaDatabaseProduksi;
use Illuminate\Console\Command;
use Throwable;

class BackupDatabaseProduksi extends Command
{
    protected $signature = 'sistem:backup-database
        {--direktori= : Direktori tujuan backup}
        {--retensi-hari=14 : Hapus backup yang lebih lama dari jumlah hari ini}
        {--simulasi : Periksa peralatan dan rencana tanpa membuat backup}
        {--json : Tampilkan hasil dalam format JSON}';

    protected $description = 'Membuat backup MySQL terkompresi, checksum SHA-256, dan membersihkan retensi';

    public function handle(PengelolaDatabaseProduksi $pengelola): int
    {
        $direktori = (string) ($this->option('direktori') ?: storage_path('app/private/backups/database'));
        $retensiHari = filter_var($this->option('retensi-hari'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 3650],
        ]);

        if ($retensiHari === false) {
            $this->error('Retensi hari harus berupa angka antara 1 dan 3650.');

            return self::INVALID;
        }

        try {
            $hasil = $pengelola->backup(
                $direktori,
                (int) $retensiHari,
                (bool) $this->option('simulasi')
            );
        } catch (Throwable $e) {
            if ($this->option('json')) {
                $this->line((string) json_encode([
                    'berhasil' => false,
                    'pesan' => $e->getMessage(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error($e->getMessage());
            }

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'berhasil' => true,
                ...$hasil,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        if ($hasil['simulasi']) {
            $this->info('Simulasi backup berhasil.');
            $this->table(['Komponen', 'Nilai'], [
                ['Program', $hasil['program']],
                ['Database', $hasil['database']],
                ['Rencana berkas', $hasil['berkas']],
                ['Retensi', $hasil['retensi_hari'].' hari'],
            ]);

            return self::SUCCESS;
        }

        $this->info('Backup database berhasil dibuat.');
        $this->table(['Komponen', 'Nilai'], [
            ['Database', $hasil['database']],
            ['Berkas', $hasil['berkas']],
            ['Ukuran', number_format((int) $hasil['ukuran_byte'], 0, ',', '.').' byte'],
            ['Checksum SHA-256', $hasil['checksum']],
            ['Retensi', $hasil['retensi_hari'].' hari'],
            ['Berkas lama dihapus', $hasil['berkas_lama_dihapus']],
        ]);

        return self::SUCCESS;
    }
}
