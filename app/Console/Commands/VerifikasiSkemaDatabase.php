<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class VerifikasiSkemaDatabase extends Command
{
    protected $signature = 'skema:verifikasi {--rinci : Tampilkan hasil pemeriksaan setiap tabel}';

    protected $description = 'Membandingkan skema MySQL aktif dengan struktur_database_toko_bangunan.sql.';

    /** @var array<int, string> */
    private array $kesalahan = [];

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->error('Verifikasi skema hanya mendukung MySQL/MariaDB.');

            return SymfonyCommand::FAILURE;
        }

        $lokasiSql = base_path('struktur_database_toko_bangunan.sql');

        if (! is_file($lokasiSql)) {
            $this->error('SQL final tidak ditemukan: '.$lokasiSql);

            return SymfonyCommand::FAILURE;
        }

        $sql = (string) file_get_contents($lokasiSql);
        $definisiTabel = $this->ambilDefinisiTabel($sql);
        $namaView = $this->ambilNamaView($sql);
        [$tabelAktual, $viewAktual] = $this->ambilObjekDatabase();

        $this->info('Verifikasi skema database final');
        $this->line('Database: '.DB::connection()->getDatabaseName());
        $this->line('Acuan   : struktur_database_toko_bangunan.sql');
        $this->newLine();

        $this->bandingkanNamaObjek('tabel', array_keys($definisiTabel), $tabelAktual);
        $this->bandingkanNamaObjek('view', $namaView, $viewAktual);

        foreach ($definisiTabel as $namaTabel => $isiDefinisi) {
            if (! in_array($namaTabel, $tabelAktual, true)) {
                continue;
            }

            $this->verifikasiKolom($namaTabel, $isiDefinisi);
            $this->verifikasiIndex($namaTabel, $isiDefinisi);
            $this->verifikasiForeignKey($namaTabel, $isiDefinisi);

            if ($this->option('rinci')) {
                $this->components->twoColumnDetail($namaTabel, '<fg=green>diperiksa</>');
            }
        }

        $this->newLine();
        $this->table(
            ['Objek', 'SQL final', 'Database aktif'],
            [
                ['Tabel bisnis', count($definisiTabel), count($tabelAktual)],
                ['View', count($namaView), count($viewAktual)],
            ]
        );

        if ($this->kesalahan !== []) {
            $this->newLine();
            $this->error('Skema BELUM sesuai dengan SQL final.');

            foreach ($this->kesalahan as $nomor => $kesalahan) {
                $this->line(($nomor + 1).'. '.$kesalahan);
            }

            return SymfonyCommand::FAILURE;
        }

        $this->newLine();
        $this->info('Skema bisnis sesuai dengan SQL final.');
        $this->warn('Tabel internal Laravel "migrations" tidak dihitung sebagai tabel bisnis.');

        return SymfonyCommand::SUCCESS;
    }

    /** @return array<string, string> */
    private function ambilDefinisiTabel(string $sql): array
    {
        preg_match_all(
            '/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?([A-Za-z0-9_]+)`?\s*\((.*?)\)\s*ENGINE\s*=\s*InnoDB\s*;/is',
            $sql,
            $hasil,
            PREG_SET_ORDER
        );

        $definisi = [];

        foreach ($hasil as $tabel) {
            $definisi[$tabel[1]] = $tabel[2];
        }

        return $definisi;
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
        $objek = DB::select(
            'SELECT TABLE_NAME AS nama, TABLE_TYPE AS jenis
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
             ORDER BY TABLE_NAME'
        );

        $tabel = [];
        $view = [];

        foreach ($objek as $item) {
            if ($item->jenis === 'BASE TABLE' && $item->nama !== 'migrations') {
                $tabel[] = $item->nama;
            }

            if ($item->jenis === 'VIEW') {
                $view[] = $item->nama;
            }
        }

        return [$tabel, $view];
    }

    /**
     * @param  array<int, string>  $diharapkan
     * @param  array<int, string>  $aktual
     */
    private function bandingkanNamaObjek(string $jenis, array $diharapkan, array $aktual): void
    {
        foreach (array_diff($diharapkan, $aktual) as $nama) {
            $this->kesalahan[] = ucfirst($jenis).' hilang: '.$nama.'.';
        }

        foreach (array_diff($aktual, $diharapkan) as $nama) {
            $this->kesalahan[] = ucfirst($jenis).' tambahan di luar SQL final: '.$nama.'.';
        }
    }

    private function verifikasiKolom(string $namaTabel, string $definisi): void
    {
        $expected = $this->parseKolom($definisi);
        $barisAktual = DB::select(sprintf('SHOW FULL COLUMNS FROM `%s`', $namaTabel));
        $actual = [];

        foreach ($barisAktual as $kolom) {
            $tipe = $this->normalisasiTipe((string) $kolom->Type);

            $actual[$kolom->Field] = [
                'tipe' => $tipe,
                'nullable' => $kolom->Null === 'YES',
                'default' => $this->normalisasiDefault($kolom->Default, $tipe),
                'auto_increment' => str_contains((string) $kolom->Extra, 'auto_increment'),
            ];
        }

        if (array_keys($expected) !== array_keys($actual)) {
            $this->kesalahan[] = 'Nama atau urutan kolom berbeda pada tabel '.$namaTabel.'.';
        }

        foreach ($expected as $namaKolom => $aturan) {
            if (! isset($actual[$namaKolom])) {
                continue;
            }

            foreach ($aturan as $atribut => $nilai) {
                if ($actual[$namaKolom][$atribut] !== $nilai) {
                    $this->kesalahan[] = sprintf(
                        '%s.%s berbeda pada %s: SQL=%s, DB=%s.',
                        $namaTabel,
                        $namaKolom,
                        $atribut,
                        var_export($nilai, true),
                        var_export($actual[$namaKolom][$atribut], true)
                    );
                }
            }
        }
    }

    /**
     * @return array<string, array{tipe: string, nullable: bool, default: ?string, auto_increment: bool}>
     */
    private function parseKolom(string $definisi): array
    {
        $hasil = [];

        foreach ($this->bagianDefinisi($definisi) as $bagian) {
            if (preg_match('/^(PRIMARY|UNIQUE|KEY|CONSTRAINT|FOREIGN|REFERENCES|ON)\b/i', $bagian) === 1) {
                continue;
            }

            if (preg_match('/^`?([A-Za-z0-9_]+)`?\s+(.+)$/s', $bagian, $cocok) !== 1) {
                continue;
            }

            $nama = $cocok[1];
            $atribut = $cocok[2];

            if (preg_match('/^([A-Za-z]+(?:\([^)]*\))?(?:\s+UNSIGNED)?)/is', $atribut, $tipeCocok) !== 1) {
                continue;
            }

            $tipe = $this->normalisasiTipe($tipeCocok[1]);
            $default = null;

            if (preg_match('/\bDEFAULT\s+(CURRENT_TIMESTAMP|NULL|[-+]?[0-9]+(?:\.[0-9]+)?|\'[^\']*\'|"[^"]*")/i', $atribut, $nilaiDefault) === 1) {
                $default = $this->normalisasiDefault($nilaiDefault[1], $tipe);
            }

            $primaryAtauAutoIncrement = preg_match('/\b(?:PRIMARY\s+KEY|AUTO_INCREMENT)\b/i', $atribut) === 1;

            $hasil[$nama] = [
                'tipe' => $tipe,
                'nullable' => $primaryAtauAutoIncrement
                    ? false
                    : preg_match('/\bNOT\s+NULL\b/i', $atribut) !== 1,
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
            $namaIndex = (string) $baris->Key_name;
            $actual[$namaIndex]['unik'] = (int) $baris->Non_unique === 0;
            $actual[$namaIndex]['kolom'][(int) $baris->Seq_in_index] = (string) $baris->Column_name;
        }

        foreach ($actual as &$index) {
            ksort($index['kolom']);
            $index['kolom'] = array_values($index['kolom']);
        }
        unset($index);

        foreach ($expected as $namaIndex => $aturan) {
            if (! isset($actual[$namaIndex])) {
                $this->kesalahan[] = "Index {$namaTabel}.{$namaIndex} tidak ditemukan.";

                continue;
            }

            if ($actual[$namaIndex] !== $aturan) {
                $this->kesalahan[] = "Definisi index {$namaTabel}.{$namaIndex} berbeda.";
            }
        }
    }

    /** @return array<string, array{unik: bool, kolom: array<int, string>}> */
    private function parseIndex(string $definisi): array
    {
        $hasil = [];

        foreach ($this->bagianDefinisi($definisi) as $bagian) {
            if (preg_match('/^`?([A-Za-z0-9_]+)`?\s+.+\bPRIMARY\s+KEY\b/is', $bagian, $inline) === 1) {
                $hasil['PRIMARY'] = ['unik' => true, 'kolom' => [$inline[1]]];

                continue;
            }

            if (preg_match('/^PRIMARY\s+KEY\s*\(([^)]+)\)/i', $bagian, $cocok) === 1) {
                $hasil['PRIMARY'] = ['unik' => true, 'kolom' => $this->parseKolomIndex($cocok[1])];

                continue;
            }

            if (preg_match('/^UNIQUE\s+KEY\s+`?([A-Za-z0-9_]+)`?\s*\(([^)]+)\)/i', $bagian, $cocok) === 1) {
                $hasil[$cocok[1]] = ['unik' => true, 'kolom' => $this->parseKolomIndex($cocok[2])];

                continue;
            }

            if (preg_match('/^KEY\s+`?([A-Za-z0-9_]+)`?\s*\(([^)]+)\)/i', $bagian, $cocok) === 1) {
                $hasil[$cocok[1]] = ['unik' => false, 'kolom' => $this->parseKolomIndex($cocok[2])];
            }
        }

        return $hasil;
    }

    /** @return array<int, string> */
    private function parseKolomIndex(string $daftar): array
    {
        return array_map(
            static fn (string $kolom): string => trim(
                preg_replace('/\(\d+\)$/', '', trim($kolom, " `\t\n\r\0\x0B")) ?? $kolom
            ),
            explode(',', $daftar)
        );
    }

    private function verifikasiForeignKey(string $namaTabel, string $definisi): void
    {
        $expected = $this->parseForeignKey($definisi);
        $barisAktual = DB::select(
            'SELECT kcu.CONSTRAINT_NAME AS nama,
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
                'aturan_hapus' => $this->normalisasiAturanHapus((string) $baris->aturan_hapus),
            ];
        }

        ksort($expected);
        ksort($actual);

        if ($expected !== $actual) {
            $this->kesalahan[] = 'Foreign key tabel '.$namaTabel.' berbeda dari SQL final.';
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

        preg_match_all($pola, $definisi, $relasi, PREG_SET_ORDER);

        foreach ($relasi as $item) {
            $hasil[$item[1]] = [
                'kolom' => $item[2],
                'tabel_tujuan' => $item[3],
                'kolom_tujuan' => $item[4],
                'aturan_hapus' => $this->normalisasiAturanHapus($item[5] ?? 'NO ACTION'),
            ];
        }

        return $hasil;
    }

    /** @return array<int, string> */
    private function bagianDefinisi(string $definisi): array
    {
        $hasil = [];
        $buffer = '';
        $kedalamanKurung = 0;
        $kutipTunggal = false;
        $kutipGanda = false;
        $kutipIdentifier = false;
        $panjang = strlen($definisi);

        for ($i = 0; $i < $panjang; $i++) {
            $karakter = $definisi[$i];
            $berikutnya = $i + 1 < $panjang ? $definisi[$i + 1] : '';
            $sebelumnya = $i > 0 ? $definisi[$i - 1] : '';
            $terlepas = $sebelumnya === '\\' && ($i < 2 || $definisi[$i - 2] !== '\\');

            if ($kutipTunggal && $karakter === "'" && $berikutnya === "'") {
                $buffer .= "''";
                $i++;

                continue;
            }

            if ($karakter === "'" && ! $kutipGanda && ! $kutipIdentifier && ! $terlepas) {
                $kutipTunggal = ! $kutipTunggal;
            } elseif ($karakter === '"' && ! $kutipTunggal && ! $kutipIdentifier && ! $terlepas) {
                $kutipGanda = ! $kutipGanda;
            } elseif ($karakter === '`' && ! $kutipTunggal && ! $kutipGanda && ! $terlepas) {
                $kutipIdentifier = ! $kutipIdentifier;
            }

            if (! $kutipTunggal && ! $kutipGanda && ! $kutipIdentifier) {
                if ($karakter === '(') {
                    $kedalamanKurung++;
                } elseif ($karakter === ')') {
                    $kedalamanKurung = max(0, $kedalamanKurung - 1);
                } elseif ($karakter === ',' && $kedalamanKurung === 0) {
                    if (trim($buffer) !== '') {
                        $hasil[] = trim($buffer);
                    }

                    $buffer = '';

                    continue;
                }
            }

            $buffer .= $karakter;
        }

        if (trim($buffer) !== '') {
            $hasil[] = trim($buffer);
        }

        return $hasil;
    }

    private function normalisasiTipe(string $tipe): string
    {
        $tipe = strtolower(preg_replace('/\s+/', ' ', trim($tipe)) ?? trim($tipe));

        return preg_replace('/\s*([(),])\s*/', '$1', $tipe) ?? $tipe;
    }

    private function normalisasiAturanHapus(string $aturan): string
    {
        $aturan = strtoupper(preg_replace('/\s+/', ' ', trim($aturan)) ?? trim($aturan));

        return $aturan === 'RESTRICT' ? 'NO ACTION' : $aturan;
    }

    private function normalisasiDefault(mixed $nilai, ?string $tipe = null): ?string
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

        $nilaiHurufKecil = strtolower($nilai);

        if (in_array($nilaiHurufKecil, ['current_timestamp', 'current_timestamp()'], true)) {
            return 'current_timestamp';
        }

        if ($this->tipeNumerik($tipe) && preg_match('/^[+-]?\d+(?:\.\d+)?$/', $nilai) === 1) {
            return $this->normalisasiAngka($nilai);
        }

        return $nilaiHurufKecil;
    }

    private function tipeNumerik(?string $tipe): bool
    {
        if ($tipe === null) {
            return false;
        }

        return preg_match(
            '/^(?:tinyint|smallint|mediumint|int|integer|bigint|decimal|numeric|float|double|real|bit)\b/i',
            trim($tipe)
        ) === 1;
    }

    private function normalisasiAngka(string $nilai): string
    {
        $negatif = str_starts_with($nilai, '-');
        $tanpaTanda = ltrim($nilai, '+-');
        [$bagianBulat, $bagianDesimal] = array_pad(explode('.', $tanpaTanda, 2), 2, '');

        $bagianBulat = ltrim($bagianBulat, '0');
        $bagianBulat = $bagianBulat === '' ? '0' : $bagianBulat;
        $bagianDesimal = rtrim($bagianDesimal, '0');

        $hasil = $bagianDesimal === ''
            ? $bagianBulat
            : $bagianBulat.'.'.$bagianDesimal;

        if ($negatif && $hasil !== '0') {
            return '-'.$hasil;
        }

        return $hasil;
    }
}
