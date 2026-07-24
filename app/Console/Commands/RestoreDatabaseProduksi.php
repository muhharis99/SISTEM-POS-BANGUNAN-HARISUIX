<?php

namespace App\Console\Commands;

use App\Services\PengelolaDatabaseProduksi;
use Illuminate\Console\Command;
use Throwable;

class RestoreDatabaseProduksi extends Command
{
    protected $signature = 'sistem:restore-database
        {berkas : Berkas .sql atau .sql.gz yang akan dipulihkan}
        {--konfirmasi= : Wajib bernilai RESTORE}
        {--direktori-backup= : Direktori backup keselamatan sebelum restore}
        {--tanpa-backup-keselamatan : Lewati backup keselamatan sebelum restore}
        {--izinkan-aplikasi-aktif : Izinkan restore saat aplikasi tidak dalam mode maintenance}
        {--simulasi : Validasi berkas dan peralatan tanpa mengubah database}
        {--json : Tampilkan hasil dalam format JSON}';

    protected $description = 'Memulihkan database dari backup dengan konfirmasi eksplisit dan backup keselamatan';

    public function handle(PengelolaDatabaseProduksi $pengelola): int
    {
        $simulasi = (bool) $this->option('simulasi');

        if ((string) $this->option('konfirmasi') !== 'RESTORE') {
            $this->error('Restore dibatalkan. Gunakan --konfirmasi=RESTORE setelah memastikan target database benar.');

            return self::INVALID;
        }

        if (! $simulasi && ! $this->option('izinkan-aplikasi-aktif') && ! app()->isDownForMaintenance()) {
            $this->error('Aplikasi wajib berada dalam mode maintenance. Jalankan php artisan down terlebih dahulu.');

            return self::FAILURE;
        }

        $direktoriBackup = (string) ($this->option('direktori-backup') ?: storage_path('app/private/backups/database'));
        $backupKeselamatan = null;

        try {
            if (! $simulasi && ! $this->option('tanpa-backup-keselamatan')) {
                $this->info('Membuat backup keselamatan sebelum restore...');
                $backupKeselamatan = $pengelola->backup($direktoriBackup, 30, false);
            }

            $hasil = $pengelola->restore((string) $this->argument('berkas'), $simulasi);

            if (! $simulasi) {
                $kodeVerifikasi = $this->call('skema:verifikasi', ['--rinci' => true]);
                if ($kodeVerifikasi !== self::SUCCESS) {
                    throw new \RuntimeException('Restore selesai, tetapi verifikasi skema paten gagal.');
                }
            }
        } catch (Throwable $e) {
            if ($this->option('json')) {
                $this->line((string) json_encode([
                    'berhasil' => false,
                    'pesan' => $e->getMessage(),
                    'backup_keselamatan' => $backupKeselamatan['berkas'] ?? null,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error($e->getMessage());
                if ($backupKeselamatan) {
                    $this->warn('Backup keselamatan tersedia di: '.$backupKeselamatan['berkas']);
                }
            }

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'berhasil' => true,
                ...$hasil,
                'backup_keselamatan' => $backupKeselamatan['berkas'] ?? null,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        if ($simulasi) {
            $this->info('Simulasi restore berhasil. Database tidak diubah.');
            $this->table(['Komponen', 'Nilai'], [
                ['Program', $hasil['program']],
                ['Database target', $hasil['database']],
                ['Berkas', $hasil['berkas']],
                ['Terkompresi', $hasil['terkompresi'] ? 'Ya' : 'Tidak'],
            ]);

            return self::SUCCESS;
        }

        $this->info('Restore database dan verifikasi skema paten berhasil.');
        $this->table(['Komponen', 'Nilai'], [
            ['Database target', $hasil['database']],
            ['Berkas sumber', $hasil['berkas']],
            ['Backup keselamatan', $backupKeselamatan['berkas'] ?? 'Dilewati'],
        ]);

        return self::SUCCESS;
    }
}
