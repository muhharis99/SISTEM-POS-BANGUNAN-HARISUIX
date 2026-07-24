<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class PemeriksaGoLive
{
    public function __construct(
        private readonly PemeriksaKesiapanProduksi $pemeriksaProduksi,
        private readonly PembuatPaketRilisFinal $pembuatPaket,
        private readonly KontrakRilisFinal $kontrakRilis
    ) {}

    public function periksa(?string $direktoriBackup = null, int $maksUsiaBackupJam = 24, ?string $paketRilis = null): array
    {
        if ($maksUsiaBackupJam < 1 || $maksUsiaBackupJam > 720) {
            throw new RuntimeException('Maksimal usia backup harus antara 1 dan 720 jam.');
        }

        $hasil = [];
        $produksi = $this->pemeriksaProduksi->periksa(true);
        $kontrak = $this->kontrakRilis->periksa();

        $this->tambahkan(
            $hasil,
            'kesiapan_produksi',
            $produksi['siap'],
            'Pemeriksaan kesiapan produksi memiliki kegagalan.'
        );
        $this->tambahkan(
            $hasil,
            'kontrak_rilis_final',
            $kontrak['valid'],
            'Kontrak rilis final 71 tabel, 3 view, 98 permission, dan nol tabel terlarang tidak terpenuhi.'
        );
        $this->tambahkan(
            $hasil,
            'maintenance_mode',
            ! app()->isDownForMaintenance(),
            'Aplikasi masih berada pada maintenance mode.'
        );

        $ruangBebas = @disk_free_space(base_path());
        $this->tambahkanPeringatan(
            $hasil,
            'ruang_disk',
            is_numeric($ruangBebas) && $ruangBebas >= 1024 * 1024 * 1024,
            'Ruang disk bebas kurang dari 1 GiB atau tidak dapat diperiksa.'
        );

        $direktoriBackup = rtrim(
            $direktoriBackup ?: storage_path('app/private/backups/database'),
            DIRECTORY_SEPARATOR
        );
        $backup = $this->backupTerbaru($direktoriBackup);
        $this->tambahkan(
            $hasil,
            'backup_tersedia',
            $backup !== null,
            'Backup database terbaru tidak ditemukan.'
        );

        if ($backup !== null) {
            $usiaJam = max(0, (time() - filemtime($backup)) / 3600);
            $this->tambahkan(
                $hasil,
                'usia_backup',
                $usiaJam <= $maksUsiaBackupJam,
                'Backup database lebih lama dari batas '.$maksUsiaBackupJam.' jam.'
            );
            $this->tambahkan(
                $hasil,
                'checksum_backup',
                $this->checksumBackupValid($backup),
                'Checksum backup database tidak tersedia atau tidak cocok.'
            );
            $this->tambahkan(
                $hasil,
                'gzip_backup',
                $this->gzipDapatDibaca($backup),
                'Backup database terkompresi tidak dapat dibaca.'
            );
        }

        if ($paketRilis === null || trim($paketRilis) === '') {
            $this->tambahkanPeringatan(
                $hasil,
                'paket_rilis',
                false,
                'Paket rilis final belum diberikan untuk verifikasi go-live.'
            );
        } else {
            try {
                $verifikasi = $this->pembuatPaket->verifikasi($paketRilis);
                $this->tambahkan(
                    $hasil,
                    'paket_rilis',
                    $verifikasi['valid'] && $verifikasi['versi'] === KontrakRilisFinal::VERSI,
                    'Paket rilis final tidak valid atau versinya tidak sesuai.'
                );
            } catch (Throwable) {
                $this->tambahkan($hasil, 'paket_rilis', false, 'Paket rilis final tidak dapat diverifikasi.');
            }
        }

        $peringatanProduksi = collect($produksi['pemeriksaan'])
            ->contains(fn (array $item): bool => $item['status'] === 'PERINGATAN');
        $peringatanGoLive = collect($hasil)
            ->contains(fn (array $item): bool => $item['status'] === 'PERINGATAN');

        return [
            'siap' => collect($hasil)->every(fn (array $item): bool => $item['status'] !== 'GAGAL'),
            'memiliki_peringatan' => $peringatanProduksi || $peringatanGoLive,
            'waktu' => now()->toIso8601String(),
            'kontrak' => $kontrak,
            'backup_terbaru' => $backup ? [
                'nama' => basename($backup),
                'usia_jam' => round(max(0, (time() - filemtime($backup)) / 3600), 2),
                'ukuran_byte' => filesize($backup) ?: 0,
            ] : null,
            'pemeriksaan_produksi' => $produksi['pemeriksaan'],
            'pemeriksaan' => $hasil,
        ];
    }

    private function backupTerbaru(string $direktori): ?string
    {
        if (! is_dir($direktori) || ! is_readable($direktori)) {
            return null;
        }

        $daftar = glob($direktori.DIRECTORY_SEPARATOR.'backup-*.sql.gz') ?: [];
        $daftar = array_values(array_filter($daftar, 'is_file'));
        usort($daftar, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        return $daftar[0] ?? null;
    }

    private function checksumBackupValid(string $backup): bool
    {
        $sidecar = $backup.'.sha256';
        if (! is_file($sidecar) || ! is_readable($sidecar)) {
            return false;
        }

        $isi = trim((string) file_get_contents($sidecar));
        if (! preg_match('/^([a-f0-9]{64})\s{2}(.+)$/i', $isi, $cocok)) {
            return false;
        }

        $aktual = hash_file('sha256', $backup);

        return $aktual !== false && hash_equals(strtolower($cocok[1]), strtolower($aktual));
    }

    private function gzipDapatDibaca(string $backup): bool
    {
        $berkas = @gzopen($backup, 'rb');
        if ($berkas === false) {
            return false;
        }

        try {
            return gzread($berkas, 1024) !== false;
        } finally {
            gzclose($berkas);
        }
    }

    private function tambahkan(array &$hasil, string $kode, bool $berhasil, string $pesanGagal): void
    {
        $hasil[] = [
            'kode' => $kode,
            'status' => $berhasil ? 'BERHASIL' : 'GAGAL',
            'pesan' => $berhasil ? 'Pemeriksaan berhasil.' : $pesanGagal,
        ];
    }

    private function tambahkanPeringatan(array &$hasil, string $kode, bool $berhasil, string $pesan): void
    {
        $hasil[] = [
            'kode' => $kode,
            'status' => $berhasil ? 'BERHASIL' : 'PERINGATAN',
            'pesan' => $berhasil ? 'Pemeriksaan berhasil.' : $pesan,
        ];
    }
}
