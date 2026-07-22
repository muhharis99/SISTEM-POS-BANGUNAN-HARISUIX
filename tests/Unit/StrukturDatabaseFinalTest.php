<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StrukturDatabaseFinalTest extends TestCase
{
    private string $sql;

    protected function setUp(): void
    {
        parent::setUp();

        $lokasi = dirname(__DIR__, 2).'/struktur_database_toko_bangunan.sql';

        self::assertFileExists($lokasi);

        $this->sql = (string) file_get_contents($lokasi);
    }

    public function test_sql_final_memiliki_tujuh_puluh_tabel_bisnis(): void
    {
        preg_match_all(
            '/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?([A-Za-z0-9_]+)`?/i',
            $this->sql,
            $hasil
        );

        $namaTabel = array_values(array_unique($hasil[1] ?? []));

        self::assertCount(70, $namaTabel);
        self::assertSame('cabang', $namaTabel[0]);
        self::assertSame('log_aktivitas', $namaTabel[69]);
    }

    public function test_sql_final_memiliki_tiga_view_yang_disepakati(): void
    {
        preg_match_all(
            '/CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+`?([A-Za-z0-9_]+)`?/i',
            $this->sql,
            $hasil
        );

        $namaView = array_values(array_unique($hasil[1] ?? []));

        self::assertSame([
            'tampilan_stok_tersedia',
            'tampilan_hutang_pemasok',
            'tampilan_piutang_pelanggan',
        ], $namaView);
    }

    public function test_sql_final_tidak_memuat_tabel_framework_tambahan(): void
    {
        foreach ([
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'password_reset_tokens',
            'roles',
            'permissions',
            'model_has_roles',
            'model_has_permissions',
            'role_has_permissions',
        ] as $tabelTerlarang) {
            self::assertDoesNotMatchRegularExpression(
                '/CREATE\s+TABLE[^;]*\b'.preg_quote($tabelTerlarang, '/').'\b/i',
                $this->sql,
                'SQL final tidak boleh memuat tabel tambahan: '.$tabelTerlarang
            );
        }
    }

    public function test_sql_final_menggunakan_database_yang_disepakati(): void
    {
        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS sistem_informasi_toko_bangunan',
            $this->sql
        );
        self::assertStringContainsString('USE sistem_informasi_toko_bangunan;', $this->sql);
    }
}
