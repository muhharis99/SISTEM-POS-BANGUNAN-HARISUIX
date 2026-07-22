<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pengguna;
use App\Models\PenggunaPeran;
use App\Models\Peran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FaseDuaAutentikasiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('FASE2_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Test integration Fase 2 hanya dijalankan pada job MySQL khusus.');
        }

        DB::beginTransaction();

        $this->beforeApplicationDestroyed(function (): void {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_administrator_dapat_masuk_dan_membuka_dashboard(): void
    {
        $response = $this->post('/masuk', [
            'nama_pengguna' => 'admin_fase2',
            'kata_sandi' => 'AdminFase2!2026',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSessionHas('id_cabang_aktif');
    }

    public function test_pengguna_nonaktif_tidak_dapat_masuk(): void
    {
        Pengguna::query()->create([
            'nama_pengguna' => 'nonaktif',
            'kata_sandi' => Hash::make('Nonaktif!2026'),
            'nama_tampilan' => 'Pengguna Nonaktif',
            'status_aktif' => 0,
            'created_at' => now(),
        ]);

        $this->post('/masuk', [
            'nama_pengguna' => 'nonaktif',
            'kata_sandi' => 'Nonaktif!2026',
        ])->assertSessionHasErrors('nama_pengguna');

        $this->assertGuest();
    }

    public function test_akun_dikunci_setelah_lima_kali_kata_sandi_salah(): void
    {
        $pengguna = $this->buatPenggunaKasir('uji_lockout');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', [
                'nama_pengguna' => $pengguna->nama_pengguna,
                'kata_sandi' => 'Salah!2026',
            ]);
        }

        $pengguna->refresh();

        $this->assertNotNull($pengguna->dikunci_sampai);
        $this->assertTrue($pengguna->dikunci_sampai->isFuture());
        $this->assertGuest();
    }

    public function test_kasir_tidak_dapat_membuka_manajemen_pengguna_melalui_url_langsung(): void
    {
        $pengguna = $this->buatPenggunaKasir('kasir_biasa');
        $cabang = Cabang::query()->firstOrFail();

        $this->actingAs($pengguna)
            ->withSession([
                'id_cabang_aktif' => $cabang->id_cabang,
                'nama_cabang_aktif' => $cabang->nama_cabang,
            ])
            ->get('/pengguna')
            ->assertForbidden();
    }

    public function test_administrator_memiliki_akses_manajemen_tanpa_permission_langsung_pengguna(): void
    {
        $admin = Pengguna::query()->where('nama_pengguna', 'admin_fase2')->firstOrFail();
        $cabang = Cabang::query()->firstOrFail();

        $this->actingAs($admin)
            ->withSession([
                'id_cabang_aktif' => $cabang->id_cabang,
                'nama_cabang_aktif' => $cabang->nama_cabang,
            ])
            ->get('/pengguna')
            ->assertOk()
            ->assertSee('Manajemen Pengguna');
    }

    private function buatPenggunaKasir(string $namaPengguna): Pengguna
    {
        $pengguna = Pengguna::query()->create([
            'nama_pengguna' => $namaPengguna,
            'kata_sandi' => Hash::make('KasirAman!2026'),
            'nama_tampilan' => 'Kasir Pengujian',
            'status_aktif' => 1,
            'created_at' => now(),
        ]);

        $peran = Peran::query()->where('kode_peran', 'KASIR')->firstOrFail();
        $cabang = Cabang::query()->firstOrFail();

        PenggunaPeran::query()->create([
            'id_pengguna' => $pengguna->id_pengguna,
            'id_peran' => $peran->id_peran,
            'id_cabang' => $cabang->id_cabang,
            'created_at' => now(),
        ]);

        return $pengguna;
    }
}
