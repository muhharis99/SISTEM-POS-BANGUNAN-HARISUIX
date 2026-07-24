<?php

use App\Http\Controllers\InputPenjualanFinalController;
use App\Http\Controllers\PenjualanFinalController;
use App\Http\Controllers\PenjualanOperasionalFinalController;
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

    Route::post('/pengiriman', [PenjualanFinalController::class, 'simpanPengiriman'])
        ->middleware('hak.akses:PENGIRIMAN_KELOLA')
        ->name('pengiriman.simpan');
    Route::patch('/pengiriman/{id}/jadwalkan', [PenjualanFinalController::class, 'jadwalkanPengiriman'])
        ->whereNumber('id')
        ->middleware('hak.akses:PENGIRIMAN_JADWALKAN')
        ->name('pengiriman.jadwalkan');
    Route::patch('/pengiriman/{id}/berangkat', [PenjualanOperasionalFinalController::class, 'berangkatkanPengiriman'])
        ->whereNumber('id')
        ->middleware('hak.akses:PENGIRIMAN_KIRIM')
        ->name('pengiriman.berangkat');
    Route::patch('/pengiriman/{id}/terima', [PenjualanFinalController::class, 'terimaPengiriman'])
        ->whereNumber('id')
        ->middleware('hak.akses:PENGIRIMAN_TERIMA')
        ->name('pengiriman.terima');
    Route::patch('/pengiriman/{id}/gagal', [PenjualanOperasionalFinalController::class, 'gagalPengiriman'])
        ->whereNumber('id')
        ->middleware('hak.akses:PENGIRIMAN_KELOLA')
        ->name('pengiriman.gagal');
    Route::patch('/pengiriman/{id}/batalkan', [PenjualanFinalController::class, 'batalkanPengiriman'])
        ->whereNumber('id')
        ->middleware('hak.akses:PENGIRIMAN_KELOLA')
        ->name('pengiriman.batalkan');

    Route::post('/retur', [PenjualanOperasionalFinalController::class, 'simpanRetur'])
        ->middleware('hak.akses:RETUR_PENJUALAN_KELOLA')
        ->name('retur.simpan');
    Route::patch('/retur/{id}/setujui', [PenjualanOperasionalFinalController::class, 'setujuiRetur'])
        ->whereNumber('id')
        ->middleware('hak.akses:RETUR_PENJUALAN_SETUJUI')
        ->name('retur.setujui');
    Route::patch('/retur/{id}/terima', [PenjualanOperasionalFinalController::class, 'terimaRetur'])
        ->whereNumber('id')
        ->middleware('hak.akses:RETUR_PENJUALAN_TERIMA')
        ->name('retur.terima');
    Route::patch('/retur/{id}/selesai', [PenjualanOperasionalFinalController::class, 'selesaikanRetur'])
        ->whereNumber('id')
        ->middleware('hak.akses:RETUR_PENJUALAN_KELOLA')
        ->name('retur.selesai');
    Route::patch('/retur/{id}/batalkan', [PenjualanOperasionalFinalController::class, 'batalkanRetur'])
        ->whereNumber('id')
        ->middleware('hak.akses:RETUR_PENJUALAN_KELOLA')
        ->name('retur.batalkan');
});
