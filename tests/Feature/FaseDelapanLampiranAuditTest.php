<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use App\Services\AuditAktivitas;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaseDelapanLampiranAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE8_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration Fase 8 hanya dijalankan pada job MySQL khusus.');
        }

        Storage::fake('local');
        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_permission_dan_skema_fase_delapan_tetap_paten(): void
    {
        $kode = [
            'LAMPIRAN_LIHAT',
            'LAMPIRAN_UNGGAH',
            'LAMPIRAN_UNDUH',
            'LAMPIRAN_HAPUS',
            'AUDIT_LIHAT_DATA',
            'AUDIT_UNDUH',
        ];

        $this->assertSame(95, DB::table('hak_akses')->whereNull('deleted_at')->count());
        $this->assertSame(6, DB::table('hak_akses')->whereIn('kode_hak_akses', $kode)->whereNull('deleted_at')->count());

        $database = config('database.connections.mysql.database');
        $this->assertSame(71, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'BASE TABLE')->count());
        $this->assertSame(3, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_TYPE', 'VIEW')->count());
        $this->assertSame(0, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->whereIn('TABLE_NAME', ['sessions', 'cache', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'])->count());

        foreach (['lampiran_dokumen', 'log_aktivitas'] as $tabel) {
            $this->assertSame(1, DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)->where('TABLE_NAME', $tabel)->where('TABLE_TYPE', 'BASE TABLE')->count());
        }
    }

    public function test_lampiran_disimpan_privat_dapat_diunduh_dan_dihapus_logis(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $idJurnal = $this->buatJurnal($cabang, 'JR-F8-UTAMA');
        $session = $this->sessionCabang($cabang);

        $this->actingAs($admin)->withSession($session)->post('/lampiran', [
            'jenis_dokumen' => 'JURNAL_UMUM',
            'id_dokumen' => $idJurnal,
            'berkas' => UploadedFile::fake()->create('bukti-fase8.pdf', 120, 'application/pdf'),
            'keterangan' => 'Bukti jurnal pengujian Fase 8',
        ])->assertSessionHasNoErrors();

        $lampiran = DB::table('lampiran_dokumen')->orderByDesc('id_lampiran_dokumen')->first();
        $this->assertNotNull($lampiran);
        $this->assertSame('JURNAL_UMUM', $lampiran->jenis_dokumen);
        $this->assertSame($idJurnal, (int) $lampiran->id_dokumen);
        $this->assertSame('bukti-fase8.pdf', $lampiran->nama_berkas_asli);
        $this->assertNotSame($lampiran->nama_berkas_asli, $lampiran->nama_berkas);
        $this->assertStringStartsWith('lampiran/jurnal_umum/', $lampiran->lokasi_berkas);
        Storage::disk('local')->assertExists($lampiran->lokasi_berkas);

        $this->actingAs($admin)
            ->withSession($session)
            ->get('/lampiran/'.$lampiran->id_lampiran_dokumen.'/unduh')
            ->assertOk()
            ->assertDownload('bukti-fase8.pdf');

        $this->assertSame(1, DB::table('log_aktivitas')
            ->where('nama_modul', 'LAMPIRAN')
            ->where('jenis_aktivitas', 'UNDUH')
            ->where('id_referensi', $lampiran->id_lampiran_dokumen)
            ->count());

        $this->actingAs($admin)
            ->withSession($session)
            ->delete('/lampiran/'.$lampiran->id_lampiran_dokumen)
            ->assertSessionHasNoErrors();

        $this->assertNotNull(DB::table('lampiran_dokumen')->where('id_lampiran_dokumen', $lampiran->id_lampiran_dokumen)->value('deleted_at'));
        Storage::disk('local')->assertExists($lampiran->lokasi_berkas);

        $this->actingAs($admin)
            ->withSession($session)
            ->get('/lampiran/'.$lampiran->id_lampiran_dokumen.'/unduh')
            ->assertNotFound();
    }

    public function test_jenis_berkas_path_dan_dokumen_cabang_lain_ditolak(): void
    {
        [$admin, $cabangAktif] = $this->adminDanCabang();
        $idJurnalAktif = $this->buatJurnal($cabangAktif, 'JR-F8-AKTIF');
        $session = $this->sessionCabang($cabangAktif);

        $this->actingAs($admin)->withSession($session)->post('/lampiran', [
            'jenis_dokumen' => 'JURNAL_UMUM',
            'id_dokumen' => $idJurnalAktif,
            'berkas' => UploadedFile::fake()->create('bahaya.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('berkas');

        $cabangLain = (int) DB::table('cabang')->insertGetId([
            'kode_cabang' => 'CAB-F8-LAIN',
            'nama_cabang' => 'Cabang F8 Lain',
            'alamat' => 'Alamat cabang lain',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);
        $idJurnalLain = (int) DB::table('jurnal_umum')->insertGetId([
            'id_cabang' => $cabangLain,
            'nomor_jurnal' => 'JR-F8-LAIN',
            'tanggal_jurnal' => '2026-07-23',
            'keterangan' => 'Jurnal cabang lain',
            'status_jurnal' => 'DRAF',
            'created_at' => now(),
        ]);
        Storage::disk('local')->put('lampiran/jurnal_umum/2026/07/lain.pdf', 'isi');
        $idLampiranLain = (int) DB::table('lampiran_dokumen')->insertGetId([
            'jenis_dokumen' => 'JURNAL_UMUM',
            'id_dokumen' => $idJurnalLain,
            'nama_berkas' => 'lain.pdf',
            'nama_berkas_asli' => 'lain.pdf',
            'lokasi_berkas' => 'lampiran/jurnal_umum/2026/07/lain.pdf',
            'jenis_berkas' => 'application/pdf',
            'ukuran_berkas' => 3,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->withSession($session)->get('/lampiran/'.$idLampiranLain.'/unduh')->assertNotFound();
        $this->actingAs($admin)->withSession($session)->delete('/lampiran/'.$idLampiranLain)->assertNotFound();

        $idPathTidakAman = (int) DB::table('lampiran_dokumen')->insertGetId([
            'jenis_dokumen' => 'JURNAL_UMUM',
            'id_dokumen' => $idJurnalAktif,
            'nama_berkas' => 'env',
            'nama_berkas_asli' => '.env',
            'lokasi_berkas' => '../.env',
            'jenis_berkas' => 'text/plain',
            'ukuran_berkas' => 1,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->withSession($session)->get('/lampiran/'.$idPathTidakAman.'/unduh')->assertNotFound();
    }

    public function test_data_sensitif_disamarkan_dan_audit_dapat_difilter_serta_diunduh(): void
    {
        [$admin, $cabang] = $this->adminDanCabang();
        $session = app('session')->driver();
        $session->start();
        $session->put('id_cabang_aktif', $cabang->id_cabang);
        $session->put('nama_cabang_aktif', $cabang->nama_cabang);

        $request = Request::create('/uji-audit', 'POST');
        $request->setLaravelSession($session);
        $request->setUserResolver(fn (): Pengguna => $admin);

        app(AuditAktivitas::class)->catat(
            $request,
            'UJI_FASE_8',
            'UBAH',
            'pengguna',
            (int) $admin->id_pengguna,
            'Menguji penyamaran audit.',
            ['nama' => 'Sebelum', 'kata_sandi' => 'rahasia-lama'],
            ['nama' => 'Sesudah', 'password_baru' => 'rahasia-baru', 'token_api' => 'token-rahasia'],
        );

        $log = DB::table('log_aktivitas')->where('nama_modul', 'UJI_FASE_8')->orderByDesc('id_log_aktivitas')->first();
        $gabungan = ($log->data_sebelum ?? '').($log->data_sesudah ?? '');
        $this->assertStringContainsString('[DISEMBUNYIKAN]', $gabungan);
        $this->assertStringNotContainsString('rahasia-lama', $gabungan);
        $this->assertStringNotContainsString('rahasia-baru', $gabungan);
        $this->assertStringNotContainsString('token-rahasia', $gabungan);

        $this->actingAs($admin)
            ->withSession($this->sessionCabang($cabang))
            ->get('/audit?nama_modul=UJI_FASE_8')
            ->assertOk()
            ->assertSee('Menguji penyamaran audit.');

        $this->actingAs($admin)
            ->withSession($this->sessionCabang($cabang))
            ->get('/audit/'.$log->id_log_aktivitas)
            ->assertOk()
            ->assertSee('[DISEMBUNYIKAN]')
            ->assertDontSee('rahasia-baru');

        $this->actingAs($admin)
            ->withSession($this->sessionCabang($cabang))
            ->get('/audit-unduh/csv?nama_modul=UJI_FASE_8')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertSame(1, DB::table('log_aktivitas')
            ->where('nama_modul', 'AUDIT')
            ->where('jenis_aktivitas', 'UNDUH')
            ->count());
    }

    private function adminDanCabang(): array
    {
        return [
            Pengguna::query()->where('nama_pengguna', 'admin_fase8')->firstOrFail(),
            Cabang::query()->where('kode_cabang', 'CAB-UJI')->firstOrFail(),
        ];
    }

    private function sessionCabang(Cabang $cabang): array
    {
        return [
            'id_cabang_aktif' => $cabang->id_cabang,
            'nama_cabang_aktif' => $cabang->nama_cabang,
        ];
    }

    private function buatJurnal(Cabang $cabang, string $nomor): int
    {
        return (int) DB::table('jurnal_umum')->insertGetId([
            'id_cabang' => $cabang->id_cabang,
            'nomor_jurnal' => $nomor.'-'.uniqid(),
            'tanggal_jurnal' => '2026-07-23',
            'sumber_jurnal' => 'PENGUJIAN',
            'keterangan' => 'Jurnal pengujian Fase 8',
            'status_jurnal' => 'DRAF',
            'created_at' => now(),
            'created_by' => $adminId = Pengguna::query()->where('nama_pengguna', 'admin_fase8')->value('id_pengguna'),
        ]);
    }
}
