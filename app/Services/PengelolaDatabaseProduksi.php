<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Throwable;

class PengelolaDatabaseProduksi
{
    public function backup(string $direktori, int $retensiHari = 14, bool $simulasi = false): array
    {
        $konfigurasi = $this->konfigurasi();
        $mysqldump = $this->cariProgram('mysqldump');
        $direktori = $this->normalisasiDirektori($direktori);
        $namaDasar = sprintf(
            'backup-%s-%s.sql.gz',
            preg_replace('/[^a-zA-Z0-9_-]+/', '-', $konfigurasi['database']),
            now()->format('Ymd-His')
        );
        $tujuan = $direktori.DIRECTORY_SEPARATOR.$namaDasar;

        if ($simulasi) {
            return [
                'simulasi' => true,
                'program' => $mysqldump,
                'database' => $konfigurasi['database'],
                'berkas' => $tujuan,
                'retensi_hari' => $retensiHari,
            ];
        }

        $this->pastikanDirektori($direktori);
        $kredensial = $this->buatBerkasKredensial($konfigurasi);
        $galat = tempnam(sys_get_temp_dir(), 'pos-backup-galat-');

        if ($galat === false) {
            @unlink($kredensial);
            throw new RuntimeException('Tidak dapat membuat berkas sementara untuk proses backup.');
        }

        try {
            $perintah = [
                $mysqldump,
                '--defaults-extra-file='.$kredensial,
                '--single-transaction',
                '--quick',
                '--routines',
                '--triggers',
                '--events',
                '--hex-blob',
                '--set-gtid-purged=OFF',
                '--no-tablespaces',
                '--default-character-set=utf8mb4',
                $konfigurasi['database'],
            ];

            $proses = proc_open($perintah, [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['file', $galat, 'w'],
            ], $pipa, null, null, ['bypass_shell' => true]);

            if (! is_resource($proses)) {
                throw new RuntimeException('Proses mysqldump tidak dapat dijalankan.');
            }

            fclose($pipa[0]);
            $keluaran = gzopen($tujuan, 'wb9');

            if ($keluaran === false) {
                fclose($pipa[1]);
                proc_terminate($proses);
                proc_close($proses);
                throw new RuntimeException('Berkas backup terkompresi tidak dapat dibuat.');
            }

            while (! feof($pipa[1])) {
                $potongan = fread($pipa[1], 1024 * 1024);
                if ($potongan === false) {
                    gzclose($keluaran);
                    fclose($pipa[1]);
                    proc_terminate($proses);
                    proc_close($proses);
                    throw new RuntimeException('Keluaran mysqldump tidak dapat dibaca.');
                }

                if ($potongan !== '') {
                    gzwrite($keluaran, $potongan);
                }
            }

            gzclose($keluaran);
            fclose($pipa[1]);
            $kodeKeluar = proc_close($proses);

            if ($kodeKeluar !== 0) {
                $pesan = trim((string) @file_get_contents($galat));
                @unlink($tujuan);
                throw new RuntimeException('Backup database gagal'.($pesan !== '' ? ': '.$pesan : '.'));
            }

            @chmod($tujuan, 0600);
            $checksum = hash_file('sha256', $tujuan);

            if ($checksum === false) {
                @unlink($tujuan);
                throw new RuntimeException('Checksum backup tidak dapat dibuat.');
            }

            $berkasChecksum = $tujuan.'.sha256';
            file_put_contents($berkasChecksum, $checksum.'  '.basename($tujuan).PHP_EOL, LOCK_EX);
            @chmod($berkasChecksum, 0600);
            $dihapus = $this->bersihkanRetensi($direktori, $retensiHari, [$tujuan, $berkasChecksum]);

            return [
                'simulasi' => false,
                'database' => $konfigurasi['database'],
                'berkas' => $tujuan,
                'checksum' => $checksum,
                'ukuran_byte' => filesize($tujuan) ?: 0,
                'retensi_hari' => $retensiHari,
                'berkas_lama_dihapus' => $dihapus,
            ];
        } finally {
            @unlink($kredensial);
            @unlink($galat);
        }
    }

