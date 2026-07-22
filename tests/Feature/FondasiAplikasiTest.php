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
            ->assertDontSee('cdnjs.cloudflare.com', false)
            ->assertDontSee('fonts.googleapis.com', false)
            ->assertDontSee('fonts.gstatic.com', false);
    }

    public function test_nunito_variable_font_disajikan_secara_lokal(): void
    {
        $lokasiCss = public_path('assets/admin/css/app.min.css');
        $lokasiFontNormal = public_path('assets/admin/fonts/nunito/Nunito-Variable.woff2');
        $lokasiFontItalic = public_path('assets/admin/fonts/nunito/Nunito-Italic-Variable.woff2');

        $this->assertFileExists($lokasiCss);
        $this->assertFileExists($lokasiFontNormal);
        $this->assertFileExists($lokasiFontItalic);

        $isiCss = file_get_contents($lokasiCss);

        $this->assertIsString($isiCss);
        $this->assertStringNotContainsString('../../../../css2', $isiCss);
        $this->assertStringNotContainsString('fonts.googleapis.com', $isiCss);
        $this->assertStringNotContainsString('fonts.gstatic.com', $isiCss);
        $this->assertStringContainsString(
            'font-weight:300 700;font-display:swap;src:url("../fonts/nunito/Nunito-Variable.woff2")',
            $isiCss
        );
        $this->assertStringContainsString(
            'font-style:italic;font-weight:300 700;font-display:swap;src:url("../fonts/nunito/Nunito-Italic-Variable.woff2")',
            $isiCss
        );
    }
}
