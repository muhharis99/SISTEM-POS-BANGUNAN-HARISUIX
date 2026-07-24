<?php

namespace Tests\Feature;

use App\Http\Controllers\InputPenjualanFinalController;
use App\Http\Controllers\PenjualanFinalController;
use Illuminate\Http\Request;
use Tests\TestCase;

class FaseTigaBelasRouteFinalTest extends TestCase
{
    public function test_seluruh_route_penjualan_berisiko_tinggi_menggunakan_controller_final(): void
    {
        $this->assertRoute('POST', '/penjualan/penawaran', InputPenjualanFinalController::class, 'simpanPenawaran');
        $this->assertRoute('POST', '/penjualan/pesanan', InputPenjualanFinalController::class, 'simpanPesanan');
        $this->assertRoute('POST', '/penjualan/transaksi', InputPenjualanFinalController::class, 'simpanPenjualan');
        $this->assertRoute('POST', '/penjualan/retur', PenjualanFinalController::class, 'simpanRetur');
        $this->assertRoute('PATCH', '/penjualan/retur/1/terima', PenjualanFinalController::class, 'terimaRetur');
        $this->assertRoute('PATCH', '/penjualan/retur/1/selesai', PenjualanFinalController::class, 'selesaikanRetur');
        $this->assertRoute('PATCH', '/penjualan/pengiriman/1/berangkat', PenjualanFinalController::class, 'berangkatkanPengiriman');
        $this->assertRoute('PATCH', '/penjualan/pengiriman/1/gagal', PenjualanFinalController::class, 'gagalPengiriman');
    }

    private function assertRoute(string $method, string $uri, string $controller, string $action): void
    {
        $route = app('router')->getRoutes()->match(Request::create($uri, $method));

        $this->assertSame($controller.'@'.$action, $route->getActionName());
    }
}