    public function restore(string $berkas, bool $simulasi = false): array
    {
        $konfigurasi = $this->konfigurasi();
        $mysql = $this->cariProgram('mysql');
        $berkas = $this->normalisasiBerkas($berkas);
        $terkompresi = str_ends_with(strtolower($berkas), '.gz');
        $this->verifikasiChecksum($berkas);

        if ($simulasi) {
            return [
                'simulasi' => true,
                'program' => $mysql,
                'database' => $konfigurasi['database'],
                'berkas' => $berkas,
                'terkompresi' => $terkompresi,
            ];
        }

        $kredensial = $this->buatBerkasKredensial($konfigurasi);
        $galat = tempnam(sys_get_temp_dir(), 'pos-restore-galat-');
        $keluaran = tempnam(sys_get_temp_dir(), 'pos-restore-keluaran-');

        if ($galat === false || $keluaran === false) {
            @unlink($kredensial);
            @unlink($galat ?: '');
            @unlink($keluaran ?: '');
            throw new RuntimeException('Tidak dapat membuat berkas sementara untuk proses restore.');
        }

        try {
            $perintah = [
                $mysql,
                '--defaults-extra-file='.$kredensial,
                '--default-character-set=utf8mb4',
                $konfigurasi['database'],
            ];
            $proses = proc_open($perintah, [
                0 => ['pipe', 'r'],
                1 => ['file', $keluaran, 'w'],
                2 => ['file', $galat, 'w'],
            ], $pipa, null, null, ['bypass_shell' => true]);

            if (! is_resource($proses)) {
                throw new RuntimeException('Proses mysql tidak dapat dijalankan.');
            }

            $sumber = $terkompresi ? gzopen($berkas, 'rb') : fopen($berkas, 'rb');
            if ($sumber === false) {
                fclose($pipa[0]);
                proc_terminate($proses);
                proc_close($proses);
                throw new RuntimeException('Berkas restore tidak dapat dibuka.');
            }

            try {
                while (! feof($sumber)) {
                    $potongan = $terkompresi
                        ? gzread($sumber, 1024 * 1024)
                        : fread($sumber, 1024 * 1024);

                    if ($potongan === false) {
                        throw new RuntimeException('Berkas restore tidak dapat dibaca.');
                    }

                    if ($potongan !== '' && fwrite($pipa[0], $potongan) === false) {
                        throw new RuntimeException('Data restore tidak dapat dikirim ke mysql.');
                    }
                }
            } finally {
                $terkompresi ? gzclose($sumber) : fclose($sumber);
                fclose($pipa[0]);
            }

            $kodeKeluar = proc_close($proses);
            if ($kodeKeluar !== 0) {
                $pesan = trim((string) @file_get_contents($galat));
                throw new RuntimeException('Restore database gagal'.($pesan !== '' ? ': '.$pesan : '.'));
            }

            return [
                'simulasi' => false,
                'database' => $konfigurasi['database'],
                'berkas' => $berkas,
                'terkompresi' => $terkompresi,
            ];
        } finally {
            @unlink($kredensial);
            @unlink($galat);
            @unlink($keluaran);
        }
    }

    private function konfigurasi(): array
    {
        if (config('database.default') !== 'mysql') {
            throw new RuntimeException('Backup dan restore Fase 10 hanya mendukung koneksi mysql.');
        }

        $konfigurasi = config('database.connections.mysql');
        $database = trim((string) ($konfigurasi['database'] ?? ''));
        $pengguna = trim((string) ($konfigurasi['username'] ?? ''));

        if ($database === '' || $pengguna === '') {
            throw new RuntimeException('Nama database dan pengguna MySQL wajib tersedia.');
        }

        return [
            'host' => (string) ($konfigurasi['host'] ?? '127.0.0.1'),
            'port' => (string) ($konfigurasi['port'] ?? '3306'),
            'socket' => (string) ($konfigurasi['unix_socket'] ?? ''),
            'database' => $database,
            'username' => $pengguna,
            'password' => (string) ($konfigurasi['password'] ?? ''),
        ];
    }

