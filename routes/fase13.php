<?php

use App\Http\Controllers\InputPenjualanFinalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cabang.aktif'])->prefix('penjualan')->name('penjualan.')->group(function (): void {
    Route::post('/penawaran', [InputPenjualanFinalController::class, 'simpanPenawaran'])
        ->middleware('hak.akses:PENAWARAN_PENJUALAN_KELOLA')
        ->name('penawaran.simpan');

    Route::post('/pesanan', [InputPenjualanFinalController::class, 'simpanPesanan'])
        ->middleware('hak.akses:PESANAN_PENJUALAN_KELOLA')
        ->name('pesanan.simpan');

    Route::post('/transaksi', [InputPenjualanFinalController::class, 'simpanPenjualan'])
        ->middleware('hak.akses:TRANSAKSI_PENJUALAN_KELOLA')
        ->name('transaksi.simpan');
});
