<?php

use App\Http\Controllers\CetakDokumenController;
use App\Http\Controllers\LaporanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cabang.aktif'])->group(function (): void {
    Route::prefix('laporan')->name('laporan.')->group(function (): void {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/unduh/csv', [LaporanController::class, 'unduh'])
            ->middleware('hak.akses:LAPORAN_OPERASIONAL_UNDUH')
            ->name('unduh');
    });

    Route::get('/penjualan/transaksi/{id}/nota', [CetakDokumenController::class, 'notaPenjualan'])
        ->whereNumber('id')
        ->middleware('hak.akses:NOTA_PENJUALAN_CETAK')
        ->name('penjualan.nota');
});
