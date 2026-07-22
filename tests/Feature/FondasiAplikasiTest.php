<?php

namespace Tests\Feature;

use Tests\TestCase;

class FondasiAplikasiTest extends TestCase
{
    public function test_halaman_utama_mengarah_ke_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
    }

    public function test_dashboard_fondasi_dapat_dibuka(): void
    {
        $response = $this->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Dashboard Fondasi')
            ->assertSee('Laravel 13')
            ->assertSee('UBold');
    }

    public function test_dashboard_memakai_asset_lokal_ubold(): void
    {
        $response = $this->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('assets/admin/css/app.min.css', false)
            ->assertSee('assets/admin/js/vendors.min.js', false)
            ->assertDontSee('cdn.jsdelivr.net', false)
            ->assertDontSee('cdnjs.cloudflare.com', false);
    }
}
