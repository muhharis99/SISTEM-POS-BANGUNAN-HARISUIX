<?php

use App\Http\Controllers\PanduanPenggunaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cabang.aktif'])->group(function (): void {
    Route::get('/panduan', [PanduanPenggunaController::class, 'index'])
        ->name('panduan.index');
});