    private function cariProgram(string $nama): string
    {
        $program = (new ExecutableFinder)->find($nama);
        if (! $program) {
            throw new RuntimeException("Program {$nama} tidak ditemukan pada PATH server.");
        }

        return $program;
    }

    private function buatBerkasKredensial(array $konfigurasi): string
    {
        $berkas = tempnam(sys_get_temp_dir(), 'pos-mysql-');
        if ($berkas === false) {
            throw new RuntimeException('Berkas kredensial MySQL sementara tidak dapat dibuat.');
        }

        $baris = [
            '[client]',
            'user="'.$this->escapeNilai($konfigurasi['username']).'"',
            'password="'.$this->escapeNilai($konfigurasi['password']).'"',
            'default-character-set=utf8mb4',
        ];

        if ($konfigurasi['socket'] !== '') {
            $baris[] = 'socket="'.$this->escapeNilai($konfigurasi['socket']).'"';
        } else {
            $baris[] = 'protocol=tcp';
            $baris[] = 'host="'.$this->escapeNilai($konfigurasi['host']).'"';
            $baris[] = 'port='.$konfigurasi['port'];
        }

        file_put_contents($berkas, implode(PHP_EOL, $baris).PHP_EOL, LOCK_EX);
        @chmod($berkas, 0600);

        return $berkas;
    }

    private function escapeNilai(string $nilai): string
    {
        return str_replace(
            ['\\', '"', "\r", "\n"],
            ['\\\\', '\\"', '', ''],
            $nilai
        );
    }

    private function normalisasiDirektori(string $direktori): string
    {
        $direktori = trim($direktori);
        if ($direktori === '') {
            throw new RuntimeException('Direktori backup tidak boleh kosong.');
        }

        return rtrim($direktori, DIRECTORY_SEPARATOR);
    }

    private function pastikanDirektori(string $direktori): void
    {
        if (! is_dir($direktori) && ! mkdir($direktori, 0700, true) && ! is_dir($direktori)) {
            throw new RuntimeException("Direktori backup {$direktori} tidak dapat dibuat.");
        }

        if (! is_writable($direktori)) {
            throw new RuntimeException("Direktori backup {$direktori} tidak dapat ditulis.");
        }
    }

    private function normalisasiBerkas(string $berkas): string
    {
        $lokasi = realpath($berkas);
        if ($lokasi === false || ! is_file($lokasi) || ! is_readable($lokasi)) {
            throw new RuntimeException('Berkas restore tidak ditemukan atau tidak dapat dibaca.');
        }

        $nama = strtolower($lokasi);
        if (! str_ends_with($nama, '.sql') && ! str_ends_with($nama, '.sql.gz')) {
            throw new RuntimeException('Berkas restore harus berekstensi .sql atau .sql.gz.');
        }

        return $lokasi;
    }

    private function verifikasiChecksum(string $berkas): void
    {
        $berkasChecksum = $berkas.'.sha256';
        if (! is_file($berkasChecksum)) {
            return;
        }

        $isi = trim((string) file_get_contents($berkasChecksum));
        $checksumTersimpan = strtolower((string) preg_split('/\s+/', $isi)[0]);
        $checksumAktual = hash_file('sha256', $berkas);

        if ($checksumAktual === false || ! hash_equals($checksumTersimpan, strtolower($checksumAktual))) {
            throw new RuntimeException('Checksum SHA-256 berkas restore tidak cocok.');
        }
    }

    private function bersihkanRetensi(string $direktori, int $retensiHari, array $pengecualian): int
    {
        if ($retensiHari < 1) {
            return 0;
        }

        $batas = now()->subDays($retensiHari)->getTimestamp();
        $dihapus = 0;

        foreach (glob($direktori.DIRECTORY_SEPARATOR.'backup-*.sql*') ?: [] as $berkas) {
            if (in_array($berkas, $pengecualian, true)) {
                continue;
            }

            try {
                if (is_file($berkas) && filemtime($berkas) < $batas && @unlink($berkas)) {
                    $dihapus++;
                }
            } catch (Throwable) {
                // Kegagalan membersihkan berkas lama tidak membatalkan backup baru.
            }
        }

        return $dihapus;
    }
}
