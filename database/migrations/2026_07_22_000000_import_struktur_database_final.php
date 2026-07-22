<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

return new class extends Migration
{
    private string $berkasSql = 'struktur_database_toko_bangunan.sql';

    public function up(): void
    {
        $this->pastikanMySql();

        $sql = $this->bacaSqlFinal();
        $namaDatabase = (string) config('database.connections.mysql.database');

        if (! preg_match('/^[A-Za-z0-9_]+$/', $namaDatabase)) {
            throw new RuntimeException('Nama database tidak aman untuk diproses: '.$namaDatabase);
        }

        DB::unprepared(sprintf(
            'ALTER DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $namaDatabase
        ));

        $daftarPernyataan = $this->pisahkanPernyataanSql($this->hapusPerintahLingkungan($sql));

        foreach ($daftarPernyataan as $indeks => $pernyataan) {
            if (trim($pernyataan) === '') {
                continue;
            }

            try {
                DB::unprepared($pernyataan);
            } catch (Throwable $exception) {
                $ringkas = preg_replace('/\s+/', ' ', trim($pernyataan)) ?? trim($pernyataan);
                $ringkas = mb_substr($ringkas, 0, 500);

                throw new RuntimeException(
                    sprintf(
                        'Gagal menjalankan statement SQL final nomor %d dari %d: %s. Penyebab: %s',
                        $indeks + 1,
                        count($daftarPernyataan),
                        $ringkas,
                        $exception->getMessage()
                    ),
                    previous: $exception
                );
            }
        }
    }

    public function down(): void
    {
        $this->pastikanMySql();

        $sql = $this->bacaSqlFinal();

        preg_match_all(
            '/CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+`?([A-Za-z0-9_]+)`?/i',
            $sql,
            $hasilView
        );

        preg_match_all(
            '/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?([A-Za-z0-9_]+)`?/i',
            $sql,
            $hasilTabel
        );

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach (array_reverse(array_unique($hasilView[1] ?? [])) as $namaView) {
                DB::statement(sprintf('DROP VIEW IF EXISTS `%s`', $namaView));
            }

            foreach (array_reverse(array_unique($hasilTabel[1] ?? [])) as $namaTabel) {
                DB::statement(sprintf('DROP TABLE IF EXISTS `%s`', $namaTabel));
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    private function pastikanMySql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'Baseline database final hanya boleh dijalankan pada koneksi MySQL/MariaDB.'
            );
        }
    }

    private function bacaSqlFinal(): string
    {
        $lokasi = base_path($this->berkasSql);

        if (! is_file($lokasi)) {
            throw new RuntimeException('Berkas SQL final tidak ditemukan: '.$lokasi);
        }

        $isi = file_get_contents($lokasi);

        if ($isi === false || trim($isi) === '') {
            throw new RuntimeException('Berkas SQL final kosong atau gagal dibaca: '.$lokasi);
        }

        return preg_replace('/^\xEF\xBB\xBF/', '', $isi) ?? $isi;
    }

    private function hapusPerintahLingkungan(string $sql): string
    {
        $pola = [
            '/\bSET\s+NAMES\s+[^;]+;/i',
            '/\bSET\s+time_zone\s*=\s*[^;]+;/i',
            '/\bCREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+[^;]+;/is',
            '/\bUSE\s+`?[A-Za-z0-9_]+`?\s*;/i',
        ];

        return preg_replace($pola, '', $sql) ?? $sql;
    }

    /**
     * Memisahkan statement tanpa memecah titik koma yang berada di dalam string.
     * SQL final tidak memakai stored procedure atau perubahan DELIMITER.
     *
     * @return array<int, string>
     */
    private function pisahkanPernyataanSql(string $sql): array
    {
        $pernyataan = [];
        $buffer = '';
        $kutipTunggal = false;
        $kutipGanda = false;
        $kutipIdentifier = false;
        $komentarBaris = false;
        $komentarBlok = false;
        $panjang = strlen($sql);

        for ($i = 0; $i < $panjang; $i++) {
            $karakter = $sql[$i];
            $berikutnya = $i + 1 < $panjang ? $sql[$i + 1] : '';
            $sebelumnya = $i > 0 ? $sql[$i - 1] : '';

            if ($komentarBaris) {
                if ($karakter === "\n") {
                    $komentarBaris = false;
                    $buffer .= "\n";
                }

                continue;
            }

            if ($komentarBlok) {
                if ($karakter === '*' && $berikutnya === '/') {
                    $komentarBlok = false;
                    $i++;
                }

                continue;
            }

            if (! $kutipTunggal && ! $kutipGanda && ! $kutipIdentifier) {
                if ($karakter === '-' && $berikutnya === '-') {
                    $komentarBaris = true;
                    $i++;

                    continue;
                }

                if ($karakter === '#') {
                    $komentarBaris = true;

                    continue;
                }

                if ($karakter === '/' && $berikutnya === '*') {
                    $komentarBlok = true;
                    $i++;

                    continue;
                }
            }

            $terlepas = $sebelumnya === '\\' && ($i < 2 || $sql[$i - 2] !== '\\');

            if ($karakter === "'" && ! $kutipGanda && ! $kutipIdentifier && ! $terlepas) {
                $kutipTunggal = ! $kutipTunggal;
            } elseif ($karakter === '"' && ! $kutipTunggal && ! $kutipIdentifier && ! $terlepas) {
                $kutipGanda = ! $kutipGanda;
            } elseif ($karakter === '`' && ! $kutipTunggal && ! $kutipGanda && ! $terlepas) {
                $kutipIdentifier = ! $kutipIdentifier;
            }

            if (
                $karakter === ';'
                && ! $kutipTunggal
                && ! $kutipGanda
                && ! $kutipIdentifier
            ) {
                $statement = trim($buffer);

                if ($statement !== '') {
                    $pernyataan[] = $statement;
                }

                $buffer = '';

                continue;
            }

            $buffer .= $karakter;
        }

        if (trim($buffer) !== '') {
            $pernyataan[] = trim($buffer);
        }

        return $pernyataan;
    }
};