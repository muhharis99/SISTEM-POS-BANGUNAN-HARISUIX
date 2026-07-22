<?php

namespace Tests\Feature;

use Tests\TestCase;

class FondasiAplikasiTest extends TestCase
{
    public function test_halaman_utama_mengarah_ke_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/dashboard')->assertRedirect('/masuk');
    }

    public function test_halaman_masuk_memakai_asset_lokal_ubold(): void
    {
        $response = $this->get('/masuk');

        $response
            ->assertOk()
            ->assertSee('Sistem POS Toko Bangunan')
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
