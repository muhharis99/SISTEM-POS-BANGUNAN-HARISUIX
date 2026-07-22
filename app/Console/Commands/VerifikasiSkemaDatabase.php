<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Console\Command\Command as CommandAlias;

class VerifikasiSkemaDatabase extends Command
{
    protected $signature = 'skema:verifikasi {--rinci : Tampilkan hasil pemeriksaan setiap tabel}';

    protected $description = 'Memeriksa tabel, view, kolom, tipe, nullable, default, index, dan foreign key terhadap SQL final.';

    private string $lokasiSql;

    /** @var array<int, string> */
    private array $kesalahan = [];

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->error('Perintah ini hanya mendukung MySQL/MariaDB.');

            return CommandAlias::FAILURE;
        }

        $this->lokasiSql = base_path('struktur_database_toko_bangunan.sql');

        if (! is_file($this->lokasiSql)) {
            $this->error('Berkas SQL final tidak ditemukan: '.$this->lokasiSql);

            return CommandAlias::FAILURE;
        }

        $sql = (string) file_get_contents($this->lokasiSql);
        $tabelDiharapkan = $this->ambilNamaTabel($sql);
        $viewDiharapkan = $this->ambilNamaView($sql);
        [$tabelAktual, $viewAktual] = $this->ambilObjekDatabase();

        $this->newLine();
        $this->info('Verifikasi baseline database Sistem POS Toko Bangunan');
        $this->line('Database : '.DB::connection()->getDatabaseName());
        $this->line('SQL final: '.$this->lokasiSql);
        $this->newLine();

        $this->bandingkanDaftar('tabel', $tabelDiharapkan, $tabelAktual);
        $this->bandingkanDaftar('view', $viewDiharapkan, $viewAktual);

        foreach ($tabelDiharapkan as $namaTabel) {
            $definisi = $this->ambilDefinisiTabel($sql, $namaTabel);

            if ($definisi === null) {
                $this->kesalahan[] = "Definisi tabel {$namaTabel} tidak dapat diparsing dari SQL final.";
                continue;
            }

            if (! in_array($namaTabel, $tabelAktual, true)) {
                continue;
            }

            $this->verifikasiKolom($namaTabel, $definisi);
            $this->verifikasiIndex($namaTabel, $definisi);
            $this->verifikasiForeignKey($namaTabel, $definisi);

            if ($this->option('rinci')) {
                $this->components->twoColumnDetail($namaTabel, '<fg=green>selesai diperiksa</>');
            }
        }

        $this->newLine();
        $this->table(
            ['Objek', 'Diharapkan', 'Aktual'],
            [
                ['Tabel bisnis', count($tabelDiharapkan), count($tabelAktual)],
                ['View', count($viewDiharapkan), count($viewAktual)],
            ]
        );

        if ($this->kesalahan !== []) {
            $this->newLine();
            $this->error('Skema BELUM identik dengan SQL final.');

            foreach ($this->kesalahan as $nomor => $kesalahan) {
                $this->line(sprintf('%d. %s', $nomor + 1, $kesalahan));
            }

            return CommandAlias::FAILURE;
        }

        $this->newLine();
        $this->info('Skema sesuai dengan SQL final untuk objek yang diverifikasi.');

        return CommandAlias::SUCCESS;
    }

    /** @return array<int, string> */
    private function ambilNamaTabel(string $sql): array
    {
        preg_match_all(
            '/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?([A-Za-z0-9_]+)`?/i',
            $sql,
            $hasil
        );

        return array_values(array_unique($hasil[1] ?? []));
    }

    /** @return array<int, string> */
    private function ambilNamaView(string $sql): array
    {
        preg_match_all(
            '/CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+`?([A-Za-z0-9_]+)`?/i',
            $sql,
            $hasil
        );

        return array_values(array_unique($hasil[1] ?? []));
    }

    /** @return array{0: array<int, string>, 1: array<int, string>} */
    private function ambilObjekDatabase(): array
    {
        $baris = DB::select(
            'SELECT TABLE_NAME AS nama, TABLE_TYPE AS jenis
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
             ORDER BY TABLE_NAME'
        );

        $tabel = [];
        $view = [];

        foreach ($baris as $objek) {
            if ($objek->jenis === 'BASE TABLE' && $objek->nama !== 'migrations') {
                $tabel[] = $objek->nama;
            }

            if ($objek->jenis === 'VIEW') {
                $view[] = $objek->nama;
            }
        }

        return [$tabel, $view];
    }

    /**
     * @param array<int, string> $diharapkan
     * @param array<int, string> $aktual
     */
    private function bandingkanDaftar(string $jenis, array $diharapkan, array $aktual): void
    {
        $hilang = array_values(array_diff($diharapkan, $aktual));
        $tambahan = array_values(array_diff($aktual, $diharapkan));

        foreach ($hilang as $nama) {
            $this->kesalahan[] = ucfirst($jenis)." hilang: {$nama}.";
        }

        foreach ($tambahan as $nama) {
            $this->kesalahan[] = ucfirst($jenis)." tambahan di luar SQL final: {$nama}.";
        }
    }

    private function ambilDefinisiTabel(string $sql, string $namaTabel): ?string
    {
        $pola = sprintf(
            '/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?%s`?\s*\((.*?)\)\s*ENGINE\s*=\s*InnoDB\s*;/is',
            preg_quote($namaTabel, '/')
        );

        return preg_match($pola, $sql, $hasil) === 1 ? $hasil[1] : null;
    }

    private function verifikasiKolom(string $namaTabel, string $definisi): void
    {
        $kolomDiharapkan = $this->parseKolom($definisi);
        $kolomAktual = DB::select(sprintf('SHOW FULL COLUMNS FROM `%s`', $namaTabel));

        $namaDiharapkan = array_keys($kolomDiharapkan);
        $namaAktual = array_map(static fn (object $kolom): string => $kolom->Field, $kolomAktual);

        if ($namaDiharapkan !== $namaAktual) {
            $this->kesalahan[] = "Urutan/nama kolom tabel {$namaTabel} berbeda. Diharapkan: "
                .implode(', ', $namaDiharapkan).'. Aktual: '.implode(', ', $namaAktual).'.';
        }

        foreach ($kolomAktual as $kolom) {
            if (! isset($kolomDiharapkan[$kolom->Field])) {
                continue;
            }

            $expected = $kolomDiharapkan[$kolom->Field];
            $actualType = $this->normalisasiTipe((string) $kolom->Type);
            $actualNullable = $kolom->Null === 'YES';
            $actualDefault = $this->normalisasiDefault($kolom->Default);
            $actualAutoIncrement = str_contains((string) $kolom->Extra, 'auto_increment');

            if ($expected['tipe'] !== $actualType) {
                $this->kesalahan[] = "Tipe {$namaTabel}.{$kolom->Field} berbeda: "
                    ."diharapkan {$expected['tipe']}, aktual {$actualType}.";
            }

            if ($expected['nullable'] !== $actualNullable) {
                $this->kesalahan[] = "Nullable {$namaTabel}.{$kolom->Field} berbeda.";
            }

            if ($expected['default'] !== $actualDefault) {
                $this->kesalahan[] = "Default {$namaTabel}.{$kolom->Field} berbeda: "
                    ."diharapkan ".var_export($expected['default'], true)
                    .', aktual '.var_export($actualDefault, true).'.';
            }

            if ($expected['auto_increment'] !== $actualAutoIncrement) {
                $this->kesalahan[] = "AUTO_INCREMENT {$namaTabel}.{$kolom->Field} berbeda.";
            }
        }
    }

    /**
     * @return array<string, array{tipe: string, nullable: bool, default: ?string, auto_increment: bool}>
     */
    private function parseKolom(string $definisi): array
    {
        $hasil = [];

        foreach (preg_split('/\R/', $definisi) ?: [] as $baris) {
            $baris = trim(rtrim($baris, ','));

            if (
                $baris === ''
                || preg_match('/^(PRIMARY|UNIQUE|KEY|CONSTRAINT|FOREIGN)\b/i', $baris) === 1
            ) {
                continue;
            }

            if (preg_match('/^`?([A-Za-z0-9_]+)`?\s+(.+)$/', $baris, $cocok) !== 1) {
                continue;
            }

            $nama = $cocok[1];
            $atribut = $cocok[2];

            if (preg_match('/^((?:[A-Za-z]+)(?:\([^)]*\))?(?:\s+UNSIGNED)?)/i', $atribut, $tipe) !== 1) {
                throw new RuntimeException('Tipe kolom gagal diparsing: '.$baris);
            }

            $default = null;

            if (preg_match('/\bDEFAULT\s+(CURRENT_TIMESTAMP|NULL|[-+]?[0-9]+(?:\.[0-9]+)?|\'[^\']*\'|"[^"]*")/i', $atribut, $nilaiDefault) === 1) {
                $default = $this->normalisasiDefault($nilaiDefault[1]);
            }

            $hasil[$nama] = [
                'tipe' => $this->normalisasiTipe($tipe[1]),
                'nullable' => preg_match('/\bNOT\s+NULL\b/i', $atribut) !== 1,
                'default' => $default,
                'auto_increment' => preg_match('/\bAUTO_INCREMENT\b/i', $atribut) === 1,
            ];
        }

        return $hasil;
    }

    private function verifikasiIndex(string $namaTabel, string $definisi): void
    {
        $expected = $this->parseIndex($definisi);
        $barisAktual = DB::select(sprintf('SHOW INDEX FROM `%s`', $namaTabel));
        $actual = [];

        foreach ($barisAktual as $baris) {
            $nama = (string) $baris->Key_name;
            $actual[$nama]['unik'] = (int) $baris->Non_unique === 0;
            $actual[$nama]['kolom'][(int) $baris->Seq_in_index] = (string) $baris->Column_name;
        }

        foreach ($actual as &$index) {
            ksort($index['kolom']);
            $index['kolom'] = array_values($index['kolom']);
        }
        unset($index);

        if ($expected !== $actual) {
            $this->kesalahan[] = "Definisi index tabel {$namaTabel} berbeda dari SQL final.";
        }
    }

    /** @return array<string, array{unik: bool, kolom: array<int, string>}> */
    private function parseIndex(string $definisi): array
    {
        $hasil = [];

        foreach (preg_split('/\R/', $definisi) ?: [] as $baris) {
            $baris = trim(rtrim($baris, ','));

            if (preg_match('/^PRIMARY\s+KEY\s*\(([^)]+)\)/i', $baris, $cocok) === 1) {
                $hasil['PRIMARY'] = ['unik' => true, 'kolom' => $this->parseNamaKolomIndex($cocok[1])];
                continue;
            }

            if (preg_match('/^UNIQUE\s+KEY\s+`?([A-Za-z0-9_]+)`?\s*\(([^)]+)\)/i', $baris, $cocok) === 1) {
                $hasil[$cocok[1]] = ['unik' => true, 'kolom' => $this->parseNamaKolomIndex($cocok[2])];
                continue;
            }

            if (preg_match('/^KEY\s+`?([A-Za-z0-9_]+)`?\s*\(([^)]+)\)/i', $baris, $cocok) === 1) {
                $hasil[$cocok[1]] = ['unik' => false, 'kolom' => $this->parseNamaKolomIndex($cocok[2])];
            }
        }

        return $hasil;
    }

    /** @return array<int, string> */
    private function parseNamaKolomIndex(string $daftar): array
    {
        return array_map(
            static fn (string $kolom): string => trim(preg_replace('/\(\d+\)$/', '', trim($kolom, " `\t\n\r\0\x0B")) ?? $kolom),
            explode(',', $daftar)
        );
    }

    private function verifikasiForeignKey(string $namaTabel, string $definisi): void
    {
        $expected = $this->parseForeignKey($definisi);
        $barisAktual = DB::select(
            'SELECT
                kcu.CONSTRAINT_NAME AS nama,
                kcu.COLUMN_NAME AS kolom,
                kcu.REFERENCED_TABLE_NAME AS tabel_tujuan,
                kcu.REFERENCED_COLUMN_NAME AS kolom_tujuan,
                rc.DELETE_RULE AS aturan_hapus
             FROM information_schema.KEY_COLUMN_USAGE kcu
             INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
               AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
               AND rc.TABLE_NAME = kcu.TABLE_NAME
             WHERE kcu.CONSTRAINT_SCHEMA = DATABASE()
               AND kcu.TABLE_NAME = ?
               AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION',
            [$namaTabel]
        );

        $actual = [];

        foreach ($barisAktual as $baris) {
            $actual[$baris->nama] = [
                'kolom' => $baris->kolom,
                'tabel_tujuan' => $baris->tabel_tujuan,
                'kolom_tujuan' => $baris->kolom_tujuan,
                'aturan_hapus' => strtoupper((string) $baris->aturan_hapus),
            ];
        }

        if ($expected !== $actual) {
            $this->kesalahan[] = "Definisi foreign key tabel {$namaTabel} berbeda dari SQL final.";
        }
    }

    /**
     * @return array<string, array{kolom: string, tabel_tujuan: string, kolom_tujuan: string, aturan_hapus: string}>
     */
    private function parseForeignKey(string $definisi): array
    {
        $hasil = [];
        $pola = '/CONSTRAINT\s+`?([A-Za-z0-9_]+)`?\s+FOREIGN\s+KEY\s*\(`?([A-Za-z0-9_]+)`?\)\s+'
            .'REFERENCES\s+`?([A-Za-z0-9_]+)`?\s*\(`?([A-Za-z0-9_]+)`?\)'
            .'(?:\s+ON\s+DELETE\s+(CASCADE|SET\s+NULL|RESTRICT|NO\s+ACTION))?/is';

        preg_match_all($pola, $definisi, $cocok, PREG_SET_ORDER);

        foreach ($cocok as $relasi) {
            $hasil[$relasi[1]] = [
                'kolom' => $relasi[2],
                'tabel_tujuan' => $relasi[3],
                'kolom_tujuan' => $relasi[4],
                'aturan_hapus' => strtoupper(preg_replace('/\s+/', ' ', $relasi[5] ?? 'RESTRICT') ?? 'RESTRICT'),
            ];
        }

        return $hasil;
    }

    private function normalisasiTipe(string $tipe): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim($tipe)) ?? trim($tipe));
    }

    private function normalisasiDefault(mixed $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = trim((string) $nilai);

        if (strtoupper($nilai) === 'NULL') {
            return null;
        }

        if (
            (str_starts_with($nilai, "'") && str_ends_with($nilai, "'"))
            || (str_starts_with($nilai, '"') && str_ends_with($nilai, '"'))
        ) {
            $nilai = substr($nilai, 1, -1);
        }

        return strtolower($nilai) === 'current_timestamp()'
            ? 'current_timestamp'
            : strtolower($nilai);
    }
}
