<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaseTujuhKeuanganTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE7_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration Fase 7 hanya dijalankan pada job MySQL khusus.');
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_permission_dan_skema_fase_tujuh_tetap_paten(): void
    {
        $kode = [
            'KEUANGAN_LIHAT',
            'AKUN_KEUANGAN_LIHAT',
            'AKUN_KEUANGAN_KELOLA',
            'PEMETAAN_AKUN_KELOLA',
            'TRANSAKSI_KAS_LIHAT',
            'TRANSAKSI_KAS_KELOLA',
            'TRANSAKSI_KAS_SETUJUI',
            'JURNAL_UMUM_LIHAT',
            'JURNAL_UMUM_KELOLA',
            'JURNAL_UMUM_POSTING',
            'LAPORAN_KAS_BANK_LIHAT',
            'LAPORAN_BUKU_BESAR_LIHAT',
            'LAPORAN_NERACA_SALDO_LIHAT',
            'LAPORAN_KEUANGAN_LIHAT',
        ];

        $this->assertSame(89, DB::table('hak_akses')->whereNull('deleted_at')->count());
        $this->assertSame(14, DB::table('hak_akses')->whereIn('kode_hak_akses', $kode)->whereNull('deleted_at')->count());

        $database = config('database.connections.mysql.database');
        $this->assertSame(71, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'BASE TABLE')->count());
        $this->assertSame(3, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'VIEW')->count());
        $this->assertSame(0, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->whereIn('TABLE_NAME', ['sessions', 'cache', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'])->count());

        foreach (['transaksi_kas', 'akun_keuangan', 'pemetaan_akun', 'jurnal_umum', 'jurnal_umum_detail'] as $tabel) {
            $this->assertSame(1, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_NAME', $tabel)->where('TABLE_TYPE', 'BASE TABLE')->count());
        }
    }

    public function test_kas_masuk_keluar_dan_pindah_membentuk_jurnal_berimbang(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $kasUtama = $this->buatKasBank($cabang, 'KAS', 'KAS-F7');
        $bankUtama = $this->buatKasBank($cabang, 'BANK', 'BANK-F7');
        $this->artisan('fase7:siapkan')->assertSuccessful();
        $session = $this->sessionCabang($cabang);

        $this->actingAs($admin)->withSession($session)->post('/keuangan/transaksi-kas', [
            'id_kas_bank' => $kasUtama,
            'tanggal_transaksi' => '2026-07-23 09:00:00',
            'jenis_transaksi' => 'MASUK',
            'nilai_transaksi' => 1000000,
            'keterangan' => 'Setoran modal awal pengujian',
        ])->assertSessionHasNoErrors();
        $idMasuk = (int) DB::table('transaksi_kas')->max('id_transaksi_kas');
        $this->actingAs($admin)->withSession($session)->patch("/keuangan/transaksi-kas/{$idMasuk}/setujui")->assertSessionHasNoErrors();
        $this->assertJurnalSumberBerimbang('TRANSAKSI_KAS', $idMasuk, 1000000);

        $this->actingAs($admin)->withSession($session)->post('/keuangan/transaksi-kas', [
            'id_kas_bank' => $kasUtama,
            'tanggal_transaksi' => '2026-07-23 10:00:00',
            'jenis_transaksi' => 'KELUAR',
            'nilai_transaksi' => 250000,
            'keterangan' => 'Beban operasional pengujian',
        ])->assertSessionHasNoErrors();
        $idKeluar = (int) DB::table('transaksi_kas')->max('id_transaksi_kas');
        $this->actingAs($admin)->withSession($session)->patch("/keuangan/transaksi-kas/{$idKeluar}/setujui")->assertSessionHasNoErrors();
        $this->assertJurnalSumberBerimbang('TRANSAKSI_KAS', $idKeluar, 250000);

        $this->actingAs($admin)->withSession($session)->post('/keuangan/transaksi-kas', [
            'id_kas_bank' => $kasUtama,
            'id_kas_bank_tujuan' => $bankUtama,
            'tanggal_transaksi' => '2026-07-23 11:00:00',
            'jenis_transaksi' => 'PINDAH',
            'nilai_transaksi' => 300000,
            'keterangan' => 'Setoran dari kas ke bank',
        ])->assertSessionHasNoErrors();
        $idPindah = (int) DB::table('transaksi_kas')->max('id_transaksi_kas');
        $this->actingAs($admin)->withSession($session)->patch("/keuangan/transaksi-kas/{$idPindah}/setujui")->assertSessionHasNoErrors();
        $this->assertJurnalSumberBerimbang('TRANSAKSI_KAS', $idPindah, 300000);

        $akunKas = (int) DB::table('pemetaan_akun')->where('id_cabang', $cabang->id_cabang)->where('kunci_pemetaan', 'KAS_BANK_'.$kasUtama)->value('id_akun_keuangan');
        $akunBank = (int) DB::table('pemetaan_akun')->where('id_cabang', $cabang->id_cabang)->where('kunci_pemetaan', 'KAS_BANK_'.$bankUtama)->value('id_akun_keuangan');
        $this->assertNotSame($akunKas, $akunBank);

        $this->actingAs($admin)->withSession($session)->get('/keuangan?tanggal_awal=2026-07-01&tanggal_akhir=2026-07-31')
            ->assertOk()
            ->assertSee('Kas, Bank, dan Akuntansi')
            ->assertSee('Setoran modal awal pengujian');

        $this->assertSame('DISETUJUI', DB::table('transaksi_kas')->where('id_transaksi_kas', $idPindah)->value('status_transaksi'));
    }

    public function test_jurnal_manual_tidak_seimbang_ditolak_dan_yang_seimbang_dapat_diposting(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $session = $this->sessionCabang($cabang);
        $akunKas = (int) DB::table('akun_keuangan')->where('kode_akun', '110101')->value('id_akun_keuangan');
        $akunModal = (int) DB::table('akun_keuangan')->where('kode_akun', '310100')->value('id_akun_keuangan');
        $jumlahAwal = DB::table('jurnal_umum')->count();

        $this->actingAs($admin)->withSession($session)->post('/keuangan/jurnal', [
            'tanggal_jurnal' => '2026-07-23',
            'sumber_jurnal' => 'MANUAL',
            'keterangan' => 'Jurnal tidak seimbang',
            'detail' => [
                ['id_akun_keuangan' => $akunKas, 'debet' => 100000, 'kredit' => 0],
                ['id_akun_keuangan' => $akunModal, 'debet' => 0, 'kredit' => 90000],
            ],
        ])->assertSessionHasErrors('detail');
        $this->assertSame($jumlahAwal, DB::table('jurnal_umum')->count());

        $this->actingAs($admin)->withSession($session)->post('/keuangan/jurnal', [
            'tanggal_jurnal' => '2026-07-23',
            'sumber_jurnal' => 'MANUAL',
            'keterangan' => 'Tambahan modal manual',
            'detail' => [
                ['id_akun_keuangan' => $akunKas, 'debet' => 100000, 'kredit' => 0],
                ['id_akun_keuangan' => $akunModal, 'debet' => 0, 'kredit' => 100000],
            ],
        ])->assertSessionHasNoErrors();

        $idJurnal = (int) DB::table('jurnal_umum')->max('id_jurnal_umum');
        $this->assertSame('DRAF', DB::table('jurnal_umum')->where('id_jurnal_umum', $idJurnal)->value('status_jurnal'));
        $this->actingAs($admin)->withSession($session)->patch("/keuangan/jurnal/{$idJurnal}/posting")->assertSessionHasNoErrors();
        $this->assertSame('DIPOSTING', DB::table('jurnal_umum')->where('id_jurnal_umum', $idJurnal)->value('status_jurnal'));
        $this->assertJurnalBerimbang($idJurnal, 100000);
    }

    public function test_transaksi_kas_cabang_lain_tidak_dapat_disetujui(): void
    {
        [$admin, $cabangAktif] = $this->adminDanCabang();
        $cabangLain = (int) DB::table('cabang')->insertGetId([
            'kode_cabang' => 'CAB-F7-LAIN',
            'nama_cabang' => 'Cabang F7 Lain',
            'alamat' => 'Alamat cabang lain',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $kasLain = (int) DB::table('kas_bank')->insertGetId([
            'id_cabang' => $cabangLain,
            'kode_kas_bank' => 'KAS-F7-LAIN',
            'nama_kas_bank' => 'Kas Cabang Lain',
            'jenis_kas_bank' => 'KAS',
            'saldo_awal' => 0,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $id = (int) DB::table('transaksi_kas')->insertGetId([
            'id_cabang' => $cabangLain,
            'id_kas_bank' => $kasLain,
            'nomor_transaksi' => 'KB-F7-LAIN',
            'tanggal_transaksi' => '2026-07-23 12:00:00',
            'jenis_transaksi' => 'MASUK',
            'nilai_transaksi' => 10000,
            'keterangan' => 'Transaksi cabang lain',
            'status_transaksi' => 'DRAF',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->withSession($this->sessionCabang($cabangAktif))->patch("/keuangan/transaksi-kas/{$id}/setujui")->assertNotFound();
        $this->assertSame('DRAF', DB::table('transaksi_kas')->where('id_transaksi_kas', $id)->value('status_transaksi'));
    }

    private function adminDanCabang(): array
    {
        return [
            Pengguna::query()->where('nama_pengguna', 'admin_fase7')->firstOrFail(),
            Cabang::query()->where('kode_cabang', 'CAB-UJI')->firstOrFail(),
        ];
    }

    private function sessionCabang(Cabang $cabang): array
    {
        return ['id_cabang_aktif' => $cabang->id_cabang, 'nama_cabang_aktif' => $cabang->nama_cabang];
    }

    private function buatKasBank(Cabang $cabang, string $jenis, string $kode): int
    {
        return (int) DB::table('kas_bank')->insertGetId([
            'id_cabang' => $cabang->id_cabang,
            'kode_kas_bank' => $kode.'-'.uniqid(),
            'nama_kas_bank' => $jenis.' Pengujian Fase 7 '.uniqid(),
            'jenis_kas_bank' => $jenis,
            'nama_bank' => $jenis === 'BANK' ? 'Bank Pengujian' : null,
            'nomor_rekening' => $jenis === 'BANK' ? '1234567890' : null,
            'saldo_awal' => 0,
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
    }

    private function assertJurnalSumberBerimbang(string $sumber, int $idSumber, float $nilai): void
    {
        $idJurnal = (int) DB::table('jurnal_umum')
            ->where('sumber_jurnal', $sumber)
            ->where('id_sumber', $idSumber)
            ->value('id_jurnal_umum');

        $this->assertGreaterThan(0, $idJurnal);
        $this->assertSame('DIPOSTING', DB::table('jurnal_umum')->where('id_jurnal_umum', $idJurnal)->value('status_jurnal'));
        $this->assertJurnalBerimbang($idJurnal, $nilai);
    }

    private function assertJurnalBerimbang(int $idJurnal, float $nilai): void
    {
        $totalDebet = (float) DB::table('jurnal_umum_detail')->where('id_jurnal_umum', $idJurnal)->sum('debet');
        $totalKredit = (float) DB::table('jurnal_umum_detail')->where('id_jurnal_umum', $idJurnal)->sum('kredit');
        $this->assertEqualsWithDelta($nilai, $totalDebet, 0.01);
        $this->assertEqualsWithDelta($nilai, $totalKredit, 0.01);
    }
}
